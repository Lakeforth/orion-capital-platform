<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\StorageService;
use App\Tables\ServiceTable;
use App\Tables\SubServiceTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Splade;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\FileUploads\HandleSpladeFileUploads;

class ServiceController extends Controller
{
    public function create(){
        $default = [
            'status' => 1,
            'color' => '#000000'
        ];
        return view('admin.services.create', compact('default'));
    }

    public function createSub(){
        $default = [
            'status' => 1,
            'color' => '#000000'
        ];
        return view('admin.services.create_sub', compact('default'));
    }

    public function index(){
        return view('admin.services.index', [
            'data' => ServiceTable::class,
        ]);
    }
    public function subServices(){
        return view('admin.services.sub_services', [
            'data' => Splade::onLazy(fn () => SubServiceTable::class),
        ]);
    }
    public function edit(Service $service){
        if(isset($service['image'])){
            $service['img'] = get_existing_file($service['image']);
        }

        return view('admin.services.edit', compact('service'));
    }



    public function destroy(Service $service){
        $service->delete();
        Toast::title('Service deleted successfully');
        return redirect()->back();
    }

    public function store(Request $request, StorageService $storageService){
        $data = $this->validateData($request);
        $data['is_featured'] = $data['is_featured'] ?? 0;

        HandleSpladeFileUploads::forRequest($request);

        if($request->hasFile('image')){
            $data['image'] = $storageService
                ->uploadFile($request->file('image'),'services');
        }

        Service::create($data);
        Toast::title('Service created successfully');
        return redirect()->back();
    }

    public function update(Request $request, $id){
        $storageService = new StorageService();
        $data = $this->validateData($request, $id);
//        dd($request->all());
        if($request->hasFile('img')){
            $data['image'] = $storageService
                ->uploadFile($request->file('img'),'services');
        }
        $service = Service::findOrFail($id);
        $service->update($data);
        Toast::title('Service updated successfully');
        return redirect()->back();
    }

    public function toggle($type, $id): RedirectResponse
    {
        $service = Service::findOrFail($id);
        if($type == 'featured'){
            $service->is_featured = !$service->is_featured;
        }else{
            $service->status = !$service->status;
        }
        $service->save();
        Toast::title('Service updated successfully');
        return redirect()->back();
    }

    private function validateData(Request $request, $id = null): array
    {
        $rules = [
            'name' => ['required','max:50','unique:services,name,' . $id],
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'nullable',
            'title' => 'nullable',
            'is_featured' => 'nullable',
            'service_id' => 'nullable',
            'description' => 'nullable|max:1000',
            'color' => 'nullable',
            'position' => 'nullable',
        ];

        return $request->validate($rules);
    }

}
