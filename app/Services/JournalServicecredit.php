<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\TransactionLines;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class JournalServicecredit
{
    /**
     * Create a new journal entry with its items
     * 
     * @param array $data Journal entry and items data
     * @return JournalEntry
     * @throws Exception
     */
    public static function createJournalEntry(array $data)
    {
        // dd($data);
        DB::beginTransaction();
        try {
            // Extract entry date for potential backdating
            $entryDate = $data['date'] ?? now()->format('Y-m-d');
            $backdate = $data['backdate'] ?? false;
            
            // Timestamps to use
            $timestamps = $backdate ? [
                'created_at' => $entryDate . ' ' . now()->format('H:i:s'),
                'updated_at' => $entryDate . ' ' . now()->format('H:i:s'),
            ] : [];
            
            // Create main journal entry
            $journalEntry = JournalEntry::create([
                'date' => $entryDate,
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'journal_id' => $data['journal_id'] ?? 0,
                'voucher_type' => $data['voucher_type'] ?? 'JV',
                'reference_id' => $data['reference_id'] ?? null,
                'prod_id' => $data['prod_id'] ?? null,
                'category' => $data['category'] ?? null,
                'module' => $data['module'] ?? null,
                'source' => $data['source'] ?? null,
                'created_by' => $data['created_by'] ?? 0,
                'owned_by' => $data['owned_by'] ?? 0,
                'status' => $data['status'] ?? 1, // 1 = Active by default
            ] + $timestamps);
            
            // Track totals for validation
            $totalDebit = 0;
            $totalCredit = 0;
            
            // Create Accounts Payable (AP) journal item (credit side) if provided
            if (!empty($data['ap_account_id']) && !empty($data['ap_amount'])) {
                 $apItemData = [
                    'journal'     => $journalEntry->id,
                    'account'     => $data['ap_account_id'],
                    'debit'       => $data['ap_amount'],
                    'credit'      => 0,
                    'description' => $data['ap_description'] ?? 'Account Payable',
                    'type'        => $data['category'] ?? null,
                    'name'        => $data['ap_name'] ?? '',
                    'created_user'=> $data['created_user'] ?? null,
                    'created_by'  => $data['created_by'] ?? 0,
                    'company_id'  => $data['company_id'] ?? null,
                ] + $timestamps;

                // Add user type mapping
                if (!empty($data['user_type']) && $data['user_type'] == 'vendor') {
                    $apItemData['vendor_id'] = $data['vendor_id'] ?? null;

                } elseif (!empty($data['user_type']) && $data['user_type'] == 'customer') {
                    $apItemData['customer_id'] = $data['vendor_id'] ?? null;

                } elseif (!empty($data['user_type']) && $data['user_type'] == 'employee') {
                    $apItemData['employee_id'] = $data['vendor_id'] ?? null;
                }

                // Finally create the Journal Item
                $apItem = JournalItem::create($apItemData);

                $aptran = TransactionLines::create([
                    'account_id' => $apItem->account,
                    'reference' => isset($data['category']) ? $data['category'] . ' Journal' : null,
                    'reference_id' => $journalEntry->id,
                    'reference_sub_id' => $apItem->id,
                    'date' => $entryDate,
                    'credit' => $apItem->credit,
                    'debit' => $apItem->debit,
                    'product_id' => $data['reference_id'] ?? ($data['reference_id'] ?? null),
                    'product_type' => $data['ap_sub_type'] ?? 'Accounts Payable',
                    'product_item_id' => $data['ap_item_id'] ?? 0,
                    'created_by' => $data['created_by'] ?? 0,
                ] + $timestamps);
                
                $totalDebit += $apItem->debit;
                $totalCredit += $apItem->credit;
            }
            
            // Create journal items (debit side)
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    // Skip empty items
                    if (empty($itemData['account_id']) && empty($itemData['debit']) && empty($itemData['credit'])) {
                        continue;
                    }
                    
                    $journalItemData = [
                        'journal' => $journalEntry->id,
                        'account' => $itemData['account_id'] ?? 0,
                        'debit' => $itemData['credit'] ?? 0,
                        'credit' => $itemData['debit'] ?? 0,
                        'description' => $itemData['description'] ?? '',
                        'product_id' => $itemData['product_id'] ?? null,
                        'product_ids' => $itemData['product_ids'] ?? null,
                        'prod_tax_id' => $itemData['prod_tax_id'] ?? null,
                        'type' => $itemData['type'] ?? null,
                        'name' => $itemData['name'] ?? '',
                        'customer_id' => $itemData['customer_id'] ?? null,
                        'vendor_id' => $itemData['vendor_id'] ?? null,
                        'employee_id' => $itemData['employee_id'] ?? null,
                        'created_by' => $itemData['created_by'] ?? ($data['created_by'] ?? 0),
                        'created_user' => $itemData['created_user'] ?? null,
                        'company_id' => $itemData['company_id'] ?? ($data['company_id'] ?? null),
                    ] + $timestamps;

                    // Add user type mapping
                    if (!empty($data['user_type']) && $data['user_type'] == 'vendor') {
                        $journalItemData['vendor_id'] = $data['vendor_id'] ?? null;

                    } elseif (!empty($data['user_type']) && $data['user_type'] == 'customer') {
                        $journalItemData['customer_id'] = $data['vendor_id'] ?? null;

                    } elseif (!empty($data['user_type']) && $data['user_type'] == 'employee') {
                        $journalItemData['employee_id'] = $data['vendor_id'] ?? null;
                    }
                    
                    $journalItem = JournalItem::create($journalItemData);
                    
                    $totalDebit += $journalItem->debit;
                    $totalCredit += $journalItem->credit;
                    
                    // Create transaction line for this journal item
                    if ($journalItem->debit > 0 || $journalItem->credit > 0) {
                        TransactionLines::create([
                            'account_id' => $journalItem->account,
                            'reference' => isset($data['category']) ? $data['category'] . ' Journal' : null,
                            'reference_id' => $journalEntry->id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $entryDate,
                            'credit' => $journalItem->credit,
                            'debit' => $journalItem->debit,
                            'product_id' => $itemData['bill_id'] ?? ($data['bill_id'] ?? null),
                            'product_type' => $itemData['sub_type'] ?? 'Bill',
                            'product_item_id' => $itemData['product_id'] ?? 0,
                            'created_by' => $data['created_by'] ?? 0,
                        ] + $timestamps);
                    }
                }
            }
            
            // Validate debit = credit balance
            if (abs($totalCredit - $totalDebit) > 0.0001) {
                throw new Exception("Journal entry is not balanced. Debit: {$totalDebit}, Credit: {$totalCredit}");
            }
            
            DB::commit();
            
            Log::info('Journal entry created successfully', [
                'journal_entry_id' => $journalEntry->id,
                'reference' => $journalEntry->reference,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);
            
            return $journalEntry;
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to create journal entry', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }
    
    /**
     * Find a journal entry by criteria
     * 
     * @param array $criteria Search criteria
     * @return JournalEntry|null
     */
    public static function findJournalEntry(array $criteria)
    {
        $query = JournalEntry::query()->with('journalItem');
        
        foreach ($criteria as $key => $value) {
            if ($key === 'id') {
                $query->where('id', $value);
            } elseif ($key === 'reference_id') {
                $query->where('reference_id', $value);
            } elseif ($key === 'category') {
                $query->where('category', $value);
            } elseif ($key === 'module') {
                $query->where('module', $value);
            } elseif ($key === 'status') {
                $query->where('status', $value);
            } elseif ($key === 'voucher_type') {
                $query->where('voucher_type', $value);
            }
        }
        
        return $query->first();
    }
    
    /**
     * Update an existing journal entry
     * 
     * @param int $id Journal entry ID
     * @param array $data Updated data
     * @return JournalEntry
     * @throws Exception
     */
    public static function updateJournalEntry($id, array $data)
    {
        DB::beginTransaction();
        
        try {
            $journalEntry = JournalEntry::findOrFail($id);
            // Extract entry date for potential backdating
            $entryDate = $data['date'] ?? $journalEntry->date .''.now()->format('H:i:s');
            $backdate = $data['backdate'] ?? false;
            
            // Timestamps to use
            $timestamps = $backdate ? [
                'updated_at' => $entryDate,
            ] : [];
            
            // Update journal entry fields
            $journalEntry->update([
                'date' => $entryDate,
                'reference' => $data['reference'] ?? $journalEntry->reference,
                'description' => $data['description'] ?? $journalEntry->description,
                'voucher_type' => $data['voucher_type'] ?? $journalEntry->voucher_type,
                'category' => $data['category'] ?? $journalEntry->category,
                'module' => $data['module'] ?? $journalEntry->module,
                'source' => $data['source'] ?? $journalEntry->source,
                'status' => $data['status'] ?? $journalEntry->status,
            ] + $timestamps);
            
            // Delete old journal items
            JournalItem::where('journal', $id)->delete();
            
            // Delete old transaction lines
            TransactionLines::where('reference_id', $id)->where('reference', ($data['category'] ?? '') . ' Journal')->delete();
            // Track totals for validation
            $totalDebit = 0;
            $totalCredit = 0;
            
            // Recreate AP journal item if provided
            if (!empty($data['ap_account_id']) && !empty($data['ap_amount'])) {
                $apItemData = [
                    'journal'      => $journalEntry->id,
                    'account'      => $data['ap_account_id'],
                    'debit'        => $data['ap_amount'],
                    'credit'       => 0,
                    'description'  => $data['ap_description'] ?? 'Account Payable',
                    'type'         => $data['category'] ?? $journalEntry->category,
                    'name'         => $data['ap_name'] ?? '',
                    'created_user' => $data['created_user'] ?? null,
                    'created_by'   => $data['created_by'] ?? $journalEntry->created_by,
                    'company_id'   => $data['company_id'] ?? null,
                    'created_at'   => $backdate ? $entryDate : now(),
                    'updated_at'   => $backdate ? $entryDate : now(),
                ];

                // ✅ Add user-specific IDs cleanly
                if (!empty($data['user_type']) && $data['user_type'] === 'vendor') {
                    $apItemData['vendor_id'] = $data['vendor_id'] ?? null;

                } elseif (!empty($data['user_type']) && $data['user_type'] === 'customer') {
                    $apItemData['customer_id'] = $data['vendor_id'] ?? null;

                } elseif (!empty($data['user_type']) && $data['user_type'] === 'employee') {
                    $apItemData['employee_id'] = $data['vendor_id'] ?? null;
                }

                // ✅ Create the item
                $apItem = JournalItem::create($apItemData);
                
                $totalCredit += $data['ap_amount'];
                
                // Create transaction line for AP
                TransactionLines::create([
                    'account_id' => $data['ap_account_id'],
                    'reference' => isset($data['category']) ? $data['category'] . ' Journal' : null,
                    'reference_id' => $journalEntry->id,
                    'reference_sub_id' => $apItem->id,
                    'date' => $entryDate,
                    'credit' => 0,
                    'debit' => $data['ap_amount'],
                    'product_id' => $data['bill_id'] ?? null,
                    'product_type' => $data['ap_sub_type'] ?? 'Bill Payable',
                    'product_item_id' => 0,
                    'created_by' => $data['created_by'] ?? $journalEntry->created_by,
                    'created_at' => $backdate ? $entryDate : now(),
                    'updated_at' => $backdate ? $entryDate : now(),
                ]);
            }
            
            // Recreate journal items
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    // Skip empty items
                    if (empty($itemData['account_id']) && empty($itemData['debit']) && empty($itemData['credit'])) {
                        continue;
                    }
                    
                    $journalItemData = [
                        'journal'       => $journalEntry->id,
                        'account'       => $itemData['account_id'] ?? 0,
                        'debit'         => $itemData['credit'] ?? 0,
                        'credit'        => $itemData['debit'] ?? 0,
                        'description'   => $itemData['description'] ?? '',
                        'product_id'    => $itemData['product_id'] ?? null,
                        'product_ids'   => $itemData['product_ids'] ?? null,
                        'prod_tax_id'   => $itemData['prod_tax_id'] ?? null,
                        'type'          => $itemData['type'] ?? null,
                        'name'          => $itemData['name'] ?? '',
                        'created_by'    => $itemData['created_by'] ?? ($data['created_by'] ?? $journalEntry->created_by),
                        'created_user'  => $itemData['created_user'] ?? null,
                        'company_id'    => $itemData['company_id'] ?? ($data['company_id'] ?? null),
                        'created_at'    => $backdate ? $entryDate : now(),
                        'updated_at'    => $backdate ? $entryDate : now(),
                    ];

                    // ✅ Conditional user-type IDs
                    if (!empty($data['user_type']) && $data['user_type'] === 'vendor') {
                        $journalItemData['vendor_id'] = $data['vendor_id'] ?? null;

                    } elseif (!empty($data['user_type']) && $data['user_type'] === 'customer') {
                        $journalItemData['customer_id'] = $data['customer_id'] ?? null;

                    } elseif (!empty($data['user_type']) && $data['user_type'] === 'employee') {
                        $journalItemData['employee_id'] = $data['employee_id'] ?? null;
                    }

                    $journalItem = JournalItem::create($journalItemData);

                    
                    $totalDebit += $journalItem->debit;
                    $totalCredit += $journalItem->credit;
                    
                    // Create transaction line for this journal item
                    if ($journalItem->debit > 0 || $journalItem->credit > 0) {
                        TransactionLines::create([
                            'account_id' => $journalItem->account,
                            'reference' => isset($data['category']) ? $data['category'] . ' Journal' : null,
                            'reference_id' => $journalEntry->id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $entryDate,
                            'credit' => $journalItem->credit,
                            'debit' => $journalItem->debit,
                            'product_id' => $itemData['bill_id'] ?? ($data['bill_id'] ?? null),
                            'product_type' => $itemData['sub_type'] ?? 'Bill',
                            'product_item_id' => $itemData['product_id'] ?? 0,
                            'created_by' => $data['created_by'] ?? $journalEntry->created_by,
                            'created_at' => $backdate ? $entryDate : now(),
                            'updated_at' => $backdate ? $entryDate : now(),
                        ]);
                    }
                }
            }
            
            // Validate debit = credit balance
            if (abs($totalCredit - $totalDebit) > 0.0001) {
                throw new Exception("Journal entry is not balanced. Debit: {$totalDebit}, Credit: {$totalCredit}");
            }
            
            DB::commit();
            
            Log::info('Journal entry updated successfully', [
                'journal_entry_id' => $journalEntry->id,
                'reference' => $journalEntry->reference,
            ]);
            
            return $journalEntry->fresh();
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to update journal entry', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Delete a journal entry and its items
     * 
     * @param int $id Journal entry ID
     * @param bool $softDelete If true, marks as cancelled instead of deleting
     * @return bool
     */
    public static function deleteJournalEntry($id, $softDelete = false)
    {
        DB::beginTransaction();
        
        try {
            $journalEntry = JournalEntry::findOrFail($id);
            
            if ($softDelete) {
                // Soft delete: mark as cancelled
                $journalEntry->update(['status' => 2]); // 2 = Cancelled
                
                Log::info('Journal entry soft deleted (cancelled)', [
                    'journal_entry_id' => $id,
                ]);
            } else {
                // Hard delete: remove items then entry
                JournalItem::where('journal', $id)->delete();
                $journalEntry->delete();
                
                Log::info('Journal entry hard deleted', [
                    'journal_entry_id' => $id,
                ]);
            }
            
            DB::commit();
            return true;
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Failed to delete journal entry', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Restore a soft-deleted (cancelled) journal entry
     * 
     * @param int $id Journal entry ID
     * @return JournalEntry
     */
    public static function restoreJournalEntry($id)
    {
        $journalEntry = JournalEntry::findOrFail($id);
        $journalEntry->update(['status' => 1]); // 1 = Active
        
        Log::info('Journal entry restored', [
            'journal_entry_id' => $id,
        ]);
        
        return $journalEntry->fresh();
    }
}
