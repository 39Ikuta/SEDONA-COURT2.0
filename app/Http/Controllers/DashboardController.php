<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Folio;
use App\Models\Order;
use App\Models\PosItem;
use App\Models\Room;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole('kitchen')) {
                return redirect()->route('kitchen.view');
            }
            if (auth()->user()->hasRole('customer_display')) {
                return redirect()->route('kiosk.view');
            }
        }

        $selectedFloor = $request->query('floor', 'all');
        $selectedStatus = $request->query('status', 'all');

        $query = Room::with(['activeFolio.guest', 'activeFolio.orders.items']);

        if ($selectedFloor !== 'all') {
            $query->where('floor', (int) $selectedFloor);
        }

        if ($selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }

        // Ensure Staff Quarters (Room 12) has an initialized staff folio for F&B add-ons
        $staffRoom = Room::where('is_staff_quarters', true)->first();
        if ($staffRoom && !$staffRoom->activeFolio) {
            $staffRoom->getOrCreateStaffFolio();
        }

        $rooms = $query->orderBy('number')->get();

        // Overall stats (3-state room lifecycle: available, occupied, maintenance)
        $allRooms = Room::all();
        $totalRooms = $allRooms->where('is_staff_quarters', false)->count();
        $availableRooms = $allRooms->where('status', 'available')->where('is_staff_quarters', false)->count();
        $occupiedRooms = $allRooms->where('status', 'occupied')->count();
        $maintenanceRooms = $allRooms->where('status', 'maintenance')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        // Active shift
        $activeShift = Shift::whereNull('closed_at')->latest()->first();

        // Available rooms for fast transfer modal
        $availableRoomsList = Room::where('status', 'available')
            ->where('is_staff_quarters', false)
            ->orderBy('number')
            ->get();

        // Pending force checkout requests
        $pendingForceCheckouts = Folio::with(['room', 'guest', 'cashier'])
            ->where('force_checkout', true)
            ->where('status', 'active')
            ->get();

        // Active folios
        $activeFolios = Folio::with(['room', 'guest', 'orders'])
            ->where('status', 'active')
            ->orderBy('expected_checkout_at')
            ->get();

        // Upcoming bookings for today/tomorrow
        $upcomingBookings = Booking::with('room')
            ->where('status', 'scheduled')
            ->where('arrival_at', '>=', now()->startOfDay())
            ->where('arrival_at', '<=', now()->addDays(2)->endOfDay())
            ->orderBy('arrival_at')
            ->take(6)
            ->get();

        // Pos Items for quick order modal
        $posItems = PosItem::where('is_available', true)->orderBy('category')->orderBy('name')->get();

        // Personal shift performance (cashier scope: own folios/orders within active shift window).
        // Admins keep full BI in Reports; cashiers must never see global sales.
        $myShiftRevenue = 0.0;
        $myCheckIns = 0;
        $myOrdersCount = 0;
        $myShiftLabel = 'No open shift';

        if (auth()->check() && auth()->user()->hasAnyRole(['cashier', 'front_desk'])) {
            $myId = auth()->id();
            $myShiftStart = $activeShift?->opened_at ?? today()->startOfDay();
            $myShiftLabel = $activeShift
                ? ucfirst($activeShift->shift_type ?? 'shift') . ' · opened ' . $activeShift->opened_at->format('m/d h:i A')
                : 'Today';

            $myCheckIns = Folio::where('user_id', $myId)
                ->where('checked_in_at', '>=', $myShiftStart)
                ->count();

            $myFolioRevenue = (float) Folio::where('user_id', $myId)
                ->where('checked_in_at', '>=', $myShiftStart)
                ->sum('net_total');

            $myPosRevenue = (float) Order::where('user_id', $myId)
                ->where('type', 'walkin_pos')
                ->where('created_at', '>=', $myShiftStart)
                ->sum('total');

            $myOrdersCount = Order::where('user_id', $myId)
                ->where('created_at', '>=', $myShiftStart)
                ->count();

            $myShiftRevenue = $myFolioRevenue + $myPosRevenue;
        }

        return view('dashboard.index', compact(
            'rooms',
            'totalRooms',
            'availableRooms',
            'occupiedRooms',
            'maintenanceRooms',
            'occupancyRate',
            'activeShift',
            'activeFolios',
            'upcomingBookings',
            'posItems',
            'availableRoomsList',
            'pendingForceCheckouts',
            'selectedFloor',
            'selectedStatus',
            'myShiftRevenue',
            'myCheckIns',
            'myOrdersCount',
            'myShiftLabel'
        ));
    }

    /**
     * Dedicated Lobby Customer Display Kiosk (Read-only, sanitized, 32-room 8x4 matrix).
     */
    public function kioskView(): View
    {
        $rooms = Room::orderBy('number')->get();
        $totalRooms = $rooms->count();
        $availableRooms = $rooms->where('status', 'available')->count();
        $occupiedRooms = $rooms->where('status', 'occupied')->count();
        $maintenanceRooms = $rooms->where('status', 'maintenance')->count();

        return view('kiosk.index', compact(
            'rooms',
            'totalRooms',
            'availableRooms',
            'occupiedRooms',
            'maintenanceRooms'
        ));
    }
}

