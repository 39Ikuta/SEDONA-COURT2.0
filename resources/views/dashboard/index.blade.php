@extends('layouts.app')

@section('title', 'Front Desk Operations Matrix')
@section('subtitle', 'Front Desk Operations | 32-Room Visual Operations Grid')


@section('content')
<div class="px-2 pt-2 space-y-3">

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 px-1 text-xs">
        <div class="bg-white p-2.5 border-l-4 border-slate-700 shadow-sm flex items-center justify-between">
            <span class="font-bold text-slate-600 uppercase text-[11px]">Total Rooms</span>
            <span class="font-mono font-bold text-base text-slate-900">{{ $totalRooms }}</span>
        </div>
        <div class="bg-white p-2.5 border-l-4 border-available shadow-sm flex items-center justify-between">
            <span class="font-bold text-emerald-800 uppercase text-[11px]">Available</span>
            <span class="font-mono font-bold text-base text-available">{{ $availableRooms }}</span>
        </div>
        <div class="bg-white p-2.5 border-l-4 border-occupied shadow-sm flex items-center justify-between">
            <span class="font-bold text-rose-800 uppercase text-[11px]">Occupied</span>
            <span class="font-mono font-bold text-base text-occupied">{{ $occupiedRooms }}</span>
        </div>
        <div class="bg-white p-2.5 border-l-4 border-classic shadow-sm flex items-center justify-between">
            <span class="font-bold text-amber-800 uppercase text-[11px]">Occupancy</span>
            <span class="font-mono font-bold text-base text-amber-700">{{ $occupancyRate }}%</span>
        </div>
    </div>

    @if(auth()->check() && auth()->user()->hasAnyRole(['cashier', 'front_desk']))
        <!-- Cashier personal shift performance (own sales only — never global) -->
        <div class="mx-1 bg-white border border-slate-200 shadow-sm px-3 py-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <div class="flex items-center gap-2 min-w-0">
                <span class="pill shrink-0" style="background-color:#421A2B;color:#fff;border-color:#341421;">My Shift</span>
                <span class="font-bold text-slate-500 uppercase text-[10px] tracking-wider shrink-0 hidden md:inline">{{ $myShiftLabel ?? '' }}</span>
                <span class="font-mono font-black text-sm text-brand whitespace-nowrap">₱{{ number_format($myShiftRevenue ?? 0, 2) }}</span>
                <span class="text-[11px] text-slate-500 whitespace-nowrap">{{ $myCheckIns ?? 0 }} check-ins &bull; {{ $myOrdersCount ?? 0 }} orders</span>
            </div>
            <div class="flex flex-wrap items-center justify-start sm:justify-end gap-1.5">
                <span class="pill pill-available shrink-0"><span class="pill-dot"></span>{{ $availableRooms }} Available</span>
                <span class="pill pill-occupied shrink-0"><span class="pill-dot"></span>{{ $occupiedRooms }} Occupied</span>
                <span class="text-[11px] font-semibold text-slate-500 whitespace-nowrap shrink-0">{{ $occupancyRate }}% Occ.</span>
            </div>
        </div>
    @endif

    <!-- Admin / Owner Pending Force Checkout Loss Resolution Banner -->
    @if(count($pendingForceCheckouts) > 0 && auth()->user()->hasAnyRole(['admin', 'owner']))
        <div class="bg-amber-50 border border-amber-400 p-3 mx-1 text-xs">
            <div class="font-bold text-amber-900 mb-1">
                ATTENTION: Pending Force Checkout Requests ({{ count($pendingForceCheckouts) }})
            </div>
            <div class="space-y-1.5">
                @foreach($pendingForceCheckouts as $pfc)
                    <div class="flex items-center justify-between bg-white p-2 border border-amber-300">
                        <div>
                            <strong>Room {{ $pfc->room->number ?? 'N/A' }}</strong> &bull;
                            Guest: {{ $pfc->guest->name ?? 'Guest' }} &bull;
                            Unsettled Total: <strong class="font-mono text-red-700">₱{{ number_format($pfc->net_total, 2) }}</strong> &bull;
                            Reason: <span class="italic text-slate-700">{{ $pfc->force_reason ?? 'Skip-out' }}</span>
                        </div>
                        <form method="POST" action="{{ route('admin.force_checkout.approve', $pfc->id) }}" onsubmit="return confirm('Approve Force Checkout? This will write off ₱{{ number_format($pfc->net_total, 2) }} as an audited loss slip.')">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-rose-700 hover:bg-rose-800 text-white font-bold text-xs rounded">
                                Approve Loss Slip (FCE-######)
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- ACTIVE CHECKOUT ALARM BANNER (Multi-Room Audio Trigger) -->
    <div id="activeAlarmBanner" class="hidden bg-purple-900 border-2 border-purple-500 text-white p-3 mx-1 shadow-lg">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-2 font-black text-xs md:text-sm">
                <span class="text-base">⚠️</span>
                <span>AUDIBLE CHECKOUT ALARM: GUEST STAY OVERDUE DETECTED</span>
            </div>
            <button onclick="if(window.sedonaAudio) window.sedonaAudio.stopDigitalAlarm()" class="px-3 py-1 bg-purple-950 hover:bg-black text-white text-[11px] font-bold rounded border border-purple-400">
                Stop Alarm Sound
            </button>
        </div>
        <div id="alarmBannerRoomsList" class="space-y-1.5">
            <!-- Dynamically populated by SedonaAudioEngine -->
        </div>
    </div>

    <!-- Main 2-Column Matrix -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">

        <!-- LEFT COLUMN: AVAILABLE ROOMS -->
        <div class="lg:col-span-4">
            <div class="hms-card">
                <div class="hms-card-header border-b pb-2 mb-2 flex items-center justify-between">
                    <span class="text-available font-bold text-sm">AVAILABLE ROOMS</span>
                    <span class="text-xs text-slate-500 font-normal">({{ $rooms->where('status', 'available')->where('is_staff_quarters', false)->count() }} Ready)</span>
                </div>

                <div class="overflow-x-auto max-h-[700px] overflow-y-auto">
                    <table class="hms-table">
                        <thead class="sticky top-0 z-10 bg-slate-100">
                            <tr>
                                <th style="width: 75px;">ACTION</th>
                                <th style="width: 70px;">ROOM</th>
                                <th style="width: 45px;">FL</th>
                                <th>ROOM TYPE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rooms->where('status', 'available') as $room)
                                @if($room->is_staff_quarters)
                                    <tr class="bg-indigo-50/70">
                                        <td class="space-x-1 whitespace-nowrap">
                                            <span class="px-2 py-0.5 bg-indigo-700 text-white text-[10px] font-bold rounded">Staff</span>
                                            @php $staffFolio = $room->activeFolio ?? $room->getOrCreateStaffFolio(); @endphp
                                            <button onclick="openAddOnModal({{ $staffFolio->id }}, '{{ $room->number }} (Staff Quarters)')" class="px-2 py-0.5 bg-[#0284c7] hover:bg-[#0369a1] text-white text-[10px] font-bold rounded shadow-sm">
                                                + Add On
                                            </button>
                                        </td>
                                        <td class="font-bold text-indigo-950 font-mono">{{ $room->number }}</td>
                                        <td class="text-slate-500 font-mono text-[11px]">{{ $room->floor }}</td>
                                        <td class="text-left pl-2 text-indigo-900 font-medium">Permanent Employee Quarters</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>
                                            <button onclick="openCheckInModal({{ $room->id }}, '{{ $room->number }}', '{{ $room->type }}', {{ $room->base_rate_3h }}, {{ $room->base_rate_12h }}, {{ $room->base_rate_24h }}, {{ $room->base_rate_promo }})" class="btn-checkin">
                                                Check In
                                            </button>
                                        </td>
                                        <td class="font-bold text-slate-900 font-mono text-sm">{{ $room->number }}</td>
                                        <td class="text-slate-500 font-mono text-[11px]">{{ $room->floor }}</td>
                                        <td class="text-left pl-2 font-medium">
                                            @php
                                                $roomUpper = strtoupper($room->type ?? '');
                                                $roomPill = str_contains($roomUpper, 'VIP') || str_contains($roomUpper, 'SUITE') ? 'pill-vip' : (str_contains($roomUpper, 'PREMIUM') ? 'pill-premium' : 'pill-classic');
                                            @endphp
                                            <span class="pill {{ $roomPill }}">{{ $room->type }}</span>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-400">No rooms available under this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: OCCUPIED ROOMS -->
        <div class="lg:col-span-8">
            <div class="hms-card">
                <div class="hms-card-header border-b pb-2 mb-2 flex items-center justify-between">
                    <span class="text-occupied font-bold text-sm">OCCUPIED ROOMS</span>
                    <span class="text-xs text-slate-500 font-normal">({{ $rooms->where('status', 'occupied')->count() }} Active Guests)</span>
                </div>

                <div class="overflow-x-auto max-h-[700px] overflow-y-auto">
                    <table class="hms-table">
                        <thead class="sticky top-0 z-10 bg-slate-100">
                            <tr>
                                <th style="min-width: 175px;">PRIMARY ACTIONS</th>
                                <th style="width: 55px;">ROOM</th>
                                <th style="width: 80px;">DATE</th>
                                <th style="width: 75px;">TIME</th>
                                <th style="width: 45px;">PAX</th>
                                <th style="width: 60px;">STAY</th>
                                <th style="width: 110px;">REMAINING</th>
                                <th style="width: 85px;">STATUS</th>
                                <th style="min-width: 220px;">FOLIO & SERVICE ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rooms->where('status', 'occupied') as $room)
                                @php
                                    $folio = $room->activeFolio;
                                @endphp
                                @if($folio)
                                    <tr data-room-id="{{ $room->id }}"
                                        data-room-number="{{ $room->number }}"
                                        data-guest-name="{{ $folio->guest->name ?? 'Guest' }}"
                                        data-checkout-time="{{ $folio->expected_checkout_at->toIso8601String() }}"
                                        data-folio-id="{{ $folio->id }}"
                                        data-status="{{ $room->status }}">
                                        
                                        <!-- Primary Actions -->
                                        <td class="space-x-1 whitespace-nowrap text-left">
                                            <a href="{{ route('checkout.process', $folio->id) }}" class="btn-occupied font-bold">
                                                Occupied
                                            </a>
                                            <button onclick="openXtendModal({{ $folio->id }}, '{{ $room->number }}')" class="btn-xtend font-bold">
                                                Xtend
                                            </button>
                                            <button onclick="openDiscountModal({{ $folio->id }}, '{{ $room->number }}', '{{ $room->type }}', '{{ $folio->rate_tier }}', '{{ $folio->discount_type ?? 'NONE' }}', '{{ $folio->discount_id_ref ?? '' }}')" class="btn-discount font-bold">
                                                Discount
                                            </button>
                                            <button onclick="openTransferModal({{ $folio->id }}, '{{ $room->number }}')" class="btn-transfer font-bold">
                                                Transfer
                                            </button>
                                        </td>

                                        <!-- Room Number -->
                                        <td class="font-bold font-mono text-sm text-slate-900">
                                            {{ $room->number }}
                                        </td>

                                        <!-- Check In Date -->
                                        <td class="font-mono text-[11px]">{{ $folio->checked_in_at->format('m/d/Y') }}</td>

                                        <!-- Check In Time -->
                                        <td class="font-mono text-[11px]">{{ $folio->checked_in_at->format('h:i A') }}</td>

                                        <!-- No of Guest -->
                                        <td class="font-bold">{{ $folio->guest->headcount ?? 2 }}</td>

                                        <!-- Stay Tier -->
                                        <td class="font-bold font-mono">{{ strtoupper($folio->rate_tier) }}</td>

                                        <!-- Live Reactive Stay Countdown Timer -->
                                        <td class="stay-countdown-timer font-mono text-[11px]">
                                            Calculating...
                                        </td>

                                        <!-- Status Badge -->
                                        <td>
                                            <span class="pill pill-occupied">
                                                <span class="pill-dot"></span>OCCUPIED
                                            </span>
                                        </td>

                                        <!-- Folio & Service Actions -->
                                        <td class="space-x-1 whitespace-nowrap text-right">
                                            <button onclick="openAddOnModal({{ $folio->id }}, '{{ $room->number }}')" class="btn-addon font-bold">
                                                Add On
                                            </button>
                                            <a href="{{ route('folios.orderslip', $folio->id) }}" target="_blank" class="btn-orderslip font-bold">
                                                Order Slip
                                            </a>
                                            <a href="{{ route('pos.index') }}" class="btn-xorder font-bold">
                                                X Order
                                            </a>
                                            <button onclick="openForceCheckoutModal({{ $folio->id }}, '{{ $room->number }}', '{{ number_format($folio->net_total, 2) }}')" class="btn-force font-bold" title="Force Checkout Request">
                                                Force
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-slate-400">No rooms currently occupied.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODAL: CHECK IN -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div id="checkInModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-black/60 hidden">
    <div class="bg-white w-full max-w-md border-t-4 border-[#421A2B] p-5 shadow-2xl">
        <div class="flex items-center justify-between border-b pb-2 mb-3">
            <h3 class="font-bold text-[#421A2B] text-sm" id="checkInModalTitle">Check In Guest</h3>
            <button onclick="closeCheckInModal()" class="text-slate-500 hover:text-black font-bold text-lg">&times;</button>
        </div>

        <form method="POST" action="{{ route('checkin.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="room_id" id="checkInRoomId">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Customer / Guest Name:</label>
                <input type="text" name="guest_name" placeholder="Walk-in Guest Name" class="form-control-hms">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Stay Duration Tier:</label>
                    <select name="rate_tier" id="checkInTierSelect" onchange="updateCheckInRatePreview()" class="form-control-hms font-bold">
                        <option value="3h">3 Hours Stay</option>
                        <option value="6h">6 Hours Stay</option>
                        <option value="12h" selected>12 Hours Stay</option>
                        <option value="24h">24 Hours Stay</option>
                        <option value="promo">Midnight Promo (8pm-6am)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">No. of Guest (Pax):</label>
                    <input type="number" name="headcount" value="2" min="1" max="8" class="form-control-hms font-bold">
                </div>
            </div>

            <div class="bg-slate-50 p-2.5 border border-slate-200 text-xs flex items-center justify-between">
                <span class="text-slate-600 font-semibold">Standard Room Rate:</span>
                <span class="font-mono font-bold text-sm text-[#421A2B]" id="checkInRatePreview">₱1,195.00</span>
            </div>

            <div class="flex items-center space-x-2 pt-1">
                <input type="checkbox" name="is_senior" id="isSenior" value="1" class="rounded text-red-700">
                <label for="isSenior" class="text-xs text-slate-700 cursor-pointer font-semibold">Apply Statutory Senior / PWD Discount (20%)</label>
            </div>

            <div class="border-t pt-3 flex justify-end space-x-2">
                <button type="button" onclick="closeCheckInModal()" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold rounded">Cancel</button>
                <button type="submit" class="px-5 py-1.5 bg-[#0284c7] hover:bg-[#0369a1] text-white text-xs font-bold rounded shadow">Process Check In</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODAL: XTEND -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div id="xtendModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-black/60 hidden">
    <div class="bg-white w-full max-w-sm border-t-4 border-[#0284c7] p-5 shadow-2xl">
        <div class="flex items-center justify-between border-b pb-2 mb-3">
            <h3 class="font-bold text-[#0284c7] text-sm" id="xtendModalTitle">Extend Stay</h3>
            <button onclick="closeXtendModal()" class="text-slate-500 hover:text-black font-bold text-lg">&times;</button>
        </div>

        <form method="POST" id="xtendForm" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select Hours to Extend (₱130.00/hr):</label>
                <select name="hours" class="form-control-hms font-bold">
                    <option value="1">1 Hour (+₱130.00)</option>
                    <option value="2">2 Hours (+₱260.00)</option>
                    <option value="3" selected>3 Hours (+₱390.00)</option>
                    <option value="6">6 Hours (+₱780.00)</option>
                    <option value="12">12 Hours (+₱1,560.00)</option>
                </select>
            </div>

            <div class="border-t pt-3 flex justify-end space-x-2">
                <button type="button" onclick="closeXtendModal()" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold rounded">Cancel</button>
                <button type="submit" class="px-4 py-1.5 bg-[#0284c7] hover:bg-[#0369a1] text-white text-xs font-bold rounded">Save Extension</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODAL: DISCOUNT -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div id="discountModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-black/60 hidden">
    <div class="bg-white w-full max-w-sm border-t-4 border-[#0284c7] p-5 shadow-2xl">
        <div class="flex items-center justify-between border-b pb-2 mb-3">
            <h3 class="font-bold text-[#0284c7] text-sm" id="discountModalTitle">Apply Discount</h3>
            <button onclick="closeDiscountModal()" class="text-slate-500 hover:text-black font-bold text-lg">&times;</button>
        </div>

        <form method="POST" id="discountForm" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Discount Classification:</label>
                <select name="discount_type" id="discountTypeSelect" onchange="updateDiscountPreview()" class="form-control-hms font-bold text-xs">
                    <option value="none">No Discount / Clear Existing</option>
                    <option value="senior">Senior Citizen (Statutory BIR Table)</option>
                    <option value="pwd">Person with Disability - PWD (Statutory BIR Table)</option>
                    <option value="dc">Sedona Discount Card (DC Loyalty Card)</option>
                </select>
            </div>

            <div id="discountRefGroup">
                <label class="block text-xs font-bold text-slate-700 mb-1">Card / ID Reference #:</label>
                <input type="text" name="discount_id_ref" id="discountIdRef" placeholder="e.g. OSCA-2024-9912 or DC-4091" class="form-control-hms font-mono text-xs font-bold">
                <span class="text-[10px] text-slate-500">Required for official tax audit and 80mm receipt itemization.</span>
            </div>

            <div class="p-2.5 bg-slate-100 border border-slate-300 rounded flex items-center justify-between text-xs">
                <div>
                    <span class="font-bold text-slate-800 uppercase text-[10px] block">Fixed Table Deduction:</span>
                    <span class="text-slate-500 text-[10px]" id="discountTierNotice">Classic Room (3h)</span>
                </div>
                <div class="font-mono font-black text-rose-700 text-base" id="discountAmountPreview">
                    -₱0.00
                </div>
            </div>

            <div class="border-t pt-3 flex justify-end space-x-2">
                <button type="button" onclick="closeDiscountModal()" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold rounded">Cancel</button>
                <button type="submit" class="px-4 py-1.5 bg-[#0284c7] hover:bg-[#0369a1] text-white text-xs font-bold rounded">Apply Fixed Discount</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODAL: ROOM TRANSFER -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div id="transferModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-black/60 hidden">
    <div class="bg-white w-full max-w-md border-t-4 border-[#0284c7] p-5 shadow-2xl">
        <div class="flex items-center justify-between border-b pb-2 mb-3">
            <h3 class="font-bold text-[#0284c7] text-sm" id="transferModalTitle">Transfer Room</h3>
            <button onclick="closeTransferModal()" class="text-slate-500 hover:text-black font-bold text-lg">&times;</button>
        </div>

        <form method="POST" id="transferForm" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select Target Available Room:</label>
                <select name="target_room_id" required class="form-control-hms font-bold">
                    @forelse($availableRoomsList as $ar)
                        <option value="{{ $ar->id }}">Room {{ $ar->number }} &bull; Floor {{ $ar->floor }} ({{ $ar->type }})</option>
                    @empty
                        <option value="" disabled selected>No other rooms currently available</option>
                    @endforelse
                </select>
            </div>
            <p class="text-[11px] text-slate-500">
                Notice: Old room will immediately transition to <strong>Available</strong>, and all active billings & food orders will follow the guest to the target room.
            </p>

            <div class="border-t pt-3 flex justify-end space-x-2">
                <button type="button" onclick="closeTransferModal()" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold rounded">Cancel</button>
                <button type="submit" class="px-4 py-1.5 bg-[#0284c7] hover:bg-[#0369a1] text-white text-xs font-bold rounded">Complete Transfer</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODAL: ADD ON -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div id="addOnModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-black/60 hidden">
    <div class="bg-white w-full max-w-md border-t-4 border-[#0284c7] p-5 shadow-2xl">
        <div class="flex items-center justify-between border-b pb-2 mb-3">
            <h3 class="font-bold text-[#0284c7] text-sm" id="addOnModalTitle">Add On Item to Folio</h3>
            <button onclick="closeAddOnModal()" class="text-slate-500 hover:text-black font-bold text-lg">&times;</button>
        </div>

        <form method="POST" id="addOnForm" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select Menu Item / Amenity:</label>
                <select name="pos_item_id" class="form-control-hms font-bold">
                    @foreach($posItems as $pi)
                        @php
                            $stock = $pi->stock_quantity ?? 0;
                            $isOut = $pi->is_tracked && ($stock <= 0);
                            $badgeLabel = $pi->isBreakfastItem() ? "Egg Stock" : "Stock";
                            $stockBadge = $pi->is_tracked ? " [{$badgeLabel}: {$stock}]" : "";
                        @endphp
                        <option value="{{ $pi->id }}" {{ $isOut ? 'disabled class=text-slate-400' : '' }}>
                            {{ $pi->category }}: {{ $pi->name }} (₱{{ number_format($pi->price, 2) }}){{ $stockBadge }}{{ $isOut ? ' - OUT OF STOCK' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Quantity:</label>
                <input type="number" name="quantity" value="1" min="1" max="50" class="form-control-hms font-bold font-mono">
            </div>

            <div class="border-t pt-3 flex justify-end space-x-2">
                <button type="button" onclick="closeAddOnModal()" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold rounded">Cancel</button>
                <button type="submit" class="px-4 py-1.5 bg-[#0284c7] hover:bg-[#0369a1] text-white text-xs font-bold rounded">Save Add On</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- MODAL: FORCE CHECKOUT REQUEST -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div id="forceCheckoutModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-black/60 hidden">
    <div class="bg-white w-full max-w-md border-t-4 border-[#374151] p-5 shadow-2xl">
        <div class="flex items-center justify-between border-b pb-2 mb-3">
            <h3 class="font-bold text-slate-900 text-sm" id="forceModalTitle">Request Force Checkout</h3>
            <button onclick="closeForceCheckoutModal()" class="text-slate-500 hover:text-black font-bold text-lg">&times;</button>
        </div>

        <form method="POST" id="forceForm" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Select Incident Reason:</label>
                <select name="force_reason" class="form-control-hms font-bold">
                    <option value="Guest skipped out without settlement">Guest skipped out without settlement</option>
                    <option value="Overstay and unreachable">Overstay and unreachable</option>
                    <option value="Disputed billing charges">Disputed billing charges</option>
                    <option value="Emergency property eviction">Emergency property eviction</option>
                </select>
            </div>

            <div class="border-t pt-3 flex justify-end space-x-2">
                <button type="button" onclick="closeForceCheckoutModal()" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold rounded">Cancel</button>
                <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded">Submit for Approval</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let currentRoomRates = {};

    function openCheckInModal(roomId, roomNumber, roomType, r3, r12, r24, rpromo) {
        document.getElementById('checkInRoomId').value = roomId;
        document.getElementById('checkInModalTitle').textContent = `Check In Room ${roomNumber} (${roomType})`;
        currentRoomRates = { '3h': r3, '6h': r3 * 2, '12h': r12, '24h': r24, 'promo': rpromo };
        updateCheckInRatePreview();
        document.getElementById('checkInModal').classList.remove('hidden');
    }
    function updateCheckInRatePreview() {
        const tier = document.getElementById('checkInTierSelect').value;
        const rate = currentRoomRates[tier] || 0;
        document.getElementById('checkInRatePreview').textContent = `₱${rate.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
    }
    function closeCheckInModal() {
        document.getElementById('checkInModal').classList.add('hidden');
    }

    function openXtendModal(folioId, roomNumber) {
        document.getElementById('xtendForm').action = `/folios/${folioId}/extend`;
        document.getElementById('xtendModalTitle').textContent = `Extend Stay - Room ${roomNumber}`;
        document.getElementById('xtendModal').classList.remove('hidden');
    }
    function closeXtendModal() {
        document.getElementById('xtendModal').classList.add('hidden');
    }

    let currentDiscountContext = { roomType: 'Classic Room', rateTier: '3h' };
    const DISCOUNT_RATES = {
        'DC': {
            'CLASSIC': { '3h': 40, '6h': 0, '12h': 55, '24h': 95 },
            'PREMIUM': { '3h': 50, '6h': 0, '12h': 60, '24h': 105 },
            'VIP':     { '3h': 65, '6h': 0, '12h': 70, '24h': 115 }
        },
        'SENIOR': {
            'CLASSIC': { '3h': 79,  '6h': 158, '12h': 195, '24h': 340 },
            'PREMIUM': { '3h': 99,  '6h': 178, '12h': 215, '24h': 375 },
            'VIP':     { '3h': 139, '6h': 218, '12h': 255, '24h': 460 }
        }
    };

    function openDiscountModal(folioId, roomNumber, roomType, rateTier, currentDiscountType, currentDiscountRef) {
        document.getElementById('discountForm').action = `/folios/${folioId}/discount`;
        document.getElementById('discountModalTitle').textContent = `Apply Fixed Discount - Room ${roomNumber} (${roomType})`;
        
        currentDiscountContext = { roomType: roomType || 'Classic Room', rateTier: (rateTier || '3h').toLowerCase() };
        
        const dt = (currentDiscountType || 'none').toLowerCase();
        document.getElementById('discountTypeSelect').value = (dt === 'senior' || dt === 'pwd' || dt === 'dc') ? dt : 'none';
        document.getElementById('discountIdRef').value = currentDiscountRef || '';
        
        updateDiscountPreview();
        document.getElementById('discountModal').classList.remove('hidden');
    }

    function updateDiscountPreview() {
        const type = document.getElementById('discountTypeSelect').value.toUpperCase();
        const tier = currentDiscountContext.roomType.toUpperCase().includes('VIP') ? 'VIP' : (currentDiscountContext.roomType.toUpperCase().includes('PREMIUM') ? 'PREMIUM' : 'CLASSIC');
        const dur = currentDiscountContext.rateTier.startsWith('6') ? '6h' : (currentDiscountContext.rateTier.startsWith('12') ? '12h' : (currentDiscountContext.rateTier.startsWith('24') ? '24h' : '3h'));

        document.getElementById('discountTierNotice').textContent = `${tier} Room (${dur})`;
        
        if (type === 'NONE') {
            document.getElementById('discountAmountPreview').textContent = '₱0.00';
            document.getElementById('discountRefGroup').style.opacity = '0.5';
            return;
        }

        document.getElementById('discountRefGroup').style.opacity = '1';
        const lookupCat = (type === 'PWD') ? 'SENIOR' : type;
        const amount = (DISCOUNT_RATES[lookupCat] && DISCOUNT_RATES[lookupCat][tier]) ? (DISCOUNT_RATES[lookupCat][tier][dur] || 0) : 0;
        document.getElementById('discountAmountPreview').textContent = `-₱${amount.toFixed(2)}`;
    }

    function closeDiscountModal() {
        document.getElementById('discountModal').classList.add('hidden');
    }

    function openTransferModal(folioId, roomNumber) {
        document.getElementById('transferForm').action = `/folios/${folioId}/transfer`;
        document.getElementById('transferModalTitle').textContent = `Transfer Guest from Room ${roomNumber}`;
        document.getElementById('transferModal').classList.remove('hidden');
    }
    function closeTransferModal() {
        document.getElementById('transferModal').classList.add('hidden');
    }

    function openAddOnModal(folioId, roomNumber) {
        document.getElementById('addOnForm').action = `/folios/${folioId}/add-on`;
        document.getElementById('addOnModalTitle').textContent = `Add On Item - Room ${roomNumber}`;
        document.getElementById('addOnModal').classList.remove('hidden');
    }
    function closeAddOnModal() {
        document.getElementById('addOnModal').classList.add('hidden');
    }

    function openForceCheckoutModal(folioId, roomNumber, netTotal) {
        document.getElementById('forceForm').action = `/folios/${folioId}/force-checkout`;
        document.getElementById('forceModalTitle').textContent = `Request Force Checkout - Room ${roomNumber} (₱${netTotal})`;
        document.getElementById('forceCheckoutModal').classList.remove('hidden');
    }
    function closeForceCheckoutModal() {
        document.getElementById('forceCheckoutModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
