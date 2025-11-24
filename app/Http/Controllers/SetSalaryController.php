<?php

namespace App\Http\Controllers;

use App\Models\Allowance;
use App\Models\AllowanceOption;
use App\Models\Commission;
use App\Models\DeductionOption;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\LoanOption;
use App\Models\OtherPayment;
use App\Models\BankAccount;
use App\Models\Overtime;
use App\Models\PayslipType;
use App\Models\SaturationDeduction;
use App\Models\Utility;
use App\Models\User;
use App\Models\Plan;
use Hash;
use Illuminate\Http\Request;

class SetSalaryController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage set salary'))
        {
            if (\Auth::user()->type == 'company') {
                $employees = Employee::where('created_by' , \Auth::user()->creatorId())->with('salaryType')->get();
            } else {
                $employees = Employee::where('owned_by' , \Auth::user()->ownedId())->with('salaryType')->get();
            }

            return view('setsalary.index', compact('employees'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {
        if(\Auth::user()->can('edit set salary'))
        {
            $payslip_type      = PayslipType::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $allowance_options = AllowanceOption::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $loan_options      = LoanOption::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $deduction_options = DeductionOption::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            if(\Auth::user()->type == 'Employee')
            {
                $currentEmployee      = Employee::where('user_id', '=', \Auth::user()->id)->first();
                $allowances           = Allowance::where('employee_id', $currentEmployee->id)->get();
                $commissions          = Commission::where('employee_id', $currentEmployee->id)->get();
                $loans                = Loan::where('employee_id', $currentEmployee->id)->get();
                $saturationdeductions = SaturationDeduction::where('employee_id', $currentEmployee->id)->get();
                $otherpayments        = OtherPayment::where('employee_id', $currentEmployee->id)->get();
                $overtimes            = Overtime::where('employee_id', $currentEmployee->id)->get();
                $employee             = Employee::where('user_id', '=', \Auth::user()->id)->first();
                return view('setsalary.employee_salary', compact('employee', 'payslip_type', 'allowance_options', 'commissions', 'loan_options', 'overtimes', 'otherpayments', 'saturationdeductions', 'loans', 'deduction_options', 'allowances'));
            }
            else
            {
                $allowances           = Allowance::where('employee_id', $id)->get();
                $commissions          = Commission::where('employee_id', $id)->get();
                $loans                = Loan::where('employee_id', $id)->get();
                $saturationdeductions = SaturationDeduction::where('employee_id', $id)->get();
                $otherpayments        = OtherPayment::where('employee_id', $id)->get();
                $overtimes            = Overtime::where('employee_id', $id)->get();
                $employee             = Employee::find($id);
                return view('setsalary.edit', compact('employee', 'payslip_type', 'allowance_options', 'commissions', 'loan_options', 'overtimes', 'otherpayments', 'saturationdeductions', 'loans', 'deduction_options', 'allowances'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id)
    {
        if (\Auth::user()->type == 'company') {
            $payslip_type      = PayslipType::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $allowance_options = AllowanceOption::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $loan_options      = LoanOption::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $deduction_options = DeductionOption::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        } else {
            $payslip_type      = PayslipType::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
            $allowance_options = AllowanceOption::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
            $loan_options      = LoanOption::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
            $deduction_options = DeductionOption::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
        }

        if(\Auth::user()->type == 'Employee')
        {
            $currentEmployee      = Employee::where('user_id', '=', \Auth::user()->id)->first();
            $allowances           = Allowance::where('employee_id', $currentEmployee->id)->with(['employee','allowanceOption'])->get();
            $commissions          = Commission::where('employee_id', $currentEmployee->id)->with(['employee'])->get();
            $loans                = Loan::where('employee_id', $currentEmployee->id)->With(['employee','loanOption'])->get();
            $saturationdeductions = SaturationDeduction::where('employee_id', $currentEmployee->id)->with(['deductionOption'])->get();
            $otherpayments        = OtherPayment::where('employee_id', $currentEmployee->id)->get();
            $overtimes            = Overtime::where('employee_id', $currentEmployee->id)->get();
            $employee             = Employee::where('user_id', '=', \Auth::user()->id)->with('salaryType')->first();

            foreach ( $allowances as  $value) {
                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            foreach ( $commissions as  $value) {
                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            foreach ( $loans as  $value) {
                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            foreach ( $saturationdeductions as  $value) {
                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            foreach ( $otherpayments as  $value) {
                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            return view('setsalary.employee_salary', compact('employee', 'payslip_type', 'allowance_options', 'commissions', 'loan_options', 'overtimes', 'otherpayments', 'saturationdeductions', 'loans', 'deduction_options', 'allowances'));


        }
        else
        {
            $allowances           = Allowance::where('employee_id', $id)->get();
            $commissions          = Commission::where('employee_id', $id)->get();
            $loans                = Loan::where('employee_id', $id)->get();
            $saturationdeductions = SaturationDeduction::where('employee_id', $id)->get();
            $otherpayments        = OtherPayment::where('employee_id', $id)->get();
            $overtimes            = Overtime::where('employee_id', $id)->get();
            $employee             = Employee::with('salaryType')->find($id);

            foreach ( $allowances as  $value) {

                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::with('salaryType')->find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            foreach ( $commissions as  $value) {
                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::with('salaryType')->find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            foreach ( $loans as  $value) {
                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::with('salaryType')->find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            foreach ( $saturationdeductions as  $value) {
                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::with('salaryType')->find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            foreach ( $otherpayments as  $value) {
                if(  $value->type == 'percentage' )
                {
                    $employee          = Employee::with('salaryType')->find($value->employee_id);
                    $empsal  = $value->amount * $employee->salary / 100;
                    $value->tota_allow = $empsal;
                }
            }

            return view('setsalary.employee_salary', compact('employee', 'payslip_type', 'allowance_options', 'commissions', 'loan_options', 'overtimes', 'otherpayments', 'saturationdeductions', 'loans', 'deduction_options', 'allowances'));
        }

    }


    public function employeeUpdateSalary(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(), [
                               'salary_type' => 'required',
                               'salary' => 'required',
                           ]
        );
        if($validator->fails())
        {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }
        $employee = Employee::findOrFail($id);
        $input    = $request->all();
        $employee->fill($input)->save();
        Utility::makeActivityLog(\Auth::user()->id,'Employee Salary',$employee->id,'Create Employee Salary',$employee->name);
        return redirect()->back()->with('success', 'Employee Salary Updated.');
    }

    public function employeeSalary()
    {
        if(\Auth::user()->type == "employee")
        {
            $employees = Employee::where('user_id', \Auth::user()->id)->get();
            return view('setsalary.index', compact('employees'));
        }
    }

    public function employeeBasicSalary($id)
    {
        if (\Auth::user()->type == 'company') {
            $payslip_type = PayslipType::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        } else {
            $payslip_type = PayslipType::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
        }
        $payslip_type->prepend('Select Payslip Type','');
        $account = BankAccount::where('created_by', \Auth::user()->creatorId())->where('holder_name' , '!=', 'cash')->get()->pluck('bank_name', 'id');
        // dd($account);
        $employee     = Employee::find($id);
        return view('setsalary.basic_salary', compact('employee', 'payslip_type' , 'account'));
    }
           public function employee_create()
    {
        return view('setsalary.employeecreate');
    }

    public function employee_store(Request $request)
    {
        \DB::beginTransaction();
        try {
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'dob' => 'required',
                    'phone' => 'required',
                    
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->withInput()->with('error', $messages->first());
            }

            $objUser = User::find(\Auth::user()->creatorId());
            $total_employee = $objUser->countEmployees();
            $plan = Plan::find($objUser->plan);

            if ($total_employee < $plan->max_users || $plan->max_users == -1) {
                $user = User::create(
                    [
                        'name' => $request['name'],
                        'email' => $request['name'] . '@example.com',
                        // 'gender'=>$request['gender'],
                        'password' => Hash::make('pass'),
                        'type' => 'employee',
                        'lang' => 'en',
                        'created_by' => \Auth::user()->creatorId(),
                        'owned_by' => \Auth::user()->ownedId(),
                    ]
                );
                $user->save();
                $user->assignRole('Employee');
            } else {
                return redirect()->back()->with('error', __('Your employee limit is over, Please upgrade plan.'));
            }

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->first();
            $department = Department::where('created_by', \Auth::user()->creatorId())->where('branch_id',@$branch->id??0)->first();
            $designation = Designation::where('created_by', \Auth::user()->creatorId())->where('department_id',@$department->id??0)->first();
            $employee = Employee::create(
                [
                    'user_id' => $user->id,
                    'name' => $request['name'],
                    'dob' => $request['dob'],
                    'gender' => $request['gender'],
                    'phone' => $request['phone'],
                    'email' => $request['name'] . '@example.com',
                    'password' => Hash::make($request['pass']),
                    'employee_id' => $this->employeeNumber(),
                    'branch_id' => @$branch->id??0,
                    'department_id' => @$department->id??0,
                    'designation_id' => @$designation->id??0,
                    'company_doj' => $request['company_doj'],
                    'created_by' => \Auth::user()->creatorId(),
                    'owned_by' => \Auth::user()->ownedId(),
                ]
            );

            \DB::commit();
            return redirect()->route('setsalary.index')->with('success', __('Employee  successfully created.'));
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e);
        }
    }

    function employeeNumber()
    {
        $latest = Employee::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->employee_id + 1;
    }


}
