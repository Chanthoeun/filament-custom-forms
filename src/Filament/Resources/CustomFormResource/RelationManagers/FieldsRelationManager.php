<?php

namespace Dcx\FilamentCustomForms\Filament\Resources\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        \Filament\Forms\Components\Select::make('parent_id')
                            ->label(__('admin_panel.parent_container'))
                            ->options(function ($livewire) {
                                return $livewire->getOwnerRecord()->fields()
                                    ->whereIn('type', ['section', 'grid', 'fieldset', 'repeater', 'wizard'])
                                    ->get()
                                    ->mapWithKeys(fn($field) => [$field->id => $field->label ?? $field->name]);
                            })
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        \Filament\Forms\Components\TextInput::make('name')
                            ->label(__('custom_form_field.name'))
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn(\Illuminate\Validation\Rules\Unique $rule, $livewire) => $rule->where('custom_form_id', $livewire->getOwnerRecord()->id)
                            )
                            ->helperText('Use snake_case keys. For layouts, this is internal ID.'),
                        \Filament\Forms\Components\TextInput::make('label')
                            ->label(__('custom_form_field.label')),
                        \Filament\Forms\Components\Select::make('type')
                            ->label(__('custom_form_field.type'))
                            ->required()
                            ->options([
                                'Layouts' => [
                                    'section' => 'Section',
                                    'grid' => 'Grid',
                                    'fieldset' => 'Fieldset',
                                    'repeater' => 'Repeater',
                                    'wizard' => 'Wizard',
                                ],
                                'Fields' => [
                                    'text_input' => 'Text Input',
                                    'textarea' => 'Text Area',
                                    'email' => 'Email',
                                    'number_input' => 'Number',
                                    'money' => 'Money',
                                    'date_picker' => 'Date',
                                    'time_picker' => 'Time Picker',
                                    'boolean' => 'Boolean (Toggle)',
                                    'select' => 'Select',
                                    'image' => 'Image',
                                    'password' => 'Password',
                                    'phone' => 'Phone',
                                    'season_select' => 'Season Select',
                                    'farmer_select' => 'Farmer Select',
                                    'land_select' => 'Land Select',
                                    'block_select' => 'Block Select',
                                ],
                            ])
                            ->default('text_input')
                            ->live(),
                        \Filament\Forms\Components\Toggle::make('required')
                            ->label(__('custom_form_field.is_required'))
                            ->default(false)
                            ->visible(fn($get) => in_array($get('type'), ['repeater']))
                            ->hidden(fn($get) => in_array($get('type'), ['section', 'grid', 'fieldset', 'wizard'])),

                        \Filament\Schemas\Components\Section::make(__('admin_panel.configuration'))
                            ->columnSpanFull()
                            ->components([
                                \Filament\Forms\Components\Select::make('options.columns')
                                    ->label(__('admin_panel.columns'))
                                    ->visible(fn($get) => in_array($get('type'), ['grid', 'section', 'fieldset', 'repeater', 'wizard']))
                                    ->options([
                                        '1' => '1 Column',
                                        '2' => '2 Columns',
                                        '3' => '3 Columns',
                                        '4' => '4 Columns',
                                    ])
                                    ->default('2'),

                                \Filament\Forms\Components\KeyValue::make('options.choices')
                                    ->label(__('admin_panel.select_options'))
                                    ->visible(fn($get) => $get('type') === 'select')
                                    ->helperText('Key corresponds to value, Label is displayed text.'),

                                \Filament\Forms\Components\KeyValue::make('options.column_span')
                                    ->label('Column Span (Responsive)')
                                    ->helperText('Key: Breakpoint (default, sm, md, lg, xl, 2xl). Value: Columns (1-12, full).')
                                    ->keyLabel('Breakpoint')
                                    ->valueLabel('Columns')
                                    ->formatStateUsing(fn($state) => is_array($state) ? $state : (empty($state) ? [] : ['default' => $state])),

                                \Filament\Forms\Components\Toggle::make('options.column_span_full')
                                    ->label(__('admin_panel.full_width'))
                                    ->default(false),

                                \Filament\Forms\Components\Toggle::make('options.image_editor')
                                    ->label(__('admin_panel.enable_image_editor'))
                                    ->visible(fn($get) => $get('type') === 'image'),

                                \Filament\Forms\Components\Toggle::make('options.is_revealable')
                                    ->label(__('admin_panel.allow_password_reveal'))
                                    ->visible(fn($get) => $get('type') === 'password'),

                                \Filament\Forms\Components\Toggle::make('options.is_copyable')
                                    ->label(__('admin_panel.allow_copy'))
                                    ->visible(fn($get) => in_array($get('type'), ['text_input', 'email', 'number_input', 'phone'])),

                                \Filament\Forms\Components\Toggle::make('options.is_decimal')
                                    ->label('Allow Decimals')
                                    ->visible(fn($get) => in_array($get('type'), ['number_input', 'number']))
                                    ->default(true),

                                \Filament\Forms\Components\Toggle::make('options.is_hidden_label')
                                    ->label('Hide Label')
                                    ->default(false),

                                \Filament\Forms\Components\Toggle::make('options.is_hidden_on_view')
                                    ->label(__('admin_panel.hide_in_view'))
                                    ->default(false),

                                \Filament\Forms\Components\Radio::make('options.currency')
                                    ->label('Currency')
                                    ->visible(fn($get) => $get('type') === 'money')
                                    ->options(\App\Enums\Currency::class)
                                    ->default(\App\Enums\Currency::USD)
                                    ->inline(),

                                \Filament\Forms\Components\Toggle::make('options.is_table')
                                    ->label('Use Table Layout (Simple)')
                                    ->visible(fn($get) => $get('type') === 'repeater')
                                    ->default(false),

                                \Filament\Forms\Components\Toggle::make('options.is_compact')
                                    ->label('Compact Mode')
                                    ->visible(fn($get) => $get('type') === 'repeater')
                                    ->default(false),
                            ]),
                    ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label(__('custom_form_field.name'))->searchable(),
                TextColumn::make('label')->label(__('custom_form_field.label'))->searchable(),
                BooleanColumn::make('required')->label(__('custom_form_field.is_required')),
                TextColumn::make('type')->label(__('custom_form_field.type'))->badge()->color(fn(string $state): string => match ($state) {
                    'section', 'grid', 'fieldset', 'wizard' => 'info',
                    default => 'gray',
                }),
                TextColumn::make('parent.name')->label(__('admin_panel.parent_container'))->badge(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc')
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
