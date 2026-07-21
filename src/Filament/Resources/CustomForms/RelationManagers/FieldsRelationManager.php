<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\RelationManagers;

use App\Models\User;
use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Chanthoeun\FilamentCustomForms\Models\CustomForm;
use Chanthoeun\FilamentCustomForms\Models\GlobalField;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable;

class FieldsRelationManager extends RelationManager
{
    use Translatable;

    protected static string $relationship = 'fields';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        Select::make('parent_id')
                            ->label(__('filament-custom-forms::fcf.admin.parent_container'))
                            ->options(function ($livewire) {
                                return $livewire->getOwnerRecord()->fields()
                                    ->whereIn('type', ['section', 'grid', 'fieldset', 'repeater', 'wizard'])
                                    ->get()
                                    ->mapWithKeys(fn ($field) => [$field->id => $field->label ?? $field->name]);
                            })
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        TextInput::make('name')
                            ->label(__('filament-custom-forms::fcf.field.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, $state) => $set('name', Str::slug($state, '_')))
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, $livewire) => $rule->where('custom_form_id', $livewire->getOwnerRecord()->id)
                            )
                            ->helperText(__('filament-custom-forms::fcf.builder.fields.name_help')),
                        TextInput::make('label')
                            ->label(__('filament-custom-forms::fcf.field.label')),
                        Select::make('type')
                            ->label(__('filament-custom-forms::fcf.field.type'))
                            ->required()
                            ->options([
                                __('filament-custom-forms::fcf.builder.blocks.section') => [
                                    'section' => __('filament-custom-forms::fcf.builder.blocks.section'),
                                    'grid' => __('filament-custom-forms::fcf.builder.blocks.grid'),
                                    'fieldset' => __('filament-custom-forms::fcf.builder.blocks.fieldset'),
                                    'repeater' => __('filament-custom-forms::fcf.builder.blocks.repeater'),
                                    'wizard' => __('filament-custom-forms::fcf.builder.blocks.wizard'),
                                    'nested_form' => 'Nested Form',
                                ],
                                __('filament-custom-forms::fcf.builder.fields.repeater_fields') => [
                                    'text_input' => __('filament-custom-forms::fcf.builder.blocks.text_input'),
                                    'textarea' => __('filament-custom-forms::fcf.builder.blocks.textarea'),
                                    'email' => __('filament-custom-forms::fcf.builder.blocks.email'),
                                    'number_input' => __('filament-custom-forms::fcf.builder.blocks.number_input'),
                                    'money' => __('filament-custom-forms::fcf.builder.blocks.money'),
                                    'date_picker' => __('filament-custom-forms::fcf.builder.blocks.date_picker'),
                                    'time_picker' => __('filament-custom-forms::fcf.builder.blocks.time_picker'),
                                    'boolean' => __('filament-custom-forms::fcf.builder.blocks.boolean'),
                                    'checkbox' => 'Checkbox (Single)',
                                    'checkbox_list' => 'Checkbox List',
                                    'radio' => 'Radio',
                                    'select' => __('filament-custom-forms::fcf.builder.blocks.select'),
                                    'image' => __('filament-custom-forms::fcf.builder.blocks.image'),
                                    'password' => __('filament-custom-forms::fcf.builder.blocks.password'),
                                    'confirm_password' => 'Confirm Password',
                                    'phone' => __('filament-custom-forms::fcf.builder.blocks.phone'),
                                ],
                            ])
                            ->default('text_input')
                            ->live()
                            ->afterStateUpdated(function ($get, $set, $state) {
                                if (in_array($state, ['select', 'radio', 'checkbox_list']) && ! $get('options.source')) {
                                    $set('options.source', 'manual');
                                }
                                if (! in_array($state, ['section', 'grid', 'fieldset', 'repeater', 'wizard', 'nested_form', 'image', 'file_upload']) && ! $get('options.default_source')) {
                                    $set('options.default_source', 'manual');
                                }
                            }),
                        Toggle::make('required')
                            ->label(__('filament-custom-forms::fcf.field.is_required'))
                            ->default(false)
                            ->hidden(fn ($get) => in_array($get('type'), ['section', 'grid', 'fieldset', 'wizard', 'nested_form'])),

