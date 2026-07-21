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

## Multi-Language Support & Translations

This plugin natively supports `spatie/laravel-translatable` for robust multi-language forms:

1. **Form Labels & Names**: The Form Name and all Field Labels are fully translatable. When a user creates a form, they can provide translated names and labels which will automatically render correctly based on the active locale.
2. **Submissions (Entries)**: Submitted data is saved securely and can be isolated by language.
3. **Translating Manual Choices**: Form Configuration and Layout Options (such as Columns, Required, Unique Rules, and Manual Choices) are intentionally shared across languages to prevent admins from having to re-configure the entire layout for every language.
    * If you use "Manual Input" for Select, Radio, or Checkbox options and want them translated, simply **enter your Laravel Translation Key** (e.g., `messages.gender_male`) into the choice label field. The plugin automatically detects translation keys and applies Laravel's `__()` helper on the frontend. (If no translation file exists, it elegantly falls back to the exact text you typed).
    * Alternatively, use the **Model** or **Enum** Option Sources, which handle translations natively.

## Dynamic Models & Enums

By default, to prevent exposing sensitive internal application classes to the form builder, Custom Forms requires you to explicitly opt-in which Models and Enums are allowed to be used as options for Select, Radio, and Checkbox fields.

To make a Model or Enum appear in the Custom Form Builder, simply implement the `IsFormOption` interface! 

### Using Enums

```php
namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Chanthoeun\FilamentCustomForms\Contracts\IsFormOption;

enum Gender: string implements HasLabel, IsFormOption
{
    case Male = 'male';
    case Female = 'female';
    
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Male => __('Male'),
            self::Female => __('Female'),
        };
    }
}
```

### Using Models

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Chanthoeun\FilamentCustomForms\Contracts\IsFormOption;

class Province extends Model implements IsFormOption
{
    protected $fillable = ['name'];
}
```

*Note: If you are using Translations and want your Enum labels to automatically adapt to the form's active language, the `IsFormOption` trait automatically handles the Laravel Application Locale swapping for you behind the scenes during resolution!*

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
3.  **Linked Forms**: You can link other Custom Forms directly into your form by selecting them in the **Linked Forms** configuration. The plugin dynamically injects them into the current form flow. If they are linked, they are automatically hidden from the main sidebar navigation to keep your menu clean!
4.  **Auto-Wizard**: By toggling **Render as Wizard**, the plugin automatically transforms your top-level layout sections (and any Linked Forms) into beautiful, seamless Wizard Steps without requiring you to manually wrap your form in a Wizard layout block.
5.  **Data Collection**: Users can submit entries through the generated forms.
6.  **Entry Management**: View and export entries in the **Custom Form Entries** resource.
7.  **Data Exporting**: Export the data grid to JSON, heavily formatted Excel files, or instantly as beautifully formatted PDF tables.

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


