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

### 2. Standard Installation (via GitHub)

Since this package is hosted on GitHub, you need to add the repository to your `composer.json` first:

```bash
composer config repositories.filament-custom-forms vcs https://github.com/Chanthoeun/filament-custom-forms.git
```

Then, you can require the package directly:

```bash
composer require dcx/filament-custom-forms:dev-main
```

### 3. Register the Plugin

Add the plugin to your Filament Panel provider:

```php
use Dcx\FilamentCustomForms\CustomFormPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(CustomFormPlugin::make());
}
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 6. Configuration

You can customize models, uploads, and navigation by publishing the config file:

```bash
php artisan vendor:publish --tag=filament-custom-forms-config
```

The config allows you to override:
- `models.form`: Custom form model.
- `models.entry`: Custom form entry model.
- `models.user`: The user model used for `created_by`.
- `uploads`: Storage disk and directory.
- `navigation`: Icons and groups.

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
