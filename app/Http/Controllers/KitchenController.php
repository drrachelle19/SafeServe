<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KitchenController extends Controller
{
    private const ACTIVE_STATUSES = ['pending', 'confirmed', 'preparing', 'ready'];

    private const ALLOWED_STATUSES = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];

    public function index(): View
    {
        Gate::authorize('manage-orders');

        $restaurantId = $this->restaurantId();

        $orders = DB::table('orders')
            ->leftJoin('restaurant_tables', 'restaurant_tables.id', '=', 'orders.restaurant_table_id')
            ->leftJoin(
                DB::raw('(select order_id, count(*) as item_count from order_items group by order_id) as item_counts'),
                'item_counts.order_id',
                '=',
                'orders.id'
            )
            ->leftJoin(
                DB::raw("(select order_id, count(*) as declared_count, sum(case when risk_status = 'matched' then 1 else 0 end) as matched_count, sum(case when risk_status = 'unknown' then 1 else 0 end) as unknown_count, sum(case when staff_acknowledged_at is null and risk_status in ('matched','unknown') then 1 else 0 end) as unacknowledged_count from order_allergens group by order_id) as allergy_summary"),
                'allergy_summary.order_id',
                '=',
                'orders.id'
            )
            ->where('orders.restaurant_id', $restaurantId)
            ->whereIn('orders.status', self::ACTIVE_STATUSES)
            ->select(
                'orders.id',
                'orders.order_number',
                'orders.customer_name',
                'orders.customer_notes',
                'orders.status',
                'orders.allergy_review_status',
                'orders.total',
                'orders.created_at',
                'restaurant_tables.table_number',
                'item_counts.item_count',
                'allergy_summary.declared_count',
                'allergy_summary.matched_count',
                'allergy_summary.unknown_count',
                'allergy_summary.unacknowledged_count'
            )
            ->get()
            ->map(function (object $order): object {
                $order->priority = ((int) $order->matched_count * 4)
                    + ((int) $order->unknown_count * 2)
                    + (int) $order->unacknowledged_count;
                $order->created_at = Carbon::parse($order->created_at);
                $order->waiting_minutes = $order->created_at->diffInMinutes(now());

                return $order;
            })
            ->sortBy([
                fn (object $order): int => -$order->priority,
                fn (object $order): string => (string) $order->created_at,
            ])
            ->values();

        $orderIds = $orders->pluck('id');

        $orderItems = DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->orderBy('id')
            ->get()
            ->groupBy('order_id');

        $allergyAlerts = DB::table('order_allergens')
            ->leftJoin('order_items', 'order_items.id', '=', 'order_allergens.order_item_id')
            ->whereIn('order_allergens.order_id', $orderIds)
            ->select(
                'order_allergens.id',
                'order_allergens.order_id',
                'order_allergens.order_item_id',
                'order_allergens.allergen_name',
                'order_allergens.risk_status',
                'order_allergens.source',
                'order_allergens.staff_acknowledged_at',
                'order_allergens.staff_note',
                'order_items.item_name'
            )
            ->get()
            ->each(function (object $alert): void {
                if ($alert->staff_acknowledged_at) {
                    $alert->staff_acknowledged_at = Carbon::parse($alert->staff_acknowledged_at);
                }
            })
            ->sortByDesc(fn (object $alert): int => match ($alert->risk_status) {
                'matched' => 3,
                'unknown' => 2,
                default => 1,
            })
            ->groupBy('order_id');

        foreach ($orders as $order) {
            $order->items = $orderItems->get($order->id, collect());
            $order->allergyAlerts = $allergyAlerts->get($order->id, collect());
        }

        $completedOrders = DB::table('orders')
            ->leftJoin('restaurant_tables', 'restaurant_tables.id', '=', 'orders.restaurant_table_id')
            ->where('orders.restaurant_id', $restaurantId)
            ->where('orders.status', 'completed')
            ->where('orders.updated_at', '>=', now()->startOfDay())
            ->select(
                'orders.id',
                'orders.order_number',
                'orders.customer_name',
                'orders.total',
                'orders.updated_at as completed_at',
                'restaurant_tables.table_number'
            )
            ->latest('orders.updated_at')
            ->limit(10)
            ->get()
            ->each(function (object $order): void {
                $order->completed_at = Carbon::parse($order->completed_at);
            });

        $workerLogs = DB::table('order_worker_logs')
            ->join('users', 'users.id', '=', 'order_worker_logs.user_id')
            ->whereIn('order_worker_logs.order_id', $completedOrders->pluck('id'))
            ->select(
                'order_worker_logs.order_id',
                'users.name',
                'order_worker_logs.role',
                'order_worker_logs.assignment_source',
                'order_worker_logs.logged_at'
            )
            ->orderBy('order_worker_logs.logged_at')
            ->get()
            ->groupBy('order_id');

        foreach ($completedOrders as $order) {
            $order->workers = $workerLogs->get($order->id, collect());
        }

        $cancelledOrders = DB::table('orders')
            ->leftJoin('restaurant_tables', 'restaurant_tables.id', '=', 'orders.restaurant_table_id')
            ->where('orders.restaurant_id', $restaurantId)
            ->where('orders.status', 'cancelled')
            ->where('orders.updated_at', '>=', now()->startOfDay())
            ->select(
                'orders.id',
                'orders.order_number',
                'orders.customer_name',
                'orders.updated_at as cancelled_at',
                'restaurant_tables.table_number'
            )
            ->latest('orders.updated_at')
            ->limit(10)
            ->get()
            ->each(function (object $order): void {
                $order->cancelled_at = Carbon::parse($order->cancelled_at);
            });

        $cancellationEvents = DB::table('order_status_events')
            ->whereIn('order_id', $cancelledOrders->pluck('id'))
            ->where('to_status', 'cancelled')
            ->orderBy('created_at')
            ->get()
            ->groupBy('order_id');

        foreach ($cancelledOrders as $order) {
            $cancellationEvent = $cancellationEvents->get($order->id, collect())->last();
            $order->cancellation_note = $cancellationEvent?->note;
        }

        return view('staff.kitchen', compact('orders', 'completedOrders', 'cancelledOrders'));
    }

    public function updateStatus(Request $request, int $order): RedirectResponse
    {
        Gate::authorize('manage-orders');

        $validated = $request->validate([
            'status' => ['required', Rule::in(self::ALLOWED_STATUSES)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $orderRow = DB::table('orders')
            ->where('id', $order)
            ->where('restaurant_id', $this->restaurantId())
            ->first();

        if (! $orderRow) {
            abort(404, 'Order not found.');
        }

        if ($validated['status'] === 'completed'
            && ! in_array($orderRow->status, ['ready', 'completed'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only orders that are Ready can be marked Completed.',
            ]);
        }

        if ($orderRow->status !== $validated['status']) {
            DB::transaction(function () use ($order, $orderRow, $validated): void {
                DB::table('orders')
                    ->where('id', $order)
                    ->update([
                        'status' => $validated['status'],
                        'updated_at' => now(),
                    ]);

                DB::table('order_status_events')->insert([
                    'order_id' => $order,
                    'from_status' => $orderRow->status,
                    'to_status' => $validated['status'],
                    'note' => $validated['note'] ?? null,
                    'changed_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($validated['status'] === 'completed') {
                    $this->logCompletedOrderWorkers($order);
                    $this->assignNextRegisteredWorker($order);
                }
            });
        }

        return back()->with('success', 'Order status updated.');
    }

    public function acknowledgeAlert(Request $request, int $alert): RedirectResponse
    {
        Gate::authorize('manage-orders');

        $validated = $request->validate([
            'staff_note' => ['nullable', 'string', 'max:500'],
        ]);

        $alertRow = DB::table('order_allergens')
            ->join('orders', 'orders.id', '=', 'order_allergens.order_id')
            ->where('order_allergens.id', $alert)
            ->where('orders.restaurant_id', $this->restaurantId())
            ->select('order_allergens.*')
            ->first();

        if (! $alertRow) {
            abort(404, 'Allergy alert not found.');
        }

        DB::transaction(function () use ($alert, $alertRow, $validated): void {
            DB::table('order_allergens')
                ->where('id', $alert)
                ->update([
                    'staff_acknowledged_at' => now(),
                    'staff_note' => $validated['staff_note'] ?? null,
                    'updated_at' => now(),
                ]);

            $remainingAlerts = DB::table('order_allergens')
                ->where('order_id', $alertRow->order_id)
                ->whereNull('staff_acknowledged_at')
                ->whereIn('risk_status', ['matched', 'unknown'])
                ->exists();

            if (! $remainingAlerts) {
                DB::table('orders')
                    ->where('id', $alertRow->order_id)
                    ->update([
                        'allergy_review_status' => 'reviewed',
                        'updated_at' => now(),
                    ]);
            }
        });

        return back()->with('success', 'Allergy alert acknowledged.');
    }

    private function logCompletedOrderWorkers(int $orderId): void
    {
        $workerIds = DB::table('order_status_events')
            ->where('order_id', $orderId)
            ->whereNotNull('changed_by')
            ->distinct()
            ->pluck('changed_by')
            ->map(fn (mixed $id): int => (int) $id);

        $workers = DB::table('users')
            ->whereIn('id', $workerIds)
            ->whereIn('role', ['admin', 'manager', 'kitchen'])
            ->select('id', 'role')
            ->get();

        if ($workers->isEmpty()) {
            return;
        }

        $now = now();

        DB::table('order_worker_logs')->upsert(
            $workers->map(fn (object $worker): array => [
                'order_id' => $orderId,
                'user_id' => $worker->id,
                'role' => $worker->role,
                'assignment_source' => 'handler',
                'logged_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['order_id', 'user_id'],
            ['role', 'assignment_source', 'logged_at', 'updated_at']
        );
    }

    private function assignNextRegisteredWorker(int $orderId): void
    {
        $restaurantId = $this->restaurantId();

        $workerIds = DB::table('users')
            ->where('restaurant_id', $restaurantId)
            ->where('role', 'kitchen')
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id);

        if ($workerIds->isEmpty()) {
            return;
        }

        $lastWorkerId = (int) (DB::table('restaurant_worker_rotations')
            ->where('restaurant_id', $restaurantId)
            ->value('last_worker_id') ?? 0);

        $nextWorkerId = $workerIds->first(fn (int $id): bool => $id > $lastWorkerId) ?? $workerIds->first();

        $now = now();

        DB::table('restaurant_worker_rotations')->updateOrInsert(
            ['restaurant_id' => $restaurantId],
            [
                'last_worker_id' => $nextWorkerId,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('order_worker_logs')->upsert(
            [[
                'order_id' => $orderId,
                'user_id' => $nextWorkerId,
                'role' => 'kitchen',
                'assignment_source' => 'auto',
                'logged_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['order_id', 'user_id'],
            ['role', 'assignment_source', 'logged_at', 'updated_at']
        );
    }

    private function restaurantId(): int
    {
        $restaurantId = Auth::user()?->restaurant_id;

        if (! $restaurantId) {
            abort(403, 'No restaurant is assigned to this staff account.');
        }

        return (int) $restaurantId;
    }
}
