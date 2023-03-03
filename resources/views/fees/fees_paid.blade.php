@extends('layouts.master')

@section('title')
{{ __('manage') . ' ' . __('fees') }} {{__('paid')}}
@endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            {{ __('manage') . ' ' . __('fees') }} {{__('paid')}}
        </h3>
    </div>
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div id="toolbar" class="row">
                        <div class="col">
                            <label for="filter_class_id" style="font-size: 0.89rem">
                                {{ __('classes') }}
                            </label>
                            <select name="filter_class_id" id="filter_class_id" class="form-control">
                                <option value="">{{__('all')}}</option>
                                @foreach ($classes as $class)
                                <option value="{{ $class->id }}">
                                    {{ $class->name }} {{$class->medium->name}}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <label for="filter_session_year_id" style="font-size: 0.89rem">
                                {{ __('session_years') }}
                            </label>
                            <select name="filter_session_year_id" id="filter_session_year_id" class="form-control">
                                <option value="">{{__('all')}}</option>
                                @foreach ($session_year_all as $session_year)
                                <option value="{{ $session_year->id}}">
                                    {{ $session_year->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col" style="font-size: 0.89rem">
                            <label for="filter_mode">
                                {{ __('mode') }}
                            </label>
                            <select name="filter_mode" id="filter_mode" class="form-control">
                                <option value="">{{__('all')}}</option>
                                <option value="0">{{__('cash')}}</option>
                                <option value="1">{{__('cheque')}}</option>
                                <option value="2">{{__('online')}}</option>
                            </select>
                        </div>
                    </div>
                    <table aria-describedby="mydesc" class='table table-striped' id='table_list' data-toggle="table" data-url="{{ route('fees.paid.list', 1) }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]' data-export-options='{ "fileName": "{{__('fees')}}-{{__('paid')}}-{{__('list')}}-<?= date(' d-m-y') ?>" ,"ignoreColumn":["operate"]}' data-show-export="true" data-query-params="feesPaidListQueryParams">
                        <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{__('id')}}</th>
                                <th scope="col" data-field="student_id" data-sortable="false" data-visible="false">{{__('student_id')}}</th>
                                <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                <th scope="col" data-field="student_name" data-sortable="false">{{ __('students').' '.__('name') }}</th>
                                <th scope="col" data-field="class_name" data-sortable="false">{{ __('class') }}</th>
                                <th scope="col" data-field="total_fees" data-sortable="false" data-align="center">{{ __('total') }} {{__('fees')}}</th>
                                <th scope="col" data-field="mode" data-sortable="false" data-align="center" data-formatter="feesPaidModeFormatter">{{ __('mode') }}</th>
                                <th scope="col" data-field="transaction_payment_id" data-sortable="false" data-align="center">{{ __('transaction_payment_id') }}</th>
                                <th scope="col" data-field="cheque_no" data-sortable="false" data-align="center">{{ __('cheque_no') }}</th>
                                <th scope="col" data-field="date" data-sortable="false" data-align="center">{{ __('date') }}</th>
                                <th scope="col" data-field="session_year_name" data-sortable="false" data-align="center">{{ __('session_years') }}</th>
                                <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">{{ __('created_at') }}</th>
                                <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">{{ __('updated_at') }}</th>
                                <th scope="col" data-field="operate" data-sortable="false" data-events="feesPaidEvents" data-align="center">{{ __('action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-m" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="exampleModalLabel">
                            {{ __('pay') . ' ' . __('fees') }}
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form class="pt-3 pay_student_fees_offline" method="post" action="{{ route('fees.paid.store') }}" novalidate="novalidate">
                        <input type="hidden" name="student_id" id="student_id" value="" />
                        <input type="hidden" name="class_id" id="class_id" value="" />
                        <input type="hidden" name="due_charges" id="due_charges" value="" />
                        <h4 class="ml-4">
                            <font class="student_name"></font>
                        </h4>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="date" class="datepicker-popup date form-control" placeholder="{{ __('date') }}" autocomplete="off" required>
                            </div>
                            <div class="choiceable_div" style="display: none">
                                <hr>
                                <label>{{ __('choiceable') }} {{__('fees')}}</label>
                                <div class="form-group col-sm-12 col-md-12">
                                    <div class="choiceable_fees_content">
                                    </div>
                                    <hr>
                                    <label>{{__('total')}} {{__('amount')}} :- </label><strong><label class="total_amount_label"></label></strong>
                                    <input type="hidden" name="total_amount" class="total_amount">
                                </div>
                                <hr>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-12">
                                    <label>{{ __('mode') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="mode" class="mode" value="0">
                                                {{ __('cash') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="mode" class="mode" value="1">
                                                {{ __('cheque') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group cheque_no_container" style="display: none">
                                <label>{{ __('cheque_no') }} <span class="text-danger">*</span></label>
                                <input type="number" id="cheque_no" name="cheque_no" placeholder="{{ __('cheque_no') }}" class="form-control" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                            <input class="btn btn-theme" type="submit" value={{ __('pay') }} />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="editFeesPaidModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-m" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="exampleModalLabel">
                            {{ __('edit') . ' ' . __('fees'). ' '. __('paid')}}
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form class="pt-3" id="edit-fees-paid-form" action="{{ url('fees/paid/update') }}" novalidate="novalidate">
                        <input type="hidden" name="edit_id" id="edit_id" value="" />
                        <input type="hidden" name="edit_student_id" id="edit_student_id" value="" />
                        <input type="hidden" name="edit_class_id" id="edit_class_id" value="" />
                        <h4 class="ml-4">
                            <font class="edit_student_name"></font>
                        </h4>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="edit_date" class="datepicker-popup edit_date form-control" placeholder="{{ __('date') }}" autocomplete="off" required>
                            </div>
                            <div class="edit_choiceable_div" style="display: none">
                                <hr>
                                <label>{{ __('choiceable') }} {{__('fees')}}</label>
                                <div class="form-group col-sm-12 col-md-12">
                                    <div class="edit_choiceable_fees_content">
                                    </div>
                                    <hr>
                                    <label>{{__('total')}} {{__('amount')}} :- </label><strong><label class="edit_total_amount_label" data-total_fees="0"></label></strong>
                                    <input type="hidden" name="edit_total_amount" class="edit_total_amount">
                                </div>
                                <hr>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-12">
                                    <label>{{ __('mode') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="edit_mode" id="edit_mode_cash" class="edit_mode" value="0">
                                                {{ __('cash') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="edit_mode" id="edit_mode_cheque" class="edit_mode" value="1">
                                                {{ __('cheque') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group edit_cheque_no_container" style="display: none">
                                <label>{{ __('cheque_no') }} <span class="text-danger">*</span></label>
                                <input type="number" id="edit_cheque_no" name="edit_cheque_no" placeholder="{{ __('cheque_no') }}" class="form-control" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                            <input class="btn btn-theme" type="submit" value='{{ __('update') }} {{__('pay')}}' />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
