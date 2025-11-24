<?php

namespace App\DataTables;

use App\Models\ProductService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class InventoryValuationSummaryDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('sale_price', fn($r) => $r->sale_price)
            ->addColumn('purchase_price', fn($r) => $r->purchase_price)
            ->addColumn('category', fn($r) => $r->category->name ?? '-')
            ->addColumn('unit', fn($r) => $r->unit->name ?? '-')
            ->addColumn('tax', function ($r) {
                if (empty($r->tax_id)) {
                    return '-';
                }

                $out = [];
                $taxData = \App\Models\Utility::getTaxData();

                foreach (explode(',', $r->tax_id) as $id) {
                    if (!isset($taxData[$id])) {
                        continue;
                    }

                    $out[] = $taxData[$id]['name'] . ' (' . $taxData[$id]['rate'] . '%)';
                }

                return implode('<br>', $out);
            })
            // Show computed on-hand quantity AS OF end date (only for products)
            ->addColumn('quantity', fn($r) => $r->type === 'product' ? (string) (float) $r->qty_as_of : '-')
            ->addColumn('type', fn($r) => ucwords($r->type))
            ->rawColumns(['tax']);
    }

    public function query(ProductService $model)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // ---- AS-OF quantity: use only "To" (end_date) ----
        $endDT = request()->get('end_date') ?? request()->get('endDate') ?? date('Y-m-d');
        // Movement mapping (edit as your app uses)
        $incoming = [
            'bill',
            'purchase',
            'vendor_bill',
            'stock_in',
            'opening',
            'adjustment_in',
            'transfer_in',
            'credit_note_in',
            'manually'
        ];

        $outgoing = [
            'invoice',
            'sale',
            'proposal',
            'stock_out',
            'adjustment_out',
            'transfer_out',
            'debit_note_out'
        ];

        // Sum movement <= end date
        $stockAgg = DB::table('stock_reports as sr')
            ->select(
                'sr.product_id',
                DB::raw("
                    SUM(CASE WHEN sr.type IN ('" . implode("','", $incoming) . "') THEN sr.quantity ELSE 0 END)
                    - SUM(CASE WHEN sr.type IN ('" . implode("','", $outgoing) . "') THEN sr.quantity ELSE 0 END)
                    AS qty_as_of
                ")
            )
            ->where('sr.created_by', $ownerId)
            ->where('sr.created_at', '<=', $endDT)
            ->groupBy('sr.product_id');

        // Products list (NO date filter here)
        $q = $model->newQuery()
            ->with(['category', 'unit'])
            ->where('product_services.created_by', $ownerId)
            ->where('product_services.created_at', '<=', $endDT)
            ->leftJoinSub($stockAgg, 'sr_agg', function ($join) {
                $join->on('product_services.id', '=', 'sr_agg.product_id');
            })
            ->addSelect('product_services.*', DB::raw('COALESCE(sr_agg.qty_as_of, 0) as qty_as_of'));

        // Category / Type filters — independent of dates
        if (request()->filled('category') && request('category') !== '') {
            $q->where('product_services.category_id', request('category'));
        }

        if (request()->filled('type') && request('type') !== '') {
            $q->where('product_services.type', request('type'));
        }

        return $q;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => true,
                'autoWidth' => false,
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'colReorder' => true,
                'fixedHeader' => true,
                'scrollY' => '420px',
                'scrollX' => true,
                'scrollCollapse' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('name')->title(__('Name')),
            Column::make('sku')->title(__('Sku')),
            Column::make('sale_price')->title(__('Sale Price'))->addClass('text-right'),
            Column::make('purchase_price')->title(__('Purchase Price'))->addClass('text-right'),
            Column::make('tax')->title(__('Tax')),
            Column::make('category')->title(__('Category')),
            Column::make('unit')->title(__('Unit')),
            // We keep the key as "quantity" to match your JS columns config
            Column::make('quantity')
                ->data('quantity')
                ->name('qty_as_of')
                ->title(__('Quantity'))
                ->addClass('text-right'),
            Column::make('type')->title(__('Type')),
        ];
    }

    protected function filename(): string
    {
        return 'InventoryValuationSummary_' . date('YmdHis');
    }
}
