<?php

namespace App\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;

/**
 * An optional daily email. Email isn't private, so these never contain
 * journal content: no entries, moods, or photos, only a link back into the
 * app. Each one carries a signed unsubscribe link, in the body and in the
 * List-Unsubscribe headers that mail apps use for one-click unsubscribe.
 */
abstract class DailyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function type(): EmailType;

    /**
     * The subject, greeting, and body, before the unsubscribe footer is added.
     */
    abstract protected function message(User $user): MailMessage;

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
        $unsubscribeUrl = self::unsubscribeUrl($notifiable, $this->type());

        return $this->message($notifiable)
            ->line("Don't want these emails? [Turn them off]({$unsubscribeUrl}).")
            ->withSymfonyMessage(function (Email $message) use ($unsubscribeUrl) {
                $message->getHeaders()
                    ->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>")
                    ->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }

    /**
     * A signed link that turns off one kind of email for one user. It never
     * expires, so an old email can still be used to unsubscribe.
     */
    public static function unsubscribeUrl(User $user, EmailType $type): string
    {
        return URL::signedRoute('unsubscribe', ['user' => $user->id, 'type' => $type->value]);
    }

    protected function firstName(User $user): string
    {
        return explode(' ', trim($user->name))[0];
    }
}
