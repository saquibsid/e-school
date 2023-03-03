<!-- partial:../../partials/_sidebar.html -->
<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        {{-- dashboard --}}
        <li class="nav-item">
            <a href="{{ url('/') }}" class="nav-link"> <span class="menu-title">{{ __('dashboard') }}</span> <i class="fa fa-home menu-icon"></i> </a>
        </li>

        @hasrole('Super Admin')
        {{-- academics --}}
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#academics-menu" aria-expanded="false" aria-controls="academics-menu"> <span class="menu-title">{{ __('academics') }}</span> <i class="fa fa-university menu-icon"></i> </a>
                <div class="collapse" id="academics-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('medium-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('medium.index') }}"> {{ __('medium') }} </a>
                            </li>
                        @endcan

                        @can('section-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('section.index') }}"> {{ __('section') }} </a>
                            </li>
                        @endcan

                        @can('subject-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('subject.index') }}"> {{ __('subject') }} </a>
                            </li>
                        @endcan

                        @can('class-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('class.index') }}"> {{ __('class') }} </a>
                            </li>
                        @endcan

                        @can('subject-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('class.subject') }}">{{ __('assign_class_subject') }} </a>
                            </li>
                        @endcan
                        @can('class-teacher-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('class.teacher') }}">
                                    {{ __('assign_class_teacher') }}
                                </a>
                            </li>
                        @endcan

                        @canany(['subject-teacher-list','subject-teacher-create','subject-teacher-edit','subject-teacher-delete'])
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('subject-teachers.index') }}">
                                    {{ __('assign') . ' ' . __('subject') . ' ' . __('teacher') }}
                                </a>
                            </li>
                        @endcan
                        @can('assign-class-to-new-student')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('students.assign-class') }}">
                                    {{ __('assign_new_student_class') }}
                                </a>
                            </li>
                        @endcan
                        @can('promote-student-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('promote-student.index') }}">
                                    {{ __('promote_student') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        
        @endrole

        {{-- student --}}
        @canany(['student-create','student-list','category-create','student-reset-password','class-teacher'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#student-menu" aria-expanded="false" aria-controls="academics-menu"> <span class="menu-title">{{ __('students') }}</span>

                    <i class="fa fa-graduation-cap menu-icon"></i> </a>
                <div class="collapse" id="student-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('student-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('students.create') }}">
                                    {{ __('student_admission') }}
                                </a>
                            </li>
                        @endcan

                        @can('student-create')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('students.index-students-roll-number') }}">
                                {{ __('assign') }} {{ __('roll_no') }}
                            </a>
                        </li>
                        @endcan

                        @canany(['student-list','class-teacher'])
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('students.index') }}">
                                    {{ __('student_details') }}
                                </a>
                            </li>
                        @endcanany

                        @can('category-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('category.index') }}">
                                    {{ __('student_category') }}
                                </a>
                            </li>
                        @endcan
                        @can('student-reset-password')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('students.reset_password') }}">
                                    {{ __('students') . ' ' . __('reset_password') }}
                                </a>
                            </li>
                        @endcan
                        @if(Auth::user()->hasRole('Super Admin'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('students.create-bulk-data') }}">
                                    {{ __('add_bulk_data') }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </li>
        @endcanany

        {{-- teacher --}}
        @can('teacher-create')
            <li class="nav-item">
                <a href="{{ route('teachers.index') }}" class="nav-link"> <span class="menu-title">{{ __('teacher') }}</span> <i class="fa fa-user menu-icon"></i> </a>
            </li>
        @endcan

        {{-- parents --}}
        @can('parents-create')
            <li class="nav-item">
                <a href="{{ route('parents.index') }}" class="nav-link"> <span class="menu-title">{{ __('parents') }}</span> <i class="fa fa-users menu-icon"></i> </a>
            </li>
        @endcan

        {{-- timetable --}}
        @canany(['timetable-create','class-timetable','teacher-timetable'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#timetable-menu" aria-expanded="false" aria-controls="timetable-menu"> <span class="menu-title">{{ __('timetable') }}</span>

                    <i class="fa fa-calendar menu-icon"></i> </a>
                <div class="collapse" id="timetable-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('timetable-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('timetable.index') }}">{{ __('create_timetable') }} </a>
                            </li>
                        @endcan
                        @canany(['class-timetable','class-teacher'])
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('class-timetable') }}">
                                    {{ __('class_timetable') }}
                                </a>
                            </li>
                        @endcanany
                        @can('teacher-timetable')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('teacher-timetable') }}">
                                    {{ __('teacher_timetable') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcanany

        {{-- Holiday --}}
        @canany(['holiday-create','holiday-list'])
            <li class="nav-item">
                @can('holiday-list')
                    <a href="{{ route('holiday.index') }}" class="nav-link"> <span class="menu-title">{{ __('holiday_list') }}</span> <i class="fa fa-calendar-check-o menu-icon"></i> </a>
                @endcan
            </li>
        @endcanany

        {{-- subject lesson --}}
        @canany(['lesson-list','lesson-create','lesson-edit','lesson-delete','topic-list','topic-create','topic-edit','topic-delete'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#subject-lesson-menu" aria-expanded="false" aria-controls="subject-lesson-menu"> <span class="menu-title">{{ __('subject_lesson') }}</span> <i class="fa fa-book menu-icon"></i> </a>
                <div class="collapse" id="subject-lesson-menu">
                    <ul class="nav flex-column sub-menu">
                        @canany(['lesson-list','lesson-create','lesson-edit','lesson-delete'])
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('lesson') }}"> {{ __('create_lesson') }}</a>
                            </li>
                        @endcanany

                        @canany(['topic-list','topic-create','topic-edit','topic-delete'])
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('lesson-topic') }}"> {{ __('create_topic') }}</a>
                            </li>
                        @endcanany
                    </ul>
                </div>
            </li>
        @endcanany

        {{-- student assignment --}}
        @canany(['assignment-create','assignment-submission'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#student-assignment-menu" aria-expanded="false" aria-controls="student-assignment-menu"> <span class="menu-title">{{ __('student_assignment') }}</span> <i class="fa fa-tasks menu-icon"></i> </a>
                <div class="collapse" id="student-assignment-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('assignment-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('assignment.index') }}">
                                    {{ __('create_assignment') }}
                                </a>
                            </li>
                        @endcan
                        @can('assignment-submission')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('assignment.submission') }}">
                                    {{ __('assignment_submission') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcanany
        @can('slider-create')
            <li class="nav-item">
                <a href="{{ route('sliders.index') }}" class="nav-link"> <span class="menu-title">{{ __('sliders') }}</span> <i class="fa fa-list menu-icon"></i> </a>
            </li>
        @endcan

        {{-- attendance --}}
        @canany(['class-teacher'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#attendance-menu" aria-expanded="false" aria-controls="attendance-menu"> <span class="menu-title">{{ __('attendance') }}</span> <i class="fa fa-check menu-icon"></i> </a>
                <div class="collapse" id="attendance-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('attendance-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('attendance.index') }}">
                                    {{ __('add_attendance') }}
                                </a>
                            </li>
                        @endcan

                        {{-- view attendance --}}
                        @can('attendance-list')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('attendance.view') }}">
                                    {{ __('view_attendance') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endcanany
        {{-- announceent --}}
        @can('announcement-create')
            <li class="nav-item">
                <a class="nav-link" href="{{ route('announcement.index') }}">
                    <span class="menu-title">{{ __('announcement') }}</span>
                    <i class="fa fa-check menu-icon"></i> </a>
            </li>
        @endcan
        {{-- exam --}}
        @canany(['exam-create','exam-timetable-create','exam-upload-marks','grade-create'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#exam-menu" aria-expanded="false" aria-controls="exam-menu">
                    <span class="menu-title">{{ __('exam') }}</span>
                    <i class="fa fa-book menu-icon"></i>
                </a>
                <div class="collapse" id="exam-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('exam-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('exams.index') }}"> {{ __('create_exam') }}
                                </a>
                            </li>
                        @endcan
                        @can('exam-timetable-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('exam-timetable.index') }}"> {{ __('create_exam_timetable') }}
                                </a>
                            </li>
                        @endcan
                        @can('class-teacher')
                            @can('exam-upload-marks')

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('exams.upload-marks') }}">
                                {{ __('upload')}} {{__('exam_marks') }}
                            </a>
                        </li>
                        @endcan
                        @can('exam-result')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('exams.get-result') }}">
                                {{ __('students')}} {{__('exam_result') }}
                            </a>
                        </li>
                        @endcan
                    @endcan
                    @can('grade-create')
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('grades') }}">
                            {{ __('exam') }} {{__('grade')}}
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>
        </li>
        @endcan

        {{-- Fees --}}
        @canany(['fees-type','fees-classes','fees-paid'])
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#fees-menu" aria-expanded="false" aria-controls="exam-menu">
                <span class="menu-title">{{ __('fees') }}</span>
                <i class="fa fa-dollar menu-icon"></i>
            </a>
            <div class="collapse" id="fees-menu">
                <ul class="nav flex-column sub-menu">
                    @can('fees-type')
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('fees-type.index') }}"> {{ __('fees') }} {{__('type')}}
                        </a>
                    </li>
                    @endcan
                     @can('fees-classes')
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('fees.class.index') }}">{{ __('assign') }} {{__('fees')}} {{__('classes')}} </a>
                        </li>
                    @endcan
                    @can('fees-paid')
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('fees.paid.index') }}"> {{__('fees')}} {{ __('paid') }}
                        </a>
                    </li>
                    @endcan
                    @can('fees-paid')
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('fees.transactions.log.index') }}"> {{__('fees')}} {{ __('transactions') }} {{__('logs')}}
                        </a>
                    </li>
                    @endcan
                    {{-- @can('fees-paid')
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('fees.receipt') }}"> {{__('fees')}} {{ __('receipt') }} {{__('logs')}}
                        </a>
                    </li>
                    @endcan --}}
                </ul>
            </div>
        </li>
        @endcan

        @canany(['manage-online-exam'])
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#online-exam-menu" aria-expanded="false" aria-controls="online-exam-menu">
                <span class="menu-title">{{ __('online') }} {{__('exam')}}</span>
                <i class="fa fa-laptop menu-icon"></i>
            </a>
            <div class="collapse" id="online-exam-menu">
                <ul class="nav flex-column sub-menu">
                @can('manage-online-exam')
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('online-exam.index') }}"> {{__('manage')}} {{ __('online') }} {{__('exam')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('online-exam-question.index') }}"> {{__('manage')}} {{__('questions')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('online-exam.terms-conditions') }}"> {{__('terms_condition')}}
                        </a>
                    </li>
                @endcan
                </ul>
            </div>
        </li>
        @endcan

        {{-- session-year --}}
        @can('session-year-create')
            <li class="nav-item">
                <a href="{{ route('session-years.index') }}" class="nav-link"> <span class="menu-title">{{ __('session_years') }}</span> <i class="fa fa-calendar-o menu-icon"></i> </a>
            </li>
        @endcan
        {{-- settings --}}
        @canany(['setting-create','fcm-setting-create','email-setting-create','privacy-policy','contact-us','about-us','role-create'])
            <li class="nav-item">
                <a class="nav-link" data-toggle="collapse" href="#settings-menu" aria-expanded="false" aria-controls="settings-menu"> <span class="menu-title">{{ __('system_settings') }}</span> <i class="fa fa-cog menu-icon"></i> </a>
                <div class="collapse" id="settings-menu">
                    <ul class="nav flex-column sub-menu">
                        @can('setting-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('app-settings') }}">
                                    {{ __('app_settings') }}</a>
                            </li>
                        @endcan
                        @can('setting-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('settings') }}">
                                    {{ __('general_settings') }}</a>
                            </li>
                        @endcan
                        @can('language-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('language') }}">
                                    {{ __('language_settings') }}</a>
                            </li>
                        @endcan
                        @can('fcm-setting-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('fcm-settings') }}"> {{ __('fcm_key') }}
                                </a>
                            </li>
                        @endcan
                        @can('fees-config')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('fees.config.index') }}"> {{__('fees')}} {{ __('configration') }}
                                </a>
                            </li>
                        @endcan
                        @can('email-setting-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('email-settings') }}">
                                    {{ __('email_configuration') }}
                                </a>
                            </li>
                        @endcan
                        @can('privacy-policy')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('privacy-policy') }}">
                                    {{ __('privacy_policy') }}
                                </a>
                            </li>
                        @endcan
                        @can('contact-us')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('contact-us') }}"> {{ __('contact_us') }}
                                </a>
                            </li>
                        @endcan
                        @can('about-us')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('about-us') }}"> {{ __('about_us') }}
                                </a>
                            </li>
                        @endcan
                        @can('terms-condition')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('terms-condition') }}">
                                    {{ __('terms_condition') }}
                                </a>
                            </li>
                        @endcan
                        @can('role-create')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('roles/') }}"> {{ __('role_permission') }}
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </li>
        @endif

        @if(Auth::user()->hasRole('Super Admin'))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('system-update.index') }}">
                    <span class="menu-title">{{ __('system_update') }}</span>
                    <i class="fa fa-cloud-download menu-icon"></i> </a>
            </li>
        @endif

    </ul>
</nav>
