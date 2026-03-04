<?php

namespace LaraSpace\FilamentCustomForms\Filament\Resources\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms;
use Filament\Forms\Components\Builder as FormBuilder;

class CustomFormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('custom_form.single')) // Or just 'Form Details'? I'll use a generic 'Form Details' if no key, or just use 'custom_form.single' contextually? The seeder has 'custom_form.single'. I'll stick to replacing inputs.
                    ->label(__('custom_form.single'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->label(__('custom_form.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($set, $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('custom_form.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('allowed_roles')
                            ->label('Allowed Roles')
                            ->multiple()
                            ->searchable()
                            ->options(\Spatie\Permission\Models\Role::pluck('name', 'name'))
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('custom_form.is_active'))
                            ->default(true)
                            ->required(),

                        Forms\Components\Toggle::make('enable_workflow')
                            ->label('Enable Request/Approval Workflow')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('reviewer_roles')
                            ->label('Reviewer Roles')
                            ->multiple()
                            ->searchable()
                            ->options(\Spatie\Permission\Models\Role::pluck('name', 'name'))
                            ->visible(fn(Get $get) => $get('enable_workflow')),

                        Forms\Components\Select::make('approver_roles')
                            ->label('Approver Roles')
                            ->multiple()
                            ->searchable()
                            ->options(\Spatie\Permission\Models\Role::pluck('name', 'name'))
                            ->visible(fn(Get $get) => $get('enable_workflow')),

                        Forms\Components\Select::make('reviewer_users')
                            ->label('Reviewer Users')
                            ->multiple()
                            ->searchable()
                            ->options(\App\Models\User::pluck('name', 'id'))
                            ->visible(fn(Get $get) => $get('enable_workflow')),

                        Forms\Components\Select::make('approver_users')
                            ->label('Approver Users')
                            ->multiple()
                            ->searchable()
                            ->options(\App\Models\User::pluck('name', 'id'))
                            ->visible(fn(Get $get) => $get('enable_workflow')),

                    ]),
                Section::make('Accounting Integration') // Keep hardcoded string as label for now, or use translation if preferred
                    ->columns(1)
                    ->columnSpanFull()
                    ->components([
                        Toggle::make('accounting_config.enabled')
                            ->label('Enable Accounting Transaction')
                            ->live(),
                        Grid::make(1)
                            ->visible(fn(Get $get) => $get('accounting_config.enabled'))
                            ->columnSpanFull()
                            ->components([
                                // Amount & Description (Common)
                                Grid::make(2)->components([
                                    Select::make('accounting_config.amount_field')
                                        ->label(__('custom_form.accounting_config.amount_field'))
                                        ->options(function (?Get $get, $record) {
                                            if ($record) {
                                                return $record->fields()
                                                    ->whereIn('type', ['number_input', 'money'])
                                                    ->pluck('label', 'name')
                                                    ->toArray();
                                            }
                                            return [];
                                        })
                                        ->required(),
                                    Select::make('accounting_config.description_field')
                                        ->label(__('custom_form.accounting_config.description_field'))
                                        ->options(function (?Get $get, $record) {
                                            if ($record) {
                                                return $record->fields()
                                                    ->whereIn('type', ['text_input', 'textarea', 'select', 'email', 'phone'])
                                                    ->pluck('label', 'name')
                                                    ->toArray();
                                            }
                                            return [];
                                        })
                                        ->required(),
                                ]),

                                // DEBIT CONFIGURATION
                                Section::make(__('custom_form.accounting_config.debit_side'))
                                    ->compact()
                                    ->schema([
                                        Select::make('accounting_config.debit_account_source')
                                            ->label(__('custom_form.accounting_config.source'))
                                            ->options([
                                                'fixed' => __('custom_form.accounting_config.fixed_source'),
                                                'field' => __('custom_form.accounting_config.field_source'),
                                            ])
                                            ->default('fixed')
                                            ->live()
                                            ->required(),
                                        Select::make('accounting_config.debit_account_id')
                                            ->label(__('custom_form.accounting_config.fixed_debit_account'))
                                            ->options(\App\Models\Account::whereIn('type', [\App\Enums\AccountType::EXPENSE, \App\Enums\AccountType::ASSET, \App\Enums\AccountType::LIABILITY, \App\Enums\AccountType::EQUITY])->pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->visible(fn(Get $get) => $get('accounting_config.debit_account_source') === 'fixed'),
                                        Select::make('accounting_config.debit_account_field')
                                            ->label(__('custom_form.accounting_config.debit_field'))
                                            ->helperText(__('custom_form.accounting_config.field_helper'))
                                            ->options(function (?Get $get, $record) {
                                                if ($record) {
                                                    return $record->fields()
                                                        ->whereIn('type', ['select']) // Only select fields make sense for picking an account
                                                        ->pluck('label', 'name')
                                                        ->toArray();
                                                }
                                                return [];
                                            })
                                            ->required()
                                            ->visible(fn(Get $get) => $get('accounting_config.debit_account_source') === 'field'),
                                    ]),

                                // CREDIT CONFIGURATION
                                Section::make(__('custom_form.accounting_config.credit_side'))
                                    ->compact()
                                    ->schema([
                                        Select::make('accounting_config.credit_account_source')
                                            ->label(__('custom_form.accounting_config.source'))
                                            ->options([
                                                'fixed' => __('custom_form.accounting_config.fixed_source'),
                                                'field' => __('custom_form.accounting_config.field_source'),
                                            ])
                                            ->default('fixed')
                                            ->live()
                                            ->required(),
                                        Select::make('accounting_config.credit_account_id')
                                            ->label(__('custom_form.accounting_config.fixed_credit_account'))
                                            ->options(\App\Models\Account::whereIn('type', [\App\Enums\AccountType::ASSET, \App\Enums\AccountType::LIABILITY, \App\Enums\AccountType::REVENUE, \App\Enums\AccountType::EQUITY])->pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->visible(fn(Get $get) => $get('accounting_config.credit_account_source') === 'fixed'),
                                        Select::make('accounting_config.credit_account_field')
                                            ->label(__('custom_form.accounting_config.credit_field'))
                                            ->helperText(__('custom_form.accounting_config.field_helper'))
                                            ->options(function (?Get $get, $record) {
                                                if ($record) {
                                                    return $record->fields()
                                                        ->whereIn('type', ['select'])
                                                        ->pluck('label', 'name')
                                                        ->toArray();
                                                }
                                                return [];
                                            })
                                            ->required()
                                            ->visible(fn(Get $get) => $get('accounting_config.credit_account_source') === 'field'),
                                    ]),
                            ]),
                    ]),

                // Section::make('Form Builder') -> Removed in favor of FieldsRelationManager
            ]);
    }

    public static function getFormBlocks(bool $includeLayouts = true): array
    {
        $blocks = [
            FormBuilder\Block::make('text_input')
                ->label('Text Input')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required()->helperText('Internal name (slug)'),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('textarea')
                ->label('Text Area')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('number_input')
                ->label('Number Input')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('money')
                ->label('Money Input')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('email')
                ->label('Email')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('phone')
                ->label('Phone')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('password')
                ->label('Password')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('date_picker')
                ->label('Date Picker')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('time_picker')
                ->label('Time Picker')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('boolean')
                ->label('Boolean (Toggle)')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('default')->label('Default to True'),
                ]),
            FormBuilder\Block::make('image')
                ->label('Image')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('select')
                ->label('Select Dropdown')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required(),
                    TextInput::make('label')->label('Label')->required(),
                    Forms\Components\Repeater::make('options')
                        ->schema([
                            Forms\Components\TextInput::make('value')->required(),
                            Forms\Components\TextInput::make('label')->required(),
                        ]),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('season_select')
                ->label('Season Select')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required()->helperText('This will sync to season_id if unique'),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('farmer_select')
                ->label('Farmer Select')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required()->helperText('This will sync to farmer_id if unique'),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('land_select')
                ->label('Land Select')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required()->helperText('This will sync to land_id if unique'),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
            FormBuilder\Block::make('block_select')
                ->label('Block Select')
                ->schema([
                    TextInput::make('name')->label('Field Name')->required()->helperText('This will sync to block_id if unique'),
                    TextInput::make('label')->label('Label')->required(),
                    Toggle::make('required')->label('Required'),
                ]),
        ];

        if ($includeLayouts) {
            $blocks[] = FormBuilder\Block::make('section')
                ->label('Section')
                ->schema([
                    TextInput::make('heading')->label('Heading'),
                    Forms\Components\Select::make('columns')
                        ->options([
                            1 => '1 Column',
                            2 => '2 Columns',
                            3 => '3 Columns',
                            4 => '4 Columns',
                        ])
                        ->default(2),
                    FormBuilder::make('schema')
                        ->label('Section Content')
                        ->blocks(self::getFormBlocks(includeLayouts: false)) // Prevent infinite nesting for simplicity
                ]);

            $blocks[] = FormBuilder\Block::make('grid')
                ->label('Grid')
                ->schema([
                    Forms\Components\Select::make('columns')
                        ->options([
                            2 => '2 Columns',
                            3 => '3 Columns',
                            4 => '4 Columns',
                        ])
                        ->default(2),
                    FormBuilder::make('schema')
                        ->label('Grid Content')
                        ->blocks(self::getFormBlocks(includeLayouts: false))
                ]);

            $blocks[] = FormBuilder\Block::make('fieldset')
                ->label('Fieldset')
                ->schema([
                    TextInput::make('label')->label('Legend')->required(),
                    Forms\Components\Select::make('columns')
                        ->options([
                            1 => '1 Column',
                            2 => '2 Columns',
                            3 => '3 Columns',
                        ])
                        ->default(2),
                    FormBuilder::make('schema')
                        ->label('Fieldset Content')
                        ->blocks(self::getFormBlocks(includeLayouts: false))
                ]);

            $blocks[] = FormBuilder\Block::make('repeater')
                ->label('Repeater')
                ->schema([
                    TextInput::make('label')->label('Label')->required(),
                    TextInput::make('name')->label('Field Name (Key)')->required()->helperText('Internal key for data storage'),
                    Forms\Components\Select::make('columns')
                        ->options([
                            1 => '1 Column',
                            2 => '2 Columns',
                        ])
                        ->default(1),
                    Toggle::make('required')->label('Required'),
                    FormBuilder::make('schema')
                        ->label('Repeater Fields')
                        ->blocks(self::getFormBlocks(includeLayouts: false))
                ]);
        }

        return $blocks;
    }
}
