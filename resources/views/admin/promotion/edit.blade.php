@extends('layouts.master')
@section('title',__('index.promotion'))
@section('action',__('index.edit'))
@section('main-content')

    <section class="content">
        @include('admin.section.flash_message')
        @include('admin.promotion.common.breadcrumb')
        <div class="card">
            <div class="card-body">
                <form id="" class="forms-sample" action="{{route('admin.promotion.update',$promotionDetail->id)}}" enctype="multipart/form-data"  method="post">
                    @method('PUT')
                    @csrf
                    @include('admin.promotion.common.form')
                </form>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    @include('admin.promotion.common.from_script')

@endsection

