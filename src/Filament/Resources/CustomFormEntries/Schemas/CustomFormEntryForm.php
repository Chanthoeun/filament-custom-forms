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
use Filament\Schemas\Components\Group;
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

                        $parentFields = [];
                        foreach ($allFields as $field) {
                            if (! empty($field->options['parent_field'])) {
                                $parentFields[] = $field->options['parent_field'];
                            }
                            if (! empty($field->options['visible_when_field'])) {
                                $parentFields[] = $field->options['visible_when_field'];
                            }
                        }

                        $locale = property_exists($livewire, 'activeLocale') && $livewire->activeLocale ? $livewire->activeLocale : app()->getLocale();

                        $footerComponents = [];

                        if (! empty($customForm->linked_forms)) {
                            $linkedForms = CustomForm::whereIn('id', $customForm->linked_forms)->where('is_active', true)->get();

                            if ($linkedForms->isNotEmpty()) {
                                if ($linkedForms->count() > 1) {
                                    $options = $linkedForms->pluck('name', 'id')->toArray();

                                    if ($customForm->allow_multiple_linked_forms !== false) {
                                        $selectField = CheckboxList::make('data.linked_forms_selection')
                                            ->label('Select Additional Forms')
                                            ->options($options)
                                            ->live();
                                    } else {
                                        $selectField = Radio::make('data.linked_forms_selection')
                                            ->label('Select Additional Forms')
                                            ->options($options)
                                            ->live();
                                    }

                                    $footerComponents[] = $selectField;
                                }
                            }
                        }

                        $isWizard = (bool) $customForm->is_wizard;

                        $components = self::getFields($rootFields, $locale, $formId, $parentFields, $isWizard, $footerComponents);

                        if ($isWizard) {
                            $steps = [];
                            $looseFields = [];
                            foreach ($components as $component) {
                                if ($component instanceof WizardStep) {
                                    if (! empty($looseFields)) {
                                        $steps[] = WizardStep::make('Step '.(count($steps) + 1))->schema($looseFields);
                                        $looseFields = [];
                                    }
                                    $steps[] = $component;
                                } else {
                                    $looseFields[] = $component;
                                }
                            }
                            if (! empty($looseFields)) {
                                $steps[] = WizardStep::make('Step '.(count($steps) + 1))->schema($looseFields);
                            }
                            $components = $steps;
                        }

                        if (! empty($customForm->linked_forms)) {
                            if (isset($linkedForms) && $linkedForms->isNotEmpty()) {
                                foreach ($linkedForms as $linkedForm) {
                                    $linkedFields = $linkedForm->fields()->orderBy('sort')->get();
                                    $linkedFieldsByParent = $linkedFields->groupBy('parent_id');
                                    foreach ($linkedFields as $linkedField) {
                                        $linkedField->setRelation('children', $linkedFieldsByParent->get($linkedField->id, collect()));
                                    }
                                    $linkedRootFields = $linkedFieldsByParent->get('', collect());

                                    $group = Group::make()
                                        ->schema(self::getFields($linkedRootFields, $locale, $linkedForm->id, []))
                                        ->statePath("data.linked_form_{$linkedForm->slug}");

                                    $visibilityCondition = function (Get $get) use ($linkedForm, $customForm, $linkedForms) {
                                        if ($linkedForms->count() === 1) {
                                            return true;
                                        }

                                        $selected = $get('data.linked_forms_selection');

                                        if ($customForm->allow_multiple_linked_forms !== false) {
                                            if (is_array($selected)) {
                                                return in_array($linkedForm->id, $selected);
                                            }
                                        }

                                        return (string) $selected === (string) $linkedForm->id;
                                    };

                                    $components[] = $isWizard
                                        ? WizardStep::make($linkedForm->name)->schema([$group])->visible($visibilityCondition)
                                        : Section::make($linkedForm->name)->schema([$group])->visible($visibilityCondition);
                                }
                            }
                        }

                        if ($isWizard) {
                            $components = [Wizard::make($components)->columnSpanFull()];
                        }

                        return $components;
                    })
                    ->columns(2),
            ]);
    }

    protected static function getFields($fields, ?string $locale = null, $formId = null, array $parentFields = [], bool $isRootWizard = false, array $footerComponents = []): array
    {
        $components = [];
        $count = count($fields);
        $i = 0;

        foreach ($fields as $fieldModel) {
            $i++;
            $isLast = ($i === $count);
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
                $schema = self::getFields($fieldModel->children, $locale, $formId, $parentFields);
                if ($isLast && empty($parentFields) && ! empty($footerComponents)) {
                    $schema = array_merge($schema, $footerComponents);
                }

                if ($isRootWizard) {
                    $component = WizardStep::make($isHiddenLabel ? null : $label) // Use label as heading
                        ->schema($schema);
                } else {
                    $component = Section::make($isHiddenLabel ? null : $label) // Use label as heading
                        ->schema($schema)
                        ->columns($options['columns'] ?? 2);
                }
            } elseif ($type === 'grid') {
                $component = Grid::make($options['columns'] ?? 2)
                    ->schema(self::getFields($fieldModel->children, $locale, $formId, $parentFields));
            } elseif ($type === 'fieldset') {
                $component = Fieldset::make($isHiddenLabel ? null : $label)
                    ->schema(self::getFields($fieldModel->children, $locale, $formId, $parentFields))
                    ->columns($options['columns'] ?? 2);
            } elseif ($type === 'nested_form') {
                $linkedFormId = $options['linked_form_id'] ?? null;
                if ($linkedFormId) {
                    $linkedForm = CustomForm::find($linkedFormId);
                    if ($linkedForm) {
                        $linkedFields = $linkedForm->fields()->orderBy('sort')->get();
                        $linkedFieldsByParent = $linkedFields->groupBy('parent_id');
                        foreach ($linkedFields as $linkedField) {
                            $linkedField->setRelation('children', $linkedFieldsByParent->get($linkedField->id, collect()));
                        }
                        $linkedRootFields = $linkedFieldsByParent->get('', collect());

                        // Using a transparent Group to isolate data keys using the nested form's name as a prefix
                        $component = Group::make()
                            ->schema(self::getFields($linkedRootFields, $locale, $linkedFormId, $parentFields))
                            ->statePath($fieldModel->name);
                    }
                }
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

                        $stepFields = self::getFields($child->children, $locale, $formId, $parentFields);

                        $step = WizardStep::make($childLabel)
                            ->schema($stepFields);

                        if (! empty($child->options['columns'])) {
                            $step->columns($child->options['columns']);
                        }

                        $steps[] = $step;
                    }
                } else {
                    // Children are fields - put them all in a single step
                    $stepFields = self::getFields($fieldModel->children, $locale, $formId, $parentFields);
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
                    $fields = self::getFields($fieldModel->children, $locale, $formId, $parentFields);
                    foreach ($fields as $field) {
                        $field->hiddenLabel();
                    }
                    $component->schema($fields);
                } else {
                    $component->schema(self::getFields($fieldModel->children, $locale, $formId, $parentFields))
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
                        $component = Radio::make("data.{$name}")->options($fieldModel->getParsedOptions($locale));
                        if (! empty($options['is_inline'])) {
                            $component->inline();
                        }
                        break;
                    case 'checkbox_list':
                        $component = CheckboxList::make("data.{$name}")->options($fieldModel->getParsedOptions($locale));
                        if (! empty($options['is_inline'])) {
                            $component->inline();
                        }
                        break;
                    case 'select':
                        if (
                            ! empty($options['source']) && $options['source'] === 'model'
                            && ! empty($options['parent_field'])
                            && ! empty($options['parent_foreign_key'])
                        ) {
                            $component = Select::make("data.{$name}")
                                ->options(function (Get $get) use ($options) {
                                    $parentValue = $get("data.{$options['parent_field']}");

                                    if (blank($parentValue)) {
                                        return [];
                                    }

                                    $modelClass = $options['model'] ?? null;
                                    if (! $modelClass || ! class_exists($modelClass)) {
                                        return [];
                                    }

                                    $labelAttr = $options['model_label_attribute'] ?? 'name';
                                    $valueAttr = $options['model_value_attribute'] ?? 'id';

                                    return app($modelClass)
                                        ->where($options['parent_foreign_key'], $parentValue)
                                        ->pluck($labelAttr, $valueAttr);
                                });
                        } else {
                            $component = Select::make("data.{$name}")->options($fieldModel->getParsedOptions($locale));
                        }

                        if (! empty($options['is_multiple'])) {
                            $component->multiple();
                        }
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
                if (in_array($fieldModel->name, $parentFields) && method_exists($component, 'live')) {
                    $component->live();
                }

                // Common Layout Options (Applied to BOTH Fields and Layouts)

                // Column Span
                if ($options['column_span_full'] ?? false) {
                    $component->columnSpanFull();
                } elseif (! empty($options['column_span'])) {
                    $component->columnSpan($options['column_span']);
                }

                // Conditional Visibility
                if (! empty($options['visible_when_field'])) {
                    $watchField = $options['visible_when_field'];
                    $watchValue = $options['visible_when_value'] ?? null;

                    if (method_exists($component, 'visible')) {
                        $component->visible(function (Get $get) use ($watchField, $watchValue) {
                            $currentValue = $get("data.{$watchField}");

                            // Handle array values (e.g. from multiple selects or checkbox lists)
                            if (is_array($currentValue)) {
                                return in_array($watchValue, $currentValue);
                            }

                            return (string) $currentValue === (string) $watchValue;
                        });
                    }
                }

                // If it's not a section (where we already appended), and it's the last root item, append footers
                if ($isLast && empty($parentFields) && ! empty($footerComponents) && $type !== 'section') {
                    $components[] = $component;
                    $components = array_merge($components, $footerComponents);
                } else {
                    $components[] = $component;
                }
            }
        }

        // If there were no fields at all at the root level, but we have footer components
        if ($count === 0 && empty($parentFields) && ! empty($footerComponents)) {
            $components = array_merge($components, $footerComponents);
        }

        return $components;
    }
}
