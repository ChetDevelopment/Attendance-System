<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class StudentAttendanceExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected $reportData;
    protected $month;
    protected $year;

    public function __construct(array $reportData, $month = null, $year = null)
    {
        $this->reportData = $reportData;
        $this->month = $month;
        $this->year = $year;
    }

    public function array(): array
    {
        $data = [];
        
        // Student Info Section
        $data[] = ['Student Information'];
        $data[] = ['Name:', $this->reportData['student']['name'] ?? ''];
        $data[] = ['Class:', $this->reportData['student']['class'] ?? ''];
        $data[] = [''];

        // Summary Section
        $data[] = ['Attendance Summary'];
        $data[] = ['Total Sessions:', $this->reportData['summary']['total_sessions'] ?? 0];
        $data[] = ['Present:', $this->reportData['summary']['present'] ?? 0];
        $data[] = ['Absent:', $this->reportData['summary']['absent'] ?? 0];
        $data[] = ['Late:', $this->reportData['summary']['late'] ?? 0];
        $data[] = ['Attendance Percentage:', ($this->reportData['summary']['attendance_percentage'] ?? 0) . '%'];
        $data[] = [''];

        // Detailed Breakdown
        if ($this->month && isset($this->reportData['daily_breakdown'])) {
            // Monthly breakdown
            $data[] = ['Daily Breakdown'];
            $data[] = ['Date', 'Session', 'Status'];
            
            foreach ($this->reportData['daily_breakdown'] as $day) {
                foreach ($day['sessions'] as $session) {
                    $data[] = [
                        $day['date'],
                        $session['session_name'] ?? '',
                        $session['status'] ?? '',
                    ];
                }
            }
        } elseif (isset($this->reportData['monthly_breakdown'])) {
            // Yearly breakdown
            $data[] = ['Monthly Breakdown'];
            $data[] = ['Month', 'Total', 'Present', 'Absent', 'Late', 'Percentage'];
            
            foreach ($this->reportData['monthly_breakdown'] as $month) {
                $data[] = [
                    $month['month_name'],
                    $month['total_sessions'],
                    $month['present'],
                    $month['absent'],
                    $month['late'],
                    $month['attendance_percentage'] . '%',
                ];
            }
        }

        // Generated timestamp
        $data[] = [''];
        $data[] = ['Generated:', $this->reportData['generated_at'] ?? now()->toIso8601String()];

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        if ($this->month) {
            return Carbon::createFromDate($this->year, $this->month)->format('F Y');
        }
        return (string) $this->year;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            7 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
