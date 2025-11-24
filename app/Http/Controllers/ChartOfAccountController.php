<?php

namespace App\Http\Controllers;

use App\Imports\ChartOfAccountsImport;
use App\Imports\FullJournalImport;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\User;
use App\Models\Utility;
use App\Models\JournalItem;
use App\Models\ChartOfAccountParent;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ChartOfAccountController extends Controller
{

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage chart of account')) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start = $request->start_date;
                $end = $request->end_date;
            } else {
                $start = date('Y-01-01');
                $end = date('Y-m-d', strtotime('+1 day'));
            }
            $filter['startDateRange'] = $start;
            $filter['endDateRange'] = $end;

            $types = ChartOfAccountType::where('created_by', '=', \Auth::user()->creatorId())->get();

            $accounts = ChartOfAccount::whereIn('type', $types->pluck('id'))
                ->where('created_by', '=', \Auth::user()->creatorId())
                ->with(['subType', 'parentAccount'])
                ->get()
                ->groupBy('type');

            $chartAccounts = [];
            foreach ($types as $type) {
                $typeName = $type->name;
                $chartAccounts[$typeName] = $accounts[$type->id] ?? [];
            }

            return view('chartOfAccount.index', compact('chartAccounts', 'types', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {

        $types = ChartOfAccountType::where('created_by', \Auth::user()->creatorId())->get();

        $account_type = [];

        foreach ($types as $type) {
            $accountTypes = ChartOfAccountSubType::where('type', $type->id)->where('created_by', \Auth::user()->creatorId())->get();
            $temp = [];
            foreach ($accountTypes as $accountType) {
                $temp[$accountType->id] = $accountType->name;
            }
            $account_type[$type->name] = $temp;
        }
        $selectAcc =     [
            null => "Select",
        ];
        $account_type =  array_merge($selectAcc, $account_type);

        return view('chartOfAccount.create', compact('account_type'));
    }

      public function importFile()
    {
        return view('chartOfAccount.import');
    }
    public function import(Request $request)
    {
        $rules = [
            'file' => 'required|mimes:csv,txt',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        Excel::import(new FullJournalImport, $request->file('file'));
        // Excel::import(new ChartOfAccountsImport, $request->file('file'));
        // if (session('failed_file')) {
        //     $filePath = storage_path(session('failed_file'));

        //     if (file_exists($filePath)) {
        //         return response()->download($filePath)->deleteFileAfterSend(true);
        //     }
        // }

    return back()->with('success', 'Chart of Accounts imported successfully!');
    }
     //Export
    public function export()
    {
        $name = 'employee_' . date('Y-m-d i:h:s');
        $data = Excel::download(new ChartOfAccountExport(), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }

    public function store(Request $request)
    {

        if (\Auth::user()->can('create chart of account')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'sub_type' => 'required',
                ]
            );
            //  also ajax submit 
            if ($request->ajax()) {
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors()->all(),
                    ], 422);
                }
            }
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            // dd('yes');
            if ($request->parent) {
                $type = ChartOfAccountSubType::where('id', $request->sub_type)->where('created_by', '=', \Auth::user()->creatorId())->first();

                $account = ChartOfAccount::where('id', $request->parent)->where('created_by', '=', \Auth::user()->creatorId())->first();
                if (!empty($account->name)) {
                    $existingparentAccount = ChartOfAccountParent::where('name', $account->name)->where('created_by', \Auth::user()->creatorId())->first();

                    if ($existingparentAccount) {
                        $parentAccount = $existingparentAccount;
                    } else {
                        $parentAccount              = new ChartOfAccountParent();
                    }
                    $parentAccount->name        = $account->name;
                    $parentAccount->sub_type    = $request->sub_type;
                    $parentAccount->type        = $type->type;
                    $parentAccount->account      = $request->parent;
                    $parentAccount->created_by  = \Auth::user()->creatorId();
                    $parentAccount->save();

                    $account              = new ChartOfAccount();
                    $account->name        = $request->name;
                    $account->code        = $request->code;
                    $account->type        = $type->type;
                    $account->sub_type    = $request->sub_type;
                    $account->parent      = $parentAccount->id;
                    $account->description = $request->description;
                    $account->is_enabled  = isset($request->is_enabled) ? 1 : 0;
                    $account->created_by  = \Auth::user()->creatorId();
                    $account->save();
                }
            } else {
                $type = ChartOfAccountSubType::where('id', $request->sub_type)->where('created_by', '=', \Auth::user()->creatorId())->first();
                $account              = new ChartOfAccount();
                $account->name        = $request->name;
                $account->code        = $request->code;
                $account->type        = $type->type;
                $account->sub_type    = $request->sub_type;
                $account->parent      = 0;
                $account->description = $request->description;
                $account->is_enabled  = isset($request->is_enabled) ? 1 : 0;
                $account->created_by  = \Auth::user()->creatorId();
                $account->save();
            }
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Account successfully created.'),
                    'data' => $account,
                    'type' => $account->type,
                    'id' => $account->id,
                    'name' => $account->name,
                ]);
            }
            return redirect()->route('chart-of-account.index')->with('success', __('Account successfully created.'));
        } else {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Permission denied.'),
                ], 401);
            }
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function show(ChartOfAccount $chartOfAccount, Request $request)
    {
        if (\Auth::user()->can('ledger report')) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start = $request->start_date;
                $end   = $request->end_date;
            } else {
                $start = date('Y-m-01');
                $end   = date('Y-m-t');
            }
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())
                    ->where('created_at', '>=', $start)
                    ->where('created_at', '<=', $end)
                    ->get()->pluck('code_name', 'id');
                $accounts->prepend('Select Account', '');
            } else {
                $accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                    ->where('created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $accounts->prepend('Select Account', '');
            }
            if (!empty($request->account)) {
                $account = ChartOfAccount::find($request->account);
            } else {
                $account = ChartOfAccount::find($chartOfAccount->id);
            }

            // $journalItems = JournalItem::select('journal_entries.journal_id', 'journal_entries.date as transaction_date', 'journal_items.*')
            //     ->leftjoin('journal_entries', 'journal_entries.id', 'journal_items.journal')
            //     ->where('journal_entries.created_by', '=', \Auth::user()->creatorId())
            //     ->where('account', !empty($account) ? $account->id : 0);
            // $journalItems->where('date', '>=', $start);
            // $journalItems->where('date', '<=', $end);
            // $journalItems = $journalItems->get();

            $balance = 0;
            $debit   = 0;
            $credit  = 0;

            // foreach($journalItems as $item)
            // {
            //     if($item->debit > 0)
            //     {
            //         $debit += $item->debit;
            //     }

            //     else
            //     {
            //         $credit += $item->credit;
            //     }

            //     $balance = $credit - $debit;
            // }

            $filter['startDateRange'] = $start;
            $filter['endDateRange']   = $end;

            return view('chartOfAccount.show', compact('filter', 'account', 'accounts'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function edit(ChartOfAccount $chartOfAccount)
    {
        $types = ChartOfAccountType::get()->pluck('name', 'id');
        $types->prepend('Select Account Type', 0);

        return view('chartOfAccount.edit', compact('chartOfAccount', 'types'));
    }


    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {

        if (\Auth::user()->can('edit chart of account')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }


            $chartOfAccount->name        = $request->name;
            $chartOfAccount->code        = $request->code;
            $chartOfAccount->description = $request->description;
            $chartOfAccount->is_enabled  = isset($request->is_enabled) ? 1 : 0;
            $chartOfAccount->save();



            return redirect()->route('chart-of-account.index')->with('success', __('Account successfully updated.'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function destroy(ChartOfAccount $chartOfAccount)
    {
        if (\Auth::user()->can('delete chart of account')) {
            $chartOfAccount->delete();

            return redirect()->route('chart-of-account.index')->with('success', __('Account successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getSubType(Request $request)
    {
        $types = ChartOfAccount::where('sub_type', $request->type)->get()->pluck('name', 'id');
        $types->prepend('Select an account', 0);

        return response()->json($types);
    }
}
