@extends('layouts.admin')
@section('page-title')
    {{__('Vendor Credits')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Vendor Credits')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <button class="btn btn-sm btn-primary openVendorCreditModal" data-url="{{ route('vendor-credit.create') }}" data-bs-toggle="tooltip" title="{{__('Create Vendor Credit')}}">
            {{__('Create Vendor Credit')}}
            <i class="ti ti-plus"></i>
        </button>
    </div>
@endsection

@section('content')
    <div class="modal fade" id="ajaxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen">
            <div class="modal-content">
            </div>
        </div>
    </div>

@push('script-page')
    <script>
        $(document).on('click', '.openVendorCreditModal', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            $('#ajaxModal').modal('show');
            $.ajax({
                url: url,
                type: 'GET',
                success: function (res) {
                    $('#ajaxModal .modal-content').html(res);
                },
                error: function () {
                    alert('Something went wrong!');
                }
            });
        });
    </script>
@endpush

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th class="text-center">{{__('Ref No.')}}</th>
                                <th class="text-center">{{__('Vendor')}}</th>
                                <th class="text-center">{{__('Date')}}</th>
                                <th class="text-center">{{__('Amount')}}</th>
                                <th class="text-center">{{__('Memo')}}</th>
                                <th width="10%">{{__('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($vendorCredits as $credit)
                                <tr>
                                    <td class="text-center">{{ $credit->vendor_credit_id }}</td>
                                    <td class="text-center">{{ optional($credit->vendor)->name ?? '-' }}</td>
                                    <td class="text-center">{{ \Auth::user()->dateFormat($credit->date) }}</td>
                                    <td class="text-center">{{ \Auth::user()->priceFormat($credit->amount) }}</td>
                                    <td class="text-center">{{ Str::limit($credit->memo, 30) }}</td>
                                    <td class="Action text-center">
                                        <span>
                                            {{-- Edit --}}
                                            <div class="action-btn bg-primary ms-2">
                                                <a href="#" data-url="{{ route('vendor-credit.edit', $credit->id) }}" 
                                                   class="mx-3 btn btn-sm align-items-center openVendorCreditModal"
                                                   data-bs-toggle="tooltip" title="{{__('Edit')}}">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                            </div>
                                            {{-- Delete --}}
                                            <div class="action-btn bg-danger ms-2">
                                                {!! Form::open(['method' => 'DELETE', 'route' => ['vendor-credit.destroy', $credit->id], 'class' => 'delete-form-btn', 'id' => 'delete-form-'.$credit->id]) !!}
                                                <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" 
                                                   data-bs-toggle="tooltip" title="{{__('Delete')}}" 
                                                   data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" 
                                                   data-confirm-yes="document.getElementById('delete-form-{{$credit->id}}').submit();">
                                                    <i class="ti ti-trash text-white"></i>
                                                </a>
                                                {!! Form::close() !!}
                                            </div>
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
