<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class ClassAttendanceExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
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
        
        // Class Info Section
        $data[] = ['Class Information'];
        $data[] = ['Class Name:', $this->reportData['class']['name'] ?? ''];
        $data[] = ['Total Students:', $this->reportData['class']['total_students'] ?? 0];
        if (isset($this->reportData['class']['room'])) {
            $data[] = ['Room:', $this->reportData['class']['room']];
        }
        $data[] = [''];

        // Period
        if ($this->month) {
            $data[] = ['Period:', Carbon::createFromDate($this->year, $this->month)->format('F Y')];
        } else {
            $data[] = ['Period:', 'Year ' . $this->year];
        }
        $data[] = [''];

        // Summary Section
        $data[] = ['Attendance Summary'];
        $data[] = ['Total Records:', $this->reportData['summary']['total_attendance_records'] ?? 0];
        $data[] = ['Present:', $this->reportData['summary']['present'] ?? 0];
        $data[] = ['Absent:', $this->reportData['summary']['absent'] ?? 0];
        $data[] = ['Late:', $this->reportData['summary']['late'] ?? 0];
        $data[] = ['Attendance Percentage:', ($this->reportData['summary']['attendance_percentage'] ?? 0) . '%'];
        $data[] = [''];

        // Session Summary (for monthly)
        if ($this->month && isset($this->reportData['session_summary'])) {
            $data[] = ['Session Breakdown'];
            $data[] = ['Session', 'Total', 'Present', 'Absent', 'Late'];
            
            foreach ($this->reportData['session_summary'] as $session) {
                $data[] = [
                    $session['session_name'],
                    $session['total'],
                    $session['present'],
                    $session['absent'],
                    $session['late'],
                ];
            }
        }

        // Daily Breakdown
        if (isset($this->reportData['daily_breakdown'])) {
            $data[] = [''];
            $data[] = ['Daily Breakdown'];
            $data[] = ['Date', 'Total', 'Present', 'Absent', 'Late', 'Percentage'];
            
            foreach ($this->reportData['daily_breakdown'] as $day) {
                $data[] = [
                    $day['date'],
                    $day['total'],
                    $day['present'],
                    $day['absent'],
                    $day['late'],
                    $day['attendance_percentage'] . '%',
                ];
            }
        }

        // Recent Sessions (for general class report)
        if (isset($this->reportData['recent_sessions'])) {
            $data[] = [''];
            $data[] = ['Recent Sessions'];
            $data[] = ['Session', 'Total', 'Present', 'Absent'];
            
            foreach ($this->reportData['recent_sessions'] as $session) {
                $data[] = [
                    $session['session_id'],
                    $session['total'],
                    $session['present'],
                    $session['absent'],
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
            8 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
