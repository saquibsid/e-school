<?php

namespace App\Http\Controllers;

use Throwable;
use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Models\Mediums;
use App\Models\FeesPaid;
use App\Models\FeesType;
use App\Models\Settings;
use App\Models\Students;
use App\Models\FeesClass;
use App\Models\ClassSchool;
use App\Models\SessionYear;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use App\Models\FeesChoiceable;
use App\Models\Parents;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FeesTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('fees.fees_types');
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
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'nullable',
            'choiceable' => 'required|in:0,1'
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $fees_type = new FeesType();
            $fees_type->name = $request->name;
            $fees_type->description = $request->description;
            $fees_type->choiceable = $request->choiceable;
            $fees_type->save();
            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully'),
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
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

        $sql = FeesType::select('*');
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%")
                ->orwhere('description', 'LIKE', "%$search%")
                ->orwhere('created_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")
                ->orwhere('updated_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%");
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
            $operate = '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a href=' . route('fees-type.destroy', $row->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $row->id . '><i class="fa fa-trash"></i></a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['description'] = $row->description;
            $tempRow['choiceable'] = $row->choiceable;
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
        $validator = Validator::make($request->all(), [
            'edit_name' => 'required',
            'edit_description' => 'nullable',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $fees_type = FeesType::findOrFail($id);
            $fees_type->name = $request->edit_name;
            $fees_type->description = $request->edit_description;
            $fees_type->choiceable = $request->edit_choiceable;
            $fees_type->save();
            $response = array(
                'error' => false,
                'message' => trans('data_update_successfully'),
            );
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
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
        try {
            // check wheather fees type id is associate with other tables...
            $fees_choiceables = FeesChoiceable::where('fees_type_id',$id)->count();
            $fees_classes = FeesClass::where('fees_type_id',$id)->count();

            if($fees_choiceables || $fees_classes){
                $response = array(
                    'error' => true,
                    'message' => trans('cannot_delete_beacuse_data_is_associated_with_other_data')
                );
            }else{
                FeesType::findOrFail($id)->delete();
                FeesClass::where('fees_type_id', $id)->delete();
                $response = array(
                    'error' => false,
                    'message' => trans('data_delete_successfully'),
                );
            }
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
            );
        }
        return response()->json($response);
    }
    public function feesClassListIndex()
    {
        $classes = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'sections')->get();
        $fees_type = FeesType::orderBy('id', 'ASC')->pluck('name', 'id');
        $fees_type_data = FeesType::get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();



        return response(view('fees.fees_class', compact('classes', 'fees_type', 'fees_type_data', 'mediums')));
    }
    public function feesClassList()
    {
        $offset = 0;
        $limit = 10;
        $sort = 'id';

        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit']))
            $limit = $_GET['limit'];

        if (isset($_GET['sort']))
            $sort = $_GET['sort'];


        $sql = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('fees_class', 'medium');
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%");
        }
        if (isset($_GET['medium_id']) && !empty($_GET['medium_id'])) {
            $sql = $sql->where('medium_id', $_GET['medium_id']);
        }
        $total = $sql->count();

        $sql->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;

        foreach ($res as $row) {

            $row = (object)$row;
            $operate = '<a href=' . route('class.edit', $row->id) . ' class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $row->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';

            $tempRow['no'] = $no++;
            $tempRow['class_id'] = $row->id;
            $tempRow['class_name'] = $row->name . ' ' . $row->medium->name;
            if (sizeof($row->fees_class)) {
                $total_amount = 0;
                $base_amount = 0;
                $fees_type_table = array();
                foreach ($row->fees_class as $fees_details) {
                    $fees_type_table[] = array(
                        'id' => $fees_details->id,
                        'fees_name' => $fees_details->fees_type->name,
                        'amount' => $fees_details->amount,
                        'fees_type_id' => $fees_details->fees_type->id,
                    );
                    if ($fees_details->fees_type->choiceable == 0) {
                        $base_amount += $fees_details->amount;
                    }
                    $total_amount += $fees_details->amount;
                }
                $tempRow['fees_type'] = isset($fees_type_table) ? $fees_type_table : ' ';
                $tempRow['base_amount'] = $base_amount;
                $tempRow['total_amount'] = $total_amount;
            } else {
                $tempRow['fees_type'] = [];
                $tempRow['base_amount'] = "-";
                $tempRow['total_amount'] = "-";
            }
            $tempRow['created_at'] = $row->created_at;
            $tempRow['updated_at'] = $row->updated_at;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
    public function updateFeesClass(Request $request)
    {
        $validation_rules = array(
            'class_id' => 'required|numeric',
            'edit_fees_type.*.fees_type_id' => 'required',
            'edit_fees_type.*.amount' => 'required:edit_fees_type',
        );
        $validator = Validator::make($request->all(), $validation_rules);

        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            // //Update Fees Type For Class first
            if ($request->edit_fees_type) {
                foreach ($request->edit_fees_type as $row) {
                    $edit_fees_type = FeesClass::findOrFail($row['fees_class_id']);
                    $edit_fees_type->fees_type_id = $row['fees_type_id'];
                    $edit_fees_type->amount = $row['amount'];
                    $edit_fees_type->save();
                }
            }

            //Add New Fees Type For Class
            if ($request->fees_type) {
                $fees_type = array();
                foreach ($request->fees_type as $row) {
                    $fees_type[] = array(
                        'class_id' => $request->class_id,
                        'fees_type_id' => $row['fees_type_id'],
                        'amount' => $row['amount'],
                    );
                }
                FeesClass::insert($fees_type);
            }
            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully'),
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => $e
            );
        }
        return response()->json($response);
    }
    public function removeFeesClass($id)
    {
        try {
            $fees_class = FeesClass::where('id',$id)->first();

            //check wheather the fees class is associated with other table..
            $fees_choiceable = FeesChoiceable::where(['class_id' => $fees_class->class_id , 'fees_type_id' => $fees_class->fees_type_id])->count();
            if($fees_choiceable){
                $response = array(
                    'error' => true,
                    'message' => trans('cannot_delete_beacuse_data_is_associated_with_other_data')
                );
            }else{
                $fees_type_class = FeesClass::findOrFail($id);
                $fees_type_class->delete();
                $response = array(
                    'error' => false,
                    'message' => trans('data_delete_successfully')
                );
            }
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function feesPaidListIndex()
    {
        $classes = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'sections')->get();
        $session_year_all = SessionYear::select('id', 'name', 'default')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        return response(view('fees.fees_paid', compact('classes', 'mediums','session_year_all')));
    }
    public function feesPaidList()
    {

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


        //Fetching Students Data on Basis of Class Section ID with Realtion fees paid
        $sql = Students::with(['user:id,first_name,last_name','fees_paid.class']);
        $session_year = getSettings('session_year');
        $session_year_id = $session_year['session_year'];

        if (isset($_GET['session_year_id']) && !empty($_GET['session_year_id'])) {
            $sql = $sql->whereHas('fees_paid',function($q){
                $q->where('session_year_id',$_GET['session_year_id']);
            });
        }
        if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
            $class_id = $_GET['class_id'];
            $class_section_id = ClassSection::where('class_id', $class_id)->pluck('id');
            $sql = $sql->whereIn('class_section_id', $class_section_id)->with(['fees_paid' => function ($q) use($session_year_id) {
                $q->with('class','session_year', 'payment_transaction')->where('session_year_id',$session_year_id);
            }]);
            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();
        } else {
            $sql = $sql->has('fees_paid');
            $total = $sql->count();
            $sql->orderBy($sort, $order)->skip($offset)->take($limit);
            $res = $sql->get();
        }
        if (isset($_GET['mode']) && $_GET['mode'] == 0 || $_GET['mode'] == 1 || $_GET['mode'] == 2 ) {
            $sql = $sql->whereHas('fees_paid',function($q){
                $q->where('mode',$_GET['mode']);
            });
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                });
        }
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        $no = 1;
        foreach ($res as $row) {
            $class_id_db = ClassSection::where('id', $row->class_section_id)->pluck('class_id')->first();
            if (sizeof($row->fees_paid)) {
                foreach ($row->fees_paid as $fees_paid) {
                    $edit_date = date('m/d/Y', strtotime($fees_paid->date));

                    $choiceable_fees_id = FeesChoiceable::where(['student_id' => $fees_paid->student_id, 'class_id' => $fees_paid->class_id])->pluck('fees_type_id');
                    // get fees type_id
                    if (sizeof($choiceable_fees_id)) {
                        $fees_type_id = FeesType::where('choiceable', 1)->whereNotIn('id', $choiceable_fees_id)->pluck('id');
                        $fees_paid_choiceable = FeesChoiceable::whereIn('fees_type_id', $choiceable_fees_id)->whereHas('fees_type', function ($q) {
                            $q->where('choiceable', 1)->with('fees_class');
                        })->where(['student_id' => $fees_paid->student_id, 'class_id' => $fees_paid->class_id])->with('fees_type')->get();
                    } else {
                        $fees_type_id = FeesType::where('choiceable', 1)->pluck('id');
                    }

                    // fetch the data of fees class on fees_type_id
                    $fees_class = FeesClass::whereIn('fees_type_id', $fees_type_id)->where('class_id', $class_id_db)->with('fees_type')->get();

                    // checks the mode of transaction returns opreates when there is cash and cheque and return null when online
                    if ($fees_paid->mode == 0 || $fees_paid->mode == 1) {
                        $operate = '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $fees_paid->id . ' title="' . trans('edit') . ' ' . trans('fees') . '" data-toggle="modal" data-target="#editFeesPaidModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                        $operate .= '<a href=' . route('fees.paid.clear.data', $fees_paid->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $fees_paid->id . '><i class="fa fa-remove"></i></a>&nbsp;&nbsp;';
                        $operate .= '<a href=' . route('fees.paid.receipt.pdf', $fees_paid->id) . ' class="btn btn-xs btn-gradient-info btn-rounded btn-icon generate-paid-fees-pdf" target="_blank" data-id=' . $fees_paid->id . ' title="' . trans('generate_pdf') . ' ' . trans('fees') . '"><i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
                    } else {
                        $operate = '<a href=' . route('fees.paid.receipt.pdf', $fees_paid->id) . ' class="btn btn-xs btn-gradient-info btn-rounded btn-icon generate-paid-fees-pdf" target="_blank" data-id=' . $fees_paid->id . ' title="' . trans('generate_pdf') . ' ' . trans('fees') . '"><i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
                    }
                    $tempRow['id'] = $fees_paid->id;
                    $tempRow['student_id'] = $row->id;
                    $tempRow['no'] = $no++;
                    $tempRow['father_id'] = $row->father_id;
                    $tempRow['mother_id'] = $row->mother_id;
                    $tempRow['student_name'] = $row->user->first_name . ' ' . $row->user->last_name;
                    $tempRow['class_id'] = $class_id_db;
                    $tempRow['class_name'] = $fees_paid->class->name.' '.$fees_paid->class->medium->name;
                    $tempRow['fees_class_choiceable_data'] = sizeof($fees_class) ? $fees_class : null;
                    $tempRow['fees_class_paid_choiceable_data'] = isset($fees_paid_choiceable) && sizeof($fees_paid_choiceable) ? $fees_paid_choiceable : null;
                    $tempRow['total_fees'] = $fees_paid->total_amount;
                    $tempRow['mode'] = $fees_paid->mode;
                    $tempRow['transaction_payment_id'] = !empty($fees_paid->payment_transaction) ? $fees_paid->payment_transaction->payment_id : null;
                    $tempRow['cheque_no'] = $fees_paid->cheque_no;
                    $tempRow['formatted_date'] = $edit_date;
                    $tempRow['date'] = $fees_paid->date;
                    $tempRow['session_year_name'] = $fees_paid->session_year->name;
                    $tempRow['created_at'] = $row->created_at;
                    $tempRow['updated_at'] = $row->updated_at;
                    $tempRow['operate'] = $operate;
                    $rows[] = $tempRow;
                }
            } else {
                $due_date = getSettings('fees_due_date');
                $due_date = $due_date['fees_due_date'];
                $current_date = Carbon::now()->format('m/d/Y');
                $due_charges = 0;

                // if due charges is applicable
                if ($current_date > $due_date) {
                    $due_charges = getSettings('fees_due_charges');
                    $due_charges = $due_charges['fees_due_charges'];
                }

                $fees_type_id = FeesType::where('choiceable', 1)->pluck('id');
                $fees_class = FeesClass::whereIn('fees_type_id', $fees_type_id)->where('class_id', $class_id_db)->with('fees_type')->get();
                $base_amount = FeesClass::whereNotIn('fees_type_id', $fees_type_id)->where('class_id', $class_id_db)->selectRaw('SUM(amount) as base_amount')->first();
                $base_amount = $base_amount['base_amount'];
                $operate = '<a href="#" class="btn btn-xs btn-gradient-success btn-rounded btn-icon pay-data" data-id=' . $row->id . ' title="' . trans('pay') . ' ' . trans('fees') . '" data-toggle="modal" data-target="#editModal"><i class="fa fa-dollar"></i></a>&nbsp;&nbsp;';

                $tempRow['id'] = null;
                $tempRow['student_id'] = $row->id;
                $tempRow['no'] = $no++;
                $tempRow['father_id'] = $row->father_id;
                $tempRow['mother_id'] = $row->mother_id;
                $tempRow['student_name'] = $row->user->first_name . ' ' . $row->user->last_name;
                $tempRow['class_id'] = $class_id_db;
                $tempRow['class_name'] = $row->class_section->class->name . ' ' . $row->class_section->class->medium->name;
                $tempRow['fees_class_data'] = sizeof($fees_class) ? $fees_class : null;
                $tempRow['base_amount'] = $base_amount;
                $tempRow['due_charges'] = $due_charges;
                $tempRow['total_fees'] = null;
                $tempRow['mode'] = null;
                $tempRow['transaction_payment_id'] = null;
                $tempRow['cheque_no'] = null;
                $tempRow['current_date'] = $current_date;
                $tempRow['date'] = null;
                $tempRow['session_year_name'] = null;
                $tempRow['created_at'] = $row->created_at;
                $tempRow['updated_at'] = $row->updated_at;
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
    public function feesPaidStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'mode' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {
            $date = date('Y-m-d', strtotime($request->date));
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_fees = FeesClass::where('class_id',$request->class_id)->selectRaw('SUM(amount) as total_amount')->groupby('class_id')->first();
            $due_date = getSettings('fees_due_date');
            $due_date = $due_date['fees_due_date'];
            $current_date = Carbon::now()->format('m/d/Y');
            $due_charges = 0;
            if ($current_date > $due_date) {
                $due_charges = getSettings('fees_due_charges');
                $due_charges = $due_charges['fees_due_charges'];
                $class_fees = $class_fees['total_amount'] + $due_charges;
            }else{
                $class_fees = $class_fees['total_amount'];
            }
            //add data to fees paid
            $fees_paid = new FeesPaid();
            $fees_paid->student_id = $request->student_id;
            $fees_paid->class_id = $request->class_id;
            if ($request->mode) {
                $fees_paid->mode = $request->mode;
                $fees_paid->cheque_no = $request->cheque_no;
            } else {
                $fees_paid->mode = $request->mode;
            }
            $fees_paid->total_amount = $request->total_amount ?? $class_fees;
            $fees_paid->date = $date;
            $fees_paid->session_year_id = $session_year_id;
            $fees_paid->save();

            // add compulsory fees in fees choiced table
            $compulsory_fees = FeesClass::where('class_id', $request->class_id)->whereHas('fees_type', function ($q) {
                $q->where('choiceable', 0);
            })->get();
            foreach ($compulsory_fees as $fees) {
                $fees_choiceable = new FeesChoiceable();
                $fees_choiceable->student_id = $request->student_id;
                $fees_choiceable->class_id = $request->class_id;
                $fees_choiceable->fees_type_id = $fees->fees_type_id;
                $fees_choiceable->is_due_charges = 0;
                $fees_choiceable->total_amount = $fees->amount;
                $fees_choiceable->session_year_id = $session_year_id;
                $fees_choiceable->save();
            }

            // add choiceable fees in fees choiced table
            if (isset($request->choiceable_fees)) {
                foreach ($request->choiceable_fees as $fees_type_id) {
                    $amount = FeesClass::where(['fees_type_id' => $fees_type_id, 'class_id' => $request->class_id])->pluck('amount')->first();
                    $fees_choiceable = new FeesChoiceable();
                    $fees_choiceable->student_id = $request->student_id;
                    $fees_choiceable->class_id = $request->class_id;
                    $fees_choiceable->fees_type_id = $fees_type_id;
                    $fees_choiceable->is_due_charges = 0;
                    $fees_choiceable->total_amount = $amount;
                    $fees_choiceable->session_year_id = $session_year_id;
                    $fees_choiceable->save();
                }
            }

            // if due charges applicable then add entery in fees choiced table
            if ($request->due_charges != null) {
                $fees_choiceable = new FeesChoiceable();
                $fees_choiceable->student_id = $request->student_id;
                $fees_choiceable->class_id = $request->class_id;
                $fees_choiceable->fees_type_id = null;
                $fees_choiceable->is_due_charges = 1;
                $fees_choiceable->total_amount = $request->due_charges;
                $fees_choiceable->session_year_id = $session_year_id;
                $fees_choiceable->save();
            }
            $response = array(
                'error' => false,
                'message' => trans('data_store_successfully')
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function feesPaidUpdate(Request $request)
    {
        $fees_paid_db = FeesPaid::find($request->edit_id);
        //get session_year_id of particular fees paid .
        $session_year_id = $fees_paid_db->session_year_id;

        $date = date('Y-m-d', strtotime($request->edit_date));
        $fees_paid_db->date = $date;
        $fees_paid_db->total_amount = $request->edit_total_amount;
        if ($request->edit_mode) {
            $fees_paid_db->mode = $request->edit_mode;
            $fees_paid_db->cheque_no = $request->edit_cheque_no;
        } else {
            $fees_paid_db->mode = $request->edit_mode;
        }
        $fees_paid_db->save();
        if (isset($request->add_new_choiceable_fees)) {
            foreach ($request->add_new_choiceable_fees as $fees_type_id) {
                $amount = FeesClass::where(['fees_type_id' => $fees_type_id, 'class_id' => $request->edit_class_id])->pluck('amount')->first();
                $fees_choiceable = new FeesChoiceable();
                $fees_choiceable->student_id = $request->edit_student_id;
                $fees_choiceable->class_id = $request->edit_class_id;
                $fees_choiceable->fees_type_id = $fees_type_id;
                $fees_choiceable->is_due_charge = 0;
                $fees_choiceable->total_amount = $amount;
                $fees_choiceable->session_year_id = $session_year_id;
                $fees_choiceable->save();
            }
        }

        $response = array(
            'error' => false,
            'message' => trans('data_update_successfully')
        );
        return response()->json($response);
    }
    public function feesPaidRemoveChoiceableFees($id)
    {
        try {
            $fees_choiceable = FeesChoiceable::find($id);
            $student_id = $fees_choiceable->student_id;
            $class_id = $fees_choiceable->class_id;
            $session_year_id = $fees_choiceable->session_year_id;

            //get the amount of particular fees choiced
            $fees_choiceable_amount = $fees_choiceable->total_amount;
            $fees_choiceable->delete();

            $fees_paid_id = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->pluck('id')->first();
            $fees_paid_amount = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->pluck('total_amount')->first();
            $updated_fees = $fees_paid_amount  - $fees_choiceable_amount;
            $fees_paid_update = FeesPaid::find($fees_paid_id);
            $fees_paid_update->total_amount = $updated_fees;
            $fees_paid_update->save();
            $response = array(
                'error' => false,
                'message' => trans('data_delete_successfully')
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    public function clearFeesPaidData($id)
    {
        try {
            $fees_paid_data = FeesPaid::find($id);

            // get the ids from fees paid to remove the fees choiced data
            $student_id = $fees_paid_data->student_id;
            $class_id = $fees_paid_data->class_id;
            $session_year_id = $fees_paid_data->session_year_id;

            $fees_paid_data->delete();

            FeesChoiceable::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->delete();

            $response = array(
                'error' => false,
                'message' => trans('data_delete_successfully')
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }
    public function feesConfigIndex()
    {
        $settings = getSettings();
        $domain =  request()->getSchemeAndHttpHost();
        return view('fees.fees_config', compact('settings', 'domain'));
    }

    public function feesConfigUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razorpay_status' => 'required',
            'razorpay_secret_key' => 'required_if:razorpay_status,1|nullable',
            'razorpay_api_key' => 'required_if:razorpay_status,1|nullable',
            'razorpay_webhook_secret' => 'required_if:razorpay_status,1|nullable',
            'razorpay_webhook_url' => 'required_if:razorpay_status,1|nullable',
            'stripe_status' => 'required',
            'stripe_publishable_key' => 'required_if:stripe_status,1|nullable',
            'stripe_secret_key' => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_secret' => 'required_if:stripe_status,1|nullable',
            'stripe_webhook_url' => 'required_if:stripe_status,1|nullable',
            'fees_due_date' => 'required|date',
            'fees_due_charges' => 'required|numeric',
            'currency_code' => 'required',
            'currency_symbol' => 'required',
        ]);
        if ($validator->fails()) {
            $response = array(
                'error' => true,
                'message' => $validator->errors()->first()
            );
            return response()->json($response);
        }
        try {

            //razorpay_status
            if (Settings::where('type', 'razorpay_status')->exists()) {
                $data = [
                    'message' => $request->razorpay_status
                ];
                Settings::where('type', 'razorpay_status')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'razorpay_status';
                $setting->message = $request->razorpay_status;
                $setting->save();
            }

            if ($request->razorpay_status) {
                //razorpay_secret_key
                if (Settings::where('type', 'razorpay_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_secret_key)
                    ];
                    Settings::where('type', 'razorpay_secret_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'razorpay_secret_key';
                    $setting->message = trim($request->razorpay_secret_key);
                    $setting->save();
                }

                //razorpay_api_key
                if (Settings::where('type', 'razorpay_api_key')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_api_key)
                    ];
                    Settings::where('type', 'razorpay_api_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'razorpay_api_key';
                    $setting->message = trim($request->razorpay_api_key);
                    $setting->save();
                }

                //razorpay_webhook_secret
                if (Settings::where('type', 'razorpay_webhook_secret')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_webhook_secret)
                    ];
                    Settings::where('type', 'razorpay_webhook_secret')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'razorpay_webhook_secret';
                    $setting->message = trim($request->razorpay_webhook_secret);
                    $setting->save();
                }

                //razorpay_webhook_url
                if (Settings::where('type', 'razorpay_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->razorpay_webhook_url)
                    ];
                    Settings::where('type', 'razorpay_webhook_url')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'razorpay_webhook_url';
                    $setting->message = trim($request->razorpay_webhook_url);
                    $setting->save();
                }

                $env_update = changeEnv([
                    'RAZORPAY_SECRET_KEY' => trim($request->razorpay_secret_key),
                    'RAZORPAY_API_KEY' => trim($request->razorpay_api_key),
                    'RAZORPAY_WEBHOOK_SECRET' => trim($request->razorpay_webhook_secret),
                    'RAZORPAY_WEBHOOK_URL' => trim($request->razorpay_webhook_url),
                ]);

                if ($env_update) {
                    $response = array(
                        'error' => false,
                        'message' => trans('data_update_successfully'),
                    );
                }
            }


            //stripe_status
            if (Settings::where('type', 'stripe_status')->exists()) {
                $data = [
                    'message' => $request->stripe_status
                ];
                Settings::where('type', 'stripe_status')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'stripe_status';
                $setting->message = $request->stripe_status;
                $setting->save();
            }

            if ($request->stripe_status) {

                //stripe_publishable_key
                if (Settings::where('type', 'stripe_publishable_key')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_publishable_key)
                    ];
                    Settings::where('type', 'stripe_publishable_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'stripe_publishable_key';
                    $setting->message = trim($request->stripe_publishable_key);
                    $setting->save();
                }

                //stripe_secret_key
                if (Settings::where('type', 'stripe_secret_key')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_secret_key)
                    ];
                    Settings::where('type', 'stripe_secret_key')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'stripe_secret_key';
                    $setting->message = trim($request->stripe_secret_key);
                    $setting->save();
                }

                //stripe_webhook_secret
                if (Settings::where('type', 'stripe_webhook_secret')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_webhook_secret)
                    ];
                    Settings::where('type', 'stripe_webhook_secret')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'stripe_webhook_secret';
                    $setting->message = trim($request->stripe_webhook_secret);
                    $setting->save();
                }

                //stripe_webhook_url
                if (Settings::where('type', 'stripe_webhook_url')->exists()) {
                    $data = [
                        'message' => trim($request->stripe_webhook_url)
                    ];
                    Settings::where('type', 'stripe_webhook_url')->update($data);
                } else {
                    $setting = new Settings();
                    $setting->type = 'stripe_webhook_url';
                    $setting->message = trim($request->stripe_webhook_url);
                    $setting->save();
                }

                $env_update = changeEnv([
                    'STRIPE_PUBLISHABLE_KEY' => trim($request->stripe_publishable_key),
                    'STRIPE_SECRET_KEY' => trim($request->stripe_secret_key),
                    'STRIPE_WEBHOOK_SECRET' => trim($request->stripe_webhook_secret),
                    'STRIPE_WEBHOOK_URL' => trim($request->stripe_webhook_url),
                ]);

                if ($env_update) {
                    $response = array(
                        'error' => false,
                        'message' => trans('data_update_successfully'),
                    );
                }
            }

            //fees_due_date
            if (Settings::where('type', 'fees_due_date')->exists()) {
                $data = [
                    'message' => $request->fees_due_date
                ];
                Settings::where('type', 'fees_due_date')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'fees_due_date';
                $setting->message = $request->fees_due_date;
                $setting->save();
            }

            //fees_due_charges
            if (Settings::where('type', 'fees_due_charges')->exists()) {
                $data = [
                    'message' => $request->fees_due_charges
                ];
                Settings::where('type', 'fees_due_charges')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'fees_due_charges';
                $setting->message = $request->fees_due_charges;
                $setting->save();
            }

            //currency_code
            if (Settings::where('type', 'currency_code')->exists()) {
                $data = [
                    'message' => $request->currency_code
                ];
                Settings::where('type', 'currency_code')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'currency_code';
                $setting->message = $request->currency_code;
                $setting->save();
            }

            //currency_symbol
            if (Settings::where('type', 'currency_symbol')->exists()) {
                $data = [
                    'message' => $request->currency_symbol
                ];
                Settings::where('type', 'currency_symbol')->update($data);
            } else {
                $setting = new Settings();
                $setting->type = 'currency_symbol';
                $setting->message = $request->currency_symbol;
                $setting->save();
            }

            $response = array(
                'error' => false,
                'message' => trans('data_update_successfully'),
            );
        } catch (\Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred')
            );
        }
        return response()->json($response);
    }

    public function feesTransactionsLogsIndex(Request $request)
    {
        $session_year_all = SessionYear::select('id', 'name', 'default')->get();
        $classes = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'sections')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        return response(view('fees.fees_transaction_logs', compact('classes', 'mediums', 'session_year_all')));
    }
    public function feesTransactionsLogsList(Request $request)
    {
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
        //Fetching Students Data on Basis of Class Section ID with Realtion fees paid
        $sql = PaymentTransaction::with('student', 'session_year');

        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('order_id', 'LIKE', "%$search%")
                ->orwhere('payment_id', 'LIKE', "%$search%")
                ->orWhereHas('student.user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                });
        }
        if (isset($_GET['session_year_id']) && !empty($_GET['session_year_id'])) {
            $sql = $sql->where('session_year_id', $_GET['session_year_id']);
        }
        if (isset($_GET['class_id']) && !empty($_GET['class_id'])) {
            $class_id = $_GET['class_id'];
            $sql = $sql->where('class_id', $class_id);
        }
        if (isset($_GET['payment_status']) && $_GET['payment_status'] == 0 || $_GET['payment_status'] == 1 || $_GET['payment_status'] == 2 ) {
            $sql->where('payment_status',$_GET['payment_status']);
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
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['student_id'] = $row->student_id;
            $tempRow['student_name'] = $row->student->user->first_name . ' ' . $row->student->user->last_name;
            $tempRow['total_fees'] = $row->total_amount;
            $tempRow['payment_gateway'] = $row->payment_gateway;
            $tempRow['payment_status'] = $row->payment_status;
            $tempRow['order_id'] = $row->order_id;
            $tempRow['payment_id'] = $row->payment_id;
            $tempRow['payment_signature'] = $row->payment_signature;
            $tempRow['session_year_id'] = $row->session_year_id;
            $tempRow['session_year_name'] = $row->session_year->name;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function feesPaidReceiptPDF($id)
    {
        try {
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


            $fees_paid = FeesPaid::where('id', $id)->with('student.user:id,first_name,last_name', 'class', 'session_year')->get()->first();
            $student_id = $fees_paid->student_id;
            $class_id = $fees_paid->class_id;
            $session_year_id = $fees_paid->session_year_id;
            $fees_choiceable_db = FeesChoiceable::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->with('fees_type')->orderby('id', 'asc')->get();
            $pdf = Pdf::loadView('fees.fees_receipt', compact('logo', 'school_name', 'fees_paid', 'fees_choiceable_db', 'currency_symbol', 'school_address'));
            return $pdf->stream('fees-receipt.pdf');
        } catch (Throwable $e) {
            $response = array(
                'error' => true,
                'message' => trans('error_occurred'),
            );
            return response()->json($response);
        }
    }
}
