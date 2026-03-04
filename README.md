# Filament Custom Forms

A powerful FilamentPHP plugin to manage and submit dynamic custom forms with approval workflows.

## Features

- Dynamic form builder with custom fields.
- Submission management with approval workflow (Reviewer/Approver).
- Multi-tenancy support (works with standard Laravel/Filament setups).
- Clean and modern UI for both form building and data entry.

## Installation

### 1. Requirements

- PHP 8.2+
- Filament 3.x, 4.x, or 5.x

### 2. Standard Installation (via Packagist)

Once the package is published on Packagist, you can install it via composer:

```bash
composer require laraspace/filament-custom-forms
```

### 3. Local Installation (for development)

If you have the package locally, add the local repository to your project's `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "../packages/filament-custom-forms"
    }
],
"require": {
    "laraspace/filament-custom-forms": "*"
}
```

### 4. Register the Plugin

Add the plugin to your Filament Panel provider:

```php
use LaraSpace\FilamentCustomForms\CustomFormPlugin;

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

## Usage

1.  Navigate to the **Custom Forms** resource in your Filament dashboard.
2.  Create a new form and define your fields (Text, Select, etc.).
3.  Users can then submit entries via the **Custom Form Entries** resource.
4.  If enabled, submissions will follow your configured approval workflow.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
