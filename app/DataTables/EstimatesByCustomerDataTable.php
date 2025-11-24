<?php

namespace App\DataTables;

use App\Models\Quotation;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;

class EstimatesByCustomerDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('date', function ($quotation) {
                return $quotation->quotation_date ? Carbon::parse($quotation->quotation_date)->format('m/d/Y') : '-';
            })
            ->addColumn('num', function ($quotation) {
                return 'QUO-' . str_pad($quotation->quotation_id, 4, '0', STR_PAD_LEFT);
            })
            ->addColumn('estimate_status', function ($quotation) {
                switch ($quotation->status) {
                    case 0:
                        return 'Pending';
                    case 1:
                        return 'Accepted';
                    case 2:
                        return 'Rejected';
                    case 3:
                        return 'Expired';
                    default:
                        return 'Unknown';
                }
            })
            ->addColumn('accepted_on', function ($quotation) {
                // Assuming we store accepted date in a field, for now returning N/A
                return $quotation->status == 1 ? ($quotation->quotation_date ? Carbon::parse($quotation->quotation_date)->format('m/d/Y') : '-') : '-';
            })
            ->addColumn('accepted_by', function ($quotation) {
                // Assuming we store who accepted, for now returning N/A
                return $quotation->status == 1 ? 'Customer' : '-';
            })
            ->addColumn('expiration_date', function ($quotation) {
                // Calculate expiration date as 30 days from quotation date
                if ($quotation->quotation_date) {
                    $expDate = Carbon::parse($quotation->quotation_date)->addDays(30);
                    return $expDate->format('m/d/Y');
                }
                return '-';
            })
            ->addColumn('invoice_number', function ($quotation) {
                // Check if quotation is converted to invoice
                if ($quotation->is_converted && $quotation->converted_pos_id) {
                    return 'INV-' . str_pad($quotation->converted_pos_id, 4, '0', STR_PAD_LEFT);
                }
                return '-';
            })
            ->addColumn('amount', function ($quotation) {
                $totalAmount = 0;
                foreach ($quotation->items as $item) {
                    $totalAmount += ($item->price * $item->quantity) + (($item->price * $item->quantity * $item->tax) / 100) - $item->discount;
                }
                return Auth::user()->priceFormat($totalAmount);
            })
            ->addColumn('customer_name', function ($quotation) {
                return $quotation->customer ? $quotation->customer->name : 'Unknown Customer';
            })
            ->rawColumns(['amount']);
    }

    public function query(Quotation $model)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();

        // Build query to get quotations with customer information
        $query = $model->newQuery()
            ->select([
                'quotations.id',
                'quotations.quotation_id',
                'quotations.customer_id',
                'quotations.quotation_date',
                'quotations.status',
                'quotations.converted_pos_id',
                'quotations.is_converted',
                'quotations.created_by',
                'customers.name as customer_name'
            ])
            // Join with customers
            ->leftJoin('customers', 'quotations.customer_id', '=', 'customers.id')
            // Filter by created_by
            ->where('quotations.created_by', $ownerId)
            // Only show quotations that have a customer
            ->whereNotNull('customers.name');

        // Apply filters from request
        if (request()->filled('customer_name') && request('customer_name') !== '') {
            $customerName = request('customer_name');
            $query->where('customers.name', 'LIKE', "%{$customerName}%");
        }

        if (request()->filled('status') && request('status') !== '') {
            $status = request('status');
            $query->where('quotations.status', $status);
        }

        if (request()->filled('start_date') && request()->filled('end_date')) {
            $startDate = request('start_date');
            $endDate = request('end_date');
            $query->whereBetween('quotations.quotation_date', [$startDate, $endDate]);
        }

        // Group by quotation fields to avoid duplicates
        $query->groupBy([
            'quotations.id',
            'quotations.quotation_id',
            'quotations.customer_id',
            'quotations.quotation_date',
            'quotations.status',
            'quotations.converted_pos_id',
            'quotations.is_converted',
            'quotations.created_by',
            'customers.name'
        ]);

        return $query->orderBy('quotations.quotation_date', 'desc')
                    ->orderBy('customers.name', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('estimates-by-customer-table')
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
                'rowGroup' => [
                    'dataSrc' => 'customer_name'
                ]
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('date')->title(__('Date'))->addClass('text-center'),
            Column::make('num')->title(__('Num')),
            Column::make('estimate_status')->title(__('Estimate Status')),
            Column::make('accepted_on')->title(__('Accepted On'))->addClass('text-center'),
            Column::make('accepted_by')->title(__('Accepted By')),
            Column::make('expiration_date')->title(__('Expiration Date'))->addClass('text-center'),
            Column::make('invoice_number')->title(__('Invoice Number')),
            Column::make('amount')->title(__('Amount'))->addClass('text-right'),
            Column::make('customer_name')->title(__('Customer'))->visible(false), // Hidden but used for grouping
        ];
    }

    protected function filename(): string
    {
        return 'EstimatesByCustomer_' . date('YmdHis');
    }
}