@extends('layouts.master')

@section('title')
    {{ __('class') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('class') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create') . ' ' . __('class') }}
                        </h4>
                        <form class="pt-3 class-create-form" id="create-form" action="{{ route('class.store') }}"
                            method="POST" novalidate="novalidate">
                            <div class="form-group">
                                <label>{{ __('medium') }} <span class="text-danger">*</span></label>
                                <div class="col-12 d-flex row">
                                    @foreach ($mediums as $medium)
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" class="form-check-input" name="medium_id"
                                                    id="medium_{{ $medium->id }}" value="{{ $medium->id }}">
                                                {{ $medium->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="form-group">
                                <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                <input name="name" type="text" placeholder="{{ __('name') }}"
                                    class="form-control" />
                            </div>

                            <div class="form-group">
                                <label>{{ __('section') }} <span class="text-danger">*</span></label>
                                @foreach ($sections as $section)
                                    <div class="form-check">
                                        <label class="form-check-label">
                                            <input type="checkbox" class="form-check-input" name="section_id[]"
                                                value="{{ $section->id }}">{{ $section->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <input class="btn btn-theme" id="create-btn" type="submit" value={{ __('submit') }}>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('class') }}
                        </h4>
                        <div id="toolbar">
                            <select name="medium_id" id="filter_medium_id" class="form-control">
                                <option value="">{{ __('all') }}</option>
                                @foreach ($mediums as $medium)
                                    <option value="{{ $medium->id }}">{{ $medium->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <table aria-describedby="mydesc" class='table table-striped' id='table_list' data-toggle="table"
                            data-url="{{ url('class-list') }}" data-click-to-select="true" data-side-pagination="server"
                            data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200, All]" data-search="true"
                            data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                            data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                            data-mobile-responsive="true" data-sort-name="id" data-toolbar="#toolbar" data-sort-order="desc"
                            data-maintain-selected="true" data-export-types='["txt","excel"]'
                            data-export-options='{ "fileName": "class-list-<?= date('d-m-y') ?>" ,"ignoreColumn":
                            ["operate"]}'
                            data-show-export="true"
                            data-query-params="classQueryParams">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}</th>
                                    <th scope="col" data-field="no" data-sortable="true">{{ __('no') }}</th>
                                    <th scope="col" data-field="name" data-sortable="true">{{ __('name') }}</th>
                                    <th scope="col" data-field="medium_name" data-sortable="true">{{ __('medium') }}
                                    </th>
                                    <th scope="col" data-field="section_names" data-sortable="true">
                                        {{ __('section') }}
                                    </th>
                                    <th scope="col" data-field="created_at" data-sortable="true" data-visible="false">
                                        {{ __('created_at') }}</th>
                                    <th scope="col" data-field="updated_at" data-sortable="true" data-visible="false">
                                        {{ __('updated_at') }}</th>
                                    <th scope="col" data-field="operate" data-sortable="false"
                                        data-events="actionEvents">{{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('class') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form class="pt-3 class-edit-form" id="edit-form" action="{{ url('class') }}"
                            novalidate="novalidate">
                            <div class="modal-body">
                                <input type="hidden" name="edit_id" id="edit_id" value="" />
                                <div class="form-group">
                                    <label>{{ __('medium') }} <span class="text-danger">*</span></label>
                                    <div class="d-flex">
                                        @foreach ($mediums as $medium)
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    <input type="radio" class="form-check-input edit" name="medium_id"
                                                        id="edit_medium_{{ $medium->id }}"
                                                        value="{{ $medium->id }}"> {{ $medium->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                    <input name="name" id="edit_name" type="text"
                                        placeholder="{{ __('name') }}" class="form-control" />
                                </div>

                                <div class="form-group">
                                    <label>{{ __('section') }} <span class="text-danger">*</span></label>
                                    @foreach ($sections as $section)
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input type="checkbox" class="form-check-input edit" name="section_id[]"
                                                    id="edit_section_id"
                                                    value="{{ $section->id }}">{{ $section->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('close') }}</button>
                                <input class="btn btn-theme" type="submit" value={{ __('edit') }} />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        window.actionEvents = {
            'click .edit-data': function(e, value, row, index) {
                //Reset the Checkbox and Radio Button
                $('input[name="section_id[]"].edit').prop('checked', false)
                $('input[name=medium_id].edit').prop('checked', false);

                $('#edit_id').val(row.id);
                $('#edit_name').val(row.name);
                $('input[name=medium_id][value=' + row.medium_id + '].edit').prop('checked', true);
                row.sections.forEach(function(data) {
                    $('input[name="section_id[]"][value=' + data.id + '].edit').prop('checked', true);
                });
            }
        };
    </script>
@endsection
