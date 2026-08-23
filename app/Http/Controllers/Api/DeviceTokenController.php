<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['required', Rule::in(['android', 'ios'])],
        ]);

        // Looked up by token alone (its unique column) rather than scoped to the current
        // technician, so a reinstalled/reassigned device correctly moves to the new owner
        // instead of hitting the unique constraint on an orphaned insert attempt.
        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            ['technician_id' => $request->user()->id, 'platform' => $data['platform']]
        );

        return response()->json(['message' => 'Device registered.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string']]);
        $request->user()->deviceTokens()->where('token', $data['token'])->delete();

        return response()->json(['message' => 'Device removed.']);
    }
}
