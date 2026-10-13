<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Tables\ProductTable;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index', [
            'data' => ProductTable::class,
        ]);
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        Product::create($validated);

        Toast::success('Product created successfully.');

        return redirect()->route('admin.products.index');
    }

    public function edit(string $id)
    {
        $product = Product::findOrFail($id);

        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $validated = $this->validateData($request);

        $product->update($validated);

        Toast::success('Product updated successfully.');

        return redirect()->route('admin.products.index');
    }

    public function destroy(string $id)
    {
        Product::findOrFail($id)->delete();

        Toast::success('Product deleted successfully.');

        return redirect()->route('admin.products.index');
    }

    public function toggle(string $id)
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);

        return back();
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);
    }
}
