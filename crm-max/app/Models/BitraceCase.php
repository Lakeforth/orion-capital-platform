<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BitraceCase extends Model
{
    use BelongsToCompany, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'manager_id',
        'assigned_manager_id',
        'case_number',
        'title',
        'status',
        'current_stage',
        'eth_wallet_address',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(BitraceCaseActivity::class, 'case_id')->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BitraceCaseDocument::class, 'case_id')->latest();
    }

    public function assignedManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_manager_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(BitraceCasePhase::class, 'case_id')->orderBy('order');
    }

    public function activePhase(): HasOne
    {
        return $this->hasOne(BitraceCasePhase::class, 'case_id')->where('is_active', true);
    }

    public function transferRequests(): HasMany
    {
        return $this->hasMany(BitraceTransferRequest::class, 'case_id')->latest();
    }

    public static function generateCaseNumber(int $companyId): string
    {
        $sequence = static::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->count() + 1;

        return sprintf('BC-%d-%04d', $companyId, $sequence);
    }

    public const TERMINAL_STATUSES = ['closed', 'withdrawn'];

    public const WITHDRAWABLE_STATUSES = ['open', 'in_progress'];

    public static function userHasActiveCase(int|string $userId, int $companyId): bool
    {
        return static::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->whereNotIn('status', self::TERMINAL_STATUSES)
            ->exists();
    }
}
