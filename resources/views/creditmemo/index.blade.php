@extends('layouts.admin')
@section('page-title')
    {{__('Credit Memo')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Credit Memo')}}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="text-end mb-3">
                <a href="{{ route('creditmemo.create', 0) }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i> {{__('Create Credit Memo')}}
                </a>
            </div>
        </div>
    </div>
@endsection
