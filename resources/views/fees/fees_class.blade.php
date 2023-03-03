@extends('layouts.master')

@section('title')
{{__('fees')}} {{__('classes')}}
@endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            {{ __('manage') }} {{__('fees')}} {{__('classes')}}
        </h3>
    </div>
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div id="toolbar">
                        <select name="filter_medium_id" id="filter_medium_id" class="form-control">
                            <option value="">{{ __('all') }}</option>
                            @foreach ($mediums as $medium)
                            <option value="{{ $medium->id }}">
                                {{ $medium->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <table aria-describedby="mydesc" class='table table-striped' id='table_list' data-toggle="table" data-url="{{ route('fees.class.list') }}" data-click-to-select="true" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200, All]" data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-fixed-columns="true" data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]' data-query-params="AssignclassQueryParams" data-export-options='{ "fileName": "class-list-<?= date(' d-m-y') ?>" ,"ignoreColumn":
                        ["operate"]}' data-show-export="true">
                        <thead>
                            <tr>
                                <th scope="col" data-field="class_id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no" data-sortable="false">{{ __('no') }}</th>
                                <th scope="col" data-field="class_name" data-sortable="false">{{ __('class') }}</th>
                                <th scope="col" data-field="fees_type" data-sortable="false" data-align="left" data-formatter="feesTypeFormatter">{{ __('fees') }} {{__('type')}}</th>
                                <th scope="col" data-field="base_amount" data-sortable="false" data-align="center">{{ __('base') }} {{__('amount')}}</th>
                                <th scope="col" data-field="total_amount" data-sortable="false" data-align="center">{{ __('total') }} {{__('amount')}}</th>
                                <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">{{ __('created_at') }}</th>
                                <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">{{ __('updated_at') }}</th>
                                <th scope="col" data-field="operate" data-sortable="false" data-events="feesClassEvents"> {{ __('action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            {{ __('edit') . ' ' . __('class') . ' ' . __('subject') }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    {{-- Template for old fees type --}}
                    <div class="row edit-fees-type-div" style="display: none;">
                        <div class="row col-11">
                            <div class="form-group col-md-6">
                                <input type="hidden" name="edit_fees_type[0][fees_class_id]" class="edit-fees-type-id form-control" disabled>
                                <select name="edit_fees_type[0][fees_type_id]" class="edit-fees-type form-control" required="required">
                                    <option value="">{{ __('select') }} {{__('fees')}} {{__('type')}}</option>
                                    @foreach ($fees_type_data as $fees_type)
                                    <option value="{{ $fees_type->id }}">
                                    {{ $fees_type->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                {!! Form::number('edit_fees_type[0][amount]', null, ['class' => 'form-control edit_amount','placeholder' => __('enter').' '.__('fees').' '.__('amount'),'id' => 'edit_amount']) !!}
                            </div>
                        </div>
                        <div class="col-1 pl-0">
                            <button type="button" class="btn btn-icon btn-inverse-danger remove-fees-type" title="Remove Core Subject">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Template for New fees type --}}
                    <div class="row template_fees_type" style="display: none;">
                        <div class="row col-11">
                            <div class="form-group col-md-6">
                                <select name="fees_type[0][fees_type_id]" class="form-control" required="required">
                                    <option value="">{{ __('select') }} {{__('fees')}} {{__('type')}}</option>
                                    @foreach ($fees_type_data as $fees_type)
                                    <option value="{{ $fees_type->id }}">
                                    {{ $fees_type->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                {!! Form::number('fees_type[0][amount]', null, ['class' => 'form-control amount-text','placeholder' => __('enter').' '.__('fees').' '.__('amount'),'id' => 'amount-text']) !!}
                            </div>
                        </div>
                        <div class="col-1 pl-0">
                            <button type="button" class="btn btn-inverse-success btn-icon add-fees-type remove_field" title="" id="remove_field">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <form class="pt-3" id="fees-class-create-form" action="{{ url('class/fees-type') }}" novalidate="novalidate">
                        <div class="modal-body">
                            <div class="form-group">
                                <label>{{ __('class') }} <span class="text-danger">*</span></label>
                                <select name="class_id" id="edit_class_id" class="form-control" disabled>
                                    <option value="">{{ __('select_class') }}</option>
                                    @foreach ($classes as $class)
                                    <option value="{{ $class->id}}" data-medium="{{$class->medium_id}}">
                                        {{ $class->name . ' - ' . $class->medium->name }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="class_id" id="class_id" value="" />
                            </div>

                            <h4 class="mb-3">
                                {{ __('fees') }} {{__('type')}}
                            </h4>
                            {{-- Dynamic New fees type will be added in this DIV --}}
                            <div class="mt-3 edit-extra-fees-types"></div>

                            <div>
                                <div class="form-group pl-0 mt-4">
                                    <button type="button" class="col-md-3 btn btn-inverse-success add-new-fees-type amount" id="amount">
                                        {{ __('fees') }} {{__('type')}} <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                            <input class="btn btn-theme" type="submit" value={{ __('save') }} />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
