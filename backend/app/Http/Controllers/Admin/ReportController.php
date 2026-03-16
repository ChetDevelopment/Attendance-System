<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentAttendanceExport;
use App\Exports\ClassAttendanceExport;
use App\Exports\AttendanceByDateRangeExport;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * GET /api/reports/student/{id}
     * 
     * Get individual student attendance report
     */
    public function getStudentReport($studentId)
    {
        try {
            $report = $this->reportService->getStudentReport($studentId);
            
            return response()->json([
                'success' => true,
                'data' => $report,
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'student report');
        }
    }

    /**
     * GET /api/reports/student/{id}/month/{month}/year/{year}
     * 
     * Get student attendance report by month
     */
    public function getStudentReportByMonth($studentId, $month, $year)
    {
        $validator = Validator::make([
            'month' => $month,
            'year' => $year,
        ], [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $report = $this->reportService->getStudentReportByMonth($studentId, $month, $year);
            
            return response()->json([
                'success' => true,
                'data' => $report,
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'student monthly report');
        }
    }

    /**
     * GET /api/reports/student/{id}/year/{year}
     * 
     * Get student attendance report by year
     */
    public function getStudentReportByYear($studentId, $year)
    {
        $validator = Validator::make([
            'year' => $year,
        ], [
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $report = $this->reportService->getStudentReportByYear($studentId, $year);
            
            return response()->json([
                'success' => true,
                'data' => $report,
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'student yearly report');
        }
    }

    /**
     * GET /api/reports/class/{id}
     * 
     * Get class attendance report
     */
    public function getClassReport($classId)
    {
        try {
            $report = $this->reportService->getClassReport($classId);
            
            return response()->json([
                'success' => true,
                'data' => $report,
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'class report');
        }
    }

    /**
     * GET /api/reports/class/{id}/month/{month}/year/{year}
     * 
     * Get class attendance monthly summary
     */
    public function getClassMonthlySummary($classId, $month, $year)
    {
        $validator = Validator::make([
            'month' => $month,
            'year' => $year,
        ], [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $report = $this->reportService->getClassMonthlySummary($classId, $month, $year);
            
            return response()->json([
                'success' => true,
                'data' => $report,
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'class monthly summary');
        }
    }

    /**
     * GET /api/reports/class/{id}/range
     * 
     * Get class attendance by date range
     */
    public function getClassReportByDateRange(Request $request, $classId)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $report = $this->reportService->getClassReportByDateRange(
                $classId,
                $request->input('start_date'),
                $request->input('end_date')
            );
            
            return response()->json([
                'success' => true,
                'data' => $report,
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'class date range report');
        }
    }

    /**
     * GET /api/reports/export/student/{id}
     * 
     * Export student attendance to Excel
     */
    public function exportStudentReport(Request $request, $studentId)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month');

        try {
            $student = Student::findOrFail($studentId);
            
            $fileName = 'student_attendance_' . $student->username . '_' . $year;
            if ($month) {
                $fileName .= '_' . $month;
            }
            $fileName .= '.xlsx';

            if ($month) {
                $report = $this->reportService->getStudentReportByMonth($studentId, $month, $year);
                return Excel::download(new StudentAttendanceExport($report, $month), $fileName);
            } else {
                $report = $this->reportService->getStudentReportByYear($studentId, $year);
                return Excel::download(new StudentAttendanceExport($report, null, $year), $fileName);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'student report export');
        }
    }

    /**
     * GET /api/reports/export/class/{id}
     * 
     * Export class attendance to Excel
     */
    public function exportClassReport(Request $request, $classId)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month');

        try {
            $class = StudentClass::findOrFail($classId);
            
            $fileName = 'class_attendance_' . str_replace(' ', '_', $class->class_name) . '_' . $year;
            if ($month) {
                $fileName .= '_' . $month;
            }
            $fileName .= '.xlsx';

            if ($month) {
                $report = $this->reportService->getClassMonthlySummary($classId, $month, $year);
                return Excel::download(new ClassAttendanceExport($report, $month), $fileName);
            } else {
                $report = $this->reportService->getClassReport($classId);
                return Excel::download(new ClassAttendanceExport($report, null, $year), $fileName);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'class report export');
        }
    }

    /**
     * GET /api/reports/export/range
     * 
     * Export attendance by date range
     */
    public function exportByDateRange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:classes,id',
            'student_id' => 'nullable|exists:students,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $classId = $request->input('class_id');
            $studentId = $request->input('student_id');

            $report = $this->reportService->getAttendanceByDateRange($startDate, $endDate, $classId, $studentId);

            $fileName = 'attendance_export_' . $startDate . '_to_' . $endDate . '.xlsx';

            return Excel::download(new AttendanceByDateRangeExport($report), $fileName);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'date range export');
        }
    }

    /**
     * GET /api/reports/attendance
     * 
     * Get attendance records with filters
     */
    public function getAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'class_id' => 'nullable|exists:classes,id',
            'student_id' => 'nullable|exists:students,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $classId = $request->input('class_id');
            $studentId = $request->input('student_id');

            $report = $this->reportService->getAttendanceByDateRange($startDate, $endDate, $classId, $studentId);
            
            return response()->json([
                'success' => true,
                'data' => $report,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'attendance fetch');
        }
    }

    /**
     * POST /api/reports/clear-cache
     * 
     * Clear report cache
     */
    public function clearCache(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:student,class',
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->reportService->clearCache(
                $request->input('type'),
                $request->input('id')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->logAndReturnServerError($e, 'report cache clear');
        }
    }

    private function logAndReturnServerError(\Throwable $exception, string $context)
    {
        Log::error("ReportController failure: {$context}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'An internal server error occurred.',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
