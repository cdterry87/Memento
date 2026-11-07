<?php

namespace App\Models;

use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A short quote for the Today screen. Rows come from database/data/quotes.php.
 *
 * @property int $id
 * @property string $text
 * @property string $author
 */
#[Fillable(['text', 'author'])]
class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory;

    public $timestamps = false;
}
