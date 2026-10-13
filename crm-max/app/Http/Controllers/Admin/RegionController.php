<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Services\CommonService;
use App\Tables\RegionTable;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class RegionController extends Controller
{
    public function create(CommonService $commonService){
        $regions = $commonService->getRegions();
        return view('admin.regions.create', compact('regions'));
    }

    public function index(){
        return view('admin.regions.index', [
            'data' => RegionTable::class,
        ]);
    }

    public function edit($id){
        $region = Region::findOrFail($id);

        return view('admin.regions.edit', compact('region'));
    }



    public function destroy($id){
        $region = Region::findOrFail($id);
        $region->delete();
        Toast::title('Region deleted successfully');
        return redirect()->back();
    }

    public function store(Request $request){
        $data = $this->validateData($request);

        Region::create($data);

        Toast::title('Region created successfully');
        return redirect()->back();
    }

    public function update(Request $request, $id){
        $data = $this->validateData($request, $id);
        $region = Region::findOrFail($id);
        $region->update($data);
        Toast::title('Region updated successfully');
        return redirect()->back();
    }


    private function validateData(Request $request, $id = null): array
    {
        $rules = [
            'name' => ['required'],
            'parent_id' => 'nullable',
        ];

        return $request->validate($rules);
    }
}
