<?php

namespace Chanthoeun\FilamentCustomForms\Tests;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TestServiceProvider extends ServiceProvider
{
    public function register()
    {
        Filament::registerPanel(
            fn (): Panel => Panel::make()
                ->default()
                ->id('admin')
                ->path('admin')
                ->colors([
                    'primary' => Color::Amber,
                ])
                ->widgets([])
                ->middleware([
                    EncryptCookies::class,
                    AddQueuedCookiesToResponse::class,
                    StartSession::class,
                    AuthenticateSession::class,
                    ShareErrorsFromSession::class,
                    VerifyCsrfToken::class,
                    SubstituteBindings::class,
                ])
                ->plugin(CustomFormPlugin::make()),
        );
    }
}
