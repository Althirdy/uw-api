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

    /**
     * Login
     * 
     * Authenticate a user with email and password. Returns access token, refresh token, and user details.
     * Supports Operators (role_id 1, 2) and Citizens (role_id 3).
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     * @bodyParam password string required The user's password. Example: password123
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "token": "1|abcdefghijklmnopqrstuvwxyz",
     *     "refreshToken": "2|zyxwvutsrqponmlkjihgfedcba",
     *     "user": {
     *       "id": 1,
     *       "email": "john.doe@example.com",
     *       "role": {
     *         "id": 3,
     *         "name": "Citizen"
     *       }
     *     }
     *   },
     *   "message": "Success"
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid credentials"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "email": ["Email address is required."]
     *   }
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "Invalid Credentials"
     * }
     */
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

    /**
     * Login for Purok Leader
     * 
     * Authenticate a Purok Leader using their PIN code. Returns access token, refresh token, and user details.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam pin string required The Purok Leader's PIN code. Example: 123456
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "token": "1|abcdefghijklmnopqrstuvwxyz",
     *     "refreshToken": "2|zyxwvutsrqponmlkjihgfedcba",
     *     "user": {
     *       "id": 2,
     *       "email": "purok.leader@example.com",
     *       "role": {
     *         "id": 2,
     *         "name": "Purok Leader"
     *       }
     *     }
     *   },
     *   "message": "Login successful"
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid PIN"
     * }
     */
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
     * Logout
     * 
     * Revoke the current user's access token and all refresh tokens, effectively logging them out.
     *
     * @group Auth
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "data": null,
     *   "message": "Logout successful"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred during logout"
     * }
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

    /**
     * Get Authenticated User
     * 
     * Retrieve the authenticated user's details including role, and either officialDetails or citizenDetails.
     *
     * @group Auth
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "email": "john.doe@example.com",
     *       "role": {
     *         "id": 3,
     *         "name": "Citizen"
     *       },
     *       "citizenDetails": {
     *         "first_name": "John",
     *         "last_name": "Doe"
     *       }
     *     }
     *   },
     *   "message": "Success"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while retrieving user details"
     * }
     */
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

    /**
     * Refresh Access Token
     * 
     * Generate a new access token using a valid refresh token. The refresh token must have 'refresh-token' ability.
     *
     * @group Auth
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "token": "3|newAccessTokenString",
     *     "refreshToken": "4|newRefreshTokenString"
     *   },
     *   "message": "Token refreshed successfully"
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid token type. Please use refresh token."
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while refreshing the token"
     * }
     */
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
     * Verify Registration Information
     * 
     * Check if an email or name combination is already registered in the system. Used during registration to prevent duplicates.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam email string optional The email to check for availability. Example: john.doe@example.com
     * @bodyParam first_name string optional The first name to check. Example: John
     * @bodyParam middle_name string optional The middle name to check. Example: Michael
     * @bodyParam last_name string optional The last name to check. Example: Doe
     * @bodyParam suffix string optional The name suffix to check. Example: Jr.
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "email": {
     *       "available": true,
     *       "message": "Email is available."
     *     },
     *     "name": {
     *       "available": false,
     *       "message": "A user with this name is already registered."
     *     }
     *   },
     *   "message": "Verification completed"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Please provide email or name information to verify"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {}
     * }
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
     * Register New Citizen
     * 
     * Create a new citizen user account with personal details and address information.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     * @bodyParam password string required Password (minimum 8 characters). Example: SecurePass123
     * @bodyParam password_confirmation string required Password confirmation. Example: SecurePass123
     * @bodyParam first_name string required First name. Example: John
     * @bodyParam middle_name string optional Middle name. Example: Michael
     * @bodyParam last_name string required Last name. Example: Doe
     * @bodyParam suffix string optional Name suffix. Example: Jr.
     * @bodyParam date_of_birth date required Date of birth (must be in the past). Example: 1990-05-15
     * @bodyParam phone_number string required Contact phone number. Example: 09171234567
     * @bodyParam address string required Street address. Example: 123 Main St
     * @bodyParam barangay string required Barangay name. Example: Barangay 1
     * @bodyParam city string required City name. Example: Baguio City
     * @bodyParam province string required Province name. Example: Benguet
     * @bodyParam postal_code string required Postal code. Example: 2600
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "token": "1|abcdefghijklmnopqrstuvwxyz",
     *     "refreshToken": "2|zyxwvutsrqponmlkjihgfedcba",
     *     "user": {
     *       "id": 1,
     *       "email": "john.doe@example.com",
     *       "role": {
     *         "id": 3,
     *         "name": "Citizen"
     *       }
     *     }
     *   },
     *   "message": "Registration successful"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "email": ["This email address is already registered"]
     *   }
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "Registration failed: error details"
     * }
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

    /**
     * Scan National ID Front
     * 
     * Extract text data from the front of a Philippine National ID using OCR service.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam image file required National ID front image (jpeg, png, jpg, gif, max 2MB). Example: id_front.jpg
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "last_name": "Doe",
     *     "first_name": "John",
     *     "middle_name": "Michael",
     *     "date_of_birth": "1990-05-15"
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Validation Error.",
     *   "errors": {}
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "Service configuration error."
     * }
     */
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
