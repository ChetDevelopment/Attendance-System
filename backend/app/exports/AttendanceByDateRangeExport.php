<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceByDateRangeExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function array(): array
    {
        $data = [];
        
        // Headers
        $data[] = ['ID', 'Student Name', 'Session', 'Date', 'Status', 'Check-in Time'];

        // Records
        foreach ($this->reportData['records'] as $record) {
            $data[] = [
                $record['id'],
                $record['student']['name'] ?? '',
                $record['session']['name'] ?? '',
                $record['date'],
                $record['status'],
                $record['created_at'],
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
