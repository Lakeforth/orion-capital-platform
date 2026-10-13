<?php

namespace App\Tables;

use App\Models\Product;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class ProductTable extends AbstractTable
{
    public function authorize(Request $request): bool
    {
        return true;
    }

    public function for()
    {
        return Product::query()->latest();
    }

    public function configure(SpladeTable $table): void
    {
        $table
            ->withGlobalSearch(columns: ['name'])
            ->defaultSort('created_at', 'desc')
            ->column('name', sortable: true)
            ->column('price', sortable: true)
            ->column('is_active', label: 'Active')
            ->column('actions')
            ->paginate(15);
    }
}
