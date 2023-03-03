@extends('layouts.master')

@section('title')
{{ __('manage') . ' ' . __('online'). ' '.__('exam') }}
@endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            {{ __('manage') . ' ' . __('online'). ' '.__('exam') }}
        </h3>
    </div>
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        {{ __('create') . ' ' . __('online'). ' '.__('exam') }}
                    </h4>
                    <form class="pt-3 mt-6" id="create-form" method="POST" action="{{ route('online-exam.store') }}">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('class') }} <span class="text-danger">*</span></label>
                                <select required name="class_id" class="form-control select2 online-exam-class-id" style="width:100%;" tabindex="-1" aria-hidden="true">
                                    <option value="">--- {{ __('select') . ' ' . __('class') }} ---</option>
                                    @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">
                                        {{ $class->name }} {{ $class->medium->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('subject') }} <span class="text-danger">*</span></label>
                                <select required name="subject_id" class="form-control select2 online-exam-subject-id" style="width:100%;" tabindex="-1" aria-hidden="true">
                                    <option value="">--- {{ __('select') . ' ' . __('subject') }} ---</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                <input type="text" id="online-exam-title" name="title" placeholder="{{ __('title') }}" class="form-control" required />
                            </div>
                            <div class="form-group col-md-2">
                                <label>{{ __('exam') }} {{__('key')}} <span class="text-danger">*</span></label>
                                <input type="number" id="online-exam-key" name="exam_key" placeholder="{{ __('exam_key') }}" class="form-control" required />
                            </div>
                            <div class="form-group col-md-2">
                                <label>{{ __('duration') }} <span class="text-danger">*</span></label><span class="text-info small">( {{__('in_minutes')}} )</span>
                                <input type="number" id="online-exam-duration" name="duration" placeholder="{{ __('duration') }}" class="form-control" required />
                            </div>
                            <div class="form-group col-md-2">
                                <label>{{ __('start_date')}} <span class="text-danger">*</span></label>
                                {{-- <input type="text" id="online-exam-date" name="date" class="datepicker-popup date form-control" placeholder="{{ __('date') }}" autocomplete="off" required> --}}
                                <input type="datetime-local" id="online-exam-start-date" name="start_date" placeholder="{{__('start_date')}}" class='form-control' required>
                            </div>
                            <div class="form-group col-md-2">
                                <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                {{-- <input type="text" id="online-exam-date" name="date" class="datepicker-popup date form-control" placeholder="{{ __('date') }}" autocomplete="off" required> --}}
                                <input type="datetime-local" id="online-exam-end-date" name="end_date" placeholder="{{ __('end_date')}}" class='form-control' required>
                            </div>
                        </div>
                        <input class="btn btn-theme" id="add-online-exam-btn" type="submit" value={{ __('submit') }}>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">
                        {{ __('list') . ' ' . __('exams') }}
                    </h4>
                    <div id="toolbar" class="row mt-4">
                        <div class="form-group ml-4">
                            <label>{{ __('class') }}</label>
                            <select name="class_id" id="filter-online-exam-class-id" class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                <option value="">{{ __('all') }}</option>
                                @foreach ($classes as $class)
                                <option value="{{ $class->id }}">
                                    {{ $class->name }} {{ $class->medium->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group ml-4">
                            <label>{{ __('subject') }}</label>
                            <select name="subject_id" id="filter-online-exam-subject-id" class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                <option value="">{{ __('all') }}</option>
                                @foreach ($all_subjects as $subject)
                                <option value="{{ $subject->id }}">
                                    {{ $subject->name }} - {{ $subject->type }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <table aria-describedby="mydesc" class='table table-striped' id='table_list' data-toggle="table" data-url="{{ route('online-exam.show', 1) }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]' data-export-options='{ "fileName": "{{__('online').' '.__('exam')}}-<?= date(' d-m-y') ?>" ,"ignoreColumn":["operate"]}' data-show-export="true" data-query-params="onlineExamQueryParams">
                        <thead>
                            <tr>
                                <th scope="col" data-field="online_exam_id" data-sortable="true" data-visible="false">{{ __('id') }}
                                </th>
                                <th scope="col" data-field="no" data-sortable="false">{{ __('no') }}</th>
                                <th scope="col" data-field="class_name" data-sortable="false">{{ __('class') }}</th>
                                <th scope="col" data-field="subject_name" data-sortable="false">{{ __('subject') }}</th>
                                <th scope="col" data-field="title" data-sortable="false">{{ __('title') }}</th>
                                <th scope="col" data-field="exam_key" data-sortable="false" data-align="center">{{ __('exam_key')}}</th>
                                <th scope="col" data-field="duration" data-sortable="false" data-align="center">{{ __('duration')}} <span class="text-info small">( {{__('in_minutes')}} )</span></th>
                                <th scope="col" data-field="start_date" data-sortable="true">{{ __('start_date') }}</th>
                                <th scope="col" data-field="end_date" data-sortable="true">{{ __('end_date') }}</th>
                                <th scope="col" data-field="total_questions" data-sortable="false" data-align="center">{{ __('total').' '.__('questions') }}</th>
                                <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">{{ __('created_at') }}</th>
                                <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">{{ __('updated_at') }}</th>
                                <th scope="col" data-field="operate" data-sortable="false" data-events="onlineExamEvents">{{ __('action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- model --}}
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{__('edit')}} {{__('online')}} {{__('exam')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fa fa-close"></i></span>
                </button>
            </div>
            <form id="edit-form" class="pt-3 edit-form" action="{{ url('online-exam') }}">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __('class') }} <span class="text-danger">*</span></label>
                        <select required name="edit_class_id" id="edit-online-exam-class-id" class="form-control select2 online-exam-class-id" style="width:100%;" tabindex="-1" aria-hidden="true">
                            <option value="">--- {{ __('select') . ' ' . __('class') }} ---</option>
                            @foreach ($classes as $class)
                            <option value="{{ $class->id }}">
                                {{ $class->name }} {{ $class->medium->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('subject') }} <span class="text-danger">*</span></label>
                        <select required name="edit_subject_id" id="edit-online-exam-subject-id" class="form-control select2 online-exam-subject-id" style="width:100%;" tabindex="-1" aria-hidden="true">
                            <option value="">--- {{ __('select') . ' ' . __('subject') }} ---</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                        <input type="text" id="edit-online-exam-title" name="edit_title" placeholder="{{ __('title') }}" class="form-control" required />
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>{{ __('exam') }} {{__('key')}} <span class="text-danger">*</span></label>
                            <input type="number" id="edit-online-exam-key" name="edit_exam_key" placeholder="{{ __('exam_key') }}" class="form-control" required />
                        </div>
                        <div class="form-group col-md-6">
                            <label>{{ __('duration') }} <span class="text-danger">*</span></label><span class="text-info small">( {{__('in_minutes')}} )</span>
                            <input type="number" id="edit-online-exam-duration" name="edit_duration" placeholder="{{ __('duration') }}" class="form-control" required />
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                                <label>{{ __('start_date')}} <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="edit-online-exam-start-date" name="edit_start_date" placeholder="{{__('start_date')}}" class='form-control' required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('end_date') }} <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="edit-online-exam-end-date" name="edit_end_date" placeholder="{{ __('end_date')}}" class='form-control' required>
                            </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                    <input class="btn btn-theme" type="submit" value={{ __('submit') }} />
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
