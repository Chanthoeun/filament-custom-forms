<?php

namespace Chanthoeun\FilamentCustomForms\Commands;

use Chanthoeun\FilamentCustomForms\Models\CustomForm;
use Chanthoeun\FilamentCustomForms\Models\CustomFormEntry;
use Chanthoeun\FilamentCustomForms\Models\CustomFormField;
use Illuminate\Console\Command;

class MigrateToTranslatableCommand extends Command
{
    protected $signature = 'filament-custom-forms:migrate-translatable {--locale=en : The default locale to migrate existing data into}';

    protected $description = 'Migrate existing single-language custom forms data to the new translatable JSON format.';

    public function handle()
    {
        $locale = $this->option('locale');

        $this->info("Migrating CustomForms to translatable format (Locale: {$locale})...");

        CustomForm::withoutEvents(function () use ($locale) {
            foreach (CustomForm::all() as $form) {
                $form->setTranslation('name', $locale, $form->getRawOriginal('name'));

                $schema = $form->getRawOriginal('schema');
                if (is_string($schema)) {
                    $schema = json_decode($schema, true);
                }
                $form->setTranslation('schema', $locale, $schema);
                $form->save();
            }
        });

        $this->info('Migrating CustomFormFields to translatable format...');

        CustomFormField::withoutEvents(function () use ($locale) {
            foreach (CustomFormField::all() as $field) {
                $field->setTranslation('label', $locale, $field->getRawOriginal('label'));

                $options = $field->getRawOriginal('options');
                if (is_string($options)) {
                    $options = json_decode($options, true);
                }
                $field->setTranslation('options', $locale, $options);
                $field->save();
            }
        });

        $this->info('Migrating CustomFormEntries to translatable format...');

        CustomFormEntry::withoutEvents(function () use ($locale) {
            foreach (CustomFormEntry::all() as $entry) {
                $data = $entry->getRawOriginal('data');
                if (is_string($data)) {
                    $data = json_decode($data, true);
                }
                $entry->setTranslation('data', $locale, $data);
                $entry->save();
            }
        });

        $this->info('Migration to translatable format completed successfully!');
    }
}
