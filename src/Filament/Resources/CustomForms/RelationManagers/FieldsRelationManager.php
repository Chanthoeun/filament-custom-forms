<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\RelationManagers;

use Chanthoeun\FilamentCustomForms\CustomFormPlugin;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
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
                            ->live(),
                        Toggle::make('required')
                            ->label(__('filament-custom-forms::fcf.field.is_required'))
                            ->default(false)
                            ->hidden(fn ($get) => in_array($get('type'), ['section', 'grid', 'fieldset', 'wizard'])),

                        Section::make(__('filament-custom-forms::fcf.admin.configuration'))
                            ->columnSpanFull()
                            ->components([
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

                                KeyValue::make('options.choices')
                                    ->label(__('filament-custom-forms::fcf.admin.select_options'))
                                    ->visible(fn ($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list']))
                                    ->helperText('Key corresponds to value, Label is displayed text.'),

                                TextInput::make('options.match_field')
                                    ->label('Match Field (Name)')
                                    ->visible(fn ($get) => $get('type') === 'confirm_password')
                                    ->helperText('Enter the name of the password field this should match.'),

                                KeyValue::make('options.column_span')
                                    ->label('Column Span (Responsive)')
                                    ->helperText('Key: Breakpoint (default, sm, md, lg, xl, 2xl). Value: Columns (1-12, full).')
                                    ->keyLabel('Breakpoint')
                                    ->valueLabel('Columns')
                                    ->formatStateUsing(fn ($state) => is_array($state) ? $state : (empty($state) ? [] : ['default' => $state])),

                                Toggle::make('options.column_span_full')
                                    ->label(__('filament-custom-forms::fcf.admin.full_width'))
                                    ->default(false),

                                Toggle::make('options.image_editor')
                                    ->label(__('filament-custom-forms::fcf.admin.enable_image_editor'))
                                    ->visible(fn ($get) => $get('type') === 'image'),

                                Toggle::make('options.is_revealable')
                                    ->label(__('filament-custom-forms::fcf.admin.allow_password_reveal'))
                                    ->visible(fn ($get) => $get('type') === 'password'),

                                Toggle::make('options.is_copyable')
                                    ->label(__('filament-custom-forms::fcf.admin.allow_copy'))
                                    ->visible(fn ($get) => in_array($get('type'), ['text_input', 'email', 'number_input', 'phone'])),

                                Toggle::make('options.is_decimal')
                                    ->label('Allow Decimals')
                                    ->visible(fn ($get) => in_array($get('type'), ['number_input', 'number']))
                                    ->default(true),

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
                TextColumn::make('parent.name')->label(__('filament-custom-forms::fcf.admin.parent_container'))->badge(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc')
            ->headerActions(array_filter([
                CustomFormPlugin::get()->hasTranslations() ? LocaleSwitcher::make() : null,
                CreateAction::make(),
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
