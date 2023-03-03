<script src="{{ asset('/assets/js/vendor.bundle.base.js') }}"></script>
<script src="{{ asset('/assets/js/Chart.min.js') }}"></script>
<script src="{{ asset('/assets/js/jquery.validate.min.js') }}"></script>
<script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>
<script src="{{ asset('/assets/select2/select2.min.js') }}"></script>

<script src="{{ asset('/assets/js/off-canvas.js') }}"></script>
<script src="{{ asset('/assets/js/hoverable-collapse.js') }}"></script>
<script src="{{ asset('/assets/js/misc.js') }}"></script>
<script src="{{ asset('/assets/js/settings.js') }}"></script>
<script src="{{ asset('/assets/js/todolist.js') }}"></script>
<script src="{{ asset('/assets/js/ekko-lightbox.min.js') }}"></script>


<script src="{{ asset('/assets/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/bootstrap-table-mobile.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/bootstrap-table-export.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/fixed-columns.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/tableExport.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/jspdf.min.js') }}"></script>
<script src="{{ asset('/assets/bootstrap-table/jspdf.plugin.autotable.js') }}"></script>


<script src="{{ asset('/assets/js/jquery.cookie.js') }}"></script>
<script src="{{ asset('/assets/js/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('/assets/js/datepicker.min.js') }}"></script>
<script src="{{ asset('/assets/js/jquery.repeater.js') }}"></script>
<script src="{{ asset('/assets/tinymce/tinymce.min.js') }}"></script>

<script src="{{ asset('/assets/color-picker/jquery-asColor.min.js') }}"></script>
<script src="{{ asset('/assets/color-picker/color.min.js') }}"></script>

<script src="{{ asset('/assets/js/custom/validate.js') }}"></script>
<script src="{{ asset('/assets/js/custom/function.js') }}"></script>
<script src="{{ asset('/assets/js/custom/custom.js') }}"></script>
<script src="{{ asset('/assets/js/custom/custom-bootstrap-table.js') }}"></script>

<script src="{{ asset('/assets/ckeditor-4/ckeditor.js') }}"></script>
<script src="{{ asset('/assets/ckeditor-4/adapters/jquery.js') }}"></script>

@if ($errors->any())
    @foreach ($errors->all() as $error)
        <script type='text/javascript'>
            $.toast({
                text: '{{ $error }}',
                showHideTransition: 'slide',
                icon: 'error',
                loaderBg: '#f2a654',
                position: 'top-right'
            });
        </script>
    @endforeach
@endif

@if (Session::has('success'))
    <script>
        $.toast({
            text: '{{ Session::get('success') }}',
            showHideTransition: 'slide',
            icon: 'success',
            loaderBg: '#f96868',
            position: 'top-right'
        });
    </script>
@endif

<script>
    $(document).on('click', '.deletedata', function() {
        Swal.fire({
            title: "{{ __('delete_title') }}",
            text: "{{ __('confirm_message') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "{{ __('yes_delete') }}"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: $(this).attr('data-url'),
                    type: "DELETE",
                    success: function(response) {
                        if (response['error'] == false) {
                            showSuccessToast(response['message']);
                            $('#table_list').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response['message']);
                        }
                    }
                });
            }
        })
    });
</script>
<script>
    const lang_no = "{{__('no')}}"
    const lang_yes = "{{__('yes')}}"
    const lang_cannot_delete_beacuse_data_is_associated_with_other_data = "{{__('cannot_delete_beacuse_data_is_associated_with_other_data')}}"
    const lang_delete_title = "{{__('delete_title')}}"
    const lang_delete_warning = "{{__('delete_warning')}}"
    const lang_yes_delete = "{{__('yes_delete')}}"
    const lang_cancel = "{{__('cancel')}}"
    const lang_no_data_found = "{{__('no_data_found')}}"
    const lang_cash = "{{__('cash')}}"
    const lang_cheque = "{{__('cheque')}}"
    const lang_online = "{{__('online')}}"
    const lang_success = "{{__('success')}}"
    const lang_failed = "{{__('failed')}}"
    const lang_pending = "{{__('pending')}}"
    const lang_select_subject = "{{__('select_subject')}}"
    const lang_option = "{{__('option')}}"
    const lang_simple_question = "{{__('simple_question')}}"
    const lang_equation_based = "{{__('equation_based')}}"
    const lang_select_option = "{{__('select') .' '. __('option')}}"
    const lang_enter_option = "{{__('enter').' '.__('option')}}"
    const lang_add_new_question = "{{__('add_new_question')}}";
    const lang_yes_change_it = "{{__('yes_change_it')}}"
    const lang_yes_uncheck = "{{ __('yes_unckeck') }}";
</script>
