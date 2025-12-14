<?php

namespace App\Vito\Plugins\NodeDogConsulting\VitoLaravelNightwatch;

use App\Plugins\AbstractPlugin;
use App\Plugins\RegisterSiteFeature;
use App\Plugins\RegisterSiteFeatureAction;
use App\DTOs\DynamicForm;
use App\DTOs\DynamicField;
use App\Vito\Plugins\NodeDogConsulting\VitoLaravelNightwatch\Actions\Enable;
use App\Vito\Plugins\NodeDogConsulting\VitoLaravelNightwatch\Actions\Disable;

class Plugin extends AbstractPlugin
{
    protected string $name = 'Laravel Nightwatch';
    protected string $description = 'Enable Laravel Nightwatch monitoring for Laravel sites';

    public function boot(): void
    {
        RegisterSiteFeature::make('laravel', 'laravel-nightwatch')
            ->label('Laravel Nightwatch')
            ->description('Monitor this Laravel site with Laravel Nightwatch')
            ->register();

        RegisterSiteFeatureAction::make('laravel', 'laravel-nightwatch', 'enable')
            ->label('Enable')
            ->form(
                DynamicForm::make([
                    DynamicField::make('nightwatch_port')
                        ->text()
                        ->label('Nightwatch Port')
                        ->default(2407),

                    DynamicField::make('nightwatch_token')
                        ->text()
                        ->label('Nightwatch Token'),
                ])
            )
            ->handler(Enable::class)
            ->register();

        RegisterSiteFeatureAction::make('laravel', 'laravel-nightwatch', 'disable')
            ->label('Disable')
            ->handler(Disable::class)
            ->register();
    }
}
