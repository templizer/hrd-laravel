
<div class="row">
    <div class="col-lg-3 col-md-6 mb-4">
        <label for="termination_type_id" class="form-label">{{ __('index.termination_type') }} <span style="color: red">*</span></label>
        <select class="form-select" id="termination_type_id" name="termination_type_id" required>
            <option value="" {{isset($terminationDetail) ? '': 'selected'}}  disabled>{{ __('index.select_termination_type') }}</option>
            @foreach($terminationTypes as $key =>  $value)
                <option value="{{$value->id}}" {{ isset($terminationDetail) && ($terminationDetail->termination_type_id ) == $value->id || old('termination_type_id') == $value->id ? 'selected': '' }}>
                    {{ucfirst($value->title)}}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <label for="employee_id" class="form-label">{{ __('index.employee') }} <span style="color: red">*</span></label>
        <select class="form-select" id="employee_id" name="employee_id">
            <option  selected disabled>{{ __('index.select_employee') }}</option>
            @foreach($employees as $key =>  $value)
                <option value="{{$value->id}}" {{ isset($terminationDetail) && ($terminationDetail->employee_id ) == $value->id || old('employee_id') == $value->id ? 'selected': '' }}>
                    {{ucfirst($value->name)}}
                </option>
            @endforeach
        </select>
    </div>


    <div class="col-lg-3 col-md-6 event-date-time mb-3">
        <label for="notice_date" class="form-label">@lang('index.notice_date') <span style="color: red">*</span> </label>



        @if($isBsEnabled)
            <input type="text" id="notice_date" name="notice_date" value="{{ ( isset( $terminationDetail) ?  $terminationDetail->notice_date: old('notice_date') )}}"
                   placeholder="yyyy-mm-dd" class="form-control nepaliDate"/>
        @else
            <input type="date" class="form-control" name="notice_date" required value="{{ ( isset( $terminationDetail) ?  $terminationDetail->notice_date: old('notice_date') )}}"
                   autocomplete="off" >

        @endif

    </div>
    <div class="col-lg-3 col-md-6 event-date-time mb-3">
        <label for="termination_date" class="form-label">@lang('index.termination_date') </label>
        @if($isBsEnabled)
            <input type="text" id="termination_date" name="termination_date" value="{{ ( isset( $terminationDetail) ?  $terminationDetail->termination_date: old('termination_date') )}}"
                   placeholder="yyyy-mm-dd" class="form-control nepaliDate"/>
        @else
            <input type="date" class="form-control" name="termination_date" value="{{ ( isset( $terminationDetail) ?  $terminationDetail->termination_date: old('termination_date') )}}"
                   autocomplete="off" >
        @endif


    </div>

    <div class="col-lg-6 mb-4">
        <label for="tinymceExample" class="form-label">{{ __('index.reason') }} <span style="color: red">*</span></label>
        <textarea class="form-control" name="reason" id="tinymceExample" rows="1">{{ ( isset($terminationDetail) ? $terminationDetail->reason: old('reason') )}}</textarea>
    </div>
    <div class="col-lg-6 mb-4">
        <label for="tinymceExample" class="form-label">{{ __('index.document') }}</label>
        <input class="form-control"
               type="file"
               id="document"
               name="document"
               value="{{ isset($terminationDetail) ? $terminationDetail->document : old('document') }}">

        @if(isset($terminationDetail->document))
            @php
                $fileExtension = pathinfo($terminationDetail->document, PATHINFO_EXTENSION);
            @endphp
            @if(in_array($fileExtension, ['jpeg', 'jpg', 'png', 'webp']))
                <img class="wd-200 ht-100" style="object-fit: cover;"
                     src="{{ asset(\App\Models\Termination::UPLOAD_PATH . $terminationDetail->document) }}"
                     alt="Certificate" data-bs-toggle="modal" data-bs-target="#certificateModal-{{ $terminationDetail->id }}">

                <div class="modal fade" id="certificateModal-{{ $terminationDetail->id }}" tabindex="-1" aria-labelledby="imageModalLabel-{{ $terminationDetail->index }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageModalLabel-{{ $terminationDetail->id }}">View Image <button type="button" class="btn-close float-end" data-bs-dismiss="modal" aria-label="Close"></button></h5>

                            </div>
                            <div class="modal-body text-center">
                                <img class="img-fluid" src="{{ asset(\App\Models\Termination::UPLOAD_PATH . $terminationDetail->document) }}" alt="IBAN">
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($fileExtension === 'pdf')
                <embed src="{{ asset(\App\Models\Termination::UPLOAD_PATH . $terminationDetail->document) }}"
                       type="application/pdf" width="150" height="100" />
                <a href="{{ asset(\App\Models\Termination::UPLOAD_PATH . $terminationDetail->document) }}"
                   target="_blank" class="mt-2">Preview PDF</a>
            @else
                <a href="{{ asset(\App\Models\Termination::UPLOAD_PATH . $terminationDetail->document) }}"
                   download class="mt-2">Download Document</a>
            @endif
        @endif
    </div>

    <div class="col-lg-4 col-md-6 mb-4">
        <label for="status" class="form-label">{{ __('index.status') }} </label>
        <select class="form-select" id="status" name="status" required>
            <option value="" {{isset($terminationDetail) ? '': 'selected'}}  disabled>{{ __('index.select_status') }}</option>
            @foreach($terminationStatus as $status)
                <option value="{{$status->value}}" {{ isset($terminationDetail) && ($terminationDetail->status ) == $status->value || old('status') == $status->value ? 'selected': '' }}>
                    {{ucfirst($status->name)}}
                </option>
            @endforeach
        </select>
    </div>

    @canany(['edit_termination','create_termination'])
        <div class="text-start">
            <button type="submit" class="btn btn-primary">
                <i class="link-icon" data-feather="plus"></i>
                {{isset($terminationDetail)?  __('index.update'): __('index.create')}}
            </button>
        </div>
    @endcanany
</div>



