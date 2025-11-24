<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\CustomField;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class BranchesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = \Auth::user();
        $users = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'branch')->with(['currentPlan'])->get();
        return view('branches.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();
        $user = \Auth::user();
        if (\Auth::user()->can('create user')) {
            return view('branches.create', compact('customFields'));
        } else {
            return redirect()->back();
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = new User();
        $user['name'] = $request->name;
        $user['email'] = $request->email;
        $psw = $request->password;
        $enableLogin = 0;
        if (!empty($request->password_switch) && $request->password_switch == 'on') {
            $enableLogin = 1;
            $validator = \Validator::make(
                $request->all(),
                ['password' => 'required|min:6']
            );

            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->errors()->first());
            }
        }
        $user['password'] = !empty($psw) ? \Hash::make($psw) : null;
        $user['type'] = 'branch';
        $user['default_pipeline'] = 1;
        $user['plan'] = 1;
        $user['lang'] = !empty($default_language) ? $default_language->value : 'en';
        $user['created_by'] = \Auth::user()->creatorId();
        $user['owned_by'] = \Auth::user()->creatorId();
        $user['email_verified_at'] = null;

        $user['is_enable_login'] = 1;

        $user->save();
        if($user){
            $new_branch = new Branch();
            $new_branch->name = $request->name;
            $new_branch->created_by = \Auth::user()->creatorId();
            $new_branch->owned_by = $user->id;
            $new_branch->save();
        }
        $role_r = Role::findByName('branch');
        $user->assignRole($role_r);
        Utility::makeActivityLog(\Auth::user()->id,'Branch',$user->id,'Create Branch',$request->name);
        return redirect()->route('branches.index')->with(
            'success',
            'Branch successfully Created.'
        );   
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = \Auth::user();
        if (\Auth::user()->can('edit user')) {
            $user = User::findOrFail($id);
            $user->customField = CustomField::getData($user, 'user');
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();
            return view('branches.edit', compact('user', 'customFields'));
        } else {
            return redirect()->back();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (\Auth::user()->can('edit user')) {
            if (\Auth::user()->type == 'company') {
                $user = User::findOrFail($id);
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users,email,' . $id,
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }
                $input = $request->all();
                $user->fill($input)->save();
                CustomField::saveData($user, $request->customField);

                Utility::makeActivityLog(\Auth::user()->id,'Branch',$user->id,'Update Branch',$user->name);

                return redirect()->route('branches.index')->with(
                    'success',
                    'Branch successfully updated.'
                );
            }
        } else {
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
