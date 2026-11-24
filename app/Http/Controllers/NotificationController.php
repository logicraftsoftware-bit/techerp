<?php

namespace App\Http\Controllers;

use App\Models\CustomerAmcTagging;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $alerts = $this->expiryAlerts($request)?->with(['customer', 'machine', 'amcPlan'])
            ->orderBy('end_date')->orderBy('id')->paginate(25);

        return view('notifications.index', compact('alerts'));
    }

    public function summary(Request $request): JsonResponse
    {
        $alerts = $this->expiryAlerts($request)?->toBase()->pluck('end_date', 'id') ?? collect();

        return response()->json([
            'alerts' => $alerts->map(fn ($date, $id) => "{$id}:{$date}")->values(),
        ])->header('Cache-Control', 'private, no-store');
    }

    private function expiryAlerts(Request $request): ?Builder
    {
        if (! $request->user()->hasRole('super-admin', 'admin') || ! Schema::hasTable('customer_amc_taggings')) {
            return null;
        }

        $today = today();

        return CustomerAmcTagging::query()->whereBetween('end_date', [$today, $today->copy()->addMonthNoOverflow()]);
    }
}
