<?php

namespace Chanthoeun\FilamentCustomForms\Exports;

use Chanthoeun\FilamentCustomForms\Models\CustomForm;
use Chanthoeun\FilamentCustomForms\Models\CustomFormField;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomFormEntryExport implements FromCollection, ShouldAutoSize, WithStyles
{
    protected Collection $records;

    protected ?string $formId;

    protected Collection $fields;

    protected ?string $locale;

    public function __construct(Collection $records, ?string $formId = null, ?string $locale = null)
    {
        $this->records = $records;
        $this->formId = $formId;
        $this->locale = $locale;
        $this->fields = CustomFormField::query()
            ->when($this->formId, fn ($query) => $query->where('custom_form_id', $this->formId))
            ->orderBy('sort')
            ->get();
    }

    public function collection(): Enumerable
    {
        $headings = $this->fields->map(function ($field) {
            $label = $field->label ?: $field->name;
            if ($this->locale && method_exists($field, 'getTranslation')) {
                $translated = $field->getTranslation('label', $this->locale, false) ?: $field->getTranslation('label', config('app.fallback_locale', 'en'), false);
                if ($translated) {
                    $label = $translated;
                }
            }

            return $label;
        })->toArray();
        $headings[] = __('filament-custom-forms::fcf.general.created_at', [], $this->locale ?? app()->getLocale());

        $locale = $this->locale ?? app()->getLocale();
        $formName = __('filament-custom-forms::fcf.entry.plural', [], $locale);
        if ($this->formId) {
            $customForm = CustomForm::find($this->formId);
            if ($customForm) {
                $name = __("filament-custom-forms::fcf.form.names.{$customForm->slug}", [], $locale);
                if ($name === "filament-custom-forms::fcf.form.names.{$customForm->slug}") {
                    if ($this->locale && method_exists($customForm, 'getTranslation')) {
                        $translatedName = $customForm->getTranslation('name', $this->locale, false) ?: $customForm->getTranslation('name', config('app.fallback_locale', 'en'), false);
                        $name = $translatedName ?: $customForm->name;
                    } else {
                        $name = $customForm->name;
                    }
                }
                $formName = $name;
            }
        }

        $mapped = $this->records->map(function ($record) {
            $data = null;
            if ($this->locale && method_exists($record, 'getTranslation')) {
                $data = $record->getTranslation('data', $this->locale, false) ?: $record->getTranslation('data', config('app.fallback_locale', 'en'), false);
            }
            $data = $data ?: ($record->data ?? []);

            $row = [];
            foreach ($this->fields as $field) {
                $value = $data[$field->name] ?? '';
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $row[] = $value;
            }
            $row[] = $record->created_at ? $record->created_at->toDateTimeString() : '';

            return $row;
        });

        $mapped->prepend($headings);

        $titleRow = array_fill(0, count($headings), '');
        $titleRow[0] = $formName;
        $mapped->prepend($titleRow);

        return $mapped;
    }

    public function headings(): array
    {
        $headings = $this->fields->map(function ($field) {
            $label = $field->label ?: $field->name;
            if ($this->locale && method_exists($field, 'getTranslation')) {
                $translated = $field->getTranslation('label', $this->locale, false) ?: $field->getTranslation('label', config('app.fallback_locale', 'en'), false);
                if ($translated) {
                    $label = $translated;
                }
            }

            return $label;
        })->toArray();

        $headings[] = __('filament-custom-forms::fcf.general.created_at', [], $this->locale ?? app()->getLocale());

        return $headings;
    }

    public function map($record): array
    {
        $data = null;
        if ($this->locale && method_exists($record, 'getTranslation')) {
            $data = $record->getTranslation('data', $this->locale, false) ?: $record->getTranslation('data', config('app.fallback_locale', 'en'), false);
        }
        $data = $data ?: ($record->data ?? []);

        $row = [];

        foreach ($this->fields as $field) {
            $value = $data[$field->name] ?? '';

            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $row[] = $value;
        }

        $row[] = $record->created_at ? $record->created_at->toDateTimeString() : '';

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->mergeCells('A1:'.$highestColumn.'1');

        $dataRange = 'A2:'.$highestColumn.$highestRow;

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
            2 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
            ],
            $dataRange => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ],
        ];
    }
}
