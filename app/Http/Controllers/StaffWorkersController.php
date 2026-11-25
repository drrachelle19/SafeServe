<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffWorkersController extends Controller
{
    private const MAX_CSV_ROWS = 100;

    public function index(): View
    {
        Gate::authorize('manage-restaurant');

        $restaurantId = $this->restaurantId();
        $restaurant = DB::table('restaurants')->where('id', $restaurantId)->first();

        $workers = DB::table('users')
            ->where('restaurant_id', $restaurantId)
            ->where('role', 'kitchen')
            ->orderBy('name')
            ->get();

        $lastWorkerId = DB::table('restaurant_worker_rotations')
            ->where('restaurant_id', $restaurantId)
            ->value('last_worker_id');

        $lastAssignedWorker = $lastWorkerId
            ? DB::table('users')->where('id', $lastWorkerId)->value('name')
            : null;

        return view('staff.workers', compact('restaurant', 'workers', 'lastAssignedWorker'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage-restaurant');

        $restaurantId = $this->restaurantId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $this->createWorker($restaurantId, $validated['name'], $validated['email'], $validated['phone'] ?? null);

        return back()->with('success', "{$validated['name']} added to the worker list.");
    }

    public function upload(Request $request): RedirectResponse
    {
        Gate::authorize('manage-restaurant');

        $restaurantId = $this->restaurantId();

        $validated = $request->validate([
            'workers_csv' => ['required', 'file', 'mimes:csv,txt', 'extensions:csv', 'max:2048'],
        ]);

        $handle = fopen($validated['workers_csv']->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'workers_csv' => 'The CSV file could not be read.',
            ]);
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            throw ValidationException::withMessages([
                'workers_csv' => 'The CSV file is empty.',
            ]);
        }

        $columns = array_map(
            fn (mixed $value): string => strtolower(trim((string) $value)),
            $header
        );
        $nameIndex = array_search('name', $columns, true);
        $emailIndex = array_search('email', $columns, true);
        $phoneIndex = array_search('phone', $columns, true);

        if ($nameIndex === false || $emailIndex === false) {
            fclose($handle);

            throw ValidationException::withMessages([
                'workers_csv' => 'The CSV must have name and email columns.',
            ]);
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $hasValue = collect($row)->contains(fn (mixed $value): bool => trim((string) $value) !== '');

            if (! $hasValue) {
                continue;
            }

            $phone = $phoneIndex === false ? '' : trim((string) ($row[$phoneIndex] ?? ''));

            $rows[] = [
                'name' => trim((string) ($row[$nameIndex] ?? '')),
                'email' => strtolower(trim((string) ($row[$emailIndex] ?? ''))),
                'phone' => $phone === '' ? null : $phone,
            ];
        }

        fclose($handle);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'workers_csv' => 'The CSV has no worker rows.',
            ]);
        }

        if (count($rows) > self::MAX_CSV_ROWS) {
            throw ValidationException::withMessages([
                'workers_csv' => 'Upload at most '.self::MAX_CSV_ROWS.' workers at a time.',
            ]);
        }

        $validator = Validator::make($rows, [
            '*.name' => ['required', 'string', 'max:100'],
            '*.email' => ['required', 'email', 'max:255', 'distinct', Rule::unique('users', 'email')],
            '*.phone' => ['nullable', 'string', 'max:30'],
        ], [], [
            '*.name' => 'worker name',
            '*.email' => 'worker email',
            '*.phone' => 'worker phone',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'workers_csv' => $validator->errors()->first(),
            ]);
        }

        $created = collect($rows)->filter(
            fn (array $row): bool => $this->createWorker($restaurantId, $row['name'], $row['email'], $row['phone']) === 1
        )->count();

        return back()->with('success', "{$created} worker(s) uploaded from the CSV.");
    }

    public function toggleActive(int $worker): RedirectResponse
    {
        Gate::authorize('manage-restaurant');

        $restaurantId = $this->restaurantId();
        $workerRow = $this->findWorker($worker, $restaurantId);

        DB::table('users')
            ->where('id', $worker)
            ->update([
                'is_active' => ! $workerRow->is_active,
                'updated_at' => now(),
            ]);

        return back()->with('success', $workerRow->is_active
            ? "{$workerRow->name} removed from the rotation."
            : "{$workerRow->name} returned to the rotation.");
    }

    public function edit(int $worker): View
    {
        Gate::authorize('manage-restaurant');

        $restaurantId = $this->restaurantId();
        $workerRow = $this->findWorker($worker, $restaurantId);

        return view('staff.worker-edit', compact('workerRow'));
    }

    public function update(Request $request, int $worker): RedirectResponse
    {
        Gate::authorize('manage-restaurant');

        $restaurantId = $this->restaurantId();
        $workerRow = $this->findWorker($worker, $restaurantId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($workerRow->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        DB::table('users')
            ->where('id', $workerRow->id)
            ->update([
                'name' => $validated['name'],
                'email' => Str::lower($validated['email']),
                'phone' => $validated['phone'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()->route('staff.workers.index')
            ->with('success', "{$validated['name']} updated.");
    }

    public function destroy(int $worker): RedirectResponse
    {
        Gate::authorize('manage-restaurant');

        $restaurantId = $this->restaurantId();
        $workerRow = $this->findWorker($worker, $restaurantId);

        DB::table('users')->where('id', $workerRow->id)->delete();

        return redirect()->route('staff.workers.index')
            ->with('success', "{$workerRow->name} removed from the worker list.");
    }

    private function findWorker(int $worker, int $restaurantId): object
    {
        $workerRow = DB::table('users')
            ->where('id', $worker)
            ->where('restaurant_id', $restaurantId)
            ->where('role', 'kitchen')
            ->first();

        abort_unless($workerRow, 404, 'Worker not found.');

        return $workerRow;
    }

    private function createWorker(int $restaurantId, string $name, string $email, ?string $phone = null): int
    {
        $email = Str::lower($email);

        if (DB::table('users')->where('email', $email)->exists()) {
            return 0;
        }

        $now = now();

        DB::table('users')->insert([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'email_verified_at' => $now,
            'password' => Hash::make(Str::random(24)),
            'role' => 'kitchen',
            'restaurant_id' => $restaurantId,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return 1;
    }

    private function restaurantId(): int
    {
        $restaurantId = Auth::user()?->restaurant_id;

        if (! $restaurantId || ! DB::table('restaurants')->where('id', $restaurantId)->exists()) {
            abort(403, 'No restaurant is assigned to this staff account.');
        }

        return (int) $restaurantId;
    }
}
