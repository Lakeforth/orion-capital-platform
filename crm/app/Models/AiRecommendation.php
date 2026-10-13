<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiRecommendation extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENT = 'sent';

    public const STATUS_RECURRING = 'recurring';

    public const SCHEDULE_INSTANT = 'instant';

    public const SCHEDULE_SPECIFIC_DATE = 'specific_date';

    public const SCHEDULE_RECURRING = 'recurring';

    protected $fillable = [
        'company_id',
        'user_id',
        'heading',
        'message',
        'source_name',
        'schedule_type',
        'recurrence',
        'scheduled_at',
        'last_delivered_at',
        'delivery_count',
        'is_active',
        'send_email',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'last_delivered_at' => 'datetime',
        'delivery_count' => 'integer',
        'is_active' => 'boolean',
        'send_email' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
