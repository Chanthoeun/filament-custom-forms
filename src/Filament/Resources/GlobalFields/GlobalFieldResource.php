<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields;

use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages\CreateGlobalField;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages\EditGlobalField;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Pages\ListGlobalFields;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Schemas\GlobalFieldForm;
use Chanthoeun\FilamentCustomForms\Filament\Resources\GlobalFields\Tables\GlobalFieldsTable;
use Chanthoeun\FilamentCustomForms\Models\GlobalField;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class GlobalFieldResource extends Resource
{
    use Translatable;

    protected static ?string $model = GlobalField::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    public static function getNavigationGroup(): ?string
    {
        return \Chanthoeun\FilamentCustomForms\CustomFormPlugin::get()->getNavigationGroup();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                \Filament\Schemas\Components\Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label(__('filament-custom-forms::fcf.field.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, $state) => $set('name', \Illuminate\Support\Str::slug($state, '_')))
                            ->unique(ignoreRecord: true)
                            ->helperText(__('filament-custom-forms::fcf.builder.fields.name_help')),
                        \Filament\Forms\Components\TextInput::make('label')
                            ->label(__('filament-custom-forms::fcf.field.label')),
                        \Filament\Forms\Components\Select::make('type')
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
                            ->live(),
                        \Filament\Forms\Components\Toggle::make('required')
                            ->label(__('filament-custom-forms::fcf.field.is_required'))
                            ->default(false)
                            ->hidden(fn ($get) => in_array($get('type'), ['section', 'grid', 'fieldset', 'wizard'])),

                        \Filament\Schemas\Components\Section::make(__('filament-custom-forms::fcf.admin.configuration'))
                            ->columnSpanFull()
                            ->components([
                                \Filament\Forms\Components\Select::make('options.columns')
                                    ->label(__('filament-custom-forms::fcf.admin.columns'))
                                    ->visible(fn ($get) => in_array($get('type'), ['grid', 'section', 'fieldset', 'repeater', 'wizard', 'checkbox_list']))
                                    ->options([
                                        '1' => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 1),
                                        '2' => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 2),
                                        '3' => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 3),
                                        '4' => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 4),
                                    ])
                                    ->default('2'),

                                \Filament\Forms\Components\KeyValue::make('options.choices')
                                    ->label(__('filament-custom-forms::fcf.admin.select_options'))
                                    ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']))
                                    ->helperText('Key corresponds to value, Label is displayed text.'),

                                \Filament\Forms\Components\TextInput::make('options.match_field')
                                    ->label('Match Field (Name)')
                                    ->visible(fn ($get) => $get('type') === 'confirm_password')
                                    ->helperText('Enter the name of the password field this should match.'),

                                \Filament\Forms\Components\KeyValue::make('options.column_span')
                                    ->label('Column Span (Responsive)')
                                    ->helperText('Key: Breakpoint (default, sm, md, lg, xl, 2xl). Value: Columns (1-12, full).')
                                    ->keyLabel('Breakpoint')
                                    ->valueLabel('Columns')
                                    ->formatStateUsing(fn ($state) => is_array($state) ? $state : (empty($state) ? [] : ['default' => $state])),

                                \Filament\Forms\Components\Toggle::make('options.column_span_full')
                                    ->label(__('filament-custom-forms::fcf.admin.full_width'))
                                    ->default(false),

                                \Filament\Forms\Components\Toggle::make('options.image_editor')
                                    ->label(__('filament-custom-forms::fcf.admin.enable_image_editor'))
                                    ->visible(fn ($get) => $get('type') === 'image'),

                                \Filament\Forms\Components\Toggle::make('options.is_revealable')
                                    ->label(__('filament-custom-forms::fcf.admin.allow_password_reveal'))
                                    ->visible(fn ($get) => $get('type') === 'password'),

                                \Filament\Forms\Components\Toggle::make('options.is_copyable')
                                    ->label(__('filament-custom-forms::fcf.admin.allow_copy'))
                                    ->visible(fn ($get) => in_array($get('type'), ['text_input', 'email', 'number_input', 'phone'])),

                                \Filament\Forms\Components\Toggle::make('options.is_decimal')
                                    ->label('Allow Decimals')
                                    ->visible(fn ($get) => in_array($get('type'), ['number_input', 'number']))
                                    ->default(true),

                                \Filament\Forms\Components\Toggle::make('options.is_hidden_label')
                                    ->label('Hide Label')
                                    ->default(false),

                                \Filament\Forms\Components\Toggle::make('options.is_hidden_on_view')
                                    ->label(__('filament-custom-forms::fcf.admin.hide_in_view'))
                                    ->default(false),

                                \Filament\Forms\Components\Toggle::make('options.is_inline')
                                    ->label('Display Inline')
                                    ->visible(fn ($get) => in_array($get('type'), ['radio', 'checkbox_list']))
                                    ->default(false),

                                \Filament\Forms\Components\Toggle::make('options.is_table')
                                    ->label('Use Table Layout (Simple)')
                                    ->visible(fn ($get) => $get('type') === 'repeater')
                                    ->default(false),

                                \Filament\Forms\Components\Toggle::make('options.is_compact')
                                    ->label('Compact Mode')
                                    ->visible(fn ($get) => $get('type') === 'repeater')
                                    ->default(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('label')
                    ->formatStateUsing(fn ($state) => is_array($state) && isset($state['en']) ? $state['en'] : (is_array($state) ? current($state) : $state))
                    ->label('Label (EN)'),
                \Filament\Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => \Chanthoeun\FilamentCustomForms\Models\CustomFormField::where('global_field_id', $record->id)->exists()),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
