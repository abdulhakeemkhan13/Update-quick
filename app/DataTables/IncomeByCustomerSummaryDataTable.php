<?php

namespace App\DataTables;

use App\Models\ProductService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class IncomeByCustomerSummaryDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('full_name', fn($r) => $r->name)
            ->addColumn('type', fn($r) => ucwords($r->type))
            ->addColumn('memo_description', fn($r) => $r->description ?? '-')
            ->addColumn('sales_price', fn($r) =>$r->sale_price)
            ->addColumn('purchase_price', fn($r) =>$r->purchase_price)
            ->addColumn('quantity_on_hand', fn($r) => $r->type === 'product' ? (string) (float) $r->total_quantity : '-');
    }

public function query(ProductService $model)
{
    $user = Auth::user();
    $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

    // Movement mapping for calculating total quantity
    $incoming = ['bill','purchase','vendor_bill','stock_in','opening','adjustment_in','transfer_in','credit_note_in','manually'];
    $outgoing = ['invoice','sale','proposal','stock_out','adjustment_out','transfer_out','debit_note_out'];

    // Sum all stock movements to get total quantity on hand
    $stockAgg = DB::table('stock_reports as sr')
        ->select('sr.product_id', DB::raw("
            SUM(CASE WHEN sr.type IN ('" . implode("','", $incoming) . "') THEN sr.quantity ELSE 0 END)
          - SUM(CASE WHEN sr.type IN ('" . implode("','", $outgoing) . "') THEN sr.quantity ELSE 0 END)
          AS total_quantity
        "))
        ->where('sr.created_by', $ownerId)
        ->groupBy('sr.product_id');

    // Get all products
    $q = $model->newQuery()
        ->with(['category','unit'])
        ->where('product_services.created_by', $ownerId)
        ->leftJoinSub($stockAgg, 'sr_agg', function ($join) {
            $join->on('product_services.id', '=', 'sr_agg.product_id');
        })
        ->addSelect('product_services.*', DB::raw('COALESCE(sr_agg.total_quantity, 0) as total_quantity'))
        // Filter out products with colon in name where the base product already exists
        ->where(function($query) use ($ownerId) {
            $query->where(function($q) {
                // Include products without colon in their name
                $q->where('product_services.name', 'NOT LIKE', '%:%');
            })->orWhere(function($q) use ($ownerId) {
                // Include products with colon ONLY if the base name (after colon) doesn't exist
                $q->where('product_services.name', 'LIKE', '%:%')
                  ->whereNotExists(function($subquery) use ($ownerId) {
                      $subquery->select(DB::raw(1))
                          ->from('product_services as ps2')
                          ->where('ps2.created_by', $ownerId)
                          ->whereRaw("ps2.name = TRIM(SUBSTRING_INDEX(product_services.name, ':', -1))")
                          ->where('ps2.name', 'NOT LIKE', '%:%');
                  });
            });
        });

    // Apply category and type filters if provided
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
            ->setTableId('inventory-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => true,
                'autoWidth'  => false,
                'paging'     => false,
                'searching'  => false,
                'info'       => false,
                'ordering'   => false,
                'colReorder' => true,
                'fixedHeader'=> true,
                'scrollY'    => '420px',
                'scrollX'    => true,
                'scrollCollapse' => true,
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('full_name')->data('full_name')->name('name')->title(__('Product/Service Full Name')),
            Column::make('type')->title(__('Type')),
            Column::make('memo_description')->data('memo_description')->name('description')->title(__('Memo/Description')),
            Column::make('sales_price')->data('sales_price')->name('sale_price')->title(__('Sales Price'))->addClass('text-right'),
            Column::make('purchase_price')->title(__('Purchase Price'))->addClass('text-right'),
            Column::make('quantity_on_hand')->data('quantity_on_hand')->name('total_quantity')->title(__('Quantity On Hand'))->addClass('text-right'),
        ];
    }

    protected function filename(): string
    {
        return 'InventoryValuationSummary_'.date('YmdHis');
    }
}
