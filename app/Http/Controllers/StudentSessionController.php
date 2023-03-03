<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudentSessionController extends Controller
{
    public function index() {
        if (!Auth::user()->can('promote-student-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        $class_sections = ClassSection::with('class', 'section')->get();
        $session_year = SessionYear::select('id', 'name')->where('default', 0)->get();
        return view('promote_student.index', compact('class_sections', 'session_year'));
    }

    public function store(Request $request) {
        if (!Auth::user()->can('promote-student-create') || !Auth::user()->can('promote-student-edit')) {
            $response = array(
                'error' => true,
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'student_id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $message = false;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $new_session_year_id = $request->session_year_id;
            $new_class_section_id = $request->new_class_section_id;
            $old_class_section_id = $request->class_section_id;
            $get_old_data = StudentSessions::where(['class_section_id' => $old_class_section_id, 'session_year_id' => $session_year_id])->get();
            for ($i = 0; $i < count($request->student_id); $i++) {
                $check_data = StudentSessions::where(['class_section_id' => $new_class_section_id, 'session_year_id' => $new_session_year_id, 'student_id' => $request->student_id[$i]])->get();
                if (!empty($check_data)) {
                    if (count($get_old_data) > 0) {
                        $status = "status" . $request->student_id[$i];
                        $result = "result" . $request->student_id[$i];
                        //  pass & continue
                        if ($request->$status == 1 && $request->$result == 1) {
                            $update_student = Students::find($get_old_data[$i]['student_id']);
                            $update_student->class_section_id = $new_class_section_id;
                            $update_student->save();

                            $promote_student = new StudentSessions();
                            $promote_student->class_section_id = $new_class_section_id;
                            $promote_student->student_id = $request->student_id[$i];
                            $promote_student->session_year_id = $new_session_year_id;
                            $promote_student->result = $request->$result;
                            $promote_student->status = $request->$status;
                            $promote_student->save();
                        }

                        // fail & continue
                        if ($request->$status == 1 && $request->$result == 0) {
                            $update_student = Students::find($get_old_data[$i]['student_id']);

                            $promote_student = new StudentSessions();
                            $promote_student->class_section_id = $update_student->class_section_id;
                            $promote_student->student_id = $request->student_id[$i];
                            $promote_student->session_year_id = $new_session_year_id;
                            $promote_student->status = $request->$status;
                            $promote_student->result = $request->$result;
                            $promote_student->save();
                        }
                        // pass & leave
                        if ($request->$status == 0 && $request->$result == 1) {
                            $update_student = Students::find($get_old_data[$i]['student_id']);
                            $promote_student = new StudentSessions();
                            $promote_student->class_section_id = $new_class_section_id;
                            $promote_student->student_id = $request->student_id[$i];
                            $promote_student->session_year_id = $new_session_year_id;
                            $promote_student->result = $request->$result;
                            $promote_student->status = $request->$status;
                            $promote_student->save();
                            $user = User::find($update_student->user_id);
                            $user->status = 0;
                            $user->save();
                        }
                        // fail & leave
                        // $request->$status==0 && $request->$result==0
                        if ($request->$status == 0 && $request->$result == 0) {
                            $update_student = Students::find($get_old_data[$i]['student_id']);
                            $promote_student = new StudentSessions();
                            $promote_student->class_section_id = $update_student->class_section_id;
                            $promote_student->student_id = $request->student_id[$i];
                            $promote_student->session_year_id = $new_session_year_id;
                            $promote_student->status = $request->$status;
                            $promote_student->result = $request->$result;
                            $promote_student->save();
                            $user = User::find($update_student->user_id);
                            $user->status = 0;
                            $user->save();
                        }
                    }

                } else {
                    $message = true;
                }
            }
            if ($message == true) {
                $response = [
                    'error' => false,
                    'message' => trans('already promoted')
                ];
            } else {

                $response = [
                    'error' => false,
                    'message' => trans('data_store_successfully')
                ];
            }
        } catch (Exception $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }
        return response()->json($response);
    }

    public function getPromoteData(Request $request) {
        $response = StudentSessions::where(['class_section_id' => $request->class_section_id])->get();
        return response()->json($response);
    }

    public function show(Request $request) {
        if (!Auth::user()->can('promote-student-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'ASC';
        $class_section_id = $request->class_section_id;
        $promote_session = $request->session_year_id;
        $data = getSettings('session_year');
//        $session_year_id = $data['session_year'];
        $session_year_id = $request->session_year_id;
        $chk = StudentSessions::with('student')->where(['class_section_id' => $class_section_id, 'session_year_id' => $session_year_id, 'status' => 1])->count();
        if ($chk > 0) {
            $sql2 = StudentSessions::with('student')->where(['class_section_id' => $class_section_id, 'session_year_id' => $session_year_id, 'status' => 1]);
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = $_GET['search'];
                $sql2->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")->orwhere('mobile', 'LIKE', "%$search%");
            }
            $total = $sql2->count();
            $res = $sql2->get();
            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            $tempRow = array();
            $no = 1;
            foreach ($res as $row) {
                $get_result = $row->result;
                if ($get_result == 1) {
                    $result = '<div class="d-flex"><div class="form-check-inline"><label class="form-check-label">
                    <input required type="radio" class="result"  name="result' . $row->student_id . '" value="1" checked>Pass
                    </label></div>';
                    $result .= '<div class="form-check-inline"><label class="form-check-label">
                    <input type="radio" class="result"  name="result' . $row->student_id . '" value="0">Fail
                    </label></div></div>';
                } else if ($get_result == 0) {
                    $result = '<div class="d-flex"><div class="form-check-inline"><label class="form-check-label">
                    <input required type="radio" class="result"  name="result' . $row->student_id . '" value="1">Pass
                    </label></div>';
                    $result .= '<div class="form-check-inline"><label class="form-check-label">
                    <input type="radio" class="result"  name="result' . $row->student_id . '" value="0" checked>Fail
                    </label></div></div>';
                } else {
                    $result = '<div class="d-flex"><div class="form-check-inline"><label class="form-check-label">
                    <input required type="radio" class="result"  name="result' . $row->student_id . '" value="1">Pass
                    </label></div>';
                    $result .= '<div class="form-check-inline"><label class="form-check-label">
                    <input type="radio" class="result"  name="result' . $row->student_id . '" value="0">Fail
                    </label></div></div>';
                }

                $get_status = $row->status;
                if ($get_status == 1) {
                    $status = '<div class="d-flex"><div class="form-check-inline"><label class="form-check-label">
                    <input required type="radio" class="status"  name="status' . $row->student_id . '" value="1" checked>Continue
                    </label></div>';
                    $status .= '<div class="form-check-inline"><label class="form-check-label">
                    <input type="radio" class="status"  name="status' . $row->student_id . '" value="0">Leave
                    </label></div></div>';
                } else if ($get_status == 0) {
                    $status = '<div class="d-flex"><div class="form-check-inline"><label class="form-check-label">
                    <input required type="radio" class="status"  name="status' . $row->student_id . '" value="1">Continue
                    </label></div>';
                    $status .= '<div class="form-check-inline"><label class="form-check-label">
                    <input type="radio" class="status"  name="status' . $row->student_id . '" value="0" checked>Leave
                    </label></div></div>';
                } else {
                    $status = '<div class="d-flex"><div class="form-check-inline"><label class="form-check-label">
                    <input required type="radio" class="status"  name="status' . $row->student_id . '" value="1">Continue
                    </label></div>';
                    $status .= '<div class="form-check-inline"><label class="form-check-label">
                    <input type="radio" class="status"  name="status' . $row->student_id . '" value="0">Leave
                    </label></div></div>';
                }
                $tempRow['id'] = $row->id;
                $tempRow['no'] = $no++;
                $tempRow['student_id'] = "<input type='text' name='student_id[]' class='form-control' readonly value=" . $row->student_id . ">";
                $tempRow['admission_no'] = $row->student->admission_no;
                $tempRow['roll_no'] = $row->student->roll_number;
                $tempRow['name'] = $row->student->user->first_name . ' ' . $row->student->user->last_name;
                $tempRow['result'] = $result;
                $tempRow['status'] = $status;
                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;
            return response()->json($bulkData);
        }
        // else {
        //     // $sql = Students::where('class_section_id', $class_section_id)->where('is_new_admission', 0)->with('user');
        //     $sql=StudentSessions::with('student')->where(['class_section_id' => $class_section_id, 'session_year_id' => $promote_session,'status'=>1]);
        //     if (isset($_GET['search']) && !empty($_GET['search'])) {
        //         $search = $_GET['search'];
        //         $sql->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")->orwhere('mobile', 'LIKE', "%$search%");
        //     }
        //     $total = $sql->count();
        //     $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        //     $res = $sql->get();
        //     $bulkData = array();
        //     $bulkData['total'] = $total;
        //     $rows = array();
        //     $tempRow = array();
        //     $no = 1;
        //     foreach ($res as $row) {
        //         $result = '<div class="d-flex"><div class="form-check-inline"><label class="form-check-label">
        //         <input required type="radio" class="result"  name="result' . $row->id . '" value="1" checked>Pass
        //         </label></div>';
        //         $result .= '<div class="form-check-inline"><label class="form-check-label">
        //         <input type="radio" class="result"  name="result' . $row->id . '" value="0">Fail
        //         </label></div></div>';

        //         $status = '<div class="d-flex"><div class="form-check-inline"><label class="form-check-label">
        //         <input required type="radio" class="status"  name="status' . $row->id . '" value="1" checked>Continue
        //         </label></div>';
        //         $status .= '<div class="form-check-inline"><label class="form-check-label">
        //         <input type="radio" class="status"  name="status' . $row->id . '" value="0">Leave
        //         </label></div></div>';


        //         $tempRow['id'] = $row->id;
        //         $tempRow['no'] = $no++;
        //         $tempRow['student_id'] = "<input type='text' name='student_id[]' class='form-control' readonly value=" . $row->id . ">";
        //         $tempRow['admission_no'] = $row->admission_no;
        //         $tempRow['roll_no'] =  $row->student->roll_number;
        //         $tempRow['name'] = $row->user->first_name . ' ' . $row->user->last_name;
        //         $tempRow['result'] = $result;
        //         $tempRow['status'] = $status;
        //         $rows[] = $tempRow;
        //     }
        // }


    }
}
