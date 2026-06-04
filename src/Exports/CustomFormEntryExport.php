<?php

namespace Chanthoeun\FilamentCustomForms\Exports;

use Chanthoeun\FilamentCustomForms\Models\CustomForm;
use Chanthoeun\FilamentCustomForms\Models\CustomFormField;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CustomFormEntryExport implements FromCollection, WithStyles, ShouldAutoSize
{
    protected Collection $records;
    protected ?string $formId;
    protected Collection $fields;

    public function __construct(Collection $records, ?string $formId = null)
    {
        $this->records = $records;
        $this->formId = $formId;
        $this->fields = CustomFormField::query()
            ->when($this->formId, fn($query) => $query->where('custom_form_id', $this->formId))
            ->orderBy('sort')
            ->get();
    }

    public function collection(): \Illuminate\Support\Enumerable
    {
        $headings = $this->fields->map(function ($field) {
            return $field->label ?: $field->name;
        })->toArray();
        $headings[] = __('filament-custom-forms::fcf.general.created_at');

        $formName = __('filament-custom-forms::fcf.entry.plural');
        if ($this->formId) {
            $customForm = CustomForm::find($this->formId);
            if ($customForm) {
                $formName = $customForm->name;
            }
        }

        $mapped = $this->records->map(function ($record) {
            $data = $record->data ?? [];
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
            return $field->label ?: $field->name;
        })->toArray();

        $headings[] = __('filament-custom-forms::fcf.general.created_at');

        return $headings;
    }

    public function map($record): array
    {
        $data = $record->data ?? [];
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
        
        $sheet->mergeCells('A1:' . $highestColumn . '1');
        
        $dataRange = 'A2:' . $highestColumn . $highestRow;

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
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
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
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
