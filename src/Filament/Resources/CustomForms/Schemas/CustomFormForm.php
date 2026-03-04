<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
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
                Section::make(__('filament-custom-forms::custom_form.details'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament-custom-forms::custom_form.name'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($set, $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('filament-custom-forms::custom_form.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_active')
                            ->label(__('filament-custom-forms::custom_form.is_active'))
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }

    public static function getFormBlocks(bool $includeLayouts = true): array
    {
        $blocks = [
            FormBuilder\Block::make('text_input')
                ->label(__('filament-custom-forms::form_builder.blocks.text_input'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required()->helperText(__('filament-custom-forms::form_builder.fields.name_help')),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('textarea')
                ->label(__('filament-custom-forms::form_builder.blocks.textarea'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('number_input')
                ->label(__('filament-custom-forms::form_builder.blocks.number_input'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('money')
                ->label(__('filament-custom-forms::form_builder.blocks.money'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('email')
                ->label(__('filament-custom-forms::form_builder.blocks.email'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('phone')
                ->label(__('filament-custom-forms::form_builder.blocks.phone'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('password')
                ->label(__('filament-custom-forms::form_builder.blocks.password'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('date_picker')
                ->label(__('filament-custom-forms::form_builder.blocks.date_picker'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('time_picker')
                ->label(__('filament-custom-forms::form_builder.blocks.time_picker'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('boolean')
                ->label(__('filament-custom-forms::form_builder.blocks.boolean'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('default')->label(__('filament-custom-forms::form_builder.fields.default')),
                ]),
            FormBuilder\Block::make('image')
                ->label(__('filament-custom-forms::form_builder.blocks.image'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
            FormBuilder\Block::make('select')
                ->label(__('filament-custom-forms::form_builder.blocks.select'))
                ->schema([
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required(),
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    Forms\Components\Repeater::make('options')
                        ->label(__('filament-custom-forms::form_builder.fields.choices'))
                        ->schema([
                            Forms\Components\TextInput::make('value')->label(__('filament-custom-forms::form_builder.fields.value'))->required(),
                            Forms\Components\TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                        ]),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                ]),
        ];

        if ($includeLayouts) {
            $blocks[] = FormBuilder\Block::make('section')
                ->label(__('filament-custom-forms::form_builder.blocks.section'))
                ->schema([
                    TextInput::make('heading')->label(__('filament-custom-forms::form_builder.fields.heading')),
                    Forms\Components\Select::make('columns')
                        ->label(__('filament-custom-forms::form_builder.fields.columns'))
                        ->options([
                            1 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 1),
                            2 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 2),
                            3 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 3),
                            4 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 4),
                        ])
                        ->default(2),
                    FormBuilder::make('schema')
                        ->label(__('filament-custom-forms::form_builder.fields.section_content'))
                        ->blocks(self::getFormBlocks(includeLayouts: false)) // Prevent infinite nesting for simplicity
                ]);

            $blocks[] = FormBuilder\Block::make('grid')
                ->label(__('filament-custom-forms::form_builder.blocks.grid'))
                ->schema([
                    Forms\Components\Select::make('columns')
                        ->label(__('filament-custom-forms::form_builder.fields.columns'))
                        ->options([
                            2 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 2),
                            3 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 3),
                            4 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 4),
                        ])
                        ->default(2),
                    FormBuilder::make('schema')
                        ->label(__('filament-custom-forms::form_builder.fields.grid_content'))
                        ->blocks(self::getFormBlocks(includeLayouts: false))
                ]);

            $blocks[] = FormBuilder\Block::make('fieldset')
                ->label(__('filament-custom-forms::form_builder.blocks.fieldset'))
                ->schema([
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.legend'))->required(),
                    Forms\Components\Select::make('columns')
                        ->label(__('filament-custom-forms::form_builder.fields.columns'))
                        ->options([
                            1 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 1),
                            2 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 2),
                            3 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 3),
                        ])
                        ->default(2),
                    FormBuilder::make('schema')
                        ->label(__('filament-custom-forms::form_builder.fields.fieldset_content'))
                        ->blocks(self::getFormBlocks(includeLayouts: false))
                ]);

            $blocks[] = FormBuilder\Block::make('repeater')
                ->label(__('filament-custom-forms::form_builder.blocks.repeater'))
                ->schema([
                    TextInput::make('label')->label(__('filament-custom-forms::form_builder.fields.label'))->required(),
                    TextInput::make('name')->label(__('filament-custom-forms::form_builder.fields.name'))->required()->helperText(__('filament-custom-forms::form_builder.fields.name_help')),
                    Forms\Components\Select::make('columns')
                        ->label(__('filament-custom-forms::form_builder.fields.columns'))
                        ->options([
                            1 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 1),
                            2 => trans_choice('filament-custom-forms::form_builder.fields.columns_help', 2),
                        ])
                        ->default(1),
                    Toggle::make('required')->label(__('filament-custom-forms::form_builder.fields.required')),
                    FormBuilder::make('schema')
                        ->label(__('filament-custom-forms::form_builder.fields.repeater_fields'))
                        ->blocks(self::getFormBlocks(includeLayouts: false))
                ]);
        }

        return $blocks;
    }
}
