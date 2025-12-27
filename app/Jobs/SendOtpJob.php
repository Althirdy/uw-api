<?php

namespace App\Jobs;

use App\Services\SemaphoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOtpJob implements ShouldQueue
{
    use Queueable,Dispatchable,InteractsWithQueue,SerializesModels;
    protected $phoneNumber;
    protected $otpCode;

    /**
     * Create a new job instance.
     */
    public function __construct($phoneNumber, $otpCode)
    {
        $this->phoneNumber = $phoneNumber;
        $this->otpCode = $otpCode;
    }

    /**
     * Execute the job.
     */
    public function handle(SemaphoreService $semaphore): void
    {
        $message = "Your secure login code is: {otp}. Do not share this.";

        $semaphore->sendOtp($this->phoneNumber, $message, $this->otpCode);
        //
    }
}
