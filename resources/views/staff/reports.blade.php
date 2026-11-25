@extends('staff.layout')

@section('title', 'Sales report · SafeServe')
@section('heading', 'Sales report')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-charcoal-700">Daily income, weekly and monthly totals, and a month-by-month sales comparison for {{ $restaurant->name }}.</p>
            <p class="mt-1 text-xs text-charcoal-700/70">Cancelled orders are excluded. This is revenue only — ingredient costs are not recorded yet, so profit is not shown.</p>
        </div>
        <span class="rounded-full bg-forest-900 px-4 py-2 text-xs font-semibold text-white">{{ $restaurant->currency }} {{ number_format($summary['range_revenue'], 0) }} in {{ strtolower($rangeLabel) }}</span>
    </div>

    <section class="mt-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4" aria-label="Sales summary">
        <article class="rounded-2xl border border-forest-900/10 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Revenue today</p>
            <p class="mt-3 text-2xl font-semibold text-forest-950">{{ $restaurant->currency }} {{ number_format($summary['today_revenue'], 0) }}</p>
            <p class="mt-1 text-xs text-charcoal-700/70">{{ $summary['today_orders'] }} order(s) today</p>
        </article>
        <article class="rounded-2xl border border-forest-900/10 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Revenue {{ strtolower($rangeLabel) }}</p>
            <p class="mt-3 text-2xl font-semibold text-forest-950">{{ $restaurant->currency }} {{ number_format($summary['range_revenue'], 0) }}</p>
            <p class="mt-1 text-xs text-charcoal-700/70">{{ $summary['range_orders'] }} order(s) in range</p>
        </article>
        <article class="rounded-2xl border border-forest-900/10 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Daily average</p>
            <p class="mt-3 text-2xl font-semibold text-forest-950">{{ $restaurant->currency }} {{ number_format($summary['range_revenue'] / $dailyIncome->count(), 0) }}</p>
            <p class="mt-1 text-xs text-charcoal-700/70">Across {{ $dailyIncome->count() }} day(s)</p>
        </article>
        <article class="rounded-2xl border border-forest-900/10 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Best selling month</p>
            <p class="mt-3 text-2xl font-semibold text-forest-950">{{ $bestMonth['label'] ?? 'No sales yet' }}</p>
            <p class="mt-1 text-xs text-charcoal-700/70">{{ $bestMonth ? $restaurant->currency.' '.number_format($bestMonth['revenue'], 0) : 'Last six months' }}</p>
        </article>
    </section>

    <section class="mt-12" aria-labelledby="daily-income-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="daily-income-heading" class="text-xl font-semibold text-forest-950">Daily income</h2>
                <p class="mt-1 text-sm text-charcoal-700/75">Choose a range to see income for each day.</p>
            </div>
            <div class="flex gap-2" role="group" aria-label="Daily income range">
                <a href="{{ route('staff.reports.sales', ['range' => 'week']) }}" @class([
                    'rounded-full px-4 py-2 text-xs font-semibold transition',
                    'bg-forest-900 text-white' => $range === 'week',
                    'border border-forest-900/15 bg-white text-forest-900 hover:bg-forest-900/5' => $range !== 'week',
                ]) @if($range === 'week') aria-current="page" @endif>Last 7 days</a>
                <a href="{{ route('staff.reports.sales', ['range' => 'month']) }}" @class([
                    'rounded-full px-4 py-2 text-xs font-semibold transition',
                    'bg-forest-900 text-white' => $range === 'month',
                    'border border-forest-900/15 bg-white text-forest-900 hover:bg-forest-900/5' => $range !== 'month',
                ]) @if($range === 'month') aria-current="page" @endif>Last 30 days</a>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto rounded-3xl border border-forest-900/10 bg-white shadow-sm">
            <table class="w-full min-w-[640px] divide-y divide-forest-900/10">
                <caption class="sr-only">Daily income for {{ strtolower($rangeLabel) }}</caption>
                <thead class="bg-forest-950/5">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Date</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Orders</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Income</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-forest-900/10">
                    @foreach ($dailyIncome as $day)
                        <tr class="{{ $day['date'] === today()->toDateString() ? 'bg-emerald-50/60' : '' }}">
                            <td class="px-5 py-3 text-sm text-charcoal-900">{{ $day['label'] }}</td>
                            <td class="px-5 py-3 text-right text-sm text-charcoal-700">{{ $day['orders'] }}</td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-forest-900">{{ $restaurant->currency }} {{ number_format($day['revenue'], 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-forest-950/5">
                    <tr>
                        <th scope="row" class="px-5 py-3 text-left text-sm font-semibold text-forest-950">Total</th>
                        <td class="px-5 py-3 text-right text-sm font-semibold text-forest-950">{{ $summary['range_orders'] }}</td>
                        <td class="px-5 py-3 text-right text-sm font-semibold text-forest-950">{{ $restaurant->currency }} {{ number_format($summary['range_revenue'], 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="mt-12" aria-labelledby="monthly-heading">
        <div>
            <h2 id="monthly-heading" class="text-xl font-semibold text-forest-950">Monthly comparison</h2>
            <p class="mt-1 text-sm text-charcoal-700/75">Which of the last six months brought in the most sales.</p>
        </div>

        <div class="mt-6 overflow-x-auto rounded-3xl border border-forest-900/10 bg-white shadow-sm">
            <table class="w-full min-w-[640px] divide-y divide-forest-900/10">
                <caption class="sr-only">Sales by month for the last six months</caption>
                <thead class="bg-forest-950/5">
                    <tr>
                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Month</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Orders</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Sales</th>
                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-charcoal-700/70">Highlight</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-forest-900/10">
                    @foreach ($monthlySales as $month)
                        <tr class="{{ $bestMonth && $bestMonth['key'] === $month['key'] ? 'bg-emerald-50/60' : '' }}">
                            <td class="px-5 py-3 text-sm font-medium text-forest-950">{{ $month['label'] }}</td>
                            <td class="px-5 py-3 text-right text-sm text-charcoal-700">{{ $month['orders'] }}</td>
                            <td class="px-5 py-3 text-right text-sm font-medium text-forest-900">{{ $restaurant->currency }} {{ number_format($month['revenue'], 0) }}</td>
                            <td class="px-5 py-3 text-right">
                                @if ($bestMonth && $bestMonth['key'] === $month['key'])
                                    <span class="rounded-full bg-emerald-700 px-3 py-1 text-xs font-semibold text-white">Best month</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
