# Filament Custom Forms

A powerful and simplified FilamentPHP plugin to manage and submit dynamic custom forms. Refactored for speed and ease of use in standalone environments.

## Features

- Dynamic form builder with extensive custom fields (Text, Select, Checkbox, Radio, Date, Time, File Upload, etc.).
- Secure password fields with automatic hashing and confirm password validation.
- Submission management with a clean interface.
- Support for Filament v3 (and forwards compatible).
- Easy integration as a standalone package.

## Requirements

- PHP 8.2+
- Filament v3.0+

## Installation

### 1. Install via Composer

```bash
composer require chanthoeun/filament-custom-forms
```

### 2. Publish Assets

```bash
php artisan vendor:publish --tag="filament-custom-forms-config"
php artisan vendor:publish --tag="filament-custom-forms-migrations"
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Register the Plugin

Add the plugin to your Filament Panel provider (`app/Providers/Filament/AdminPanelProvider.php`):

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

**Note on Translations**: If you enable `->translations(true)`, ensure you have defined your desired locales in `config/filament-custom-forms.php` under the `locales` key.

## Step-by-Step Guide

Here is a detailed guide on how to build and use your first custom form from start to finish.

### Step 1: Create a Global Field (Optional but Recommended)
Global fields are reusable fields you can use across many different forms (e.g., "Gender" or "Country").
1. In your Filament admin panel, navigate to **Form Builder > Global Fields**.
2. Click **New Global Field**.
3. Name it "Gender", select the Type `Select` or `Radio`.
4. Go to the **Options & Choices** tab, and enter the choices manually (e.g. `male` => `Male`, `female` => `Female`).
5. Save the field.

### Step 2: Create a Custom Form
1. Navigate to **Form Builder > Custom Forms**.
2. Click **New Custom Form**.
3. Give it a name (e.g., "User Registration Survey") and toggle it to **Active**.
4. Save to continue to the field builder.

### Step 3: Add Fields to Your Form
Once the form is created, you will see a section at the bottom to add fields.
1. Click **Create Field** to make a brand new field.
    - Set the **Type** to `Text Input` and name it `first_name`.
    - Check the **Is Required** toggle.
    - Save it.
2. Click **Import Global Field** to use a pre-made field.
    - Select the "Gender" global field we made in Step 1. It will instantly attach itself to your form.
3. You can reorder fields by dragging them up and down.

### Step 4: Grouping with Layouts (Sections, Grids, Wizards)
Want to organize your form?
1. Click **Create Field** and set the type to **Section**. Name it "Personal Details".
2. Under the **Layout & Display** tab, set the Columns to `2`. Save it.
3. Edit your `first_name` and `gender` fields, and set their **Parent Container** to the "Personal Details" section.
4. *(Optional Wizard)*: If you create multiple Sections and toggle the **Render as Wizard** option on your main form settings, those sections will automatically become beautifully interactive Wizard Steps!

### Step 5: Test and Submit Entries
1. Navigate to **Form Entry** (usually automatically generated based on your form's name, e.g., "User Registration Survey Entries").
2. Click **New Entry**.
3. You will see the dynamic form you just built! Fill it out and click Save.
4. Back on the list view, you can easily view, search, export to PDF/Excel, and manage all the submissions.

---

## Form Configuration Options

When building a form, the plugin provides a powerful set of configurations for every field and layout block.

#### Default Values
- **Manual Input**: Set a static default value that the field will start with.
- **Get from Authenticated User**: Automatically pre-fill the field with data from the currently logged-in user (e.g., automatically populate their `email` or `name`).

#### Options & Choices (For Selects, Radios, Checkboxes)
- **Manual Input**: Define key-value pairs manually.
- **Link to Model**: Dynamically pull options from a Database Model. You can select the Label Attribute (what the user sees) and the Value Attribute (what is saved).
    - **Dependent Dropdowns**: You can chain select fields together by specifying a **Parent Field Name** and a **Parent Foreign Key** so the choices filter automatically based on a previous selection!
- **Link to Enum**: Pull options directly from a PHP Enum class.
- **Linked Forms** (For Nested Form types): You can link other Custom Forms directly into your form. The plugin dynamically injects them into the current form flow. 

#### Layout & Display
- **Columns**: Define how many grid columns a layout block (like a Section or Grid) should have.
- **Column Span**: Control how wide a specific field should be within its parent grid. You can set responsive breakpoints (e.g., `sm: 12`, `md: 6`).
- **Full Width**: Instantly force the field to take up the entire row.
- **Conditional Visibility**: Dynamically show or hide fields based on other fields! 
    - *Visible When (Field Name)*: Enter the slug of the field you want to watch.
    - *Visible When (Value)*: The exact value that field must equal for this field to appear.
- **Hide Label**: Visually hide the field's label.
- **Hide in View**: Hide the field when an admin is viewing the submitted Entry, but keep it visible during the form creation/editing process.
- **Display Inline**: (For Radios/Checkboxes) Display the selectable options horizontally instead of stacking them vertically.
- **Use Table Layout / Compact Mode**: (For Repeaters) Condense large repeater blocks into a clean table layout.
- **Auto-Wizard**: By toggling **Render as Wizard** on the main Custom Form settings, the plugin automatically transforms your top-level layout sections (and any Linked Forms) into beautiful, seamless Wizard Steps.

#### Field Specifics
- **Must be unique**: Ensures the submitted value does not already exist in the database for this form.
- **Match Field**: (For Confirm Password fields) Enter the slug of the original password field to enforce identical matching.
- **Allow Password Reveal**: Adds a toggle to let users see the password they typed.
- **Allow Copy**: Adds a quick-copy button to text inputs.
- **Allow Decimals**: (For Number inputs) Restrict or allow floating point numbers.
- **Enable Image Editor**: (For Image fields) Allows users to crop and edit images directly before uploading.

## Advanced Features

### Multi-Language Support & Translations

This plugin natively supports `spatie/laravel-translatable` for robust multi-language forms:

1. **Form Labels & Names**: The Form Name and all Field Labels are fully translatable. When a user creates a form, they can provide translated names and labels which will automatically render correctly based on the active locale.
2. **Submissions (Entries)**: Submitted data is saved securely and can be isolated by language.
3. **Translating Manual Choices**: Form Configuration and Layout Options (such as Columns, Required, Unique Rules, and Manual Choices) are intentionally shared across languages to prevent admins from having to re-configure the entire layout for every language.
    * If you use "Manual Input" for Select, Radio, or Checkbox options and want them translated, simply **enter your Laravel Translation Key** (e.g., `messages.gender_male`) into the choice label field. The plugin automatically detects translation keys and applies Laravel's `__()` helper on the frontend. (If no translation file exists, it elegantly falls back to the exact text you typed).
    * Alternatively, use the **Model** or **Enum** Option Sources, which handle translations natively.

### Dynamic Models & Enums

By default, to prevent exposing sensitive internal application classes to the form builder, Custom Forms requires you to explicitly opt-in which Models and Enums are allowed to be used as options for Select, Radio, and Checkbox fields.

To make a Model or Enum appear in the Custom Form Builder, simply implement the `IsFormOption` interface! 

#### Using Enums

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

#### Using Models

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

## Maintenance

### Updates

To update the package to the latest version, run:

```bash
composer update chanthoeun/filament-custom-forms
```

If the update includes new migrations or changes to published assets, you may need to re-publish or run:

```bash
php artisan migrate
```

### Versioning

This project follows [Semantic Versioning](https://semver.org/). We use Git tags to manage releases.

To release a new version:
1. Update `CHANGELOG.md`.
2. Commit your changes.
3. Tag the release: `git tag v1.0.1`.
4. Push the tag: `git push origin v1.0.1`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
