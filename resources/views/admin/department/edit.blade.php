@extends('layouts.master')

@section('title', __('index.edit_department'))

@section('button')
    <a href="{{ route('admin.departments.index') }}">
        <button class="btn btn-sm btn-primary"><i class="link-icon" data-feather="arrow-left"></i> {{ __('index.button_back') }}</button>
    </a>
@endsection

@section('main-content')

    <section class="content">

        @include('admin.section.flash_message')

        @include('admin.department.common.breadcrumb',['title'=>__('index.edit')])
        <div class="card">
            <div class="card-body pb-0">
                <form class="forms-sample" action="{{ route('admin.departments.update', $departmentsDetail->id) }}" enctype="multipart/form-data" method="post">
                    @method('PUT')
                    @csrf
                    @include('admin.department.common.form')
                </form>
            </div>
        </div>

    </section>
@endsection
