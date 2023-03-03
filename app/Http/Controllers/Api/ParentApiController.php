<?php

namespace App\Http\Controllers\Api;

use Throwable;
use Carbon\Carbon;
use App\Models\Exam;
use App\Models\Grade;
use Razorpay\Api\Api;
use App\Models\Lesson;
use App\Models\Holiday;
use App\Models\Parents;
use App\Models\FeesPaid;
use App\Models\Students;
use Stripe\StripeClient;
use App\Models\ExamClass;
use App\Models\ExamMarks;
use App\Models\FeesClass;
use App\Models\Timetable;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\OnlineExam;
use App\Models\LessonTopic;
use App\Models\SessionYear;
use App\Models\Announcement;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use Illuminate\Http\Request;
use App\Models\ExamTimetable;
use App\Models\FeesChoiceable;
use App\Models\SubjectTeacher;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\OnlineExamStudentAnswer;
use App\Models\StudentOnlineExamStatus;
use App\Models\OnlineExamQuestionAnswer;
use App\Models\OnlineExamQuestionChoice;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TimetableCollection;

class ParentApiController extends Controller
{
    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $auth = Auth::user();
            if ($request->fcm_id) {
                $auth->fcm_id = $request->fcm_id;
                $auth->save();
            }

            $token = $auth->createToken($auth->first_name)->plainTextToken;

            $user = $auth->load(['parent']);
            $children = Students::where('father_id', $user->parent->id)->orWhere('mother_id', $user->parent->id)->orWhere('guardian_id', $user->parent->id)->with('class_section')->get();
            $user = flattenMyModel($user);

