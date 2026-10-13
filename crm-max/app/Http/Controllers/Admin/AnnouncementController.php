<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Tables\AnnouncementTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ProtoneMedia\Splade\Facades\Toast;

class AnnouncementController extends Controller
{
    public const MAX_ACTIVE = 5;

    public function index(): View
    {
        return view('admin.announcements.index', [
            'data'           => AnnouncementTable::class,
            'activeCount'    => $this->activeCount(),
            'sectionEnabled' => $this->isSectionEnabled(),
        ]);
    }

    public function toggleSection(): RedirectResponse
    {
        $enabled = $this->isSectionEnabled();
        set_company_setting('announcements_section_enabled', $enabled ? 'no' : 'yes');

        Toast::title('Announcements section ' . ($enabled ? 'disabled' : 'enabled'));

        return redirect()->back();
    }

    public function create(): View
    {
        return view('admin.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($this->wantsActive($data) && ! $this->canActivate()) {
            Toast::warning('Active limit reached — deactivate an item before adding more.');

            return redirect()->back()->withInput();
        }

        Announcement::create($data);
        Toast::title('Announcement created successfully');

        return redirect()->route('admin.announcements.index');
    }

    public function edit(string $id): View
    {
        $announcement = Announcement::findOrFail($id);

        return view('admin.announcements.edit', [
            'announcement' => $announcement,
            'formData' => $this->formDefaults($announcement),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $announcement = Announcement::findOrFail($id);
        $data = $this->validateData($request);

        if ($this->wantsActive($data) && ! $announcement->is_active && ! $this->canActivate($id)) {
            Toast::warning('Active limit reached — deactivate an item before adding more.');

            return redirect()->back()->withInput();
        }

        $announcement->update($data);
        Toast::title('Announcement updated successfully');

        return redirect()->route('admin.announcements.index');
    }

    public function toggle(string $id): RedirectResponse
    {
        $announcement = Announcement::findOrFail($id);

        if (! $announcement->is_active && ! $this->canActivate($id)) {
            Toast::warning('Active limit reached — deactivate an item before adding more.');

            return redirect()->back();
        }

        $announcement->is_active = ! $announcement->is_active;
        $announcement->save();

        Toast::title('Announcement updated successfully');

        return redirect()->back();
    }

    public function destroy(string $id): RedirectResponse
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        Toast::title('Announcement deleted successfully');

        return redirect()->back();
    }

    private function isSectionEnabled(): bool
    {
        return company_settings('announcements_section_enabled') !== 'no';
    }

    private function activeCount(): int
    {
        return Announcement::where('is_active', true)->count();
    }

    private function canActivate(?string $excludingId = null): bool
    {
        $query = Announcement::where('is_active', true);

        if ($excludingId) {
            $query->where('id', '!=', $excludingId);
        }

        return $query->count() < self::MAX_ACTIVE;
    }

    private function wantsActive(array $data): bool
    {
        return ! empty($data['is_active']);
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'body' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['priority'] = $data['priority'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        foreach (['starts_at', 'ends_at'] as $field) {
            if (empty($data[$field])) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function formDefaults(Announcement $announcement): array
    {
        return [
            ...$announcement->toArray(),
            'starts_at' => $announcement->starts_at?->format('Y-m-d\TH:i'),
            'ends_at' => $announcement->ends_at?->format('Y-m-d\TH:i'),
        ];
    }
}
