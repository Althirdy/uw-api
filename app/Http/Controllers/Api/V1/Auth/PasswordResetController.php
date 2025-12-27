<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    /**
     * Reset Password
     * 
     * Reset the user's password using a verified OTP token. The token must be obtained from the OTP verification endpoint.
     * All existing user tokens will be revoked for security.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam token string required The reset token obtained from OTP verification. Example: abcdefghijklmnopqrstuvwxyz1234567890
     * @bodyParam email string required The user's email address. Example: john.doe@example.com
     * @bodyParam password string required New password (minimum 8 characters). Example: NewSecurePass123
     * @bodyParam password_confirmation string required Password confirmation. Example: NewSecurePass123
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Password has been successfully reset."
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid or expired reset token."
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Reset token expired."
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "password": ["Password must be at least 8 characters"]
     *   }
     * }
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        // Check if the token exists in password_reset_tokens
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        // Check token expiration (e.g., 60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'success' => false,
                'message' => 'Reset token expired.',
            ], 400);
        }

        // Update user password
        $user = User::where('email', $request->email)->first();

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // Delete the used token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Also clean up the OTPs
        Otp::where('email', $request->email)
            ->where('type', 'forgot_password')
            ->delete();

        // Revoke all tokens (Security)
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password has been successfully reset.',
        ]);
    }
}
