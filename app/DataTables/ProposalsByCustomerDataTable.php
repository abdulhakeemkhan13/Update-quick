<?php

namespace App\DataTables;

use App\Models\Proposal;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProposalsByCustomerDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('date', function ($proposal) {
                if ($proposal->is_customer_header ?? false) {
                    $customerId = $proposal->customer_id;
                    $chevron = '<i class="chevron-icon" data-parent-type="customer" data-parent-id="' . $customerId . '" style="margin-right: 10px; cursor: pointer; display: inline-block; transition: transform 0.2s;">▼</i>';
                    return '<strong title="' . e($proposal->customer_name) . '">' . $chevron . e($proposal->customer_name) . '</strong>';
                }
                if ($proposal->is_grand_total ?? false) {
                    return '<strong style="font-size: 14px;">TOTAL</strong>';
                }
                return $proposal->issue_date ? Carbon::parse($proposal->issue_date)->format('m/d/Y') : '-';
            })
            ->addColumn('num', function ($proposal) {
                if ($proposal->is_customer_header ?? false) {
                    return '';
                }
                if ($proposal->is_grand_total ?? false) {
                    return '';
                }
                return 'EST-' . str_pad($proposal->proposal_id, 4, '0', STR_PAD_LEFT);
            })
            ->addColumn('estimate_status', function ($proposal) {
                if ($proposal->is_customer_header ?? false) {
                    return '';
                }
                if ($proposal->is_grand_total ?? false) {
                    return '';
                }
                $statuses = Proposal::$statues;
                return isset($statuses[$proposal->status]) ? __($statuses[$proposal->status]) : 'Unknown';
            })
            ->addColumn('accepted_on', function ($proposal) {
                if ($proposal->is_customer_header ?? false) {
                    return '';
                }
                if ($proposal->is_grand_total ?? false) {
                    return '';
                }
                if ($proposal->status == 2) {
                    return $proposal->issue_date ? Carbon::parse($proposal->issue_date)->format('m/d/Y') : '-';
                }
                return '-';
            })
            ->addColumn('accepted_by', function ($proposal) {
                if ($proposal->is_customer_header ?? false) {
                    return '';
                }
                if ($proposal->is_grand_total ?? false) {
                    return '';
                }
                if ($proposal->status == 2) {
                    return $proposal->customer ? $proposal->customer->name : 'Customer';
                }
                return '-';
            })
            ->addColumn('expiration_date', function ($proposal) {
                if ($proposal->is_customer_header ?? false) {
                    return '';
                }
                if ($proposal->is_grand_total ?? false) {
                    return '';
                }
                if ($proposal->issue_date) {
                    $expDate = Carbon::parse($proposal->issue_date)->addDays(30);
                    return $expDate->format('m/d/Y');
                }
                return '-';
            })
            ->addColumn('invoice_number', function ($proposal) {
                if ($proposal->is_customer_header ?? false) {
                    return '';
                }
                if ($proposal->is_grand_total ?? false) {
                    return '';
                }
                if ($proposal->is_convert && $proposal->converted_invoice_id) {
                    return 'INV-' . str_pad($proposal->converted_invoice_id, 4, '0', STR_PAD_LEFT);
                }
                return '-';
            })
            ->addColumn('amount', function ($proposal) {
                if ($proposal->is_customer_header ?? false) {
                    $amount = number_format($proposal->customer_total ?? 0, 2);
                    return '<strong class="customer-total-amount">' . $amount . '</strong>';
                }
                if ($proposal->is_grand_total ?? false) {
                    $amount = number_format($proposal->customer_total ?? 0, 2);
                    return '<strong class="grand-total-amount">' . $amount . '</strong>';
                }
                $totalAmount = $proposal->getTotal();
                return number_format($totalAmount, 2);
            })
            ->addColumn('DT_RowClass', function ($proposal) {
                $classes = [];
                if ($proposal->is_customer_header ?? false) {
                    $classes[] = 'customer-header-row';
                    $classes[] = 'parent-customer-' . $proposal->customer_id;
                } elseif ($proposal->is_grand_total ?? false) {
                    $classes[] = 'grand-total-row';
                } else {
                    $classes[] = 'child-row';
                    $classes[] = 'child-of-customer-' . $proposal->customer_id;
                }
                return implode(' ', $classes);
            })
            ->addColumn('DT_RowData', function ($proposal) {
                $data = [];
                if ($proposal->is_customer_header ?? false) {
                    $data['customer-id'] = $proposal->customer_id;
                }
                return $data;
            })
            ->rawColumns(['amount', 'date']);
    }

    public function query(Proposal $model)
    {
        $user = Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';

        $query = $model->newQuery()
            ->select([
                'proposals.id as id',
                'proposals.proposal_id as proposal_id',
                'proposals.customer_id as customer_id',
                'proposals.issue_date as issue_date',
                'proposals.status as status',
                'proposals.converted_invoice_id as converted_invoice_id',
                'proposals.is_convert as is_convert',
                'proposals.created_by as created_by',
                'customers.name as customer_name'
            ])
            ->leftJoin('customers', 'proposals.customer_id', '=', 'customers.id')
            ->where('customers.is_active', 1)
            ->where('proposals.' . $column, $ownerId)
            ->whereIn('proposals.status',[1,4])
            ->whereNotNull('customers.name');

        if (request()->filled('customer_name') && request('customer_name') !== '') {
            $customerName = request('customer_name');
            $query->where('customers.name', 'LIKE', "%{$customerName}%");
        }

        if (request()->filled('status') && request('status') !== '') {
            $status = request('status');
            $query->where('proposals.status', $status);
        }

        if (request()->filled('startDate') && request()->filled('endDate')) {
            $startDate = request('startDate');
            $endDate = request('endDate');
            $query->whereBetween('proposals.issue_date', [$startDate, $endDate]);
        }
        $proposals = $query->orderBy('customers.name', 'asc')
                    ->orderBy('proposals.issue_date', 'desc')
                    ->get();

        return $this->buildHierarchicalReport($proposals);
    }

    private function buildHierarchicalReport(Collection $proposals)
    {
        $report = collect();
        $groupedByCustomer = $proposals->groupBy('customer_id');
        $grandTotal = 0;

        foreach ($groupedByCustomer as $customerId => $customerProposals) {
            $customerName = $customerProposals->first()->customer_name;
            $customerTotal = $customerProposals->sum(function ($p) {
                return $p->getTotal();
            });
            $grandTotal += $customerTotal;

            // Add customer header row
            $report->push((object)[
                'id' => 'customer_header_' . $customerId,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_total' => $customerTotal,
                'is_customer_header' => true,
                'proposal_id' => null,
                'issue_date' => null,
                'status' => null,
                'converted_invoice_id' => null,
                'is_convert' => null,
            ]);

            // Add proposal rows under each customer
            foreach ($customerProposals as $proposal) {
                $report->push($proposal);
            }
        }

        // Add grand total row
        $report->push((object)[
            'id' => 'grand_total_row',
            'customer_id' => null,
            'customer_name' => 'TOTAL',
            'customer_total' => $grandTotal,
            'is_grand_total' => true,
            'proposal_id' => null,
            'issue_date' => null,
            'status' => null,
            'converted_invoice_id' => null,
            'is_convert' => null,
        ]);

        return $report;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('proposals-by-customer-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt')
            ->parameters([
                'responsive' => false,
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
                'columnDefs' => [
                    ['width' => '22%', 'targets' => 0],
                    ['width' => '10%', 'targets' => 1],
                    ['width' => '12%', 'targets' => 2],
                    ['width' => '10%', 'targets' => 3],
                    ['width' => '13%', 'targets' => 4],
                    ['width' => '12%', 'targets' => 5],
                    ['width' => '10%', 'targets' => 6],
                    ['width' => '11%', 'targets' => 7],
                ],
                'createdRow' => "function(row, data, dataIndex) {
                    if (data.DT_RowClass) {
                        $(row).addClass(data.DT_RowClass);
                    }
                    if (data.DT_RowData) {
                        for (let key in data.DT_RowData) {
                            $(row).attr('data-' + key, data.DT_RowData[key]);
                        }
                    }
                }"
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('date')->title(__('Date'))->addClass('col-date'),
            Column::make('num')->title(__('Num'))->addClass('col-num'),
            Column::make('estimate_status')->title(__('Estimate Status'))->addClass('col-status'),
            Column::make('accepted_on')->title(__('Accepted On'))->addClass('col-accepted-on'),
            Column::make('accepted_by')->title(__('Accepted By'))->addClass('col-accepted-by'),
            Column::make('expiration_date')->title(__('Expiration Date'))->addClass('col-expiration'),
            Column::make('invoice_number')->title(__('Invoice Number'))->addClass('col-invoice'),
            Column::make('amount')->title(__('Amount'))->addClass('col-amount text-right'),
        ];
    }

    protected function filename(): string
    {
        return 'ProposalsByCustomer_' . date('YmdHis');
    }
}