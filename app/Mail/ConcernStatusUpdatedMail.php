<?php

namespace App\Mail;

use App\Models\Citizen\Concern;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConcernStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Concern $concern;
    public User $updatedBy;
    public string $previousStatus;
    public string $newStatus;
    public ?string $remarks;

    /**
     * Create a new message instance.
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
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Concern Status Update - ' . ucfirst($this->newStatus),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.concern-status-updated',
            with: [
                'concern' => $this->concern,
                'updatedBy' => $this->updatedBy,
                'previousStatus' => $this->previousStatus,
                'newStatus' => $this->newStatus,
                'remarks' => $this->remarks,
                'citizenName' => $this->getCitizenName(),
                'updatedByName' => $this->getUpdatedByName(),
            ],
        );
    }

    /**
     * Get the citizen's name
     */
    protected function getCitizenName(): string
    {
        if ($this->concern->citizen && $this->concern->citizen->citizenDetails) {
            $details = $this->concern->citizen->citizenDetails;
            return trim("{$details->first_name} {$details->last_name}");
        }
        
        return $this->concern->citizen?->name ?? 'Citizen';
    }

    /**
     * Get the name of who updated the concern
     */
    protected function getUpdatedByName(): string
    {
        if ($this->updatedBy->officialDetails) {
            $details = $this->updatedBy->officialDetails;
            return trim("{$details->first_name} {$details->last_name}");
        }
        
        return $this->updatedBy->name ?? 'Purok Leader';
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
