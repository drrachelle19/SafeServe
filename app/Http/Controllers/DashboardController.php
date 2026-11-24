<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(): View
    {
        Gate::authorize('access-staff-area');

        $restaurantId = Auth::user()?->restaurant_id;

        if (! $restaurantId || ! DB::table('restaurants')->where('id', $restaurantId)->exists()) {
            abort(403, 'No restaurant is assigned to this staff account.');
        }

        $restaurant = DB::table('restaurants')
            ->where('id', $restaurantId)
            ->first();

        $todayOrders = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today());

        $highPriorityAlerts = DB::table('order_allergens')
            ->join('orders', 'orders.id', '=', 'order_allergens.order_id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereNull('order_allergens.staff_acknowledged_at')
            ->whereIn('order_allergens.risk_status', ['matched', 'unknown'])
            ->whereNotIn('orders.status', ['completed', 'cancelled']);

        $stats = [
            'orders_today' => (clone $todayOrders)->count(),
            'order_value_today' => (float) (clone $todayOrders)->sum('total'),
            'active_orders' => DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready'])
                ->count(),
            'allergy_alerts' => (clone $highPriorityAlerts)->count(),
        ];

        $statusCounts = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $recentOrders = DB::table('orders')
            ->leftJoin('restaurant_tables', 'restaurant_tables.id', '=', 'orders.restaurant_table_id')
            ->leftJoin(
                DB::raw('(select order_id, count(*) as item_count from order_items group by order_id) as item_counts'),
                'item_counts.order_id',
                '=',
                'orders.id'
            )
            ->leftJoin(
                DB::raw("(select order_id, count(*) as declared_count, sum(case when risk_status in ('matched','unknown') then 1 else 0 end) as risk_count from order_allergens group by order_id) as allergy_counts"),
                'allergy_counts.order_id',
                '=',
                'orders.id'
            )
            ->where('orders.restaurant_id', $restaurantId)
            ->select(
                'orders.id',
                'orders.order_number',
                'orders.customer_name',
                'orders.total',
                'orders.status',
                'orders.allergy_review_status',
                'orders.created_at',
                'restaurant_tables.table_number',
                'item_counts.item_count',
                'allergy_counts.declared_count',
                'allergy_counts.risk_count'
            )
            ->latest('orders.created_at')
            ->limit(8)
            ->get()
            ->each(function (object $order): void {
                $order->created_at = Carbon::parse($order->created_at);
            });

        $alerts = (clone $highPriorityAlerts)
            ->leftJoin('order_items', 'order_items.id', '=', 'order_allergens.order_item_id')
            ->leftJoin('restaurant_tables', 'restaurant_tables.id', '=', 'orders.restaurant_table_id')
            ->select(
                'order_allergens.id',
                'order_allergens.risk_status',
                'order_allergens.allergen_name',
                'order_allergens.source',
                'order_allergens.created_at',
                'orders.id as order_id',
                'orders.order_number',
                'orders.status as order_status',
                'order_items.item_name',
                'restaurant_tables.table_number'
            )
            ->latest('order_allergens.created_at')
            ->limit(6)
            ->get()
            ->each(function (object $alert): void {
                $alert->created_at = Carbon::parse($alert->created_at);
            });

        return view('staff.dashboard', compact(
            'restaurant',
            'stats',
            'statusCounts',
            'recentOrders',
            'alerts'
        ));
    }
}
