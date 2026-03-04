<?php

namespace Dcx\FilamentCustomForms\Filament\Resources\Tables;

use App\Filament\Actions\PrintAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Actions\Action; // Use generic Action
use Illuminate\Database\Eloquent\Builder;

class CustomFormEntriesTable
{
    public static function configure(Table $table): Table
    {
        $formId = self::getFormId($table);

        return $table
            ->columns(self::getColumns($formId))
            ->filters(self::getFilters($formId))
            ->recordActions(self::getRecordActions())
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn(Builder $query) => self::applyQueryConstraints($query, $formId));
    }

    protected static function getFormId(Table $table): ?string
    {
        $livewire = $table->getLivewire();

        return data_get($livewire, 'tableFilters.custom_form_id.value')
            ?? data_get($livewire, 'activeFormId')
            ?? request()->input('tableFilters.custom_form_id.value')
            ?? data_get(request()->query('tableFilters'), 'custom_form_id.value')
            ?? request()->query('form_id');
    }

    protected static function getColumns(?string $formId): array
    {
        $columns = [];

        // Fetch fields metadata from parent CustomFormField table first
        $fieldsMetadata = \App\Models\CustomFormField::query()
            ->when($formId, fn($query) => $query->where('custom_form_id', $formId))
            ->orderBy('sort')
            ->get()
            ->keyBy('name');

        $definedKeys = $fieldsMetadata->keys();

        // Fetch keys from existing entries to catch any legacy/extra data (Limited to 20 for performance)
        $dataKeys = \App\Models\CustomFormEntry::query()
            ->when($formId, fn($query) => $query->where('custom_form_id', $formId))
            ->latest()
            ->limit(20)
            ->get()
            ->flatMap(fn($entry) => array_keys(is_array($entry->data) ? $entry->data : []))
            ->unique();

        $keys = $definedKeys->merge($dataKeys)->unique();

        $sortOrder = $fieldsMetadata->pluck('sort', 'name');
        $fieldTypes = $fieldsMetadata->pluck('type', 'name');
        $fieldOptions = $fieldsMetadata->pluck('options', 'name');
        $fieldsById = $fieldsMetadata->keyBy('id');

        $sortedKeys = $keys->sortBy(fn($key) => $sortOrder[$key] ?? 999999);

        foreach ($sortedKeys as $key) {
            if (in_array(($fieldTypes[$key] ?? null), ['repeater', 'section', 'grid', 'fieldset'])) {
                continue;
            }

            $field = $fieldsMetadata[$key] ?? null;
            if ($field && $field->parent_id) {
                $parent = $fieldsById[$field->parent_id] ?? null;
                if ($parent && $parent->type === 'repeater') {
                    continue;
                }
            }

            $columnKey = "data.{$key}";
            $label = \Illuminate\Support\Str::headline($key);

            $column = TextColumn::make($columnKey)
                ->label($label)
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false);

            if (($fieldTypes[$key] ?? null) === 'number_input') {
                $column->numeric();
            }

            if (($fieldTypes[$key] ?? null) === 'money') {
                $currency = $fieldOptions[$key]['currency'] ?? 'USD';
                $column->money(strtoupper($currency));
            }

            if (($fieldTypes[$key] ?? null) === 'time_picker') {
                $column->time();
            }

            if (($fieldTypes[$key] ?? null) === 'season_select') {
                $column->formatStateUsing(fn($state, $record) => $record->season?->name ?? $state);
            }
            if (($fieldTypes[$key] ?? null) === 'farmer_select') {
                $column->formatStateUsing(function ($state, $record) {
                    $farmer = $record->farmer;
                    if (!$farmer)
                        return $state;

                    $label = $farmer->code . ' - ' . $farmer->name;
                    if ($farmer->spouse) {
                        $label .= ' - ' . $farmer->spouse;
                    }
                    return $label;
                });
            }
            if (($fieldTypes[$key] ?? null) === 'land_select') {
                $column->formatStateUsing(function ($state, $record) {
                    $land = $record->land;
                    if (!$land)
                        return $state;
                    return $land->parcel_code . ' - ' . ($land->block->name ?? '') . ' - ' . $land->area_size;
                });
            }
            if (($fieldTypes[$key] ?? null) === 'block_select') {
                $column->formatStateUsing(fn($state, $record) => $record->block?->name ?? $state);
            }

            $columns[] = $column;
        }

        $columns[] = TextColumn::make('status')
            ->label(__('custom_form_entry.status'))
            ->badge()
            ->sortable();

        if (count($columns) > 0) {
            $columns[] = TextColumn::make('created_at')
                ->label(__('general.created_at'))
                ->dateTime()
                ->sortable();
        }

        return $columns;
    }

    protected static function getFilters(?string $formId): array
    {
        $filters = [];

        if ($formId) {
            $formSchema = \App\Models\CustomForm::find($formId);
            if ($formSchema) {
                $schemaFields = $formSchema->fields()->orderBy('sort')->get();

                foreach ($schemaFields as $field) {
                    $jsonKey = $field->name;
                    $label = $field->label ?? $field->name;

                    switch ($field->type) {
                        case 'boolean':
                            $filters[] = TernaryFilter::make($field->name)
                                ->label($label)
                                ->query(
                                    fn(Builder $query, array $data) =>
                                    $query->when(
                                        isset($data['value']),
                                        fn($q) => $q->where("data->{$jsonKey}", $data['value'] === '1' || $data['value'] === true)
                                    )
                                );
                            break;

                        case 'select':
                            $choices = $field->options['choices'] ?? [];
                            if (!empty($choices)) {
                                $filters[] = SelectFilter::make($field->name)
                                    ->label($label)
                                    ->options($choices)
                                    ->query(
                                        fn(Builder $query, array $data) =>
                                        $query->when(
                                            $data['value'],
                                            fn($q) => $q->where("data->{$jsonKey}", $data['value'])
                                        )
                                    );
                            }
                            break;

                        case 'date_picker':
                            $filters[] = Filter::make($field->name)
                                ->label($label)
                                ->form([
                                    DatePicker::make('from')->label($label . ' From'),
                                    DatePicker::make('until')->label($label . ' Until'),
                                ])
                                ->query(function (Builder $query, array $data) use ($jsonKey) {
                                    return $query
                                        ->when($data['from'], fn($q) => $q->where("data->{$jsonKey}", '>=', $data['from']))
                                        ->when($data['until'], fn($q) => $q->where("data->{$jsonKey}", '<=', $data['until']));
                                });
                            break;
                    }
                }
            }
        }

        $filters[] = SelectFilter::make('status')
            ->label(__('custom_form_entry.status'))
            ->options(\App\Enums\CustomFormEntryStatus::class);

        $filters[] = SelectFilter::make('custom_form_id')
            ->label('Form')
            ->options(\App\Models\CustomForm::pluck('name', 'id'))
            ->hidden();

        return $filters;
    }

    protected static function getRecordActions(): array
    {
        return [
            Action::make('review')
                ->label(__('custom_form_entry.action.review'))
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->visible(function ($record) {
                    $form = $record->customForm;
                    if (!$form || !$form->enable_workflow)
                        return false;
                    if ($record->status !== \App\Enums\CustomFormEntryStatus::Submitted)
                        return false;

                    $user = auth()->user();
                    if (!$user)
                        return false;

                    if ($form->reviewer_users && in_array($user->id, $form->reviewer_users))
                        return true;
                    if ($form->reviewer_roles && $user->hasAnyRole($form->reviewer_roles))
                        return true;

                    return false;
                })
                ->action(function ($record) {
                    $record->update(['status' => \App\Enums\CustomFormEntryStatus::Reviewed]);
                    \Filament\Notifications\Notification::make()
                        ->title(__('custom_form_entry.action.reviewed'))
                        ->success()
                        ->send();
                }),

            Action::make('approve')
                ->label(__('custom_form_entry.action.approve'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(function ($record) {
                    $form = $record->customForm;
                    if (!$form || !$form->enable_workflow)
                        return false;
                    if ($record->status !== \App\Enums\CustomFormEntryStatus::Reviewed)
                        return false;

                    $user = auth()->user();
                    if (!$user)
                        return false;

                    if ($form->approver_users && in_array($user->id, $form->approver_users))
                        return true;
                    if ($form->approver_roles && $user->hasAnyRole($form->approver_roles))
                        return true;

                    return false;
                })
                ->action(function ($record) {
                    $record->update(['status' => \App\Enums\CustomFormEntryStatus::Approved]);
                    \Filament\Notifications\Notification::make()
                        ->title(__('custom_form_entry.action.approved'))
                        ->success()
                        ->send();
                }),

            EditAction::make(),
            DeleteAction::make(),
            PrintAction::make(),
        ];
    }

    protected static function applyQueryConstraints(Builder $query, ?string $formId): Builder
    {
        $user = auth()->user();
        if (!$user)
            return $query;

        $query->with(['creator', 'customForm', 'season', 'farmer', 'land.block', 'block'])
            ->when($formId, fn($q, $id) => $q->where('custom_form_id', $id));

        if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
            return $query;
        }

        $query->where(function ($q) use ($user) {
            $q->where('created_by', $user->id);

            $q->orWhereHas('customForm', function ($formQ) use ($user) {
                $formQ->where(function ($accessQ) use ($user) {
                    $accessQ->whereJsonContains('reviewer_users', (string) $user->id)
                        ->orWhereJsonContains('approver_users', (string) $user->id)
                        ->orWhere(function ($roleQ) use ($user) {
                            foreach ($user->getRoleNames() as $roleName) {
                                $roleQ->orWhereJsonContains('reviewer_roles', $roleName)
                                    ->orWhereJsonContains('approver_roles', $roleName)
                                    ->orWhereJsonContains('allowed_roles', $roleName);
                            }
                        });
                });
            });
        });

        return $query;
    }
}
