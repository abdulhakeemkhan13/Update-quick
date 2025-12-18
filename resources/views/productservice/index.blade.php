 @extends('layouts.admin')
 @section('page-title')
     {{ __('Manage Product & Services') }}
 @endsection
 @push('script-page')
 @endpush
 @section('breadcrumb')
     <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
     <li class="breadcrumb-item">{{ __('Product & Services') }}</li>
 @endsection
 @section('action-btn')
     <div class="float-end">
         {{-- <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
             data-url="{{ route('productservice.file.import') }}" data-ajax-popup="true"
             data-title="{{ __('Import product CSV file') }}" class="btn btn-sm btn-primary">
             <i class="ti ti-file-import"></i>
         </a>
         <a href="{{ route('productservice.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
             class="btn btn-sm btn-primary">
             <i class="ti ti-file-export"></i>
         </a> --}}

         <a href="#" data-size="lg" data-url="{{ route('productservice.create') }}" data-ajax-popup="true"
             data-bs-toggle="tooltip" data-title="{{ __('Create New Product & Service') }}" class="btn btn-sm btn-primary">
             {{ __('Create Product & Service') }}
             <i class="ti ti-plus"></i>
         </a>

     </div>
 @endsection

 @section('content')
     {{-- Include Sales Tabs --}}
     @include('transaction.sales-tabs')
<div class="d-flex justify-content-between align-items-center mt-3 mb-3">

    {{-- Filters Dropdown --}}
    <div class="dropdown">
        <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button"
            id="filtersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-filter me-1"></i> {{ __('Filters') }}
        </button>

        <div class="dropdown-menu p-3" style="min-width: 350px;">
            <div class="card shadow-none border-0">
                <div class="card-body p-0">
                    {{ Form::open(['route' => ['productservice.index'], 'method' => 'GET', 'id' => 'product_service']) }}
                    <div class="row">

                        {{-- Category --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('category', __('Category'), ['class' => 'form-label']) }}
                            {{ Form::select('category', $category, request('category'), ['class' => 'form-control select', 'id' => 'choices-multiple']) }}
                        </div>

                        {{-- Type --}}
                        <div class="col-12 mb-3">
                            {{ Form::label('type', __('Type'), ['class' => 'form-label']) }}
                            {{ Form::select('type', ['' => __('Select Type')] + $types, request('type'), ['class' => 'form-control select']) }}
                        </div>

                        {{-- Buttons --}}
                        <div class="col-12 d-flex justify-content-between">
                            <a href="{{ route('productservice.index') }}"
                                class="btn btn-outline-secondary btn-sm"
                                data-bs-toggle="tooltip" title="{{ __('Reset') }}">
                                <i class="ti ti-trash-off"></i> {{ __('Reset') }}
                            </a>

                            <button type="submit" class="btn btn-success btn-sm"
                                data-bs-toggle="tooltip" title="{{ __('Apply') }}">
                                <i class="ti ti-search"></i> {{ __('Apply') }}
                            </button>
                        </div>

                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Import / Export Buttons --}}
    <div class="d-flex gap-2">
        <a href="#" data-size="md" data-bs-toggle="tooltip" title="{{ __('Import') }}"
            data-url="{{ route('productservice.file.import') }}" data-ajax-popup="true"
            data-title="{{ __('Import product CSV file') }}" class="btn btn-sm btn-primary d-flex align-items-center">
            <i class="ti ti-file-import"></i>
        </a>
        <a href="{{ route('productservice.export') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            class="btn btn-sm btn-primary d-flex align-items-center">
            <i class="ti ti-file-export"></i>
        </a>
    </div>

