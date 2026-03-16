<?php

namespace App\Http\Controllers;

use App\Services\AttendancePredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    protected $predictionService;

    public function __construct(AttendancePredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    /**
     * Get at-risk students
     * GET /api/predictions/at-risk
     */
    public function getAtRiskStudents(Request $request): JsonResponse
    {
        try {
            $threshold = $request->query('threshold', 30);
            
            $result = $this->predictionService->getAtRiskStudents((int)$threshold);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve at-risk students',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get individual student prediction
     * GET /api/predictions/student/{id}
     */
    public function getStudentPrediction($studentId): JsonResponse
    {
        try {
            $result = $this->predictionService->predictStudentAbsence($studentId);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve student prediction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get overall system insights
     * GET /api/predictions/insights
     */
    public function getInsights(): JsonResponse
    {
        try {
            $result = $this->predictionService->generateInsights();
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate insights',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get weekly prediction
     * GET /api/predictions/weekly
     */
    public function getWeeklyPrediction(Request $request): JsonResponse
    {
        try {
            $weekOffset = (int)$request->query('week_offset', 0);
            
            // Validate week offset (0-4 weeks ahead)
            if ($weekOffset < 0 || $weekOffset > 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Week offset must be between 0 and 4',
                ], 400);
            }
            
            $result = $this->predictionService->getWeeklyPrediction($weekOffset);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate weekly prediction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get historical data analysis
     * GET /api/predictions/historical
     */
    public function getHistoricalData(Request $request): JsonResponse
    {
        try {
            $days = (int)$request->query('days', 30);
            
            // Validate days
            if ($days < 7 || $days > 90) {
                return response()->json([
                    'success' => false,
                    'message' => 'Days must be between 7 and 90',
                ], 400);
            }
            
            $result = $this->predictionService->analyzeHistoricalData($days);
            
            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze historical data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear prediction cache
     * POST /api/predictions/clear-cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->predictionService->clearCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Prediction cache cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
