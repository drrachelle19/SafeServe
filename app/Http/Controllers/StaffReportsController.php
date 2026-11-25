<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StaffReportsController extends Controller
{
    private const RANGE_DAYS = ['week' => 7, 'month' => 30];

    private const EXCLUDED_STATUSES = ['cancelled'];

    public function index(Request $request): View
    {
        Gate::authorize('manage-restaurant');

        $restaurantId = Auth::user()?->restaurant_id;

        if (! $restaurantId || ! DB::table('restaurants')->where('id', $restaurantId)->exists()) {
            abort(403, 'No restaurant is assigned to this staff account.');
        }

        $restaurant = DB::table('restaurants')->where('id', $restaurantId)->first();

        $requestedRange = $request->string('range')->toString();
        $range = array_key_exists($requestedRange, self::RANGE_DAYS) ? $requestedRange : 'week';
        $days = self::RANGE_DAYS[$range];
        $rangeLabel = $range === 'week' ? 'Last 7 days' : 'Last 30 days';

        $sales = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->whereNotIn('status', self::EXCLUDED_STATUSES);

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $rangeStart = now()->startOfDay()->subDays($days - 1);

        $summary = [
            'today_revenue' => (float) (clone $sales)->whereBetween('created_at', [$todayStart, $todayEnd])->sum('total'),
            'today_orders' => (clone $sales)->whereBetween('created_at', [$todayStart, $todayEnd])->count(),
            'range_revenue' => (float) (clone $sales)->whereBetween('created_at', [$rangeStart, $todayEnd])->sum('total'),
            'range_orders' => (clone $sales)->whereBetween('created_at', [$rangeStart, $todayEnd])->count(),
        ];

        $dailyRows = (clone $sales)
            ->whereBetween('created_at', [$rangeStart, $todayEnd])
            ->select('created_at', 'total')
            ->get()
            ->groupBy(fn (object $order): string => Carbon::parse($order->created_at)->toDateString());

        $dailyIncome = collect(range(0, $days - 1))
            ->map(function (int $offset) use ($dailyRows, $days): array {
                $date = now()->startOfDay()->subDays($days - 1 - $offset);
                $ordersOnDate = $dailyRows->get($date->toDateString(), collect());

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('D, M j'),
                    'orders' => $ordersOnDate->count(),
                    'revenue' => (float) $ordersOnDate->sum(fn (object $order): float => (float) $order->total),
                ];
            });

        $monthlyRows = (clone $sales)
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->select('created_at', 'total')
            ->get();

        $monthlySales = collect(range(5, 0))
            ->map(function (int $monthsAgo) use ($monthlyRows): array {
                $month = now()->startOfMonth()->subMonths($monthsAgo);
                $key = $month->format('Y-m');
                $ordersInMonth = $monthlyRows->filter(
                    fn (object $order): bool => Carbon::parse($order->created_at)->format('Y-m') === $key
                );

                return [
                    'key' => $key,
                    'label' => $month->format('M Y'),
                    'orders' => $ordersInMonth->count(),
                    'revenue' => (float) $ordersInMonth->sum(fn (object $order): float => (float) $order->total),
                ];
            });

        $bestMonth = $monthlySales->sortByDesc('revenue')->firstWhere('revenue', '>', 0);

        return view('staff.reports', compact(
            'restaurant',
            'range',
            'rangeLabel',
            'summary',
            'dailyIncome',
            'monthlySales',
            'bestMonth'
        ));
    }
}
