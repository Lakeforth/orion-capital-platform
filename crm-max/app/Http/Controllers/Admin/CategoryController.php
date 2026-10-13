<?php

namespace App\Http\Controllers\Admin;

use App\Forms\CreateCategoryForm;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Site;
use App\Services\StorageService;
use App\Tables\CategoryTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Splade;
use ProtoneMedia\Splade\Facades\Toast;


class CategoryController extends Controller
{
    public function create(Request $request){
        $service_id = $request->get('service_id');
        $default = [
            'status' => 1,
            'service_id' => $service_id
        ];
        $service = getService($service_id);
        return view('admin.categories.create', compact('default','service'));
    }

    public function createSub(){
        $default = [
            'status' => 1,
            'is_featured' => false,
        ];
        return view('admin.categories.create_sub', compact('default'));
    }

    public function index($service_id){
        return view('admin.categories.index', [
            'data' => new CategoryTable($service_id),
            'service_id' => $service_id,
            'service' => getService($service_id),
        ]);
    }
    public function subCategories(){
        return view('admin.categories.sub_categories', [
            'data' => new CategoryTable('sub_category'),
        ]);
    }
    public function edit($id){
        $category = Category::findOrFail($id);
        return view('admin.categories.edit', compact('category'));
    }



    public function destroy($id){
        $category = Category::findOrFail($id);
        $category->delete();
        Toast::title('Category deleted successfully');
        return redirect()->back();
    }

    public function store(Request $request){
        $data = $this->validateData($request);
        $data['featured'] = $data['featured'] ?? 0;
        Category::create($data);
        Toast::title('Category created successfully');
        return redirect()->back();
    }

    public function update(Request $request, $id){
        $data = $this->validateData($request, $id);
        $category = Category::findOrFail($id);
        $category->update($data);
        Toast::title('Category updated successfully');
        return redirect()->back();
    }

    public function toggle($type, $id): RedirectResponse
    {
        $category = Category::findOrFail($id);
        if($type == 'featured'){
            $category->featured = !$category->featured;
        }elseif($type == 'is_service_type'){
            $category->is_service_type = !$category->is_service_type;
        }else{
            $category->active = !$category->active;
        }
        $category->save();
        Toast::title('Category updated successfully');
        return redirect()->back();
    }

    private function validateData(Request $request, $id = null): array
    {
        $rules = [
            'name' => ['required','max:50','unique:categories,name,' . $id],
            'image' => 'nullable|file|mimes:jpeg,jpg,png',
            'active' => 'nullable',
            'featured' => 'nullable',
            'type' => 'nullable',
            'parent_id' => 'nullable',
            'service_id' => 'nullable',
            'description' => ['nullable','max:1000'],
//            'color' => 'nullable',
        ];



        $data = $request->validate($rules);

        if($request->hasFile('image')){
            $storageService = new StorageService();
            $path = 'categories';
            $data['image'] = $storageService
                ->uploadFile($request->file('image'),$path);
        }

        return $data;
    }

    public function getMain(Request $request){
        $service_id = $request->get('service_id');
        return Category::main()->where('service_id', $service_id)->get();
    }

}