                        Tabs::make('Configuration')
                            ->columnSpanFull()
                            ->tabs([
                                Tab::make('Default Values')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        Select::make('options.default_source')
                                            ->label('Default Value Source')
                                            ->options([
                                                'manual' => 'Manual Input',
                                                'auth_user' => 'Get from Authenticated User',
                                            ])
                                            ->default('manual')
                                            ->live()
                                            ->visible(fn ($get) => ! in_array($get('type'), ['section', 'grid', 'fieldset', 'repeater', 'wizard', 'nested_form', 'image', 'file_upload'])),

                                        TextInput::make('options.default_value')
                                            ->label('Default Value')
                                            ->helperText('Enter a static default value for this field.')
                                            ->visible(fn ($get) => $get('options.default_source') === 'manual' && ! in_array($get('type'), ['section', 'grid', 'fieldset', 'repeater', 'wizard', 'nested_form', 'image', 'file_upload'])),

                                        Select::make('options.auth_user_attribute')
                                            ->label('Auth User Attribute')
                                            ->options(function () {
                                                $model = config('auth.providers.users.model', User::class);
                                                $attributes = CustomFormPlugin::getModelAttributes($model);

                                                return array_combine($attributes, $attributes);
                                            })
                                            ->searchable()
                                            ->helperText('Select the user attribute to use as the default value.')
                                            ->visible(fn ($get) => $get('options.default_source') === 'auth_user')
                                            ->required(fn ($get) => $get('options.default_source') === 'auth_user'),
                                    ]),

                                Tab::make('Options & Choices')
                                    ->icon('heroicon-m-list-bullet')
                                    ->schema([
                                        Select::make('options.source')
                                            ->label('Options Source')
                                            ->options([
                                                'manual' => 'Manual Input',
                                                'model' => 'Link to Model',
                                                'enum' => 'Link to Enum',
                                            ])
                                            ->default('manual')
                                            ->live()
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list'])),

                                        Hidden::make('options.choices_translations')
                                            ->default(fn ($record) => $record ? data_get($record->options, 'choices', []) : [])
                                            ->dehydrated(false),

                                        KeyValue::make('options.choices')
                                            ->label(__('filament-custom-forms::fcf.admin.select_options'))
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && (! $get('options.source') || $get('options.source') === 'manual'))
                                            ->helperText('Key corresponds to value, Label is displayed text.')
                                            ->formatStateUsing(function ($state, $livewire, $record, $get) {
                                                $locale = property_exists($livewire, 'activeLocale') ? $livewire->activeLocale : app()->getLocale();
                                                $fullState = $get('options.choices_translations') ?? ($record ? data_get($record->options, 'choices', []) : []);

                                                if (empty($fullState) || ! is_array($fullState)) {
                                                    return [];
                                                }

                                                $firstElement = reset($fullState);
                                                if (! is_array($firstElement)) {
                                                    $fallback = config('app.fallback_locale', 'en');
                                                    $fullState = [$fallback => $fullState];
                                                }

                                                return $fullState[$locale] ?? $fullState[config('app.fallback_locale', 'en')] ?? [];
                                            })
                                            ->dehydrateStateUsing(function ($state, $record, $livewire, $get, $set) {
                                                $locale = property_exists($livewire, 'activeLocale') ? $livewire->activeLocale : app()->getLocale();
                                                $fallback = config('app.fallback_locale', 'en');

                                                $existingChoices = $get('options.choices_translations') ?? [];

                                                if (empty($existingChoices) && $record) {
                                                    $existingChoices = data_get($record->options, 'choices', []);
                                                }

                                                if (! empty($existingChoices)) {
                                                    $firstElement = reset($existingChoices);
                                                    if (! is_array($firstElement)) {
                                                        $existingChoices = [$fallback => $existingChoices];
                                                    }
                                                }

                                                if (! is_array($existingChoices)) {
                                                    $existingChoices = [];
                                                }

                                                $existingChoices[$locale] = $state ?? [];

                                                $set('options.choices_translations', $existingChoices);

                                                return $existingChoices;
                                            }),

                                        Fieldset::make('Model Configuration')
                                            ->schema([
                                                Select::make('options.model')
                                                    ->label('Model')
                                                    ->options(CustomFormPlugin::getAvailableModels())
                                                    ->required()
                                                    ->live()
                                                    ->columnSpanFull(),

                                                TextInput::make('options.model_label_attribute')
                                                    ->label('Label Attribute')
                                                    ->helperText('The column to display in the dropdown (e.g., name, title).')
                                                    ->default('name')
                                                    ->datalist(fn ($get) => array_values(CustomFormPlugin::getModelAttributes($get('options.model'))))
                                                    ->required(),

                                                TextInput::make('options.model_value_attribute')
                                                    ->label('Value Attribute')
                                                    ->helperText('The column to save to the database (e.g., id, uuid).')
                                                    ->default('id')
                                                    ->datalist(fn ($get) => array_values(CustomFormPlugin::getModelAttributes($get('options.model'))))
                                                    ->required(),

                                                TextInput::make('options.parent_field')
                                                    ->label('Parent Field Name')
                                                    ->helperText('The internal name (slug) of the parent field this depends on. Only applicable for Select fields.')
                                                    ->visible(fn ($get) => $get('type') === 'select'),

                                                TextInput::make('options.parent_foreign_key')
                                                    ->label('Parent Foreign Key')
                                                    ->helperText('The column in this model that links it to the parent model.')
                                                    ->visible(fn ($get) => $get('type') === 'select' && filled($get('options.parent_field')))
                                                    ->required(fn ($get) => $get('type') === 'select' && filled($get('options.parent_field'))),
                                            ])
                                            ->columns(2)
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'model'),