</div>

     <div class="row">
         <div class="col-xl-12">
             <div class="card">
                 <div class="card-body table-border-style">
                     <div class="table-responsive">
                         <table class="table datatable">
                             <thead>
                                 <tr>
                                     <th>{{ __('Name') }}</th>
                                     <th>{{ __('Sku') }}</th>
                                     <th>{{ __('Sale Price') }}</th>
                                     <th>{{ __('Purchase Price') }}</th>
                                     <th>{{ __('Tax') }}</th>
                                     <th>{{ __('Category') }}</th>
                                     <th>{{ __('Unit') }}</th>
                                     <th>{{ __('Quantity') }}</th>
                                     <th>{{ __('Balance') }}</th>
                                     <th>{{ __('Type') }}</th>
                                     <th>{{ __('Action') }}</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 @foreach ($productServices as $productService)
                                     <tr class="font-style">
                                         <td>{{ $productService->name }}</td>
                                         <td>{{ $productService->sku }}</td>
                                         <td>{{ \Auth::user()->priceFormat($productService->sale_price) }}</td>
                                         <td>{{ \Auth::user()->priceFormat($productService->purchase_price) }}</td>
                                         <td>
                                             @if (!empty($productService->tax_id))
                                                 @php
                                                     $itemTaxes = [];
                                                     $getTaxData = Utility::getTaxData();

                                                     foreach (explode(',', $productService->tax_id) as $tax) {
                                                         $itemTax['name'] = $getTaxData[$tax]['name'];
                                                         $itemTax['rate'] = $getTaxData[$tax]['rate'] . '%';

                                                         $itemTaxes[] = $itemTax;
                                                     }
                                                     $productService->itemTax = $itemTaxes;
                                                 @endphp
                                                 @foreach ($productService->itemTax as $tax)
                                                     <span>{{ $tax['name'] . ' (' . $tax['rate'] . ')' }}</span><br>
                                                 @endforeach
                                             @else
                                                 -
                                             @endif
                                         </td>
                                         <td>{{ !empty($productService->category) ? $productService->category->name : '' }}
                                         </td>
                                         <td>{{ !empty($productService->unit) ? $productService->unit->name : '' }}</td>
                                         @if ($productService->type == 'product')
                                             <td>{{ $productService->quantity }}</td>
                                         @else
                                             <td>-</td>
                                         @endif
                                         <td>{{ $productService->qb_balance }}</td>
                                         <td>{{ ucwords($productService->type) }}</td>

                                         @if (Gate::check('edit product & service') || Gate::check('delete product & service'))
                                             <td class="Action">
                                                 <div class="action-btn bg-warning ms-2">
                                                     <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                         data-url="{{ route('productservice.detail', $productService->id) }}"
                                                         data-ajax-popup="true" data-bs-toggle="tooltip"
                                                         title="{{ __('Warehouse Details') }}"
                                                         data-title="{{ __('Warehouse Details') }}">
                                                         <i class="ti ti-eye text-white"></i>
                                                     </a>
                                                 </div>

                                                 @can('edit product & service')
                                                     <div class="action-btn bg-info ms-2">
                                                         <a href="#" class="mx-3 btn btn-sm  align-items-center"
                                                             data-url="{{ route('productservice.edit', $productService->id) }}"
                                                             data-ajax-popup="true" data-size="lg " data-bs-toggle="tooltip"
                                                             title="{{ __('Edit') }}"
                                                             data-title="{{ __('Edit Product') }}">
                                                             <i class="ti ti-pencil text-white"></i>
                                                         </a>
                                                     </div>
                                                 @endcan
                                                 @can('delete product & service')
                                                     <div class="action-btn bg-danger ms-2">
                                                         {!! Form::open([
                                                             'method' => 'DELETE',
                                                             'route' => ['productservice.destroy', $productService->id],
                                                             'id' => 'delete-form-' . $productService->id,
                                                         ]) !!}
                                                         <a href="#"
                                                             class="mx-3 btn btn-sm  align-items-center bs-pass-para"
                                                             data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                                 class="ti ti-trash text-white"></i></a>
                                                         {!! Form::close() !!}
                                                     </div>
                                                 @endcan
                                             </td>
                                         @endif
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
