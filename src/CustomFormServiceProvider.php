<?php

namespace Chanthoeun\FilamentCustomForms;

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
            ]);
    }

    public function packageBooted(): void
    {
        \Chanthoeun\FilamentCustomForms\Models\CustomForm::observe(\Chanthoeun\FilamentCustomForms\Observers\CustomFormObserver::class);

        if ($this->app->runningInConsole()) {
            // Also publish the document builder migration when publishing custom forms migrations
            if (class_exists(\Chanthoeun\FilamentDocumentBuilder\FilamentDocumentBuilderServiceProvider::class)) {
                $reflector = new \ReflectionClass(\Chanthoeun\FilamentDocumentBuilder\FilamentDocumentBuilderServiceProvider::class);
                $docBuilderPath = dirname($reflector->getFileName(), 2);
                
                $this->publishes([
                    $docBuilderPath . '/database/migrations/create_document_templates_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_create_document_templates_table.php'),
                ], 'filament-custom-forms-migrations');
            }
        }
    }
}
