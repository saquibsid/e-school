<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\Exam;
use App\Models\FeesPaid;
use App\Models\ExamMarks;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\OnlineExam;
use App\Models\SessionYear;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\ExamTimetable;
use App\Models\FeesChoiceable;
use App\Models\StudentSubject;
use App\Models\StudentSessions;
use App\Models\PaymentTransaction;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Auth;

class SessionYearController extends Controller
{
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index()
    {
        if (!Auth::user()->can('session-year-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);

        }
        return view('session_years.index');
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request)
    {
        if (!Auth::user()->can('session-year-create')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $request->validate([
            'name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $session_year = new SessionYear();
            $session_year->name = $request->name;
            $session_year->start_date = date('Y-m-d',strtotime($request->start_date));
            $session_year->end_date = date('Y-m-d',strtotime($request->end_date));
            $session_year->save();
            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully')
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }
        return response()->json($response);
    }


    public function update(Request $request)
    {
        if (!Auth::user()->can('session-year-edit')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $request->validate([
            'id' => 'required',
            'name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $session_year = SessionYear::find($request->id);
            $session_year->name = $request->name;
            $session_year->start_date = date('Y-m-d',strtotime($request->start_date));
            $session_year->end_date = date('Y-m-d',strtotime($request->end_date));
            $session_year->save();
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully')
            ];
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }
        return response()->json($response);
    }
    /**
    * Display the specified resource.
    *
    * @param int $id
    * @return \Illuminate\Http\Response
    */
    public function show()
    {
        if (!Auth::user()->can('session-year-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset']))
        $offset = $_GET['offset'];
        if (isset($_GET['limit']))
        $limit = $_GET['limit'];

        if (isset($_GET['sort']))
        $sort = $_GET['sort'];
        if (isset($_GET['order']))
        $order = $_GET['order'];

        $sql = SessionYear::where('id','!=',0);
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
            ->orwhere('name', 'LIKE', "%$search%")
            ->orwhere('start_date', 'LIKE', "%$search%")
            ->orwhere('end_date', 'LIKE', "%$search%")
            ->orwhere('default', 'LIKE', "%$search%");
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
            $operate = '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata" data-id=' . $row->id . ' data-url=' . url('session-years') . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletedata" data-id=' . $row->id . ' data-url=' . url('session-years', $row->id) . ' title="Delete"><i class="fa fa-trash"></i></a>';

            $data=getSettings('date_formate');

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['default'] = $row->default;
            $tempRow['start_date'] = date($data['date_formate'],strtotime($row->start_date));
            $tempRow['end_date'] = date($data['date_formate'],strtotime($row->end_date));
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param int $id
    * @return \Illuminate\Http\Response
    */
    public function edit($id)
    {
        $session_year = SessionYear::find($id);
        return response($session_year);
    }


    /**
    * Remove the specified resource from storage.
    *
    * @param int $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($id)
    {
        if (!Auth::user()->can('session-year-delete')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        try {

            //check wheather session year id is associated with other table..
            $announcements = Announcement::where('session_year_id',$id)->count();
            $assignment_submissions = AssignmentSubmission::where('session_year_id',$id)->count();
            $assignments = Assignment::where('session_year_id',$id)->count();
            $attendances = Attendance::where('session_year_id',$id)->count();
            $exam_marks = ExamMarks::where('session_year_id',$id)->count();
            $exam_results = ExamResult::where('session_year_id',$id)->count();
            $exam_timetables = ExamTimetable::where('session_year_id',$id)->count();
            $exams = Exam::where('session_year_id',$id)->count();
            $fees_choiceables = FeesChoiceable::where('session_year_id',$id)->count();
            $fees_paids = FeesPaid::where('session_year_id',$id)->count();
            $online_exams = OnlineExam::where('session_year_id',$id)->count();
            $payment_transactions = PaymentTransaction::where('session_year_id',$id)->count();
            $student_sessions = StudentSessions::where('session_year_id',$id)->count();
            $student_subjects = StudentSubject::where('session_year_id',$id)->count();

            if($announcements || $assignment_submissions || $assignments || $attendances || $exam_marks || $exam_results || $exam_timetables || $exams || $fees_choiceables || $fees_paids || $online_exams || $payment_transactions || $student_sessions || $student_subjects){
                $response = array(
                    'error' => true,
                    'message' => trans('cannot_delete_beacuse_data_is_associated_with_other_data')
                );
            }else{
                $year = SessionYear::find($id);
                if($year->default == 1){
                    $response = array(
                        'error' => true,
                        'message' => trans('default_session_year_cannot_delete')
                    );
                }else{
                    $year->delete();
                    $response = [
                        'error' => false,
                        'message' => trans('data_delete_successfully')
                    ];
                }
            }
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
}
