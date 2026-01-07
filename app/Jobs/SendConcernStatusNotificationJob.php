<?php

namespace App\Jobs;

use App\Mail\ConcernStatusUpdatedMail;
use App\Models\Citizen\Concern;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendConcernStatusNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Concern $concern;
    public User $updatedBy;
    public string $previousStatus;
    public string $newStatus;
    public ?string $remarks;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Concern $concern,
        User $updatedBy,
        string $previousStatus,
        string $newStatus,
        ?string $remarks = null
    ) {
        $this->concern = $concern;
        $this->updatedBy = $updatedBy;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
        $this->remarks = $remarks;
    }

    /**
     * Execute the job.
     */
    public function handle(MailService $mailService): void
    {
        try {
            // Get the citizen's email
            $citizenEmail = $this->getCitizenEmail();

            if (!$citizenEmail) {
                Log::warning('Cannot send concern status notification - no citizen email found', [
                    'concern_id' => $this->concern->id,
                ]);
                return;
            }

            // Create the mailable
            $mailable = new ConcernStatusUpdatedMail(
                $this->concern,
                $this->updatedBy,
                $this->previousStatus,
                $this->newStatus,
                $this->remarks
            );

            // Send the email using MailService
            $sent = $mailService->sendConcernStatusNotification(
                $citizenEmail,
                $mailable
            );

            if ($sent) {
                Log::info('Concern status notification email sent successfully', [
                    'concern_id' => $this->concern->id,
                    'citizen_email' => $citizenEmail,
                    'new_status' => $this->newStatus,
                ]);
            } else {
                Log::error('Failed to send concern status notification email', [
                    'concern_id' => $this->concern->id,
                    'citizen_email' => $citizenEmail,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending concern status notification', [
                'concern_id' => $this->concern->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to allow retry
        }
    }

    /**
     * Get the citizen's email address
     */
    protected function getCitizenEmail(): ?string
    {
        // Load citizen relationship if not loaded
        $this->concern->loadMissing('citizen');

        return $this->concern->citizen?->email;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendConcernStatusNotificationJob failed after all retries', [
            'concern_id' => $this->concern->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
