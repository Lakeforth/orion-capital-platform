<?php

namespace App\Tables;

use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class AssetTable extends AbstractTable
{
    public function __construct(public ?string $type = null) {}

    public function authorize(Request $request)
    {
        return true;
    }

    /**
     * The resource or query builder.
     *
     * @return mixed
     */
    public function for()
    {
        $query = Asset::query();
        if ($this->type) {
            $query->tradable();
        }

        return $query;
    }

    public function configure(SpladeTable $table)
    {

        $table
            ->withGlobalSearch(columns: ['name', 'sym', 'ex_sym', 'sym_key', 'coin_id'])
            ->defaultSort('position', 'asc')
            ->column('name', sortable: true)
            ->column('sym', label: 'Symbol', sortable: true)
            ->column('rate', sortable: true, as: fn ($value) => number_format((float) $value, 4))
            ->when(! $this->type, function (SpladeTable $table) {
                $table
                    ->column('is_active', label: 'Status', sortable: true)
                    ->column('is_provider_active', label: 'Provider Status', sortable: true)
                    ->column('ex_sym', label: 'Exchange Symbol', sortable: true)
                    ->column('sym_key', label: 'Provider Symbol', hidden: true, sortable: true)
                    ->column('coin_id', label: 'Provider ID', hidden: true, sortable: true)
                    ->column('price_low', label: '24h Low', sortable: true, as: fn ($value) => number_format((float) $value, 4))
                    ->column('price_high', label: '24h High', sortable: true, as: fn ($value) => number_format((float) $value, 4))
                    ->column(
                        'updated_at',
                        label: 'Last Updated',
                        sortable: true,
                        as: fn ($value) => $value ? Carbon::parse($value)->format('M d, Y H:i') : '—'
                    );
            })
//            ->column('leverage',sortable: true)
            ->column(
                key: 'volume',
                sortable: true,
                as: fn ($value) => number_format((float) $value)
            )
//            ->column(
//                key: 'market_cap',
//                label: 'Market Cap',
//                sortable: true,
//                as: fn($value) => number_format($value)
//            )
            ->column(
                key: 'change_percent',
                label: '% Change',
                sortable: true,
                as: fn ($value) => number_format((float) $value, 2).'%'
            )
            ->column('position', sortable: true)
            ->column('type', sortable: true)
            ->when($this->type, function (SpladeTable $table) {
                $table->column('is_active', label: 'Status');
            })
            ->when(! $this->type, function (SpladeTable $table) {
                $table->column('actions');
            })
            ->selectFilter(
                key: 'type',
                options: get_asset_types()
            )
            ->when(! $this->type, function (SpladeTable $table) {
                $table
                    ->selectFilter(
                        key: 'is_active',
                        options: [
                            1 => 'Active',
                            0 => 'Inactive',
                        ]
                    )
                    ->selectFilter(
                        key: 'is_provider_active',
                        options: [
                            1 => 'Provider Active',
                            0 => 'Provider Inactive',
                        ]
                    );
            })
            ->when($this->type, function (SpladeTable $table) {
                $table->selectFilter(
                    key: 'is_active',
                    options: [
                        1 => 'Enabled',
                    ]
                );
            })
            ->paginate(15);
    }
}
