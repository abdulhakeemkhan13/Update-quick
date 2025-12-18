<?php

namespace App\DataTables;

use App\Models\Vender;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Button;

class VendorsPhoneList extends DataTable
{
    protected $status;

    public function __construct()
    {
        parent::__construct();

        // Default: 2 months ago to today
    }
    public function dataTable($query)
    {
        return datatables()->eloquent($query)->editColumn('name', fn($row) => $row->name)
        ->editColumn('contact', fn($row) => $row->contact ? $row->contact : '-');
    }

    public function query(Vender $model)
    {
        $this->status = request('status', 1);
        return $model->newQuery()
            ->select('id', 'name', 'contact')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->where('is_active', $this->status);
    }

    public function html()
    {
        $tableId = 'customer-balance-table';
        $pageTitle = $this->pageTitle ?? 'Vendor Phone List';

        return $this->builder()
            ->setTableId($tableId)
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'asc')
            ->parameters([
                'paging' => false,
                'searching' => false,
                'info' => false,
                'ordering' => false,
                'responsive' => true,
                'fixedHeader' => true,
                'dom' => 'Bfrtip',
                'buttons' => [
                    // Button::make('excel')
                    //     ->text('<i class="fa fa-file-excel"></i> Excel')
                    //     ->action("exportDataTable('{$tableId}', '{$pageTitle}');"), // Call global JS export
                    // Button::make('print')->text('<i class="fa fa-print"></i> Print'),
                ],
            ]);
    }

    protected function getColumns()
    {
        return [
            Column::make('name')->title('Vendor'),
            Column::make('contact')->title('Phone Number')->addClass('text-nowrap'),
        ];
    }

    protected function filename(): string
    {
        return str_replace(' ', '_', $this->pageTitle ?? 'Vendor_Phone_List') . '_' . date('YmdHis');
    }
}
