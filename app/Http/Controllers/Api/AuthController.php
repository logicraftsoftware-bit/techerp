<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TechnicianResource;
use App\Models\Technician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $technician = Technician::where('mobile', $data['mobile'])->first();

        if (! $technician || ! Hash::check($data['password'], $technician->password)) {
            throw ValidationException::withMessages(['mobile' => 'Invalid mobile number or password.']);
        }

        abort_if($technician->status !== 'active', 403, 'Your account is inactive. Contact your admin.');

        $technician->loginLogs()->create([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
        $technician->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);

        $token = $technician->createToken($data['device_name'] ?? 'mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'technician' => new TechnicianResource($technician),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => new TechnicianResource($request->user())]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $technician = $request->user();
        abort_unless(Hash::check($data['current_password'], $technician->password), 422, 'Current password is incorrect.');

        $technician->update(['password' => Hash::make($data['new_password'])]);
        $technician->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password updated.']);
    }
}
