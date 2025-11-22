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
     * Reset password using verified OTP
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        // Check if the token exists in password_reset_tokens
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
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
