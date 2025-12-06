<?php

namespace App\Services;

use App\Models\CitizenDetails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(string $email, string $password)
    {
        $user = User::with(['role', 'officialDetails', 'citizenDetails'])
            ->where('email', $email)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $this->generateAuthData($user);
    }

    public function loginPurokLeader(string $pin)
    {
        $purokLeaders = User::with(['role', 'officialDetails'])
            ->where('role_id', 2)
            ->get();

        $user = null;
        foreach ($purokLeaders as $leader) {
            if (Hash::check($pin, $leader->password)) {
                $user = $leader;
                break;
            }
        }

        if (!$user) {
            return null;
        }

        return $this->generateAuthData($user);
    }

    public function register(array $data)
    {
        DB::beginTransaction();
        try {
            $fullName = trim($data['first_name'] . ' ' .
                ($data['middle_name'] ?? '') . ' ' .
                $data['last_name'] .
                ($data['suffix'] ? ' ' . $data['suffix'] : ''));

            $user = User::create([
                'name' => $fullName,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
                'role_id' => 3,
            ]);

            CitizenDetails::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'suffix' => $data['suffix'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'phone_number' => $data['phone_number'],
                'address' => $data['address'],
                'barangay' => $data['barangay'],
                'city' => $data['city'],
                'province' => $data['province'],
                'postal_code' => $data['postal_code'],
                'is_verified' => true,
            ]);

            DB::commit();

            return $this->generateAuthData($user);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function refreshToken(User $user)
    {
        $user->tokens()->delete();

        $access_token = $user->createToken('mobile-app', ['access-api'], Carbon::now()->addMinutes(config('sanctum.access_token_expiration')))->plainTextToken;
        $refresh_token = $user->createToken('mobile-app-refresh', ['refresh-token'], Carbon::now()->addMinutes(config('sanctum.refresh_token_expiration')))->plainTextToken;

        return [
            'token' => $access_token,
            'refreshToken' => $refresh_token,
        ];
    }

    protected function generateAuthData(User $user)
    {
        $access_token = $user->createToken('mobile-app', ['access-api'], Carbon::now()->addMinutes(config('sanctum.access_token_expiration')))->plainTextToken;
        $refresh_token = $user->createToken('mobile-app-refresh', ['refresh-token'], Carbon::now()->addMinutes(config('sanctum.refresh_token_expiration')))->plainTextToken;

        return [
            'token' => $access_token,
            'refreshToken' => $refresh_token,
            'user' => $user
        ];
    }
}
