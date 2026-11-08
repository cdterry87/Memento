<?php

namespace App\Models;

use App\Enums\EmailType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record that an email went to a user for one of their local dates. The
 * unique key on user, type, and date stops the same email going twice.
 *
 * @property int $id
 * @property int $user_id
 * @property EmailType $type
 * @property CarbonImmutable $local_date
 */
#[Fillable(['user_id', 'type', 'local_date'])]
class NotificationSend extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EmailType::class,
            'local_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
