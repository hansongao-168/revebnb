<?php

namespace App\Filament\Resources\Landlords\Pages;

use App\Filament\Resources\Landlords\LandlordResource;
use App\Mail\LandlordPortalAccessMail;
use App\Models\Landlord;
use App\Services\LandlordTokenService;
use App\Support\Auditor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateLandlord extends CreateRecord
{
    protected static string $resource = LandlordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['password'] = Str::password(length: 40, symbols: false);

        return $data;
    }

    protected function afterCreate(): void
    {
        parent::afterCreate();

        /** @var Landlord $landlord */
        $landlord = $this->getRecord();
        $tokens = app(LandlordTokenService::class);
        $issued = $tokens->issueNewToken($landlord);
        $url = $tokens->portalMagicUrl($issued['plain']);
        $expires = $issued['token']->expires_at->timezone(config('app.timezone'))->format('Y-m-d H:i');

        Mail::queue(new LandlordPortalAccessMail($landlord, $url, $expires));
        Auditor::recordFromGuard('web', 'landlord.created', $landlord, []);
    }
}
