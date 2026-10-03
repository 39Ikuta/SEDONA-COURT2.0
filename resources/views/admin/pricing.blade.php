@extends('layouts.app')

@section('title', 'Master Pricing & Catalog Editor')
@section('subtitle', 'System Configuration | Master Pricing & POS Item Maintenance')

@section('top_action')
    <a href="{{ route('dashboard') }}" class="bg-[#333333] hover:bg-[#222222] text-white text-[11px] font-bold px-3 py-1 rounded shadow-sm border border-[#666666]">
        &larr; Return to Dashboard
    </a>
@endsection

@section('content')
<div class="px-3 pt-2 space-y-4 max-w-7xl mx-auto">

    <!-- Header -->
    <div class="bg-white p-3 border-l-4 border-[#421A2B] shadow-sm flex items-center justify-between">
        <div>
            <h1 class="font-bold text-slate-900 text-sm uppercase tracking-wide">Master Rates & Item Catalog Maintenance</h1>
            <p class="text-xs text-slate-500">Authorized administrators and executives can adjust room stay duration prices and menu catalog rates.</p>
        </div>
        <div class="text-xs font-mono text-slate-600">
            Overtime Rate: <strong class="text-[#421A2B]">₱130.00 / hour</strong>
        </div>
    </div>

    <!-- Section 1: Room Rates by Tier -->
    <div class="hms-card">
        <div class="hms-card-header border-b pb-2 mb-3">
            ROOM STAY RATES MAINTENANCE (32 ROOMS)
        </div>

        <div class="overflow-x-auto">
            <table class="hms-table">
                <thead>
                    <tr>
                        <th>ROOM #</th>
                        <th>FLOOR</th>
                        <th>ROOM TYPE</th>
                        <th>3 HOURS</th>
                        <th>6 HOURS</th>
                        <th>12 HOURS</th>
                        <th>24 HOURS</th>
                        <th>MIDNIGHT PROMO</th>
                        <th>MAINTENANCE TOGGLE</th>
                        <th>UPDATE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rooms as $room)
                        <tr>
                            <td class="font-bold font-mono text-sm">{{ $room->number }}</td>
                            <td class="font-mono text-[11px] text-slate-500">Floor {{ $room->floor }}</td>
                            <td class="text-left pl-2 font-medium">{{ $room->type }}</td>
                            
                            @if($room->is_staff_quarters)
                                <td colspan="5" class="bg-indigo-50 text-indigo-900 font-bold text-center">
                                    Permanent Employee Quarters (Non-billable)
                                </td>
                                <td>
                                    <span class="text-slate-400 text-[10px]">Staff Room</span>
                                </td>
                                <td>—</td>
                            @else
                                <form method="POST" action="{{ route('admin.pricing.update') }}">
                                    @csrf
                                    <input type="hidden" name="room_id" value="{{ $room->id }}">
                                    
                                    <td>
                                        <input type="number" step="0.01" name="base_rate_3h" value="{{ $room->base_rate_3h }}" class="form-control-hms font-mono text-right !w-20">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="base_rate_6h" value="{{ $room->base_rate_6h }}" class="form-control-hms font-mono text-right !w-20">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="base_rate_12h" value="{{ $room->base_rate_12h }}" class="form-control-hms font-mono text-right !w-24">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="base_rate_24h" value="{{ $room->base_rate_24h }}" class="form-control-hms font-mono text-right !w-24">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="base_rate_promo" value="{{ $room->base_rate_promo }}" class="form-control-hms font-mono text-right !w-24">
                                    </td>
                                    <td>
                                        <button type="submit" formaction="{{ route('rooms.maintenance.toggle', $room->id) }}" class="px-2 py-1 {{ $room->status === 'maintenance' ? 'bg-amber-600 text-white' : 'bg-slate-200 text-slate-700' }} text-[10px] font-bold rounded">
                                            {{ $room->status === 'maintenance' ? 'In Maintenance' : 'Set Out of Order' }}
                                        </button>
                                    </td>
                                    <td>
                                        <button type="submit" class="px-3 py-1 bg-[#0284c7] hover:bg-[#0369a1] text-white text-[11px] font-bold rounded">
                                            Save Rate
                                        </button>
                                    </td>
                                </form>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: POS Item Master Catalog Prices -->
    <div class="hms-card">
        <div class="hms-card-header border-b pb-2 mb-3">
            F&B, REFRESHMENT & AMENITY MASTER CATALOG ({{ count($posItems) }} ITEMS)
        </div>

        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
            <table class="hms-table">
                <thead class="sticky top-0 bg-slate-100">
                    <tr>
                        <th>CATEGORY</th>
                        <th>ITEM DESCRIPTION</th>
                        <th>UNIT PRICE (₱)</th>
                        <th>AVAILABILITY</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($posItems as $item)
                        <tr>
                            <td class="font-bold text-[11px] text-slate-600 text-left pl-3">{{ $item->category }}</td>
                            <td class="font-bold text-slate-900 text-left pl-3">{{ $item->name }}</td>
                            
                            <form method="POST" action="{{ route('admin.pricing.update') }}">
                                @csrf
                                <input type="hidden" name="pos_item_id" value="{{ $item->id }}">
                                
                                <td style="width: 140px;">
                                    <input type="number" step="0.01" name="price" value="{{ $item->price }}" class="form-control-hms font-mono text-right !w-28 font-bold">
                                </td>
                                <td style="width: 120px;">
                                    <label class="flex items-center justify-center space-x-1 cursor-pointer">
                                        <input type="checkbox" name="is_available" value="1" {{ $item->is_available ? 'checked' : '' }} class="rounded text-red-700">
                                        <span class="text-xs font-semibold">{{ $item->is_available ? 'Active' : 'Disabled' }}</span>
                                    </label>
                                </td>
                                <td style="width: 100px;">
                                    <button type="submit" class="px-3 py-1 bg-[#0284c7] hover:bg-[#0369a1] text-white text-[11px] font-bold rounded">
                                        Update
                                    </button>
                                </td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
