<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Tables\DataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use ProtoneMedia\Splade\Facades\Toast;

class DataController extends Controller
{
    //    public function index($type){
    //        return view('admin.data.index', [
    //            'data' => new DataTable::class($type),
    //            'type' => $type
    //        ]);
    //    }

    public function desks()
    {
        return view('admin.data.index', [
            'data' => new DataTable('desk'),
            'type' => 'desk',
            'title' => 'Desks Listing',
        ]);
    }

    public function ecn()
    {
        return view('admin.data.index', [
            'data' => new DataTable('ecn'),
            'type' => 'ecn',
            'title' => 'ECN Asset Listing',
        ]);
    }

    public function teams()
    {
        return view('admin.data.index', [
            'data' => new DataTable('team'),
            'type' => 'team',
            'title' => 'Teams Listing',
        ]);
    }

    public function plans()
    {
        return view('admin.data.plans', [
            'data' => new DataTable('plan'),
            'type' => 'plan',
            'title' => 'Plan Features Listing',
        ]);
    }

    public function labels()
    {
        return view('admin.data.index', [
            'data' => new DataTable('label'),
            'type' => 'label',
            'title' => 'Label Listing',
        ]);
    }

    public function brands()
    {
        return view('admin.data.index', [
            'data' => new DataTable('brand'),
            'type' => 'brand',
            'title' => 'Brand Listing',
        ]);
    }

    public function sources()
    {
        return view('admin.data.index', [
            'data' => new DataTable('source'),
            'type' => 'source',
            'title' => 'Sources Listing',
        ]);
    }

    public function campaigns()
    {
        return view('admin.data.index', [
            'data' => new DataTable('campaign'),
            'type' => 'campaign',
            'title' => 'Campaigns Listing',
        ]);
    }

    public function status()
    {
        return view('admin.data.index', [
            'data' => new DataTable('status'),
            'type' => 'status',
            'title' => 'Status Listing',
        ]);
    }

    public function create(Request $request)
    {
        $default = [
            'type' => $request->get('type'),
            'icon_key' => 'sparkles',
            'use_image' => false,
            'is_active' => true,
        ];
        $type = $request->get('type');

        return view('admin.data.create', compact('default', 'type'));
    }

    public function edit($id, Request $request)
    {
        $data = Data::findOrFail($id);
        $type = $data->type ?? $request->get('type');
        $default = $data;

        return view('admin.data.edit', compact('default', 'data', 'type'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        Data::create($data);
        Toast::title($data['type'].' created successfully');

        return redirect()->back();
    }

    public function destroy($id)
    {
        $data = Data::findOrFail($id);
        $data->delete();
        Toast::title($data->name.' deleted');

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        $data = $this->validateData($request, $id);
        $dataFirst = Data::findOrFail($id);
        $dataFirst->update($data);
        Toast::title($dataFirst->name.' updated successfully');

        return redirect()->back();
    }

    public function toggle($id, $field): RedirectResponse
    {
        $data = Data::query()->where('type', 'plan')->findOrFail($id);

        if (! in_array($field, ['is_active'], true)) {
            Toast::danger('Invalid request');

            return redirect()->back();
        }

        $data->{$field} = ! $data->{$field};
        $data->save();

        Toast::title('Plan feature updated successfully');

        return redirect()->back();
    }

    private function validateData(Request $request, $id = null): array
    {
        $rules = [
            'name' => ['required', 'max:50'],
            'icon_key' => ['nullable', Rule::in(array_keys(customer_plan_feature_icon_options()))],
            'type' => ['required', 'max:50'],
            'image' => 'nullable',
            'use_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];

        $data = $request->validate($rules);
        $data['icon_key'] = $data['icon_key'] ?? 'sparkles';
        $data['use_image'] = $request->has('use_image')
            ? $request->boolean('use_image')
            : false;
        $data['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : true;

        return $data;
    }
}
