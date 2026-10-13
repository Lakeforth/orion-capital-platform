<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ProtoneMedia\Splade\Facades\Toast;

class CommentController extends Controller
{
    public function create(){
        return view('admin.comments.create');
    }

    public function index(Request $request) {
        $user_id = $request->get('user_id');
        if(!$user_id){
            return redirect()->back();
        }
        $notes = Comment::with('by')->where('user_id', $user_id)->get();

        $default = [
            'user_id' => $user_id
        ];

        return view('admin.comments.index', [
            'data' => $notes,
            'default' => $default
        ]);
    }

    public function loadComments($id) {

        $notes = Comment::with('by')->where('user_id', $id)->get();

        return view('admin.components.comments', [
            'data' => $notes,
        ]);
    }


    public function edit($id) : View {
        $item = Comment::findOrFail($id);
        return view('admin.comments.edit', compact('item'));
    }


    public function destroy($id): RedirectResponse
    {
        $note = Comment::findOrFail($id);
        $note->delete();
        Toast::title('Comment deleted successfully');
        return redirect()->back();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['user_id'] = $request->get('user_id');
        Comment::create($data);
        Toast::title('Comment created successfully');
        return redirect()->back();
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $menu = Comment::findOrFail($id);
        $data = $this->validateData($request, $id);
        $menu->update($data);
        Toast::title('Comment updated successfully');
        return redirect()->back();
    }

    public function toggle($type, $id): RedirectResponse
    {
        $menu = Comment::findOrFail($id);

        $menu->status = !$menu->status;

        $menu->save();
        Toast::title('Comment updated successfully');
        return redirect()->back();
    }

    public function updatePosition($id, $direction){
        $menu = Comment::findOrFail($id);
        if($direction == 1){
            if($menu->position > 0){
                $menu->position = $menu->position - 1;
            }
        }
        if($direction == 0){
            $menu->position = $menu->position + 1;
        }

        $menu->save();
//        Toast::title('Comment updated successfully');
        return redirect()->back();
    }

    private function validateData(Request $request, $id = null): array
    {
        $rules = [
            'comment' => 'required',
        ];

        $data =  $request->validate($rules);


        $data['by_id'] = auth()->id();

        return $data;
    }

}