                                        Select::make('options.enum')
                                            ->label('Enum Class')
                                            ->options(config('filament-custom-forms.field_options.enums', []))
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'enum')
                                            ->required(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'enum'),

                                        Select::make('options.linked_form_id')
                                            ->label('Select Custom Form')
                                            ->options(fn () => CustomForm::where('is_active', true)->pluck('name', 'id'))
                                            ->visible(fn ($get) => $get('type') === 'nested_form')
                                            ->required(fn ($get) => $get('type') === 'nested_form')
                                            ->helperText('Select the form whose fields should be embedded here.'),
                                    ]),

                                Tab::make('Layout & Display')
                                    ->icon('heroicon-m-paint-brush')
                                    ->schema([
                                        Select::make('options.columns')
                                            ->label(__('filament-custom-forms::fcf.admin.columns'))
                                            ->visible(fn ($get) => in_array($get('type'), ['grid', 'section', 'fieldset', 'repeater', 'wizard', 'nested_form', 'checkbox_list']))
                                            ->options([
                                                '1' => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 1),
                                                '2' => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 2),
                                                '3' => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 3),
                                                '4' => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 4),
                                            ])
                                            ->default('2'),

                                        KeyValue::make('options.column_span')
                                            ->label('Column Span (Responsive)')
                                            ->helperText('Key: Breakpoint (default, sm, md, lg, xl, 2xl). Value: Columns (1-12, full).')
                                            ->keyLabel('Breakpoint')
                                            ->valueLabel('Columns')
                                            ->formatStateUsing(fn ($state) => is_array($state) ? $state : (empty($state) ? [] : ['default' => $state]))
                                            ->visible(fn ($get) => ! in_array($get('type'), ['wizard', 'nested_form']) && ! ($get('options.column_span_full') ?? false)),

                                        Toggle::make('options.column_span_full')
                                            ->label(__('filament-custom-forms::fcf.admin.full_width'))
                                            ->default(false)
                                            ->visible(fn ($get) => ! in_array($get('type'), ['wizard', 'nested_form'])),

                                        Toggle::make('options.is_hidden_label')
                                            ->label('Hide Label')
                                            ->default(false),

                                        TextInput::make('options.visible_when_field')
                                            ->label('Visible When (Field Name)')
                                            ->helperText('Internal name (slug) of the field to watch for conditional visibility.'),

                                        TextInput::make('options.visible_when_value')
                                            ->label('Visible When (Value)')
                                            ->helperText('Value the watched field must match to become visible.'),

                                        Toggle::make('options.is_hidden_on_view')
                                            ->label(__('filament-custom-forms::fcf.admin.hide_in_view'))
                                            ->default(false),

                                        Toggle::make('options.is_inline')
                                            ->label('Display Inline')
                                            ->visible(fn ($get) => in_array($get('type'), ['radio', 'checkbox_list']))
                                            ->default(false),

                                        Toggle::make('options.is_multiple')
                                            ->label('Allow Multiple Selections')
                                            ->visible(fn ($get) => $get('type') === 'select')
                                            ->default(false),

                                        Toggle::make('options.is_table')
                                            ->label('Use Table Layout (Simple)')
                                            ->visible(fn ($get) => $get('type') === 'repeater')
                                            ->default(false),

                                        Toggle::make('options.is_compact')
                                            ->label('Compact Mode')
                                            ->visible(fn ($get) => $get('type') === 'repeater')
                                            ->default(false),
                                    ]),

                                Tab::make('Field Specifics')
                                    ->icon('heroicon-m-cog-6-tooth')
                                    ->schema([
                                        Toggle::make('options.image_editor')
                                            ->label(__('filament-custom-forms::fcf.admin.enable_image_editor'))
                                            ->visible(fn ($get) => $get('type') === 'image'),

                                        Toggle::make('options.is_revealable')
                                            ->label(__('filament-custom-forms::fcf.admin.allow_password_reveal'))
                                            ->visible(fn ($get) => $get('type') === 'password'),

                                        TextInput::make('options.match_field')
                                            ->label('Match Field (Name)')
                                            ->visible(fn ($get) => $get('type') === 'confirm_password')
                                            ->helperText('Enter the name of the password field this should match.'),

                                        Toggle::make('options.is_copyable')
                                            ->label(__('filament-custom-forms::fcf.admin.allow_copy'))
                                            ->visible(fn ($get) => in_array($get('type'), ['text_input', 'email', 'number_input', 'phone'])),

                                        Toggle::make('options.is_unique')
                                            ->label('Must be unique')
                                            ->helperText('Ensure the submitted value is unique across this form.')
                                            ->visible(fn ($get) => ! in_array($get('type'), ['section', 'grid', 'fieldset', 'repeater', 'wizard', 'image', 'file_upload', 'boolean'])),

                                        Toggle::make('options.is_decimal')
                                            ->label('Allow Decimals')
                                            ->visible(fn ($get) => in_array($get('type'), ['number_input', 'number']))
                                            ->default(true),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label(__('filament-custom-forms::fcf.field.name'))->searchable(),
                TextColumn::make('label')->label(__('filament-custom-forms::fcf.field.label'))->searchable(),
                ToggleColumn::make('required')->label(__('filament-custom-forms::fcf.field.is_required')),
                TextColumn::make('type')->label(__('filament-custom-forms::fcf.field.type'))->badge()->color(fn (string $state): string => match ($state) {
                    'section', 'grid', 'fieldset', 'wizard' => 'info',
                    default => 'gray',
                }),
                TextColumn::make('parent.label')->label(__('filament-custom-forms::fcf.admin.parent_container'))->badge(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc')
            ->headerActions(array_filter([
                CustomFormPlugin::get()->hasTranslations() ? LocaleSwitcher::make() : null,
                CreateAction::make(),
                Action::make('import_global_field')
                    ->label(__('Import Global Field'))
                    ->icon('heroicon-o-document-duplicate')
                    ->form([
                        Select::make('parent_id')
                            ->label(__('filament-custom-forms::fcf.admin.parent_container'))
                            ->options(function ($livewire) {
                                return $livewire->getOwnerRecord()->fields()
                                    ->whereIn('type', ['section', 'grid', 'fieldset', 'repeater', 'wizard'])
                                    ->get()
                                    ->mapWithKeys(fn ($field) => [$field->id => $field->label ?? $field->name]);
                            }),
                        Select::make('global_field_id')
                            ->label(__('Select Global Field'))
                            ->options(GlobalField::all()->pluck('label', 'id'))
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $globalField = GlobalField::find($data['global_field_id']);
                        if ($globalField) {
                            $livewire->getOwnerRecord()->fields()->create([
                                'parent_id' => $data['parent_id'] ?? null,
                                'global_field_id' => $globalField->id,
                                'name' => $globalField->name,
                                'label' => $globalField->getTranslations('label'),
                                'type' => $globalField->type,
                                'required' => $globalField->required ?? false,
                                'options' => $globalField->options,
                            ]);
                        }
                    }),
            ]))
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
