@extends('layouts.app')

@section('title', 'Guest Check Out Processing')
@section('subtitle', 'Check Out | Settle Guest Stay & Issue Official Receipt')

@section('top_action')
    <a href="{{ route('dashboard') }}" class="bg-[#333333] hover:bg-[#222222] text-white text-[11px] font-bold px-3 py-1 rounded shadow-sm border border-[#666666]">
        &larr; Back to Front Desk Matrix
    </a>
@endsection

@section('content')
<div class="px-3 pt-2 max-w-6xl mx-auto">

    <div class="hms-card">
        <div class="flex items-center justify-between border-b pb-2 mb-3">
            <h2 class="text-[#421A2B] text-base font-bold uppercase tracking-wide flex items-center space-x-2">
                <span>Official Guest Check Out &amp; Settlement</span>
            </h2>
            <div class="text-xs font-mono text-slate-600">
                Folio ID: <strong class="text-[#421A2B]">{{ $folio->transaction_id }}</strong>
            </div>
        </div>

        <form method="POST" action="{{ route('checkout.submit', $folio->id) }}" id="checkoutProcessForm">
            @csrf

            <!-- Top Stay Details 3-Column Grid (Tamper-Proof Readouts) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 mb-3 text-xs bg-slate-50 p-3 border border-slate-200">
                
                <!-- Col 1: Room Number, Check In Time, Room Rate -->
                <div class="space-y-2">
                    <div class="flex items-center">
                        <label class="w-32 font-bold text-slate-700 text-[11px]">ROOM NUMBER:</label>
                        <input type="text" value="{{ $room->number ?? 'N/A' }} ({{ $room->type ?? '' }})" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms !text-left font-bold text-xs">
                    </div>

                    <div class="flex items-center">
                        <label class="w-32 font-bold text-slate-700 text-[11px]">CHECK IN TIME:</label>
                        <input type="text" value="{{ $checkInTime->format('m/d/Y h:i A') }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms !text-left text-[11px] font-mono">
                    </div>

                    <div class="flex items-center">
                        <label class="w-32 font-bold text-slate-700 text-[11px]">BASE ROOM RATE:</label>
                        <input type="text" id="inpRoomRate" value="₱{{ number_format($roomRate, 2) }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono font-bold text-slate-800">
                    </div>
                </div>

                <!-- Col 2: Customer Name, Check Out Time, Add. Hours Rate -->
                <div class="space-y-2">
                    <div class="flex items-center">
                        <label class="w-36 font-bold text-slate-700 text-[11px]">CUSTOMER NAME:</label>
                        <input type="text" value="{{ $guest->name ?? '' }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms text-xs font-semibold">
                    </div>

                    <div class="flex items-center">
                        <label class="w-36 font-bold text-slate-700 text-[11px]">CHECK OUT TIME:</label>
                        <input type="text" value="{{ $checkOutTime->format('m/d/Y h:i A') }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms !text-left text-[11px] font-mono">
                    </div>

                    <div class="flex items-center">
                        <label class="w-36 font-bold text-slate-700 text-[11px]">ADD. HOURS RATE:</label>
                        <input type="text" id="inpAddHoursRate" value="₱{{ number_format($addHoursRate, 2) }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono text-purple-700 font-bold">
                    </div>
                </div>

                <!-- Col 3: Booked Stay, Xtend, Actual Stay Diff, Excess Overtime -->
                <div class="space-y-2">
                    <div class="flex items-center">
                        <label class="w-44 font-bold text-slate-700 text-[11px]">BOOKED STAY:</label>
                        <input type="text" value="{{ $tierHours }} Hours ({{ strtoupper($folio->rate_tier) }})" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono">
                    </div>

                    <div class="flex items-center">
                        <label class="w-44 font-bold text-sky-800 text-[11px]">XTEND / EXTENSIONS:</label>
                        <input type="text" value="+{{ $xtendHours }} Hours (₱{{ number_format($xtendHours * 130, 2) }})" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono font-bold text-sky-800">
                    </div>

                    <div class="flex items-center">
                        <label class="w-44 font-bold text-slate-700 text-[11px]">ACTUAL STAY DIFF:</label>
                        <input type="text" value="{{ $actualHoursDiff }} Hours" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono">
                    </div>

                    <div class="flex items-center">
                        <label class="w-44 font-bold text-slate-700 text-[11px]">EXCESS OVERTIME:</label>
                        <input type="text" value="{{ $excessHours }} Hours" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono font-bold text-purple-700">
                    </div>
                </div>
            </div>

            <!-- SECURITY DEPOSIT LEDGER SECTION -->
            <div class="section-bar-grey flex items-center justify-between">
                <span>SECURITY DEPOSIT LEDGER &amp; TRUST ACCOUNT</span>
                <span class="text-[10px] font-normal text-slate-300">Folio Ref: DEP-{{ $folio->transaction_id }}</span>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 py-2.5 px-3 mb-3 text-xs bg-slate-100 border border-slate-300">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Deposit Input -->
                    <div class="flex items-center space-x-2">
                        <label for="inpDeposit" class="font-bold text-slate-800 text-[11px]">GUEST DEPOSIT RECEIVED (IN TRUST):</label>
                        <div class="relative inline-flex items-center">
                            <span class="absolute left-2.5 text-slate-500 font-bold font-mono text-sm pointer-events-none select-none">₱</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   max="100000" 
                                   name="security_deposit" 
                                   id="inpDeposit" 
                                   value="{{ number_format($deposit, 2, '.', '') }}" 
                                   placeholder="0.00" 
                                   oninput="onDepositChange(this.value)" 
                                   onblur="saveDepositToServer(this.value)" 
                                   class="form-control-hms font-mono font-bold text-emerald-800 !w-32 pl-6 text-center bg-white border-slate-300 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-500 shadow-inner" 
                                   title="Cashier: Enter or adjust guest deposit held in trust">
                        </div>
                    </div>

                    <!-- Option to Apply or Not Apply Deposit -->
                    <div class="flex items-center space-x-2">
                        <input type="hidden" name="apply_deposit" value="0">
                        <label class="inline-flex items-center space-x-1.5 cursor-pointer bg-white px-2.5 py-1 rounded border border-slate-300 shadow-sm hover:bg-slate-50 select-none">
                            <input type="checkbox" 
                                   id="chkApplyDeposit" 
                                   name="apply_deposit" 
                                   value="1" 
                                   {{ $applyDeposit ? 'checked' : '' }} 
                                   onchange="onApplyDepositToggle(this.checked)" 
                                   class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                            <span id="lblApplyDeposit" class="font-bold text-[11px] {{ $applyDeposit ? 'text-emerald-800' : 'text-slate-600' }}">
                                {{ $applyDeposit ? '✓ Applied to Bill' : '✕ Do Not Apply' }}
                            </span>
                        </label>
                    </div>

                    <!-- Remaining Balance of Deposit After Deduction of Gross Sub-Total -->
                    <div class="flex items-center space-x-2 bg-emerald-50 px-2.5 py-1 rounded border border-emerald-300" id="boxRemainingDeposit">
                        <label class="font-bold text-emerald-900 text-[11px]">REMAINING DEPOSIT BALANCE:</label>
                        <input type="text" 
                               id="inpRemainingDeposit" 
                               value="₱{{ number_format($remainingDeposit, 2) }}" 
                               readonly tabindex="-1" 
                               style="pointer-events: none; background-color: #fff; user-select: none;" 
                               class="form-control-hms font-mono font-bold text-emerald-800 !w-28 text-center text-xs border-emerald-400">
                        <span id="lblDepositExplanation" class="text-[10px] font-bold text-emerald-700">
                            {{ $remainingDeposit > 0 ? '(Refund to Guest)' : ($deposit > 0 ? '(Fully Absorbed)' : '(No Deposit)') }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('folios.deposit_slip', $folio->id) }}@if($deposit > 0)?deposit={{ $deposit }}@endif" id="depositSlipTopLink" target="_blank" class="px-2.5 py-1 bg-slate-700 hover:bg-slate-800 text-white font-bold text-[11px] rounded flex items-center space-x-1 shadow-sm">
                        <span>💰</span>
                        <span>Print Official Deposit Slip</span>
                    </a>
                </div>
            </div>

            <!-- Bottom Two Columns Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- LEFT COLUMN: ADD ONS & ITEMIZED CHARGES -->
                <div>
                    <div class="section-bar-grey">ROOM SERVICE, EXTENSION &amp; ADD ON CHARGES</div>

                    <div class="border border-slate-300 mb-2 overflow-x-auto max-h-60 overflow-y-auto">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-100 text-slate-700 font-bold text-[11px] border-b">
                                <tr>
                                    <th class="p-1.5 border-r">Item / Service Description</th>
                                    <th class="p-1.5 border-r text-center">Qty</th>
                                    <th class="p-1.5 border-r text-right">Price</th>
                                    <th class="p-1.5 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Room Base Stay -->
                                <tr class="border-b bg-slate-50/50">
                                    <td class="p-1.5 border-r font-medium">Room Lodging Base Charge ({{ strtoupper($folio->rate_tier) }} Tier)</td>
                                    <td class="p-1.5 border-r text-center font-mono">1</td>
                                    <td class="p-1.5 border-r text-right font-mono">{{ number_format($roomRate, 2) }}</td>
                                    <td class="p-1.5 text-right font-mono font-bold">{{ number_format($roomRate, 2) }}</td>
                                </tr>

                                <!-- Xtend Stay Extension if applicable -->
                                @if($xtendHours > 0)
                                    <tr class="border-b bg-sky-50/50">
                                        <td class="p-1.5 border-r font-medium text-sky-900">Stay Extension (+{{ $xtendHours }}h Xtend @ ₱130.00/hr)</td>
                                        <td class="p-1.5 border-r text-center font-mono">{{ $xtendHours }}</td>
                                        <td class="p-1.5 border-r text-right font-mono">130.00</td>
                                        <td class="p-1.5 text-right font-mono font-bold text-sky-800">{{ number_format($xtendHours * 130, 2) }}</td>
                                    </tr>
                                @endif

                                <!-- Excess Overtime if applicable -->
                                @if($excessHours > 0)
                                    <tr class="border-b bg-purple-50/50">
                                        <td class="p-1.5 border-r font-medium text-purple-900">Excess Overtime (+{{ $excessHours }}h Overstay @ ₱130.00/hr)</td>
                                        <td class="p-1.5 border-r text-center font-mono">{{ $excessHours }}</td>
                                        <td class="p-1.5 border-r text-right font-mono">130.00</td>
                                        <td class="p-1.5 text-right font-mono font-bold text-purple-800">{{ number_format($excessHours * 130, 2) }}</td>
                                    </tr>
                                @endif

                                <!-- POS / Kitchen / Amenity Items -->
                                @forelse($addOnItems as $addOn)
                                    <tr class="border-b hover:bg-slate-50">
                                        <td class="p-1.5 border-r font-medium">{{ $addOn['name'] }}</td>
                                        <td class="p-1.5 border-r text-center font-mono">{{ $addOn['qty'] }}</td>
                                        <td class="p-1.5 border-r text-right font-mono">{{ number_format($addOn['price'], 2) }}</td>
                                        <td class="p-1.5 text-right font-mono font-bold">{{ number_format($addOn['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    @if($xtendHours == 0 && $excessHours == 0)
                                        <tr>
                                            <td colspan="4" class="p-3 text-slate-400 text-center text-xs">
                                                No food, drink, or amenity add-ons billed to this folio.
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-1.5 text-xs bg-slate-50 p-2.5 border border-slate-200">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700 text-[11px]">TOTAL ADD ONS (DINING/AMENITIES):</label>
                            <input type="text" id="inpAddOnsTotal" value="₱{{ number_format($addOnsTotal, 2) }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono !w-40 text-right">
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700 text-[11px]">GROSS SUB-TOTAL:</label>
                            <input type="text" id="inpSubTotal" value="₱{{ number_format($subTotal, 2) }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono !w-40 font-bold text-slate-900 text-right">
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: DISCOUNT & FINAL SETTLEMENT -->
                <div>
                    <div class="section-bar-grey">SETTLEMENT &amp; PAYMENT DETAILS</div>

                    <div class="space-y-2 text-xs bg-slate-50 p-3 border border-slate-200">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700 text-[11px]">GROSS TOTAL CHARGE:</label>
                            <input type="text" id="inpTotalCharge" value="₱{{ number_format($totalCharge, 2) }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono !w-44 font-bold text-right">
                        </div>

                        <!-- DISCOUNT SELECTION & DEDUCTION -->
                        <div class="border border-slate-300 bg-white p-2.5 rounded space-y-2 shadow-sm">
                            <div class="flex items-center justify-between">
                                <label for="discountTypeSelect" class="font-bold text-slate-800 text-[11px]">DISCOUNT CLASSIFICATION:</label>
                                <select name="discount_type" id="discountTypeSelect" onchange="onDiscountChange()" class="form-control-hms !w-44 font-bold text-xs bg-amber-50/40 border-amber-300 focus:border-amber-600">
                                    <option value="none" {{ strtolower($folio->discount_type ?? 'none') === 'none' ? 'selected' : '' }}>None (₱0.00)</option>
                                    <option value="senior" {{ strtolower($folio->discount_type ?? '') === 'senior' ? 'selected' : '' }}>Senior Citizen (-₱{{ number_format($discountOptions['SENIOR'] ?? 79, 2) }})</option>
                                    <option value="pwd" {{ strtolower($folio->discount_type ?? '') === 'pwd' ? 'selected' : '' }}>PWD Disability (-₱{{ number_format($discountOptions['PWD'] ?? 79, 2) }})</option>
                                    <option value="dc" {{ strtolower($folio->discount_type ?? '') === 'dc' ? 'selected' : '' }}>Sedona Card / DC (-₱{{ number_format($discountOptions['DC'] ?? 40, 2) }})</option>
                                </select>
                            </div>

                            <div class="flex items-center justify-between" id="rowDiscountRef" style="{{ strtolower($folio->discount_type ?? 'none') === 'none' ? 'display: none;' : '' }}">
                                <div>
                                    <label for="inpDiscountRef" class="font-bold text-slate-700 text-[11px] block">CARD / ID REFERENCE #:</label>
                                    <span class="text-[9px] text-slate-400">Required for official tax audit</span>
                                </div>
                                <input type="text" name="discount_id_ref" id="inpDiscountRef" value="{{ $folio->discount_id_ref ?? '' }}" placeholder="e.g. OSCA-2024-9912 or DC-4091" oninput="updateDiscountBadgeRef(); syncBillingStatementUrl()" onblur="saveSettlementDraftToServer()" class="form-control-hms font-mono !w-44 font-bold text-xs">
                            </div>

                            <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                                <div>
                                    <label class="font-bold text-slate-700 text-[11px] block">TOTAL DISCOUNT DEDUCTION:</label>
                                    <span id="badgeDiscountType" class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $totalDiscount > 0 ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $folio->discount_type ?? 'NONE' }}{{ $folio->discount_id_ref ? ' (' . $folio->discount_id_ref . ')' : '' }}
                                    </span>
                                </div>
                                <input type="text" id="inpTotalDiscount" value="{{ $totalDiscount > 0 ? '-₱' . number_format($totalDiscount, 2) : '₱0.00' }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono !w-44 font-bold text-rose-700 text-right">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700 text-[11px]">AMOUNT AFTER DISCOUNT:</label>
                            <input type="text" id="inpAmountToPay" value="₱{{ number_format($amountToPay, 2) }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono !w-44 font-bold text-right">
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="font-bold text-slate-700 text-[11px]">LESS: SECURITY DEPOSIT:</label>
                            <input type="text" id="inpLessDeposit" value="{{ $applyDeposit && $depositApplied > 0 ? '-₱' . number_format($depositApplied, 2) : ($applyDeposit ? '₱0.00' : '₱0.00 (NOT APPLIED)') }}" readonly tabindex="-1" style="pointer-events: none; background-color: #f1f5f9; user-select: none; border-color: #cbd5e1;" class="form-control-hms font-mono !w-44 text-emerald-700 font-bold text-right">
                        </div>

                        <!-- Remaining Deposit Refund Row -->
                        <div class="flex items-center justify-between bg-emerald-50 p-1.5 border border-emerald-300 {{ $remainingDeposit > 0 ? '' : 'hidden' }}" id="rowRemainingDepositRefund">
                            <label class="font-bold text-emerald-900 text-xs">REMAINING DEPOSIT TO REFUND:</label>
                            <input type="text" id="inpRemainingDepositRefund" value="₱{{ number_format($remainingDeposit, 2) }}" readonly tabindex="-1" style="pointer-events: none; background-color: #fff; user-select: none; border-color: #10b981;" class="form-control-hms font-mono !w-44 font-bold text-emerald-800 text-sm text-right">
                        </div>

                        <div class="flex items-center justify-between bg-red-50 p-2 border border-red-300">
                            <label class="font-bold text-red-900 text-xs">FINAL BALANCE TO PAY:</label>
                            <input type="text" id="inpTotalAmountToPay" value="₱{{ number_format($totalAmountToPay, 2) }}" readonly tabindex="-1" style="pointer-events: none; background-color: #fff; user-select: none; border-color: #ef4444;" class="form-control-hms font-mono !w-44 font-black text-sm text-red-700 text-right">
                        </div>

                        <!-- Cashier Editable Settlement Fields -->
                        <div class="border-t border-slate-300 pt-2 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-700 text-[11px]">PAYMENT METHOD:</label>
                                <select name="payment_method" id="paymentMethodSelect" onchange="togglePaymentInputs()" class="form-control-hms !w-44 font-bold">
                                    <option value="cash" selected>Cash Payment</option>
                                    <option value="gcash">GCash E-Wallet</option>
                                    <option value="split">Split (Cash + GCash)</option>
                                </select>
                            </div>

                            <div class="flex items-center justify-between" id="rowCashTendered">
                                <label class="font-bold text-slate-700 text-[11px]">CASH TENDERED (₱):</label>
                                <input type="number" step="0.01" id="inpCashTendered" name="cash_tendered" placeholder="0.00" oninput="calculateChange()" onblur="saveSettlementDraftToServer()" class="form-control-hms font-mono !w-44 font-bold text-right">
                            </div>

                            <div class="flex items-center justify-between" id="rowGcashAmount" style="display: none;">
                                <label class="font-bold text-slate-700 text-[11px]">GCASH AMOUNT PAID (₱):</label>
                                <input type="number" step="0.01" id="inpGcashAmount" name="gcash_amount" placeholder="0.00" oninput="calculateChange()" onblur="saveSettlementDraftToServer()" class="form-control-hms font-mono !w-44 font-bold text-right">
                            </div>

                            <div class="flex items-center justify-between" id="rowGcashRef" style="display: none;">
                                <label class="font-bold text-slate-700 text-[11px]">GCASH REFERENCE #:</label>
                                <input type="text" id="inpGcashRef" name="gcash_reference" placeholder="e.g. 10098234821" oninput="calculateChange()" onblur="saveSettlementDraftToServer()" class="form-control-hms font-mono !w-44">
                            </div>

                            <div class="flex items-center justify-between bg-emerald-50 p-2 border border-emerald-300">
                                <label class="font-bold text-emerald-900 text-xs">CHANGE DUE TO GUEST:</label>
                                <input type="text" id="inpChange" value="₱0.00" readonly tabindex="-1" style="pointer-events: none; background-color: #fff; user-select: none; border-color: #10b981;" class="form-control-hms font-mono !w-44 font-bold text-emerald-800 text-sm text-right">
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Bottom Actions Row -->
            <div class="mt-4 pt-3 border-t flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('folios.billing', $folio->id) }}@if($deposit > 0)?deposit={{ $deposit }}@endif" id="billingStatementLink" target="_blank" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-800 text-white font-bold text-xs rounded">
                        📄 Statement / Billing
                    </a>
                    <a href="{{ route('folios.deposit_slip', $folio->id) }}@if($deposit > 0)?deposit={{ $deposit }}@endif" id="depositSlipBottomLink" target="_blank" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-800 text-white font-bold text-xs rounded">
                        💰 Deposit Slip
                    </a>
                    <a href="{{ route('folios.deposit_refund', $folio->id) }}@if($deposit > 0)?deposit={{ $deposit }}@endif" id="depositRefundLink" target="_blank" class="px-3 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded">
                        💵 Deposit Refund
                    </a>
                    <a href="{{ route('folios.orderslip', $folio->id) }}" target="_blank" class="px-3 py-1.5 bg-[#0284c7] hover:bg-[#0369a1] text-white font-bold text-xs rounded">
                        🍽️ Order Slip
                    </a>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded">
                        Cancel &amp; Return
                    </a>
                    <button type="submit" onclick="return confirm('Confirm check out and finalize settlement?')" class="px-6 py-2 bg-[#421A2B] hover:bg-[#341421] text-white font-bold text-xs rounded shadow">
                        Complete Official Check-Out
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
    const grossTotal = {{ $totalCharge }};
    const discountRates = @json($discountOptions);
    let currentDiscountType = "{{ strtolower($folio->discount_type ?? 'none') }}";
    let currentDiscountAmount = {{ (float)($totalDiscount ?? 0) }};
    let amountAfterDiscount = {{ $amountToPay }};
    let currentDeposit = {{ $deposit }};
    let applyDeposit = {{ $applyDeposit ? 'true' : 'false' }};
    let finalBalanceToPay = applyDeposit ? Math.max(0, amountAfterDiscount - currentDeposit) : amountAfterDiscount;
    let depositSaveTimer = null;

    const baseBillingUrl = "{{ route('folios.billing', $folio->id) }}";
    const baseDepositSlipUrl = "{{ route('folios.deposit_slip', $folio->id) }}";
    const baseDepositRefundUrl = "{{ route('folios.deposit_refund', $folio->id) }}";

    function updateDiscountBadgeRef() {
        const badge = document.getElementById('badgeDiscountType');
        const refVal = document.getElementById('inpDiscountRef')?.value?.trim();
        if (badge && currentDiscountType !== 'none') {
            badge.textContent = currentDiscountType.toUpperCase() + (refVal ? ` (${refVal})` : '');
        }
    }

    function onDiscountChange() {
        const sel = document.getElementById('discountTypeSelect');
        currentDiscountType = (sel ? sel.value : 'none').toLowerCase();
        const rowRef = document.getElementById('rowDiscountRef');
        const badge = document.getElementById('badgeDiscountType');
        const refVal = document.getElementById('inpDiscountRef')?.value?.trim();
        const refStr = refVal ? ` (${refVal})` : '';

        if (currentDiscountType === 'none') {
            currentDiscountAmount = 0.00;
            if (rowRef) rowRef.style.display = 'none';
            if (badge) {
                badge.textContent = 'NONE';
                badge.className = 'px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-600';
            }
        } else {
            if (rowRef) rowRef.style.display = 'flex';
            if (currentDiscountType === 'senior') {
                currentDiscountAmount = discountRates['SENIOR'] || 0;
                if (badge) {
                    badge.textContent = 'SENIOR' + refStr;
                    badge.className = 'px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300';
                }
            } else if (currentDiscountType === 'pwd') {
                currentDiscountAmount = discountRates['PWD'] || 0;
                if (badge) {
                    badge.textContent = 'PWD' + refStr;
                    badge.className = 'px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300';
                }
            } else if (currentDiscountType === 'dc') {
                currentDiscountAmount = discountRates['DC'] || 0;
                if (badge) {
                    badge.textContent = 'DC' + refStr;
                    badge.className = 'px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300';
                }
            }
        }

        // Update Total Discount readout
        const inpTotalDiscount = document.getElementById('inpTotalDiscount');
        if (inpTotalDiscount) {
            inpTotalDiscount.value = currentDiscountAmount > 0 
                ? `-₱${currentDiscountAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` 
                : '₱0.00';
        }

        // Update Amount After Discount
        amountAfterDiscount = Math.max(0, grossTotal - currentDiscountAmount);
        const inpAmountToPay = document.getElementById('inpAmountToPay');
        if (inpAmountToPay) {
            inpAmountToPay.value = `₱${amountAfterDiscount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        updateCalculations();
        saveSettlementDraftToServer();
    }

    function onApplyDepositToggle(checked) {
        applyDeposit = !!checked;
        const lbl = document.getElementById('lblApplyDeposit');
        if (lbl) {
            lbl.textContent = applyDeposit ? '✓ Applied to Bill' : '✕ Do Not Apply';
            lbl.className = 'font-bold text-[11px] ' + (applyDeposit ? 'text-emerald-800' : 'text-slate-600');
        }
        updateCalculations();
        saveSettlementDraftToServer();
    }

    function onDepositChange(val) {
        currentDeposit = Math.max(0, parseFloat(val) || 0);
        updateCalculations();

        clearTimeout(depositSaveTimer);
        depositSaveTimer = setTimeout(() => {
            saveDepositToServer(currentDeposit);
        }, 400);
    }

    function updateCalculations() {
        const depositApplied = applyDeposit ? Math.min(currentDeposit, amountAfterDiscount) : 0;
        const remainingDeposit = applyDeposit ? Math.max(0, currentDeposit - amountAfterDiscount) : currentDeposit;
        finalBalanceToPay = applyDeposit ? Math.max(0, amountAfterDiscount - currentDeposit) : amountAfterDiscount;

        // Update Remaining Deposit Balance readout & explanation
        const inpRemainingDeposit = document.getElementById('inpRemainingDeposit');
        if (inpRemainingDeposit) {
            inpRemainingDeposit.value = `₱${remainingDeposit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }
        const lblDepositExplanation = document.getElementById('lblDepositExplanation');
        if (lblDepositExplanation) {
            if (!applyDeposit) {
                lblDepositExplanation.textContent = '(Not Applied - Full Refund)';
            } else if (remainingDeposit > 0) {
                lblDepositExplanation.textContent = '(Refund to Guest)';
            } else if (currentDeposit > 0) {
                lblDepositExplanation.textContent = '(Fully Absorbed)';
            } else {
                lblDepositExplanation.textContent = '(No Deposit)';
            }
        }

        // Update Less Deposit in settlement column
        const inpLessDeposit = document.getElementById('inpLessDeposit');
        if (inpLessDeposit) {
            if (applyDeposit && depositApplied > 0) {
                inpLessDeposit.value = `-₱${depositApplied.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            } else if (applyDeposit) {
                inpLessDeposit.value = '₱0.00';
            } else {
                inpLessDeposit.value = '₱0.00 (NOT APPLIED)';
            }
        }

        // Update Remaining Deposit Refund Row in settlement column
        const rowRemainingRefund = document.getElementById('rowRemainingDepositRefund');
        const inpRemainingRefund = document.getElementById('inpRemainingDepositRefund');
        if (rowRemainingRefund && inpRemainingRefund) {
            inpRemainingRefund.value = `₱${remainingDeposit.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            if (remainingDeposit > 0) {
                rowRemainingRefund.classList.remove('hidden');
            } else {
                rowRemainingRefund.classList.add('hidden');
            }
        }

        // Update Final Balance To Pay in settlement column
        const inpTotalAmountToPay = document.getElementById('inpTotalAmountToPay');
        if (inpTotalAmountToPay) {
            inpTotalAmountToPay.value = `₱${finalBalanceToPay.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        // Update deposit status badge if present
        const badge = document.getElementById('depositStatusBadge');
        if (badge) {
            if (currentDeposit > 0) {
                if (applyDeposit) {
                    badge.textContent = 'HELD IN TRUST (APPLIED)';
                    badge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300';
                } else {
                    badge.textContent = 'HELD IN TRUST (NOT APPLIED)';
                    badge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300';
                }
            } else {
                badge.textContent = 'NONE';
                badge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700';
            }
        }

        // Update printable slip links
        const depositSlipTop = document.getElementById('depositSlipTopLink');
        if (depositSlipTop) {
            depositSlipTop.href = currentDeposit > 0 ? `${baseDepositSlipUrl}?deposit=${currentDeposit}` : baseDepositSlipUrl;
        }

        const depositSlipBottom = document.getElementById('depositSlipBottomLink');
        if (depositSlipBottom) {
            depositSlipBottom.href = currentDeposit > 0 ? `${baseDepositSlipUrl}?deposit=${currentDeposit}` : baseDepositSlipUrl;
        }

        const depositRefund = document.getElementById('depositRefundLink');
        if (depositRefund) {
            depositRefund.href = currentDeposit > 0 ? `${baseDepositRefundUrl}?deposit=${currentDeposit}&apply_deposit=${applyDeposit ? 1 : 0}&refund=${remainingDeposit}` : baseDepositRefundUrl;
        }

        // Re-sync payment and change calculations
        togglePaymentInputs();
    }

    function syncBillingStatementUrl() {
        const billingLink = document.getElementById('billingStatementLink');
        if (!billingLink) return;

        const method = document.getElementById('paymentMethodSelect')?.value || 'cash';
        const cashTendered = parseFloat(document.getElementById('inpCashTendered')?.value) || 0;
        const gcashAmount = parseFloat(document.getElementById('inpGcashAmount')?.value) || 0;
        const gcashRef = document.getElementById('inpGcashRef')?.value?.trim() || '';
        const discountRef = document.getElementById('inpDiscountRef')?.value?.trim() || '';

        let change = 0;
        if (method === 'cash') {
            change = cashTendered > 0 ? Math.max(0, cashTendered - finalBalanceToPay) : 0;
        } else if (method === 'gcash') {
            change = 0;
        } else if (method === 'split') {
            const remainingCashDue = Math.max(0, finalBalanceToPay - gcashAmount);
            change = cashTendered > 0 ? Math.max(0, cashTendered - remainingCashDue) : 0;
        }

        const url = new URL(baseBillingUrl);
        if (currentDeposit > 0) {
            url.searchParams.set('deposit', currentDeposit);
        }
        url.searchParams.set('apply_deposit', applyDeposit ? '1' : '0');
        url.searchParams.set('discount_type', currentDiscountType);
        if (discountRef) {
            url.searchParams.set('discount_id_ref', discountRef);
        }
        url.searchParams.set('payment_method', method);
        if (cashTendered > 0) {
            url.searchParams.set('cash_tendered', cashTendered);
        }
        if (gcashAmount > 0) {
            url.searchParams.set('gcash_amount', gcashAmount);
        }
        if (gcashRef) {
            url.searchParams.set('gcash_reference', gcashRef);
        }
        if (change > 0) {
            url.searchParams.set('change_due', change);
        }

        billingLink.href = url.toString();
    }

    function saveDepositToServer(deposit) {
        saveSettlementDraftToServer();
    }

    function saveSettlementDraftToServer() {
        const csrfToken = document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}';
        const method = document.getElementById('paymentMethodSelect')?.value || 'cash';
        const cashTendered = parseFloat(document.getElementById('inpCashTendered')?.value) || 0;
        const gcashAmount = parseFloat(document.getElementById('inpGcashAmount')?.value) || 0;
        const gcashRef = document.getElementById('inpGcashRef')?.value?.trim() || '';
        const discountRef = document.getElementById('inpDiscountRef')?.value?.trim() || '';

        let change = 0;
        if (method === 'cash') {
            change = cashTendered > 0 ? Math.max(0, cashTendered - finalBalanceToPay) : 0;
        } else if (method === 'gcash') {
            change = 0;
        } else if (method === 'split') {
            const remainingCashDue = Math.max(0, finalBalanceToPay - gcashAmount);
            change = cashTendered > 0 ? Math.max(0, cashTendered - remainingCashDue) : 0;
        }

        fetch("{{ route('folios.deposit', $folio->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                security_deposit: currentDeposit,
                apply_deposit: applyDeposit ? 1 : 0,
                discount_type: currentDiscountType,
                discount_id_ref: discountRef,
                payment_method: method,
                cash_tendered: cashTendered,
                gcash_amount: gcashAmount,
                gcash_reference: gcashRef,
                change_due: change
            })
        })
        .then(res => res.json())
        .catch(err => console.error('Error auto-saving settlement draft:', err));
    }

    function togglePaymentInputs() {
        const method = document.getElementById('paymentMethodSelect').value;
        const rowCash = document.getElementById('rowCashTendered');
        const rowGcash = document.getElementById('rowGcashAmount');
        const rowRef = document.getElementById('rowGcashRef');
        const inpGcash = document.getElementById('inpGcashAmount');
        const inpCash = document.getElementById('inpCashTendered');

        if (method === 'cash') {
            rowCash.style.display = 'flex';
            rowGcash.style.display = 'none';
            rowRef.style.display = 'none';
            inpGcash.value = '';
        } else if (method === 'gcash') {
            rowCash.style.display = 'none';
            rowGcash.style.display = 'flex';
            rowRef.style.display = 'flex';
            inpCash.value = '';
            inpGcash.value = finalBalanceToPay.toFixed(2);
        } else if (method === 'split') {
            rowCash.style.display = 'flex';
            rowGcash.style.display = 'flex';
            rowRef.style.display = 'flex';
        }

        calculateChange();
    }

    function calculateChange() {
        const method = document.getElementById('paymentMethodSelect').value;
        const cashTendered = parseFloat(document.getElementById('inpCashTendered').value) || 0;
        const gcashAmount = parseFloat(document.getElementById('inpGcashAmount').value) || 0;
        let change = 0;

        if (method === 'cash') {
            change = cashTendered > 0 ? Math.max(0, cashTendered - finalBalanceToPay) : 0;
        } else if (method === 'gcash') {
            change = 0;
        } else if (method === 'split') {
            const remainingCashDue = Math.max(0, finalBalanceToPay - gcashAmount);
            change = cashTendered > 0 ? Math.max(0, cashTendered - remainingCashDue) : 0;
        }

        document.getElementById('inpChange').value = `₱${change.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

        syncBillingStatementUrl();

        clearTimeout(depositSaveTimer);
        depositSaveTimer = setTimeout(() => {
            saveSettlementDraftToServer();
        }, 500);
    }

    // Initialize display state
    togglePaymentInputs();
</script>
@endpush
@endsection
