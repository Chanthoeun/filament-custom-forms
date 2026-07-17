<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomFormEntries\Schemas;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Chanthoeun\FilamentCustomForms\Models\CustomForm;
use Chanthoeun\FilamentCustomForms\Models\CustomFormEntry;
use Chanthoeun\FilamentCustomForms\Models\CustomFormField;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step as WizardStep;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Unique;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class CustomFormEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        $livewire = $schema->getLivewire();

        $preselectedFormId = property_exists($livewire, 'form_id') && $livewire->form_id
            ? $livewire->form_id
            : (request()->query('form_id') ?? request()->input('tableFilters.custom_form_id.value'));

        if (! $preselectedFormId && property_exists($livewire, 'record') && $livewire->record) {
            $preselectedFormId = $livewire->record->custom_form_id;
        }

        return $schema
            ->components([
                Select::make('custom_form_id')
                    ->label(__('filament-custom-forms::fcf.form.single'))
                    ->options(fn () => CustomForm::where('is_active', true)->whereNotNull('name')->pluck('name', 'id'))
                    ->required()
                    ->default($preselectedFormId)
                    ->hidden(fn () => ! empty($preselectedFormId))
                    ->live()
                    ->columnSpanFull(),
                Grid::make()
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema(function (Get $get, ?Model $record) use ($preselectedFormId, $livewire) {
                        $formId = $get('custom_form_id') ?? $record?->custom_form_id;

                        // Fallback to pre-selected ID if not set in state (e.g. initial load)
                        if (! $formId && $preselectedFormId) {
                            $formId = $preselectedFormId;
                        }

                        if (! $formId) {
                            return [];
                        }

                        $customForm = CustomForm::find($formId);

                        if (! $customForm) {
                            return [];
                        }

                        // Eager load all fields and build the relationship tree in memory
                        // to prevent massive N+1 queries during recursive form building.
                        $allFields = $customForm->fields()->orderBy('sort')->get();
                        $fieldsByParent = $allFields->groupBy('parent_id');

                        foreach ($allFields as $field) {
                            $field->setRelation('children', $fieldsByParent->get($field->id, collect()));
                        }

                        $rootFields = $fieldsByParent->get('', collect());

                        $locale = property_exists($livewire, 'activeLocale') && $livewire->activeLocale ? $livewire->activeLocale : app()->getLocale();

                        return self::getFields($rootFields, $locale, $formId);
                    })
                    ->columns(2),
            ]);
    }

    protected static function getFields($fields, ?string $locale = null, $formId = null): array
    {
        $components = [];

        foreach ($fields as $fieldModel) {
            $type = $fieldModel->type;

            $label = $fieldModel->label;
            $options = $fieldModel->options ?? [];
            if ($locale && method_exists($fieldModel, 'getTranslation')) {
                $translatedLabel = $fieldModel->getTranslation('label', $locale, false) ?: $fieldModel->getTranslation('label', config('app.fallback_locale', 'en'), false);
                if ($translatedLabel) {
                    $label = $translatedLabel;
                }
            }

            // Handle Hidden Label
            $isHiddenLabel = $options['is_hidden_label'] ?? false;

            $component = null;

            // Handle Layouts
            if ($type === 'section') {
                $component = Section::make($isHiddenLabel ? null : $label) // Use label as heading
                    ->schema(self::getFields($fieldModel->children, $locale, $formId))
                    ->columns($options['columns'] ?? 2);
            } elseif ($type === 'grid') {
                $component = Grid::make($options['columns'] ?? 2)
                    ->schema(self::getFields($fieldModel->children, $locale, $formId));
            } elseif ($type === 'fieldset') {
                $component = Fieldset::make($isHiddenLabel ? null : $label)
                    ->schema(self::getFields($fieldModel->children, $locale, $formId))
                    ->columns($options['columns'] ?? 2);
            } elseif ($type === 'wizard') {
                // Convert children into wizard steps
                // If children are sections, each section becomes a step
                // If children are fields, group them all into a single step
                $steps = [];

                // Check if children are sections/containers or actual fields
                $hasContainers = $fieldModel->children->contains(function ($child) {
                    return in_array($child->type, ['section', 'fieldset', 'grid']);
                });

                if ($hasContainers) {
                    // Children are sections/containers - each becomes a step
                    /** @var CustomFormField $child */
                    foreach ($fieldModel->children as $child) {
                        $childLabel = $child->label;
                        if ($locale && method_exists($child, 'getTranslation')) {
                            $translated = $child->getTranslation('label', $locale, false) ?: $child->getTranslation('label', config('app.fallback_locale', 'en'), false);
                            if ($translated) {
                                $childLabel = $translated;
                            }
                        }

                        $stepFields = self::getFields($child->children, $locale, $formId);

                        $step = WizardStep::make($childLabel)
                            ->schema($stepFields);

                        if (! empty($child->options['columns'])) {
                            $step->columns($child->options['columns']);
                        }

                        $steps[] = $step;
                    }
                } else {
                    // Children are fields - put them all in a single step
                    $stepFields = self::getFields($fieldModel->children, $locale, $formId);
                    $step = WizardStep::make($label)
                        ->schema($stepFields);

                    // Apply columns from wizard options
                    $wizardOpts = $fieldModel->options ?? [];
                    if (! empty($wizardOpts['columns'])) {
                        $step->columns($wizardOpts['columns']);
                    }

                    $steps[] = $step;
                }

                $component = Wizard::make()
                    ->schema($steps);
            } elseif ($type === 'repeater') {
                $component = Repeater::make("data.{$fieldModel->name}")
                    ->label($label);

                if (! empty($options['is_table'])) {
                    // Table Layout: Headers + Hidden Label Fields
                    $headers = [];
                    /** @var CustomFormField $child */
                    foreach ($fieldModel->children as $child) {
                        $childLabel = $child->label;
                        if ($locale && method_exists($child, 'getTranslation')) {
                            $childLabel = $child->getTranslation('label', $locale, false) ?: $child->getTranslation('label', config('app.fallback_locale', 'en'), false) ?: $child->label;
                        }
                        $headers[] = TableColumn::make($childLabel ?? $child->name);
                    }

                    $component->table($headers);

                    // Fields must hide labels in table mode
                    $fields = self::getFields($fieldModel->children, $locale, $formId);
                    foreach ($fields as $field) {
                        $field->hiddenLabel();
                    }
                    $component->schema($fields);
                } else {
                    $component->schema(self::getFields($fieldModel->children, $locale, $formId))
                        ->columns($options['columns'] ?? 1);
                }

                if (! empty($options['is_compact'])) {
                    $component->compact();
                }

                if ($fieldModel->required) {
                    $component->required();
                }
            } else {
                // Handle Fields
                $name = $fieldModel->name;
                $required = $fieldModel->required;

                switch ($type) {
                    case 'text':
                    case 'text_input':
                        $component = TextInput::make("data.{$name}");
                        break;
                    case 'textarea':
                        $component = Textarea::make("data.{$name}");
                        break;
                    case 'number':
                    case 'number_input':
                        $isDecimal = $options['is_decimal'] ?? true;
                        $component = TextInput::make("data.{$name}")
                            ->numeric()
                            ->inputMode($isDecimal ? 'decimal' : 'numeric');
                        break;
                    case 'money':
                        $currency = $options['currency'] ?? 'usd';
                        // Handle Enum backed value which is lowercase 'usd'/'khr'
                        $symbol = match ($currency) {
                            'khr' => '៛',
                            'usd' => '$',
                            default => '$',
                        };
                        $component = TextInput::make("data.{$name}")
                            ->numeric()
                            ->prefix($symbol)
                            ->inputMode('decimal');
                        break;
                    case 'date_picker':
                        $component = DatePicker::make("data.{$name}");
                        break;
                    case 'time_picker':
                        $component = TimePicker::make("data.{$name}")
                            ->seconds(false);
                        break;

                    case 'email':
                        $component = TextInput::make("data.{$name}")->email();
                        break;
                    case 'phone':
                        // Use PhoneInput if available, falling back to TextInput
                        if (class_exists(PhoneInput::class)) {
                            $component = PhoneInput::make("data.{$name}");
                        } else {
                            $component = TextInput::make("data.{$name}")->tel();
                        }
                        break;
                    case 'password':
                        $component = TextInput::make("data.{$name}")
                            ->password()
                            ->dehydrateStateUsing(function ($state, ?Model $record) use ($name) {
                                if (filled($state)) {
                                    return Hash::make($state);
                                }

                                return $record ? data_get($record->data, $name) : null;
                            })
                            ->revealable();
                        break;
                    case 'confirm_password':
                        $component = TextInput::make("data.{$name}")
                            ->password()
                            ->revealable()
                            ->dehydrated(false);

                        $matchField = $options['match_field'] ?? null;
                        if ($matchField) {
                            $component->same("data.{$matchField}");
                        }
                        break;
                    case 'boolean':
                    case 'checkbox':
                        $component = Toggle::make("data.{$name}");
                        if ($type === 'checkbox') {
                            $component = Checkbox::make("data.{$name}");
                        }
                        if ($options['default'] ?? false) {
                            $component->default(true);
                        }
                        break;
                    case 'image':
                        $component = FileUpload::make("data.{$name}")
                            ->image() // Enforce image types
                            ->disk(CustomFormPlugin::get()->getUploadDisk())
                            ->directory(CustomFormPlugin::get()->getUploadDirectory())
                            ->visibility(CustomFormPlugin::get()->getUploadVisibility());
                        break;
                    case 'radio':
                        $component = Radio::make("data.{$name}")->options($fieldModel->getParsedOptions());
                        if (! empty($options['is_inline'])) {
                            $component->inline();
                        }
                        break;
                    case 'checkbox_list':
                        $component = CheckboxList::make("data.{$name}")->options($fieldModel->getParsedOptions());
                        if (! empty($options['is_inline'])) {
                            $component->inline();
                        }
                        break;
                    case 'select':
                        $component = Select::make("data.{$name}")->options($fieldModel->getParsedOptions());
                        break;
                }

                if ($component) {
                    $component->label($label);

                    if (isset($options['default_source']) && method_exists($component, 'default')) {
                        if ($options['default_source'] === 'auth_user' && ! empty($options['auth_user_attribute'])) {
                            $component->default(function () use ($options) {
                                return auth()->check() ? auth()->user()->{$options['auth_user_attribute']} : null;
                            });
                        } elseif ($options['default_source'] === 'manual' && array_key_exists('default_value', $options)) {
                            $component->default($options['default_value']);
                        }
                    }

                    if ($required) {
                        $component->required();
                    }

                    if ($isHiddenLabel) {
                        $component->hiddenLabel();
                    }

                    if (($options['is_hidden_on_view'] ?? false)) {
                        $component->hiddenOn('view');
                    }

                    // Safe Option Application
                    if (! empty($options['is_revealable']) && method_exists($component, 'revealable')) {
                        $component->revealable();
                    }

                    if (! empty($options['image_editor']) && method_exists($component, 'imageEditor')) {
                        $component->imageEditor();
                    }

                    if (! empty($options['is_copyable']) && method_exists($component, 'copyable')) {
                        $component->copyable();
                    }

                    if (! empty($options['is_unique']) && method_exists($component, 'unique')) {
                        $component->unique(
                            table: CustomFormEntry::class,
                            column: "data->{$name}",
                            ignoreRecord: true,
                            modifyRuleUsing: function (Unique $rule) use ($formId) {
                                return $rule->where('custom_form_id', $formId);
                            }
                        );
                    }
                }
            }

            if ($component) {
                // Common Layout Options (Applied to BOTH Fields and Layouts)

                // Column Span
                if ($options['column_span_full'] ?? false) {
                    $component->columnSpanFull();
                } elseif (! empty($options['column_span'])) {
                    $component->columnSpan($options['column_span']);
                }

                $components[] = $component;
            }
        }

        return $components;
    }
}
