<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    public function __construct()
    {

    }

    public function index(Request $request)
    {
        // dd($request->all());
        if (\Auth::user()->can('manage constant custom field')) {
            $custom_fields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->get();
            $modules = CustomField::$modules;
            if (!empty($request->module)) {
                $custom_fields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', $request->module)->get();
            }
            return view('customFields.index', compact('custom_fields', 'modules'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function create()
    {
        if (\Auth::user()->can('create constant custom field')) {
            $types = CustomField::$fieldTypes;
            $is_required = CustomField::$is_required;
            $modules = CustomField::$modules;

            return view('customFields.create', compact('types', 'modules', 'is_required'));
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }


    public function store(Request $request)
    {
        if (\Auth::user()->can('create constant custom field')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required|max:40',
                    'type' => 'required',
                    'module' => 'required',
                    'is_required' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('custom-field.index')->with('error', $messages->first());
            }

            $custom_field = new CustomField();
            $custom_field->name = $request->name;
            $custom_field->type = $request->type;
            $custom_field->module = $request->module;
            $custom_field->is_required = $request->is_required;
            $custom_field->values = $request->values;
            $custom_field->created_by = \Auth::user()->creatorId();
            $custom_field->save();

            return redirect()->route('custom-field.index')->with('success', __('Custom Field successfully created!'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function show(CustomField $customField)
    {
        return redirect()->route('custom-field.index');
    }

    public function edit(CustomField $customField)
    {
        if (\Auth::user()->can('edit constant custom field')) {
            if ($customField->created_by == \Auth::user()->creatorId()) {
                $types = CustomField::$fieldTypes;
                $is_required = CustomField::$is_required;
                $modules = CustomField::$modules;

                return view('customFields.edit', compact('customField', 'types', 'modules', 'is_required'));
            } else {
                return response()->json(['error' => __('Permission Denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission Denied.')], 401);
        }
    }


    public function update(Request $request, CustomField $customField)
    {
        if (\Auth::user()->can('edit constant custom field')) {

            if ($customField->created_by == \Auth::user()->creatorId()) {

                $validator = \Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|max:40',
                    ]
                );

                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('custom-field.index')->with('error', $messages->first());
                }

                $customField->name = $request->name;
                $customField->is_required = $request->is_required;
                $customField->save();

                return redirect()->route('custom-field.index')->with('success', __('Custom Field successfully updated!'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function destroy(CustomField $customField)
    {
        if (\Auth::user()->can('delete constant custom field')) {
            if ($customField->created_by == \Auth::user()->creatorId()) {
                $customField->delete();

                return redirect()->route('custom-field.index')->with('success', __('Custom Field successfully deleted!'));
            } else {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
    public function getFieldOptions(Request $request,$id)
    {
        $field = CustomField::find($id);

        if ($field) {
            $options = explode(',', $field->values);
            return response()->json(['success' => true, 'options' => $options]);
        }

        return response()->json(['success' => false, 'message' => 'Field not found.']);
    }

}
