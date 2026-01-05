<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\PurokLeaderLoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\AuthUserResource;
use App\Models\CitizenDetails;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseApiController
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // ****LOGIN METHODD */

    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $authData = $this->authService->login($validated['email'], $validated['password']);

            if (! $authData) {
                return $this->sendUnauthorized('Invalid credentials');
            }

            $user = $authData['user'];

            if (($user->role_id == 1 || $user->role_id == 2) && ! $user->officialDetails) {
                return $this->sendError('Official details not found for this user');
            }

            if ($user->role_id == 3 && ! $user->citizenDetails) {
                return $this->sendError('Citizen details not found for this user');
            }

            if (! in_array($user->role_id, [1, 2, 3])) {
                return $this->sendError('Invalid user role');
            }

            return $this->sendResponse([
                'token' => $authData['token'],
                'refreshToken' => $authData['refreshToken'],
                'user' => new AuthUserResource($user),
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Invalid Credentials');
        }
    }

    // Login for Purok Leader
    public function loginPurokLeader(PurokLeaderLoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $authData = $this->authService->loginPurokLeader($validated['pin']);

            if (! $authData) {
                return $this->sendUnauthorized('Invalid PIN');
            }

            $user = $authData['user'];

            if (! $user->officialDetails) {
                return $this->sendError('Official details not found for this user');
            }

            return $this->sendResponse([
                'token' => $authData['token'],
                'refreshToken' => $authData['refreshToken'],
                'user' => new AuthUserResource($user),
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->sendUnauthorized('Invalid PIN');
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

            if (($user->role_id == 1 || $user->role_id == 2) && ! $user->officialDetails) {
                return $this->sendError('Official details not found for this user');
            }

            if ($user->role_id == 3 && ! $user->citizenDetails) {
                return $this->sendError('Citizen details not found for this user');
            }

            if (! in_array($user->role_id, [1, 2, 3])) {
                return $this->sendError('Invalid user role');
            }

            return $this->sendResponse([
                'user' => new AuthUserResource($user),
            ]);
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while retrieving user details');
        }
    }

    public function refreshToken(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Verify the token has 'refresh-token' ability
            if (! $request->user()->tokenCan('refresh-token')) {
                return $this->sendUnauthorized('Invalid token type. Please use refresh token.');
            }

            $tokens = $this->authService->refreshToken($request->user());

            return $this->sendResponse($tokens, 'Token refreshed successfully');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while refreshing the token');
        }
    }

    /**
     * Verify registration information availability (name and email)
     */
    public function verifyRegistrationInfo(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'nullable|email',
                'first_name' => 'nullable|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'suffix' => 'nullable|string|max:10',
            ]);

            $results = [];

            // Check email availability if provided
            if (! empty($validated['email'])) {
                $existingEmail = User::where('email', $validated['email'])->first();
                $results['email'] = [
                    'available' => ! $existingEmail,
                    'message' => $existingEmail
                        ? 'This email is already registered.'
                        : 'Email is available.',
                ];
            }

            // Check name availability if first_name and last_name are provided
            if (! empty($validated['first_name']) && ! empty($validated['last_name'])) {
                $query = CitizenDetails::where('first_name', $validated['first_name'])
                    ->where('last_name', $validated['last_name']);

                if (isset($validated['middle_name'])) {
                    $query->where('middle_name', $validated['middle_name']);
                }

                if (isset($validated['suffix'])) {
                    $query->where('suffix', $validated['suffix']);
                }

                $existingCitizen = $query->first();
                $results['name'] = [
                    'available' => ! $existingCitizen,
                    'message' => $existingCitizen
                        ? 'A user with this name is already registered.'
                        : 'Name is available.',
                ];
            }

            if (empty($results)) {
                return $this->sendError('Please provide email or name information to verify', null, 400);
            }

            return $this->sendResponse($results, 'Verification completed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while verifying registration information: '.$e->getMessage());
        }
    }

    /**
     * Register a new citizen user
     */
    public function register(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $authData = $this->authService->register($request->validated());

            return $this->sendResponse([
                'token' => $authData['token'],
                'refreshToken' => $authData['refreshToken'],
                'user' => new AuthUserResource($authData['user']),
            ], 'Registration successful');
        } catch (\Exception $e) {
            return $this->sendError('Registration failed: '.$e->getMessage());
        }
    }

    public function scanFrontId(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        try {
            $flaskServiceUrl = env('FLASK_SERVICE_URL');
            $serviceApiKey = env('SERVICE_API_KEY');

            if (! $flaskServiceUrl || ! $serviceApiKey) {
                Log::error('Flask service URL or API key not configured.');

                return $this->sendError('Service configuration error.', [], 500);
            }

            $response = Http::withHeaders([
                'X-Key-Service' => $serviceApiKey,
            ])->attach(
                'image',
                file_get_contents($request->file('image')->getRealPath()),
                $request->file('image')->getClientOriginalName()
            )->post("{$flaskServiceUrl}/api/v1/ocr-front-id");

            if ($response->successful()) {
                return response()->json($response->json(), $response->status());
            } else {
                Log::error('Flask OCR service responded with an error.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->sendError('Failed to process image with OCR service.', $response->json(), $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Error calling Flask OCR service: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->sendError('An internal server error occurred.', $e->getMessage(), 500);
        }
    }
}
