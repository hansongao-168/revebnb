<?php

namespace Tests\Feature;

use App\Mail\SaasPanelLoginTokenIssuedMail;
use App\Models\SaasPanelLoginToken;
use App\Models\SaasUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RotateExpiredSaasPanelLoginTokensTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_supersedes_expired_and_queues_mail(): void
    {
        Mail::fake();

        $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
        $user = SaasUser::factory()->for($tenant)->create(['status' => 1]);
        SaasPanelLoginToken::query()->create([
            'saas_user_id' => $user->id,
            'token_hash' => hash('sha256', 'old-token-'.str_repeat('o', 40)),
            'expires_at' => now()->subHour(),
            'created_reason' => SaasPanelLoginToken::REASON_MANUAL,
        ]);

        Artisan::call('panel-tokens:rotate-expired');

        $this->assertSame(
            1,
            SaasPanelLoginToken::query()
                ->where('saas_user_id', $user->id)
                ->whereNotNull('superseded_at')
                ->count()
        );

        $this->assertSame(
            1,
            SaasPanelLoginToken::query()
                ->where('saas_user_id', $user->id)
                ->where('created_reason', SaasPanelLoginToken::REASON_EXPIRY_ROTATION)
                ->count()
        );

        Mail::assertQueued(SaasPanelLoginTokenIssuedMail::class);
    }
}
