<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Utility;
use App\Models\Customer;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use Illuminate\Http\Request;
use Auth;

class CreditNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {

        if(\Auth::user()->can('manage credit note'))
        {
            $invoices = Invoice::where('created_by', \Auth::user()->creatorId())->get();

            return view('creditNote.index', compact('invoices'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create($invoice_id)
    {

        if(\Auth::user()->can('create credit note'))
        {

            $invoiceDue = Invoice::where('id', $invoice_id)->first();

            return view('creditNote.create', compact('invoiceDue', 'invoice_id'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request, $invoice_id)
    {
        \DB::beginTransaction();
        try {
        if(\Auth::user()->can('create credit note'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'amount' => 'required|numeric',
                                   'date' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $invoiceDue = Invoice::where('id', $invoice_id)->first();
            if($request->amount > $invoiceDue->getDue())
            {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }
            $invoice = Invoice::where('id', $invoice_id)->first();

            $credit              = new CreditNote();
            $credit->invoice     = $invoice_id;
            $credit->customer    = $invoice->customer_id;
            $credit->date        = $request->date;
            $credit->amount      = $request->amount;
            $credit->description = $request->description;
            $credit->save();

            $customer = Customer::find($invoice->customer_id);
            $balance = 0;
            if($customer->credit_balance != 0)
            {
                $balance = $customer->credit_balance + $request->amount;
            }

            $customer->credit_balance = $balance;
            $customer->save();

            Utility::updateUserBalance('credit', $invoice->customer_id, $request->amount, 'debit');

             // // WorkFlow get which is active
             $us_mail = 'false';
             $us_notify = 'false';
             $us_approve = 'false';
             $usr_Notification = [];
             $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'accounts')->where('status', 1)->first();
             if ($workflow) {
                 $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
                 foreach ($workflowaction as $action) {
                     $useraction = json_decode($action->assigned_users);
                     if (strtolower('issue-credit-note') == $action->node_id) {
                         // Pick that stage user assign or change on lead
                         if (@$useraction != '') {
                             $useraction = json_decode($useraction);
                             foreach ($useraction as $anyaction) {
                                 // make new user array
                                 if ($anyaction->type == 'user') {
                                     $usr_Notification[] = $anyaction->id;
                                 }
                             }
                         }
                         $raw_json = trim($action->applied_conditions, '"');
                         $cleaned_json = stripslashes($raw_json);
                         $applied_conditions = json_decode($cleaned_json, true);

                         if (isset($applied_conditions['conditions']) && is_array($applied_conditions['conditions'])) {
                             $arr = [
                                 'invoice' => 'invoice_invoice_id',
                                 'customer' => 'customer_name',
                                 'amount' => 'amount',
                             ];
                             $relate = [
                                'invoice_invoice_id' => 'invoice',
                                'customer_name' => 'customer',
                             ];

                             foreach ($applied_conditions['conditions'] as $conditionGroup) {

                                 if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                     $query = CreditNote::where('id', $credit->id);
                                     foreach ($conditionGroup['conditions'] as $condition) {
                                         $field = $condition['field'];
                                         $operator = $condition['operator'];
                                         $value = $condition['value'];
                                         if (isset($arr[$field], $relate[$arr[$field]])) {
                                             $relatedField = strpos($arr[$field], '_') !== false ? explode('_', $arr[$field], 2)[1] : $arr[$field];
                                             $relation = $relate[$arr[$field]];
                                             // Apply condition to the related model
                                             $query->whereHas($relation, function ($relatedQuery) use ($relatedField, $operator, $value) {
                                                 $relatedQuery->where($relatedField, $operator, $value);
                                            });
                                         } else {
                                             // Apply condition directly to the contract model
                                             $query->where($arr[$field], $operator, $value);
                                         }
                                     }
                                     $result = $query->first();

                                     if (!empty($result)) {
                                         if ($conditionGroup['action'] === 'send_email') {
                                             $us_mail = 'true';
                                         } elseif ($conditionGroup['action'] === 'send_notification') {
                                             $us_notify = 'true';
                                         } elseif ($conditionGroup['action'] === 'send_approval') {
                                             $us_approve = 'true';
                                         }
                                     }
                                 }
                             }
                         }
                         if ($us_mail == 'true') {
                             // email send
                         }
                         if ($us_notify == 'true' || $us_approve == 'true') {
                             // notification generate
                            if (count($usr_Notification) > 0) {
                                $usr_Notification[] = Auth::user()->creatorId();
                                foreach ($usr_Notification as $usrLead) {
                                    $data = [
                                        "updated_by" => Auth::user()->id,
                                        "data_id" => $credit->id,
                                        "name" => '',
                                    ];
                                    if($us_notify == 'true'){
                                        Utility::makeNotification($usrLead,'create_credit',$data,$credit->id,'create Credit');
                                    }elseif($us_approve == 'true'){
                                        Utility::makeNotification($usrLead,'approve_credit',$data,$credit->id,'For Approval Credit Note');
                                    }
                                }
                            }
                         }
                     }
                 }
             }
            \DB::commit();
            return redirect()->back()->with('success', __('Credit Note successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        } catch (\Exception $e) {
            \DB::rollback();
            dd($e);
            return redirect()->back()->with('error', $e);
        }
    }


    public function edit($invoice_id, $creditNote_id)
    {
        if(\Auth::user()->can('edit credit note'))
        {

            $creditNote = CreditNote::find($creditNote_id);

            return view('creditNote.edit', compact('creditNote'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, $invoice_id, $creditNote_id)
    {

        if(\Auth::user()->can('edit credit note'))
        {

            $validator = \Validator::make(
                $request->all(), [
                                   'amount' => 'required|numeric',
                                   'date' => 'required',
                               ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $invoiceDue = Invoice::where('id', $invoice_id)->first();
            $credit = CreditNote::find($creditNote_id);
            if($request->amount > $invoiceDue->getDue()+$credit->amount)
            {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }


            Utility::updateUserBalance('customer', $invoiceDue->customer_id, $credit->amount, 'credit');

            $credit->date        = $request->date;
            $credit->amount      = $request->amount;
            $credit->description = $request->description;
            $credit->save();

            Utility::updateUserBalance('customer', $invoiceDue->customer_id, $request->amount, 'debit');


            return redirect()->back()->with('success', __('Credit Note successfully updated.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy($invoice_id, $creditNote_id)
    {
        if(\Auth::user()->can('delete credit note'))
        {

            $creditNote = CreditNote::find($creditNote_id);
            $creditNote->delete();

            Utility::updateUserBalance('customer', $creditNote->customer, $creditNote->amount, 'credit');

            return redirect()->back()->with('success', __('Credit Note successfully deleted.'));

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customCreate()
    {
        if(\Auth::user()->can('create credit note'))
        {

            $invoices = Invoice::where('created_by', \Auth::user()->creatorId())->get()->pluck('invoice_id', 'id');

            return view('creditNote.custom_create', compact('invoices'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customStore(Request $request)
    {
        if(\Auth::user()->can('create credit note'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'invoice' => 'required|numeric',
                                   'amount' => 'required|numeric',
                                   'date' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $invoice_id = $request->invoice;
            $invoiceDue = Invoice::where('id', $invoice_id)->first();

            if($request->amount > $invoiceDue->getDue())
            {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }
            $invoice             = Invoice::where('id', $invoice_id)->first();
            $credit              = new CreditNote();
            $credit->invoice     = $invoice_id;
            $credit->customer    = $invoice->customer_id;
            $credit->date        = $request->date;
            $credit->amount      = $request->amount;
            $credit->description = $request->description;
            $credit->save();

            Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

            return redirect()->back()->with('success', __('Credit Note successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getinvoice(Request $request)
    {
        $invoice = Invoice::where('id', $request->id)->first();

        echo json_encode($invoice->getDue());
    }

}
