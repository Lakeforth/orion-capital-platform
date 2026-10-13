<?php

namespace App\Http\Controllers\Admin;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use ProtoneMedia\Splade\SpladeCore;

/**
 * LeadMax chat and notifications.
 * Client chat follows the client-area convention: messages.customer_id is the client and staff replies set is_admin.
 * Team chat is staff-to-staff: conversation_type 'team' with no customer_id, so it never appears in client chats.
 */
class LeadMaxChatController extends LeadMaxController
{
    public const TEAM = 'team';

    public const STAFF_ROLES = ['admin', 'tenant_admin', 'manager', 'agent'];

    public function team(Request $request)
    {
        $me = auth()->user();
        $staff = User::query()->whereHasRole(self::STAFF_ROLES)->whereKeyNot($me->id)->orderBy('first_name')->get();
        // Each thread's last message and unread count come from grouped queries, not from loading
        // every team message ever exchanged (this page is re-fetched every few seconds).
        $mine = fn () => Message::query()->without(['sender', 'attachments'])->where('conversation_type', self::TEAM)
            ->where(fn ($q) => $q->where('sender_id', $me->id)->orWhere('receiver_id', $me->id));
        $partner = 'CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END';
        $lastAt = $mine()->selectRaw("$partner as partner_id, MAX(created_at) as last_at", [$me->id])->groupByRaw($partner, [$me->id])->pluck('last_at', 'partner_id');
        $lastMessages = $lastAt->isEmpty() ? collect() : $mine()->whereIn('created_at', $lastAt->values()->unique()->all())->latest()
            ->get(['id', 'sender_id', 'receiver_id', 'message', 'created_at'])
            ->map(fn (Message $m) => [$m->sender_id === $me->id ? $m->receiver_id : $m->sender_id, $m])
            ->filter(fn ($pair) => (string) ($lastAt[$pair[0]] ?? '') === $pair[1]->getRawOriginal('created_at'))
            ->unique(fn ($pair) => $pair[0])->mapWithKeys(fn ($pair) => [$pair[0] => $pair[1]]);
        $unread = Message::query()->where('conversation_type', self::TEAM)->where('receiver_id', $me->id)->whereNull('read_at')
            ->selectRaw('sender_id, COUNT(*) as unread')->groupBy('sender_id')->pluck('unread', 'sender_id');
        $conversations = $staff->map(fn (User $user) => $this->conversation($user, $lastMessages->get($user->id), (int) ($unread[$user->id] ?? 0)));

        return $this->page($request, 'team', $conversations, function (User $user) use ($me) {
            return Message::query()->where('conversation_type', self::TEAM)->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('sender_id', $me->id)->where('receiver_id', $user->id))
                ->orWhere(fn ($q) => $q->where('sender_id', $user->id)->where('receiver_id', $me->id)));
        }, fn (User $user) => Message::query()->where('conversation_type', self::TEAM)->where('sender_id', $user->id)->where('receiver_id', $me->id));
    }

    public function clients(Request $request)
    {
        $clientIds = $this->clientQuery()->select('users.id');
        $latest = $this->clientMessages()->whereIn('customer_id', $clientIds)
            ->selectRaw('customer_id, MAX(created_at) as last_at')->groupBy('customer_id')->pluck('last_at', 'customer_id');
        $unread = $this->clientMessages()->whereIn('customer_id', $clientIds)->where('is_admin', false)->whereNull('read_at')
            ->selectRaw('customer_id, COUNT(*) as unread')->groupBy('customer_id')->pluck('unread', 'customer_id');
        $withId = $request->input('with');
        $ids = $latest->keys()->when($withId, fn ($ids) => $ids->push($withId))->unique();
        $clients = $this->clientQuery()->whereIn('users.id', $ids)->with(['roles', 'affiliate'])->get();
        // Only each conversation's last message, matched on the grouped MAX(created_at) above,
        // instead of every message of every listed client.
        $lastAt = $latest->only($clients->pluck('id')->all());
        $lastMessages = $lastAt->isEmpty() ? collect() : $this->clientMessages()->whereIn('customer_id', $lastAt->keys())->whereIn('created_at', $lastAt->values()->unique()->all())
            ->latest()->get(['customer_id', 'message', 'created_at'])
            ->filter(fn (Message $m) => (string) $lastAt[$m->customer_id] === $m->getRawOriginal('created_at'))
            ->unique('customer_id')->keyBy('customer_id');
        $conversations = $clients->map(fn (User $client) => $this->conversation($client, $lastMessages->get($client->id), (int) ($unread[$client->id] ?? 0)));

        return $this->page($request, 'clients', $conversations,
            fn (User $client) => $this->clientMessages()->where('customer_id', $client->id),
            fn (User $client) => $this->clientMessages()->where('customer_id', $client->id)->where('is_admin', false));
    }

    public function send(Request $request, string $type, string $id)
    {
        abort_unless(in_array($type, ['team', 'clients'], true), 404);
        $data = $request->validate(['message' => 'required|string|max:5000']);
        if ($type === 'team') {
            $user = User::query()->whereHasRole(self::STAFF_ROLES)->whereKeyNot(auth()->id())->findOrFail($id);
            $message = Message::create(['message' => $data['message'], 'sender_id' => auth()->id(), 'receiver_id' => $user->id, 'is_admin' => true, 'conversation_type' => self::TEAM]);
        } else {
            $client = $this->clientQuery()->findOrFail($id);
            abort_if((bool) data_get($client->settings, 'communications_blocked', false), 422, 'Communications are blocked for this lead.');
            $message = Message::create(['message' => $data['message'], 'sender_id' => auth()->id(), 'receiver_id' => $client->id, 'customer_id' => $client->id, 'is_admin' => true]);
            try {
                // Same event the client area listens to, so the client sees the reply live.
                broadcast(new MessageSent($message->load(['sender', 'attachments'])));
            } catch (\Throwable $exception) {
                Log::warning('leadmax.chat.broadcast_failed', ['message_id' => $message->id, 'error' => $exception->getMessage()]);
            }
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Sent'])
            : redirect()->route('admin.leadmax.chat.'.$type, ['with' => $id]);
    }

    public function pin(Request $request, string $type, string $id)
    {
        abort_unless(in_array($type, ['team', 'clients'], true), 404);
        $me = auth()->user();
        $pins = collect(data_get($me->settings, 'leadmax_pinned_chats.'.$type, []));
        $pins = $pins->contains($id) ? $pins->reject(fn ($pin) => $pin === $id)->values() : $pins->push($id);
        $settings = $me->settings ?? [];
        data_set($settings, 'leadmax_pinned_chats.'.$type, $pins->take(20)->all());
        $me->forceFill(['settings' => $settings])->saveQuietly();

        return redirect()->route('admin.leadmax.chat.'.$type, ['with' => $id]);
    }

    public function notifications(Request $request)
    {
        $tab = $request->input('tab') === 'unread' ? 'unread' : 'all';
        $limit = min(max((int) $request->input('limit', 20), 20), 200);
        $query = self::visibleNotifications(auth()->user())->when($tab === 'unread', fn ($q) => $q->whereNull('read_at'))->latest();
        $total = (clone $query)->count();

        return view('admin.leadmax.notifications', [
            ...$this->common($request, 'notifications'),
            'tab' => $tab,
            'groups' => self::groupNotifications((clone $query)->limit($limit)->get(), true),
            'hasMore' => $total > $limit,
            'nextLimit' => $limit + 20,
            'unreadCount' => self::visibleNotifications(auth()->user())->whereNull('read_at')->count(),
        ]);
    }

    /** Polled by the bell: unread totals and the newest unread items, so it updates without a reload. */
    public function notificationSummary(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        // The poll is what announces a new arrival, so its badge is counted fresh (and refreshes the
        // cache the pages read), never a page's count from up to 20 s before.
        self::forgetUnreadCounts($user);
        $counts = self::unreadCounts($user);

        return response()->json([
            'notifications' => $counts['notifications'],
            'incoming_unread' => $counts['incoming'],
            'latest' => self::visibleNotifications($user)->whereNull('read_at')->latest()->limit(20)->get()
                ->map(fn ($n) => ['id' => $n->id, 'title' => $n->data['title'] ?? 'Notification', 'type' => $n->data['type'] ?? 'system', 'tone' => \App\Support\LeadMax\NotificationFamily::tone($n->data ?? []), 'created_at' => $n->created_at?->toIso8601String()])
                ->values(),
        ]);
    }

    public function notificationsReadAll(Request $request)
    {
        // Only what the user could have seen: anything that arrived after the list was shown
        // (the form's "before" time) stays unread.
        $before = $request->date('before')?->setTimezone(config('app.timezone'));
        self::visibleNotifications(auth()->user())->whereNull('read_at')
            ->when($before, fn ($query) => $query->where('created_at', '<=', $before))
            ->update(['read_at' => now()]);
        self::forgetUnreadCounts(auth()->user());

        return $request->expectsJson() ? response()->json(['message' => 'Done']) : redirect()->back();
    }

    public function notificationDismiss(Request $request, string $id)
    {
        auth()->user()->notifications()->whereKey($id)->delete();
        self::forgetUnreadCounts(auth()->user());

        return $request->expectsJson() ? response()->json(['message' => 'Dismissed']) : redirect()->back();
    }

    /** Today / Yesterday / This week / Earlier buckets used by the dropdown and the notifications page. */
    public static function groupNotifications(Collection $notifications, bool $withWeek = false): array
    {
        $groups = [];
        foreach ($notifications as $notification) {
            $at = $notification->created_at;
            $label = match (true) {
                $at->isToday() => 'Today',
                $at->isYesterday() => 'Yesterday',
                $withWeek && $at->isAfter(now()->subDays(7)) => 'This week',
                default => 'Earlier',
            };
            $groups[$label][] = $notification;
        }

        return $groups;
    }

    /** Unread counts for the topbar chat icons, scoped like the chat pages. */
    /**
     * The user's notifications, minus lead notifications for leads they can no longer open:
     * a reassigned lead's "New lead received" must not keep showing its name to the previous
     * owner. Admins can open every lead, so nothing is hidden from them.
     */
    public static function visibleNotifications(User $user)
    {
        $query = $user->notifications();
        if ($user->hasRole(['admin', 'superadmin', 'tenant_admin'])) {
            return $query;
        }

        return $query->where(fn ($q) => $q->whereNull('data->type')->orWhere('data->type', '!=', 'lead_created')
            ->orWhereIn('data->meta->lead_id', User::query()->whereHasRole('lead')->where('manager_id', $user->id)->select('id')));
    }

    /**
     * Cached for 20 seconds: these counts run on every CRM page and every bell poll. Anything
     * this user reads or opens clears them at once (forgetUnreadCounts), so their own badges
     * never lag; what others send shows within the bell's poll interval anyway.
     */
    public static function unreadCounts(User $user): array
    {
        return \Illuminate\Support\Facades\Cache::remember('leadmax:unread:'.$user->id, 20, fn () => self::countUnread($user));
    }

    public static function forgetUnreadCounts(User $user): void
    {
        \Illuminate\Support\Facades\Cache::forget('leadmax:unread:'.$user->id);
    }

    private static function countUnread(User $user): array
    {
        $clients = Message::query()->whereNull('conversation_type')->whereNotNull('customer_id')->where('is_admin', false)->whereNull('read_at')
            ->whereIn('customer_id', User::query()->whereHasRole(['lead', 'customer', 'user'])->when(! is_admin(), fn ($q) => $q->where('manager_id', $user->id))->select('users.id'));

        return [
            'team' => Message::query()->where('conversation_type', self::TEAM)->where('receiver_id', $user->id)->whereNull('read_at')->count(),
            'clients' => $clients->count(),
            'notifications' => static::visibleNotifications($user)->whereNull('read_at')->count(),
            // Leads this user may open and has not opened yet (sidebar Attribution badge).
            'incoming' => \App\Models\LeadView::unreadCount($user),
        ];
    }

    private function page(Request $request, string $type, Collection $conversations, \Closure $threadQuery, \Closure $unreadQuery)
    {
        $pins = collect(data_get(auth()->user()->settings, 'leadmax_pinned_chats.'.$type, []));
        $search = trim($request->string('q')->toString());
        $conversations = $conversations
            ->when($search !== '', fn ($c) => $c->filter(fn ($row) => str_contains(mb_strtolower($row['user']->name.' '.$row['user']->email), mb_strtolower($search))))
            ->when($request->input('filter') === 'unread', fn ($c) => $c->filter(fn ($row) => $row['unread'] > 0))
            ->sortByDesc(fn ($row) => $row['at']?->getTimestamp() ?? 0)->values();
        $selected = $conversations->firstWhere('user.id', $request->input('with')) ?? $conversations->first();
        $messages = collect();
        if ($selected) {
            $unreadQuery($selected['user'])->whereNull('read_at')->update(['read_at' => now()]);
            self::forgetUnreadCounts(auth()->user());
            $selected['unread'] = 0;
            $conversations = $conversations->map(fn ($row) => $row['user']->id === $selected['user']->id ? [...$row, 'unread' => 0] : $row);
            $messages = $threadQuery($selected['user'])->with('sender')->latest()->limit(100)->get()->reverse()->values();
        }
        $data = [
            ...$this->common($request, 'chat-'.$type),
            'type' => $type,
            'pinned' => $conversations->filter(fn ($row) => $pins->contains($row['user']->id))->values(),
            'recent' => $conversations->reject(fn ($row) => $pins->contains($row['user']->id))->values(),
            'pins' => $pins,
            'selected' => $selected,
            'messages' => $messages,
            'search' => $search,
            'filter' => $request->input('filter') === 'unread' ? 'unread' : 'all',
        ];
        if ($request->header('X-LeadMax-Fragment') === 'chat') {
            return response(view('admin.leadmax.partials.chat-thread', $data)->render())->header(SpladeCore::HEADER_SKIP_MIDDLEWARE, 'true');
        }

        return view('admin.leadmax.chat', $data);
    }

    private function conversation(User $user, ?Message $last, int $unread): array
    {
        return ['user' => $user, 'last' => $last?->message, 'at' => $last?->created_at ? Carbon::parse($last->created_at) : null, 'unread' => $unread];
    }

    /** Leads and clients the viewer may chat with (managers only see their own). */
    private function clientQuery(): Builder
    {
        $query = User::query()->whereHasRole(['lead', 'customer', 'user']);

        return is_admin() ? $query : $query->where('manager_id', auth()->id());
    }

    private function clientMessages(): Builder
    {
        return Message::query()->without(['sender', 'attachments'])->whereNull('conversation_type')->whereNotNull('customer_id');
    }
}
