<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Utility;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage document type'))
        {
            $user = \Auth::user();
            
            if ($user->type == 'company') {
                $documents = Document::where('created_by', '=', \Auth::user()->creatorId())->get();
            } else {
                $documents = Document::where(function ($query) use ($user) {
                    $query->where('created_by', $user->creatorId())  
                        ->orWhere('owned_by', $user->ownedId()); 
                })->get();
            }
            return view('document.index', compact('documents'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create document type'))
        {
            return view('document.create');
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create document type'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required|max:20',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $document              = new Document();
            $document->name        = $request->name;
            $document->is_required = $request->is_required;
            $document->created_by  = \Auth::user()->creatorId();
            $document->owned_by = \Auth::user()->ownedId();
            $document->save();
            Utility::makeActivityLog(\Auth::user()->id,'Document',$document->id,'Create Document',$document->name);
            return redirect()->route('document.index')->with('success', __('Document type successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Document $document)
    {
        return redirect()->route('document.index');
    }

    public function edit(Document $document)
    {
        if(\Auth::user()->can('edit document type'))
        {
            if($document->created_by == \Auth::user()->creatorId())
            {

                return view('document.edit', compact('document'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Document $document)
    {

        if(\Auth::user()->can('edit document type'))
        {
            if($document->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'name' => 'required|max:20',
                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }


                $document->name        = $request->name;
                $document->is_required = $request->is_required;
                $document->save();
                Utility::makeActivityLog(\Auth::user()->id,'Document',$document->id,'Update Document',$document->name);
                return redirect()->route('document.index')->with('success', __('Document type successfully updated.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Document $document)
    {
        if(\Auth::user()->can('delete document type'))
        {
            if($document->created_by == \Auth::user()->creatorId())
            {
                Utility::makeActivityLog(\Auth::user()->id,'Document',$document->id,'Delete Document',$document->name);
                $document->delete();

                return redirect()->route('document.index')->with('success', __('Document type successfully deleted.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
