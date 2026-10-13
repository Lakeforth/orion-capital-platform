<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The first time a staff member opened a lead. A lead is unread for a viewer while it was
 * captured through intake (not a legacy backfill row) and that viewer has no row here, so one
 * person opening a lead never clears it for anyone else, and a newly assigned owner who never
 * opened it still sees it as new.
 */
class LeadView extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'lead_id', 'viewer_id', 'first_viewed_at'];

    protected $casts = ['first_viewed_at' => 'datetime'];

    public static function record(User $lead, User $viewer): self
    {
        return static::query()->firstOrCreate(
            ['lead_id' => $lead->id, 'viewer_id' => $viewer->id],
            ['company_id' => $lead->company_id, 'first_viewed_at' => now()],
        );
    }

    /** Narrow a users query to the leads $viewerId has not opened yet. */
    public static function unread(Builder $leads, string $viewerId): Builder
    {
        $table = $leads->getModel()->getTable();

        return $leads
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('lead_attributions')
                ->whereColumn('lead_attributions.lead_id', $table.'.id')
                ->where('lead_attributions.source_origin', '!=', LeadAttribution::LEGACY))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('lead_views')
                ->whereColumn('lead_views.lead_id', $table.'.id')
                ->where('lead_views.viewer_id', $viewerId));
    }

    /** How many leads the user can see and has not opened (admins see every lead, others their own). */
    public static function unreadCount(User $viewer): int
    {
        $leads = User::query()->whereHasRole('lead')->when(! is_admin(), fn ($q) => $q->where('manager_id', $viewer->id));

        return static::unread($leads, $viewer->id)->count();
    }
}
