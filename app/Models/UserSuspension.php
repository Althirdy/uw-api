<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Suspension Model
 *
 * SUSPENSION POLICY:
 * - Suspended users CAN login and view content (announcements, accidents, concerns)
 * - Suspended users CANNOT perform write operations (create/delete concerns)
 * - This allows citizens to stay informed while preventing misuse
 *
 * PUNISHMENT LEVELS:
 * 1. warning_1: 3-day temporary suspension
 * 2. warning_2: 7-day temporary suspension
 * 3. suspension: Permanent ban (stores identity to prevent re-registration)
 *
 * IMPLEMENTATION:
 * - Login: No suspension check (intentionally allows suspended users)
 * - Registration: Checks isIdentityBanned() to prevent banned users from re-registering
 * - Write Operations: Controllers check isUserSuspended() before allowing actions
 */
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
        'phone_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
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
        if (! $latestSuspension) {
            return [
                [
                    'type' => 'warning_1',
                    'label' => 'Warning 1',
                    'duration' => 3,
                    'description' => '3 days suspension',
                ],
            ];
        }

        // Has warning_1 - can give warning_2 or permanent suspension
        if ($latestSuspension->punishment_type === 'warning_1') {
            return [
                [
                    'type' => 'warning_2',
                    'label' => 'Warning 2',
                    'duration' => 7,
                    'description' => '7 days suspension',
                ],
                [
                    'type' => 'suspension',
                    'label' => 'Permanent Suspension',
                    'duration' => null,
                    'description' => 'Permanent ban from the app',
                ],
            ];
        }

        // Has warning_2 - can only give permanent suspension
        if ($latestSuspension->punishment_type === 'warning_2') {
            return [
                [
                    'type' => 'suspension',
                    'label' => 'Permanent Suspension',
                    'duration' => null,
                    'description' => 'Permanent ban from the app',
                ],
            ];
        }

        return [];
    }

    /**
     * Apply a suspension to a user
     */
    public static function applySuspension(int $userId, string $punishmentType, int $adminId, ?string $reason = null): self
    {
        $durationDays = match ($punishmentType) {
            'warning_1' => 3,
            'warning_2' => 7,
            'suspension' => null,
            default => throw new \InvalidArgumentException('Invalid punishment type')
        };

        $expiresAt = $durationDays ? Carbon::now()->addDays($durationDays) : null;

        // Get user's identity information for permanent bans
        $identityData = [];
        if ($punishmentType === 'suspension') {
            $user = User::with('citizenDetails')->find($userId);
            if ($user && $user->citizenDetails) {
                $identityData = [
                    'phone_number' => $user->citizenDetails->phone_number,
                    'first_name' => $user->citizenDetails->first_name,
                    'middle_name' => $user->citizenDetails->middle_name,
                    'last_name' => $user->citizenDetails->last_name,
                    'suffix' => $user->citizenDetails->suffix,
                ];
            }
        }

        return self::create(array_merge([
            'user_id' => $userId,
            'punishment_type' => $punishmentType,
            'duration_days' => $durationDays,
            'suspended_at' => Carbon::now(),
            'expires_at' => $expiresAt,
            'status' => 'active',
            'reason' => $reason,
            'suspended_by' => $adminId,
        ], $identityData));
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

    /**
     * Check if registration identity matches a permanently banned user
     */
    public static function isIdentityBanned(
        string $phoneNumber,
        string $firstName,
        ?string $middleName,
        string $lastName,
        ?string $suffix
    ): bool {
        // Check for permanent bans (punishment_type = 'suspension') that are active
        $query = self::where('punishment_type', 'suspension')
            ->where('status', 'active');

        // Primary check: Phone number match (strongest identifier)
        $phoneMatch = (clone $query)
            ->where('phone_number', $phoneNumber)
            ->exists();

        if ($phoneMatch) {
            return true;
        }

        // Secondary check: Exact full name match
        $nameQuery = (clone $query)
            ->where('first_name', $firstName)
            ->where('last_name', $lastName);

        // Match middle name (consider null as different from any value)
        if ($middleName !== null) {
            $nameQuery->where('middle_name', $middleName);
        } else {
            $nameQuery->whereNull('middle_name');
        }

        // Match suffix (consider null as different from any value)
        if ($suffix !== null) {
            $nameQuery->where('suffix', $suffix);
        } else {
            $nameQuery->whereNull('suffix');
        }

        return $nameQuery->exists();
    }
}
