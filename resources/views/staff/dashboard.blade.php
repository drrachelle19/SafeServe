@extends('staff.layout')

@section('title', 'Overview · SafeServe')
@section('heading', 'Restaurant overview')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-charcoal-700">{{ $restaurant->name }} · {{ now()->format('l, F j, Y') }}</p>
            <p class="mt-1 text-xs text-charcoal-700/70">Live operations snapshot from orders placed through table QR codes.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('manage-restaurant')
                <a href="{{ route('staff.tables.qr') }}" class="inline-flex items-center gap-2 rounded-xl border border-forest-900/15 bg-white px-5 py-3 text-sm font-semibold text-forest-900 transition hover:bg-forest-900/5 focus:outline-none focus:ring-2 focus:ring-forest-700 focus:ring-offset-2">
                    Table QR codes
                    <span aria-hidden="true">↗</span>
                </a>
            @endcan
            <a href="{{ route('kitchen') }}" class="inline-flex items-center gap-2 rounded-xl bg-forest-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-forest-800 focus:outline-none focus:ring-2 focus:ring-forest-700 focus:ring-offset-2">
                Open kitchen board
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>

    <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Key metrics">
        <article class="rounded-2xl border border-forest-900/10 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Orders today</p>
            <p class="mt-3 text-4xl font-semibold text-forest-950">{{ $stats['orders_today'] }}</p>
            <p class="mt-2 text-xs text-charcoal-700/70">Placed since midnight</p>
        </article>
        <article class="rounded-2xl border border-forest-900/10 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Order value today</p>
            <p class="mt-3 text-3xl font-semibold text-forest-950">
                {{ $restaurant->currency }} {{ number_format($stats['order_value_today'], 0) }}
            </p>
            <p class="mt-2 text-xs text-charcoal-700/70">Calculated from submitted orders</p>
        </article>
        <article class="rounded-2xl border border-forest-900/10 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Active orders</p>
            <p class="mt-3 text-4xl font-semibold text-forest-950">{{ $stats['active_orders'] }}</p>
            <p class="mt-2 text-xs text-charcoal-700/70">Pending, preparing or ready</p>
        </article>
        <article class="rounded-2xl border border-red-200 bg-red-50 p-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-red-800/80">Allergy alerts</p>
            <p class="mt-3 text-4xl font-semibold text-red-900">{{ $stats['allergy_alerts'] }}</p>
            <p class="mt-2 text-xs text-red-800/80">Unacknowledged matches or unknowns</p>
        </article>
    </section>

    <section class="mt-8 grid gap-6 xl:grid-cols-3">
        <article class="rounded-2xl border border-forest-900/10 bg-white p-6 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-forest-950">Order pipeline</h2>
                    <p class="mt-1 text-xs text-charcoal-700/70">All orders for this restaurant</p>
                </div>
                <a href="{{ route('kitchen') }}" class="text-sm font-semibold text-forest-700 hover:underline">View board</a>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-4">
                @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing', 'ready' => 'Ready'] as $statusKey => $statusLabel)
                    <div class="rounded-xl border border-forest-900/10 bg-ivory-50 p-4">
                        <p class="text-xs font-medium text-charcoal-700/70">{{ $statusLabel }}</p>
                        <p class="mt-2 text-2xl font-semibold text-forest-950">{{ $statusCounts->get($statusKey, 0) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <caption class="sr-only">Recent restaurant orders</caption>
                    <thead>
                        <tr class="border-b border-forest-900/10 text-xs uppercase tracking-wider text-charcoal-700/70">
                            <th scope="col" class="pb-3 font-semibold">Order</th>
                            <th scope="col" class="pb-3 font-semibold">Table</th>
                            <th scope="col" class="pb-3 font-semibold">Items</th>
                            <th scope="col" class="pb-3 font-semibold">Allergy info</th>
                            <th scope="col" class="pb-3 font-semibold">Total</th>
                            <th scope="col" class="pb-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-forest-900/5">
                        @forelse ($recentOrders as $order)
                            <tr>
                                <td class="py-4">
                                    <p class="font-semibold text-forest-950">{{ $order->order_number }}</p>
                                    <p class="mt-1 text-xs text-charcoal-700/70">{{ $order->customer_name ?: 'Guest' }} · {{ $order->created_at->format('H:i') }}</p>
                                </td>
                                <td class="py-4">{{ $order->table_number ?: 'Takeaway' }}</td>
                                <td class="py-4">{{ $order->item_count ?: 0 }}</td>
                                <td class="py-4">
                                    @if (($order->risk_count ?? 0) > 0)
                                        <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">{{ $order->risk_count }} risk flag(s)</span>
                                    @elseif (($order->declared_count ?? 0) > 0)
                                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">{{ $order->declared_count }} declared</span>
                                    @else
                                        <span class="text-xs text-charcoal-700/70">None declared</span>
                                    @endif
                                </td>
                                <td class="py-4 font-medium">{{ $restaurant->currency }} {{ number_format($order->total, 0) }}</td>
                                <td class="py-4">
                                    <span class="inline-flex rounded-full bg-forest-900/5 px-3 py-1 text-xs font-semibold capitalize text-forest-900">{{ $order->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-sm text-charcoal-700/70">
                                    No orders yet. Share a table QR code to get started.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-forest-950">Allergy review queue</h2>
            <p class="mt-1 text-xs text-charcoal-700/70">Matches and unknowns that still need acknowledgement.</p>

            <div class="mt-6 space-y-3">
                @forelse ($alerts as $alert)
                    <div class="rounded-xl border border-red-100 bg-red-50/60 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-red-900">{{ $alert->allergen_name }}</p>
                                <p class="mt-1 text-xs text-red-800/80">{{ $alert->item_name ?: 'Order item' }} · {{ $alert->order_number }}</p>
                            </div>
                            <span class="rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wide {{ $alert->risk_status === 'matched' ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-900' }}">
                                {{ $alert->risk_status === 'matched' ? 'Match' : 'Unknown' }}
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-red-800/70">Table {{ $alert->table_number ?: '—' }} · {{ $alert->created_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-5 text-sm text-emerald-900">
                        No unacknowledged high-priority alerts.
                    </div>
                @endforelse
            </div>

            <a href="{{ route('kitchen') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-xl border border-forest-900/15 px-4 py-3 text-sm font-semibold text-forest-900 transition hover:bg-forest-900/5">
                Review in kitchen
            </a>
            <p class="mt-4 text-[11px] leading-5 text-charcoal-700/70">
                Alerts support staff review. They do not guarantee absence of allergens or cross-contact.
            </p>
        </article>
    </section>
@endsection
