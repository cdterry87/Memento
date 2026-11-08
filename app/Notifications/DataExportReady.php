<?php

namespace App\Notifications;

use App\Models\DataExport;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Sent when an export has been built. It holds only a signed link, which
 * expires with the export, and never the journal itself.
 */
class DataExportReady extends Notification
{
    public function __construct(public DataExport $export) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Memento export is ready')
            ->greeting('Hi '.explode(' ', trim($notifiable->name))[0].',')
            ->line('The copy of your journal you asked for is ready to download.')
            ->action('Download your export', self::downloadUrl($this->export))
            ->line('The link works for '.DataExport::EXPIRES_AFTER_HOURS.' hours, and you need to be signed in to use it. After that the file is deleted, and you can make a new one from your Account page.')
            ->line("If you didn't ask for this, you can ignore this email, but consider changing your password.")
            ->salutation('Memento');
    }

    /**
     * A signed link to the download that stops working when the export expires.
     */
    public static function downloadUrl(DataExport $export): string
    {
        return URL::temporarySignedRoute(
            'account.export.download',
            $export->expires_at ?? now()->addHours(DataExport::EXPIRES_AFTER_HOURS),
            ['export' => $export->ulid],
        );
    }
}
