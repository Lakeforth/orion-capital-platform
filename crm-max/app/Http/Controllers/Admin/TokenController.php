<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApiTokenAction;
use App\Http\Controllers\Controller;
use App\Models\Token;
use App\Tables\AllTokenTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use ProtoneMedia\Splade\Facades\Toast;

class TokenController extends Controller
{
    public function index()
    {
        return view('admin.tokens.index', [
            'data' => AllTokenTable::class
        ]);
    }

    public function create()
    {
        return view('admin.tokens.create');
    }

    public function store(Request $request)
    {
        $validatedData = $this->getData($request);
        $user = auth()->user();
        $token = (new ApiTokenAction())->createToken($validatedData['user_id'] ?? $user->id);
        Toast::success('Token generated successfully.');

        return redirect()->back();
    }


    public function destroy($id)
    {
        $item = Token::findOrFail($id);
        $item->delete();

        Toast::success('Token revoked successfully.');
        return redirect()->back();
    }

    protected function getData(Request $request): array
    {
        return $request->validate([
            'user_id' => 'nullable',
        ]);
    }
}
