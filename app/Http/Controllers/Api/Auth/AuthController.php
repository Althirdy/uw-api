<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\CitizenDetails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
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

    // Login for Purok Leader
    public function loginPurokLeader(Request $request): \Illuminate\Http\JsonResponse{
        return $this->sendError('Not implemented yet');
    }
    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Revoke the current token
            $request->user()->currentAccessToken()->delete();
            $request->user()->tokens()->delete();

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

            // Delete ALL existing tokens (both access and refresh tokens)
            $user->tokens()->delete();

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

    /**
     * Verify if citizen name is already registered
     */
    public function verifyNameAvailability(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'suffix' => 'nullable|string|max:10',
            ]);

            // Check if a citizen with the same name already exists
            $query = CitizenDetails::where('first_name', $validated['first_name'])
                ->where('last_name', $validated['last_name']);

            $existingCitizen = $query->first();

            if ($existingCitizen) {
                return $this->sendResponse([
                    'available' => false,
                    'message' => 'A user with this name is already registered.'
                ], 'Name verification completed');
            }

            return $this->sendResponse([
                'available' => true,
                'message' => 'Name is available for registration.'
            ], 'Name verification completed');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while verifying name availability: ' . $e->getMessage());
        }
    }

    /**
     * Register a new citizen user
     */
    public function register(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // Concatenate full name
            $fullName = trim($validated['first_name'] . ' ' . 
                           ($validated['middle_name'] ?? '') . ' ' . 
                           $validated['last_name'] . 
                           ($validated['suffix'] ? ' ' . $validated['suffix'] : ''));

            // Create user record
            $user = User::create([
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(), // Set verification timestamp
                'role_id' => 3, // Default citizen role
            ]);

            // Create citizen details record
            $citizenDetails = CitizenDetails::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'date_of_birth' => $validated['date_of_birth'],
                'phone_number' => $validated['phone_number'],
                'address' => $validated['address'],
                'barangay' => $validated['barangay'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'postal_code' => $validated['postal_code'],
                'is_verified' => true, // Mark as verified after OTP verification
            ]);

            DB::commit();

            // Generate tokens
            $access_token = $user->createToken('mobile-app', ['access-api'], Carbon::now()->addMinutes(config('sanctum.access_token_expiration')))->plainTextToken;
            $refresh_token = $user->createToken('mobile-app-refresh', ['refresh-token'], Carbon::now()->addMinutes(config('sanctum.refresh_token_expiration')))->plainTextToken;

            // Return response with user data
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
                    'role' => 'Citizen',
                    'address' => $citizenDetails->address,
                    'phoneNumber' => $citizenDetails->phone_number,
                    'barangay' => $citizenDetails->barangay,
                    'city' => $citizenDetails->city,
                    'province' => $citizenDetails->province,
                    'postalCode' => $citizenDetails->postal_code,
                    'isVerified' => $citizenDetails->is_verified,
                ]
            ], 'Registration successful');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Registration failed: ' . $e->getMessage());
        }
    }

    
}
