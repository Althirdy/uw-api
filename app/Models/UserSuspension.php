<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSuspension extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'punishment_type',
        'duration_days',
        'suspended_at',
        'expires_at',
        'status',
        'reason',
        'suspended_by',
    ];

    protected $casts = [
        'suspended_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that was suspended
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin who suspended the user
     */
    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /**
     * Check if suspension is currently active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // Permanent suspension
        if ($this->punishment_type === 'suspension') {
            return true;
        }

        // Check if temporary suspension has expired
        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->update(['status' => 'expired']);
            return false;
        }

        return true;
    }

    /**
     * Get the next available punishment type based on user's history
     */
    public static function getAvailablePunishments(int $userId): array
    {
        // Check if user has an active permanent suspension
        $activePermanentSuspension = self::where('user_id', $userId)
            ->where('punishment_type', 'suspension')
            ->where('status', 'active')
            ->exists();

        // If already permanently suspended, no more punishments available
        if ($activePermanentSuspension) {
            return [];
        }

        $latestSuspension = self::where('user_id', $userId)
            ->whereIn('punishment_type', ['warning_1', 'warning_2'])
            ->orderBy('created_at', 'desc')
            ->first();

        // No previous warnings - can only give warning_1
        if (!$latestSuspension) {
            return [
                [
                    'type' => 'warning_1',
                    'label' => 'Warning 1',
                    'duration' => 3,
                    'description' => '3 days suspension'
                ]
            ];
        }

        // Has warning_1 - can give warning_2 or permanent suspension
        if ($latestSuspension->punishment_type === 'warning_1') {
            return [
                [
                    'type' => 'warning_2',
                    'label' => 'Warning 2',
                    'duration' => 7,
                    'description' => '7 days suspension'
                ],
                [
                    'type' => 'suspension',
                    'label' => 'Permanent Suspension',
                    'duration' => null,
                    'description' => 'Permanent ban from the app'
                ]
            ];
        }

        // Has warning_2 - can only give permanent suspension
        if ($latestSuspension->punishment_type === 'warning_2') {
            return [
                [
                    'type' => 'suspension',
                    'label' => 'Permanent Suspension',
                    'duration' => null,
                    'description' => 'Permanent ban from the app'
                ]
            ];
        }

        return [];
    }

    /**
     * Apply a suspension to a user
     */
    public static function applySuspension(int $userId, string $punishmentType, int $adminId, ?string $reason = null): self
    {
        $durationDays = match($punishmentType) {
            'warning_1' => 3,
            'warning_2' => 7,
            'suspension' => null,
            default => throw new \InvalidArgumentException('Invalid punishment type')
        };

        $expiresAt = $durationDays ? Carbon::now()->addDays($durationDays) : null;

        return self::create([
            'user_id' => $userId,
            'punishment_type' => $punishmentType,
            'duration_days' => $durationDays,
            'suspended_at' => Carbon::now(),
            'expires_at' => $expiresAt,
            'status' => 'active',
            'reason' => $reason,
            'suspended_by' => $adminId,
        ]);
    }

    /**
     * Check if a user is currently suspended
     */
    public static function isUserSuspended(int $userId): bool
    {
        $activeSuspension = self::where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        return $activeSuspension && $activeSuspension->isActive();
    }

    /**
     * Get active suspension for a user
     */
    public static function getActiveSuspension(int $userId): ?self
    {
        $suspension = self::where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($suspension && $suspension->isActive()) {
            return $suspension;
        }

        return null;
    }
}
