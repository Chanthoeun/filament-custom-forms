<?php

namespace Chanthoeun\FilamentCustomForms;

use Chanthoeun\FilamentCustomForms\Commands\MigrateToTranslatableCommand;
use Chanthoeun\FilamentCustomForms\Models\CustomForm;
use Chanthoeun\FilamentCustomForms\Observers\CustomFormObserver;
use Chanthoeun\FilamentDocumentBuilder\FilamentDocumentBuilderServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CustomFormServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-custom-forms')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                'create_custom_forms_table',
                'create_custom_form_fields_table',
                'create_custom_form_entries_table',
                'add_panel_access_to_custom_forms_table',
            ])
            ->hasCommand(MigrateToTranslatableCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('chanthoeun/filament-custom-forms');
            });
    }

    public function packageBooted(): void
    {
        CustomForm::observe(CustomFormObserver::class);

        if ($this->app->runningInConsole()) {
            // Also publish the document builder migration when publishing custom forms migrations
            if (class_exists(FilamentDocumentBuilderServiceProvider::class)) {
                $reflector = new \ReflectionClass(FilamentDocumentBuilderServiceProvider::class);
                $docBuilderPath = dirname($reflector->getFileName(), 2);

                $this->publishes([
                    $docBuilderPath.'/database/migrations/create_document_templates_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_document_templates_table.php'),
                ], 'filament-custom-forms-migrations');
            }
        }
    }
}
