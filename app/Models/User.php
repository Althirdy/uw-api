<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute; // Import the Attribute class

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'name', // Keep 'name' fillable, but accessor will override its display
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'phone_number',
        'assigned_brgy',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's full name.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (string $value = null, array $attributes) => trim(
                ($attributes['first_name'] ?? '') . ' ' .
                (($attributes['middle_name'] ?? null) ? (($attributes['middle_name'] ?? '') . ' ') : '') .
                ($attributes['last_name'] ?? '')
            ),
        );
    }

    /**
     * Interact with the user's first name.
     */
    protected function firstName(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => ucwords(trim($value)),
        );
    }

    /**
     * Interact with the user's middle name.
     */
    protected function middleName(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => ucwords(trim($value)),
        );
    }

    /**
     * Interact with the user's last name.
     */
    protected function lastName(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => ucwords(trim($value)),
        );
    }

    public function role()
    {
        return $this->belongsTo(Roles::class);
    }

    public function officialDetails()
    {
        return $this->hasOne(OfficialsDetails::class);
    }

    public function citizenDetails()
    {
        return $this->hasOne(CitizenDetails::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function acknowledgedReports()
    {
        return $this->hasMany(Report::class, 'acknowledge_by');
    }

    public function citizenConcerns()
    {
        return $this->hasMany(\App\Models\Citizen\Concern::class, 'citizen_id');
    }
}
