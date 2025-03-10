
@extends('layouts.master')

@section('title',__('index.office_time'))

@section('action',__('index.create'))

@section('button')
    <a href="{{route('admin.office-times.index')}}" >
        <button class="btn btn-sm btn-primary" ><i class="link-icon" data-feather="arrow-left"></i> {{ __('index.back') }}</button>
    </a>
@endsection

@section('main-content')

    <section class="content">

        @include('admin.section.flash_message')

        @include('admin.officeTime.common.breadcrumb')
        <div class="card">
            <div class="card-body">
                <h4 class="mb-4">{{ __('index.office_schedule') }}</h4>
                <form class="forms-sample" action="{{route('admin.office-times.store')}}" enctype="multipart/form-data" method="POST">
                    @csrf
                    @include('admin.officeTime.common.form')
                </form>
            </div>
        </div>

    </section>
@endsection

@section('scripts')

    @include('admin.officeTime.common.scripts')
@endsection