            foreach ($children as $child) {
                $child->first_name = $child->user->first_name;
                $child->last_name = $child->user->last_name;
                $child->image = $child->user->image;
                unset($child->user);
                //Set Class Section name
                $child->class_section_name = $child->class_section->class->name . " " . $child->class_section->section->name;
                //Set Medium name
                $child->medium_name = $child->class_section->class->medium->name;
                unset($child->class_section);

                //Set Category name
                $child->category_name = $child->category->name;
                unset($child->category);
            }
            $data = array_merge($user, ['children' => $children->toArray()]);
            $response = array(
                'error' => false,
                'message' => 'User logged-in!',
                'token' => $token,
                'data' => $data,
                'code' => 100,
            );
            return response()->json($response, 200);
        } else {
            $response = array(
                'error' => true,
                'message' => 'Invalid Login Credentials',
                'code' => 101
            );
            return response()->json($response, 200);
        }
    }

    // public function getChildren(Request $request) {
    //     try {
    //         $user = $request->user();
    //         $children = $user->parent->children->load(['user:id,first_name,last_name']);
    //         $response = array(
    //             'error' => false,
    //             'message' => 'Children Fetched Successfully.',
    //             'data' => $children,
    //             'code' => 200,
    //         );
    //         return response()->json($response, 200);
    //     } catch (\Exception $e) {
    //         $response = array(
    //             'error' => true,
    //             'message' => trans('error_occurred'),
    //             'code' => 103,
    //         );
    //         return response()->json($response, 200);
    //     }
    // }

    public function subjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $user = $request->user();
            $children = $user->parent->children()->where('id', $request->child_id)->first();
            $subjects = $children->subjects();

            $response = array(
                'error' => false,
                'message' => 'Student Subject Fetched Successfully.',
                'data' => $subjects,
                'code' => 200,
            );
            return response()->json($response, 200);
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
            return response()->json($response, 200);
        }
    }

    public function classSubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $user = $request->user();
            $children = $user->parent->children()->where('id', $request->child_id)->first();
            $subjects = $children->classSubjects();
            $response = array(
                'error' => false,
                'message' => 'Class Subject Fetched Successfully.',
                'data' => $subjects,
                'code' => 103
            );
            return response()->json($response, 200);
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103
            );
            return response()->json($response, 200);
        }
    }

    public function getTimetable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $user = $request->user();
            $children = $user->parent->children()->where('id', $request->child_id)->first();
            $timetable = Timetable::where('class_section_id', $children->class_section_id)->with('subject_teacher')->orderBy('day', 'asc')->orderBy('start_time', 'asc')->get();
            $response = array(
                'error' => false,
                'message' => "Timetable Fetched Successfully",
                'data' => new TimetableCollection($timetable),
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    /**
     * @param
     * subject_id : 2
     * lesson_id : 1 //OPTIONAL
     */
    public function getLessons(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'lesson_id' => 'nullable|numeric',
            'subject_id' => 'required',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }

        try {
            $user = $request->user();
            $children = $user->parent->children()->where('id', $request->child_id)->first();
            $data = Lesson::where('class_section_id', $children->class_section_id)->where('subject_id', $request->subject_id)->with('topic', 'file');
            if ($request->lesson_id) {
                $data->where('id', $request->lesson_id);
            }
            $data = $data->get();

            $response = array(
                'error' => false,
                'message' => "Lessons Fetched Successfully",
                'data' => $data,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    /**
     * @param
     * lesson_id : 1
     * topic_id : 1    //OPTIONAL
     */
    public function getLessonTopics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'lesson_id' => 'required|numeric',
            'topic_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }

        try {
            //Not Used Anywhere
            //            $user = $request->user();
            //            $children = $user->parent->children()->where('id',$request->child_id)->first();
            //            $subjects = $children->subjects();
            //
            //            $student = $request->user()->student;

            $data = LessonTopic::where('lesson_id', $request->lesson_id)->with('file');
            if ($request->topic_id) {
                $data->where('id', $request->topic_id);
            }
            $data = $data->get();

            $response = array(
                'error' => false,
                'message' => "Topics Fetched Successfully",
                'data' => $data,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    /**
     * @param
     * assignment_id : 1    //OPTIONAL
     * subject_id : 1       //OPTIONAL
     */
    public function getAssignments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'assignment_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
            'is_submitted' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }

        try {
            $user = $request->user();
            $children = $user->parent->children()->where('id', $request->child_id)->first();


            $data = Assignment::where('class_section_id', $children->class_section_id)->with('file', 'subject', 'submission.file');
            if ($request->assignment_id) {
                $data->where('id', $request->assignment_id);
            }
            if ($request->subject_id) {
                $data->where('subject_id', $request->subject_id);
            }
            if ($request->is_submitted) {
                if ($request->is_submitted == 1) {
                    $data->has('submission')->get();
                } else if ($request->is_submitted == 0) {
                    $data->has('submission', '<', 1)->get();
                }
            }

            $data = $data->orderBy('id', 'desc')->paginate();

            $response = array(
                'error' => false,
                'message' => "Assignments Fetched Successfully",
                'data' => $data,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    /**
     * @param
     * month : 4 //OPTIONAL
     * year : 2022 //OPTIONAL
     */
    public function getAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'month' => 'nullable|numeric',
            'year' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $user = $request->user();
            $children = $user->parent->children()->where('id', $request->child_id)->first();
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $attendance = Attendance::where('student_id', $children->id)->where('session_year_id', $session_year_id);
            $holidays = new Holiday;
            $session_year_data = SessionYear::find($session_year_id);
            if (isset($request->month)) {
                $attendance = $attendance->whereMonth('date', $request->month);
                $holidays = $holidays->whereMonth('date', $request->month);
            }

            if (isset($request->year)) {
                $attendance = $attendance->whereYear('date', $request->year);
                $holidays = $holidays->whereYear('date', $request->year);
            }
            $attendance = $attendance->get();
            $holidays = $holidays->get();

            $response = array(
                'error' => false,
                'message' => "Attendance Details Fetched Successfully",
                'data' => ['attendance' => $attendance, 'holidays' => $holidays, 'session_year' => $session_year_data],
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    public function getAnnouncements(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:subject,noticeboard,class',
            'child_id' => 'required_if:type,subject,class|numeric',
            'subject_id' => 'required_if:type,subject|numeric'
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $children = null;
            if ($request->type !== "noticeboard") {
                $user = $request->user();
                $children = $user->parent->children()->where('id', $request->child_id)->first();
                if (empty($children)) {
                    $response = array(
                        'error' => true,
                        'message' => "Invalid Child  ID",
                        'code' => 106,
                    );
                    return response()->json($response);
                }
                $class_id = $children->class_section->class->id;
            }


            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            if (isset($request->type) && $request->type == "subject") {
                $table = SubjectTeacher::where('class_section_id', $children->class_section_id)->where('subject_id', $request->subject_id)->get()->pluck('id');
                if (empty($table)) {
                    $response = array(
                        'error' => true,
                        'message' => "Invalid Subject ID",
                        'code' => 106,
                    );
                    return response()->json($response);
                }
            }
            $data = Announcement::with('file')->where('session_year_id', $session_year_id);

            if (isset($request->type) && $request->type == "noticeboard") {
                $data = $data->where('table_type', "");
            }

            if (isset($request->type) && $request->type == "class") {
                $data = $data->where('table_type', "App\Models\ClassSchool")->where('table_id', $class_id);
            }

            if (isset($request->type) && $request->type == "subject") {
                $data = $data->where('table_type', "App\Models\SubjectTeacher")->whereIn('table_id', $table);
            }

            $data = $data->orderBy('id', 'desc')->paginate();
            $response = array(
                'error' => false,
                'message' => "Announcement Details Fetched Successfully",
                'data' => $data,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    public function getTeachers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $user = $request->user();
            $children = $user->parent->children()->where('id', $request->child_id)->first();
            $subject_teachers = $children->class_section->subject_teachers->load(['subject:id,name', 'teacher.user']);
            $response = array(
                'error' => false,
                'message' => "Teacher Details Fetched Successfully",
                'data' => $subject_teachers,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }
    public function getExamList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|nullable',
            'status' => 'nullable:digits:0,1,2,3'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $student = Students::with('class_section')->where('id', $request->child_id)->first();
            $class_id = $student->class_section->class_id;
            $exam_data_db = ExamClass::with('exam.session_year:id,name')->where('class_id', $class_id)->get();

            foreach ($exam_data_db as $data) {

                // date status
                $starting_date_db = ExamTimetable::select(DB::raw("min(date)"))->where(['exam_id' => $data->exam->id, 'class_id' => $class_id])->first();
                $starting_date = $starting_date_db['min(date)'];
                $ending_date_db = ExamTimetable::select(DB::raw("max(date)"))->where(['exam_id' => $data->exam->id, 'class_id' => $class_id])->first();
                $ending_date = $ending_date_db['max(date)'];
                $currentTime = Carbon::now();
                $current_date = date($currentTime->toDateString());
                if ($current_date >= $starting_date && $current_date <= $ending_date) {
                    $exam_status = "1"; // Upcoming = 0 , On Going = 1 , Completed = 2
                } elseif ($current_date < $starting_date) {
                    $exam_status = "0"; // Upcoming = 0 , On Going = 1 , Completed = 2
                } else {
                    $exam_status = "2"; // Upcoming = 0 , On Going = 1 , Completed = 2
                }

                // $request->status  =  0 :- all exams , 1 :- Upcoming , 2 :- On Going , 3 :- Completed

                if (isset($request->status)) {
                    if ($request->status == 0) {
                        $exam_data[] = array(
                            'id' => $data->exam->id,
                            'name' => $data->exam->name,
                            'description' => $data->exam->description,
                            'publish' => $data->exam->publish,
                            'session_year' => $data->exam->session_year->name,
                            'exam_starting_date' => $starting_date,
                            'exam_ending_date' => $ending_date,
                            'exam_status' => $exam_status,
                        );
                    } else if ($request->status == 1) {
                        if ($exam_status == 0) {
                            $exam_data[] = array(
                                'id' => $data->exam->id,
                                'name' => $data->exam->name,
                                'description' => $data->exam->description,
                                'publish' => $data->exam->publish,
                                'session_year' => $data->exam->session_year->name,
                                'exam_starting_date' => $starting_date,
                                'exam_ending_date' => $ending_date,
                                'exam_status' => $exam_status,
                            );
                        }
                    } else if ($request->status == 2) {
                        if ($exam_status == 1) {
                            $exam_data[] = array(
                                'id' => $data->exam->id,
                                'name' => $data->exam->name,
                                'description' => $data->exam->description,
                                'publish' => $data->exam->publish,
                                'session_year' => $data->exam->session_year->name,
                                'exam_starting_date' => $starting_date,
                                'exam_ending_date' => $ending_date,
                                'exam_status' => $exam_status,
                            );
                        }
                    } else {
                        if ($exam_status == 2) {
                            $exam_data[] = array(
                                'id' => $data->exam->id,
                                'name' => $data->exam->name,
                                'description' => $data->exam->description,
                                'publish' => $data->exam->publish,
                                'session_year' => $data->exam->session_year->name,
                                'exam_starting_date' => $starting_date,
                                'exam_ending_date' => $ending_date,
                                'exam_status' => $exam_status,
                            );
                        }
                    }
                } else {
                    $exam_data[] = array(
                        'id' => $data->exam->id,
                        'name' => $data->exam->name,
                        'description' => $data->exam->description,
                        'publish' => $data->exam->publish,
                        'session_year' => $data->exam->session_year->name,
                        'exam_starting_date' => $starting_date,
                        'exam_ending_date' => $ending_date,
                        'exam_status' => $exam_status,
                    );
                }
            }

            $response = array(
                'error' => false,
                'data' => isset($exam_data) ? $exam_data : [],
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    public function getExamDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|nullable',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $student = Students::with('class_section')->where('id', $request->child_id)->first();
            $class_id = $student->class_section->class_id;
            $exam_data = Exam::with(['timetable' => function ($q) use ($request, $class_id) {
                $q->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->with('subject')->orderby('date');
            }])->where('id', $request->exam_id)->first();

            $data = isset($exam_data) ? $exam_data->timetable : [];

            $response = array(
                'error' => false,
                'data' => $data,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    public function getExamMarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|nullable',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $exam_result_db = ExamResult::with(['student' => function ($q) {
                $q->select('id', 'user_id', 'roll_number')->with('user:id,first_name,last_name');
            }])->with('exam', 'session_year:id,name')->where('student_id', $request->child_id)->get();

            if (sizeof($exam_result_db)) {
                foreach ($exam_result_db as $result) {
                    $exam_timetable_id = ExamTimetable::where('exam_id', $result->exam_id)->pluck('id');

                    $exam_marks_db = ExamMarks::whereIn('exam_timetable_id', $exam_timetable_id)->where('student_id', $result->student_id)->get();

                    $class_data = ClassSection::where('id', $result->class_section_id)->with('class.medium', 'section')->first();

                    $starting_date_db = ExamTimetable::select(DB::raw("min(date)"))->where(['exam_id' => $result->exam_id, 'class_id' => $class_data->class_id])->first();
                    $starting_date = $starting_date_db['min(date)'];

                    $exam_result = array();
                    $exam_result = array(
                        'result_id' => $result->id,
                        'exam_id' => $result->exam_id,
                        'exam_name' => $result->exam->name,
                        'class_name' => $class_data->class->name . '-' . $class_data->section->name . ' ' . $class_data->class->medium->name,
                        'student_name' => $result->student->user->first_name . ' ' . $result->student->user->last_name,
                        'exam_date' => $starting_date,
                        'total_marks' => $result->total_marks,
                        'obtained_marks' => $result->obtained_marks,
                        'percentage' => $result->percentage,
                        'grade' => $result->grade,
                        'session_year' => $result->session_year->name,
                    );

                    $exam_marks = array();
                    foreach ($exam_marks_db as $marks) {
                        $exam_marks[] = array(
                            'marks_id' => $marks->id,
                            'subject_name' => $marks->subject->name,
                            'subject_type' => $marks->subject->type,
                            'total_marks' => $marks->timetable->total_marks,
                            'obtained_marks' => $marks->obtained_marks,
                            'teacher_review' => $marks->teacher_review,
                            'grade' => $marks->grade,
                        );
                    }
                    $data[] = array(
                        'result' => $exam_result,
                        'exam_marks' => $exam_marks,
                    );
                }

                $response = array(
                    'error' => false,
                    'message' => "Exam Result Fetched Successfully",
                    'data' => $data,
                    'code' => 200,
                );
            } else {
                $response = array(
                    'error' => false,
                    'message' => "Exam Result Fetched Successfully",
                    'data' => [],
                    'code' => 200,
                );
            }
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    //get the class fees list
    public function getFeesClassList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $due_date = getSettings('fees_due_date');
            $due_date = $due_date['fees_due_date'];
            $current_date = Carbon::now()->format('m/d/Y');
            $due_charges = 0;

            // if due charges is applicable
            if ($current_date > $due_date) {
                $due_charges = getSettings('fees_due_charges');
                $due_charges = $due_charges['fees_due_charges'];
            }
            $class_id = ClassSection::where('id', $request->class_section_id)->pluck('class_id')->first();

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $fees_paid = FeesPaid::where(['student_id' => $request->child_id, 'session_year_id' => $session_year_id])->with('student.user:id,first_name,last_name', 'class');
            if ($fees_paid->count()) {
                $response = array(
                    'message' => 'Fees Already Paid',
                );
                return response()->json($response);
            } else {
                $fees_class = FeesClass::where('class_id', $class_id)->with('fees_type')->get();
                $fees_data = array();
                foreach ($fees_class as $fees) {
                    $fees_data[] = array(
                        'id' => $fees->fees_type->id,
                        'name' => $fees->fees_type->name,
                        'description' => $fees->fees_type->description,
                        'choiceable' => $fees->fees_type->choiceable,
                        'amount' => $fees->amount
                    );
                }

                // checks the due date and show the corresponding response
                if ($due_charges) {
                    $due_data = array(
                        'due_date' => $due_date,
                        'due_charges' => $due_charges,
                    );
                    $response = array(
                        'error' => false,
                        'data' => $fees_data,
                        'due_data' => $due_data,
                        'code' => 200,
                    );
                } else {
                    $response = array(
                        'error' => false,
                        'data' => $fees_data,
                        'code' => 200,
                    );
                }
            }
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    //store the fees choice
    public function storeFeesChoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required',
            'fees_type_id' => 'required'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $parent_id = Auth::user()->parent->id;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_section_id = Students::where('id', $request->child_id)->pluck('class_section_id')->first();
            $class_id = ClassSection::where('id', $class_section_id)->pluck('class_id')->first();
            $total_fees = 0;
            foreach ($request->fees_type_id as $fees_id) {
                $due_date = getSettings('fees_due_date');
                $due_date = $due_date['fees_due_date'];
                $current_date = Carbon::now()->format('m/d/Y');
                $due_charges = 0;

                $fees_amount = FeesClass::where(['class_id' => $class_id, 'fees_type_id' => $fees_id])->pluck('amount')->first();
                $total_fees += $fees_amount;

                $fees_choice_id = FeesChoiceable::where(['student_id' => $request->child_id, 'fees_type_id' => $fees_id, 'session_year_id' => $session_year_id])->pluck('id')->first();
                if (!empty($fees_choice_id)) {
                    $fees_choice_update = FeesChoiceable::find($fees_choice_id);
                    $fees_choice_update->student_id = $request->child_id;
                    $fees_choice_update->class_id = $class_id;
                    $fees_choice_update->fees_type_id = $fees_id;
                    $fees_choice_update->is_due_charges = 0;
                    $fees_choice_update->total_amount = $fees_amount;
                    $fees_choice_update->session_year_id = $session_year_id;
                    $fees_choice_update->save();
                } else {
                    $fees_choice = new FeesChoiceable();
                    $fees_choice->student_id = $request->child_id;
                    $fees_choice->class_id = $class_id;
                    $fees_choice->fees_type_id = $fees_id;
                    $fees_choice->is_due_charges = 0;
                    $fees_choice->total_amount = $fees_amount;
                    $fees_choice->session_year_id = $session_year_id;
                    $fees_choice->save();
                }
            }
            // if due charges is applicable
            if ($current_date > $due_date) {
                $due_charges = getSettings('fees_due_charges');
                $due_charges = $due_charges['fees_due_charges'];
                $fees_choice_id = FeesChoiceable::where(['student_id' => $request->child_id, 'is_due_charges' => 1, 'session_year_id' => $session_year_id])->pluck('id')->first();
                if (!empty($fees_choice_id)) {
                    $fees_choice_update = FeesChoiceable::find($fees_choice_id);
                    $fees_choice_update->student_id = $request->child_id;
                    $fees_choice_update->class_id = $class_id;
                    $fees_choice_update->is_due_charges = 1;
                    $fees_choice_update->total_amount = $due_charges;
                    $fees_choice_update->session_year_id = $session_year_id;
                    $fees_choice_update->save();
                } else {
                    $fees_choice = new FeesChoiceable();
                    $fees_choice->student_id = $request->child_id;
                    $fees_choice->class_id = $class_id;
                    $fees_choice->is_due_charges = 1;
                    $fees_choice->total_amount = $due_charges;
                    $fees_choice->session_year_id = $session_year_id;
                    $fees_choice->save();
                }
            }
            $payment_gateway_details = array();
            $razorpay_status = getSettings('razorpay_status');
            $razorpay_status = $razorpay_status['razorpay_status'];

            // get currency code from settings
            $setting_currency_code = getSettings('currency_code');
            $currency_code = $setting_currency_code['currency_code'];
            if ($due_charges) {
                $amount = $total_fees + $due_charges;
            } else {
                $amount = $total_fees;
            }
            if ($razorpay_status) {
                // get api key from settings
                $razorpay_setting_api_key = getSettings('razorpay_api_key');
                $razorpay_api_key = $razorpay_setting_api_key['razorpay_api_key'];

                // get secret key from settings
                $razorpay_setting_secret_key = getSettings('razorpay_secret_key');
                $razorpay_secret_key = $razorpay_setting_secret_key['razorpay_secret_key'];

                $currency_code = strtoupper($currency_code);

                // add the data to transaction table local
                $payment_transaction_db = new PaymentTransaction();
                $payment_transaction_db->student_id = $request->child_id;
                $payment_transaction_db->class_id = $class_id;
                $payment_transaction_db->parent_id = $parent_id;
                $payment_transaction_db->payment_gateway = 1;
                $payment_transaction_db->payment_status = 2;
                $payment_transaction_db->total_amount = $amount;
                $payment_transaction_db->session_year_id = $session_year_id;
                $payment_transaction_db->save();

                $api = new Api($razorpay_api_key, $razorpay_secret_key);
                $order = $api->order->create(array('amount' => $amount * 100, 'currency' => $currency_code, 'notes' => array(
                    'student_id' => $request->child_id,
                    'parent_id' => $parent_id,
                    'class_id' => $class_id,
                    'session_year_id' => $session_year_id,
                    'payment_transaction_id' => $payment_transaction_db->id
                )));

                // update the order id in trasaction table local
                $payemnt_transaction_update = PaymentTransaction::find($payment_transaction_db->id);
                $payemnt_transaction_update->order_id = $order->id;
                $payemnt_transaction_update->save();

                $payment_gateway_details = array(
                    'order_id' => $order->id,
                    'amount' => $order->amount,
                    'payment_transaction_id' => $payment_transaction_db->id,
                );
            }
            $stripe_status = getSettings('stripe_status');
            $stripe_status = $stripe_status['stripe_status'];
            if ($stripe_status) {
                $stripe_setting_secret_key = getSettings('stripe_secret_key');
                $stripe_secret_key = $stripe_setting_secret_key['stripe_secret_key'];

                $currency_code = strtolower($currency_code);

                // add the data to transaction table local
                $payment_transaction_db = new PaymentTransaction();
                $payment_transaction_db->student_id = $request->child_id;
                $payment_transaction_db->class_id = $class_id;
                $payment_transaction_db->parent_id = $parent_id;
                $payment_transaction_db->payment_gateway = 2;
                $payment_transaction_db->total_amount = $amount;
                $payment_transaction_db->session_year_id = $session_year_id;
                $payment_transaction_db->save();

                $stripe = new StripeClient($stripe_secret_key);
                $stripe_data = $stripe->paymentIntents->create(
                    [
                        'amount' => $amount * 100,
                        'currency' => $currency_code,
                        'metadata' => [
                            'student_id' => $request->child_id,
                            'parent_id' => $parent_id,
                            'class_id' => $class_id,
                            'session_year_id' => $session_year_id,
                            'payment_transaction_id' => $payment_transaction_db->id
                        ],
                    ]
                );

                // update the order id in trasaction table local
                $payemnt_transaction_update = PaymentTransaction::find($payment_transaction_db->id);
                $payemnt_transaction_update->order_id = $stripe_data->id;
                $payemnt_transaction_update->save();

                $payment_gateway_details = array(
                    'payment_intent_id' => $stripe_data->id,
                    'amount' => $stripe_data->amount,
                    'client_secret' => $stripe_data->client_secret,
                    'payment_transaction_id' => $payment_transaction_db->id,
                );
            }

            //validating the enable of gateways ..
            if ($razorpay_status == 0 && $stripe_status == 0) {
                $response = array(
                    'error' => true,
                    'message' => 'Please enable the payment gateway in panel',
                    'code' => 404,
                );
                return response()->json($response);
            }
            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully'),
                'payment_gateway_details' => $payment_gateway_details,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    // add the transaction data in transaction table
    public function feesTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'child_id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $transaction_db = PaymentTransaction::findOrFail($request->transaction_id);
            if ($request->payment_id) {
                $transaction_db->payment_id = $request->payment_id;
            }
            if ($request->payment_signature) {
                $transaction_db->payment_signature = $request->payment_signature;
            }
            $transaction_db->save();
            $response = array(
                'error' => false,
                'message' => trans('data_update_successfully'),
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    //get the fees paid list
    public function feesPaidList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $fees_paid = FeesPaid::where(['student_id' => $request->child_id])->with('session_year:id,name', 'class.medium')->get();

            $response = array(
                'error' => false,
                'data' => $fees_paid,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    //generate the reciept
    public function feesPaidReceiptPDF(Request $request)
    {
        try {
            $parent = Auth::user();
            $parent_name = $parent->first_name . ' ' . $parent->last_name;
            $logo = env('LOGO2');
            $logo = public_path('/storage/' . $logo);
            $school_name = env('APP_NAME');
            $school_address = getSettings('school_address');
            $school_address = $school_address['school_address'];

            $currency_symbol = getSettings('currency_symbol');
            if (isset($currency_symbol) && sizeof($currency_symbol)) {
                $currency_symbol = $currency_symbol['currency_symbol'];
            } else {
                $currency_symbol = null;
            }


            $fees_paid = FeesPaid::where('id', $request->fees_paid_id)->with('student.user:id,first_name,last_name', 'class', 'session_year')->get()->first();
            $student_id = $fees_paid->student_id;
            $class_id = $fees_paid->class_id;
            $session_year_id = $fees_paid->session_year_id;
            $fees_choiceable_db = FeesChoiceable::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->with('fees_type')->orderby('id', 'asc')->get();
            $pdf = Pdf::loadView('fees.fees_receipt', compact('logo', 'school_name', 'fees_paid', 'fees_choiceable_db', 'currency_symbol', 'school_address'));
            $data = [
                'subject' => 'Fees Receipt',
                'email' => $parent->email,
                'student_name' => $fees_paid->student->user->first_name . ' ' . $fees_paid->student->user->last_name,
                'session_name' => $fees_paid->session_year->name
            ];

            Mail::send('fees.pdf_email', $data, function ($message) use ($data, $pdf) {
                $message->to($data['email'])->subject($data['subject'])
                    ->attachData($pdf->output(), "fees-receipt.pdf");
            });

            $response = array(
                'error' => false,
                'message' => "Receipt Sent to your email",
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
            );
        }
        return response()->json($response);
    }

    public function getOnlineExamList(Request $request){
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' =>'nullable|numeric'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $student = Students::where('id',$request->child_id)->first();
            $class_id = ClassSection::where('id',$student->class_section_id)->pluck('class_id')->first();
            $class_subject_id = ClassSubject::where('class_id',$class_id);
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            //get current
            $time_data = Carbon::now()->toArray();
            $current_date_time = $time_data['formatted'];

            // checks the subject id param is passed or not .
            if(isset($request->subject_id) && !empty($request->subject_id)){
                $class_subject_id = $class_subject_id->where('subject_id',$request->subject_id)->pluck('id');
                $exam_data_db = OnlineExam::whereIn('class_subject_id',$class_subject_id)->where('session_year_id',$session_year_id)->where('end_date','>=',$current_date_time)->with('class_subject')->has('question_choice')->with(['student_attempt' => function($q) use($student){
                    $q->where('student_id',$student->id);
                }])->orderby('start_date')->paginate(15)->toArray();
            }else{
                $class_subject_id = $class_subject_id->pluck('id');
                $exam_data_db = OnlineExam::whereIn('class_subject_id',$class_subject_id)->where('session_year_id',$session_year_id)->where('end_date','>=',$current_date_time)->with('class_subject')->has('question_choice')->with(['student_attempt' => function($q) use($student){
                    $q->where('student_id',$student->id);
                }])->orderby('start_date')->paginate(15)->toArray();
            }
            if(isset($exam_data_db) && !empty($exam_data_db)){

                $exam_data = array();
                $exam_list = array();
                // making the array of exam data
                foreach ($exam_data_db['data'] as $data) {

                    // total marks of exams
                    $total_marks = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->where('online_exam_id',$data['id'])->first();
                    $total_marks = $total_marks['sum(marks)'];

                    if(!isset($data['student_attempt']) && empty($data['student_attempt'])){
                        $exam_list[] = array(
                            'exam_id' => $data['id'],
                            'class' => array(
                                'id' => $data['class_subject']['class_id'],
                                'name' => $data['class_subject']['class']['name'].' '.$data['class_subject']['class']['medium']['name']
                            ),
                            'subject' => array(
                                'id' => $data['class_subject']['subject_id'],
                                'name' => $data['class_subject']['subject']['name'].' - '.$data['class_subject']['subject']['type']
                            ),
                            'title' => $data['title'],
                            'exam_key' => $data['exam_key'],
                            'duration' => $data['duration'],
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                            'total_marks' => $total_marks,
                        );
                    }
                }

                //adding the exam data with pagination data
                $exam_data = array(
                    'current_page' => $exam_data_db['current_page'],
                    'data' => $exam_list,
                    'from' => $exam_data_db['from'],
                    'last_page' => $exam_data_db['last_page'],
                    'per_page' => $exam_data_db['per_page'],
                    'to' => $exam_data_db['to'],
                    'total' => $exam_data_db['total'],
                );
            }else{
                //if no data found
                $exam_data = null;
            }
            $response = array(
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $exam_data,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }
    public function getOnlineExamResultList(Request $request){
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' => 'nullable|numeric'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try{
            $student = Students::where('id',$request->child_id)->first();
            $class_id = ClassSection::where('id',$student->class_section_id)->pluck('class_id')->first();

            // get the class subject id on the basis of subject id passed
            if(isset($request->subject_id) && !empty($request->subject_id)){
                $class_subject_id = ClassSubject::where(['class_id' => $class_id , 'subject_id' => $request->subject_id])->pluck('id');
            }else{
                $class_subject_id = ClassSubject::where('class_id' , $class_id)->pluck('id');
            }

            // current session year id
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];


            $online_exam_db = OnlineExam::whereHas('student_attempt' , function($q) use($student){
                $q->where('student_id',$student->id);
            })->with('class_subject')->whereIn('class_subject_id',$class_subject_id)->where('session_year_id',$session_year_id)->paginate(10)->toArray();

            $exam_list_data = array();
            foreach ($online_exam_db['data'] as $data) {
                //get the choice question id
                $exam_submitted_question_ids = OnlineExamStudentAnswer::where(['student_id' => $student->id , 'online_exam_id' => $data['id']])->pluck('question_id');
                $exam_submitted_date = OnlineExamStudentAnswer::where(['student_id' => $student->id , 'online_exam_id' => $data['id']])->pluck('submitted_date')->first();

                $question_ids = OnlineExamQuestionChoice::whereIn('id',$exam_submitted_question_ids)->pluck('question_id');


                $exam_attempted_answers = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $data['id']])->pluck('option_id');

                //removes the question id of the question if one of the answer of particular question is wrong
                foreach ($question_ids as $question_id) {
                    $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id',$question_id)->whereNotIn('answer',$exam_attempted_answers)->count();
                    if($check_questions_answers_exists){
                        unset($question_ids[array_search($question_id, $question_ids->toArray())]);
                    }
                }

                $exam_correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id',$question_ids)->whereIn('answer',$exam_attempted_answers)->pluck('question_id');

                // get the data of only attempted data
                $total_obtained_marks_exam = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->where('online_exam_id',$data['id'])->whereIn('question_id',$exam_correct_answers_question_id)->first();
                $total_obtained_marks_exam = $total_obtained_marks_exam['sum(marks)'];
                $total_marks_exam = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->where('online_exam_id',$data['id'])->first();
                $total_marks_exam = $total_marks_exam['sum(marks)'];

                $exam_list_data[] = array(
                    'online_exam_id' => $data['id'],
                    'subject' => array(
                        'id' => $data['class_subject']['subject']['id'],
                        'name' => $data['class_subject']['subject']['name'].' - '.$data['class_subject']['subject']['type'],
                    ),
                    'title' => $data['title'],
                    'obtained_marks' => $total_obtained_marks_exam ?? "0",
                    'total_marks' => $total_marks_exam,
                    'exam_submitted_date' => $exam_submitted_date ?? date('Y-m-d', strtotime($data['end_date']))
                );
            }
            $exam_list = array(
                'current_page' => $online_exam_db['current_page'],
                'data' => $exam_list_data ?? '',
                'from' => $online_exam_db['from'],
                'last_page' => $online_exam_db['last_page'],
                'per_page' => $online_exam_db['per_page'],
                'to' => $online_exam_db['to'],
                'total' => $online_exam_db['total'],
            );
            $response = array(
                'error' => false,
                'data' => $exam_list ?? '',
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }
    public function getOnlineExamResult(Request $request){
        $validator = Validator::make($request->all(), [
            'child_id' => 'required_|numeric',
            'online_exam_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $student = Students::where('id',$request->child_id)->first();

            //get the total questions count
            $total_questions = OnlineExamQuestionChoice::where('online_exam_id' , $request->online_exam_id)->count();

            //get the exam's choiced question id
            $exam_choiced_question_ids = OnlineExamStudentAnswer::where(['student_id' => $student->id , 'online_exam_id' => $request->online_exam_id])->pluck('question_id');

            //get the questions id
            $question_ids = OnlineExamQuestionChoice::whereIn('id',$exam_choiced_question_ids)->pluck('question_id');

            //get the options submitted by student
            $exam_attempted_answers = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $request->online_exam_id])->pluck('option_id');

            //removes the question id of the question if one of the answer of particular question is wrong
            foreach ($question_ids as $question_id) {
                $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id',$question_id)->whereNotIn('answer',$exam_attempted_answers)->count();
                if($check_questions_answers_exists){
                    unset($question_ids[array_search($question_id, $question_ids->toArray())]);
                }
            }

            // get the correct answers counter
            $exam_correct_answers = OnlineExamQuestionAnswer::whereIn('question_id',$question_ids)->whereIn('answer',$exam_attempted_answers)->groupby('question_id')->pluck('question_id')->count();

            // question id of correct answers
            $exam_correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id',$question_ids)->whereIn('answer',$exam_attempted_answers)->pluck('question_id');

            //data of correct answers
            $exam_correct_answers_data = OnlineExamQuestionAnswer::whereIn('question_id',$question_ids)->whereIn('answer',$exam_attempted_answers)->groupby('question_id')->get();

            // array of correct answer with choiced exam id and marks
            $correct_answers_data = array();
            foreach ($exam_correct_answers_data as $correct_data) {
                $choice_questions = OnlineExamQuestionChoice::where(['online_exam_id' => $request->online_exam_id , 'question_id' => $correct_data->question_id])->first();
                $correct_answers_data[] = array(
                    'question_id' => $choice_questions->id,
                    'marks' => $choice_questions->marks
                );

            }

            // get questions ids
            $all_questions_ids = OnlineExamQuestionChoice::whereNotIn('question_id',$question_ids)->where('online_exam_id',$request->online_exam_id)->pluck('question_id');

            // get the incorrect answers && unattempted counter
            $exam_in_correct_answers = OnlineExamQuestionAnswer::whereIn('question_id',$all_questions_ids)->whereNotIn('answer',$exam_attempted_answers)->groupby('question_id')->pluck('question_id')->count();

            // data of in correct && unattempted answers
            $exam_in_correct_answers_data = OnlineExamQuestionAnswer::whereIn('question_id',$all_questions_ids)->whereNotIn('answer',$exam_attempted_answers)->groupby('question_id')->get();

            // array of in correct answer && unattempted with choiced exam id and marks
            $in_correct_answers_data = array();
            foreach ($exam_in_correct_answers_data as $in_correct_data) {
                $choice_questions = OnlineExamQuestionChoice::where(['online_exam_id' => $request->online_exam_id , 'question_id' => $in_correct_data->question_id])->first();
                if(isset($choice_questions) && !empty($choice_questions)){
                    $in_correct_answers_data[] = array(
                        'question_id' => $choice_questions->id,
                        'marks' => $choice_questions->marks
                    );
                }
            }

            // total obtained and total marks
            $total_obtained_marks = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->where('online_exam_id',$request->online_exam_id)->whereIn('question_id',$exam_correct_answers_question_id)->first();
            $total_obtained_marks = $total_obtained_marks['sum(marks)'];
            $total_marks = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->where('online_exam_id',$request->online_exam_id)->first();
            $total_marks = $total_marks['sum(marks)'];

            // final array data
            $exam_result = array(
                'total_questions' => $total_questions,
                'correct_answers' => array(
                    'total_questions' => $exam_correct_answers,
                    'question_data' => $correct_answers_data ?? ''
                ),
                'in_correct_answers' => array(
                    'total_questions' => $exam_in_correct_answers,
                    'question_data' => $in_correct_answers_data ?? ''
                ),
                'total_obtained_marks' => $total_obtained_marks ?? '0',
                'total_marks' => $total_marks
            );
            $response = array(
                'error' => false,
                'data' => $exam_result ?? '',
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    public function getOnlineExamReport(Request $request){
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $student = Students::where('id',$request->child_id)->first();
            $class_id = ClassSection::where('id',$student->class_section_id)->pluck('class_id')->first();
            $class_subject_id = ClassSubject::where(['class_id' => $class_id , 'subject_id' => $request->subject_id])->pluck('id');


            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            //get current
            $time_data = Carbon::now()->toArray();
            $current_date_time = $time_data['formatted'];

            // checks the exams exists
            $exam_exists = OnlineExam::whereIn('class_subject_id',$class_subject_id)->where('session_year_id',$session_year_id)->where('start_date','<=',$current_date_time)->count();
            if(isset($exam_exists) && !empty($exam_exists)){
                //total online exams id and counts
                $total_exam_ids = OnlineExam::whereIn('class_subject_id',$class_subject_id)->where('session_year_id',$session_year_id)->where('start_date','<=',$current_date_time)->pluck('id');
                //online exam ids attempted
                $attempted_online_exam_ids = StudentOnlineExamStatus::where('student_id',$student->id)->whereIn('online_exam_id',$total_exam_ids)->pluck('online_exam_id');

                //get the submitted answers (i.e. option id)
                $online_exams_attempted_answers = OnlineExamStudentAnswer::where('student_id',$student->id)->whereIn('online_exam_id',$total_exam_ids)->pluck('option_id');

                //get the submitted choiced question id
                $online_exams_submitted_question_ids = OnlineExamStudentAnswer::where('student_id',$student->id)->whereIn('online_exam_id',$total_exam_ids)->pluck('question_id');

                //get the questions id
                $get_question_ids = OnlineExamQuestionChoice::whereIn('id',$online_exams_submitted_question_ids)->pluck('question_id');

                //removes the question id of the question if one of the answer of particular question is wrong
                foreach ($get_question_ids as $question_id) {
                    $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id',$question_id)->whereNotIn('answer',$online_exams_attempted_answers)->count();
                    if($check_questions_answers_exists){
                        unset($get_question_ids[array_search($question_id, $get_question_ids->toArray())]);
                    }
                }
                //get the correct answers question id
                $correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id',$get_question_ids)->whereIn('answer',$online_exams_attempted_answers)->pluck('question_id');


                //total exams
                $total_exams = OnlineExam::whereIn('class_subject_id',$class_subject_id)->count();

                //total exam attempted
                $total_attempted_exams = StudentOnlineExamStatus::where('student_id',$student->id)->whereIn('online_exam_id',$total_exam_ids)->count();

                // total missed exams
                $total_missed_exams = OnlineExam::whereNotIn('id',$attempted_online_exam_ids)->whereIn('class_subject_id',$class_subject_id)->count();

                // get the correct choiced question id and marks
                $total_obtained_marks = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->whereIn('online_exam_id',$total_exam_ids)->whereIn('question_id',$correct_answers_question_id)->first();
                $total_obtained_marks = $total_obtained_marks['sum(marks)'];

                //overall total marks
                $total_marks = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->whereIn('online_exam_id',$total_exam_ids)->first();
                $total_marks = $total_marks['sum(marks)'];

                if($total_obtained_marks){
                    $percentage = number_format(($total_obtained_marks * 100) / $total_marks,2);
                }


                // particular online exam data
                $online_exams_db = OnlineExam::with(['student_attempt' => function($q) use($student){
                    $q->where('student_id',$student->id);
                }])->has('question_choice')->whereIn('class_subject_id',$class_subject_id)->paginate(10)->toArray();


                $exam_list = array();
                $total_obtained_marks_exam = '';
                $no = 0;
                foreach ($online_exams_db['data'] as $data) {
                    $exam_submitted_question_ids = OnlineExamStudentAnswer::where(['student_id' => $student->id , 'online_exam_id' => $data['id']])->pluck('question_id');
                    $get_exam_question_ids = OnlineExamQuestionChoice::whereIn('id',$exam_submitted_question_ids)->pluck('question_id');


                    $exam_attempted_answers = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $data['id']])->pluck('option_id');


                    //removes the question id of the question if one of the answer of particular question is wrong
                    foreach ($get_exam_question_ids as $question_id) {
                        $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id',$question_id)->whereNotIn('answer',$exam_attempted_answers)->count();
                        if($check_questions_answers_exists){
                            unset($get_exam_question_ids[array_search($question_id, $get_exam_question_ids->toArray())]);
                        }
                    }

                    $exam_correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id',$get_exam_question_ids)->whereIn('answer',$exam_attempted_answers)->pluck('question_id');

                    $total_obtained_marks_exam = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->where('online_exam_id',$data['id'])->whereIn('question_id',$exam_correct_answers_question_id)->first();
                    $total_obtained_marks_exam = $total_obtained_marks_exam['sum(marks)'];
                    $total_marks_exam = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->where('online_exam_id',$data['id'])->first();
                    $total_marks_exam = $total_marks_exam['sum(marks)'];

                    $exam_list[] = array(
                        'online_exam_id' => $data['id'],
                        'title' => $data['title'],
                        'obtained_marks' => $total_obtained_marks_exam ?? "0",
                        'total_marks' => $total_marks_exam ?? "0",
                    );

                }


                // array of final data
                $online_exam_report_data = array(
                    'total_exams' => $total_exams,
                    'attempted' => $total_attempted_exams,
                    'missed_exams' => $total_missed_exams,
                    'total_marks' => $total_marks ?? "0",
                    'total_obtained_marks' => $total_obtained_marks ?? "0",
                    'percentage' => $percentage ?? "0",
                    'exam_list' => array(
                        'current_page' => $online_exams_db['current_page'],
                        'data' => $exam_list,
                        'from' => $online_exams_db['from'],
                        'last_page' => $online_exams_db['last_page'],
                        'per_page' => $online_exams_db['per_page'],
                        'to' => $online_exams_db['to'],
                        'total' => $online_exams_db['total'],
                    )
                );
            }
            if(isset($online_exam_report_data)){
                $response = array(
                    'error' => false,
                    'data' => $online_exam_report_data,
                    'code' => 200,
                );
            }else{
                $response = array(
                    'error' => true,
                    'code' => 103,
                );
            }
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response);
    }

    public function getAssignmentReport(Request $request){
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first(),
                'code' => 102,
            );
            return response()->json($response);
        }
        try {
            $student = Students::where('id',$request->child_id)->first();

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            // get the assignments ids
            $assingment_ids = Assignment::where(['class_section_id' => $student->class_section_id,'session_year_id' => $session_year_id , 'subject_id' => $request->subject_id])->pluck('id');

            //total assignments of class
            $total_assignments = Assignment::where(['class_section_id' => $student->class_section_id,'session_year_id' => $session_year_id , 'subject_id' => $request->subject_id])->count();

            //total assignment submiited
            $total_submitted_assignments = AssignmentSubmission::where('student_id' , $student->id)->whereIn('assignment_id',$assingment_ids)->count();

            // submitted assingment id
            $submitted_assignment_ids = AssignmentSubmission::where('student_id' , $student->id)->whereIn('assignment_id',$assingment_ids)->pluck('assignment_id');

            //total assignment unsubmitted
            $total_assingment_unsubmitted = Assignment::where(['class_section_id' => $student->class_section_id , 'subject_id' => $request->subject_id])->whereNotIn('id',$submitted_assignment_ids)->count();

            //total points of assignment submitted
            $total_assignment_submitted_points = Assignment::select(DB::raw("sum(points)"))->where('class_section_id' , $student->class_section_id)->whereIn('id',$submitted_assignment_ids)->whereNot('points',null)->first();
            $total_assignment_submitted_points = $total_assignment_submitted_points['sum(points)'];

            // total obtained assignment points
            $assingment_id_with_points = Assignment::where(['class_section_id' => $student->class_section_id , 'subject_id' => $request->subject_id])->whereIn('id',$submitted_assignment_ids)->whereNot('points',null)->pluck('id');
            $total_points_obtained = AssignmentSubmission::select(DB::raw("sum(points)"))->whereIn('assignment_id',$assingment_id_with_points)->first();
            $total_points_obtained = $total_points_obtained['sum(points)'];

            //percentage
            $percentage = number_format(($total_points_obtained*100) / $total_assignment_submitted_points,2);

            $submitted_assignment_data_db = Assignment::with('submission')->where(['class_section_id' => $student->class_section_id , 'subject_id' => $request->subject_id])->whereIn('id',$submitted_assignment_ids)->whereNot('points',null);
            $submitted_assignment_data_with_points = $submitted_assignment_data_db->paginate(10)->toArray();

            $submitted_assingment_data = array();
            foreach ($submitted_assignment_data_with_points['data'] as $submitted_data) {
                $submitted_assingment_data[] = array(
                    'assignment_id' => $submitted_data['id'],
                    'assignment_name' => $submitted_data['name'],
                    'obtained_points' => $submitted_data['submission']['points'],
                    'total_points' => $submitted_data['points']
                );
            }
            $assingment_report = array(
                'assignments' => $total_assignments,
                'submitted_assignments' => $total_submitted_assignments,
                'unsubmitted_assignments' => $total_assingment_unsubmitted,
                'total_points' => $total_assignment_submitted_points,
                'total_obtained_points' => $total_points_obtained,
                'percentage' => $percentage,
                'submitted_assignment_with_points_data' => array(
                    'current_page' => $submitted_assignment_data_with_points['current_page'],
                    'data' => $submitted_assingment_data,
                    'from' => $submitted_assignment_data_with_points['from'],
                    'last_page' => $submitted_assignment_data_with_points['last_page'],
                    'per_page' => $submitted_assignment_data_with_points['per_page'],
                    'to' => $submitted_assignment_data_with_points['to'],
                    'total' => $submitted_assignment_data_with_points['total'],
                )
            );
            $response = array(
                'error' => false,
                'data' => $assingment_report,
                'code' => 200,
            );
        } catch (\Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'code' => 103,
            );
        }
        return response()->json($response,200,[],JSON_PRESERVE_ZERO_FRACTION);
    }
}
