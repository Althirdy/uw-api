<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * Send a simple text email
     */
    public function sendRawEmail(string $to, string $subject, string $message): bool
    {
        try {
            Log::info('Sending raw email', ['to' => $to, 'subject' => $subject]);

            Mail::raw($message, function ($mail) use ($to, $subject) {
                $mail->to($to)
                    ->subject($subject);
            });

            Log::info('Raw email sent successfully', ['to' => $to]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send raw email', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send an OTP email
     */
    public function sendOtpEmail(string $to, string $otp, string $userName = 'User'): bool
    {
        try {
            Log::info('Sending OTP email', ['to' => $to, 'otp' => $otp]);

            Mail::to($to)->send(new \App\Mail\OtpMail($otp, $userName));

            Log::info('OTP email sent successfully', ['to' => $to]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP email', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Send email using a Mailable class
     */
    public function sendMailable(string $to, $mailable): bool
    {
        try {
            Log::info('Sending mailable email', [
                'to' => $to,
                'mailable' => get_class($mailable),
            ]);

            Mail::to($to)->send($mailable);

            Log::info('Mailable email sent successfully', ['to' => $to]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send mailable email', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the last error message
     */
    public function getLastError(): ?string
    {
        // You can implement more sophisticated error tracking here
        return 'Check logs for details';
    }
}
