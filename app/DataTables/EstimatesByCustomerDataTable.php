<?php

namespace App\DataTables;

use App\Models\Proposal;
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
        // Fetch rows from the query
        $rows = $query->get();

        $final = collect();

        // Group by customer_id
        $grouped = $rows->groupBy('customer_id');

        $grandTotal = 0;

        foreach ($grouped as $customerId => $proposals) {
            $displayName = $proposals->first()->customer_name ?? 'Unknown Customer';

            // Calculate group total
            $groupTotal = 0;
            foreach ($proposals as $proposal) {
                $groupTotal += $this->calculateProposalAmountRaw($proposal);
            }
            $grandTotal += $groupTotal;

            // Add group header
            $final->push([
                'customer_id' => $customerId,
                'customer_name' => $displayName,
                'group_total' => $groupTotal,
                'formatted_total' => Auth::user()->priceFormat($groupTotal),
                'date' => '',
                'num' => '',
                'estimate_status' => '',
                'accepted_on' => '',
                'accepted_by' => '',
                'expiration_date' => '',
                'invoice_number' => '',
                'amount' => '',
                'is_group_header' => true,
            ]);

            // Add proposal rows
            foreach ($proposals as $proposal) {
                $final->push([
                    'customer_id' => $customerId,
                    'customer_name' => $displayName,
                    'date' => $proposal->issue_date ? Carbon::parse($proposal->issue_date)->format('m/d/Y') : '-',
                    'num' => 'EST-' . str_pad($proposal->proposal_id, 4, '0', STR_PAD_LEFT),
                    'estimate_status' => isset(Proposal::$statues[$proposal->status]) ? __(Proposal::$statues[$proposal->status]) : 'Unknown',
                    'accepted_on' => $proposal->status == 2 ? ($proposal->issue_date ? Carbon::parse($proposal->issue_date)->format('m/d/Y') : '-') : '-',
                    'accepted_by' => $proposal->status == 2 ? ($proposal->customer ? $proposal->customer->name : 'Customer') : '-',
                    'expiration_date' => $proposal->issue_date ? Carbon::parse($proposal->issue_date)->addDays(30)->format('m/d/Y') : '-',
                    'invoice_number' => $proposal->is_convert && $proposal->converted_invoice_id ? 'INV-' . str_pad($proposal->converted_invoice_id, 4, '0', STR_PAD_LEFT) : '-',
                    'amount' => Auth::user()->priceFormat($this->calculateProposalAmountRaw($proposal)),
                    'is_group_header' => false,
                ]);
            }

            // Add customer total row
            $final->push([
                'customer_id' => $customerId,
                'customer_name' => $displayName,
                'date' => 'Total',
                'num' => '',
                'estimate_status' => '',
                'accepted_on' => '',
                'accepted_by' => '',
                'expiration_date' => '',
                'invoice_number' => '',
                'amount' => $groupTotal,
                'is_group_header' => false,
                'is_customer_total' => true,
            ]);
        }

        // Add grand total row
        $final->push([
            'customer_id' => null,
            'customer_name' => 'TOTAL',
            'group_total' => $grandTotal,
            'date' => 'TOTAL',
            'num' => '',
            'estimate_status' => '',
            'accepted_on' => '',
            'accepted_by' => '',
            'expiration_date' => '',
            'invoice_number' => '',
            'amount' => $grandTotal,
            'is_group_header' => false,
            'is_grand_total' => true,
        ]);

        return datatables()
            ->collection($final)
            ->addColumn('date', function ($r) {
                if (!empty($r['is_group_header'])) {
                    $customerId = $r['customer_id'];
                    $chevron = '<i class="chevron-icon" data-parent-type="customer" data-parent-id="' . $customerId . '" style="margin-right: 10px; cursor: pointer; display: inline-block; transition: transform 0.2s;">▼</i>';
                    return '<strong title="' . e($r['customer_name']) . '">' . $chevron . e($r['customer_name']) . '</strong>';
                }
                if (!empty($r['is_grand_total'])) {
                    return '<strong>' . e($r['date']) . '</strong>';
                }
                if (!empty($r['is_customer_total'])) {
                    return '<strong>' . e($r['date']) . '</strong>';
                }
                return e($r['date']);
            })
            ->addColumn('num', fn($r) => $r['is_group_header'] ? '' : e($r['num']))
            ->addColumn('estimate_status', fn($r) => $r['is_group_header'] ? '' : e($r['estimate_status']))
            ->addColumn('accepted_on', fn($r) => $r['is_group_header'] ? '' : e($r['accepted_on']))
            ->addColumn('accepted_by', fn($r) => $r['is_group_header'] ? '' : e($r['accepted_by']))
            ->addColumn('expiration_date', fn($r) => $r['is_group_header'] ? '' : e($r['expiration_date']))
            ->addColumn('invoice_number', fn($r) => $r['is_group_header'] ? '' : e($r['invoice_number']))
            ->addColumn('amount', function ($r) {
                if (!empty($r['is_grand_total'])) {
                    return '<strong>' . Auth::user()->priceFormat($r['amount']) . '</strong>';
                }
                if (!empty($r['is_customer_total'])) {
                    return '<strong>' . Auth::user()->priceFormat($r['amount']) . '</strong>';
                }
                return $r['is_group_header'] ? '' : $r['amount'];
            })
            ->setRowAttr([
                'class' => function ($r) {
                    $classes = [];
                    if (!empty($r['is_group_header'])) {
                        $classes[] = 'customer-header-row';
                        $classes[] = 'parent-customer-' . $r['customer_id'];
                    } elseif (!empty($r['is_grand_total'])) {
                        $classes[] = 'grand-total-row';
                    } elseif (!empty($r['is_customer_total'])) {
                        $classes[] = 'customer-total-row';
                        $classes[] = 'child-of-customer-' . $r['customer_id'];
                    } else {
                        $classes[] = 'child-row';
                        $classes[] = 'child-of-customer-' . $r['customer_id'];
                    }
                    return implode(' ', $classes);
                },
                'data-customer-id' => function ($r) {
                    return $r['customer_id'];
                },
                'data-customer-name' => function ($r) {
                    return $r['customer_name'];
                },
                'data-formatted-total' => function ($r) {
                    return $r['formatted_total'] ?? '';
                },
                'style' => function ($r) {
                    if (!empty($r['is_grand_total'])) {
                        return 'background-color:#e8f4f8; font-weight:700;';
                    }
                    if (!empty($r['is_customer_total'])) {
                        return 'background-color:#f0f8ff; font-weight:600;';
                    }
                    return !empty($r['is_group_header'])
                        ? 'background-color:#f8f9fa; font-weight:600; cursor:pointer;'
                        : 'display: table-row;';
                },
            ])
            ->rawColumns(['date', 'amount']);
    }

    private function calculateProposalAmountRaw($proposal)
    {
        $totalAmount = 0;
        foreach ($proposal->items as $item) {
            $totalAmount += ($item->price * $item->quantity) + (($item->price * $item->quantity * $item->tax) / 100) - $item->discount;
        }
        return $totalAmount;
    }

    public function query(Proposal $model)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        // Build query to get proposals with customer information
        $query = $model->newQuery()
            ->select([
                'proposals.id',
                'proposals.proposal_id',
                'proposals.customer_id',
                'proposals.issue_date',
                'proposals.status',
                'proposals.converted_invoice_id',
                'proposals.is_convert',
                'proposals.created_by',
                'customers.name as customer_name'
            ])
            // Join with customers
            ->leftJoin('customers', 'proposals.customer_id', '=', 'customers.id')
            // Filter by created_by
            ->where('proposals.created_by', $ownerId)
            // Only show proposals that have a customer
            ->whereNotNull('customers.name');

        // Apply filters from request
        if (request()->filled('customer_name') && request('customer_name') !== '') {
            $customerName = request('customer_name');
            $query->where('customers.name', 'LIKE', "%{$customerName}%");
        }

        if (request()->filled('status') && request('status') !== '') {
            $status = request('status');
            $query->where('proposals.status', $status);
        }

        $startDate = request('startDate', date('Y-01-01'));
        $endDate = request('endDate', date('Y-m-d'));
        $query->whereBetween('proposals.issue_date', [$startDate, $endDate]);
        // dd($query->get(),$query->first());
        return $query->orderBy(DB::raw("REPLACE(REPLACE(customers.name, ' - ', '-'), '- ', '-')"), 'asc')
                    ->orderBy('proposals.issue_date', 'asc');
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('proposals-by-customer-table')
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
                'createdRow' => "function(row, data, dataIndex) {
                    if (data.is_group_header) {
                        $('td', row).eq(0).attr('colspan', 8);
                        $('td', row).slice(1).remove();
                    }
                    if (data.is_grand_total) {
                        $('td', row).eq(0).css('text-align', 'left');
                    }
                }",
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
        ];
    }

    protected function filename(): string
    {
        return 'EstimatesByCustomer_' . date('YmdHis');
    }
}