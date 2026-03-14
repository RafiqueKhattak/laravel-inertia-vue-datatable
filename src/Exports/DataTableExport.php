<?php

namespace QBits\InertiaDataTable\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DataTableExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Builder $query,
        protected array   $columns,
        protected array   $selectedIds = []
    ) {}

    public function query(): Builder
    {
        if (!empty($this->selectedIds)) {
            return $this->query->whereIn('id', $this->selectedIds);
        }
        return $this->query;
    }

    public function headings(): array
    {
        return collect($this->columns)->where('exportable', true)->pluck('label')->toArray();
    }

    public function map($row): array
    {
        return collect($this->columns)
            ->where('exportable', true)
            ->map(fn ($col) => data_get($row, $col['key']))
            ->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
