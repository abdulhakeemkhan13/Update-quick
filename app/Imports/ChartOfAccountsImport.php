<?php

namespace App\Imports;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;

class ChartOfAccountsImport implements ToCollection
{
    protected $failedRows = [];

    public function collection(Collection $rows)
    {
        $rows->shift(); // skip header

        $typeMapping = [
            // Liabilities
            'accounts payable (a/p)'     => 'Liabilities',
            'accounts payable'           => 'Liabilities',
            'credit card'                => 'Liabilities',
            'long term liabilities'      => 'Liabilities',
            'other current liabilities'  => 'Liabilities',
            'loan payable'               => 'Liabilities',
            'notes payable'              => 'Liabilities',
            'board of equalization payable' => 'Liabilities',
            'arizona dept. of revenue payable' => 'Liabilities',

            // Assets
            'accounts receivable (a/r)'  => 'Assets',
            'accounts receivable'        => 'Assets',
            'bank'                       => 'Assets',
            'checking'                   => 'Assets',
            'savings'                    => 'Assets',
            'undeposited funds'          => 'Assets',
            'inventory asset'            => 'Assets',
            'other current assets'       => 'Assets',
            'fixed assets'               => 'Assets',
            'truck'                      => 'Assets',

            // Equity
            'equity'                     => 'Equity',
            'opening balance equity'     => 'Equity',
            'retained earnings'          => 'Equity',

            // Income
            'income'                     => 'Income',
            'other income'               => 'Income',
            'sales of product income'    => 'Income',
            'service/fee income'         => 'Income',
            'sales'                      => 'Income',

            // Costs of Goods Sold
            'cost of goods sold'         => 'Costs of Goods Sold',
            'cogs'                       => 'Costs of Goods Sold',

            // Expenses
            'expenses'                   => 'Expenses',
            'other expense'              => 'Expenses',
            'marketing'                  => 'Expenses',
            'insurance'                  => 'Expenses',
            'utilities'                  => 'Expenses',
            'rent or lease'              => 'Expenses',
            'meals and entertainment'    => 'Expenses',
            'bank charges'               => 'Expenses',
            'depreciation'               => 'Expenses',
        ];

        foreach ($rows as $row) {
            $fullName   = trim($row[0]);
            $typeName   = strtolower(trim($row[1]));
            $detailType = trim($row[2]);

            if (!isset($typeMapping[$typeName])) {
                $this->failedRows[] = [
                    'Full name'   => $fullName,
                    'Type'        => $row[1],
                    'Detail type' => $detailType,
                    'Reason'      => 'Type not mapped',
                ];
                continue;
            }

            $systemTypeName = $typeMapping[$typeName];

            $type = ChartOfAccountType::where('name', $systemTypeName)->where('created_by', \Auth::user()->creatorId())->first();
            if (!$type) {
                $this->failedRows[] = [
                    'Full name'   => $fullName,
                    'Type'        => $row[1],
                    'Detail type' => $detailType,
                    'Reason'      => 'ChartOfAccountType not found',
                ];
                continue;
            }

            $subType = ChartOfAccountSubType::firstOrCreate([
                'type' => $type->id,
                'name' => $detailType,
                'created_by' => \Auth::user()->creatorId(),
            ]);
            
            $ch=ChartOfAccount::firstOrCreate([
                'name'     => $fullName,
                'type'     => $type->id,
                'sub_type' => $subType->id,
                'created_by' => \Auth::user()->creatorId(),
            ]);
        
        }
        

        // Export failed rows if any
        if (!empty($this->failedRows)) {
            $filename = 'not_uploaded_' . now()->format('Y_m_d_His') . '.xlsx';
            Excel::store(new class($this->failedRows) implements FromArray {
                protected $rows;
                public function __construct(array $rows)
                {
                    $this->rows = $rows;
                }
                public function array(): array
                {
                    return $this->rows;
                }
            }, $filename, 'local'); // stored in storage/app/

            session()->flash('failed_file', $filename);

        }

    }
    
}
