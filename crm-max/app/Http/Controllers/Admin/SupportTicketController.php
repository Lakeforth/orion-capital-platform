<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ProtoneMedia\Splade\Facades\Toast;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $tickets = SupportTicket::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->with(['user:id,first_name,last_name,email', 'replies' => fn ($q) => $q->latest()->limit(1)])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.support-tickets.index', compact('tickets', 'status'));
    }

    public function show(string $id): View
    {
        $ticket = $this->findTicket($id);
        $ticket->load(['user', 'challenge.plan', 'replies.attachments']);

        return view('admin.support-tickets.show', compact('ticket'));
    }

    public function reply(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate(['message' => ['required', 'string']]);

        $ticket = $this->findTicket($id);

        SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'author_type' => 'admin',
            'author_id' => auth()->id(),
            'message' => $validated['message'],
        ]);

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        Toast::title('Reply sent.');

        return redirect()->back();
    }

    public function updateStatus(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', SupportTicket::STATUSES)],
        ]);

        $ticket = $this->findTicket($id);
        $ticket->update(['status' => $validated['status']]);

        Toast::title('Ticket status updated.');

        return redirect()->back();
    }

    private function findTicket(string $id): SupportTicket
    {
        return SupportTicket::withoutGlobalScope('company')
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);
    }
}
