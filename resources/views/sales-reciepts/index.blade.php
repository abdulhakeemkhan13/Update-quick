@extends('layouts.admin')
@section('page-title')
    {{__('Sales Receipts')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Sales Receipts')}}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="text-end mb-3">
                <a href="{{ route('sales.reciepts.create', 0) }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i> {{__('Create Sales Receipt')}}
                </a>
            </div>
        </div>
    </div>
@endsection
