<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\User;
use App\Models\Teacher;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use App\Models\SubjectTeacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Auth::user()->can('teacher-list')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return redirect(route('home'))->withErrors($response);
        }
        return view('teacher.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('teacher-create') || !Auth::user()->can('teacher-edit')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'email' => 'required|unique:users,email',
            'mobile' => 'required',
            'dob' => 'required',
            'qualification' => 'required',
            'current_address' => 'required',
            'permanent_address' => 'required',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {


            $user = new User();
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                // made file name with combination of current time
                $file_name = time() . '-' . $image->getClientOriginalName();
                //made file path to store in database
                $file_path = 'teachers/' . $file_name;
                //resized image
                resizeImage($image);
                //stored image to storage/public/teachers folder
                $destinationPath = storage_path('app/public/teachers');
                $image->move($destinationPath, $file_name);

                $user->image = $file_path;
            } else {
                $user->image = "";
            }
            $teacher_plain_text_password = str_replace('-', '', date('d-m-Y', strtotime($request->dob)));
            $user->password = Hash::make($teacher_plain_text_password);

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->gender = $request->gender;
            $user->current_address = $request->current_address;
            $user->permanent_address = $request->permanent_address;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->dob = date('Y-m-d', strtotime($request->dob));
            $user->save();


            $teacher = new Teacher();
            $teacher->user_id = $user->id;
            $teacher->qualification = $request->qualification;
            $teacher->save();
            if($request->grant_permission){
                $user->givePermissionTo([
                    'student-create',
                    'student-list',
                    'student-edit',
                    'student-delete',
                    'parents-create',
                    'parents-list',
                    'parents-edit'
                ]);
            }else{
                $user->revokePermissionTo([
                    'student-create',
                    'student-list',
                    'student-edit',
                    'student-delete',
                    'parents-create',
                    'parents-list',
                    'parents-edit'
                ]);
            }
            $user->assignRole([2]);
            $school_name = getSettings('school_name');
            $data = [
                'subject' => 'Welcome to ' . $school_name['school_name'],
                'name' => $request->first_name,
                'email' => $request->email,
                'password' => $teacher_plain_text_password,
                'school_name' => $school_name['school_name']
            ];

            Mail::send('teacher.email', $data, function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully')
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
        if (!Auth::user()->can('teacher-list')) {
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

        $sql = Teacher::with('user');
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('user_id', 'LIKE', "%$search%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")
                        ->orwhere('last_name', 'LIKE', "%$search%")
                        ->orwhere('gender', 'LIKE', "%$search%")
                        ->orwhere('email', 'LIKE', "%$search%")
                        ->orwhere('dob', 'LIKE', "%" . date('Y-m-d', strtotime($search)) . "%")
                        ->orwhere('qualification', 'LIKE', "%$search%")
                        ->orwhere('current_address', 'LIKE', "%$search%")
                        ->orwhere('permanent_address', 'LIKE', "%$search%");
                });
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
            $operate = '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata" data-id=' . $row->id . ' data-url=' . url('teachers') . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletedata" data-id=' . $row->id . ' data-user_id=' . $row->user_id . ' data-url=' . url('teachers', $row->user_id) . ' title="Delete"><i class="fa fa-trash"></i></a>';

            $data = getSettings('date_formate');

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $row->user_id;
            $tempRow['first_name'] = $row->user->first_name;
            $tempRow['last_name'] = $row->user->last_name;
            $tempRow['gender'] = $row->user->gender;
            $tempRow['current_address'] = $row->user->current_address;
            $tempRow['permanent_address'] = $row->user->permanent_address;
            $tempRow['email'] = $row->user->email;
            $tempRow['dob'] = date($data['date_formate'], strtotime($row->user->dob));
            $tempRow['mobile'] = $row->user->mobile;
            $tempRow['image'] =  $row->user->image;
            $tempRow['qualification'] = $row->qualification;

            if($row->user->can('student-create','student-list','student-edit','parents-create','parents-list','parents-edit')){
                $tempRow['has_student_permissions'] = 1;
            }else{
                $tempRow['has_student_permissions'] = 0;
            }

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
        $teacher = Teacher::find($id);
        return response($teacher);
    }


    public function update(Request $request)
    {
        if (!Auth::user()->can('teacher-edit')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'gender' => 'required',
            'email' => 'required|unique:users,email,' . $request->user_id,
            'mobile' => 'required',
            'dob' => 'required',
            'qualification' => 'required',
            'current_address' => 'required',
            'permanent_address' => 'required',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $user = User::find($request->user_id);
            if ($request->hasFile('image')) {
                if (Storage::disk('public')->exists($user->getRawOriginal('image'))) {
                    Storage::disk('public')->delete($user->getRawOriginal('image'));
                }
                $image = $request->file('image');
                // made file name with combination of current time
                $file_name = time() . '-' . $image->getClientOriginalName();
                //made file path to store in database
                $file_path = 'teachers/' . $file_name;
                //resized image
                resizeImage($image);
                //stored image to storage/public/teachers folder
                $destinationPath = storage_path('app/public/teachers');
                $image->move($destinationPath, $file_name);

                $user->image = $file_path;
            }
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->gender = $request->gender;
            $user->current_address = $request->current_address;
            $user->permanent_address = $request->permanent_address;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->dob = date('Y-m-d', strtotime($request->dob));
            $user->save();

            $teacher = Teacher::find($request->id);
            $teacher->user_id = $user->id;
            $teacher->qualification = $request->qualification;
            $teacher->save();

            if($request->edit_grant_permission){
                $user->givePermissionTo([
                    'student-create',
                    'student-list',
                    'student-edit',
                    'student-delete',
                    'parents-create',
                    'parents-list',
                    'parents-edit'
                ]);
            }else{
                $user->revokePermissionTo([
                    'student-create',
                    'student-list',
                    'student-edit',
                    'student-delete',
                    'parents-create',
                    'parents-list',
                    'parents-edit'
                ]);
            }

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
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->can('teacher-delete')) {
            $response = array(
                'message' => trans('no_permission_message')
            );
            return response()->json($response);
        }
        try {
            $teacher_id = Teacher::where('user_id',$id)->with('user')->get()->first();

            //check wheather the teacher exists in other table
            $subject_teacher = SubjectTeacher::where('teacher_id' , $teacher_id->id)->count();
            $class_section = ClassSection::where('class_teacher_id' , $teacher_id->id)->count();
            if($subject_teacher || $class_section){
                $response = array(
                    'error' => true,
                    'message' => trans('cannot_delete_beacuse_data_is_associated_with_other_data')
                );

            }else{
                $class_section_id = ClassSection::where('class_teacher_id',$teacher_id->id)->pluck('id')->first();
                if($class_section_id){
                    $class_teacher = ClassSection::find($class_section_id);
                    $class_teacher->class_teacher_id = null;
                    $class_teacher->save();
                    $teacher_id->user->revokePermissionTo('class-teacher');
                }
                $user = User::find($id);
                if (Storage::disk('public')->exists($user->image)) {
                    Storage::disk('public')->delete($user->image);
                }
                $user->delete();

                Teacher::where('user_id', $id)->delete();
                $response = [
                    'error' => false,
                    'message' => trans('data_delete_successfully')
                ];
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
