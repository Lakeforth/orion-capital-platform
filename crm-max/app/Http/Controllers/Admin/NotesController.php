<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Services\StorageService;
use App\Tables\MenuTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ProtoneMedia\Splade\Facades\Toast;

class NotesController extends Controller
{
    public function create(){
        return view('admin.notes.create');
    }

    public function index(Request $request) {
        $user_id = $request->get('user_id');
        if(!$user_id){
            return redirect()->back();
        }
        $notes = Note::with('by')->where('user_id', $user_id)->get();

        $default = [
            'user_id' => $user_id
        ];

        return view('admin.notes.index', [
            'data' => $notes,
            'default' => $default
        ]);
    }

    public function loadNotes($id) {

        $notes = Note::with('by')->where('user_id', $id)->get();

        return view('admin.components.notes', [
            'data' => $notes,
        ]);
    }


    public function edit($id) : View {
        $item = Note::findOrFail($id);
        return view('admin.notes.edit', compact('item'));
    }


    public function destroy($id): RedirectResponse
    {
        $note = Note::findOrFail($id);
        $note->delete();
        Toast::title('Note deleted successfully');
        return redirect()->back();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['user_id'] = $request->get('user_id');
        Note::create($data);
        Toast::title('Note created successfully');
        return redirect()->back();
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $menu = Note::findOrFail($id);
        $data = $this->validateData($request, $id);
        $menu->update($data);
        Toast::title('Note updated successfully');
        return redirect()->back();
    }

    public function toggle($type, $id): RedirectResponse
    {
        $menu = Note::findOrFail($id);

        $menu->status = !$menu->status;

        $menu->save();
        Toast::title('Note updated successfully');
        return redirect()->back();
    }

    public function updatePosition($id, $direction){
        $menu = Note::findOrFail($id);
        if($direction == 1){
            if($menu->position > 0){
                $menu->position = $menu->position - 1;
            }
        }
        if($direction == 0){
            $menu->position = $menu->position + 1;
        }

        $menu->save();
//        Toast::title('Note updated successfully');
        return redirect()->back();
    }

    private function validateData(Request $request, $id = null): array
    {
        $rules = [
            'note' => 'required',
        ];

        $data =  $request->validate($rules);


        $data['by_id'] = auth()->id();

        return $data;
    }

}
