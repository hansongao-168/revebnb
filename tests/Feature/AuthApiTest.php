<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_and_login_return_token_and_profile(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $register->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'test@example.com');
    }

    public function test_login_rejects_disabled_user(): void
    {
        User::factory()->create([
            'email' => 'disabled@example.com',
            'password' => Hash::make('secret1234'),
            'status' => 0,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'disabled@example.com',
            'password' => 'secret1234',
        ])->assertForbidden();
    }

    public function test_wechat_login_uses_mock_openid_when_not_configured_in_debug(): void
    {
        config(['services.wechat_mini.app_id' => null, 'services.wechat_mini.secret' => null]);

        $this->postJson('/api/auth/wechat-login', [
            'loginCode' => 'test-code-abc',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_authenticated_profile_and_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'password' => Hash::make('secret1234'),
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user/profile')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)
            ->putJson('/api/user/profile', [
                'name' => 'Updated',
                'phone' => '13800138000',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated')
            ->assertJsonPath('data.phone', '13800138000');

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $this->withToken($token)
            ->post('/api/user/avatar', ['avatar' => $file])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($user->fresh()->avatar);
    }

    public function test_wechat_login_calls_remote_when_configured(): void
    {
        config([
            'services.wechat_mini.app_id' => 'wx-test',
            'services.wechat_mini.secret' => 'secret',
        ]);

        Http::fake([
            'api.weixin.qq.com/*' => Http::response([
                'openid' => 'remote-openid-1',
                'session_key' => 'sk',
            ], 200),
        ]);

        $this->postJson('/api/auth/wechat-login', [
            'loginCode' => 'wx-code',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.id', User::query()->where('wechat_openid', 'remote-openid-1')->value('id'));
    }
}
