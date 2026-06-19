<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\Schemas;

use Filament\Forms;
use Filament\Forms\Components\Builder as FormBuilder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CustomFormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament-custom-forms::fcf.form.details'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament-custom-forms::fcf.form.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state)))
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('filament-custom-forms::fcf.form.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_active')
                            ->label(__('filament-custom-forms::fcf.form.is_active'))
                            ->default(true)
                            ->required(),
                        \Filament\Forms\Components\Repeater::make('panel_access')
                            ->label('Panel Access Configuration')
                            ->columnSpanFull()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['panel_id'] ? ucfirst($state['panel_id']) : null)
                            ->schema([
                                Select::make('panel_id')
                                    ->label('Filament Panel')
                                    ->options(function () {
                                        return collect(filament()->getPanels())->mapWithKeys(fn ($panel) => [$panel->getId() => ucfirst($panel->getId())])->toArray();
                                    })
                                    ->required()
                                    ->columnSpan(1),
                                
                                \Filament\Forms\Components\Select::make('allowed_users')
                                    ->label('Allowed Users')
                                    ->multiple()
                                    ->searchable()
                                    ->options(function () {
                                        $userModel = config('auth.providers.users.model', \App\Models\User::class);
                                        return class_exists($userModel) ? $userModel::pluck('email', 'id')->toArray() : [];
                                    })
                                    ->columnSpan(1),
                                \Filament\Forms\Components\Select::make('allowed_roles')
                                    ->label('Allowed Roles')
                                    ->multiple()
                                    ->searchable()
                                    ->hidden(!class_exists(\Spatie\Permission\Models\Role::class))
                                    ->options(function () {
                                        $roleClass = config('permission.models.role', \Spatie\Permission\Models\Role::class);
                                        return class_exists($roleClass) 
                                            ? $roleClass::pluck('name', 'name')->toArray() 
                                            : [];
                                    })
                                    ->columnSpan(1),
                                
                                \Filament\Forms\Components\Toggle::make('isolate_users')
                                    ->label('Isolate User Data')
                                    ->helperText('If enabled, users can only see form entries they created themselves.')
                                    ->default(false)
                                    ->columnSpan(1),

                                \Filament\Schemas\Components\Section::make('Permissions')
                                    ->hidden(!class_exists(\Spatie\Permission\Models\Permission::class))
                                    ->schema([
                                        \Filament\Forms\Components\CheckboxList::make('custom_form_entry_permissions')
                                            ->hiddenLabel()
                                            ->options(function () {
                                                $permissionClass = config('permission.models.permission', \Spatie\Permission\Models\Permission::class);
                                                if (!class_exists($permissionClass)) {
                                                    return [];
                                                }
                                                
                                                $permissions = $permissionClass::pluck('name', 'name')
                                                    ->filter(fn($name) => str_contains($name, 'CustomFormEntry'))
                                                    ->toArray();
                                                    
                                                $options = [];
                                                foreach ($permissions as $name) {
                                                    $label = str_replace(['CustomFormEntry', ':'], ['', ' '], $name);
                                                    $options[$name] = \Illuminate\Support\Str::headline($label);
                                                }
                                                
                                                return $options;
                                            })
                                            ->bulkToggleable()
                                            ->columns(4)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->compact()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public static function getFormBlocks(bool $includeLayouts = true): array
    {
        $blocks = [
            FormBuilder\Block::make('text_input')
                ->label(__('filament-custom-forms::fcf.builder.blocks.text_input'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required()->helperText(__('filament-custom-forms::fcf.builder.fields.name_help')),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('textarea')
                ->label(__('filament-custom-forms::fcf.builder.blocks.textarea'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('number_input')
                ->label(__('filament-custom-forms::fcf.builder.blocks.number_input'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('money')
                ->label(__('filament-custom-forms::fcf.builder.blocks.money'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('email')
                ->label(__('filament-custom-forms::fcf.builder.blocks.email'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('phone')
                ->label(__('filament-custom-forms::fcf.builder.blocks.phone'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('password')
                ->label(__('filament-custom-forms::fcf.builder.blocks.password'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('confirm_password')
                ->label('Confirm Password')
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    TextInput::make('match_field')->label('Match Field (Name)')->required()->helperText('Enter the name of the password field this should match.'),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('date_picker')
                ->label(__('filament-custom-forms::fcf.builder.blocks.date_picker'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('time_picker')
                ->label(__('filament-custom-forms::fcf.builder.blocks.time_picker'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('boolean')
                ->label(__('filament-custom-forms::fcf.builder.blocks.boolean'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('default')->label(__('filament-custom-forms::fcf.builder.fields.default')),
                ]),
            FormBuilder\Block::make('image')
                ->label(__('filament-custom-forms::fcf.builder.blocks.image'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
            FormBuilder\Block::make('select')
                ->label(__('filament-custom-forms::fcf.builder.blocks.select'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    Forms\Components\Repeater::make('options')
                        ->label(__('filament-custom-forms::fcf.builder.fields.choices'))
                        ->schema([
                            TextInput::make('value')->label(__('filament-custom-forms::fcf.builder.fields.value'))->required(),
                            TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                        ]),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                ]),
        ];

        if ($includeLayouts) {
            $blocks[] = FormBuilder\Block::make('section')
                ->label(__('filament-custom-forms::fcf.builder.blocks.section'))
                ->schema([
                    TextInput::make('heading')->label(__('filament-custom-forms::fcf.builder.fields.heading')),
                    Select::make('columns')
                        ->label(__('filament-custom-forms::fcf.builder.fields.columns'))
                        ->options([
                            1 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 1),
                            2 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 2),
                            3 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 3),
                            4 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 4),
                        ])
                        ->default(2),
                    FormBuilder::make('schema')
                        ->label(__('filament-custom-forms::fcf.builder.fields.section_content'))
                        ->blocks(self::getFormBlocks(includeLayouts: false)), // Prevent infinite nesting for simplicity
                ]);

            $blocks[] = FormBuilder\Block::make('grid')
                ->label(__('filament-custom-forms::fcf.builder.blocks.grid'))
                ->schema([
                    Select::make('columns')
                        ->label(__('filament-custom-forms::fcf.builder.fields.columns'))
                        ->options([
                            2 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 2),
                            3 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 3),
                            4 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 4),
                        ])
                        ->default(2),
                    FormBuilder::make('schema')
                        ->label(__('filament-custom-forms::fcf.builder.fields.grid_content'))
                        ->blocks(self::getFormBlocks(includeLayouts: false)),
                ]);

            $blocks[] = FormBuilder\Block::make('fieldset')
                ->label(__('filament-custom-forms::fcf.builder.blocks.fieldset'))
                ->schema([
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.legend'))->required(),
                    Select::make('columns')
                        ->label(__('filament-custom-forms::fcf.builder.fields.columns'))
                        ->options([
                            1 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 1),
                            2 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 2),
                            3 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 3),
                        ])
                        ->default(2),
                    FormBuilder::make('schema')
                        ->label(__('filament-custom-forms::fcf.builder.fields.fieldset_content'))
                        ->blocks(self::getFormBlocks(includeLayouts: false)),
                ]);

            $blocks[] = FormBuilder\Block::make('repeater')
                ->label(__('filament-custom-forms::fcf.builder.blocks.repeater'))
                ->schema([
                    TextInput::make('label')->label(__('filament-custom-forms::fcf.builder.fields.label'))->required(),
                    TextInput::make('name')->label(__('filament-custom-forms::fcf.builder.fields.name'))->required()->helperText(__('filament-custom-forms::fcf.builder.fields.name_help')),
                    Select::make('columns')
                        ->label(__('filament-custom-forms::fcf.builder.fields.columns'))
                        ->options([
                            1 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 1),
                            2 => trans_choice('filament-custom-forms::fcf.builder.fields.columns_help', 2),
                        ])
                        ->default(1),
                    Toggle::make('required')->label(__('filament-custom-forms::fcf.builder.fields.required')),
                    FormBuilder::make('schema')
                        ->label(__('filament-custom-forms::fcf.builder.fields.repeater_fields'))
                        ->blocks(self::getFormBlocks(includeLayouts: false)),
                ]);
        }

        return $blocks;
    }
}
