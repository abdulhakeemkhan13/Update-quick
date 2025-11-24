<?php

namespace App\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Services\DataTable;

class TaxableSalesSummaryDataTable extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        // ✅ Group by Product/Service
        $grouped = $data->groupBy('product_service');
        $final = collect();

        $grandTaxable = 0;
        $grandTax = 0;

        foreach ($grouped as $product => $rows) {
            $slug = \Str::slug($product);

            $subtotalTaxable = $rows->sum('taxable_amount');
            $subtotalTax = $rows->sum('tax_amount');

            // ✅ Parent row
            $final->push((object) [
                'isParent' => true,
                'product_service' => $product,
                'slug' => $slug,
                'taxable_amount' => '',
                'tax_amount' => '',
            ]);

            // ✅ Child rows
            foreach ($rows as $r) {
                $final->push((object) [
                    'isChild' => true,
                    'bucket' => $slug,
                    'invoice_number' => $r->invoice_number,
                    'customer_name' => $r->customer_name,
                    'taxable_amount' => $r->taxable_amount,
                    'tax_amount' => $r->tax_amount,
                ]);
            }

            // ✅ Subtotal row
            $final->push((object) [
                'isSubtotal' => true,
                'bucket' => $slug,
                'product_service' => $product,
                'taxable_amount' => $subtotalTaxable,
                'tax_amount' => $subtotalTax,
            ]);

            $grandTaxable += $subtotalTaxable;
            $grandTax += $subtotalTax;
        }

        // ✅ Grand total row
        $final->push((object) [
            'isGrandTotal' => true,
            'product_service' => 'Grand Total',
            'taxable_amount' => $grandTaxable,
            'tax_amount' => $grandTax,
        ]);

        return datatables()
            ->collection($final)
            ->editColumn('product_service', function ($row) {
                if (isset($row->isParent)) {
                    return '<span class="toggle-bucket" data-bucket="' . $row->slug . '">
                    <span class="icon">▼</span> <strong>' . e($row->product_service) . '</strong>
                </span>';
                } elseif (isset($row->isChild)) {
                    // return '↳ ' . e($row->customer_name);
                    return '' . e($row->customer_name);
                } elseif (isset($row->isSubtotal)) {
                    return '<strong>Subtotal for ' . e($row->product_service) . '</strong>';
                } elseif (isset($row->isGrandTotal)) {
                    return '<strong>Grand Total</strong>';
                }
                return e($row->product_service);
            })

            ->editColumn('taxable_amount', function ($row) {
                if (isset($row->isParent))
                    return '';
                return number_format($row->taxable_amount ?? 0, 0);
            })
            ->editColumn('tax_amount', function ($row) {
                if (isset($row->isParent))
                    return '';
                return number_format($row->tax_amount ?? 0, 0);
            })
            ->setRowClass(function ($row) {
                if (isset($row->isParent))
                    return 'parent-row toggle-bucket bucket-' . $row->slug;
                if (isset($row->isChild))
                    return 'child-row bucket-' . $row->bucket;
                if (isset($row->isSubtotal))
                    return 'subtotal-row bucket-' . $row->bucket;
                if (isset($row->isGrandTotal))
                    return 'grandtotal-row';
                return '';
            })
            ->rawColumns(['product_service']);
    }

    public function query()
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $ownerColumn = $user->type === 'company' ? 'invoices.created_by' : 'invoices.owned_by';

        // $start = request()->get('start_date') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        // $end = request()->get('end_date') ?? Carbon::now()->endOfDay()->format('Y-m-d');

        $start = request()->get('start_date')
            ?? request()->get('startDate')
            ?? Carbon::now()->startOfYear()->format('Y-m-d');

        $end = request()->get('end_date')
            ?? request()->get('endDate')
            ?? Carbon::now()->endOfDay()->format('Y-m-d');

        return DB::table('invoices')
            ->join('invoice_products', 'invoices.id', '=', 'invoice_products.invoice_id')
            ->join('product_services', 'invoice_products.product_id', '=', 'product_services.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->selectRaw('
                product_services.name as product_service,
                invoices.invoice_id as invoice_number,
                customers.name as customer_name,
                SUM(invoice_products.price * invoice_products.quantity) as taxable_amount,
                SUM((invoice_products.price * invoice_products.quantity) *
                    ((SELECT COALESCE(SUM(t.rate),0)
                      FROM taxes t
                      WHERE FIND_IN_SET(t.id, invoice_products.tax)) / 100)
                ) as tax_amount
            ')
            ->where($ownerColumn, $ownerId)
            ->whereBetween('invoices.issue_date', [$start, $end])
            ->whereNotNull('invoice_products.tax')
            ->where('invoice_products.tax', '!=', '')
            ->groupBy('product_services.name', 'invoices.invoice_id', 'customers.name')
            ->orderBy('product_services.name')
            ->orderBy('invoices.invoice_id');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('customer-balance-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('t')
            ->orderBy(0, 'asc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
            ]);
    }

    protected function getColumns()
    {
        return [
            ['data' => 'product_service', 'title' => 'Product/Service'],
            ['data' => 'taxable_amount', 'title' => 'Taxable Amount', 'class' => 'text-end'],
            ['data' => 'tax_amount', 'title' => 'Tax', 'class' => 'text-end'],
        ];
    }
}
