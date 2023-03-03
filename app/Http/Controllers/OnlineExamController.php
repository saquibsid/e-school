<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Subject;
use App\Models\Settings;
use App\Models\Students;
use App\Models\OnlineExam;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use Illuminate\Http\Request;
use App\Models\SubjectTeacher;
use App\Models\OnlineExamQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\OnlineExamStudentAnswer;
use App\Models\StudentOnlineExamStatus;
use App\Models\OnlineExamQuestionAnswer;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamQuestionOption;
use Illuminate\Support\Facades\Validator;

class OnlineExamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;

        //get the class and subject according to subject teacher
        $subject_teacher = SubjectTeacher::where('teacher_id',$teacher_id);
        $class_section_id = $subject_teacher->pluck('class_section_id');
        $class_id = ClassSection::whereIn('id',$class_section_id)->pluck('class_id');
        $subject_id = $subject_teacher->pluck('subject_id');

        $classes = ClassSchool::whereIn('id',$class_id)->with('medium')->get();
        $all_subjects = Subject::whereIn('id',$subject_id)->get();
        return response(view('online_exam.index', compact('classes', 'all_subjects')));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'class_id' => 'required',
            'subject_id' => 'required',
            'title' => 'required',
            'exam_key' => 'required|numeric|unique:online_exams,exam_key,NULL,id,deleted_at,NULL',
            'duration' => 'required|numeric|gte:1',
            'start_date' => 'required',
            'end_date' => 'required|after:start_date',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $data = getSettings('session_year');
            $session_year_id = $data['session_year'];

            $online_exam_create = new OnlineExam();
            $class_subject_id = ClassSubject::where(['class_id' => $request->class_id, 'subject_id' => $request->subject_id])->pluck('id')->first();
            $online_exam_create->class_subject_id = $class_subject_id;
            $online_exam_create->title = htmlspecialchars($request->title);
            $online_exam_create->exam_key = $request->exam_key;
            $online_exam_create->duration = $request->duration;
            $online_exam_create->start_date = $request->start_date;
            $online_exam_create->end_date = $request->end_date;
            $online_exam_create->session_year_id = $session_year_id;
            $online_exam_create->save();

            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully')
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        $sql = OnlineExam::with('class_subject','question_choice');


        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql = $sql->where('id', 'LIKE', "%$search%")
                ->orWhere('title', 'LIKE', "%$search%")
                ->orWhere('exam_key', 'LIKE', "%$search%")
                ->orWhere('duration', 'LIKE', "%$search%")
                ->orWhere('start_date', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                ->orWhere('end_date', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                ->orWhere('created_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                ->orWhere('updated_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                ->orWhereHas('class_subject', function ($q) use ($search) {
                    $q->whereHas('class', function ($c) use ($search) {
                        $c->where('name', 'LIKE', "%$search%");
                    })
                        ->orWhereHas('subject', function ($s) use ($search) {
                            $s->where('name', 'LIKE', "%$search%")
                                ->orWhere('type', 'LIKE', "%$search%");
                        });
                });
        }

        if (isset($_GET['class_id']) && !empty($_GET['class_id']) && isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {
            $class_subject_id = ClassSubject::where(['class_id' => $_GET['class_id'] , 'subject_id' => $_GET['subject_id']])->pluck('id');
            $sql = $sql->whereIn('class_subject_id', $class_subject_id);
        }
        if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
            $class_subject_id = ClassSubject::where('class_id',$_GET['class_id'])->pluck('id');
            $sql = $sql->whereIn('class_subject_id', $class_subject_id);
        }
        if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {
            $class_subject_id = ClassSubject::where('class_id',$_GET['class_id'])->pluck('id');
            $sql = $sql->whereIn('class_subject_id', $class_subject_id);
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = '';
            $operate = '<a href="' . route('exam.questions.index', ['id' => $row->id]) . '" class="btn btn-xs btn-gradient-info btn-rounded btn-icon add-questions" data-online_exam_id=' . $row->id . ' data-url=' . url('online-exam-question.index') . '><i class="fa fa-question-circle"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href="' . route('online-exam.result.index', ['id' => $row->id]) . '" title="Result" class="btn btn-xs btn-gradient-success btn-rounded btn-icon view-result"><i class="fa fa-file-text-o"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href=' . route('online-exam.destroy', $row->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $row->id . '><i class="fa fa-trash"></i></a>';

            $tempRow['online_exam_id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['class_id'] = $row->class_subject->class_id;
            $tempRow['class_name'] = $row->class_subject->class->name . ' - ' . $row->class_subject->class->medium->name;
            $tempRow['class_subject_id'] = $row->class_subject_id;
            $tempRow['subject_id'] = $row->class_subject->subject_id;
            $tempRow['subject_name'] = $row->class_subject->subject->name . ' - ' . $row->class_subject->subject->type;
            $tempRow['title'] = htmlspecialchars_decode($row->title);
            $tempRow['exam_key'] = $row->exam_key;
            $tempRow['duration'] = $row->duration;
            $tempRow['start_date'] = $row->start_date;
            $tempRow['end_date'] = $row->end_date;
            $tempRow['total_questions'] = $row->question_choice->count();
            $tempRow['created_at'] = $row->created_at;
            $tempRow['updated_at'] = $row->updated_at;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'edit_class_id' => 'required',
            'edit_subject_id' => 'required',
            'edit_title' => 'required',
            'edit_exam_key' => 'required|numeric|unique:online_exams,exam_key,' . $id . ',id,deleted_at,NULL',
            'edit_duration' => 'required|numeric|gte:1',
            'edit_start_date' => 'required|date',
            'edit_end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            // get the class subject id on the basis of class id and subject id
            $class_subject_id = ClassSubject::where(['class_id' => $request->edit_class_id, 'subject_id' => $request->edit_subject_id])->pluck('id')->first();

            $update_online_exam = OnlineExam::findOrFail($id);
            $update_online_exam->class_subject_id = $class_subject_id;
            $update_online_exam->title = $request->edit_title;
            $update_online_exam->exam_key = $request->edit_exam_key;
            $update_online_exam->duration = $request->edit_duration;
            $update_online_exam->start_date = $request->edit_start_date;
            $update_online_exam->end_date = $request->edit_end_date;
            $update_online_exam->save();

            $response = array(
                'error' => false,
                'message' => trans('data_update_successfully')
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {

            // check wheather online exam is associated with other table...
            $online_exam_question_choices = OnlineExamQuestionChoice::where('online_exam_id',$id)->count();
            $online_exam_student_answers = OnlineExamStudentAnswer::where('online_exam_id',$id)->count();
            $student_online_exam_status = StudentOnlineExamStatus::where('online_exam_id',$id)->count();

            if($online_exam_question_choices || $online_exam_student_answers || $student_online_exam_status){
                $response = array(
                    'error' => true,
                    'message' => trans('cannot_delete_beacuse_data_is_associated_with_other_data')
                );
            }else{
                OnlineExam::where('id', $id)->delete();
                $response = array(
                    'error' => false,
                    'message' => trans('data_delete_successfully')
                );
            }
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    public function getSubjects(Request $request)
    {
        $teacher_id = Auth::user()->teacher->id;
        $class_section_id = ClassSection::where('class_id',$request->class_id)->pluck('id');
        $subject_id = SubjectTeacher::whereIn('class_section_id',$class_section_id)->where('teacher_id',$teacher_id)->pluck('subject_id');
        try {
            $class_subjects = ClassSubject::with('subject')->where('class_id', $request->class_id)->whereIn('subject_id',$subject_id)->get();
            $response = array(
                'error' => false,
                'data' => $class_subjects
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function examQuestionsIndex()
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $online_exam_id = $_GET['id'];
        $online_exam_db = OnlineExam::where('id', $online_exam_id)->with('class_subject')->first();
        $exam_questions = OnlineExamQuestionChoice::where('online_exam_id',$online_exam_id)->with('online_exam','questions')->get();
        return response(view('online_exam.exam_questions', compact('online_exam_db','exam_questions')));
    }

    public function storeExamQuestionChoices(Request $request)
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'online_exam_id' => 'required',
            'question_type' => 'required|in:0,1',
            'question' => 'required_if:question_type,0',
            'option.*' => 'required_if:question_type,0',
            'equestion' => 'required_if:question_type,1',
            'eoption.*' => 'required_if:question_type,1',
            'answer.*' => 'required_if:question_type,0',
            'image' => 'nullable|mimes:jpeg,png,jpg|image|max:3048',
        ],
        [
            'question.required_if' => __('question_is_required'),
            'option.*.required_if' => __('all_options_are_required'),
            'equestion.required_if' => __('question_is_required'),
            'eoption.*.required_if' => __('all_options_are_required'),
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            if ($request->question_type == 1) {
                $question_store = new OnlineExamQuestion();
                $question_store->class_subject_id = $request->class_subject_id;
                $question_store->question_type = $request->question_type;
                $question_store->question = htmlspecialchars($request->equestion);
                $question_store->note = htmlspecialchars($request->note);
                if ($request->hasFile('image')) {
                    $image = $request->file('image');

                    // made file name with combination of current time
                    $file_name = time() . '-' . $image->getClientOriginalName();

                    //made file path to store in database
                    $file_path = 'online-exam-questions/' . $file_name;

                    //resized image
                    resizeImage($image);

                    //stored image to storage/public/online-exam-questions folder
                    $destinationPath = storage_path('app/public/online-exam-questions');
                    $image->move($destinationPath, $file_name);

                    //saved file path to database
                    $question_store->image_url = $file_path;
                }
                $question_store->save();

                // store options
                $options_id = array();
                foreach ($request->eoption as $key => $option) {
                    $question_option_store = new OnlineExamQuestionOption();
                    $question_option_store->question_id = $question_store->id;
                    $question_option_store->option = htmlspecialchars($option);
                    $question_option_store->save();
                    $options_id[$key] = $question_option_store->id;
                }
                foreach ($request->answer as $answer) {
                    foreach ($options_id as $key => $option) {
                        if ($key == $answer) {
                            $question_answer_store = new OnlineExamQuestionAnswer();
                            $question_answer_store->question_id = $question_store->id;
                            $question_answer_store->answer = $options_id[$key];
                            $question_answer_store->save();
                        }
                    }
                }
            } else {
                $question_store = new OnlineExamQuestion();
                $question_store->class_subject_id = $request->class_subject_id;
                $question_store->question_type = $request->question_type;
                $question_store->question = htmlspecialchars($request->question);
                $question_store->note = htmlspecialchars($request->note);
                if ($request->hasFile('image')) {
                    $image = $request->file('image');

                    // made file name with combination of current time
                    $file_name = time() . '-' . $image->getClientOriginalName();

                    //made file path to store in database
                    $file_path = 'online-exam-questions/' . $file_name;

                    //resized image
                    resizeImage($image);

                    //stored image to storage/public/online-exam-questions folder
                    $destinationPath = storage_path('app/public/online-exam-questions');
                    $image->move($destinationPath, $file_name);

                    //saved file path to database
                    $question_store->image_url = $file_path;
                }
                $question_store->save();

                // store options
                $options_id = array();
                foreach ($request->option as $key => $option) {
                    $question_option_store = new OnlineExamQuestionOption();
                    $question_option_store->question_id = $question_store->id;
                    $question_option_store->option = htmlspecialchars($option);
                    $question_option_store->save();
                    $options_id[$key] = $question_option_store->id;
                }
                foreach ($request->answer as $answer) {
                    foreach ($options_id as $key => $option) {
                        if ($key == $answer) {
                            $question_answer_store = new OnlineExamQuestionAnswer();
                            $question_answer_store->question_id = $question_store->id;
                            $question_answer_store->answer = $options_id[$key];
                            $question_answer_store->save();
                        }
                    }
                }
            }
            if($request->question_type){
                $response = array(
                    'error' => false,
                    'message' => trans('data_store_successfully'),
                    'data' => array(
                        'exam_id' => $request->online_exam_id,
                        'question_type' => $request->question_type,
                        'question_id' => $question_store->id,
                        'question' => "<textarea id='qc" . $question_store->id . "'>" . htmlspecialchars_decode($request->equestion) . "</textarea><script>setTimeout(() => {equation_editor = CKEDITOR.inline('qc" . $question_store->id . "', { skin:'moono',extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, }); },1000);</script>"
                    )
                );

            }else{
                $response = array(
                    'error' => false,
                    'message' => trans('data_store_successfully'),
                    'data' => array(
                        'exam_id' => $request->online_exam_id,
                        'question_type' => $request->question_type,
                        'question_id' => $question_store->id,
                        'question' => $request->question
                    )
                );
            }
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function getClassSubjectQuestions($class_subject_id)
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];
        $online_exam_id = OnlineExam::where('class_subject_id',$class_subject_id)->pluck('id');
        $exclude_question_id = OnlineExamQuestionChoice::whereIn('online_exam_id',$online_exam_id)->pluck('question_id');
        $sql = OnlineExamQuestion::with('class_subject', 'options', 'answers')->where('class_subject_id', $class_subject_id)->whereNotIn('id',$exclude_question_id);

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql = $sql->where('id', 'LIKE', "%$search%")
                ->orWhere('question', 'LIKE', "%$search%")
                ->orWhere('created_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                ->orWhere('updated_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                ->orWhereHas('class_subject', function ($q) use ($search) {
                    $q->WhereHas('class', function ($c) use ($search) {
                        $c->where('name', 'LIKE', "%$search%")
                            ->orWhereHas('medium', function ($m) use ($search) {
                                $m->where('name', 'LIKE', "%$search%");
                            });
                    })
                        ->orWhereHas('subject', function ($c) use ($search) {
                            $c->where('name', 'LIKE', "%$search%")
                                ->orWhere('type', 'LIKE', "%$search%");
                        });
                })
                ->orWhereHas('options', function ($p) use ($search) {
                    $p->where('option', 'LIKE', "%$search%");
                });
        }

        // if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
        //     $sql = $sql->where('class_id', $_GET['class_id']);
        // }
        // if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {
        //     $sql = $sql->where('subject_id', $_GET['subject_id']);
        // }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = '';
            $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            // $operate .= '<a href=' . route('online-exam-question.edit', $row->id) . ' class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href=' . route('online-exam-question.destroy', $row->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $row->id . '><i class="fa fa-trash"></i></a>';

            $tempRow['question_id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['class_id'] = $row->class_subject->class_id;
            $tempRow['class_name'] = $row->class_subject->class->name . ' - ' . $row->class_subject->class->medium->name;
            $tempRow['class_subject_id'] = $row->class_subject_id;
            $tempRow['subject_id'] = $row->class_subject->subject_id;
            $tempRow['subject_name'] = $row->class_subject->subject->name . ' - ' . $row->class_subject->subject->type;
            $tempRow['question_type'] = $row->question_type;
            $tempRow['question'] = '';
            $tempRow['options'] = array();
            $tempRow['answers'] = array();
            if ($row->question_type) {
                // $tempRow['question'] = "<textarea class='equation-editor-inline' name='qc" . $row->id . "'>" . htmlspecialchars_decode($row->question) . "</textarea>";
                $tempRow['question'] = "<div class='equation-editor-inline' contenteditable=false name='qc" . $row->id . "'>".htmlspecialchars_decode($row->question)."</div>";
                $tempRow['question_row'] = htmlspecialchars_decode($row->question);
                // $tempRow['question_textarea'] = "<textarea id='qc" . $row->id . "'>" . htmlspecialchars_decode($row->question) . "</textarea><script>for(equation_editor in CKEDITOR.instances){equation_editor = CKEDITOR.instances[equation_editor].updateElement();}setTimeout(() => {equation_editor = CKEDITOR.inline('qc" . $row->id . "', { skin:'moono',extraPlugins: 'mathjax', mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML', readOnly:true, }); },1000);</script>";
                $option_data = array();
                foreach ($row->options as $key => $options) {
                    $option_data = array(
                        'id' => $options->id,
                        // 'option' => "<textarea class='equation-editor-inline' name='op" . $options->id . "'>" . htmlspecialchars_decode($options->option) . "</textarea>",
                        'option' => "<div class='equation-editor-inline' contenteditable=false>".htmlspecialchars_decode($options->option)."</div>",
                        'option_row' => htmlspecialchars_decode($options->option)
                    );
                    $tempRow['options'][] = $option_data;
                }
                $answer_data = array();
                foreach ($row->answers as $answers) {
                    $answer_data = array(
                        'id' => $answers->id,
                        // 'answer' => "<textarea class='equation-editor-inline' name='an" . $answers->id . "'>" . htmlspecialchars_decode($answers->options->option) . "</textarea>",
                        'answer' => "<div class='equation-editor-inline' contenteditable=false>".htmlspecialchars_decode($answers->options->option)."</div>",
                    );
                    $tempRow['answers'][] = $answer_data;
                }
            } else {
                $tempRow['question'] = htmlspecialchars_decode($row->question);
                $tempRow['question_textarea'] = null;
                $option_data = array();
                foreach ($row->options as $key => $options) {
                    $option_data = array(
                        'id' => $options->id,
                        'option' => htmlspecialchars_decode($options->option),
                    );
                    $tempRow['options'][] = $option_data;
                }
                foreach ($row->answers as $key => $answers) {
                    $answer_data = array(
                        'id' => $answers->id,
                        'answer' => htmlspecialchars_decode($answers->options->option),
                    );
                    $tempRow['answers'][] =  $answer_data;
                }
            }
            $tempRow['image'] = $row->image_url;
            $tempRow['created_at'] = $row->created_at;
            $tempRow['updated_at'] = $row->updated_at;
            // $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
    public function storeQuestionsChoices(Request $request){
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required',
            'assign_questions.*.question_id' => 'required',
            'assign_questions.*.marks' => 'required|numeric'
        ],
        [
            'assign_questions.*.marks.required' => trans('marks_are_required')
        ]);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $data_store = array();
            foreach ($request->assign_questions as $question) {
                if(isset($question['edit_id']) && !empty($question['edit_id'])){
                    $edit_question_choice = OnlineExamQuestionChoice::find($question['edit_id']);
                    $edit_question_choice->marks = $question['marks'];
                    $edit_question_choice->save();
                }else{
                    $data_store[] = array(
                        'online_exam_id' => $request->exam_id,
                        'question_id' => $question['question_id'],
                        'marks' => $question['marks'],
                    );
                }
            }
            if(isset($data_store) && !empty($data_store)){
                OnlineExamQuestionChoice::insert($data_store);
            }
            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully'),
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function removeQuestionsChoices($id){
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {

            $student_submitted_answers = OnlineExamStudentAnswer::where('question_id',$id)->count();
            if($student_submitted_answers){
                $response = array(
                    'error' => true,
                    'message' => trans('cannot_delete_beacuse_data_is_associated_with_other_data')
                );
            }else{
                OnlineExamQuestionChoice::where('id',$id)->delete();
                $response = array(
                    'error' => false,
                    'message' => trans('data_delete_successfully'),
                );
            }
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function onlineExamTermsConditionIndex()
    {
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $settings = Settings::where('type', 'online_exam_terms_condition')->first();
        $type = 'online_exam_terms_condition';
        return response(view('online_exam.terms_conditions', compact('settings', 'type')));
    }
    public function storeOnlineExamTermsCondition(Request $request){
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        try {
            $type = $request->type;
            $message = $request->message;
            $id = Settings::select('id')->where('type', $type)->pluck('id')->first();
            if (isset($id) && !empty($id)) {
                $setting = Settings::find($id);
                $setting->message = htmlspecialchars($message);
                $setting->save();
                $response = array(
                    'error' => false,
                    'message' => trans('data_update_successfully'),
                );
            } else {
                $setting = new Settings();
                $setting->type = $type;
                $setting->message = htmlspecialchars($message);
                $setting->save();
                $response = array(
                    'error' => false,
                    'message' => trans('data_store_successfully'),
                );
            }
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function onlineExamResultIndex($id){
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $online_exam = OnlineExam::where('id',$id)->with('class_subject')->first();
        return response(view('online_exam.online_exam_result',compact('online_exam')));
    }
    public function showOnlineExamResult($id){
        if (!Auth::user()->can('manage-online-exam')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];
        if (isset($_GET['order']))
            $order = $_GET['order'];

        $teacher_id = Auth::user()->teacher->id;
        $class_section_id = SubjectTeacher::where('teacher_id',$teacher_id)->pluck('class_section_id');

        $student_id = Students::whereIn('class_section_id',$class_section_id)->pluck('id');

        $sql = StudentOnlineExamStatus::with('student_data','online_exam.question_choice')->whereIn('student_id',$student_id)->where('status',2)->where('online_exam_id',$id);


        // if (isset($_GET['search']) && !empty($_GET['search'])) {
        //     $search = $_GET['search'];
        //     $sql = $sql->where('id', 'LIKE', "%$search%")
        //         ->orWhere('title', 'LIKE', "%$search%")
        //         ->orWhere('key', 'LIKE', "%$search%")
        //         ->orWhere('duration', 'LIKE', "%$search%")
        //         ->orWhere('date', 'LIKE', "%$search%")
        //         ->orWhere('created_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
        //         ->orWhere('updated_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
        //         ->orWhereHas('class', function ($q) use ($search) {
        //             $q->where('name', 'LIKE', "%$search%");
        //         });
        // }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;
        foreach ($res as $student_attempt) {
        //get the total marks and obtained marks
            $total_obtained_marks = 0;
            $total_marks = 0;

            $exam_submitted_question_ids = OnlineExamStudentAnswer::where(['student_id' => $student_attempt->student_id , 'online_exam_id' => $student_attempt->online_exam_id])->pluck('question_id');

            $question_ids = OnlineExamQuestionChoice::whereIn('id',$exam_submitted_question_ids)->pluck('question_id');


            $exam_attempted_answers = OnlineExamStudentAnswer::where(['student_id' => $student_attempt->student_id, 'online_exam_id' => $student_attempt->online_exam_id])->pluck('option_id');

            //removes the question id of the question if one of the answer of particular question is wrong
            foreach ($question_ids as $question_id) {
                $check_questions_answers_exists = OnlineExamQuestionAnswer::where('question_id',$question_id)->whereNotIn('answer',$exam_attempted_answers)->count();
                if($check_questions_answers_exists){
                    unset($question_ids[array_search($question_id, $question_ids->toArray())]);
                }
            }

            $exam_correct_answers_question_id = OnlineExamQuestionAnswer::whereIn('question_id',$question_ids)->whereIn('answer',$exam_attempted_answers)->pluck('question_id');

            // get the data of only attempted data
            $total_obtained_marks = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->where('online_exam_id',$student_attempt->online_exam_id)->whereIn('question_id',$exam_correct_answers_question_id)->first();
            $total_obtained_marks = $total_obtained_marks['sum(marks)'];
            $total_marks = OnlineExamQuestionChoice::select(DB::raw("sum(marks)"))->where('online_exam_id',$student_attempt->online_exam_id)->first();
            $total_marks = $total_marks['sum(marks)'];

            $tempRow['student_id'] = $student_attempt->student_id;
            $tempRow['no'] = $no++;
            $tempRow['student_name'] = $student_attempt->student_data->user->first_name.' '.$student_attempt->student_data->user->last_name;
            if($total_obtained_marks){
                $tempRow['marks'] = $total_obtained_marks.' / '.$total_marks;
            }else{
                $total_obtained_marks = 0;
                $tempRow['marks'] = $total_obtained_marks.' / '.$total_marks;
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
