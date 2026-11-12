<?php

namespace App\Notifications;

use App\Models\LetterRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemActivityNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public string $category = 'info',
        public ?string $icon = null,
        public ?string $attachmentPath = null,
        public ?string $attachmentName = null,
        public ?LetterRequest $letter = null,
        public ?array $details = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Send email for student and faculty persuratan lifecycle events if user has email
        if (! empty($notifiable->email) && in_array($this->category, ['letter_signed', 'letter_submitted', 'letter_rejected'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionUrl = $this->url ? url($this->url) : null;
        $actionText = match ($this->category) {
            'letter_signed' => 'Unduh Dokumen Resmi (PDF)',
            'letter_rejected' => 'Perbaiki Formulir Pengajuan',
            'letter_submitted' => 'Pantau Status Pengajuan',
            default => 'Lihat Detail Surat',
        };

        $hasAttachment = ! empty($this->attachmentPath) && file_exists($this->attachmentPath);

        $rejectionReason = null;
        if ($this->category === 'letter_rejected') {
            $rejectionReason = $this->details['Alasan Penolakan'] ?? ($this->letter?->rejection_reason ?? null);
        }

        $mail = (new MailMessage)
            ->subject("[SILAS-TTE] {$this->title}")
            ->view('emails.letter-status', [
                'notifiable' => $notifiable,
                'title' => $this->title,
                'messageText' => $this->message,
                'category' => $this->category,
                'letter' => $this->letter,
                'rejectionReason' => $rejectionReason,
                'actionUrl' => $actionUrl,
                'actionText' => $actionText,
                'hasAttachment' => $hasAttachment,
            ]);

        // Attach signed official PDF directly if available
        if ($hasAttachment) {
            $mail->attach($this->attachmentPath, [
                'as' => $this->attachmentName ?: 'surat-resmi-fasilkom.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'category' => $this->category,
            'icon' => $this->icon,
            'time' => now()->diffForHumans(),
            'created_at' => now()->toIso8601String(),
        ];
    }
}
