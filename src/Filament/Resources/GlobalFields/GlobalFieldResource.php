<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields;

use App\Models\User;
use BackedEnum;
use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages\CreateGlobalField;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages\EditGlobalField;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages\ListGlobalFields;
use Chanthoeun\FilamentCustomForms\Models\CustomFormField;
use Chanthoeun\FilamentCustomForms\Models\GlobalField;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class GlobalFieldResource extends Resource
{
    use Translatable;

    protected static ?string $model = GlobalField::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    public static function getNavigationGroup(): ?string
    {
        return CustomFormPlugin::get()->getNavigationGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('name')
                            ->label(__('filament-custom-forms::fcf.field.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, $state) => $set('name', Str::slug($state, '_')))
                            ->unique(ignoreRecord: true)
                            ->helperText(__('filament-custom-forms::fcf.builder.fields.name_help')),
                        TextInput::make('label')
                            ->label(__('filament-custom-forms::fcf.field.label')),
                        Select::make('type')
                            ->label(__('filament-custom-forms::fcf.field.type'))
                            ->required()
                            ->options([
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
                            ])
                            ->default('text_input')
                            ->live()
                            ->afterStateUpdated(function ($get, $set, $state) {
                                if (in_array($state, ['select', 'radio', 'checkbox_list']) && ! $get('options.source')) {
                                    $set('options.source', 'manual');
                                }
                                if (! in_array($state, ['section', 'grid', 'fieldset', 'repeater', 'wizard', 'image', 'file_upload']) && ! $get('options.default_source')) {
                                    $set('options.default_source', 'manual');
                                }
                            }),
                        Toggle::make('required')
                            ->label(__('filament-custom-forms::fcf.field.is_required'))
                            ->default(false)
                            ->hidden(fn ($get) => in_array($get('type'), ['section', 'grid', 'fieldset', 'wizard'])),

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
                                            ->visible(fn ($get) => ! in_array($get('type'), ['section', 'grid', 'fieldset', 'repeater', 'wizard', 'image', 'file_upload'])),

                                        TextInput::make('options.default_value')
                                            ->label('Default Value')
                                            ->helperText('Enter a static default value for this field.')
                                            ->visible(fn ($get) => $get('options.default_source') === 'manual' && ! in_array($get('type'), ['section', 'grid', 'fieldset', 'repeater', 'wizard', 'image', 'file_upload'])),

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

                                        KeyValue::make('options.choices')
                                            ->label(__('filament-custom-forms::fcf.admin.select_options'))
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && (! $get('options.source') || $get('options.source') === 'manual'))
                                            ->helperText('Key corresponds to value, Label is displayed text.'),

                                        Select::make('options.model')
                                            ->label('Model')
                                            ->options(CustomFormPlugin::getAvailableModels())
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'model')
                                            ->required(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'model')
                                            ->live(),

                                        TextInput::make('options.model_label_attribute')
                                            ->label('Label Attribute')
                                            ->default('name')
                                            ->datalist(fn ($get) => array_values(CustomFormPlugin::getModelAttributes($get('options.model'))))
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'model')
                                            ->required(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'model'),

                                        TextInput::make('options.model_value_attribute')
                                            ->label('Value Attribute')
                                            ->default('id')
                                            ->datalist(fn ($get) => array_values(CustomFormPlugin::getModelAttributes($get('options.model'))))
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'model')
                                            ->required(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'model'),

                                        Select::make('options.enum')
                                            ->label('Enum Class')
                                            ->options(config('filament-custom-forms.field_options.enums', []))
                                            ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'enum')
                                            ->required(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']) && $get('options.source') === 'enum'),
                                    ]),

                                Tab::make('Layout & Display')
                                    ->icon('heroicon-m-paint-brush')
                                    ->schema([
                                        Select::make('options.columns')
                                            ->label(__('filament-custom-forms::fcf.admin.columns'))
                                            ->visible(fn ($get) => in_array($get('type'), ['grid', 'section', 'fieldset', 'repeater', 'wizard', 'checkbox_list']))
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
                                            ->formatStateUsing(fn ($state) => is_array($state) ? $state : (empty($state) ? [] : ['default' => $state])),

                                        Toggle::make('options.column_span_full')
                                            ->label(__('filament-custom-forms::fcf.admin.full_width'))
                                            ->default(false),

                                        Toggle::make('options.is_hidden_label')
                                            ->label('Hide Label')
                                            ->default(false),

                                        Toggle::make('options.is_hidden_on_view')
                                            ->label(__('filament-custom-forms::fcf.admin.hide_in_view'))
                                            ->default(false),

                                        Toggle::make('options.is_inline')
                                            ->label('Display Inline')
                                            ->visible(fn ($get) => in_array($get('type'), ['radio', 'checkbox_list']))
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('label')
                    ->formatStateUsing(fn ($state) => is_array($state) && isset($state['en']) ? $state['en'] : (is_array($state) ? current($state) : $state))
                    ->label('Label (EN)'),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn ($record) => CustomFormField::where('global_field_id', $record->id)->exists()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGlobalFields::route('/'),
            'create' => CreateGlobalField::route('/create'),
            'edit' => EditGlobalField::route('/{record}/edit'),
        ];
    }
}
