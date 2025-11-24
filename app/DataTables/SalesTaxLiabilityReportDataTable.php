<?php

namespace App\DataTables;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\Services\DataTable;

class SalesTaxLiabilityReportDataTable extends DataTable
{
    public function dataTable($query)
    {
        $data = collect($query->get());

        // ✅ Group by tax agency and rate
        $grouped = $data->groupBy(function ($row) {
            return $row->tax_name . ' (' . $row->tax_rate . '%)';
        });

        $final = collect();

        $grandTaxable = 0;
        $grandNonTaxable = 0;
        $grandTax = 0;

        foreach ($grouped as $groupName => $rows) {
            $slug = \Str::slug($groupName);

            $subtotalTaxable = $rows->sum('taxable_sales');
            $subtotalNonTaxable = $rows->sum('nontaxable_sales');
            $subtotalTax = $rows->sum('tax_collected');

            // ✅ Parent (Tax agency/rate)
            $final->push((object) [
                'isParent' => true,
                'slug' => $slug,
                'tax_name' => $groupName,
            ]);

            // ✅ Child (Invoices)
            foreach ($rows as $r) {
                $final->push((object) [
                    'isChild' => true,
                    'bucket' => $slug,
                    'invoice_number' => $r->invoice_number,
                    'issue_date' => $r->issue_date,
                    'customer_name' => $r->customer_name,
                    'taxable_sales' => $r->taxable_sales,
                    'nontaxable_sales' => $r->nontaxable_sales,
                    'tax_collected' => $r->tax_collected,
                ]);
            }

            // ✅ Subtotal (per tax rate)
            $final->push((object) [
                'isSubtotal' => true,
                'bucket' => $slug,
                'tax_name' => $groupName,
                'taxable_sales' => $subtotalTaxable,
                'nontaxable_sales' => $subtotalNonTaxable,
                'tax_collected' => $subtotalTax,
            ]);

            $grandTaxable += $subtotalTaxable;
            $grandNonTaxable += $subtotalNonTaxable;
            $grandTax += $subtotalTax;
        }

        // ✅ Grand total row
        $final->push((object) [
            'isGrandTotal' => true,
            'tax_name' => 'Grand Total',
            'taxable_sales' => $grandTaxable,
            'nontaxable_sales' => $grandNonTaxable,
            'tax_collected' => $grandTax,
        ]);

        return datatables()
            ->collection($final)
            ->editColumn('tax_name', function ($row) {
                if (isset($row->isParent)) {
                    return '<span class="toggle-bucket" data-bucket="' . $row->slug . '">
                        <span class="icon">▼</span> <strong>' . e($row->tax_name) . '</strong>
                    </span>';
                } elseif (isset($row->isChild)) {
                    return e($row->customer_name) . ' <small>(' . e($row->invoice_number) . ')</small>';
                } elseif (isset($row->isSubtotal)) {
                    return '<strong>Subtotal for ' . e($row->tax_name) . '</strong>';
                } elseif (isset($row->isGrandTotal)) {
                    return '<strong>Grand Total</strong>';
                }
                return e($row->tax_name);
            })
            ->editColumn('taxable_sales', fn($row) => isset($row->isParent) ? '' : number_format($row->taxable_sales ?? 0, 2))
            ->editColumn('nontaxable_sales', fn($row) => isset($row->isParent) ? '' : number_format($row->nontaxable_sales ?? 0, 2))
            ->editColumn('tax_collected', fn($row) => isset($row->isParent) ? '' : number_format($row->tax_collected ?? 0, 2))
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
            ->rawColumns(['tax_name']);
    }

    public function query()
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $ownerColumn = $user->type === 'company' ? 'invoices.created_by' : 'invoices.owned_by';

        $start = request()->get('startDate') ?? Carbon::now()->startOfYear()->format('Y-m-d');
        $end = request()->get('endDate') ?? Carbon::now()->endOfDay()->format('Y-m-d');


        return DB::table('invoices')
            ->join('invoice_products', 'invoices.id', '=', 'invoice_products.invoice_id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('taxes', DB::raw("FIND_IN_SET(taxes.id, invoice_products.tax)"), ">", DB::raw("0"))
            ->selectRaw('
                taxes.name as tax_name,
                taxes.rate as tax_rate,
                invoices.invoice_id as invoice_number,
                invoices.issue_date,
                customers.name as customer_name,
                SUM(CASE WHEN invoice_products.tax IS NOT NULL AND invoice_products.tax != "" THEN invoice_products.price * invoice_products.quantity ELSE 0 END) as taxable_sales,
                SUM(CASE WHEN invoice_products.tax IS NULL OR invoice_products.tax = "" THEN invoice_products.price * invoice_products.quantity ELSE 0 END) as nontaxable_sales,
                SUM((invoice_products.price * invoice_products.quantity) * (COALESCE(taxes.rate, 0) / 100)) as tax_collected
            ')
            ->where($ownerColumn, $ownerId)
            ->whereBetween('invoices.issue_date', [$start, $end])
            ->groupBy('taxes.name', 'taxes.rate', 'invoices.invoice_id', 'invoices.issue_date', 'customers.name')
            ->orderBy('taxes.name')
            ->orderBy('taxes.rate');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('sales-tax-liability-report-table')
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
            ['data' => 'tax_name', 'title' => 'Tax Agency / Rate'],
            ['data' => 'taxable_sales', 'title' => 'Taxable Sales', 'class' => 'text-end'],
            ['data' => 'nontaxable_sales', 'title' => 'Non-Taxable Sales', 'class' => 'text-end'],
            ['data' => 'tax_collected', 'title' => 'Tax Collected', 'class' => 'text-end'],
        ];
    }
}
