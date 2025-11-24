<?php

namespace App\Http\Controllers\sync;
use App\Http\Controllers\Controller;
use App\DataTables\TrialBalanceDataTable;
use App\Models\ChartOfAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrialBalanceController extends Controller
{
    public function __construct()
    {
        // parent::__construct();
        $this->pageTitle = 'Trial Balance';
        // $this->middleware(function ($request, $next) {
        //     abort_403(!in_array('accounting', $this->user->modules));
        //     return $next($request);
        // });
    }

    public function index(TrialBalanceDataTable $dataTable, Request $request)
    {
        // $viewPermission = user()->permission('view_accounting');
        // abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        $this->pageTitle = 'Trial Balance';
        $this->accounts = ChartOfAccount::where('company_id', 2)
            ->orderBy('type')
            ->orderBy('sub_type')
            ->get();


         return $dataTable->render('sync.trial-balance.index', [
            'pageTitle' => $this->pageTitle,
            'startDate' => $request->get('start_date', date('Y-01-01')),
            'endDate' => $request->get('end_date', date('Y-m-d', strtotime('+1 day'))),
            'accounting_method' => false,
        ]);

        // return $dataTable->render('sync.trial-balance.index', $this->data);
    }
}