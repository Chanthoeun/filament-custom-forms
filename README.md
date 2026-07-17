# Filament Custom Forms

A powerful and simplified FilamentPHP plugin to manage and submit dynamic custom forms. Refactored for speed and ease of use in standalone environments.

## Features

- Dynamic form builder with extensive custom fields (Text, Select, Checkbox, Radio, Date, Time, File Upload, etc.).
- Secure password fields with automatic hashing and confirm password validation.
- Submission management with a clean interface.
- Support for Filament v4 and v5.
- Easy integration as a standalone package.

## Installation

### 1. Requirements

- PHP 8.2+
- Filament v4.0 or v5.0

### 2. Install via Composer

```bash
composer require chanthoeun/filament-custom-forms
```
### 3. Publish Assets

```bash
php artisan vendor:publish --tag="filament-custom-forms-config"
php artisan vendor:publish --tag="filament-custom-forms-migrations"
```

### 4. Run Migrations

```bash
php artisan migrate
```

### 5. Register the Plugin

Add the plugin to your Filament Panel provider:

```php
use Chanthoeun\FilamentCustomForms\CustomFormPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(
            CustomFormPlugin::make()
                ->navigationGroup('Form Builder')
                ->navigationFormIcon('heroicon-o-document-duplicate')
                ->navigationEntryIcon('heroicon-o-clipboard-document-list')
                ->panelAccess(true)  // Optional: Enable granular panel access controls
                ->translations(true) // Optional: Enable spatie/laravel-translatable fields
        );
}
```

**Note on Translations**: If you enable `->translations(true)`, ensure you have defined your desired locales in the `config/filament-custom-forms.php` under the `locales` key.

## Updates

To update the package to the latest version, run:

```bash
composer update chanthoeun/filament-custom-forms
```

If the update includes new migrations or changes to published assets, you may need to re-publish or run:

```bash
php artisan migrate
```

## Versioning

This project follows [Semantic Versioning](https://semver.org/). We use Git tags to manage releases.

To release a new version:
1.  Update `CHANGELOG.md`.
2.  Commit your changes.
3.  Tag the release: `git tag v1.0.1`.
4.  Push the tag: `git push origin v1.0.1`.

## Usage

1.  **Global Fields**: Under **Form Builder > Global Fields**, you can create reusable fields (e.g., standard 'Gender' or 'Country' selects) that can be imported into multiple custom forms with a single click.
2.  **Form Creation**: Navigate to the **Custom Forms** resource to create dynamic forms using the builder. You can define new fields or import existing Global Fields instantly.
3.  **Data Collection**: Users can submit entries through the generated forms.
4.  **Entry Management**: View and export entries in the **Custom Form Entries** resource.
5.  **Data Exporting**: Export the data grid to JSON, heavily formatted Excel files, or instantly as beautifully formatted PDF tables.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Recent Fixes & Improvements

- **Global Fields**: Prevented deletion of global fields when they are in use by form entries to maintain data integrity.
- **Translatable Labels**: Dropdowns and table columns now automatically display beautifully translated `label` texts instead of internal `name` slugs (e.g., Parent Container dropdowns, Global Fields Selects).
- **Table Formatting**: The Form Entries table now dynamically parses and maps JSON key values back to their human-readable labels for Select, Radio, and Checkbox List components.
- **Data Tables**: Prevented non-data layout elements (like `wizard`, `section`, `grid`) from incorrectly rendering as columns in the Entries data tables.
- **Navigation active states**: Enhanced the sidebar active state evaluation on the Edit/Create pages to accurately highlight the correct dynamic Form Entry resource without mistakenly highlighting incorrect parent forms due to route parameter bleeding.
- **Form UI Display**: The Form entry edit page now automatically hides the "Select Custom Form" dropdown when a form ID is already established for the entry.


