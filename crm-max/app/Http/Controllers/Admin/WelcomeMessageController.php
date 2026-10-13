<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WelcomeMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class WelcomeMessageController extends Controller
{
    public function index()
    {
        $messages = WelcomeMessage::latest()->get();
        return view('admin.welcome_messages.index', compact('messages'));
    }

    public function create()
    {
        return view('admin.welcome_messages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        WelcomeMessage::create($data);
        Toast::title('Template created successfully');
        return redirect()->route('admin.welcome-messages.index');
    }

    public function edit(string $id)
    {
        $message = WelcomeMessage::findOrFail($id);
        return view('admin.welcome_messages.edit', compact('message'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $data = $this->validateData($request);
        $message = WelcomeMessage::findOrFail($id);
        $message->update($data);
        Toast::title('Template updated successfully');
        return redirect()->route('admin.welcome-messages.index');
    }

    public function destroy(string $id): RedirectResponse
    {
        WelcomeMessage::findOrFail($id)->delete();
        Toast::title('Template deleted');
        return redirect()->route('admin.welcome-messages.index');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'language' => ['required', 'in:English,French'],
            'subject'  => ['required', 'string', 'max:500'],
            'body'     => ['required', 'string'],
        ]);
    }
}
