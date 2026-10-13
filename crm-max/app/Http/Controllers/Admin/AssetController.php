<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\CompanyAsset;
use App\Tables\AssetTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use ProtoneMedia\Splade\Facades\Toast;
use Throwable;

class AssetController extends Controller
{
    public function index()
    {
        return view('super.assets.index', [
            'data' => AssetTable::class,
            'stats' => $this->getAssetStats(),
        ]);
    }

    public function togglePosition($position, $id)
    {
        $item = Asset::find($id);

        if (in_array($position, ['up', 'down'])) {
            $item->togglePosition($position);
        }

        return redirect()->back();
    }

    public function listing()
    {
        session()->put('active_assets', app('company')->assets->assets ?? []);

        return view('admin.assets.listing', [
            'data' => new AssetTable('listing'),
        ]);
    }

    public function create()
    {
        return view('admin.assets.create');
    }

    public function getAssets()
    {
        return Asset::query()->tradable()->get();
    }

    public function syncAssets(): RedirectResponse
    {
        try {
            $exitCode = Artisan::call('app:asset-sync');

            if ($exitCode !== 0) {
                Toast::danger('Asset sync failed.');

                return redirect()->back();
            }

            Toast::success('Asset sync completed successfully.');

            return redirect()->back();
        } catch (Throwable $exception) {
            report($exception);
            Toast::danger('Asset sync failed.');

            return redirect()->back();
        }
    }

    public function store(Request $request)
    {
        $validated = $this->getData($request);

        Asset::create($validated);

        Toast::success('Asset created successfully.');

        return redirect()->back();
    }

    public function edit($id)
    {
        $asset = Asset::findOrFail($id);

        return view('super.assets.edit', compact('asset'));
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $this->getData($request);

        $asset->update($validated);

        Toast::success('Asset updated successfully.');

        return redirect()->back();
    }

    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();

        Toast::success('Asset deleted successfully.');

        return redirect()->back();
    }

    public function toggle($id, $field): RedirectResponse
    {
        $item = Asset::findOrFail($id);
        $item->{$field} = ! $item->{$field};
        $item->save();
        Toast::title('Asset updated successfully');

        return redirect()->back();
    }

    public function toggleStatus($id)
    {
        $company = app('company');

        $asset = Asset::findOrFail($id);

        $ca = CompanyAsset::firstOrCreate(
            ['company_id' => $company->id],
            ['assets' => []]
        );

        $assets = $ca->assets;

        if (in_array($asset->id, $assets, true)) {
            $assets = array_values(array_diff($assets, [$asset->id]));
        } else {
            $assets[] = $asset->id;
        }

        $ca->assets = $assets;

        $ca->save();

        Toast::title('Asset updated successfully');

        return redirect()->back();
    }

    protected function getData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'ex_sym' => 'nullable|string|max:50',
            'sym' => 'nullable|string|max:50',
            'rate' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'market_cap' => 'nullable|numeric|min:0',
            'change' => 'nullable|numeric',
            'change_percent' => 'nullable|numeric',
            'buy_spread' => 'nullable|numeric|min:0',
            'sell_spread' => 'nullable|numeric|min:0',
            'base' => 'nullable|string|max:10',
            'leverage' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'sym_key' => 'nullable|string|max:50',
            'coin_id' => 'nullable|string|max:50',
            'est_profit' => 'nullable|string|max:50',
            'live_market_end_time' => 'nullable|string|max:50',
            'type' => 'nullable|in:crypto,forex,stock',
        ]);
    }

    protected function getAssetStats(): array
    {
        return [
            [
                'title' => 'Total Assets',
                'value' => Asset::query()->count(),
            ],
            [
                'title' => 'Tradable Assets',
                'value' => Asset::query()->tradable()->count(),
            ],
            [
                'title' => 'Admin Disabled Assets',
                'value' => Asset::query()->where('is_active', false)->count(),
            ],
            [
                'title' => 'Provider Inactive Assets',
                'value' => Asset::query()->where('is_provider_active', false)->count(),
            ],
        ];
    }
}
