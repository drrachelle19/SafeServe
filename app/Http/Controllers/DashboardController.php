<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $restaurantId = DB::table('restaurants')->value('id');

        $stats = [
            'orders' => 0,
            'revenue' => 0,
            'alerts' => 0,
            'pending' => 0,
        ];

        $recentOrders = collect();
        $alerts = collect();

        if ($restaurantId) {
            $todayOrders = DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->whereDate('created_at', today());

            $stats['orders'] = (clone $todayOrders)->count();

            $stats['revenue'] = (clone $todayOrders)
                ->where('payment_status', 'paid')
                ->sum('total');

            $stats['pending'] = DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->whereIn('status', ['pending', 'confirmed', 'preparing'])
                ->count();

            $stats['alerts'] = DB::table('order_allergens')
                ->join('orders', 'orders.id', '=', 'order_allergens.order_id')
                ->where('orders.restaurant_id', $restaurantId)
                ->whereNull('order_allergens.staff_acknowledged_at')
                ->whereNotIn('orders.status', ['completed', 'cancelled'])
                ->count();

            $recentOrders = DB::table('orders')
                ->leftJoin('restaurant_tables', 'restaurant_tables.id', '=', 'orders.restaurant_table_id')
                ->where('orders.restaurant_id', $restaurantId)
                ->select(
                    'orders.id',
                    'orders.order_number',
                    'orders.total',
                    'orders.status',
                    'orders.created_at',
                    'restaurant_tables.table_number'
                )
                ->latest('orders.created_at')
                ->limit(8)
                ->get();

            $alerts = DB::table('order_allergens')
                ->join('orders', 'orders.id', '=', 'order_allergens.order_id')
                ->where('orders.restaurant_id', $restaurantId)
                ->whereNull('order_allergens.staff_acknowledged_at')
                ->whereNotIn('orders.status', ['completed', 'cancelled'])
                ->select(
                    'orders.order_number',
                    'orders.status',
                    'order_allergens.allergen_name',
                    'order_allergens.risk_status',
                    'order_allergens.source'
                )
                ->latest('order_allergens.created_at')
                ->limit(5)
                ->get();
        }

        return view('dashboard', compact('stats', 'recentOrders', 'alerts'));
    }
}
