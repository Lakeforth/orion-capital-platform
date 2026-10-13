<?php

namespace App\Tables;

use App\Models\Transaction;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class WithdrawalTable extends AbstractTable
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public ?string $user_id;

    public function __construct($user_id = null)
    {
        $this->user_id = $user_id;
    }

    /**
     * Determine if the user is authorized to perform bulk actions and exports.
     *
     * @return bool
     */
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
        $query = Transaction::query()->with('user')->whereIn('type',['withdrawal','w','system_withdrawal']);

        if (! is_admin()) {
            $query->whereHas('user', function ($builder) {
                $builder->where('manager_id', auth()->id());
            });
        }

        if ($this->user_id) {
            $query->where('user_id', $this->user_id);
        }

        if (request('from')) {
            $query->where('created_at', '>=', Carbon::parse(request('from'))->startOfDay());
        }
        if (request('to')) {
            $query->where('created_at', '<=', Carbon::parse(request('to'))->endOfDay());
        }

        return $query;
    }


    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['amount','description'])
            ->defaultSort('created_at','desc')
            ->column('user_id', sortable: true)
            ->column('amount', sortable: true)
            ->column('status', sortable: true)
            ->column('method', sortable: true)
            ->column('description', hidden: true, sortable: true)
            ->column('details', sortable: true)
            ->column('created_at', label: 'date', sortable: true)
            ->column('actions', sortable: true)
            ->paginate(10);

            // ->searchInput()
            // ->selectFilter()
            // ->withGlobalSearch()

            // ->bulkAction()
            // ->export()
    }
}
