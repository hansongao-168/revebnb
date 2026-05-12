<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Services\WeChatMiniProgramAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_admin' => false,
            'status' => 1,
            'gender' => 0,
        ]);

        $token = $user->createToken('uniapp')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => new UserProfileResource($user),
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ((int) $user->status !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Account is disabled.',
            ], 403);
        }

        $token = $user->createToken('uniapp')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => new UserProfileResource($user),
            ],
        ]);
    }

    public function wechatLogin(Request $request, WeChatMiniProgramAuth $weChat): JsonResponse
    {
        $validated = $request->validate([
            'loginCode' => ['required', 'string', 'max:512'],
            'appid' => ['sometimes', 'string', 'max:64'],
            'telephoneCode' => ['sometimes', 'string', 'max:512'],
        ]);

        try {
            $wx = $weChat->exchangeCode($validated['loginCode']);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $user = User::query()->firstOrCreate(
            ['wechat_openid' => $wx['openid']],
            [
                'name' => '微信用户',
                'email' => 'wx_'.$wx['openid'].'@wechat.local',
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'is_admin' => false,
                'status' => 1,
                'gender' => 0,
            ],
        );

        if ((int) $user->status !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Account is disabled.',
            ], 403);
        }

        $token = $user->createToken('uniapp')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => new UserProfileResource($user),
            ],
        ]);
    }
}
