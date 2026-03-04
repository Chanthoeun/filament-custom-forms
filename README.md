# Filament Custom Forms

A powerful and simplified FilamentPHP plugin to manage and submit dynamic custom forms. Refactored for speed and ease of use in standalone environments.

## Features

- Dynamic form builder with custom fields.
- Submission management with a clean interface.
- Support for Filament v4 and v5.
- Easy integration as a standalone package.

## Installation

### 1. Requirements

- PHP 8.2+
- Filament v4.0 or v5.0

### 2. Standard Installation

You can install the package via composer:

```bash
composer require chanthoeun/filament-custom-forms
```

### 3. Register the Plugin

Add the plugin to your Filament Panel provider:

```php
use Chanthoeun\FilamentCustomForms\CustomFormPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(
            CustomFormPlugin::make()
                ->navigationGroup('System Settings')
                ->navigationFormIcon('heroicon-o-document-duplicate')
                ->navigationEntryIcon('heroicon-o-clipboard-document-list')
                // ->formModel(MyCustomForm::class)
                // ->entryModel(MyCustomFormEntry::class)
                // ->userModel(App\Models\User::class)
                // ->uploadDisk('s3')
                // ->uploadDirectory('forms')
        );
}
```

By default, the plugin provides a fluent API to customize the models, uploads, and navigation icons directly from your panel provider.

### 4. Run Migrations

```bash
php artisan migrate
```

### 6. Configuration (Optional)

Alternatively, you can publish the configuration file to customize the plugin globally instead of per-panel:

```bash
php artisan vendor:publish --tag=filament-custom-forms-config
```

### 7. Publishing Resources

You can publish the translations, views, and migrations:

```bash
# Publish Translations
php artisan vendor:publish --tag=filament-custom-forms-translations

# Publish Views
php artisan vendor:publish --tag=filament-custom-forms-views

# Publish Migrations
php artisan vendor:publish --tag=filament-custom-forms-migrations
```

## Usage

1.  **Form Creation**: Navigate to the **Custom Forms** resource to create dynamic forms using the builder.
2.  **Data Collection**: Users can submit entries through the generated forms.
3.  **Entry Management**: View and export entries in the **Custom Form Entries** resource.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
