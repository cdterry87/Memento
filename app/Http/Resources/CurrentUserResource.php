<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed-in user, as shared with every page. Only what the interface
 * needs, so nothing else leaks to the browser.
 *
 * @mixin User
 */
class CurrentUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{name: string, email: string, emailVerified: bool, timezone: string, theme: string, showQuotes: bool, reminderEmails: bool, reminderTime: string, onThisDayEmails: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'emailVerified' => $this->hasVerifiedEmail(),
            'timezone' => $this->timezone,
            'theme' => $this->theme->value,
            'showQuotes' => $this->show_quotes,
            'reminderEmails' => $this->reminder_emails,
            'reminderTime' => substr($this->reminder_time, 0, 5),
            'onThisDayEmails' => $this->on_this_day_emails,
        ];
    }
}
