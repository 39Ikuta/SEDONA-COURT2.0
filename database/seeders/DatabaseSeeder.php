<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosItem;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with Sedona Court Executive PMS dataset.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 1. Create Permissions ─────────────────────────────────────
        $permissions = [
            'view_dashboard', 'manage_rooms', 'manage_guests', 'manage_bookings',
            'manage_folios', 'manage_pos', 'manage_shifts', 'view_reports',
            'manage_users', 'force_checkout', 'apply_discounts', 'view_kds',
            'view_kiosk', 'manage_pricing', 'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // ── 2. Create 5 Discrete Roles ───────────────────────────────
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions(Permission::all());

        $ownerRole = Role::findOrCreate('owner', 'web');
        $ownerRole->syncPermissions(Permission::all());

        $cashierRole = Role::findOrCreate('cashier', 'web');
        $cashierRole->syncPermissions([
            'view_dashboard', 'manage_rooms', 'manage_guests', 'manage_folios',
            'manage_pos', 'manage_shifts', 'apply_discounts', 'manage_bookings',
        ]);

        $kitchenRole = Role::findOrCreate('kitchen', 'web');
        $kitchenRole->syncPermissions([
            'view_kds', 'manage_pos',
        ]);

        $kioskRole = Role::findOrCreate('customer_display', 'web');
        $kioskRole->syncPermissions([
            'view_kiosk',
        ]);

        // Legacy role aliases for backward compatibility
        $managerRole = Role::findOrCreate('manager', 'web');
        $managerRole->syncPermissions(Permission::all());

        $frontDeskRole = Role::findOrCreate('front_desk', 'web');
        $frontDeskRole->syncPermissions($cashierRole->permissions);

        // ── 3. Seed 10 Pre-Seeded User Accounts ───────────────────────
        $accounts = [
            [
                'email' => 'kitchen1@sedonapms.com',
                'name' => 'SCTI Kitchen Staff',
                'role' => 'kitchen',
            ],
            [
                'email' => 'pau@sedonapms.com',
                'name' => 'Pau (Cashier)',
                'role' => 'cashier',
            ],
            [
                'email' => 'raquel@sedonapms.com',
                'name' => 'Raquel (Cashier)',
                'role' => 'cashier',
            ],
            [
                'email' => 'tuter@sedonapms.com',
                'name' => 'Tuter (Cashier)',
                'role' => 'cashier',
            ],
            [
                'email' => 'ann@sedonapms.com',
                'name' => 'Ann (Cashier)',
                'role' => 'cashier',
            ],
            [
                'email' => 'admin1@sedonapms.com',
                'name' => 'Alex (Admin 1)',
                'role' => 'admin',
            ],
            [
                'email' => 'admin2@sedonapms.com',
                'name' => 'Chris (Admin 2)',
                'role' => 'admin',
            ],
            [
                'email' => 'admin@sedonapms.com',
                'name' => 'Admin Terminal',
                'role' => 'admin',
            ],
            [
                'email' => 'owner@sedonapms.com',
                'name' => 'Sedona Owner',
                'role' => 'owner',
            ],
            [
                'email' => 'kiosk@sedonapms.com',
                'name' => 'Lobby Display Kiosk',
                'role' => 'customer_display',
            ],
            // Additional convenience accounts
            [
                'email' => 'frontdesk@sedonapms.com',
                'name' => 'Maria Santos (Front Desk)',
                'role' => 'cashier',
            ],
            [
                'email' => 'agbayaniangel388@gmail.com',
                'name' => 'barbara (BSS)',
                'role' => 'cashier',
            ],
        ];

        $usersByEmail = [];
        foreach ($accounts as $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$acc['role']]);
            $usersByEmail[$acc['email']] = $user;
        }

        $defaultCashier = $usersByEmail['pau@sedonapms.com'] ?? $usersByEmail['frontdesk@sedonapms.com'];

        // ── 4. Seed Exact Physical Room Inventory (32 Rooms) ──────────
        // Clean legacy cleaning states
        Room::where('status', 'cleaning')->update(['status' => 'available']);

        $roomsData = [
            // FLOOR 1 (11 Rooms)
            // Rooms 1 - 5: VIP Suite
            ['number' => '1', 'floor' => 1, 'name' => 'VIP Suite 1', 'type' => 'VIP Suite Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 695, 'base_rate_6h' => 1090, 'base_rate_12h' => 1395, 'base_rate_24h' => 2500, 'base_rate_promo' => 1155],
            ['number' => '2', 'floor' => 1, 'name' => 'VIP Suite 2', 'type' => 'VIP Suite Room', 'status' => 'occupied', 'is_staff_quarters' => false, 'base_rate_3h' => 695, 'base_rate_6h' => 1090, 'base_rate_12h' => 1395, 'base_rate_24h' => 2500, 'base_rate_promo' => 1155],
            ['number' => '3', 'floor' => 1, 'name' => 'VIP Suite 3', 'type' => 'VIP Suite Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 695, 'base_rate_6h' => 1090, 'base_rate_12h' => 1395, 'base_rate_24h' => 2500, 'base_rate_promo' => 1155],
            ['number' => '4', 'floor' => 1, 'name' => 'VIP Suite 4', 'type' => 'VIP Suite Room', 'status' => 'occupied', 'is_staff_quarters' => false, 'base_rate_3h' => 695, 'base_rate_6h' => 1090, 'base_rate_12h' => 1395, 'base_rate_24h' => 2500, 'base_rate_promo' => 1155],
            ['number' => '5', 'floor' => 1, 'name' => 'VIP Suite 5', 'type' => 'VIP Suite Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 695, 'base_rate_6h' => 1090, 'base_rate_12h' => 1395, 'base_rate_24h' => 2500, 'base_rate_promo' => 1155],
            // Rooms 6 - 11: Classic Room
            ['number' => '6', 'floor' => 1, 'name' => 'Classic Room 6', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '7', 'floor' => 1, 'name' => 'Classic Room 7', 'type' => 'Classic Room', 'status' => 'occupied', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '8', 'floor' => 1, 'name' => 'Classic Room 8', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '9', 'floor' => 1, 'name' => 'Classic Room 9', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '10', 'floor' => 1, 'name' => 'Classic Room 10', 'type' => 'Classic Room', 'status' => 'occupied', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '11', 'floor' => 1, 'name' => 'Classic Room 11', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],

            // FLOOR 2 (15 Rooms)
            // Room 12: Staff House
            ['number' => '12', 'floor' => 2, 'name' => 'Staff House (Quarters)', 'type' => 'Staff House', 'status' => 'available', 'is_staff_quarters' => true, 'base_rate_3h' => 0, 'base_rate_6h' => 0, 'base_rate_12h' => 0, 'base_rate_24h' => 0, 'base_rate_promo' => 0],
            // Rooms 13 - 26: Premium Room
            ['number' => '13', 'floor' => 2, 'name' => 'Premium Room 13', 'type' => 'Premium Room', 'status' => 'occupied', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '14', 'floor' => 2, 'name' => 'Premium Room 14', 'type' => 'Premium Room', 'status' => 'occupied', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '15', 'floor' => 2, 'name' => 'Premium Room 15', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '16', 'floor' => 2, 'name' => 'Premium Room 16', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '17', 'floor' => 2, 'name' => 'Premium Room 17', 'type' => 'Premium Room', 'status' => 'occupied', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '18', 'floor' => 2, 'name' => 'Premium Room 18', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '19', 'floor' => 2, 'name' => 'Premium Room 19', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '20', 'floor' => 2, 'name' => 'Premium Room 20', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '21', 'floor' => 2, 'name' => 'Premium Room 21', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '22', 'floor' => 2, 'name' => 'Premium Room 22', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '23', 'floor' => 2, 'name' => 'Premium Room 23', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '24', 'floor' => 2, 'name' => 'Premium Room 24', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '25', 'floor' => 2, 'name' => 'Premium Room 25', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],
            ['number' => '26', 'floor' => 2, 'name' => 'Premium Room 26', 'type' => 'Premium Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 495, 'base_rate_6h' => 890, 'base_rate_12h' => 1295, 'base_rate_24h' => 2300, 'base_rate_promo' => 1055],

            // FLOOR 3 (6 Rooms)
            // Rooms 27 - 32: Classic Room
            ['number' => '27', 'floor' => 3, 'name' => 'Classic Room 27', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '28', 'floor' => 3, 'name' => 'Classic Room 28', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '29', 'floor' => 3, 'name' => 'Classic Room 29', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '30', 'floor' => 3, 'name' => 'Classic Room 30', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '31', 'floor' => 3, 'name' => 'Classic Room 31', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
            ['number' => '32', 'floor' => 3, 'name' => 'Classic Room 32', 'type' => 'Classic Room', 'status' => 'available', 'is_staff_quarters' => false, 'base_rate_3h' => 395, 'base_rate_6h' => 790, 'base_rate_12h' => 1195, 'base_rate_24h' => 2100, 'base_rate_promo' => 995],
        ];

        // Clean up legacy 12 A / 12 B if exists
        Room::whereIn('number', ['12 A', '12 B'])->delete();

        foreach ($roomsData as $r) {
            Room::updateOrCreate(['number' => $r['number']], $r);
        }

        // ── 5. Seed Complete Food, Beverage, Misc & Services Master Catalog ──
        $catalog = [
            // A. All Day Breakfast
            ['name' => 'Bangsilog', 'category' => 'Breakfast', 'price' => 150.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 1],
            ['name' => 'Porksilog', 'category' => 'Breakfast', 'price' => 150.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 2],
            ['name' => 'Chicksilog', 'category' => 'Breakfast', 'price' => 150.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 3],
            ['name' => 'Tapsilog', 'category' => 'Breakfast', 'price' => 160.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 4],
            ['name' => 'Longsilog', 'category' => 'Breakfast', 'price' => 150.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 5],
            ['name' => 'Hotsilog', 'category' => 'Breakfast', 'price' => 130.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 6],

            // B. All Time Favorites (Hot Kitchen)
            ['name' => 'Calamares', 'category' => 'Favorites', 'price' => 180.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 10],
            ['name' => 'Lechon Kawali', 'category' => 'Favorites', 'price' => 230.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 11],
            ['name' => 'Chicharong Bulaklak', 'category' => 'Favorites', 'price' => 200.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 12],
            ['name' => 'Buffalo Wings', 'category' => 'Favorites', 'price' => 230.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 13],
            ['name' => 'Buttered Chicken', 'category' => 'Favorites', 'price' => 230.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 14],
            ['name' => 'Garlic Chicken', 'category' => 'Favorites', 'price' => 230.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 15],
            ['name' => "Tokwa't Baboy", 'category' => 'Favorites', 'price' => 140.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 16],
            ['name' => 'Sizzling Tofu', 'category' => 'Favorites', 'price' => 140.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 17],
            ['name' => 'Sizzling Sisig w/ Egg', 'category' => 'Favorites', 'price' => 230.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 18],
            ['name' => 'Sizzling Hotdog', 'category' => 'Favorites', 'price' => 150.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 19],
            ['name' => 'Pancit Canton', 'category' => 'Favorites', 'price' => 130.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 20],
            ['name' => 'Lomi', 'category' => 'Favorites', 'price' => 130.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 21],
            ['name' => 'French Fries', 'category' => 'Favorites', 'price' => 90.00, 'kitchen_hours_only' => true, 'is_available' => true, 'sort_order' => 22],

            // C. Kitchen Extras
            ['name' => 'Plain Rice', 'category' => 'Kitchen Extras', 'price' => 30.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 30],
            ['name' => 'Garlic Rice', 'category' => 'Kitchen Extras', 'price' => 40.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 31],
            ['name' => 'Egg (Fried/Boiled)', 'category' => 'Kitchen Extras', 'price' => 20.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 32],
            ['name' => 'Ice Bucket', 'category' => 'Kitchen Extras', 'price' => 30.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 33],
            ['name' => 'Hot Water (Thermos)', 'category' => 'Kitchen Extras', 'price' => 20.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 34],

            // D. 24/7 Beverages & Refreshments
            ['name' => 'Coke Regular (320ml can)', 'category' => 'Drinks', 'price' => 60.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 40],
            ['name' => 'Coke Zero Sugar (320ml can)', 'category' => 'Drinks', 'price' => 60.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 41],
            ['name' => 'Sprite Lemon-Lime (320ml can)', 'category' => 'Drinks', 'price' => 60.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 42],
            ['name' => 'Royal Tru-Orange (320ml can)', 'category' => 'Drinks', 'price' => 60.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 43],
            ['name' => 'Pineapple Juice (Del Monte can)', 'category' => 'Drinks', 'price' => 60.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 44],
            ['name' => 'C2 Green Tea Apple (500ml)', 'category' => 'Drinks', 'price' => 60.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 45],
            ['name' => 'Mineral Water (Purified 500ml)', 'category' => 'Drinks', 'price' => 25.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 46],
            ['name' => 'Coffee Sachet (Brown/Blanca)', 'category' => 'Drinks', 'price' => 25.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 47],
            ['name' => 'Milo Chocolate Malt Hot Cup', 'category' => 'Drinks', 'price' => 25.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 48],
            ['name' => 'San Miguel Pale Pilsen (330ml)', 'category' => 'Drinks', 'price' => 90.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 49],
            ['name' => 'San Mig Light Beer (330ml)', 'category' => 'Drinks', 'price' => 90.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 50],
            ['name' => 'Red Horse Extra Strong (330ml)', 'category' => 'Drinks', 'price' => 90.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 51],

            // E. Miscellaneous Snacks, Tobacco & Toiletries
            ['name' => 'Cup Noodles - Beef (Nissin)', 'category' => 'Miscellaneous', 'price' => 60.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 60],
            ['name' => 'Cup Noodles - Bulalo (Nissin)', 'category' => 'Miscellaneous', 'price' => 60.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 61],
            ['name' => 'Cup Noodles - Seafood (Nissin)', 'category' => 'Miscellaneous', 'price' => 60.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 62],
            ['name' => 'Piattos Potato Chips (40g)', 'category' => 'Miscellaneous', 'price' => 45.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 63],
            ['name' => 'Nova Multigrain Chips (40g)', 'category' => 'Miscellaneous', 'price' => 45.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 64],
            ['name' => 'Pic-A Snack Mix (40g)', 'category' => 'Miscellaneous', 'price' => 45.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 65],
            ['name' => 'V-Cut Ridged Chips (40g)', 'category' => 'Miscellaneous', 'price' => 45.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 66],
            ['name' => 'Halls / Snowbear Candy Pack', 'category' => 'Miscellaneous', 'price' => 20.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 67],
            ['name' => 'Marlboro Red (20s Pack)', 'category' => 'Miscellaneous', 'price' => 235.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 68],
            ['name' => 'Marlboro Lights / Gold (20s)', 'category' => 'Miscellaneous', 'price' => 235.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 69],
            ['name' => 'Disposable Gas Lighter', 'category' => 'Miscellaneous', 'price' => 35.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 70],
            ['name' => 'Lubricated Condoms (Pack of 3)', 'category' => 'Miscellaneous', 'price' => 65.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 71],
            ['name' => 'Sanitary Napkin (Pads w/ Wings)', 'category' => 'Miscellaneous', 'price' => 25.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 72],
            ['name' => 'Pantiliners Pack', 'category' => 'Miscellaneous', 'price' => 20.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 73],
            ['name' => 'Feminine Wash Travel Sachet', 'category' => 'Miscellaneous', 'price' => 25.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 74],
            ['name' => 'Tissue Roll (2-Ply Bathroom)', 'category' => 'Miscellaneous', 'price' => 25.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 75],
            ['name' => 'Shaving Kit (Twin Blade Razor)', 'category' => 'Miscellaneous', 'price' => 25.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 76],
            ['name' => 'Safeguard Soap (60g White Bar)', 'category' => 'Miscellaneous', 'price' => 35.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 77],
            ['name' => 'Shampoo Sachet (Revitalizing)', 'category' => 'Miscellaneous', 'price' => 25.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 78],
            ['name' => 'Conditioner Sachet', 'category' => 'Miscellaneous', 'price' => 25.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 79],
            ['name' => 'Colgate Toothpaste Sachet', 'category' => 'Miscellaneous', 'price' => 30.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 80],
            ['name' => 'Travel Manual Toothbrush', 'category' => 'Miscellaneous', 'price' => 40.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 81],

            // F. Bedding, Linens & Custom Billable Services
            ['name' => 'Extra Person Charge', 'category' => 'Services', 'price' => 150.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 90],
            ['name' => 'Extra Single Bed (Mattress)', 'category' => 'Services', 'price' => 250.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 91],
            ['name' => 'Clean Bed Sheet (Single/Double)', 'category' => 'Services', 'price' => 150.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 92],
            ['name' => 'Complete Extra Bed Set (Bed+Linen)', 'category' => 'Services', 'price' => 500.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 93],
            ['name' => 'Extra Fluffy Pillow', 'category' => 'Services', 'price' => 200.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 94],
            ['name' => 'Fresh Pillow Case', 'category' => 'Services', 'price' => 100.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 95],
            ['name' => 'Thermal Warm Blanket', 'category' => 'Services', 'price' => 100.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 96],
            ['name' => 'Plush Bath Towel', 'category' => 'Services', 'price' => 100.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 97],
            ['name' => 'Guest Amenity Hygiene Kit', 'category' => 'Services', 'price' => 50.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 98],
            ['name' => 'Complete Fresh Beddings Pack', 'category' => 'Services', 'price' => 200.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 99],
            ['name' => 'Regular Laundry Service (Per kg)', 'category' => 'Services', 'price' => 120.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 100],
            ['name' => 'Swedish Massage (60 mins Spa)', 'category' => 'Services', 'price' => 600.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 101],
            ['name' => 'Late Checkout / Excess Hour', 'category' => 'Services', 'price' => 130.00, 'kitchen_hours_only' => false, 'is_available' => true, 'sort_order' => 102],
        ];

        foreach ($catalog as $item) {
            PosItem::updateOrCreate(['name' => $item['name']], $item);
        }

        // Synchronize all breakfast meals with master egg stock
        app(\App\Services\InventoryService::class)->syncBreakfastItemsStockWithEgg();


        // ── 6. Seed Guests & Occupied Folios ─────────────────────────
        $guest1 = Guest::updateOrCreate(['contact' => '09171111111'], ['name' => 'Juan Dela Cruz', 'headcount' => 2, 'is_senior' => false, 'is_pwd' => false]);
        $guest2 = Guest::updateOrCreate(['contact' => '09172222222'], ['name' => 'Roberto Santos', 'headcount' => 2, 'is_senior' => false, 'is_pwd' => false]);
        $guest3 = Guest::updateOrCreate(['contact' => '09173333333'], ['name' => 'Elena Bautista', 'headcount' => 2, 'is_senior' => false, 'is_pwd' => false]);
        $guest4 = Guest::updateOrCreate(['contact' => '09174444444'], ['name' => 'Carlos Mendoza', 'headcount' => 1, 'is_senior' => false, 'is_pwd' => false]);
        $guest5 = Guest::updateOrCreate(['contact' => '09175555555'], ['name' => 'Antonio Ramos', 'headcount' => 2, 'is_senior' => false, 'is_pwd' => false]);
        $guest6 = Guest::updateOrCreate(['contact' => '09176666666'], ['name' => 'Grace Lim', 'headcount' => 2, 'is_senior' => false, 'is_pwd' => false]);
        $guest7 = Guest::updateOrCreate(['contact' => '09177777777'], ['name' => 'Michael Tan', 'headcount' => 2, 'is_senior' => false, 'is_pwd' => false]);

        // Room 13 (Premium, Overdue by 3 hours, Rate: 495, 3 excess hrs * 130 = 390, Total: 885)
        $r13 = Room::where('number', '13')->first();
        if ($r13) {
            Folio::updateOrCreate(
                ['transaction_id' => 'SCTI-042880'],
                [
                    'room_id' => $r13->id,
                    'guest_id' => $guest1->id,
                    'user_id' => $defaultCashier->id,
                    'rate_tier' => '3h',
                    'checked_in_at' => now()->subHours(6),
                    'expected_checkout_at' => now()->subHours(3),
                    'extra_hours' => 3,
                    'status' => 'active',
                    'room_charge' => 495.00,
                    'surcharge_total' => 390.00,
                    'pos_total' => 0.00,
                    'gross_total' => 885.00,
                    'discount_amount' => 0.00,
                    'senior_pwd_discount' => false,
                    'net_total' => 885.00,
                    'payment_method' => 'cash',
                ]
            );
        }

        // Room 7 (Classic, 12h, with F&B items: 2x Coffee, 1x Tapsilog, 1x Mineral Water)
        $r7 = Room::where('number', '7')->first();
        if ($r7) {
            $folio7 = Folio::updateOrCreate(
                ['transaction_id' => 'SCTI-056191'],
                [
                    'room_id' => $r7->id,
                    'guest_id' => $guest2->id,
                    'user_id' => $defaultCashier->id,
                    'rate_tier' => '12h',
                    'checked_in_at' => now()->subHours(3),
                    'expected_checkout_at' => now()->addHours(9),
                    'extra_hours' => 0,
                    'status' => 'active',
                    'pos_items' => [
                        ['name' => 'Coffee Sachet (Brown/Blanca)', 'qty' => 2, 'price' => 25, 'subtotal' => 50],
                        ['name' => 'Tapsilog', 'qty' => 1, 'price' => 160, 'subtotal' => 160],
                        ['name' => 'Mineral Water (Purified 500ml)', 'qty' => 1, 'price' => 25, 'subtotal' => 25],
                    ],
                    'room_charge' => 1195.00,
                    'surcharge_total' => 0.00,
                    'pos_total' => 235.00,
                    'gross_total' => 1430.00,
                    'discount_amount' => 0.00,
                    'senior_pwd_discount' => false,
                    'net_total' => 1430.00,
                    'payment_method' => 'cash',
                ]
            );

            // Add Kitchen Order for Room 7
            $coffee = PosItem::where('name', 'Coffee Sachet (Brown/Blanca)')->first();
            $tapsilog = PosItem::where('name', 'Tapsilog')->first();
            $water = PosItem::where('name', 'Mineral Water (Purified 500ml)')->first();
            if ($coffee && $tapsilog && $water) {
                $order7 = Order::updateOrCreate(
                    ['transaction_id' => 'ORD-07-01'],
                    [
                        'user_id' => $defaultCashier->id,
                        'folio_id' => $folio7->id,
                        'room_id' => $r7->id,
                        'type' => 'room_charge',
                        'status' => 'preparing',
                        'total' => 235.00,
                        'payment_method' => 'charge_to_room',
                    ]
                );
                OrderItem::updateOrCreate(['order_id' => $order7->id, 'pos_item_id' => $coffee->id], ['item_name' => $coffee->name, 'unit_price' => 25, 'quantity' => 2, 'subtotal' => 50]);
                OrderItem::updateOrCreate(['order_id' => $order7->id, 'pos_item_id' => $tapsilog->id], ['item_name' => $tapsilog->name, 'unit_price' => 160, 'quantity' => 1, 'subtotal' => 160]);
                OrderItem::updateOrCreate(['order_id' => $order7->id, 'pos_item_id' => $water->id], ['item_name' => $water->name, 'unit_price' => 25, 'quantity' => 1, 'subtotal' => 25]);
            }
        }

        // Room 4 (VIP Suite, 3h)
        $r4 = Room::where('number', '4')->first();
        if ($r4) {
            Folio::updateOrCreate(
                ['transaction_id' => 'SCTI-000004'],
                [
                    'room_id' => $r4->id,
                    'guest_id' => $guest3->id,
                    'user_id' => $defaultCashier->id,
                    'rate_tier' => '3h',
                    'checked_in_at' => now()->subMinutes(90),
                    'expected_checkout_at' => now()->addMinutes(90),
                    'status' => 'active',
                    'room_charge' => 695.00,
                    'net_total' => 695.00,
                    'gross_total' => 695.00,
                ]
            );
        }

        // Room 10 (Classic, 3h)
        $r10 = Room::where('number', '10')->first();
        if ($r10) {
            Folio::updateOrCreate(
                ['transaction_id' => 'SCTI-000010'],
                [
                    'room_id' => $r10->id,
                    'guest_id' => $guest4->id,
                    'user_id' => $defaultCashier->id,
                    'rate_tier' => '3h',
                    'checked_in_at' => now()->subMinutes(45),
                    'expected_checkout_at' => now()->addMinutes(135),
                    'status' => 'active',
                    'room_charge' => 395.00,
                    'net_total' => 395.00,
                    'gross_total' => 395.00,
                ]
            );
        }

        // Room 2 (VIP Suite, 12h)
        $r2 = Room::where('number', '2')->first();
        if ($r2) {
            Folio::updateOrCreate(
                ['transaction_id' => 'SCTI-000002'],
                [
                    'room_id' => $r2->id,
                    'guest_id' => $guest5->id,
                    'user_id' => $defaultCashier->id,
                    'rate_tier' => '12h',
                    'checked_in_at' => now()->subHours(2),
                    'expected_checkout_at' => now()->addHours(10),
                    'status' => 'active',
                    'room_charge' => 1395.00,
                    'net_total' => 1395.00,
                    'gross_total' => 1395.00,
                ]
            );
        }

        // Room 17 (Premium, 3h)
        $r17 = Room::where('number', '17')->first();
        if ($r17) {
            Folio::updateOrCreate(
                ['transaction_id' => 'SCTI-000017'],
                [
                    'room_id' => $r17->id,
                    'guest_id' => $guest6->id,
                    'user_id' => $defaultCashier->id,
                    'rate_tier' => '3h',
                    'checked_in_at' => now()->subMinutes(75),
                    'expected_checkout_at' => now()->addMinutes(105),
                    'status' => 'active',
                    'room_charge' => 495.00,
                    'net_total' => 495.00,
                    'gross_total' => 495.00,
                ]
            );
        }

        // Room 14 (Premium, 24h)
        $r14 = Room::where('number', '14')->first();
        if ($r14) {
            Folio::updateOrCreate(
                ['transaction_id' => 'SCTI-000014'],
                [
                    'room_id' => $r14->id,
                    'guest_id' => $guest7->id,
                    'user_id' => $defaultCashier->id,
                    'rate_tier' => '24h',
                    'checked_in_at' => now()->subHours(5),
                    'expected_checkout_at' => now()->addHours(19),
                    'status' => 'active',
                    'room_charge' => 2300.00,
                    'net_total' => 2300.00,
                    'gross_total' => 2300.00,
                ]
            );
        }

        // ── 7. Seed Active Shift ─────────────────────────────────────
        Shift::updateOrCreate(
            ['opened_by' => $defaultCashier->id, 'closed_at' => null],
            [
                'shift_type' => 'day',
                'shift_date' => now()->toDateString(),
                'opened_at' => now()->startOfDay()->addHours(6),
                'is_frozen' => false,
                'room_revenue' => 6790.00,
                'kitchen_revenue' => 235.00,
                'gross_revenue' => 7025.00,
                'cash_total' => 7025.00,
                'gcash_total' => 0.00,
                'denomination_count' => [
                    '1000' => 6, '500' => 1, '200' => 2,
                    '100' => 1, '50' => 0, '20' => 1, 'coins' => 5,
                ],
                'handoff_notes' => 'Room 13 is in overtime status. Room 7 room service order dispatched to kitchen.',
            ]
        );

        // ── 8. Seed Operating Expenses ────────────────────────────────
        $expenses = [
            [
                'voucher_number' => 'EXP-20261001-001',
                'expense_date' => now()->toDateString(),
                'category' => 'utilities',
                'description' => 'Meralco Commercial Electricity Bill (Partial payment)',
                'amount' => 4500.00,
                'payment_source' => 'cash_drawer',
                'receipt_reference' => 'OR-991203',
                'status' => 'approved',
                'notes' => 'Settled via cashier cash drawer',
            ],
            [
                'voucher_number' => 'EXP-20261001-002',
                'expense_date' => now()->toDateString(),
                'category' => 'kitchen_inventory',
                'description' => 'Wet Market Meat & Vegetable Restock for Kitchen Dining',
                'amount' => 2850.00,
                'payment_source' => 'cash_drawer',
                'receipt_reference' => 'MR-4412',
                'status' => 'approved',
                'notes' => 'Purchased fresh pork belly, chicken, garlic, rice',
            ],
            [
                'voucher_number' => 'EXP-20261001-003',
                'expense_date' => now()->subDay()->toDateString(),
                'category' => 'laundry_linens',
                'description' => 'Commercial Laundry Services (Duvets & Bedspreads 45kg)',
                'amount' => 2250.00,
                'payment_source' => 'cash_drawer',
                'receipt_reference' => 'LND-8891',
                'status' => 'approved',
                'notes' => 'Floor 1 and 2 full linen exchange',
            ],
            [
                'voucher_number' => 'EXP-20261001-004',
                'expense_date' => now()->subDays(2)->toDateString(),
                'category' => 'maintenance_repairs',
                'description' => 'Room 4 AC Split-Type Freon Recharge & Filter Deep Cleaning',
                'amount' => 1800.00,
                'payment_source' => 'petty_cash',
                'receipt_reference' => 'SRV-1029',
                'status' => 'approved',
                'notes' => 'Contracted technician service',
            ],
            [
                'voucher_number' => 'EXP-20261001-005',
                'expense_date' => now()->subDays(3)->toDateString(),
                'category' => 'staff_allowances',
                'description' => 'Housekeeping & Front Desk Staff Meal Allowances',
                'amount' => 1200.00,
                'payment_source' => 'petty_cash',
                'receipt_reference' => 'STAFF-1001',
                'status' => 'approved',
                'notes' => '4 staff duty meals',
            ],
        ];

        foreach ($expenses as $exp) {
            \App\Models\Expense::updateOrCreate(
                ['voucher_number' => $exp['voucher_number']],
                array_merge($exp, ['user_id' => $defaultCashier->id])
            );
        }
    }
}
