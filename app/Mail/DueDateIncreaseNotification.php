<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DueDateIncreaseNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $dueDateIncrease;
    public $status;

    /**
     * Create a new message instance.
     */
    public function __construct($dueDateIncrease, $status)
    {
        $this->dueDateIncrease = $dueDateIncrease;
        $this->status = $status;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Due Date Increase Request ' . ucfirst($this->status),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.bookloans.due_date_increase_notification',
            with: [
                'dueDateIncrease' => $this->dueDateIncrease,
                'status' => $this->status,
            ],
        );
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
