<?php

namespace LaraSpace\FilamentCustomForms\Filament\Resources;

use LaraSpace\FilamentCustomForms\Filament\Resources\Pages;
use LaraSpace\FilamentCustomForms\Filament\Resources\Schemas\CustomFormEntryForm;
use LaraSpace\FilamentCustomForms\Filament\Resources\Tables\CustomFormEntriesTable;
use LaraSpace\FilamentCustomForms\Models\CustomFormEntry;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use LaraSpace\FilamentCustomForms\Models\CustomForm;

class CustomFormEntryResource extends Resource
{
    protected static ?string $model = CustomFormEntry::class;

    protected static ?string $slug = 'custom-form-entries';

    public static function shouldRegisterNavigation(): bool
    {
        if (!(tenant()?->checkFeature('custom_form') ?? false)) {
            return false;
        }
        return parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        return tenant()?->checkFeature('custom_form') ?? false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->guest()) {
            return $query;
        }

        // Filter based on allowed_roles of the related CustomForm
        $user = auth()->user();

        // Standardize on snake_case super_admin, but keep Title Case check for safety during transition
        if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
            return $query;
        }

        // Fetch IDs of forms the user is allowed to see
        $allowedFormIds = CustomForm::where('is_active', true)->get()->filter(function ($form) use ($user) {
            // 1. Allowed Roles
            if (!empty($form->allowed_roles) && $user->hasAnyRole($form->allowed_roles)) {
                return true;
            }
            // 2. Reviewer
            if (!empty($form->reviewer_roles) && $user->hasAnyRole($form->reviewer_roles)) {
                return true;
            }
            if (!empty($form->reviewer_users) && in_array($user->id, $form->reviewer_users)) {
                return true;
            }

            // 3. Approver
            if (!empty($form->approver_roles) && $user->hasAnyRole($form->approver_roles)) {
                return true;
            }
            if (!empty($form->approver_users) && in_array($user->id, $form->approver_users)) {
                return true;
            }

            // 4. Fallback (if no roles/users specified, everyone can see it)
            if (empty($form->allowed_roles) && empty($form->reviewer_roles) && empty($form->approver_roles)) {
                return true;
            }

            return false;
        })->pluck('id');

        return $query->whereIn('custom_form_id', $allowedFormIds);
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function getModelLabel(): string
    {
        $id = request()->input('tableFilters.custom_form_id.value');
        if ($id) {
            // Using find() here is technically 1 query, but if called multiple times it's better to cache
            $form = static::getFormFromCache($id);
            if ($form)
                return $form->name . ' Entry';
        }
        return __('custom_form_entry.single');
    }

    public static function getPluralModelLabel(): string
    {
        $id = request()->input('tableFilters.custom_form_id.value');
        if ($id) {
            $form = static::getFormFromCache($id);
            if ($form)
                return $form->name . ' Entries';
        }
        return __('custom_form_entry.plural');
    }

    protected static array $formCache = [];

    protected static function getFormFromCache(string $id): ?CustomForm
    {
        if (!isset(static::$formCache[$id])) {
            static::$formCache[$id] = CustomForm::find($id);
        }
        return static::$formCache[$id];
    }

    public static function getNavigationItems(): array
    {
        $items = [];
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user || !(tenant()?->checkFeature('custom_form') ?? false)) {
            return [];
        }

        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('custom_forms')) {
                return [];
            }

            // Pre-fetch all active forms at once to avoid N+1 in the loop
            $forms = CustomForm::where('is_active', true)->whereNotNull('name')->get();
            $activeFormId = data_get(request()->query('tableFilters'), 'custom_form_id.value');

            foreach ($forms as $form) {
                // Populate cache for label methods to use if they haven't run yet
                static::$formCache[$form->id] = $form;

                $shouldShow = false;

                // 1. Super Admin always sees all forms
                if ($user->hasRole('super_admin') || $user->hasRole('Super Admin')) {
                    $shouldShow = true;
                }
                // 2. Check Allowed Roles/Users
                elseif (
                    (!empty($form->allowed_roles) && $user->hasAnyRole($form->allowed_roles)) ||
                    (in_array($user->id, $form->allowed_users ?? []))
                ) {
                    $shouldShow = true;
                }
                // 3. Check Reviewer/Approver
                elseif (
                    (!empty($form->reviewer_roles) && $user->hasAnyRole($form->reviewer_roles)) ||
                    (!empty($form->approver_roles) && $user->hasAnyRole($form->approver_roles)) ||
                    (in_array($user->id, $form->reviewer_users ?? [])) ||
                    (in_array($user->id, $form->approver_users ?? []))
                ) {
                    $shouldShow = true;
                }
                // 4. Fallback (everyone can see if no restrictions defined)
                elseif (empty($form->allowed_roles) && empty($form->reviewer_roles) && empty($form->approver_roles)) {
                    $shouldShow = true;
                }

                if ($shouldShow) {
                    $items[] = NavigationItem::make($form->name)
                        ->group(__('custom_form.operations_group'))
                        ->icon('heroicon-o-document-text')
                        ->isActiveWhen(fn() => $activeFormId == $form->id)
                        ->url(static::getUrl('index', [
                            'tableFilters' => [
                                'custom_form_id' => [
                                    'value' => $form->id,
                                ],
                            ],
                        ], panel: 'app'));
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('CustomFormEntryResource Navigation Error: ' . $e->getMessage());
        }

        return $items;
    }

    public static function form(Schema $schema): Schema
    {
        return CustomFormEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomFormEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomFormEntries::route('/'),
            'create' => Pages\CreateCustomFormEntry::route('/create'),
            'edit' => Pages\EditCustomFormEntry::route('/{record}/edit'),
        ];
    }
}
