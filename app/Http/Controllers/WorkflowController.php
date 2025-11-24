<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\Department;
use App\Models\Designation;
use App\Models\LeadStage;
use App\Models\User;
use App\Models\Stage;
use App\Models\WorkFlow;
use App\Models\WorkFlowAction;
use DB;

use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $type = 'crm')
    {
        $user = \Auth::user();
        $ownerId = $user->type === 'company' ? $user->creatorId() : $user->ownedId();
        $column = ($user->type == 'company') ? 'created_by' : 'owned_by';
        $module = 'crm';
        switch($type){
            case 'crm':
                $module = 'crm';
                break;
            case 'hrm':
                $module = 'hrm';
                break;
            case 'project':
                $module = 'project';
                break;
            case 'accounts':
                $module = 'accounts';
                break;
            default:
                $module = 'crm';
        }
        $workflows = Workflow::where($column , $ownerId)->where('module',$module)->get();
        return view('workflow.index',compact('workflows','module'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return $this->renderWorkflowView('workflow.docs.create');
    }
    public function workflowHrm()
    {
        return $this->renderWorkflowView('workflow.docs.hrmcreate');
    }
    public function workflowProject()
    {

        return $this->renderWorkflowView('workflow.docs.projectcreate');
    }
    public function workflowAccount()
    {
        return $this->renderWorkflowView('workflow.docs.accountscreate');
    }
    private function renderWorkflowView($viewName)
    {
        $allusers = User::whereNotIn('type', ['client', 'super admin'])->get();
        $departments = Department::all();
        $designations = Designation::all();
        $leadcusotmCols = CustomField::where('module','lead')->get();
        //in array custom feilds and merge leadcolumns and this in a single array combined
        $leaddbcolumns = ['name-db', 'subject-db', 'sources-db', 'products-db'];
        $leadcolumns = array_merge($leadcusotmCols->pluck('name')->toArray(), $leaddbcolumns);
        $dealcolumns = ['price', 'sources', 'products', 'labels'];
        $contractcolumns = ['subject', 'value', 'project', 'contract type'];
        $invoicecolumns = ['category', 'customer','referance number'];
        $projectcolumns = ['name', 'budget', 'hours'];
        $taskcolumns = ['name', 'priority', 'hours', 'stage'];
        $checklistcolumns = [];
        $employeecolumns = ['d.o.b', 'gender', 'branch', 'department', 'designation'];
        $payslipcolumns = ['net_salary'];
        $leavecolumns = ['leave_type', 'days', 'status'];
        $resignationcolumns = ['resignation_date','notice_date'];
        $terminationcolumns = ['termination type'];
        $bugcolumns = ['title', 'priority'];
        $warningcolumns = ['subject'];
        //accounts
        $bankaccountcolumns = ['holder_name', 'bank_name', 'account_number'];
        $banktransfercolumns = ['from_account', 'to_account', 'amount', 'date', 'payment_method'];
        $customercolumns = ['name', 'email', 'contact'];
        $suppliercolumns = ['name', 'email', 'contact'];
        $bilcolumns = ['bill_date', 'due_date', 'order_number'];
        $expensecolumns = ['bill_date', 'due_date', 'order_number'];
        $revenuecolumns = ['amount'];
        $creditnotecolumns = ['invoice', 'customer', 'amount'];
        $paymentscolumns = ['date', 'amount'];
        $debitnotecolumns = ['bill', 'vendor', 'amount'];


        $leads = [];
        $deals = [];

        if (\Auth::user()->type == 'company') {
            $lead_stages = $this->getLeadStages('created_by');
            $deal_stages = $this->getDealStages('created_by');
            // $leads = $this->getLeads('created_by', $leadcolumns);
            // $deals = $this->getDeals('created_by', $dealcolumns);
        } else {
            $lead_stages = $this->getLeadStages('owned_by');
            $deal_stages = $this->getDealStages('owned_by');
            // $leads = $this->getLeads('owned_by', $leadcolumns);
            // $deals = $this->getDeals('owned_by', $dealcolumns);
        }

        // Preparing data for select dropdown
        $availableLeadColumns = $leadcolumns;
        $availableDealColumns = $dealcolumns;

        return view($viewName, compact(

            'allusers',
            'departments',
            'designations',
            'lead_stages',
            'deal_stages',
            'availableLeadColumns',
            'availableDealColumns',
            'contractcolumns',
            'invoicecolumns',
            'projectcolumns',
            'taskcolumns',
            'checklistcolumns',
            'bugcolumns',
            'employeecolumns',
            'payslipcolumns',
            'leavecolumns',
            'resignationcolumns',
            'terminationcolumns',
            'warningcolumns',
            //accounts
            'bankaccountcolumns',
            'banktransfercolumns',
            'customercolumns',
            'suppliercolumns',
            'bilcolumns',
            'expensecolumns',
            'revenuecolumns',
            'creditnotecolumns',
            'paymentscolumns',
            'debitnotecolumns'
        ));

    }
    private function getLeadStages($column)
    {
        return LeadStage::where($column, \Auth::user()->{$column === 'created_by' ? 'creatorId' : 'ownedId'}())->get();
    }
    private function getDealStages($column)
    {
        return Stage::where($column, \Auth::user()->{$column === 'created_by' ? 'creatorId' : 'ownedId'}())->get();
    }
    private function getLeads($column, $columns)
    {
        return DB::table('leads')
            ->select($columns)
            ->where($column, \Auth::user()->{$column === 'created_by' ? 'creatorId' : 'ownedId'}())
            ->get();
    }
    private function getDeals($column, $columns)
    {
        return DB::table('deals')
            ->select($columns)
            ->where($column, \Auth::user()->{$column === 'created_by' ? 'creatorId' : 'ownedId'}())
            ->get();
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $requestData = $request->input('drawflow');

        $requestData = $requestData['drawflow'];
        DB::beginTransaction();
        try {
            $workflow = new WorkFlow();

            $workflow->name = $request->input('type') . '-Workflow';
            $workflow->module = $request->input('type');
            $workflow->status = true;
            $workflow->created_by = \Auth::user()->creatorId();
            $workflow->save();
            if (!$workflow) {
                throw new \Exception("Failed to save Workflow");
            }
            if (isset($requestData['Home']['data'])) {
                $dataArray = $requestData['Home']['data'];
                foreach ($dataArray as $key => $value) {
                    // dd($value['assignedUser']);
                    $dom = new \DOMDocument();
                    @$dom->loadHTML($value['html']);
                    $xpath = new \DOMXPath($dom);

                    // Extract tabid and nodeid
                    $tabid = $xpath->evaluate('string(//input[@name="tabid"]/@value)');
                    // $tabparentid = $xpath->evaluate('string(//input[@name="tabparentid"]/@value)'); // hrm,crm,projec
                    $nodeid = $xpath->evaluate('string(//input[@name="nodeid"]/@value)');
                    // Save WorkFlowAction
                    $workflowaction = new WorkFlowAction();
                    $workflowaction->workflow_id = $workflow->id;
                    $workflowaction->level_id = $tabid ?? '';
                    $workflowaction->node_id = $nodeid ?? '';
                    $workflowaction->node_actual_id = $value['nodeActualId'] ?? '';
                    $workflowaction->inputs = json_encode($value['inputs']) ?? '';
                    $workflowaction->outputs = json_encode($value['outputs']) ?? '';
                    $workflowaction->assigned_users = json_encode(isset($value['assignedUser']) ? $value['assignedUser'] : '') ?? '';
                    $workflowaction->applied_conditions = json_encode(isset($value['appliedConditions']) ? $value['appliedConditions'] : '') ?? '';
                    $workflowaction->status = true;

                    if (!$workflowaction->save()) {
                        throw new \Exception("Failed to save WorkFlowAction");
                    }
                }
            } else {
                throw new \Exception("Data array not found in the request");
            }
            DB::commit();
            return response()->json(['message' => 'Workflow saved successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $workflow = Workflow::findOrFail($id);
        $workflow->status = $request->input('status');
        $workflow->save();
    
        return redirect()->back()->with('success', 'Workflow status updated successfully.');
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // decrypt id 
        $workflow = Workflow::find(\Crypt::decrypt($id));
        $workflow_actions = WorkFlowAction::where('workflow_id',\Crypt::decrypt($id))->get();
        // dd($workflow);
        $viewName = 'workflow.docs.show';
        if (!$workflow) {
            return redirect()->route('workflow.index')->with('error', 'Workflow not found.');
        }
        return view($viewName, compact('workflow','workflow_actions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // dd($id);
        $workflow = Workflow::find($id);
        $workflow_actions = WorkFlowAction::where('workflow_id',$id)->get();
        // dd($workflow);
        $allusers = User::whereNotIn('type', ['client', 'super admin'])->get();
        $departments = Department::all();
        $designations = Designation::all();
        
        $leadcolumns = ['name', 'subject', 'sources', 'products'];
        $dealcolumns = ['price', 'sources', 'products', 'labels'];
        $contractcolumns = ['subject', 'value', 'project', 'contract type'];
        $invoicecolumns = ['category', 'customer', 'amount'];
        $projectcolumns = ['name', 'budget', 'hours'];
        $viewName = 'workflow.docs.edit.crm';
        $leads = [];
        $deals = [];

        if (\Auth::user()->type == 'company') {
            $lead_stages = $this->getLeadStages('created_by');
            $deal_stages = $this->getDealStages('created_by');
            $leads = $this->getLeads('created_by', $leadcolumns);
            $deals = $this->getDeals('created_by', $dealcolumns);
        } else {
            $lead_stages = $this->getLeadStages('owned_by');
            $deal_stages = $this->getDealStages('owned_by');
            $leads = $this->getLeads('owned_by', $leadcolumns);
            $deals = $this->getDeals('owned_by', $dealcolumns);
        }

        // Preparing data for select dropdown
        $availableLeadColumns = $leadcolumns;
        $availableDealColumns = $dealcolumns;
        if (!$workflow) {
            return redirect()->route('workflow.index')->with('error', 'Workflow not found.');
        }
        return view($viewName, compact('workflow','workflow_actions','designations','contractcolumns','invoicecolumns','projectcolumns','allusers','departments','lead_stages','deal_stages','availableLeadColumns','availableDealColumns'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $requestData = $request->input('drawflow');
        $requestData = $requestData['drawflow'];
        $backslash = "\{";
        DB::beginTransaction();
        try {
            if (isset($requestData['Home']['data'])) {
                $dataArray = $requestData['Home']['data'];
                foreach ($dataArray as $key => $value) {
                    $dom = new \DOMDocument();
                    @$dom->loadHTML($value['html']);
                    $xpath = new \DOMXPath($dom);
                    $tabid = $xpath->evaluate('string(//input[@name="tabid"]/@value)');
                    $nodeid = $xpath->evaluate('string(//input[@name="nodeid"]/@value)');
                    $workflowaction = WorkFlowAction::find($value['actionId']);
                    $workflowaction->workflow_id = $id;
                    $workflowaction->level_id = $tabid ?? '';
                    $workflowaction->node_id = $nodeid ?? '';
                    $workflowaction->node_actual_id = $value['nodeActualId'] ?? '';
                    $workflowaction->inputs = json_encode($value['inputs']) ?? '';
                    $workflowaction->outputs = json_encode($value['outputs']) ?? '';
                    if (isset($value['assignedUser']) && preg_match('/\\\\/', $value['assignedUser'])) {
                        // dd('found');
                        $workflowaction->assigned_users = $value['assignedUser'] ?? '';
                    } else {
                        // dd('not found');
                        $workflowaction->assigned_users = json_encode($value['assignedUser']) ?? '';
                    }
                    if (isset($value['appliedConditions']) && preg_match('/\\\\/', $value['appliedConditions'])) {
                        $workflowaction->applied_conditions = $value['appliedConditions'] ?? '';
                    } else {
                        $workflowaction->applied_conditions = json_encode($value['appliedConditions']) ?? '';
                    }
                    $workflowaction->status = true;

                    if (!$workflowaction->save()) {
                        throw new \Exception("Failed to Update WorkFlowAction");
                    }
                }
            }else {
                throw new \Exception("Data array not found in the request");
            }
            DB::commit();
            return response()->json(['message' => 'Workflow Updat successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
