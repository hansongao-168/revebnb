<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本信息')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('邮箱')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('手机号')
                            ->tel()
                            ->maxLength(20),
                        DateTimePicker::make('email_verified_at')
                            ->label('邮箱验证时间'),
                        TextInput::make('wechat_openid')
                            ->label('微信 OpenID')
                            ->maxLength(64)
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make('安全')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->label('密码')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->same('passwordConfirmation'),
                        TextInput::make('passwordConfirmation')
                            ->label('确认密码')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->dehydrated(false),
                    ]),
                Section::make('个人资料')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('avatar')
                            ->label('头像')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public'),
                        Select::make('gender')
                            ->label('性别')
                            ->options([
                                0 => '未知',
                                1 => '男',
                                2 => '女',
                            ])
                            ->native(false),
                    ]),
                Section::make('管理')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_admin')
                            ->label('管理员')
                            ->inline(false),
                        Select::make('status')
                            ->label('状态')
                            ->options([
                                1 => '正常',
                                0 => '禁用',
                            ])
                            ->default(1)
                            ->native(false),
                    ]),
            ]);
    }
}
