<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Customer;
use App\Models\ProductService;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunInvoiceRecurring extends Command
{
    protected $signature = 'recurring:run';
    protected $description = 'Generate due recurring invoices from master schedules';

    public function handle()
    {
        $now = Carbon::now();

        // Masters that are due:
        $masters = Invoice::query()
            ->where('is_recurring', true)
            ->whereNull('recurring_parent_id')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', $now)
            ->get();


        foreach ($masters as $master) {
            // If "by" and next_run_at > end_by, stop
            if ($master->recurring_end_type === 'by' && $master->recurring_end_date) {
                if (Carbon::parse($master->next_run_at)->gt(Carbon::parse($master->recurring_end_date)->endOfDay())) {
                    $master->next_run_at = null;
                    $master->save();
                    continue;
                }
            }

            DB::beginTransaction();
            try {
                // 1) Create a child invoice
                $child = new Invoice();
                $child->invoice_id  = $this->nextInvoiceNumber();
                $child->customer_id = $master->customer_id;
                $child->status      = 0;

                // Issue date = the scheduled run slot
                $issueDate = Carbon::parse($master->next_run_at)->toDateString();
                $child->issue_date  = $issueDate;

                // Preserve original issue→due delta (days)
                $deltaDays = Carbon::parse($master->due_date)->diffInDays(Carbon::parse($master->issue_date));
                $child->due_date    = Carbon::parse($issueDate)->addDays($deltaDays)->toDateString();

                $child->category_id = $master->category_id;
                $child->ref_number  = $master->ref_number;
                $child->created_by  = $master->created_by;
                $child->owned_by    = $master->owned_by;

                // link to master
                $child->recurring_parent_id = $master->id;
                $child->save();

                // 2) Copy items + stock movements
                $items    = $master->items()->get();
                $newitems = [];
                foreach ($items as $it) {
                    $p = new InvoiceProduct();
                    $p->invoice_id  = $child->id;
                    $p->product_id  = $it->product_id;
                    $p->quantity    = $it->quantity;
                    $p->tax         = $it->tax;
                    $p->discount    = $it->discount;
                    $p->price       = $it->price;
                    $p->description = $it->description;
                    $p->save();

                    $newitems[] = [
                        'prod_id'     => $p->id,
                        'item'        => $p->product_id,
                        'quantity'    => $p->quantity,
                        'tax'         => $p->tax,
                        'discount'    => $p->discount,
                        'price'       => $p->price,
                        'description' => $p->description,
                    ];

                    // inventory
                    Utility::total_quantity('minus', $p->quantity, $p->product_id);

                    $type = 'invoice';
                    // CLI-safe description (no Auth in console)
                    $description = $p->quantity . ' ' . __(' quantity sold in invoice') . ' #' . $child->invoice_id;
                    Utility::addProductStock($p->product_id, $p->quantity, $type, $description, $child->id);
                }

                // 3) Journal entry
                $data = [
                    'id'         => $child->id,
                    'no'         => $child->invoice_id,
                    'date'       => $child->issue_date,
                    'created_at' => date('Y-m-d', strtotime($child->issue_date)) . ' ' . date('H:i:s'),
                    'reference'  => $child->ref_number,
                    'category'   => 'Invoice',
                    'owned_by'   => $child->owned_by,
                    'created_by' => $child->created_by,
                    'prod_id'    => $items->first()?->product_id,
                    'items'      => $newitems,
                ];
                $voucherId = Utility::jrentry($data);
                $child->voucher_id = $voucherId;
                $child->save();

                // 4) Advance schedule on master
                // Decrement remaining occurrences (children) if > 0
                // When it reaches 0, stop scheduling.
                if ((int)$master->recurring_every_n > 0) {
                    $master->recurring_every_n = (int)$master->recurring_every_n - 1;
                }

                if ((int)$master->recurring_every_n === 0) {
                    // Reached the requested quantity → stop
                    $master->next_run_at = null;
                } else {
                    // Compute next run based on base interval only
                    $next = $this->advanceByBaseInterval(
                        Carbon::parse($master->next_run_at),
                        $master->recurring_repeat
                    );

                    // Honor end-by
                    if ($master->recurring_end_type === 'by' && $master->recurring_end_date) {
                        $endBy = Carbon::parse($master->recurring_end_date)->endOfDay();
                        $master->next_run_at = ($next > $endBy) ? null : $next->toDateTimeString();
                    } else {
                        $master->next_run_at = $next->toDateTimeString();
                    }
                }

                $master->save();

                DB::commit();
                $this->info("Generated child invoice #{$child->invoice_id} from master {$master->id}");
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Failed master {$master->id}: " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    protected function nextInvoiceNumber(): int
    {
        $latest = Invoice::latest()->first();
        return $latest ? ((int)$latest->invoice_id + 1) : 1;
    }

    /**
     * Advance a date by the base interval for the repeating rule
     * (monthly=+1, quarterly=+4, 6months=+6, yearly=+12).
     * Does NOT use recurring_every_n (that's quantity, not spacing).
     */
    protected function advanceByBaseInterval(Carbon $from, string $repeat): Carbon
    {
        $baseMonths = match ($repeat) {
            'monthly'   => 1,
            'quarterly' => 4,
            '6months'   => 6,
            'yearly'    => 12,
            default     => 1,
        };

        // keep the time component; use addMonthsNoOverflow to avoid day overflow
        return $from->copy()->addMonthsNoOverflow($baseMonths);
    }
}
