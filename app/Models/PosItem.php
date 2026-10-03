<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosItem extends Model
{
    protected $table = 'pos_items';

    protected $fillable = [
        'name', 'category', 'price',
        'stock_quantity', 'is_tracked', 'reorder_level',
        'kitchen_hours_only', 'is_available', 'sort_order',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'stock_quantity'      => 'integer',
        'is_tracked'          => 'boolean',
        'reorder_level'       => 'integer',
        'kitchen_hours_only'  => 'boolean',
        'is_available'        => 'boolean',
    ];

    protected static ?PosItem $cachedEgg = null;

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Determine if this item is the Master Egg item (Egg (Fried/Boiled)).
     */
    public function isEggItem(): bool
    {
        $name = strtolower(trim($this->name ?? ''));
        return $name === 'egg (fried/boiled)' 
            || $name === 'egg' 
            || str_starts_with($name, 'egg (')
            || (str_contains($name, 'egg') && strtolower(trim($this->category ?? '')) === 'kitchen extras');
    }

    /**
     * Determine if this item is an egg-limited breakfast meal (Silog or Breakfast category).
     */
    public function isBreakfastItem(): bool
    {
        if ($this->isEggItem()) {
            return false;
        }

        $category = strtolower(trim($this->category ?? ''));
        $name = strtolower(trim($this->name ?? ''));

        return $category === 'breakfast' || str_contains($name, 'silog');
    }

    /**
     * Retrieve the master egg item used for breakfast pooling.
     */
    public static function getMasterEggItem(): ?self
    {
        if (static::$cachedEgg !== null && static::$cachedEgg->exists) {
            return static::$cachedEgg;
        }

        return static::$cachedEgg = static::where('name', 'Egg (Fried/Boiled)')
            ->orWhere('name', 'like', '%Egg (Fried/Boiled)%')
            ->orWhere(function ($q) {
                $q->where('name', 'like', '%Egg%')->where('category', 'Kitchen Extras');
            })
            ->first();
    }

    /**
     * Clear master egg cached instance.
     */
    public static function clearEggCache(): void
    {
        static::$cachedEgg = null;
    }

    /**
     * Dynamically resolve stock quantity.
     * All breakfast meals share the exact same inventory pool as Egg (Fried/Boiled).
     */
    public function getStockQuantityAttribute($value)
    {
        if ($this->isBreakfastItem()) {
            $egg = static::getMasterEggItem();
            if ($egg && $egg->id !== $this->id) {
                return (int) $egg->getRawOriginal('stock_quantity', $value);
            }
        }

        return $value !== null ? (int) $value : 0;
    }

    /**
     * Dynamically resolve item availability.
     * If eggs are exhausted (stock <= 0) or unavailable, all breakfast items are unavailable.
     */
    public function getIsAvailableAttribute($value)
    {
        if ($this->isBreakfastItem()) {
            $egg = static::getMasterEggItem();
            if ($egg && $egg->id !== $this->id) {
                $eggStock = (int) $egg->getRawOriginal('stock_quantity', 0);
                $eggAvailable = (bool) $egg->getRawOriginal('is_available', true);
                if ($eggStock <= 0 || !$eggAvailable) {
                    return false;
                }
            }
        }

        return (bool) $value;
    }

    /**
     * Check if this item can be ordered right now based on kitchen hours.
     */
    public function isOrderable(): bool
    {
        if (!$this->is_available) return false;
        if (!$this->kitchen_hours_only) return true;

        $hour = (int) now()->format('G');
        return $hour >= 6 && $hour < 22; // 6:00 AM – 10:00 PM
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->orderBy('sort_order');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

