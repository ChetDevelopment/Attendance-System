<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiometricScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'session_id',
        'scan_type',
        'scan_data',
        'status',
        'failure_reason',
        'device_id',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scan type constants
     */
    const SCAN_TYPE_CARD = 'card';
    const SCAN_TYPE_FINGERPRINT = 'fingerprint';

    /**
     * Status constants
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_DUPLICATE = 'duplicate';
    const STATUS_INVALID = 'invalid';

    /**
     * Get the student associated with this scan.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the session associated with this scan.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Check if this is a card scan.
     */
    public function isCardScan(): bool
    {
        return $this->scan_type === self::SCAN_TYPE_CARD;
    }

    /**
     * Check if this is a fingerprint scan.
     */
    public function isFingerprintScan(): bool
    {
        return $this->scan_type === self::SCAN_TYPE_FINGERPRINT;
    }

    /**
     * Check if the scan was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check for recent duplicate scans (cooldown check).
     */
    public static function hasRecentDuplicate(int $studentId, int $sessionId, string $scanType, int $cooldownSeconds = 3): bool
    {
        $recentScan = self::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('scan_type', $scanType)
            ->where('status', self::STATUS_SUCCESS)
            ->where('created_at', '>=', now()->subSeconds($cooldownSeconds))
            ->first();

        return $recentScan !== null;
    }

    /**
     * Get today's scan count for a student.
     */
    public static function getTodayScanCount(int $studentId): int
    {
        return self::where('student_id', $studentId)
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Get recent scans for a student in a session.
     */
    public static function getRecentScans(int $studentId, int $sessionId, int $minutes = 30)
    {
        return self::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderByDesc('created_at')
            ->get();
    }
}
