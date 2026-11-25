<?php

namespace App\Http\Controllers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StaffTableQrController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-restaurant');

        $restaurantId = $this->restaurantId();

        $restaurant = DB::table('restaurants')
            ->where('id', $restaurantId)
            ->first();

        if (! $restaurant) {
            abort(403, 'No restaurant is assigned to this staff account.');
        }

        $tables = DB::table('restaurant_tables')
            ->where('restaurant_id', $restaurantId)
            ->orderBy('table_number')
            ->select('id', 'table_number', 'qr_code', 'is_active')
            ->get()
            ->map(function (object $table) use ($restaurant): object {
                $table->menu_url = $table->qr_code
                    ? $this->menuUrl($table->qr_code)
                    : null;
                $table->manual_code = $restaurant->slug.':'.$table->table_number;

                return $table;
            });

        return view('staff.qr', compact('restaurant', 'tables'));
    }

    public function image(int $table): Response
    {
        Gate::authorize('manage-restaurant');

        $tableRow = DB::table('restaurant_tables')
            ->where('id', $table)
            ->where('restaurant_id', $this->restaurantId())
            ->first();

        if (! $tableRow || ! $tableRow->qr_code) {
            abort(404, 'Table QR code not found.');
        }

        $result = (new Builder(
            writer: new SvgWriter,
            writerOptions: [
                SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true,
                SvgWriter::WRITER_OPTION_EXCLUDE_SVG_WIDTH_AND_HEIGHT => true,
            ],
            data: $this->menuUrl($tableRow->qr_code),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 512,
            margin: 16,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(6, 42, 32),
            backgroundColor: new Color(255, 255, 255),
        ))->build();

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType())
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function menuUrl(string $qrCode): string
    {
        return rtrim((string) config('app.url'), '/').route('customer.menu', $qrCode, false);
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
