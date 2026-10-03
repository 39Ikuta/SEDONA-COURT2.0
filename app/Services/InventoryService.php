<?php

namespace App\Services;

use App\Models\InventoryEvent;
use App\Models\PosItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Synchronize all breakfast items with the master egg stock.
     * In Sedona PMS, all breakfast meals share the exact same inventory pool as Egg (Fried/Boiled).
     */
    public function syncBreakfastItemsStockWithEgg(?PosItem $eggItem = null): void
    {
        PosItem::clearEggCache();
        $egg = $eggItem ?? PosItem::getMasterEggItem();
        if (!$egg) {
            return;
        }

        $stock = (int) ($egg->getRawOriginal('stock_quantity') ?? 0);
        $isAvailable = $stock > 0 && (bool) $egg->getRawOriginal('is_available', true);

        // Update all items where category is 'Breakfast' or name contains 'silog' (excluding the egg itself)
        PosItem::where('id', '!=', $egg->id)
            ->where(function ($q) {
                $q->where('category', 'Breakfast')
                  ->orWhere('name', 'like', '%silog%');
            })
            ->update([
                'stock_quantity' => $stock,
                'is_available'   => $isAvailable,
            ]);

        PosItem::clearEggCache();
    }

    /**
     * Atomically decrement stock for a tracked POS item.
     * Auto-toggles item availability to false when stock hits zero.
     */
    public function atomicDecrementStock(
        int $posItemId,
        int $qty,
        string $reason = 'sold',
        ?string $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): bool {
        return DB::transaction(function () use ($posItemId, $qty, $reason, $referenceId, $userId, $notes) {
            $item = PosItem::where('id', $posItemId)->lockForUpdate()->first();
            if (!$item || !$item->is_tracked) {
                return true;
            }

            $user = $userId ? \App\Models\User::find($userId) : Auth::user();

            // Case 1: Item is a Breakfast Item (shares the exact same inventory pool as Egg)
            if ($item->isBreakfastItem()) {
                $eggItem = PosItem::where('name', 'Egg (Fried/Boiled)')
                    ->orWhere('name', 'like', '%Egg (Fried/Boiled)%')
                    ->orWhere(function ($q) {
                        $q->where('name', 'like', '%Egg%')->where('category', 'Kitchen Extras');
                    })
                    ->lockForUpdate()
                    ->first();

                if ($eggItem && $eggItem->is_tracked) {
                    $eggStock = (int) ($eggItem->getRawOriginal('stock_quantity') ?? 0);
                    $newEggStock = max(0, $eggStock - $qty);

                    $eggItem->stock_quantity = $newEggStock;
                    $eggItem->is_available = $newEggStock > 0;
                    $eggItem->save();

                    // Log recipe ingredient deduction on egg
                    InventoryEvent::create([
                        'pos_item_id'     => $eggItem->id,
                        'item_name'       => $eggItem->name,
                        'event_type'      => 'recipe_ingredient',
                        'quantity_change' => -$qty,
                        'balance_after'   => $newEggStock,
                        'reference_id'    => $referenceId,
                        'user_id'         => $user?->id,
                        'operator_name'   => $user?->name ?? 'Kitchen Recipe System',
                        'notes'           => "Shared breakfast egg deduction: {$qty}x egg used for {$qty}x {$item->name}" . ($notes ? " ({$notes})" : ""),
                    ]);

                    // Sync ordered item
                    $item->stock_quantity = $newEggStock;
                    $item->is_available = $newEggStock > 0;
                    $item->save();

                    // Log sold event on breakfast item
                    InventoryEvent::create([
                        'pos_item_id'     => $item->id,
                        'item_name'       => $item->name,
                        'event_type'      => $reason,
                        'quantity_change' => -$qty,
                        'balance_after'   => $newEggStock,
                        'reference_id'    => $referenceId,
                        'user_id'         => $user?->id,
                        'operator_name'   => $user?->name ?? 'System Cashier',
                        'notes'           => $notes ?? "Stock decreased by {$qty} via {$reason} (shared egg inventory)",
                    ]);

                    // Synchronize ALL other breakfast items
                    $this->syncBreakfastItemsStockWithEgg($eggItem);

                    return true;
                }
            }

            // Case 2: Master Egg Item ordered directly
            if ($item->isEggItem()) {
                $currentStock = (int) ($item->getRawOriginal('stock_quantity') ?? 0);
                $newStock = max(0, $currentStock - $qty);

                $item->stock_quantity = $newStock;
                $item->is_available = $newStock > 0;
                $item->save();

                InventoryEvent::create([
                    'pos_item_id'     => $item->id,
                    'item_name'       => $item->name,
                    'event_type'      => $reason,
                    'quantity_change' => -$qty,
                    'balance_after'   => $newStock,
                    'reference_id'    => $referenceId,
                    'user_id'         => $user?->id,
                    'operator_name'   => $user?->name ?? 'System Cashier',
                    'notes'           => $notes ?? "Stock decreased by {$qty} via {$reason}",
                ]);

                // Synchronize all breakfast items
                $this->syncBreakfastItemsStockWithEgg($item);

                return true;
            }

            // Case 3: Standard item or non-breakfast egg-dependent item (e.g. Calamares, Sisig w/ Egg)
            $currentStock = (int) ($item->getRawOriginal('stock_quantity') ?? 0);
            $newStock = max(0, $currentStock - $qty);

            $item->stock_quantity = $newStock;
            $item->is_available = $newStock > 0;
            $item->save();

            InventoryEvent::create([
                'pos_item_id'     => $item->id,
                'item_name'       => $item->name,
                'event_type'      => $reason,
                'quantity_change' => -$qty,
                'balance_after'   => $newStock,
                'reference_id'    => $referenceId,
                'user_id'         => $user?->id,
                'operator_name'   => $user?->name ?? 'System Cashier',
                'notes'           => $notes ?? "Stock decreased by {$qty} via {$reason}",
            ]);

            // If this non-breakfast item consumes egg (Calamares, Sisig w/ Egg):
            if (self::requiresEggDeduction($item->name)) {
                $eggItem = $this->deductRelationalEggStock($item, $qty, $referenceId, $user?->id, $notes);
                if ($eggItem) {
                    $this->syncBreakfastItemsStockWithEgg($eggItem);
                }
            }

            return true;
        });
    }

    /**
     * Check if a dish requires relational deduction of egg inventory.
     * Matches dishes containing 'silog', 'w/ egg', or 'calamares' (case-insensitive),
     * while excluding the egg item itself.
     */
    public static function requiresEggDeduction(string $itemName): bool
    {
        $name = strtolower(trim($itemName));

        // Avoid infinite loop or self-deduction if the ordered item is the egg itself
        if ($name === 'egg (fried/boiled)' || $name === 'egg' || str_starts_with($name, 'egg (')) {
            return false;
        }

        // 1. Matches silog (e.g. Bangsilog, Porksilog, Chicksilog, Tapsilog, Longsilog, Hotsilog)
        if (str_contains($name, 'silog')) {
            return true;
        }

        // 2. Matches 'w/ egg' or 'with egg' (e.g. Sizzling Sisig w/ Egg)
        if (str_contains($name, 'w/ egg') || str_contains($name, 'w/egg') || str_contains($name, 'with egg')) {
            return true;
        }

        // 3. Matches 'calamares' (e.g. Calamares)
        if (str_contains($name, 'calamares')) {
            return true;
        }

        return false;
    }

    /**
     * Deduct egg stock relationally (1 egg per unit) for composite dishes.
     */
    public function deductRelationalEggStock(
        PosItem $dishItem,
        int $qty,
        ?string $referenceId = null,
        ?int $userId = null,
        ?string $notes = null
    ): ?PosItem {
        $eggItem = PosItem::where('name', 'Egg (Fried/Boiled)')
            ->orWhere('name', 'like', '%Egg (Fried/Boiled)%')
            ->orWhere(function ($q) {
                $q->where('name', 'like', '%Egg%')->where('category', 'Kitchen Extras');
            })
            ->lockForUpdate()
            ->first();

        if (!$eggItem || $eggItem->id === $dishItem->id || !$eggItem->is_tracked) {
            return null;
        }

        $currentStock = (int) ($eggItem->getRawOriginal('stock_quantity') ?? 0);
        $newStock = max(0, $currentStock - $qty);

        $eggItem->stock_quantity = $newStock;
        $eggItem->is_available = $newStock > 0;
        $eggItem->save();

        $user = $userId ? \App\Models\User::find($userId) : Auth::user();

        InventoryEvent::create([
            'pos_item_id'     => $eggItem->id,
            'item_name'       => $eggItem->name,
            'event_type'      => 'recipe_ingredient',
            'quantity_change' => -$qty,
            'balance_after'   => $newStock,
            'reference_id'    => $referenceId,
            'user_id'         => $user?->id,
            'operator_name'   => $user?->name ?? 'Kitchen Recipe System',
            'notes'           => "Recipe ingredient deduction: {$qty}x egg used for {$qty}x {$dishItem->name}" . ($notes ? " ({$notes})" : ""),
        ]);

        $this->syncBreakfastItemsStockWithEgg($eggItem);

        return $eggItem;
    }

    /**
     * Set exact stock count for a single item (manual adjustment or restock).
     */
    public function setStockCount(
        int $posItemId,
        int $newCount,
        string $reason = 'adjustment',
        ?string $notes = null,
        ?int $userId = null
    ): PosItem {
        return DB::transaction(function () use ($posItemId, $newCount, $reason, $notes, $userId) {
            $item = PosItem::where('id', $posItemId)->lockForUpdate()->firstOrFail();
            $user = $userId ? \App\Models\User::find($userId) : Auth::user();

            // If adjusting a breakfast item, synchronize it with the Master Egg
            if ($item->isBreakfastItem()) {
                $eggItem = PosItem::getMasterEggItem();
                if ($eggItem) {
                    $eggItem = PosItem::where('id', $eggItem->id)->lockForUpdate()->first();
                    $prevEggStock = (int) ($eggItem->getRawOriginal('stock_quantity') ?? 0);
                    $diff = $newCount - $prevEggStock;

                    $eggItem->stock_quantity = max(0, $newCount);
                    $eggItem->is_available = $newCount > 0;
                    $eggItem->save();

                    InventoryEvent::create([
                        'pos_item_id'     => $eggItem->id,
                        'item_name'       => $eggItem->name,
                        'event_type'      => $reason,
                        'quantity_change' => $diff,
                        'balance_after'   => $eggItem->stock_quantity,
                        'user_id'         => $user?->id,
                        'operator_name'   => $user?->name ?? 'Duty Operator',
                        'notes'           => $notes ?? "Egg stock adjusted to {$newCount} via {$item->name} ({$reason})",
                    ]);

                    $this->syncBreakfastItemsStockWithEgg($eggItem);
                    return $item->fresh();
                }
            }

            $prevStock = (int) ($item->getRawOriginal('stock_quantity') ?? 0);
            $diff = $newCount - $prevStock;

            $item->stock_quantity = max(0, $newCount);
            $item->is_available = $newCount > 0;
            $item->save();

            InventoryEvent::create([
                'pos_item_id'     => $item->id,
                'item_name'       => $item->name,
                'event_type'      => $reason,
                'quantity_change' => $diff,
                'balance_after'   => $item->stock_quantity,
                'user_id'         => $user?->id,
                'operator_name'   => $user?->name ?? 'Duty Operator',
                'notes'           => $notes ?? "Stock set from {$prevStock} to {$newCount} ({$reason})",
            ]);

            // If master egg was adjusted, synchronize all breakfast items
            if ($item->isEggItem()) {
                $this->syncBreakfastItemsStockWithEgg($item);
            }

            return $item;
        });
    }

    /**
     * Batch recount all items (Cashier Shift Inventory Recount).
     */
    public function batchRecount(array $counts, ?int $userId = null, ?string $notes = null): int
    {
        $updatedCount = 0;
        foreach ($counts as $itemId => $count) {
            if ($count === null || $count === '') {
                continue;
            }
            $this->setStockCount((int) $itemId, (int) $count, 'recount', $notes, $userId);
            $updatedCount++;
        }

        $this->syncBreakfastItemsStockWithEgg();

        return $updatedCount;
    }

    /**
     * Export Inventory Movement Ledger as CSV.
     */
    public function generateMovementCsv(): string
    {
        $events = InventoryEvent::with('posItem')->latest()->get();
        $output = fopen('php://temp', 'r+');

        fputcsv($output, [
            'ID', 'Date Time', 'Item Name', 'Category', 'Event Type',
            'Quantity Change', 'Balance After', 'Reference ID', 'Operator', 'Notes'
        ]);

        foreach ($events as $e) {
            fputcsv($output, [
                $e->id,
                $e->created_at->format('Y-m-d H:i:s'),
                $e->item_name,
                $e->posItem->category ?? 'N/A',
                strtoupper($e->event_type),
                $e->quantity_change > 0 ? "+{$e->quantity_change}" : $e->quantity_change,
                $e->balance_after,
                $e->reference_id ?? '—',
                $e->operator_name ?? 'Staff',
                $e->notes ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
