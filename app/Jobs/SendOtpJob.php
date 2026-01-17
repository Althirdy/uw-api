<?php

namespace App\Jobs;

use App\Services\TextBeeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;

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
    public function handle(): void
    {
        $textBee = new TextBeeService(
            config('services.textbee.api_key'),
            config('services.textbee.device_id')
        );

        $textBee->sendOtp($this->phoneNumber, $this->otpCode);
    }
}
