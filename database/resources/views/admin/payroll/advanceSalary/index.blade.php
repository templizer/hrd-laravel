@extends('layouts.master')
@section('title',__('index.advance_salary_requests'))
@section('action',__('index.lists'))

@section('main-content')
    <section class="content">
        @include('admin.section.flash_message')

        <div id="showFlashMessageResponse">
            <div class="alert alert-danger error d-none">
                <p class="errorMessageDelete"></p>
            </div>
        </div>

        @include('admin.payroll.advanceSalary.common.breadcrumb')

        <div class="search-box p-4 bg-white rounded mb-3 box-shadow pb-2">
            <form class="forms-sample" action="{{route('admin.advance-salaries.index')}}" method="get">
                <div class="col-lg-12 mb-3">
                    <h5>{{ __('index.advance_salary_request_filter') }}</h5>
                </div>
                <div class="row align-items-center">


                    <div class="col-lg-3 col-md-4 mb-3">
                        <input type="text" name="employee" id="employee" placeholder="{{ __('index.search_by_employee_name') }}" value="{{$filterParameters['employee']}}" class="form-control" />
                    </div>

                    <div class="col-lg-3 col-md-4 mb-3">
                        <select class="form-select" id="status" name="status" >
                            <option value="">{{ __('index.search_by_status') }}</option>
                            @foreach(\App\Models\AdvanceSalary::STATUS as $value)
                                <option value="{{$value}}" {{ isset($filterParameters['status']) && $filterParameters['status'] == $value ? 'selected':''}}> {{ucfirst($value)}}</option>
                            @endforeach
                        </select>
                    </div>

                     <div class="col-lg-3 col-md-4 mb-3">
                        <select class="form-select" id="month" name="month" >
                            <option value="">{{ __('index.search_by_month') }}</option>
                            @foreach ($months as $key => $value)
                                <option {{ isset($filterParameters['month']) && $filterParameters['month'] == $key ? 'selected' : '' }} value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-4 mb-3">
                        <div class="d-flex float-md-end">
                            <button type="submit" class="btn btn-block btn-secondary me-2">{{ __('index.filter') }}</button>
                            <a class="btn btn-block btn-danger" href="{{route('admin.advance-salaries.index')}}">{{ __('index.reset') }}</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="dataTableExample" class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('index.employee') }}</th>
                                <th class="text-center">{{ __('index.requested_amount') }}({{\App\Helpers\AppHelper::getCompanyPaymentCurrencySymbol()}}.)</th>
                                <th class="text-center">{{ __('index.requested_on') }}</th>
                                <th class="text-center">{{ __('index.released_amount') }}({{\App\Helpers\AppHelper::getCompanyPaymentCurrencySymbol()}}.)</th>
                                <th class="text-center">{{ __('index.released_on') }}</th>
                                <th class="text-center">{{ __('index.is_paid') }}</th>
                                <th class="text-center">{{ __('index.status') }}</th>
                                <th class="text-center">{{ __('index.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <?php
                                $status = [
                                    'pending' => 'primary',
                                    'approved' => 'success',
                                    'processing' => 'secondary',
                                    'rejected' => 'danger',
                                ];
                            ?>
                        @forelse($advanceSalaryRequestLists as $key => $value)
                            <tr>
                                <td>{{(($advanceSalaryRequestLists->currentPage()- 1 ) * $advanceSalaryRequestLists->perPage() + (++$key))}} </td>
                                <td>{{($value->requestedBy->name)}}</td>
                                <td class="text-center">{{number_format($value->requested_amount)}}</td>

                                <td class="text-center">{{ isset($value->advance_requested_date) ? \App\Helpers\AppHelper::formatDateForView($value->advance_requested_date) : 'N/A'}}</td>
                                 <td class="text-center">{{number_format($value->released_amount)}}</td>
                                <td class="text-center">{{ isset($value->amount_granted_date) ? \App\Helpers\AppHelper::formatDateForView($value->amount_granted_date) : 'N/A'}}</td>

                                <td class="text-center">
                                  <span class="btn btn-{{$value->is_settled ? 'success' : 'warning'}} btn-xs cursor-default">{{$value->is_settled == 1 ? 'Yes' : 'No'}}</span>
                                </td>
                                <td class="text-center">
                                    <span class="btn btn-{{$status[$value->status]}} btn-xs">
                                        {{ucfirst($value->status)}}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <ul class="d-flex list-unstyled mb-0 justify-content-center">
                                        <li class="me-2">
                                            <a href="{{route('admin.advance-salaries.show',$value->id)}}"
                                               id="edit"
                                               title="{{ __('index.update') }}"
                                               data-id="{{ $value->id }}">
                                                <i class="link-icon" data-feather="eye"></i>
                                            </a>
                                        </li>

                                        <li>
                                            <a class="delete"
                                               data-href="{{route('admin.advance-salaries.delete',$value->id)}}"
                                               data-title="Delete Detail"
                                               title="{{ __('index.delete') }}">
                                                <i class="link-icon"  data-feather="delete"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%">
                                    <p class="text-center"><b>{{ __('index.no_records_found') }}</b></p>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="dataTables_paginate mt-3">
            {{$advanceSalaryRequestLists->appends($_GET)->links()}}
        </div>
    </section>
@endsection

@section('scripts')
    @include('admin.payroll.advanceSalary.common.scripts')
@endsection






