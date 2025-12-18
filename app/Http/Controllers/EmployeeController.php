<?php

namespace App\Http\Controllers;

use App\Exports\CustomerExport;
use App\Exports\EmployeeExport;
use App\Imports\EmployeesImport;
use App\Models\Branch;
use App\Models\CustomField;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\ExperienceCertificate;
use App\Models\JoiningLetter;
use App\Models\NOC;
use App\Models\Plan;
use App\Models\Termination;
use App\Models\User;
use App\Models\Utility;
use App\Models\WorkFlow;
use App\Models\Notification;
use App\Models\WorkFlowAction;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use DB;
//use Faker\Provider\File;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(\App\DataTables\QboEmployeesDataTable $dataTable)
    {
        if (\Auth::user()->can('manage employee')) {
            // Get status filter from request
            $status = request()->get('status', 'Active');
            $dataTable->setStatus($status);
            
            // For AJAX requests, return DataTable JSON
            if (request()->ajax()) {
                return $dataTable->render('employee.index');
            }
            
            // For regular requests, pass to view with DataTable
            return $dataTable->render('employee.index');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create employee')) {
            if (\Auth::user()->type == 'company') {
                $documents = Document::where('created_by', \Auth::user()->creatorId())->get();
                $branches = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $employees = User::where('created_by', \Auth::user()->creatorId())->get();
                $employee_report = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->prepend('Select Report to user', '');
            } else {
                $documents = Document::where('owned_by', \Auth::user()->ownedId())->get();
                $branches = Branch::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                $departments = Department::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                $designations = Designation::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                $employees = User::where('owned_by', \Auth::user()->ownedId())->get();
                $employee_report = Employee::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id')->prepend('Select Report to user', '');
            }
            $employeesId = \Auth::user()->employeeIdFormat($this->employeeNumber());
            $company_settings = Utility::settings();
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'employee')->get();

            return view('employee.create', compact('employees', 'employeesId', 'departments', 'designations', 'documents', 'branches', 'company_settings', 'employee_report', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        \DB::beginTransaction();
        try {
        if (\Auth::user()->can('create employee')) {
            
            // Check if this is from the QBO modal (simplified form)
            $isQboForm = $request->has('first_name') && !$request->has('name');
            
            if ($isQboForm) {
                // QBO Modal - simplified validation
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'first_name' => 'required|string|max:100',
                        'last_name' => 'required|string|max:100',
                        'email' => 'nullable|email|unique:employees,email',
                    ]
                );
                
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return response()->json(['message' => $messages->first()], 422);
                }
                
                // Build full name from parts
                $fullName = trim($request->first_name . ' ' . ($request->middle_initial ? $request->middle_initial . '. ' : '') . $request->last_name);
                
                // Create employee with QBO fields
                $employee = Employee::create([
                    'first_name' => $request->first_name,
                    'middle_initial' => $request->middle_initial,
                    'last_name' => $request->last_name,
                    'name' => $fullName,
                    'display_name' => $fullName,
                    'email' => $request->email,
                    'hire_date' => $request->hire_date,
                    'status' => 'Active',
                    'is_active' => 1,
                    'employee_id' => $this->employeeNumber(),
                    'created_by' => \Auth::user()->creatorId(),
                    'owned_by' => \Auth::user()->ownedId(),
                ]);
                
                Utility::makeActivityLog(\Auth::user()->id, 'Employee', $employee->id, 'Create Employee', $employee->name);
                \DB::commit();
                
                return response()->json(['success' => true, 'message' => __('Employee successfully created.')]);
            }
            
            // Original full form validation
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'dob' => 'required',
                    'phone' => 'required',
                    'address' => 'required',
                    'email' => 'required|unique:users',
                    'password' => 'required',
                    'branch_id' => 'required',
                    'department_id' => 'required',
                    'designation_id' => 'required',
                    'biometric_emp_id' => 'required',
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
                        'email' => $request['email'],
                        // 'gender'=>$request['gender'],
                        'password' => Hash::make($request['password']),
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


            if (!empty($request->document) && !is_null($request->document)) {
                $document_implode = implode(',', array_keys($request->document));
            } else {
                $document_implode = null;
            }

            $employee = Employee::create(
                [
                    'user_id' => $user->id,
                    'name' => $request['name'],
                    'dob' => $request['dob'],
                    'gender' => $request['gender'],
                    'phone' => $request['phone'],
                    'address' => $request['address'],
                    'email' => $request['email'],
                    'password' => Hash::make($request['password']),
                    'employee_id' => $this->employeeNumber(),
                    'biometric_emp_id' => !empty($request['biometric_emp_id']) ? $request['biometric_emp_id'] : '',
                    'branch_id' => $request['branch_id'],
                    'department_id' => $request['department_id'],
                    'designation_id' => $request['designation_id'],
                    'company_doj' => $request['company_doj'],
                    'documents' => $document_implode,
                    'account_holder_name' => $request['account_holder_name'],
                    'account_number' => $request['account_number'],
                    'bank_name' => $request['bank_name'],
                    'bank_identifier_code' => $request['bank_identifier_code'],
                    'branch_location' => $request['branch_location'],
                    'tax_payer_id' => $request['tax_payer_id'],
                    'created_by' => \Auth::user()->creatorId(),
                    'owned_by' => \Auth::user()->ownedId(),
                ]
            );

            CustomField::saveData($employee, $request->customField);

            if ($request->hasFile('document')) {
                foreach ($request->document as $key => $document) {

                    $filenameWithExt = $request->file('document')[$key]->getClientOriginalName();
                    $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension = $request->file('document')[$key]->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    $dir = storage_path('uploads/document/');
                    $image_path = $dir . $filenameWithExt;

                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }

                    if (!file_exists($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    $path = $request->file('document')[$key]->storeAs('uploads/document/', $fileNameToStore);
                    $employee_document = EmployeeDocument::create(
                        [
                            'employee_id' => $employee['employee_id'],
                            'document_id' => $key,
                            'document_value' => $fileNameToStore,
                            'created_by' => \Auth::user()->creatorId(),
                            'owned_by' => \Auth::user()->ownedId(),
                        ]
                    );
                    $employee_document->save();

                }

            }


            // // WorkFlow get which is active
            $us_mail = 'false';
            $us_notify = 'false';
            $us_approve = 'false';
            $usr_Notification = [];
            $workflow = WorkFlow::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'hrm')->where('status', 1)->first();
            if ($workflow) {
                $workflowaction = WorkFlowAction::where('workflow_id', $workflow->id)->where('status', 1)->get();
                foreach ($workflowaction as $action) {
                    $useraction = json_decode($action->assigned_users);
                    if (strtolower('create-employee') == $action->node_id) {
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
                                'd.o.b' => 'dob',
                                'gender' => 'gender',
                                'branch' => 'branch_name',
                                'department' => 'department_name',
                                'designation' => 'designation_name',
                            ];
                            $relate = [
                                'branch_name' => 'branch',
                                'department_name' => 'department',
                                'designation_name' => 'designation',
                            ];
                            foreach ($applied_conditions['conditions'] as $conditionGroup) {
                                if (in_array($conditionGroup['action'], ['send_email', 'send_notification', 'send_approval'])) {
                                    $query = Employee::where('id', $employee->id);
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
                                        "data_id" => $employee->id,
                                        "name" => @$employee->name,
                                    ];
                                    if($us_notify == 'true'){
                                        Utility::makeNotification($usrLead,'create_employee',$data,$employee->id,'create Employee');
                                    }elseif($us_approve == 'true'){
                                        Utility::makeNotification($usrLead,'approve_employee',$data,$employee->id,'For Approval Employee');
                                    }
                                }
                            }
                        }
                    }
                }
            }


            $setings = Utility::settings();

            if ($setings['new_user'] == 1) {
                $userArr = [
                    'email' => $user->email,
                    'password' => $user->password,
                ];

                $resp = Utility::sendEmailTemplate('new_user', [$user->id => $user->email], $userArr);
                Utility::makeActivityLog(\Auth::user()->id, 'Employee', $employee->id, 'Create Employee', $employee->name);
                \DB::commit();
                return redirect()->route('employee.index')->with('success', __('Employee successfully created.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
            Utility::makeActivityLog(\Auth::user()->id, 'Employee', $employee->id, 'Create Employee', $employee->name);
            \DB::commit();
            return redirect()->route('employee.index')->with('success', __('Employee  successfully created.'));

        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->back()->with('error', $e);
        }
    }

    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        if (\Auth::user()->can('edit employee')) {
            $employee = Employee::find($id);
            //  $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);
            $employeesId = \Auth::user()->employeeIdFormat(!empty($employee) ? $employee->employee_id : '');
            $departmentData = Department::where('created_by', \Auth::user()->creatorId())->where('branch_id', $employee->branch_id)->get()->pluck('name', 'id');
            if (\Auth::user()->type == 'company') {
                $documents = Document::where('created_by', \Auth::user()->creatorId())->get();
                $branches = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $employee_report = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id')->prepend('Select Report to user', '');
            } else {
                $documents = Document::where('owned_by', \Auth::user()->ownedId())->get();
                $branches = Branch::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                $departments = Department::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                $designations = Designation::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                $employee_report = Employee::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id')->prepend('Select Report to user', '');
            }
            $customFields = CustomField::where('module', '=', 'employee')->get();
            $employee->customField = CustomField::getData($employee, 'employee')->toArray();

            return view('employee.edit', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents', 'departmentData', 'employee_report', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if (\Auth::user()->can('edit employee')) {
            // Handle QBO profile modal form types
            $formType = $request->input('form_type');
            
            if (in_array($formType, ['personal_info', 'employment_details', 'emergency_contact'])) {
                try {
                    $empId = Crypt::decrypt($id);
                } catch (\Throwable $th) {
                    return redirect()->back()->with('error', __('Employee Not Found.'));
                }
                
                $employee = Employee::findOrFail($empId);
                
                if ($formType === 'personal_info') {
                    // Update personal info fields
                    $employee->update([
                        'title' => $request->title,
                        'first_name' => $request->first_name,
                        'middle_initial' => $request->middle_initial,
                        'last_name' => $request->last_name,
                        'preferred_first_name' => $request->preferred_first_name,
                        'display_name' => trim($request->first_name . ' ' . ($request->middle_initial ? $request->middle_initial . '. ' : '') . $request->last_name),
                        'name' => trim($request->first_name . ' ' . ($request->middle_initial ? $request->middle_initial . '. ' : '') . $request->last_name),
                        'email' => $request->email,
                        'home_phone' => $request->home_phone,
                        'home_phone_ext' => $request->home_phone_ext,
                        'work_phone' => $request->work_phone,
                        'work_phone_ext' => $request->work_phone_ext,
                        'mobile_phone' => $request->mobile_phone,
                        'phone' => $request->mobile_phone ?? $request->home_phone ?? $request->work_phone,
                        'address' => $request->address,
                        'city' => $request->city,
                        'state' => $request->state,
                        'zip' => $request->zip,
                        'mailing_address_same' => $request->has('mailing_address_same') ? 1 : 0,
                        'birth_date' => $request->birth_date,
                        'dob' => $request->birth_date,
                        'gender' => $request->gender,
                        'ssn' => $request->ssn,
                    ]);
                } elseif ($formType === 'employment_details') {
                    // Update employment details fields
                    $employee->update([
                        'status' => $request->status,
                        'is_active' => $request->status === 'Active' ? 1 : 0,
                        'hire_date' => $request->hire_date,
                        'company_doj' => $request->hire_date,
                        'manager_id' => $request->manager_id,
                        'department_name' => $request->department_name,
                        'job_title' => $request->job_title,
                        'employee_id' => $request->employee_id,
                        'name_on_checks' => $request->name_on_checks,
                        'billing_rate' => $request->billing_rate,
                        'billable_by_default' => $request->has('billable_by_default') ? 1 : 0,
                    ]);
                } elseif ($formType === 'emergency_contact') {
                    // Update emergency contact fields
                    $employee->update([
                        'emergency_first_name' => $request->emergency_first_name,
                        'emergency_last_name' => $request->emergency_last_name,
                        'emergency_relationship' => $request->emergency_relationship,
                        'emergency_phone' => $request->emergency_phone,
                        'emergency_email' => $request->emergency_email,
                    ]);
                }
                
                Utility::makeActivityLog(\Auth::user()->id, 'Employee', $employee->id, 'Update Employee', $employee->name);
                
                return redirect()->back()->with('success', __('Employee updated successfully.'));
            }
            
            // Original full form validation for legacy edit page
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'dob' => 'required',
                    'gender' => 'required',
                    'phone' => 'required|numeric',
                    'address' => 'required',
                    //                                   'document.*' => 'mimes:jpeg,png,jpg,gif,svg,pdf,doc,zip|max:20480',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $employee = Employee::findOrFail($id);

            if ($request->document) {
                foreach ($request->document as $key => $document) {
                    if (!empty($document)) {
                        $filenameWithExt = $request->file('document')[$key]->getClientOriginalName();
                        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension = $request->file('document')[$key]->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                        //                        $dir        = storage_path('uploads/document/');
                        $dir = 'uploads/document/';

                        $image_path = $dir . $filenameWithExt;

                        if (File::exists($image_path)) {
                            File::delete($image_path);
                        }
                        //                        if(!file_exists($dir))
//                        {
//                            mkdir($dir, 0777, true);
//                        }
//                        $path = $request->file('document')[$key]->storeAs('uploads/document/', $fileNameToStore);

                        $path = \Utility::upload_coustom_file($request, 'document', $fileNameToStore, $dir, $key, []);


                        if ($path['flag'] == 1) {
                            $url = $path['url'];
                        } else {
                            return redirect()->back()->with('error', __($path['msg']));
                        }


                        $employee_document = EmployeeDocument::where('employee_id', $employee->employee_id)->where('document_id', $key)->first();

                        if (!empty($employee_document)) {
                            $employee_document->document_value = $fileNameToStore;
                            $employee_document->save();
                        } else {
                            $employee_document = new EmployeeDocument();
                            $employee_document->employee_id = $employee->employee_id;
                            $employee_document->document_id = $key;
                            $employee_document->document_value = $fileNameToStore;
                            $employee_document->save();
                        }

                    }
                }
            }
            $employee = Employee::findOrFail($id);
            $input = $request->all();
            $employee->fill($input)->save();
            CustomField::saveData($employee, $request->customField);
            $employee = Employee::find($id);
            $user = User::where('id', $employee->user_id)->first();
            if (!empty($user)) {
                $user->name = $employee->name;
                $user->email = $employee->email;
                $user->save();
            }
            if ($request->salary) {
                //log will be here
                Utility::makeActivityLog(\Auth::user()->id, 'Employee', $employee->id, 'Update Employee', $employee->name);
                return redirect()->route('setsalary.index')->with('success', 'Employee successfully updated.');
            }

            if (\Auth::user()->type != 'employee') {
                Utility::makeActivityLog(\Auth::user()->id, 'Employee', $employee->id, 'Update Employee', $employee->name);
                return redirect()->route('employee.index')->with('success', 'Employee successfully updated.');
            } else {
                Utility::makeActivityLog(\Auth::user()->id, 'Employee', $employee->id, 'Update Employee', $employee->name);
                return redirect()->route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))->with('success', 'Employee successfully updated.');
            }

        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {

        if (Auth::user()->can('delete employee')) {
            $employee = Employee::findOrFail($id);
            $user = User::where('id', '=', $employee->user_id)->first();
            $emp_documents = EmployeeDocument::where('employee_id', $employee->employee_id)->get();
            Utility::makeActivityLog(\Auth::user()->id, 'Employee', $employee->id, 'Delete Employee', $employee->name);

            $employee->delete();
            $user->delete();
            $dir = storage_path('uploads/document/');
            foreach ($emp_documents as $emp_document) {
                $emp_document->delete();
                if (!empty($emp_document->document_value)) {
                    unlink($dir . $emp_document->document_value);
                }

            }

            return redirect()->route('employee.index')->with('success', 'Employee successfully deleted.');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    public function show($id)
    {

        if (\Auth::user()->can('view employee')) {
            try {
                $empId = Crypt::decrypt($id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }

            $empId = Crypt::decrypt($id);
            $documents = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $employee = Employee::where('id', $empId)->first();

            $employeesId = \Auth::user()->employeeIdFormat(!empty($employee) ? $employee->employee_id : '');


            return view('employee.employeeProfile', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the Edit Personal Info page
     */
    public function editPersonalInfo($id)
    {
        if (\Auth::user()->can('edit employee')) {
            try {
                $empId = Crypt::decrypt($id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }

            $employee = Employee::where('id', $empId)->first();
            if (!$employee) {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }

            return view('employee.edit_personal_info', compact('employee'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the Edit Employment Details page
     */
    public function editEmploymentDetails($id)
    {
        if (\Auth::user()->can('edit employee')) {
            try {
                $empId = Crypt::decrypt($id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }

            $employee = Employee::where('id', $empId)->first();
            if (!$employee) {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }

            $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('employee.edit_employment_details', compact('employee', 'departments'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the Edit Emergency Contact page
     */
    public function editEmergencyContact($id)
    {
        if (\Auth::user()->can('edit employee')) {
            try {
                $empId = Crypt::decrypt($id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }

            $employee = Employee::where('id', $empId)->first();
            if (!$employee) {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }

            return view('employee.edit_emergency_contact', compact('employee'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function json(Request $request)
    {
        $designations = Designation::where('department_id', $request->department_id)->get()->pluck('name', 'id')->toArray();

        return response()->json($designations);
    }

    function employeeNumber()
    {
        $latest = Employee::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->employee_id + 1;
    }

    public function profile(Request $request)
    {
        if (\Auth::user()->can('manage employee profile')) {
            if (\Auth::user()->type == 'company') {
                $employees = Employee::where('created_by', \Auth::user()->creatorId());
                $brances = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $brances->prepend('All', '');

                $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $departments->prepend('All', '');

                $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $designations->prepend('All', '');

            } else {
                $employees = Employee::where('owned_by', \Auth::user()->ownedId());
                $brances = Branch::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                $brances->prepend('All', '');

                $departments = Department::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                $departments->prepend('All', '');

                $designations = Designation::where('owned_by', \Auth::user()->ownedId())->get()->pluck('name', 'id');
                $designations->prepend('All', '');
            }
            if (!empty($request->branch)) {
                $employees->where('branch_id', $request->branch);
            }
            if (!empty($request->department)) {
                $employees->where('department_id', $request->department);
            }
            if (!empty($request->designation)) {
                $employees->where('designation_id', $request->designation);
            }
            $employees = $employees->get();
            return view('employee.profile', compact('employees', 'departments', 'designations', 'brances'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function profileShow($id)
    {
        if (\Auth::user()->can('show employee profile')) {
            $empId = Crypt::decrypt($id);
            $documents = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employee = Employee::find($empId);
            $employeesId = \Auth::user()->employeeIdFormat($employee->employee_id);

            return view('employee.show', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function lastLogin()
    {
        $users = User::where('created_by', \Auth::user()->creatorId())->get();

        return view('employee.lastLogin', compact('users'));
    }

    public function employeeJson(Request $request)
    {
        $employees = Employee::where('branch_id', $request->branch)->get()->pluck('name', 'id')->toArray();

        return response()->json($employees);
    }

    public function getdepartment(Request $request)
    {

        if ($request->branch_id == 0) {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        } else {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->where('branch_id', $request->branch_id)->get()->pluck('name', 'id')->toArray();
        }

        return response()->json($departments);
    }

    public function joiningletterPdf($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $joiningletter = JoiningLetter::where(['lang' => $currantLang, 'created_by' => \Auth::user()->creatorId()])->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
        $obj = [
            'date' => \Auth::user()->dateFormat($date),
            'app_name' => env('APP_NAME'),
            'employee_name' => $employees->name,
            'address' => !empty($employees->address) ? $employees->address : '',
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'start_date' => !empty($employees->company_doj) ? $employees->company_doj : '',
            'branch' => !empty($employees->Branch->name) ? $employees->Branch->name : '',
            'start_time' => !empty($settings['company_start_time']) ? $settings['company_start_time'] : '',
            'end_time' => !empty($settings['company_end_time']) ? $settings['company_end_time'] : '',
            'total_hours' => $result,
        ];

        $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
        return view('employee.template.joiningletterpdf', compact('joiningletter', 'employees'));

    }
    public function joiningletterDoc($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $joiningletter = JoiningLetter::where(['lang' => $currantLang, 'created_by' => \Auth::user()->creatorId()])->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);



        $obj = [
            'date' => \Auth::user()->dateFormat($date),

            'app_name' => env('APP_NAME'),
            'employee_name' => $employees->name,
            'address' => !empty($employees->address) ? $employees->address : '',
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'start_date' => !empty($employees->company_doj) ? $employees->company_doj : '',
            'branch' => !empty($employees->Branch->name) ? $employees->Branch->name : '',
            'start_time' => !empty($settings['company_start_time']) ? $settings['company_start_time'] : '',
            'end_time' => !empty($settings['company_end_time']) ? $settings['company_end_time'] : '',
            'total_hours' => $result,
            //

        ];
        // dd($obj);
        $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
        return view('employee.template.joiningletterdocx', compact('joiningletter', 'employees'));

    }
    public function ExpCertificatePdf($id)
    {
        $currantLang = \Cookie::get('LANGUAGE');
        if (!isset($currantLang)) {
            $currantLang = 'en';
        }
        $termination = Termination::where('employee_id', $id)->first();
        $experience_certificate = ExperienceCertificate::where(['lang' => $currantLang, 'created_by' => \Auth::user()->creatorId()])->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        // dd($employees->salaryType->name);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
        $date1 = date_create($employees->company_doj);
        $date2 = date_create($employees->termination_date);
        $diff = date_diff($date1, $date2);
        $duration = $diff->format("%a days");

        if (!empty($termination->termination_date)) {

            $obj = [
                'date' => \Auth::user()->dateFormat($date),
                'app_name' => env('APP_NAME'),
                'employee_name' => $employees->name,
                'payroll' => !empty($employees->salaryType->name) ? $employees->salaryType->name : '',
                'duration' => $duration,
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',

            ];
        } else {
            return redirect()->back()->with('error', __('Termination date is required.'));
        }


        $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
        return view('employee.template.ExpCertificatepdf', compact('experience_certificate', 'employees'));

    }
    public function ExpCertificateDoc($id)
    {
        $currantLang = \Cookie::get('LANGUAGE');
        if (!isset($currantLang)) {
            $currantLang = 'en';
        }
        $termination = Termination::where('employee_id', $id)->first();
        $experience_certificate = ExperienceCertificate::where(['lang' => $currantLang, 'created_by' => \Auth::user()->creatorId()])->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);
        $date1 = date_create($employees->company_doj);
        $date2 = date_create($employees->termination_date);
        $diff = date_diff($date1, $date2);
        $duration = $diff->format("%a days");
        if (!empty($termination->termination_date)) {
            $obj = [
                'date' => \Auth::user()->dateFormat($date),
                'app_name' => env('APP_NAME'),
                'employee_name' => $employees->name,
                'payroll' => !empty($employees->salaryType->name) ? $employees->salaryType->name : '',
                'duration' => $duration,
                'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',

            ];
        } else {
            return redirect()->back()->with('error', __('Termination date is required.'));
        }

        $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
        return view('employee.template.ExpCertificatedocx', compact('experience_certificate', 'employees'));

    }
    public function NocPdf($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $noc_certificate = NOC::where(['lang' => $currantLang, 'created_by' => \Auth::user()->creatorId()])->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);


        $obj = [
            'date' => \Auth::user()->dateFormat($date),
            'employee_name' => !empty($employees) ? $employees->name : '',
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'app_name' => env('APP_NAME'),
        ];

        $noc_certificate->content = NOC::replaceVariable($noc_certificate->content, $obj);
        return view('employee.template.Nocpdf', compact('noc_certificate', 'employees'));

    }
    public function NocDoc($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $noc_certificate = NOC::where(['lang' => $currantLang, 'created_by' => \Auth::user()->creatorId()])->first();
        $date = date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time']) - strtotime("00:00");
        $result = date("H:i", strtotime($settings['company_end_time']) - $secs);


        $obj = [
            'date' => \Auth::user()->dateFormat($date),
            'employee_name' => $employees->name,
            'designation' => !empty($employees->designation->name) ? $employees->designation->name : '',
            'app_name' => env('APP_NAME'),
        ];

        $noc_certificate->content = NOC::replaceVariable($noc_certificate->content, $obj);
        return view('employee.template.Nocdocx', compact('noc_certificate', 'employees'));

    }

    //Export
    public function export()
    {
        $name = 'employee_' . date('Y-m-d i:h:s');
        $data = Excel::download(new EmployeeExport(), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }

    //import
    public function importFile()
    {
        return view('employee.import');
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

        $employees = (new EmployeesImport())->toArray(request()->file('file'))[0];
        $totalCustomer = count($employees) - 1;
        $errorArray = [];
        try {    // Start a transaction
            for ($i = 1; $i <= count($employees) - 1; $i++) {
            DB::beginTransaction();
            
            $employee = $employees[$i];

            if (count($employee) >= 8) {
                if ($employee[5] == null) {
                    return redirect()->back()->with('error', __('Email Filed is Required'));
                }
                $employeeByEmail = Employee::where('email', $employee[5])->first();
                $userByEmail = User::where('email', $employee[5])->first();
                // dd($userByEmail);

                if (!empty($employeeByEmail) && !empty($userByEmail)) {
                    $employeeData = $employeeByEmail;
                } else {
                    $user = new User();
                    $user->name = $employee[0];
                    $user->email = $employee[5];
                    $user->password = Hash::make($employee[6]);
                    $user->type = 'employee';
                    $user->lang = 'en';
                    $user->created_by = \Auth::user()->creatorId();
                    $user->save();
                    $user->assignRole('Employee');
                    $employeeData = new Employee();
                    $employeeData->employee_id = $this->employeeNumber();
                    $employeeData->user_id = $user->id;
                }

                $employeeData->name = $employee[0];
                $employeeData->dob = $employee[1];
                $employeeData->gender = $employee[2];
                $employeeData->phone = $employee[3];
                $employeeData->address = $employee[4];
                $employeeData->email = $employee[5];
                $employeeData->password = Hash::make($employee[6]);
                $employeeData->employee_id = $this->employeeNumber();
                // $employeeData->branch_id = $employee[8];
                // $employeeData->department_id = $employee[9];
                // $employeeData->designation_id = $employee[10];
                $employeeData->company_doj = $employee[7];
                // $employeeData->account_holder_name = $employee[12];
                // $employeeData->account_number = $employee[13];
                // $employeeData->bank_name = $employee[14];
                // $employeeData->bank_identifier_code = $employee[15];
                // $employeeData->branch_location = $employee[16];
                // $employeeData->tax_payer_id = $employee[17];
                $employeeData->created_by = \Auth::user()->creatorId();

                if (empty($employeeData)) {

                    $errorArray[] = $employeeData;
                } else {

                    $employeeData->save();
                }


                $errorRecord = [];

                if (empty($errorArray)) {
                    $data['status'] = 'success';
                    $data['msg'] = __('Record successfully imported');
                } else {
                    $data['status'] = 'error';
                    $data['msg'] = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalCustomer . ' ' . 'record');

                    foreach ($errorArray as $errorData) {

                        $errorRecord[] = implode(',', $errorData);
                    }

                    \Session::put('errorArray', $errorRecord);
                }
                DB::commit(); 
                return redirect()->back()->with($data['status'], $data['msg']);
            } else {
                DB::rollback();
                return redirect()->back()->with('error', __('Something went wrong'));
            }

        }    // Commit the transaction
        } catch (\Exception $e) {
            DB::rollback();
            dd($e);
            return redirect()->back()->with('error', $e->getMessage());
        }

    }




}
