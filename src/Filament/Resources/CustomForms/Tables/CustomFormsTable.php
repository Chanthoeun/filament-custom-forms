<?php

namespace Chanthoeun\FilamentCustomForms\Filament\Resources\CustomForms\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class CustomFormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-custom-forms::fcf.form.name'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(__('filament-custom-forms::fcf.form.slug'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('filament-custom-forms::fcf.form.is_active'))
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label(__('filament-custom-forms::fcf.general.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('filament-custom-forms::fcf.general.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('filament-custom-forms::fcf.general.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([ // Standard way is actions()
                \Filament\Actions\Action::make('edit_template')
                    ->label('Edit Template')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(function ($record) {
                        if (class_exists(\Chanthoeun\FilamentDocumentBuilder\Models\DocumentTemplate::class)) {
                            $template = \Chanthoeun\FilamentDocumentBuilder\Models\DocumentTemplate::where('type', 'custom_form_' . $record->id)->first();
                            if ($template) {
                                if (class_exists(\App\Filament\Resources\DocumentTemplateResource::class)) {
                                    return \App\Filament\Resources\DocumentTemplateResource::getUrl('edit', ['record' => $template]);
                                } elseif (class_exists(\Chanthoeun\FilamentDocumentBuilder\Resources\DocumentTemplateResource::class)) {
                                    return \Chanthoeun\FilamentDocumentBuilder\Resources\DocumentTemplateResource::getUrl('edit', ['record' => $template]);
                                }
                            }
                        }
                        return null;
                    })
                    ->visible(function ($record) {
                        if (!class_exists(\Chanthoeun\FilamentDocumentBuilder\Models\DocumentTemplate::class)) {
                            return false;
                        }
                        return \Chanthoeun\FilamentDocumentBuilder\Models\DocumentTemplate::where('type', 'custom_form_' . $record->id)->exists();
                    }),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
