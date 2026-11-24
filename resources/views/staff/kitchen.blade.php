@extends('staff.layout')

@section('title', 'Kitchen board · SafeServe')
@section('heading', 'Kitchen board')

@section('head')
    <meta http-equiv="refresh" content="60">
@endsection

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-charcoal-700">Active orders sorted by allergy risk, then oldest first.</p>
            <p class="mt-1 text-xs text-charcoal-700/70">Acknowledge every match or unknown before preparing the order.</p>
        </div>
        <span class="rounded-full bg-forest-900 px-4 py-2 text-xs font-semibold text-white">{{ $orders->count() }} active order(s)</span>
    </div>

    <div class="mt-8 space-y-6">
        @forelse ($orders as $order)
            @php
                $riskClass = $order->matched_count > 0
                    ? 'border-red-300 bg-red-50'
                    : ($order->unknown_count > 0 ? 'border-amber-300 bg-amber-50' : 'border-forest-900/10 bg-white');
            @endphp

            <article class="overflow-hidden rounded-3xl border {{ $riskClass }} shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-black/5 px-6 py-5">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-xl font-semibold text-forest-950">{{ $order->order_number }}</h2>
                            <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-semibold capitalize text-forest-900">Table {{ $order->table_number ?: '—' }}</span>
                            <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-semibold capitalize text-forest-900">{{ $order->status }}</span>
                        </div>
                        <p class="mt-2 text-xs text-charcoal-700/75">
                            {{ $order->customer_name ?: 'Guest' }} · {{ $order->waiting_minutes }} min waiting · {{ $order->item_count ?: 0 }} item(s) · {{ $order->created_at->format('H:i') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($order->matched_count > 0)
                            <span class="rounded-full bg-red-600 px-3 py-1 text-xs font-bold text-white">{{ $order->matched_count }} allergen match(es)</span>
                        @endif
                        @if ($order->unknown_count > 0)
                            <span class="rounded-full bg-amber-400 px-3 py-1 text-xs font-bold text-amber-950">{{ $order->unknown_count }} unknown warning(s)</span>
                        @endif
                        @if ($order->unacknowledged_count > 0)
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-red-800">{{ $order->unacknowledged_count }} awaiting acknowledgement</span>
                        @endif
                    </div>
                </div>

                <div class="grid gap-6 px-6 py-6 lg:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-forest-950">Order items</h3>
                        <ul class="mt-4 space-y-3">
                            @forelse ($order->items as $item)
                                <li class="flex items-start justify-between gap-4 rounded-xl border border-forest-900/10 bg-white/80 px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-forest-950">{{ $item->quantity }} × {{ $item->item_name }}</p>
                                        @if ($item->special_instructions)
                                            <p class="mt-1 text-xs text-charcoal-700/70">{{ $item->special_instructions }}</p>
                                        @endif
                                    </div>
                                    <p class="text-sm font-medium text-forest-900">{{ number_format($item->line_total, 0) }}</p>
                                </li>
                            @empty
                                <li class="text-sm text-charcoal-700/70">No line items recorded.</li>
                            @endforelse
                        </ul>

                        @if ($order->customer_notes)
                            <div class="mt-4 rounded-xl border border-forest-900/10 bg-white/80 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Customer instructions</p>
                                <p class="mt-2 text-sm text-charcoal-900">{{ $order->customer_notes }}</p>
                            </div>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-forest-950">Allergy declarations</h3>
                        <div class="mt-4 space-y-3">
                            @forelse ($order->allergyAlerts as $alert)
                                <div class="rounded-xl border bg-white/90 px-4 py-3 {{ $alert->risk_status === 'matched' ? 'border-red-200' : ($alert->risk_status === 'unknown' ? 'border-amber-200' : 'border-forest-900/10') }}">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-forest-950">{{ $alert->allergen_name }}</p>
                                            <p class="mt-1 text-xs text-charcoal-700/70">{{ $alert->item_name ?: 'Order item' }}</p>
                                        </div>
                                        <span class="rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wide {{ $alert->risk_status === 'matched' ? 'bg-red-600 text-white' : ($alert->risk_status === 'unknown' ? 'bg-amber-400 text-amber-950' : 'bg-forest-900/10 text-forest-900') }}">
                                            {{ str_replace('_', ' ', $alert->risk_status) }}
                                        </span>
                                    </div>

                                    @if ($alert->staff_acknowledged_at)
                                        <p class="mt-3 text-xs font-medium text-emerald-800">Acknowledged {{ $alert->staff_acknowledged_at->diffForHumans() }}</p>
                                        @if ($alert->staff_note)
                                            <p class="mt-1 text-xs text-charcoal-700/75">Note: {{ $alert->staff_note }}</p>
                                        @endif
                                    @elseif ($alert->risk_status === 'matched' || $alert->risk_status === 'unknown')
                                        <form method="POST" action="{{ route('kitchen.alerts.acknowledge', $alert->id) }}" class="mt-3 flex flex-wrap gap-2">
                                            @csrf
                                            <input type="text" name="staff_note" maxlength="500" placeholder="Staff note (optional)"
                                                class="min-w-48 grow rounded-lg border border-forest-900/15 bg-ivory-50 px-3 py-2 text-xs outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                                            <button type="submit" class="rounded-lg bg-forest-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-forest-800">
                                                Acknowledge
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="rounded-xl border border-forest-900/10 bg-white/80 px-4 py-3 text-sm text-charcoal-700/70">
                                    No allergies declared for this order.
                                </p>
                            @endforelse
                        </div>
                        <p class="mt-3 text-[11px] leading-5 text-charcoal-700/70">
                            “No known match” means no recorded match in current ingredient data — not allergen-free.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('kitchen.orders.status', $order->id) }}" class="flex flex-wrap items-end gap-3 border-t border-black/5 bg-white/60 px-6 py-5">
                    @csrf
                    <div>
                        <label for="status-{{ $order->id }}" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Update status</label>
                        <select id="status-{{ $order->id }}" name="status"
                            class="mt-2 rounded-xl border border-forest-900/15 bg-white px-4 py-2 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                            @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing', 'ready' => 'Ready', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" @selected($order->status === $statusKey)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grow">
                        <label for="note-{{ $order->id }}" class="block text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Status note (optional)</label>
                        <input id="note-{{ $order->id }}" type="text" name="note" maxlength="500"
                            class="mt-2 w-full rounded-xl border border-forest-900/15 bg-white px-4 py-2 text-sm outline-none focus:border-forest-700 focus:ring-2 focus:ring-forest-700/20">
                    </div>
                    <button type="submit" class="rounded-xl bg-forest-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-800">
                        Save status
                    </button>
                </form>
            </article>
        @empty
            <div class="rounded-3xl border border-forest-900/10 bg-white px-8 py-16 text-center shadow-sm">
                <p class="text-lg font-semibold text-forest-950">The kitchen board is clear.</p>
                <p class="mt-2 text-sm text-charcoal-700/70">New orders from table QR codes will appear here automatically.</p>
            </div>
        @endforelse
    </div>
@endsection
