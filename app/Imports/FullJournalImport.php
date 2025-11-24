<?php

namespace App\Imports;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\TransactionLines;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Stripe\InvoiceItem;

class FullJournalImport implements ToCollection, WithHeadingRow
{
    public function headingRow(): int
    {
        // Your headers start on row 4 in Excel
        return 0;
    }

    public function collection(Collection $rows)
    {
        $grouped = [];
        $currentGroup = null;

        // Define column mapping
        $columns = [
            0 => 'order',
            1 => 'transaction_date',
            2 => 'transaction_type',
            3 => 'num',
            4 => 'name',
            5 => 'memo_description',
            6 => 'full_name',
            7 => 'debit',
            8 => 'credit',
            9 => 'distribution_account_type',
            10 => 'rate',
            11 => 'quantity',
            12 => 'product_service_full_name',
        ];

        foreach ($rows as $row) {
            // Map numeric indices to named keys
            $rowData = [];
            foreach ($columns as $index => $name) {
                $rowData[$name] = $row[$index] ?? null;
            }

            $order = trim((string) ($rowData['order'] ?? ''));

            // Skip header rows
            if (strtolower($order) === 'order') {
                continue;
            }

            // If it's a group start (e.g. "4", "90", "10", "95", etc.)
            if ($order !== '' && !str_starts_with(strtolower($order), 'total for')) {
                $currentGroup = $order;
                $grouped[$currentGroup] = [];
                continue;
            }
                if (str_starts_with(strtolower($order), 'total for')) {
                    // Group ended — create invoice for this group
                    if (!empty($grouped[$currentGroup])) {
                        if($grouped[$currentGroup][0]['transaction_type'] == 'Invoice'){
                            $this->createInvoice($currentGroup, $grouped[$currentGroup]);
                        }elseif($grouped[$currentGroup][0]['transaction_type'] == 'Bill'){
                            $this->createBill($currentGroup, $grouped[$currentGroup]);
                        }elseif($grouped[$currentGroup][0]['transaction_type'] == 'Deposit'){
                            $this->createDeposit($currentGroup, $grouped[$currentGroup]);
                        }
                    }
                    $currentGroup = null;
                    continue;
                }

            // If it's a total row ("Total for X") — end of current group
            if (str_starts_with(strtolower($order), 'total for')) {
                $currentGroup = null;
                continue;
            }

            // Only add rows that belong to a current group
            if ($currentGroup) {
                $grouped[$currentGroup][] = [
                    'transaction_date'           => $rowData['transaction_date'],
                    'transaction_type'           => $rowData['transaction_type'],
                    'num'                        => $rowData['num'],
                    'name'                       => $rowData['name'],
                    'memo_description'           => $rowData['memo_description'],
                    'full_name'                  => $rowData['full_name'],
                    'debit'                      => $this->cleanAmount($rowData['debit']),
                    'credit'                     => $this->cleanAmount($rowData['credit']),
                    'distribution_account_type'  => $rowData['distribution_account_type'],
                    'rate'                       => $rowData['rate'],
                    'quantity'                   => $rowData['quantity'],
                    'product_service_full_name'  => $rowData['product_service_full_name'],
                ];
            }
        }

        // dd($grouped);
        
    }
    private function createInvoice($groupId, $items)
    {
        // Example: Create Invoice
        $invoice = \App\Models\Invoice::create([
            'group_id' => $groupId,
            'date' => now(),
            'total_amount' => collect($items)->sum(function ($item) {
                return $item['debit'] ?? 0; // or your logic to calculate total
            }),
            'description' => 'Invoice for group ' . $groupId,
        ]);

        // Attach each row as invoice item
        foreach ($items as $item) {
                $a=InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'transaction_date' => $item['transaction_date'],
                'transaction_type' => $item['transaction_type'],
                'num' => $item['num'],
                'name' => $item['name'],
                'memo_description' => $item['memo_description'],
                'full_name' => $item['full_name'],
                'debit' => $item['debit'],
                'credit' => $item['credit'],
                'distribution_account_type' => $item['distribution_account_type'],
                'rate' => $item['rate'],
                'quantity' => $item['quantity'],
                'product_service_full_name' => $item['product_service_full_name'],
            ]);
        }
    }
    private function createBill($groupId, $items)
    {
        // dd($items, $groupId,'bill');
    }
    private function createDeposit($groupId, $items)
    {
        try{
        // dd($items, $groupId,'deposit');
        $totalDebit = 0;
        $totalCredit = 0;
        for ($i = 0; $i < count($items); $i++) {
            $debit = isset($items[$i]['debit']) ? $items[$i]['debit'] : 0;
            $credit = isset($items[$i]['credit']) ? $items[$i]['credit'] : 0;
            $totalDebit += $debit;
            $totalCredit += $credit;
        }
      
        // $totalDebit += $debit;

        if ($totalCredit != $totalDebit) {
            // MAKE A RETURN FILE LIKE its come with error and also by groupby 

        }

        $journal = new JournalEntry();
        $journal->journal_id = $this->journalNumber();
        $journal->date = date('Y-m-d', strtotime($items[0]['transaction_date']));
        $journal->reference = $items[0]['num'];
        $journal->description = $items[0]['memo_description'];
        $journal->created_by = \Auth::user()->creatorId();
        $journal->owned_by = \Auth::user()->ownedId();
        $journal->save();
        $journal->created_at = date('Y-m-d H:i:s', strtotime($items[0]['transaction_date']));
        $journal->updated_at = date('Y-m-d H:i:s', strtotime($items[0]['transaction_date']));
        $journal->save();

        for ($i = 0; $i < count($items); $i++) {
            $account = $this->ensureChartOfAccount($items[$i]['full_name'], $items[$i]['distribution_account_type'],$items[$i]['distribution_account_type']);
            //   dd($totalDebit, $totalCredit,$items,$account);
            if($account == null){
                continue;
            }
            $journalItem = new JournalItem();
            $journalItem->journal = $journal->id;
            $journalItem->account = $account->id;
            $journalItem->description = $items[$i]['memo_description'];
            $journalItem->debit = isset($items[$i]['debit']) ? $items[$i]['debit'] : 0;
            $journalItem->credit = isset($items[$i]['credit']) ? $items[$i]['credit'] : 0;
            $journalItem->save();
            $journalItem->created_at = date('Y-m-d H:i:s', strtotime($items[0]['transaction_date']));
            $journalItem->updated_at = date('Y-m-d H:i:s', strtotime($items[0]['transaction_date']));
            $journalItem->save();

            $bankAccounts = BankAccount::where('chart_account_id', '=', $account->id)->get();
            if (!empty($bankAccounts)) {
                foreach ($bankAccounts as $bankAccount) {
                    $old_balance = $bankAccount->opening_balance;
                    if ($journalItem->debit > 0) {
                        $new_balance = $old_balance - $journalItem->debit;
                    }
                    if ($journalItem->credit > 0) {
                        $new_balance = $old_balance + $journalItem->credit;
                    }
                    if (isset($new_balance)) {
                        $bankAccount->opening_balance = $new_balance;
                        $bankAccount->save();
                    }
                }
            }
            if (isset($items[$i]['debit'])) {
                $data = [
                    'account_id' => $account->id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $items[$i]['debit'],
                    'reference' => 'Journal',
                    'reference_id' => $journal->id,
                    'reference_sub_id' => $journalItem->id,
                    'date' => $journal->date,
                ];
            } else {
                $data = [
                    'account_id' => $account->id,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $items[$i]['credit'],
                    'reference' => 'Journal',
                    'reference_id' => $journal->id,
                    'reference_sub_id' => $journalItem->id,
                    'date' => $journal->date,
                ];
            }
            $this->addTransactionLines($data , 'create');
        }
        }catch(\Exception $e){
            dd($e->getMessage());
        }
    }

    private function cleanAmount($value)
    {
        if (!$value) return null;
        // remove $ and commas
        return str_replace([',', '$'], '', $value);
    }
    public function journalNumber()
    {
        $latest = JournalEntry::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->journal_id + 1;
    }
    private function ensureChartOfAccount($fullName, $distributionAccountType, $detailType = 'Other')
{
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

    $typeName = strtolower($distributionAccountType);
    
    if (!isset($typeMapping[$typeName])) {
        \Log::warning("Distribution type '{$distributionAccountType}' not mapped. Skipping: {$fullName}");
        return null;
    }

    $systemTypeName = $typeMapping[$typeName];

    $type = ChartOfAccountType::firstOrCreate(
        ['name' => $systemTypeName, 'created_by' => \Auth::user()->creatorId()]
    );
    $acct = ChartOfAccount::where('name', $fullName)
        ->where('type', $type->id)
        ->where('created_by', \Auth::user()->creatorId())
        ->first();
        if($acct){
            $subType = ChartOfAccountSubType::where('type', $type->id)->where('id', $acct->sub_type)
            ->where('created_by', \Auth::user()->creatorId())->first();
        }else{
            $subType = ChartOfAccountSubType::firstOrCreate([
                'type' => $type->id,
                'name' => $detailType ?: 'Other',
                'created_by' => \Auth::user()->creatorId(),
            ]);
        }

    $account = ChartOfAccount::firstOrCreate([
        'name'       => $fullName,
        'type'       => $type->id,
        'sub_type'   => $subType->id,
        'created_by' => \Auth::user()->creatorId(),
    ]);

    return $account;
}
     public static function addTransactionLines($data, $action)
    {
        $existingTransaction = TransactionLines::where('reference_id', $data['reference_id'])
            ->where('reference_sub_id', $data['reference_sub_id'])->where('reference', $data['reference'])
            ->first();
        if ($existingTransaction && $action == 'edit') {
            $transactionLines = $existingTransaction;
        } else {
            $transactionLines = new TransactionLines();
        }
        $transactionLines->account_id = $data['account_id'];
        $transactionLines->reference = $data['reference'];
        $transactionLines->reference_id = $data['reference_id'];
        $transactionLines->reference_sub_id = $data['reference_sub_id'];
        $transactionLines->date = $data['date'];
        $transactionLines->product_id = @$data['product_id'] ?? @$transactionLines->product_id;
        $transactionLines->product_type = @$data['product_type'] ?? @$transactionLines->product_type;
        $transactionLines->product_item_id = @$data['product_item_id'] ?? @$transactionLines->product_item_id;
        if ($data['transaction_type'] == "Credit") {
            $transactionLines->credit = $data['transaction_amount'];
            $transactionLines->debit = 0;
        } else {
            $transactionLines->credit = 0;
            $transactionLines->debit = $data['transaction_amount'];
        }
        $transactionLines->created_by = Auth::user()->creatorId();
        $transactionLines->created_at = date('Y-m-d H:i:s', strtotime($data['date']));
        $transactionLines->updated_at = date('Y-m-d H:i:s', strtotime($data['date']));
        $transactionLines->save();
        $transactionLines->created_at = date('Y-m-d H:i:s', strtotime($data['date']));
        $transactionLines->updated_at = date('Y-m-d H:i:s', strtotime($data['date']));
        $transactionLines->save();
    }
}
