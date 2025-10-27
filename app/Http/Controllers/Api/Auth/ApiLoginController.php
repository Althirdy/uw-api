<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiLoginController extends BaseApiController
{
    //****LOGIN METHODD */

    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $user = User::with(['role', 'officialDetails', 'citizenDetails'])
                ->where('email', $validated['email'])
                ->firstOrFail();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return $this->sendUnauthorized('Invalid credentials');
            }

            // Create access token with 'access-api' ability
            $access_token = $user->createToken('mobile-app', ['access-api'], Carbon::now()->addMinutes(config('sanctum.access_token_expiration')))->plainTextToken;
            
            // Create refresh token with 'refresh-token' ability
            $refresh_token = $user->createToken('mobile-app-refresh', ['refresh-token'], Carbon::now()->addMinutes(config('sanctum.refresh_token_expiration')))->plainTextToken;

            if ($user['role_id'] == 2 || $user['role_id'] == 1) {
                $officialDetails = $user->officialDetails;

                if (!$officialDetails) {
                    return $this->sendError('Official details not found for this user');
                }

                return $this->sendResponse([
                    'token' => $access_token,
                    'refreshToken' => $refresh_token,
                    'user' => [
                        'id' => $user->id,
                        'firstName' => $officialDetails->first_name,
                        'lastName' => $officialDetails->last_name,
                        'middleName' => $officialDetails->middle_name,
                        'suffix' => $officialDetails->suffix,
                        'email' => $user->email,
                        'role' => $user->role->name,
                        'officeAddress' => $officialDetails->office_address,
                        'phoneNumber' => $officialDetails->contact_number,
                    ]
                ]);
            } else if ($user['role_id'] == 3) {
                $citizenDetails = $user->citizenDetails;

                if (!$citizenDetails) {
                    return $this->sendError('Citizen details not found for this user');
                }

                return $this->sendResponse([
                    'token' => $access_token,
                    'refreshToken' => $refresh_token,
                    'user' => [
                        'id' => $user->id,
                        'firstName' => $citizenDetails->first_name,
                        'lastName' => $citizenDetails->last_name,
                        'middleName' => $citizenDetails->middle_name,
                        'suffix' => $citizenDetails->suffix,
                        'email' => $user->email,
                        'role' => $user->role->name,
                        'address' => $citizenDetails->address,
                        'phoneNumber' => $citizenDetails->phone_number,
                        'barangay' => $citizenDetails->barangay,
                        'city' => $citizenDetails->city,
                        'province' => $citizenDetails->province,
                        'postalCode' => $citizenDetails->postal_code,
                        'isVerified' => $citizenDetails->is_verified,
                    ]
                ]);
            }

            return $this->sendError('Invalid user role');
        } catch (\Exception $e) {
            return $this->sendError('Invalid Credentials');
        }
    }
    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Revoke the current token
            $request->user()->currentAccessToken()->delete();

            return $this->sendResponse(null, 'Logout successful');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred during logout');
        }
    }

    public function user(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user()->load(['role', 'officialDetails', 'citizenDetails']);

            if ($user['role_id'] == 2 || $user['role_id'] == 1) {
                $officialDetails = $user->officialDetails;

                if (!$officialDetails) {
                    return $this->sendError('Official details not found for this user');
                }

                return $this->sendResponse([
                    'user' => [
                        'id' => $user->id,
                        'firstName' => $officialDetails->first_name,
                        'lastName' => $officialDetails->last_name,
                        'middleName' => $officialDetails->middle_name,
                        'suffix' => $officialDetails->suffix,
                        'email' => $user->email,
                        'role' => $user->role->name,
                        'officeAddress' => $officialDetails->office_address,
                        'phoneNumber' => $officialDetails->contact_number,
                    ]
                ]);
            } else if ($user['role_id'] == 3) {
                $citizenDetails = $user->citizenDetails;

                if (!$citizenDetails) {
                    return $this->sendError('Citizen details not found for this user');
                }

                return $this->sendResponse([
                    'user' => [
                        'id' => $user->id,
                        'firstName' => $citizenDetails->first_name,
                        'lastName' => $citizenDetails->last_name,
                        'middleName' => $citizenDetails->middle_name,
                        'suffix' => $citizenDetails->suffix,
                        'email' => $user->email,
                        'role' => $user->role->name,
                        'address' => $citizenDetails->address,
                        'phoneNumber' => $citizenDetails->phone_number,
                        'barangay' => $citizenDetails->barangay,
                        'city' => $citizenDetails->city,
                        'province' => $citizenDetails->province,
                        'zipCode' => $citizenDetails->postal_code,
                        'isVerified' => $citizenDetails->is_verified,
                    ]
                ]);
            }

            return $this->sendError('Invalid user role');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while retrieving user details');
        }
    }

    public function refreshToken(Request  $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Verify the token has 'refresh-token' ability
            if (!$request->user()->tokenCan('refresh-token')) {
                return $this->sendUnauthorized('Invalid token type. Please use refresh token.');
            }

            $user = $request->user();

            // Revoke the current refresh token
            $request->user()->currentAccessToken()->delete();

            // Create new access token with 'access-api' ability
            $new_access_token = $user->createToken('mobile-app', ['access-api'], Carbon::now()->addMinutes(config('sanctum.access_token_expiration')))->plainTextToken;
            
            // Create new refresh token with 'refresh-token' ability
            $new_refresh_token = $user->createToken('mobile-app-refresh', ['refresh-token'], Carbon::now()->addMinutes(config('sanctum.refresh_token_expiration')))->plainTextToken;

            return $this->sendResponse([
                'token' => $new_access_token,
                'refreshToken' => $new_refresh_token,
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while refreshing the token');
        }
    }
}
