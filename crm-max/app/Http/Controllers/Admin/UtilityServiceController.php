<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UtilityService;
use App\Tables\UtilityServiceTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Splade;
use ProtoneMedia\Splade\Facades\Toast;

class UtilityServiceController extends Controller
{
    public function create(){
        $default = [
            'status' => 1,
        ];
        return view('admin.utility_services.create', compact('default'));
    }

    public function index(){

        return view('admin.utility_services.index', [
            'data' => UtilityServiceTable::class,
        ]);
    }

    public function edit($id){
        $service = UtilityService::findOrFail($id);

        return view('admin.utility_services.edit', compact('service'));
    }



    public function destroy($id){
        $service = UtilityService::findOrFail($id);
        $service->delete();
        Toast::title('UtilityService deleted successfully');
        return redirect()->back();
    }

    public function store(Request $request){
        $data = $this->validateData($request);

        UtilityService::create($data);
        Toast::title('UtilityService created successfully');
        return redirect()->back();
    }

    public function update(Request $request, $id){
        $data = $this->validateData($request, $id);

        $service = UtilityService::findOrFail($id);
        $service->update($data);
        Toast::title('UtilityService updated successfully');
        return redirect()->back();
    }

    private function validateData(Request $request, $id = null): array
    {
        $rules = [
            'code' => ['required','max:50','unique:utility_services,code,' . $id],
            'service' => 'required',
            'biller_id' => 'required',
            'provider' => 'required',
            'name' => 'required',
            'type' => 'required',
            'price' => 'required',
            'discount' => 'required',
            'cashback' => 'required',
        ];

        return $request->validate($rules);
    }

}
