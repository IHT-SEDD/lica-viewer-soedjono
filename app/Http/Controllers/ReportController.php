<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DataTables;
use DB;
use Illuminate\Support\Carbon;
use Auth;
use PDF;
use App\FinishTransaction;
use App\FinishTransactionTest;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TestDetailExport;
use App\Exports\RoomReport;
use App\Exports\TATExport;
use App\Exports\DuploExport;
use App\Exports\BillingExport;
use App\Exports\ExportHistoryPatient;
use App\Exports\SupportActivitiesExport;
use App\Exports\CustomTestExport;
use App\Exports\TATTestExport;
use App\Exports\analyzerReport;

class ReportController extends Controller
{
    const STATUS = 2;

    public function selectOptionsType()
    {
        try {
            $data = array("rawat_inap", "rawat_jalan", "igd");

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function selectOptionsInsurance(Request $request)
    {
        try {
            $search = $request->input('query');
            // dd($search);
    
            $query = DB::table('insurances')->selectRaw('insurances.id as insurance_id, insurances.name as insurance_name')
            ->where('insurances.name', 'LIKE', '%' . $search . '%'); 
            $data = $query->get();
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function selectOptionsPatient(Request $request)
    {
        try {
            $query = DB::table('patients')->selectRaw('patients.id as patient_id, patients.name as patient_name ,  patients.medrec as patient_medrec');
            $query = $query->where('name', 'LIKE', '%' . $request->input('query') . '%');
           
            $data = $query->get();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function selectOptionsDoctor()
    {
        try {
            $query = DB::table('doctors')->selectRaw('doctors.id as doctor_id, doctors.name as doctor_name');
            $data = $query->get();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function selectOptionsTest(Request $request)
    {
        try {
            $query = DB::table('tests')->selectRaw('tests.id as test_id, tests.name as test_name');
            $query = $query->where('name', 'LIKE', '%' . $request->input('query') . '%');
            $data = $query->get();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function selectOptionsRoom(Request $request)
    {
        try {
            $query = DB::table('rooms')->selectRaw('rooms.id as room_id, rooms.room as room_name');
            $query = $query->where('room', 'LIKE', '%' . $request->input('query') . '%');
            $data = $query->get();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    //===============================================================================================
    // CRITICAL REPORT
    //===============================================================================================
    public function criticalReport()
    {
        $data['title'] = 'Critical Report';
        return view('dashboard.report.report_critical.index', $data);
    }

    public function criticalDatatable($startDate = null, $endDate = null, $group_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = \App\FinishTransactionTest::selectRaw('finish_transaction_tests.*, finish_transactions.no_lab, finish_transactions.patient_name, finish_transactions.patient_medrec, finish_transactions.patient_birthdate, finish_transactions.room_name');

        $model->leftJoin('finish_transactions', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id');
        if ($group_id != null && $group_id != "null" && $group_id != 0) {
            $model->where('finish_transaction_tests.group_id', '=', $group_id);
        }
        $model->where('finish_transaction_tests.input_time', '>=', $from);
        $model->where('finish_transaction_tests.input_time', '<=', $to);
        $model->where('finish_transaction_tests.report_status', '=', 1);
        $model->orderBy('finish_transaction_tests.input_time', 'desc');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function criticalPrint($startDate = null, $endDate = null, $group_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transaction_tests')->select('finish_transaction_tests.*', 'finish_transactions.no_lab', 'finish_transactions.patient_name', 'finish_transactions.patient_medrec', 'finish_transactions.patient_birthdate', 'finish_transactions.room_name');
        $query->leftJoin('finish_transactions', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id');
        if ($group_id != null && $group_id != "null" && $group_id != 0) {
            $query->where('finish_transaction_tests.group_id', '=', $group_id);
        }
        $query->where('finish_transaction_tests.input_time', '>=', $from);
        $query->where('finish_transaction_tests.input_time', '<=', $to);
        $query->where('finish_transaction_tests.report_status', '=', 1);
        $query->orderBy('finish_transaction_tests.input_time', 'desc');

        $data["criticalData"] = $query->get();

        if ($group_id != null && $group_id != "null" && $group_id != 0) {
            $query_group = DB::table('groups')->select('groups.*')->where('id', $group_id);
            $group = $query_group->first();
            $data["group"] = $group->name;
        } else {
            $data["group"] = '-';
        }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_critical.print', $data);
    }

    //===============================================================================================
    // DUPLO REPORT
    //===============================================================================================
    public function duploReport()
    {
        $data['title'] = 'Duplo Report';
        return view('dashboard.report.report_duplo.index', $data);
    }

    public function duploDatatable($startDate = null, $endDate = null, $group_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->startOfDay()->toDateTimeString();
            $to = Carbon::today()->endOfDay()->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
            $to = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
        }

        // 🔹 Query sama seperti Excel (tanpa perubahan logika)
        $query = DB::table('finish_transaction_tests as ftt')
            ->leftJoin('finish_transactions as ft', 'ftt.finish_transaction_id', '=', 'ft.id')
            ->select(
                'ftt.id as id',
                'ftt.finish_transaction_id',
                'ftt.group_id',
                'ftt.group_name',
                'ftt.input_time',
                'ftt.test_name',
                'ft.no_lab',
                'ft.patient_name',
                'ft.patient_medrec',
                'ft.patient_birthdate',
                'ft.patient_gender',
                DB::raw("
                    MAX(CASE WHEN ftt.mark_duplo = 1 THEN ftt.result_number END) as result,
                    MAX(CASE WHEN ftt.mark_duplo = 2 THEN ftt.result_number END) as result_duplo,
                    MAX(CASE WHEN ftt.mark_duplo = 1 THEN ftt.analyzer_name END) as analyzer,
                    MAX(CASE WHEN ftt.mark_duplo = 2 THEN ftt.analyzer_name END) as analyzer_duplo
                ")
            )
            ->whereBetween('ftt.input_time', [$from, $to])
            ->where('ftt.mark_duplo', '!=', 0)
            ->when($group_id && $group_id != "null" && $group_id != 0, function ($q) use ($group_id) {
                $q->where('ftt.group_id', $group_id);
            })
            ->groupBy(
                'ftt.finish_transaction_id',
                'ftt.group_id',
                'ftt.group_name',
                'ftt.input_time',
                'ftt.test_name',
                'ft.no_lab',
                'ft.patient_name',
                'ft.patient_medrec',
                'ft.patient_birthdate',
                'ft.patient_gender'
            )
            ->orderByDesc('ftt.input_time');

        return DataTables::of($query)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function duploPrint($startDate = null, $endDate = null, $group_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = DB::table('finish_transaction_tests')
        ->select(
            'finish_transaction_tests.id',
            'finish_transaction_tests.finish_transaction_id',
            'finish_transaction_tests.group_id',
            'finish_transaction_tests.input_time',
            'finish_transaction_tests.test_name as test_name',
            'finish_transactions.no_lab',
            'finish_transactions.patient_name',
            'finish_transactions.patient_medrec',
            'finish_transactions.patient_birthdate',
            'finish_transactions.patient_gender',
            DB::raw('
                MAX(CASE WHEN finish_transaction_tests.mark_duplo = 1 THEN result_number END) as result,
                MAX(CASE WHEN finish_transaction_tests.mark_duplo = 2 THEN result_number END) as result_duplo,
                MAX(CASE WHEN finish_transaction_tests.mark_duplo = 1 THEN finish_transaction_tests.analyzer_name END) as analyzer,
                MAX(CASE WHEN finish_transaction_tests.mark_duplo = 2 THEN finish_transaction_tests.analyzer_name END) as analyzer_duplo
            ')
        );
        $model->leftJoin('finish_transactions', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id');
        if ($group_id != null && $group_id != "null" && $group_id != 0) {
            $model->where('finish_transaction_tests.group_id', '=', $group_id);
        }
        $model->where('finish_transaction_tests.input_time', '>=', $from);
        $model->where('finish_transaction_tests.input_time', '<=', $to);
        $model->where('finish_transaction_tests.mark_duplo', '!=', 0);
        $model->orderBy('finish_transaction_tests.input_time', 'desc');
        $model->groupBy('finish_transaction_tests.finish_transaction_id');

        $data["duploData"] = $model->get();

        if ($group_id != null && $group_id != "null" && $group_id != 0) {
            $query_group = DB::table('groups')->select('groups.*')->where('id', $group_id);
            $group = $query_group->first();
            $data["group"] = $group->name;
        } else {
            $data["group"] = '-';
        }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = Carbon::today()->toDateTimeString();
            $data["endDate"] = Carbon::today()->toDateTimeString();
        }

        return view('dashboard.report.report_duplo.print', $data);
    }

    public function duploExcel($startDate = null, $endDate = null, $group_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->startOfDay()->toDateTimeString();
            $to = Carbon::today()->endOfDay()->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
            $to = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
        }

        // 🔹 Query utama — sama dengan datatable
        $model = DB::table('finish_transaction_tests as ftt')
            ->leftJoin('finish_transactions as ft', 'ftt.finish_transaction_id', '=', 'ft.id')
            ->select(
                'ftt.finish_transaction_id',
                'ftt.group_id',
                'ftt.group_name',
                'ftt.input_time',
                'ftt.test_name',
                'ft.no_lab',
                'ft.patient_name',
                'ft.patient_medrec',
                'ft.patient_birthdate',
                'ft.patient_gender',
                DB::raw("
                    MAX(CASE WHEN ftt.mark_duplo = 1 THEN ftt.result_number END) as result,
                    MAX(CASE WHEN ftt.mark_duplo = 2 THEN ftt.result_number END) as result_duplo,
                    MAX(CASE WHEN ftt.mark_duplo = 1 THEN ftt.analyzer_name END) as analyzer,
                    MAX(CASE WHEN ftt.mark_duplo = 2 THEN ftt.analyzer_name END) as analyzer_duplo
                ")
            )
            ->whereBetween('ftt.input_time', [$from, $to])
            ->where('ftt.mark_duplo', '!=', 0)
            ->when($group_id && $group_id != "null" && $group_id != 0, function ($q) use ($group_id) {
                $q->where('ftt.group_id', $group_id);
            })
            ->groupBy(
                'ftt.finish_transaction_id',
                'ftt.group_id',
                'ftt.group_name',
                'ftt.input_time',
                'ftt.test_name',
                'ft.no_lab',
                'ft.patient_name',
                'ft.patient_medrec',
                'ft.patient_birthdate',
                'ft.patient_gender'
            )
            ->orderByDesc('ftt.input_time');

        $data["duploData"] = $model->get();

        // 🔹 Hitung summary cepat
        $summary = DB::table('finish_transaction_tests')
            ->selectRaw("
                COUNT(*) as total_test,
                SUM(CASE WHEN mark_duplo != 0 THEN 1 ELSE 0 END) as total_duplo
            ")
            ->whereBetween('input_time', [$from, $to])
            ->when($group_id && $group_id != 'null' && $group_id != 0, function ($q) use ($group_id) {
                $q->where('group_id', $group_id);
            })
            ->first();

        $total_test = $summary->total_test ?? 0;
        $total_duplo = $summary->total_duplo ?? 0;
        $duplo_percentage = $total_test > 0 ? round(($total_duplo / $total_test) * 100, 2) . '%' : '0%';

        $data["total_test"] = $total_test;
        $data["duplo_percentage"] = $duplo_percentage;
        $data["startDate"] = $startDate ? date('d/m/Y', strtotime($startDate)) : '-';
        $data["endDate"] = $endDate ? date('d/m/Y', strtotime($endDate)) : '-';

        $fileName = 'Duplo Report.xlsx';
        return Excel::download(new DuploExport($data), $fileName);
    }

    //===============================================================================================
    // GROUP TEST REPORT
    //===============================================================================================
    public function groupTestReport()
    {
        $data['title'] = 'Group Test Report';
        return view('dashboard.report.report_group_test.index', $data);
    }

    public function groupTestDatatable($startDate = null, $endDate = null, $group_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model  = DB::table('finish_transaction_tests')
        ->selectRaw('finish_transaction_tests.finish_transaction_id, finish_transaction_tests.group_name, finish_transaction_tests.group_id, COUNT(finish_transaction_tests.group_id) as total_test, finish_transactions.id , finish_transactions.created_time')
        ->leftJoin('finish_transactions', 'finish_transactions.id', 'finish_transaction_tests.finish_transaction_id')
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to);
        if ($group_id != null && $group_id != "null" && $group_id != 0) {
            $model->where('group_id', $group_id);
        }
        $model->groupBy('finish_transaction_tests.finish_transaction_id','group_id');
       $query =  $model->get();

    //    echo '<pre>';
    //    print_r($query );
    //    die;
       foreach ($query as $key => $value) {
        $query[$key]->group_id = $value->group_id;
        $query[$key]->group_name = $value->group_name;
        $query[$key]->total_test = 1;
       }


       $data_group = [];

       foreach($query as $key => $value){
        $isDuplicate = false ;

        foreach ($data_group as $uniqueKey => $uniqueValue) {
            
            if($value->group_id === $uniqueValue->group_id){
                $isDuplicate = true ;
            $data_group[$uniqueKey]->total_test += $value->total_test;
            break;
            }
        }

        if(!$isDuplicate){
            $data_group[] = $value;
            
        }

       }
    

       usort($data_group, function($a, $b){
        return $a->group_id - $b->group_id;
       });


  

        return DataTables::of($data_group)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);

       
    }

    public function groupTestPrint($startDate = null, $endDate = null, $group_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model  = DB::table('finish_transaction_tests')
        ->selectRaw('finish_transaction_tests.finish_transaction_id, finish_transaction_tests.group_name, finish_transaction_tests.group_id, COUNT(finish_transaction_tests.group_id) as total_test, finish_transactions.id , finish_transactions.created_time')
        ->leftJoin('finish_transactions', 'finish_transactions.id', 'finish_transaction_tests.finish_transaction_id')
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to);
        if ($group_id != null && $group_id != "null" && $group_id != 0) {
            $model->where('group_id', $group_id);
        }
        $model->groupBy('finish_transaction_tests.finish_transaction_id','group_id');
       $query =  $model->get();

    //    echo '<pre>';
    //    print_r($query );
    //    die;
       foreach ($query as $key => $value) {
        $query[$key]->group_id = $value->group_id;
        $query[$key]->group_name = $value->group_name;
        $query[$key]->total_test = 1;
       }


       $data_group = [];

       foreach($query as $key => $value){
        $isDuplicate = false ;

        foreach ($data_group as $uniqueKey => $uniqueValue) {
            
            if($value->group_id === $uniqueValue->group_id){
                $isDuplicate = true ;
            $data_group[$uniqueKey]->total_test += $value->total_test;
            break;
            }
        }

        if(!$isDuplicate){
            $data_group[] = $value;
            
        }

       }
    

       usort($data_group, function($a, $b){
        return $a->group_id - $b->group_id;
       });
        $data["groupData"] = $data_group;

        if ($group_id != null && $group_id != "null" && $group_id != 0) {
            $query_group = DB::table('groups')->select('name as group_name');
            $group = $query_group->first();
            $data["group"] = $group->group_name;
        } else {
            $data["group"] = '-';
        }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        // $query_transactions = DB::table('finish_transactions')->selectRaw('finish_transactions.*, finish_transaction_tests.group_name');
        // $query_transactions->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id');
        // $query_transactions->where('finish_transactions.created_time', '>=', $from);
        // $query_transactions->where('finish_transactions.created_time', '<=', $to);
        // if ($group_id != null && $group_id != "null" && $group_id != 0) {
        //     $query_transactions->where('finish_transaction_tests.group_id', $group_id);
        // }
        // $query_transactions->orderBy('finish_transactions.created_time', 'desc');
        // $query_transactions->groupBy('finish_transaction_tests.group_id');


        return view('dashboard.report.report_group_test.print', $data);
    }

     //===============================================================================================
    // TAT Test (TURNAROUND TIME) REPORT
    //===============================================================================================
    public function TATTestReport()
    {
        $data['title'] = 'Turnaround Time Test Report';
        return view('dashboard.report.report_tat_test.index', $data);
    }

    public function TATTestDatatable($startDate = null, $endDate = null, $groupId = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $data = DB::table('finish_transactions AS ft')
        ->join('finish_transaction_tests AS ftt', 'ft.id', '=', 'ftt.finish_transaction_id')
        ->whereBetween('ft.created_time', [$from, $to])
        ->orderBy('ft.created_time', 'asc')
        ->select([
            'ft.id',
            'ft.no_lab',
            'ft.created_time',
            'ft.checkin_time',
            'ft.analytic_time',
            'ft.post_time',
            'ftt.test_name',
            'ftt.draw_time',
            'ftt.validate_time',
            'ftt.verify_time',
        ]);
        if($groupId != null && $groupId != 'null'){
            $data->where('ftt.group_id', $groupId);
        }
        $model =  $data->groupBy('ft.id', 'ftt.test_id')->get();
    
        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function TATTestExcel($startDate = null, $endDate = null, $groupId = null)
    {

        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
        // dd($groupId);
        $data['group'] = '-';

        $query = DB::table('finish_transactions AS ft')
        ->join('finish_transaction_tests AS ftt', 'ft.id', '=', 'ftt.finish_transaction_id')
        ->whereBetween('ft.created_time', [$from, $to])
        ->orderBy('ft.created_time', 'asc')
        ->select([
            'ft.id',
            'ft.no_lab',
            'ft.created_time',
            'ft.checkin_time',
            'ft.analytic_time',
            'ft.post_time',
            'ftt.test_name',
            'ftt.draw_time',
            'ftt.validate_time',
            'ftt.verify_time',
        ]);
        if($groupId != null && $groupId != 'null'){
            $query->where('ftt.group_id', $groupId);
            $data['group'] = \App\Group::find($groupId)->name;
        }
        $model =  $query->groupBy('ft.id', 'ftt.test_id')->get();

        $data['transactions'] = $model;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }
       
        $fileName = 'TAT Per Test.xlsx'; // Nama file Excel yang akan diunduh
    
        return Excel::download(new TATTestExport($data), $fileName);

    }

    //===============================================================================================
    // TAT (TURNAROUND TIME) REPORT
    //===============================================================================================
    public function TATReport()
    {
        $data['title'] = 'Turnaround Time Report';
        return view('dashboard.report.report_tat.index', $data);
    }

    public function TATDatatable($startDate = null, $endDate = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = DB::table('finish_transactions')->selectRaw('finish_transactions.*, finish_transaction_tests.group_name, finish_transaction_tests.draw_time, finish_transaction_tests.validate_time, finish_transaction_tests.verify_time');
        $model->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id');
        // $model->leftJoin('log_print', 'finish_transactions.id', '=', 'log_print.finish_transaction_id');
        $model->where('finish_transactions.created_time', '>=', $from);
        $model->where('finish_transactions.created_time', '<=', $to);
        $model->orderBy('finish_transactions.created_time', 'asc');
        $model->groupBy('finish_transactions.id');
        $data = $model->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function TATPrint($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')->selectRaw('finish_transactions.*, finish_transaction_tests.group_name, finish_transaction_tests.draw_time, finish_transaction_tests.validate_time, finish_transaction_tests.verify_time');
        $query->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id');
        // $query->leftJoin('log_print', 'finish_transactions.id', '=', 'log_print.finish_transaction_id');
        $query->where('finish_transactions.created_time', '>=', $from);
        $query->where('finish_transactions.created_time', '<=', $to);
        $query->orderBy('finish_transactions.created_time', 'asc');
        $query->groupBy('finish_transactions.id');

        $data["tatData"] = $query->get();

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        // echo '<pre>';
        // print_r($data);
        // die;

        return view('dashboard.report.report_tat.print', $data);
    }
    public function TATExcel($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')->selectRaw('finish_transactions.*, finish_transaction_tests.group_name, finish_transaction_tests.draw_time, finish_transaction_tests.validate_time, finish_transaction_tests.verify_time');
        $query->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id');
        // $query->leftJoin('log_print', 'finish_transactions.id', '=', 'log_print.finish_transaction_id');
        $query->where('finish_transactions.created_time', '>=', $from);
        $query->where('finish_transactions.created_time', '<=', $to);
        $query->orderBy('finish_transactions.created_time', 'asc');
        $query->groupBy('finish_transactions.id');

        $data["tatData"] = $query->get();

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        $fileName = 'TAT.xlsx'; // Nama file Excel yang akan diunduh
    
        return Excel::download(new TATExport($data), $fileName);
    }

    public function TATGroupPrint($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query_group = DB::table('finish_transactions')->selectRaw('finish_transactions.created_time, finish_transaction_tests.group_id, finish_transaction_tests.group_name')
            ->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->orderBy('finish_transaction_tests.group_id', 'asc')
            ->groupBy('finish_transaction_tests.group_id');
        $groupData = $query_group->get();

        $query_tat = DB::table('finish_transactions')->selectRaw('finish_transactions.*, finish_transaction_tests.group_id, finish_transaction_tests.group_name, finish_transaction_tests.draw_time, finish_transaction_tests.validate_time')
            ->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transactions.id')
            ->groupBy('finish_transaction_tests.group_id');
        $tatData = $query_tat->get();

        $data["groupData"] = $groupData;
        $data["tatData"] = $tatData; 
        
        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_tat.print-group', $data);
    }

    //===============================================================================================
    // TAT TARGET REPORT
    //===============================================================================================
    public function TATTargetReport()
    {
        $data['title'] = 'TAT Target Report';
        return view('dashboard.report.report_tat_target.index', $data);
    }

    public function TATTargetDatatable($startDate = null, $endDate = null)
    {

        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $transaksiData = DB::table('finish_transaction_tests')
        ->join('finish_transactions', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
        ->whereBetween('finish_transaction_tests.input_time', [$from, $to])
        ->where('finish_transactions.type', '!=', 'igd')
        ->where('finish_transactions.room_name', '!=', 'RUANG ICU')
        ->select(
            'finish_transaction_tests.group_name',
            'finish_transactions.checkin_time',
            'finish_transaction_tests.validate_time'
        )
        ->get()
        ->groupBy('group_name');

        $groupData = DB::table('groups')
        ->select('id as group_id', 'name as group_name', 'target_tat')
        ->get();

        foreach ($groupData as $key => $group) {
            $groupName = $group->group_name;
            $targetSeconds = $group->target_tat * 60;
    
            $groupData[$key]->target_tat_in_minute = gmdate('H:i:s', $targetSeconds);
            $groupData[$key]->target_tat_in_seconds = $targetSeconds;
    
            $dataGroup = $transaksiData->get($groupName, collect());
    
            $totalTATSeconds = 0;
            $countBelowTarget = 0;
            $countAboveTarget = 0;
            $validCount = 0;
    
            foreach ($dataGroup as $item) {
                if ($item->checkin_time && $item->validate_time) {
                    $checkin = Carbon::parse($item->checkin_time);
                    $validate = Carbon::parse($item->validate_time);
                    $tatSeconds = $validate->diffInSeconds($checkin);
    
                    $totalTATSeconds += $tatSeconds;
                    $validCount++;
    
                    if ($tatSeconds < $targetSeconds) {
                        $countBelowTarget++;
                    } else {
                        $countAboveTarget++;
                    }
                }
            }
    
            $groupData[$key]->jumlah_pemeriksaan = $validCount;
            $groupData[$key]->total_tat_in_seconds = $totalTATSeconds;
            $groupData[$key]->target_dibawah = $countBelowTarget;
            $groupData[$key]->target_diatas = $countAboveTarget;
    
            $groupData[$key]->final_target_dibawah = $validCount > 0
                ? round(($countBelowTarget / $validCount) * 100) . '%'
                : '0%';
    
            $groupData[$key]->final_target_diatas = $validCount > 0
                ? round(($countAboveTarget / $validCount) * 100) . '%'
                : '0%';
        }

        return DataTables::of($groupData)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    // public function TATTargetDatatable($startDate = null, $endDate = null)
    // {
    //     if ($startDate == null && $endDate == null) {
    //         $from = Carbon::today()->startOfDay()->toDateTimeString();
    //         $to = Carbon::today()->endOfDay()->toDateTimeString();
    //     } else {
    //         $from = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
    //         $to = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
    //     }

    //     $tatData = DB::table('finish_transaction_tests')
    //         ->leftJoin('finish_transactions', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
    //         ->leftJoin('groups', 'finish_transaction_tests.group_id', '=', 'groups.id')
    //         ->whereBetween('finish_transaction_tests.input_time', [$from, $to])
    //         ->where('finish_transactions.type', '!=', 'igd')
    //         ->where('finish_transactions.room_name', '!=', 'RUANG ICU')
    //         ->whereNotNull('finish_transactions.checkin_time')
    //         ->whereNotNull('finish_transaction_tests.validate_time')
    //         ->select(
    //             'finish_transaction_tests.group_name',
    //             'finish_transaction_tests.group_id as group_id',
    //             DB::raw('COUNT(*) as jumlah_pemeriksaan'),
    //             DB::raw('SUM(TIMESTAMPDIFF(SECOND, finish_transactions.checkin_time, finish_transaction_tests.validate_time)) as total_tat_in_seconds'),
    //             DB::raw('SUM(CASE WHEN TIMESTAMPDIFF(SECOND, finish_transactions.checkin_time, finish_transaction_tests.validate_time) < (groups.target_tat * 60) THEN 1 ELSE 0 END) as target_dibawah'),
    //             DB::raw('SUM(CASE WHEN TIMESTAMPDIFF(SECOND, finish_transactions.checkin_time, finish_transaction_tests.validate_time) >= (groups.target_tat * 60) THEN 1 ELSE 0 END) as target_diatas'),
    //             DB::raw('groups.target_tat as target_tat_in_minutes')
    //         )
    //         ->groupBy('finish_transaction_tests.group_name', 'groups.target_tat')
    //         ->orderBy('finish_transaction_tests.group_name')
    //         ->get();

    //     foreach ($tatData as $data) {
    //         $data->target_tat_in_seconds = $data->target_tat_in_minutes * 60;
    //         $data->target_tat_in_minute = gmdate('H:i:s', $data->target_tat_in_seconds);

    //         if ($data->jumlah_pemeriksaan > 0) {
    //             $data->final_target_dibawah = round(($data->target_dibawah / $data->jumlah_pemeriksaan) * 100) . '%';
    //             $data->final_target_diatas = round(($data->target_diatas / $data->jumlah_pemeriksaan) * 100) . '%';
    //         } else {
    //             $data->final_target_dibawah = '0%';
    //             $data->final_target_diatas = '0%';
    //         }
    //     }

    //     return DataTables::of($tatData)
    //         ->addIndexColumn()
    //         ->escapeColumns([])
    //         ->make(true);
    // }

    public function TATTargetPrint($startDate = null, $endDate = null)
    {

        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $groups = DB::table('groups')
        ->select('id as group_id', 'name as group_name', 'target_tat')
        ->get()
        ->keyBy('group_name');
    
    // ambil semua data transaksi sekali query
    $transactions = DB::table('finish_transaction_tests')
        ->select(
            'finish_transaction_tests.group_name',
            'finish_transactions.checkin_time',
            'finish_transaction_tests.validate_time'
        )
        ->leftJoin('finish_transactions', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
        ->whereBetween(DB::raw('date(finish_transaction_tests.input_time)'), [$from, $to])
        ->where('finish_transactions.type', '!=', 'igd')
        ->where('finish_transactions.room_name', '!=', 'RUANG ICU')
        ->whereIn('finish_transaction_tests.group_name', $groups->keys())
        ->get();
    
    // kelompokkan berdasarkan group_name
    $grouped = $transactions->groupBy('group_name');
    
    $results = collect();
    
    foreach ($groups as $group_name => $group) {
        $data = $grouped->get($group_name, collect());
    
        if ($data->isEmpty()) {
            $results->push((object)[
                'group_id' => $group->group_id,
                'group_name' => $group_name,
                'target_tat' => $group->target_tat,
                'jumlah_pemeriksaan' => 0,
                'total_tat_in_seconds' => 0,
                'target_tat_in_seconds' => 0,
                'target_tat_in_minute' => '00:00:00',
                'target_dibawah' => 0,
                'target_diatas' => 0,
                'final_target_dibawah' => '0%',
                'final_target_diatas' => '0%',
            ]);
            continue;
        }
    
        $target_tat_in_seconds = $group->target_tat * 60;
        $target_tat_in_minute = gmdate('H:i:s', $target_tat_in_seconds);
    
        // hitung TAT per transaksi
        $tatSeconds = $data->map(function ($item) {
            $checkin = Carbon::parse($item->checkin_time);
            $validate = Carbon::parse($item->validate_time);
            return $validate->diffInSeconds($checkin);
        });
    
        $total_tat_in_seconds = $tatSeconds->sum();
        $jumlah_pemeriksaan = $tatSeconds->count();
    
        // hitung di bawah dan di atas target
        $count_tat_dibawah = $tatSeconds->filter(fn($t) => $t < $target_tat_in_seconds)->count();
        $count_tat_diatas = $jumlah_pemeriksaan - $count_tat_dibawah;
    
        // hitung persentase
        $final_target_dibawah = $jumlah_pemeriksaan > 0 ? round(($count_tat_dibawah / $jumlah_pemeriksaan) * 100) . '%' : '0%';
        $final_target_diatas = $jumlah_pemeriksaan > 0 ? round(($count_tat_diatas / $jumlah_pemeriksaan) * 100) . '%' : '0%';
    
        // simpan hasil
        $results->push((object)[
            'group_id' => $group->group_id,
            'group_name' => $group_name,
            'target_tat' => $group->target_tat,
            'target_tat_in_minute' => $target_tat_in_minute,
            'target_tat_in_seconds' => $target_tat_in_seconds,
            'jumlah_pemeriksaan' => $jumlah_pemeriksaan,
            'total_tat_in_seconds' => $total_tat_in_seconds,
            'target_dibawah' => $count_tat_dibawah,
            'target_diatas' => $count_tat_diatas,
            'final_target_dibawah' => $final_target_dibawah,
            'final_target_diatas' => $final_target_diatas,
        ]);
    }

        // echo '<pre>';
        // print_r( $results);
        // die;

        $data["tatData"] = $results;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_tat_target.print', $data);
    }

    //===============================================================================================
    // TAT (TURNAROUND TIME) REPORT
    //===============================================================================================
    public function TATCitoReport()
    {
        $data['title'] = 'Turnaround Time CITO Report';
        return view('dashboard.report.report_tat_cito.index', $data);
    }

    public function TATCitoDatatable($startDate = null, $endDate = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = DB::table('finish_transactions')
        ->select(
            'finish_transactions.id',
            'finish_transactions.patient_name as patient_name',
            'finish_transactions.patient_medrec as medrec',
            'finish_transactions.no_lab as no_lab',
            'finish_transactions.room_name as room_name',
            'finish_transaction_tests.test_name as test_name',
            'finish_transactions.checkin_time as checkin_time',
            'finish_transactions.analytic_time as analytic_time',
            'finish_transactions.post_time as post_time',
            'finish_transactions.created_time',
            'finish_transactions.cito',
            DB::raw('
                MAX(finish_transaction_tests.group_name) as group_name,
                MAX(finish_transaction_tests.draw_time) as draw_time,
                MAX(finish_transaction_tests.validate_time) as validate_time
            ')
        );
        $model->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id');
        $model->where('finish_transactions.created_time', '>=', $from);
        $model->where('finish_transactions.created_time', '<=', $to);
        $model->where('finish_transactions.cito', 1);
        $model->orderBy('finish_transactions.created_time', 'desc');
        $model->groupBy('finish_transactions.id');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function TATCitoPrint($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = DB::table('finish_transactions')
        ->select(
            'finish_transactions.id',
            'finish_transactions.patient_name as patient_name',
            'finish_transactions.patient_medrec as medrec',
            'finish_transactions.no_lab as no_lab',
            'finish_transactions.room_name as room_name',
            'finish_transaction_tests.test_name as test_name',
            'finish_transactions.checkin_time as checkin_time',
            'finish_transactions.analytic_time as analytic_time',
            'finish_transactions.post_time as post_time',
            'finish_transactions.created_time',
            'finish_transactions.cito',
            DB::raw('
                MAX(finish_transaction_tests.group_name) as group_name,
                MAX(finish_transaction_tests.draw_time) as draw_time,
                MAX(finish_transaction_tests.validate_time) as validate_time
            ')
        );
        $model->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id');
        $model->where('finish_transactions.created_time', '>=', $from);
        $model->where('finish_transactions.created_time', '<=', $to);
        $model->where('finish_transactions.cito', 1);
        $model->orderBy('finish_transactions.created_time', 'desc');
        $model->groupBy('finish_transactions.id');

        $data["tatData"] = $model->get();

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = Carbon::today()->toDateTimeString();
            $data["endDate"] = Carbon::today()->toDateTimeString();
        }

        return view('dashboard.report.report_tat_cito.print', $data);
    }

    //===============================================================================================
    // PATIENT REPORT
    //===============================================================================================
    public function patientReport()
    {
        $data['title'] = 'Patient Report';
        return view('dashboard.report.report_patient.index', $data);
    }

    public function patientDatatable($startDate = null, $endDate = null, $type_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = DB::table('finish_transactions')->selectRaw('finish_transactions.*');
        $model->where('created_time', '>=', $from);
        $model->where('created_time', '<=', $to);
        // $model->where('type', '=', 'rawat_jalan');
        if ($type_id) {
            $model->where('type', '=', $type_id);
        }
        $model->orderBy('created_time', 'desc');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function patientPrint($startDate = null, $endDate = null, $type = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')->selectRaw('finish_transactions.*');
        $query->where('created_time', '>=', $from);
        $query->where('created_time', '<=', $to);
        if ($type != null && $type != "null" && $type != 0) {
            $query->where('type', $type);
        }
        $query->orderBy('created_time', 'desc');

        $data["patientData"] = $query->get();

        if ($type != null && $type != "null" && $type != 0) {
            if ($type == 'rawat_inap') {
                $data["type"] = 'Rawat Inap';
            } else if ($type == 'rawat_jalan') {
                $data["type"] = 'Rawat Jalan';
            } else {
                $data["type"] = 'IGD';
            }
        } else {
            $data["type"] = '-';
        }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_patient.print', $data);
    }

    //===============================================================================================
    // ROOM REPORT
    //===============================================================================================
    public function roomReport()
    {
        $data['title'] = 'Room Report';
        return view('dashboard.report.report_room.index', $data);
    }

    public function roomDatatable($startDate = null, $endDate = null, $roomId = null)
    {
        // dd($startDate, $endDate);
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
        // dd($from, $to);

        // dd($from, $to);

        $model = DB::table('finish_transactions')
        ->selectRaw(
            'finish_transactions.id,
            finish_transactions.created_time,
            finish_transactions.room_id,
            finish_transactions.room_name,
            finish_transactions.type,
            finish_transactions.patient_name,
            finish_transactions.patient_medrec,
            finish_transactions.patient_gender'
        );
        $model->where('created_time', '>=', $from);
        $model->where('created_time', '<=', $to);   
        if ($roomId) {
            $model->where('room_id', '=', $roomId);
        }
        $model->orderBy('created_time', 'desc');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function roomPrint($startDate = null, $endDate = null, $roomId = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
        ->selectRaw(
            'finish_transactions.id,
            finish_transactions.created_time,
            finish_transactions.room_id,
            finish_transactions.room_name,
            finish_transactions.type,
            finish_transactions.patient_name,
            finish_transactions.patient_medrec,
            finish_transactions.patient_gender'
        );
        $query->where('created_time', '>=', $from);
        $query->where('created_time', '<=', $to);
        if ($roomId) {
            $query->where('room_id', $roomId);
        }
        $query->orderBy('created_time', 'desc');

        $data["roomData"] = $query->get();

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = Carbon::now()->toDateString();
            $data["endDate"] = Carbon::now()->toDateString();
        }

        return view('dashboard.report.report_room.print', $data);
    }

    public function exportExcelRoom($startDate = null, $endDate = null, $roomId = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
        ->selectRaw(
            'finish_transactions.id,
            finish_transactions.created_time,
            finish_transactions.room_id,
            finish_transactions.room_name,
            finish_transactions.type,
            finish_transactions.patient_name,
            finish_transactions.patient_medrec,
            finish_transactions.patient_gender'
        );
        $query->where('created_time', '>=', $from);
        $query->where('created_time', '<=', $to);
        if ($roomId) {
            $query->where('room_id', $roomId);
        }
        $query->orderBy('created_time', 'desc');

        $data = $query->get();

        if ($startDate != null && $endDate != null) {
            $tanggalStart = date('d/m/Y', strtotime($startDate));
            $tanggalEnd = date('d/m/Y', strtotime($endDate));
        } else {
            $tanggalStart = Carbon::now()->toDateString();
            $tanggalEnd = Carbon::now()->toDateString();
        }

        $fileName = 'RoomReport.xlsx'; // Nama file Excel yang akan diunduh

        return Excel::download(new RoomReport($data, $tanggalStart, $tanggalEnd), $fileName);
    }

    //===============================================================================================
    // PATIENT RUJUKAN REPORT
    //===============================================================================================
    public function patientRujukanReport()
    {
        $data['title'] = 'Patient Rujukan Report';
        return view('dashboard.report.report_patient_rujukan.index', $data);
    }

    public function patientRujukanDatatable($startDate = null, $endDate = null, $patient_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $insuranceRujukan = [44, 45, 46, 47];

        $query = DB::table('finish_transactions')
        ->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id')
        ->leftJoin('insurances', 'finish_transactions.insurance_id', '=', 'insurances.id')
        ->select(
            'finish_transactions.id as finish_transaction_id',
            'finish_transactions.patient_id',
            'finish_transactions.patient_name as patient_name',
            'finish_transactions.no_lab as no_lab',
            'finish_transactions.created_time',
            'finish_transaction_tests.input_time',
            'finish_transaction_tests.test_name',
            'finish_transaction_tests.global_result',
            'insurances.name as insurance_name',
        );

        if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
            $query->where('finish_transactions.patient_id', '=', $patient_id);
        }

        $query->whereIn('finish_transactions.insurance_id', $insuranceRujukan);
        $query->whereBetween('finish_transactions.created_time', [$from, $to]);
        $query->orderBy('finish_transactions.created_time', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function patientRujukanPrint($startDate = null, $endDate = null, $patient_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $insuranceRujukan = [44, 45, 46, 47];

        $query = DB::table('finish_transactions')
        ->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id')
        ->leftJoin('insurances', 'finish_transactions.insurance_id', '=', 'insurances.id')
        ->select(
            'finish_transactions.id as finish_transaction_id',
            'finish_transactions.patient_id',
            'finish_transactions.patient_name as patient_name',
            'finish_transactions.no_lab as no_lab',
            'finish_transactions.created_time',
            'finish_transaction_tests.input_time',
            'finish_transaction_tests.test_name',
            'finish_transaction_tests.global_result',
            'insurances.name as insurance_name',
        );

        if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
            $query->where('finish_transactions.patient_id', '=', $patient_id);
        }

        if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
            $query->where('finish_transactions.patient_id', '=', $patient_id);
            $query_patient = DB::table('patients')->select('patients.name as patient_name')
                ->where('id', '=', $patient_id);
            $patient = $query_patient->first();
            $data['patient'] = $patient;
        }

        $data['patient'] = '';

        $query->whereIn('finish_transactions.insurance_id', $insuranceRujukan);
        $query->whereBetween('finish_transactions.created_time', [$from, $to]);
        $query->orderBy('finish_transactions.created_time', 'desc');

        $data["patientData"] = $query->get();

        // if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
        //     $query_patient = DB::table('patients')->select('patients.name as patient_name')
        //         ->where('id', '=', $patient_id);
        //     $patient = $query_patient->first();
        //     $data['patient'] = $patient;
        // } else {
        //     $data['patient'] = '';
        // }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = Carbon::today()->toDateTimeString();
            $data["endDate"] = Carbon::today()->toDateTimeString();
        }

        return view('dashboard.report.report_patient_rujukan.print', $data);
    }

    //===============================================================================================
    // PATIENT DETAIL REPORT
    //===============================================================================================
    public function patientDetailReport()
    {
        $data['title'] = 'Patient Detail Report';
        return view('dashboard.report.report_patient_detail.index', $data);
    }

    public function patientDetailDatatable($startDate = null, $endDate = null, $patient_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
        ->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id')
        ->select(
            'finish_transactions.id as finish_transaction_id',
            'finish_transactions.patient_id',
            'finish_transactions.patient_name as patient_name',
            'finish_transactions.no_lab as no_lab',
            'finish_transactions.created_time',
            'finish_transaction_tests.input_time',
            'finish_transaction_tests.test_name',
            'finish_transaction_tests.global_result'
        );

        if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
            $query->where('finish_transactions.patient_id', '=', $patient_id);
        }

        $query->whereBetween('finish_transactions.created_time', [$from, $to]);
        $query->orderBy('finish_transactions.created_time', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function patientDetailPrint($startDate = null, $endDate = null, $patient_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
        ->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id')
        ->select(
            'finish_transactions.id as finish_transaction_id',
            'finish_transactions.patient_id',
            'finish_transactions.patient_name as patient_name',
            'finish_transactions.no_lab as no_lab',
            'finish_transactions.created_time',
            'finish_transaction_tests.input_time',
            'finish_transaction_tests.test_name',
            'finish_transaction_tests.global_result'
        );

        if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
            $query->where('finish_transactions.patient_id', '=', $patient_id);
            $query_patient = DB::table('patients')->select('patients.name as patient_name')
                ->where('id', '=', $patient_id);
            $patient = $query_patient->first();
            $data['patient'] = $patient;
        }

        $data['patient'] = '';

        $query->whereBetween('finish_transactions.created_time', [$from, $to]);
        $query->orderBy('finish_transactions.created_time', 'desc');

        $data["patientData"] = $query->get();

        // if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
        //     $query_patient = DB::table('patients')->select('patients.name as patient_name')
        //         ->where('id', '=', $patient_id);
        //     $patient = $query_patient->first();
        //     $data['patient'] = $patient;
        // } else {
        //     $data['patient'] = '';
        // }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = Carbon::today()->toDateTimeString();
            $data["endDate"] = Carbon::today()->toDateTimeString();
        }

        return view('dashboard.report.report_patient_detail.print', $data);
    }

    //===============================================================================================
    // TEST REPORT
    //===============================================================================================
    public function testReport()
    {
        $data['title'] = 'Test Report';
        return view('dashboard.report.report_test.index', $data);
    }

    public function testDatatable($startDate = null, $endDate = null, $test_id = null)
    {
        // dd($test_id);
        if (!$startDate && !$endDate) {
            $from = Carbon::today()->startOfDay();
            $to = Carbon::today()->endOfDay();
        } else {
            $from = Carbon::parse($startDate)->startOfDay();
            $to = Carbon::parse($endDate)->endOfDay();
        }

        $query_package = DB::table('finish_transaction_tests as ftt')
        ->select(
            'ftt.package_id as test_id',
            'ftt.package_name as test_name',
            DB::raw("'package' as type"),
            DB::raw('COUNT(DISTINCT ftt.transaction_id) as total_test')
        )
        ->join('finish_transactions as ft', 'ftt.transaction_id', '=', 'ft.transaction_id')
        ->whereNotNull('ftt.package_id')
        ->whereBetween('ft.created_time', [$from, $to])
        ->groupBy('ftt.package_id', 'ftt.package_name');

        if ($test_id && $test_id != "null") {
            $query_package->where('ftt.package_id', $test_id);
        }

        $packageData = $query_package->get();

        $query_single = DB::table('finish_transaction_tests as ftt')
        ->select(
            'ftt.test_id',
            'ftt.test_name',
            DB::raw("'single' as type"),
            DB::raw('COUNT(*) as total_test')
        )
        ->join('finish_transactions as ft', 'ftt.transaction_id', '=', 'ft.transaction_id')
        ->whereNull('ftt.package_id')
        ->whereBetween('ft.created_time', [$from, $to])
        ->groupBy('ftt.test_id', 'ftt.test_name');

        if ($test_id && $test_id != "null") {
            $query_single->where('ftt.test_id', $test_id);
        }

        $singleData = $query_single->get();

        $data = $packageData->merge($singleData);

        return DataTables::of($data)
        ->addIndexColumn()
        ->escapeColumns([])
        ->make(true);
    }

    public function testPrint($startDate = null, $endDate = null, $test_id = null)
    {
        if (!$startDate && !$endDate) {
            $from = Carbon::today()->startOfDay();
            $to = Carbon::today()->endOfDay();
        } else {
            $from = Carbon::parse($startDate)->startOfDay();
            $to = Carbon::parse($endDate)->endOfDay();
        }

        $query_package = DB::table('finish_transaction_tests as ftt')
        ->select(
            'ftt.package_id as test_id',
            'ftt.package_name as test_name',
            DB::raw("'package' as type"),
            DB::raw('COUNT(DISTINCT ftt.transaction_id) as total_test')
        )
        ->join('finish_transactions as ft', 'ftt.transaction_id', '=', 'ft.transaction_id')
        ->whereNotNull('ftt.package_id')
        ->whereBetween('ft.created_time', [$from, $to])
        ->groupBy('ftt.package_id', 'ftt.package_name');

        if ($test_id && $test_id != "null") {
            $query_package->where('ftt.package_id', $test_id);
        }

        $packageData = $query_package->get();

        $query_single = DB::table('finish_transaction_tests as ftt')
        ->select(
            'ftt.test_id',
            'ftt.test_name',
            DB::raw("'single' as type"),
            DB::raw('COUNT(*) as total_test')
        )
        ->join('finish_transactions as ft', 'ftt.transaction_id', '=', 'ft.transaction_id')
        ->whereNull('ftt.package_id')
        ->whereBetween('ft.created_time', [$from, $to])
        ->groupBy('ftt.test_id', 'ftt.test_name');

        if ($test_id && $test_id != "null") {
            $query_single->where('ftt.test_id', $test_id);
        }

        $singleData = $query_single->get();

        $dataReport = $packageData->merge($singleData);

        $data['testData'] = $dataReport;

        // dd($data);

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_test.print', $data);
    }

      //===============================================================================================
    // TEST DETAIL REPORT
    //===============================================================================================

    public function customTestReport()
    {
        $data['title'] = 'Custom Test Report';
        return view('dashboard.report.report_custom_test.index', $data);
    }

    public function customTestDatatable($startDate = null, $endDate = null)
    {
        
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
        
        $testIds = [525, 877, 967, 1002, 1365, 1425, 1430, 526, 781, 878, 1073, 1190, 1273, 1342, 1343, 741, 950, 1270, 1271];
        
        $data = \App\FinishTransaction::query()
            ->select(['finish_transactions.id','finish_transactions.created_time','finish_transactions.patient_name' ,'finish_transactions.patient_medrec', DB::raw('GROUP_CONCAT(DISTINCT finish_transaction_tests.test_name ORDER BY finish_transaction_tests.test_name SEPARATOR ", ") AS test_names'),
            ])
            ->join('finish_transaction_tests', function ($join) use ($testIds) {
                $join->on('finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
                     ->whereIn('finish_transaction_tests.test_id', $testIds);
            })
            ->whereBetween('finish_transactions.created_time', [$from, $to])
            ->groupBy('finish_transactions.id')
            ->orderByDesc('finish_transactions.created_time')
            ->get();
        

        return DataTables::of($data)
        ->addIndexColumn()
        ->escapeColumns([])
        ->make(true);
    
    }

    public function excelCustomTest($startDate = null, $endDate = null)
    {
        
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
        
        $testIds = [525, 877, 967, 1002, 1365, 1425, 1430, 526, 781, 878, 1073, 1190, 1273, 1342, 1343, 741, 950, 1270, 1271];
        
        $query = \App\FinishTransaction::query()
            ->select(['finish_transactions.id','finish_transactions.created_time','finish_transactions.patient_name' ,'finish_transactions.patient_medrec', DB::raw('GROUP_CONCAT(DISTINCT finish_transaction_tests.test_name ORDER BY finish_transaction_tests.test_name SEPARATOR ", ") AS test_names'),
            ])
            ->join('finish_transaction_tests', function ($join) use ($testIds) {
                $join->on('finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
                     ->whereIn('finish_transaction_tests.test_id', $testIds);
            })
            ->whereBetween('finish_transactions.created_time', [$from, $to])
            ->groupBy('finish_transactions.id')
            ->orderByDesc('finish_transactions.created_time')
            ->get();

            $data["transactions"] = $query;

            if ($startDate != null && $endDate != null) {
                $data["startDate"] = date('d/m/Y', strtotime($startDate));
                $data["endDate"] = date('d/m/Y', strtotime($endDate));
            } else {
                $data["startDate"] = '-';
                $data["endDate"] = '-';
            }
        
            $fileName = 'Custom Test.xlsx'; // Nama file Excel yang akan diunduh

            return Excel::download(new CustomTestExport($data), $fileName);
    
    }

      //===============================================================================================
    // TEST DETAIL REPORT
    //===============================================================================================

    public function testDetailReport()
    {
        $data['title'] = 'Test Detail Report';
        return view('dashboard.report.report_test_detail.index', $data);
    }

    public function testDetailDatatable($startDate = null, $endDate = null, $test_id = null)
    {
        
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
        // dd($test_id);
        $test_ids = [$test_id];
        $model = FinishTransaction::selectRaw('finish_transactions.id');
        if ($test_ids != null && $test_ids != ["null"] && $test_ids != [0]) {
            $model->join('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
                ->whereIn('test_id', $test_ids);
        }
        

        $model->where('created_time', '>=', $from);
        $model->where('created_time', '<=', $to);
        $finishTransactionIds = $model->get();
        $ftIds = [];
        foreach($finishTransactionIds as $key => $value) {
            $ftIds[] = $value->id;
        }
       
        $finishTransactionTestModel = FinishTransactionTest::selectRaw('finish_transaction_tests.finish_transaction_id, count(finish_transaction_tests.finish_transaction_id)')
            ->whereIn('finish_transaction_tests.finish_transaction_id', $ftIds)
            ->groupBy('finish_transaction_tests.finish_transaction_id');
        
            if ($test_ids != null && $test_ids != ["null"] && $test_ids != [0]) {
                $finishTransactionTestModel = $finishTransactionTestModel->whereIn('test_id', $test_ids);
            }
            
        
        $newFtIds = [];
        foreach($finishTransactionTestModel->get() as $key => $value) {
            $newFtIds[] = $value->finish_transaction_id;
        }
       
        $finishTransactionModel = FinishTransaction::whereIn('id', $newFtIds);
      
        return DataTables::of($finishTransactionModel)
        ->addIndexColumn()
        ->addColumn('test_names', function ($data) use ($test_ids) {
            $ftt = DB::table('finish_transaction_tests')
                ->select('test_name')
                ->where('finish_transaction_id', $data->id);
    
            if (!empty($test_ids) && $test_ids != [0]) {
                $ftt->whereIn('test_id', $test_ids);
            }
    
            $ftt = $ftt->get();
    
            $names = '';
            if (!empty($ftt)) {
                foreach ($ftt as $key => $value) {
                    $names .= $value->test_name;
                    if ($key < count($ftt) - 1) {
                        $names .= ', ';
                    }
                }
            } else {
                $names = 'All Tests';
            }
    
            return $names;
        })
      
        ->addColumn('test_global_results', function ($data) use ($test_ids) {
            if (!empty($test_ids) && $test_ids != [0]) {
                $ftt = DB::table('finish_transaction_tests')
                    ->select( 'global_result')
                    ->where('finish_transaction_id', $data->id)
                    ->whereIn('test_id', $test_ids)
                    ->get();
                    $global_result = '';
                    foreach ($ftt as $key => $value) {
                        $global_result .=  $value->global_result;
                        if ($key < count($ftt) - 1) {
                            $global_result .= '<br>';
                        }
                    }
                    
            return $global_result;
            } else {
                $ftt = DB::table('finish_transaction_tests')
                    ->select('test_name', 'global_result', 'unit')
                    ->where('finish_transaction_id', $data->id)
                    ->get();
                    $global_result = '';
                    foreach ($ftt as $key => $value) {
                        $global_result .= $value->test_name . ": " . $value->global_result . " " . $value->unit;
                        if ($key < count($ftt) - 1) {
                            $global_result .= '<br>';
                        }
                    }
                    return $global_result;
            }    
        })
        ->escapeColumns([])
        ->make(true);

     
    }
    public function printTestDetail($startDate = null, $endDate = null, $test_id = null) 
    {

        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
        
        $query_test = DB::table('finish_transactions')->select('finish_transactions.id');
        if ($test_id != null && $test_id != "null" && $test_id != 0) {
            $test_ids = explode(',', $test_id); // Ubah menjadi array
            $query_test->join('finish_transaction_tests as ftt1', 'ftt1.finish_transaction_id', '=', 'finish_transactions.id')
                ->whereIn('ftt1.test_id', $test_ids); // Gunakan $test_ids yang merupakan array
        }
        
        $query_test->where('created_time', '>=', $from);
        $query_test->where('created_time', '<=', $to);
        $id["id"] = $query_test->get();
        $jumlah = count($id["id"]);
        
        $testDetails = [];
        for ($i = 0; $i < $jumlah; $i++) {
            if (!empty($test_ids) && $test_ids != [0]) {
                $model = DB::table('finish_transactions')->selectRaw('finish_transactions.*, GROUP_CONCAT(ftt2.test_name SEPARATOR ", ") AS test_names, GROUP_CONCAT(ftt2.global_result SEPARATOR ", ") AS global_results');
         
            } else{
                $model = DB::table('finish_transactions')->selectRaw('finish_transactions.*, GROUP_CONCAT(ftt2.test_name SEPARATOR ", ") AS test_names, GROUP_CONCAT(CONCAT(ftt2.test_name, " : ", ftt2.global_result, " ", ftt2.unit) SEPARATOR ", ") AS global_results');
            }
            if ($test_id != null && $test_id != "null" && $test_id != 0) {
                $model->join('finish_transaction_tests as ftt2', 'ftt2.finish_transaction_id', '=', 'finish_transactions.id')
                    ->whereIn('ftt2.test_id', $test_ids); // Gunakan $test_ids yang merupakan array
            } else {
                $model->join('finish_transaction_tests as ftt2', 'ftt2.finish_transaction_id', '=', 'finish_transactions.id');
            }
            $model->where('finish_transactions.id', $id["id"][$i]->id);
            $model->where('created_time', '>=', $from);
            $model->where('created_time', '<=', $to);
            $model->groupBy('finish_transactions.id');
        
            $data['testDetail'][] = $model->get();
        }
        
        if ($startDate != null && $endDate != null) {
            $tanggal["startDate"] = date('d/m/Y', strtotime($startDate));
            $tanggal["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $tanggal["startDate"] = '-';
            $tanggal["endDate"] = '-';
        }
            return view('dashboard.report.report_test_detail.print', compact('data'), $tanggal);
    }


    public function exportExcelTestDetail($startDate = null, $endDate = null, $test_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query_test = FinishTransaction::query()->select('finish_transactions.id');
        if ($test_id != null && $test_id != "null" && $test_id != 0) {
            $test_ids = explode(',', $test_id); // Ubah menjadi array
            $query_test->join('finish_transaction_tests as ftt1', 'ftt1.finish_transaction_id', '=', 'finish_transactions.id')
                ->whereIn('ftt1.test_id', $test_ids); // Gunakan $test_ids yang merupakan array
        }

        $query_test->where('created_time', '>=', $from);
        $query_test->where('created_time', '<=', $to);
        $id = $query_test->pluck('id')->toArray();
        $jumlah = count($id);

        $testDetails = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $model = FinishTransaction::query()->selectRaw('finish_transactions.*, GROUP_CONCAT(ftt2.test_name SEPARATOR ", ") AS test_names, GROUP_CONCAT(ftt2.global_result SEPARATOR ", ") AS global_results');
            if ($test_id != null && $test_id != "null" && $test_id != 0) {
                $model->join('finish_transaction_tests as ftt2', 'ftt2.finish_transaction_id', '=', 'finish_transactions.id')
                    ->whereIn('ftt2.test_id', $test_ids); // Gunakan $test_ids yang merupakan array
            } else {
                $model->join('finish_transaction_tests as ftt2', 'ftt2.finish_transaction_id', '=', 'finish_transactions.id');
            }
            $model->where('finish_transactions.id', $id[$i]);
            $model->where('created_time', '>=', $from);
            $model->where('created_time', '<=', $to);
            $model->groupBy('finish_transactions.id');

            $testDetails[] = $model->get();
        }
 

        $data = collect($testDetails)->flatten();

        if ($startDate != null && $endDate != null) {
            $tanggalStart = date('d/m/Y', strtotime($startDate));
            $tanggalEnd = date('d/m/Y', strtotime($endDate));
        } else {
            $tanggalStart = '-';
            $tanggalEnd = '-';
        }

        // dd($data);

        $fileName = 'TestDetailReport.xlsx'; // Nama file Excel yang akan diunduh

        return Excel::download(new TestDetailExport($data, $tanggalStart, $tanggalEnd), $fileName);
    }

    //===============================================================================================
    // HISTORY PATIENT REPORT
    //===============================================================================================

    public function historyPatientReport()
    {
        $data['title'] = 'History Patient Report';
        return view('dashboard.report.report_history_patient.index', $data);
    }

    public function historyPatientDatatable($startDate = null, $endDate = null, $test_id = null, $patient_id = null)
    {
        
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
        // dd($test_id);
        $test_ids = [$test_id];
        $model = FinishTransaction::selectRaw('finish_transactions.id');
        if ($test_ids != null && $test_ids != ["null"] && $test_ids != [0]) {
            $model->join('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
                ->whereIn('test_id', $test_ids);
        }
        if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
            $model->where('patient_id', $patient_id);
        }
        

        $model->where('created_time', '>=', $from);
        $model->where('created_time', '<=', $to);
        $finishTransactionIds = $model->get();
        $ftIds = [];
        foreach($finishTransactionIds as $key => $value) {
            $ftIds[] = $value->id;
        }
       
        $finishTransactionTestModel = FinishTransactionTest::selectRaw('finish_transaction_tests.finish_transaction_id, count(finish_transaction_tests.finish_transaction_id)')
             ->join('finish_transactions', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
            ->whereIn('finish_transaction_tests.finish_transaction_id', $ftIds)
            ->groupBy('finish_transaction_tests.finish_transaction_id');
        
            if ($test_ids != null && $test_ids != ["null"] && $test_ids != [0]) {
                $finishTransactionTestModel = $finishTransactionTestModel->whereIn('test_id', $test_ids);
            }
            if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
                $finishTransactionTestModel = $finishTransactionTestModel->where('patient_id', $patient_id);
            }
            
        
        $newFtIds = [];
        foreach($finishTransactionTestModel->get() as $key => $value) {
            $newFtIds[] = $value->finish_transaction_id;
        }
       
        $finishTransactionModel = FinishTransaction::whereIn('id', $newFtIds);
      
        return DataTables::of($finishTransactionModel)
        ->addIndexColumn()
        ->addColumn('test_names', function ($data) use ($test_ids) {
            $ftt = DB::table('finish_transaction_tests')
                ->select('test_name')
                ->where('finish_transaction_id', $data->id);
    
            if (!empty($test_ids) && $test_ids != [0]) {
                $ftt->whereIn('test_id', $test_ids);
            }
    
            $ftt = $ftt->get();
    
            $names = '';
            if (!empty($ftt)) {
                foreach ($ftt as $key => $value) {
                    $names .= $value->test_name;
                    if ($key < count($ftt) - 1) {
                        $names .= ', ';
                    }
                }
            } else {
                $names = 'All Tests';
            }
    
            return $names;
        })
      
        ->addColumn('test_global_results', function ($data) use ($test_ids) {
            if (!empty($test_ids) && $test_ids != [0]) {
                $ftt = DB::table('finish_transaction_tests')
                    ->select( 'global_result')
                    ->where('finish_transaction_id', $data->id)
                    ->whereIn('test_id', $test_ids)
                    ->get();
                    $global_result = '';
                    foreach ($ftt as $key => $value) {
                        $global_result .=  $value->global_result;
                        if ($key < count($ftt) - 1) {
                            $global_result .= '<br>';
                        }
                    }
                    
            return $global_result;
            } else {
                $ftt = DB::table('finish_transaction_tests')
                    ->select('test_name', 'global_result', 'unit')
                    ->where('finish_transaction_id', $data->id)
                    ->get();
                    $global_result = '';
                    foreach ($ftt as $key => $value) {
                        $global_result .= $value->test_name . ": " . $value->global_result . " " . $value->unit;
                        if ($key < count($ftt) - 1) {
                            $global_result .= '<br>';
                        }
                    }
                    return $global_result;
            }    
        })
        ->escapeColumns([])
        ->make(true);

     
    }
    public function historyPatientPrint($startDate = null, $endDate = null, $test_id = null, $patient_id = null) 
    {

        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
        
        $query_test = DB::table('finish_transactions')->select('finish_transactions.id');
        if ($test_id != null && $test_id != "null" && $test_id != 0) {
            $test_ids = explode(',', $test_id); // Ubah menjadi array
            $query_test->join('finish_transaction_tests as ftt1', 'ftt1.finish_transaction_id', '=', 'finish_transactions.id')
                ->whereIn('ftt1.test_id', $test_ids); // Gunakan $test_ids yang merupakan array
        }
        if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
            $query_test->where('finish_transactions.patient_id', $patient_id); // Gunakan $test_ids yang merupakan array
        }
        
        $query_test->where('created_time', '>=', $from);
        $query_test->where('created_time', '<=', $to);
        $id["id"] = $query_test->get();
        $jumlah = count($id["id"]);
        
        $testDetails = [];
        for ($i = 0; $i < $jumlah; $i++) {
            if (!empty($test_ids) && $test_ids != [0]) {
                $model = DB::table('finish_transactions')->selectRaw('finish_transactions.*, GROUP_CONCAT(ftt2.test_name SEPARATOR ", ") AS test_names, GROUP_CONCAT(ftt2.global_result SEPARATOR ", ") AS global_results');
         
            } else{
                $model = DB::table('finish_transactions')->selectRaw('finish_transactions.*, GROUP_CONCAT(ftt2.test_name SEPARATOR ", ") AS test_names, GROUP_CONCAT(CONCAT(ftt2.test_name, " : ", ftt2.global_result, " ", ftt2.unit) SEPARATOR ", ") AS global_results');
            }
            if ($test_id != null && $test_id != "null" && $test_id != 0) {
                $model->join('finish_transaction_tests as ftt2', 'ftt2.finish_transaction_id', '=', 'finish_transactions.id')
                    ->whereIn('ftt2.test_id', $test_ids); // Gunakan $test_ids yang merupakan array
            } else {
                $model->join('finish_transaction_tests as ftt2', 'ftt2.finish_transaction_id', '=', 'finish_transactions.id');
            }
            $model->where('finish_transactions.id', $id["id"][$i]->id);
            $model->where('created_time', '>=', $from);
            $model->where('created_time', '<=', $to);
         
            $model->groupBy('finish_transactions.id');
       
            $testDetail['testDetail'][] = $model->get();
        }
        $dataCollect = collect($testDetail)->flatten();

        $data['historyPatient'] = $dataCollect;
        
        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        if ($patient_id != null && $patient_id != 'null' && $patient_id != 0) {
          $patient = DB::table('patients')
                    ->select('name')
                    ->where('id', $patient_id)
                    ->first();
            $data['patient_name'] = $patient->name;
        } else {
            $data['patient_name'] = '-';
        }


        if ($test_id != null && $test_id != 'null' && $test_id != 0) {
          $test = DB::table('tests')
                    ->select('name')
                    ->where('id', $test_id)
                    ->first();
            $data['test_name'] = $test->name;
        } else {
            $data['test_name'] = '-';
        }
        
      

        // echo '<pre>';
        // print_r($data);
        // die;
        
      
            return view('dashboard.report.report_history_patient.print',  $data);
    }


    public function historyPatientExcel($startDate = null, $endDate = null, $test_id = null, $patient_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
        
        $query_test = DB::table('finish_transactions')->select('finish_transactions.id');
        if ($test_id != null && $test_id != "null" && $test_id != 0) {
            $test_ids = explode(',', $test_id); // Ubah menjadi array
            $query_test->join('finish_transaction_tests as ftt1', 'ftt1.finish_transaction_id', '=', 'finish_transactions.id')
                ->whereIn('ftt1.test_id', $test_ids); // Gunakan $test_ids yang merupakan array
        }
        if ($patient_id != null && $patient_id != "null" && $patient_id != 0) {
            $query_test->where('finish_transactions.patient_id', $patient_id); // Gunakan $test_ids yang merupakan array
        }
        
        $query_test->where('created_time', '>=', $from);
        $query_test->where('created_time', '<=', $to);
        $id["id"] = $query_test->get();
        $jumlah = count($id["id"]);
        
        $testDetails = [];
        for ($i = 0; $i < $jumlah; $i++) {
            if (!empty($test_ids) && $test_ids != [0]) {
                $model = DB::table('finish_transactions')->selectRaw('finish_transactions.*, GROUP_CONCAT(ftt2.test_name SEPARATOR ", ") AS test_names, GROUP_CONCAT(ftt2.global_result SEPARATOR ", ") AS global_results');
         
            } else{
                $model = DB::table('finish_transactions')->selectRaw('finish_transactions.*, GROUP_CONCAT(ftt2.test_name SEPARATOR ", ") AS test_names, GROUP_CONCAT(CONCAT(ftt2.test_name, " : ", ftt2.global_result, " ", ftt2.unit) SEPARATOR ", ") AS global_results');
            }
            if ($test_id != null && $test_id != "null" && $test_id != 0) {
                $model->join('finish_transaction_tests as ftt2', 'ftt2.finish_transaction_id', '=', 'finish_transactions.id')
                    ->whereIn('ftt2.test_id', $test_ids); // Gunakan $test_ids yang merupakan array
            } else {
                $model->join('finish_transaction_tests as ftt2', 'ftt2.finish_transaction_id', '=', 'finish_transactions.id');
            }
            $model->where('finish_transactions.id', $id["id"][$i]->id);
            $model->where('created_time', '>=', $from);
            $model->where('created_time', '<=', $to);
         
            $model->groupBy('finish_transactions.id');
       
            $testDetail['testDetail'][] = $model->get();
        }
        $dataCollect = collect($testDetail)->flatten();

        $data['historyPatient'] = $dataCollect;
        
        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        if ($patient_id != null && $patient_id != 'null' && $patient_id != 0) {
          $patient = DB::table('patients')
                    ->select('name')
                    ->where('id', $patient_id)
                    ->first();
            $data['patient_name'] = $patient->name;
        } else {
            $data['patient_name'] = '-';
        }


        if ($test_id != null && $test_id != 'null' && $test_id != 0) {
          $test = DB::table('tests')
                    ->select('name')
                    ->where('id', $test_id)
                    ->first();
            $data['test_name'] = $test->name;
        } else {
            $data['test_name'] = '-';
        }
        // dd($data);

        $fileName = 'History Patient Report.xlsx'; // Nama file Excel yang akan diunduh

        return Excel::download(new ExportHistoryPatient($data), $fileName);

  

    }
    //===============================================================================================
    // SARS Cov-2 ANTIGENT REPORT
    //===============================================================================================
    public function sarsCovReport()
    {
        $data['title'] = 'Sars Cov-2 Report';
        return view('dashboard.report.report_sars_cov.index', $data);
    }

    public function sarsCovDatatable($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
            ->select('finish_transactions.*', 'finish_transaction_tests.global_result')
            ->leftJoin('finish_transaction_tests', 'finish_transactions.transaction_id', '=', 'finish_transaction_tests.transaction_id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->where('finish_transaction_tests.test_id', 856)
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transactions.id');
        $patientData = $query->get();

        return DataTables::of($patientData)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function sarsCovPrint($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
            ->select('finish_transactions.*', 'finish_transaction_tests.global_result')
            ->leftJoin('finish_transaction_tests', 'finish_transactions.transaction_id', '=', 'finish_transaction_tests.transaction_id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->where('finish_transaction_tests.test_id', 856)
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transactions.id');
        $patientData = $query->get();

        $data['patientData'] = $patientData;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_sars_cov.print', $data);
    }

    //===============================================================================================
    // RAPID HIV REPORT
    //===============================================================================================
    public function rapidHivReport()
    {
        $data['title'] = 'Rapid HIV Report';
        return view('dashboard.report.report_rapid_hiv.index', $data);
    }

    public function rapidHivDatatable($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
            ->select('finish_transactions.*', 'finish_transaction_tests.global_result')
            ->leftJoin('finish_transaction_tests', 'finish_transactions.transaction_id', '=', 'finish_transaction_tests.transaction_id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->where('finish_transaction_tests.test_name', 'HIV (RAPID)')
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transactions.id');
        $patientData = $query->get();

        return DataTables::of($patientData)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function rapidHivPrint($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
            ->select('finish_transactions.*', 'finish_transaction_tests.global_result')
            ->leftJoin('finish_transaction_tests', 'finish_transactions.transaction_id', '=', 'finish_transaction_tests.transaction_id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->where('finish_transaction_tests.test_name', 'HIV (RAPID)')
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transactions.id');
        $patientData = $query->get();

        $data['patientData'] = $patientData;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_rapid_hiv.print', $data);
    }

    //===============================================================================================
    // SPECIMEN REPORT
    //===============================================================================================
    public function specimenReport()
    {
        $data['title'] = 'Specimen Report';
        return view('dashboard.report.report_specimen.index', $data);
    }

    public function specimenDatatable($startDate = null, $endDate = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->startOfDay()->toDateTimeString();
            $to = Carbon::today()->endOfDay()->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
            $to = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
        }

        // BASE QUERY with optional DATE FILTER
        $query = DB::table('finish_transactions')
        ->leftJoin('finish_transaction_tests', 'finish_transactions.id', 'finish_transaction_tests.finish_transaction_id')
            ->select('finish_transaction_tests.specimen_name', '.finish_transactions.id', DB::raw('COUNT(DISTINCT finish_transaction_tests.finish_transaction_id) as total'))
            ->whereBetween('finish_transactions.created_time', [$from, $to])
            ->groupBy('finish_transaction_tests.specimen_id')
            ->get();
        // dd($query);
        return DataTables::of($query)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function specimenPrint($startDate = null, $endDate = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->startOfDay()->toDateString();
            $to = Carbon::today()->endOfDay()->toDateString();
        } else {
            $from = Carbon::parse($startDate)->startOfDay()->toDateString();
            $to = Carbon::parse($endDate)->endOfDay()->toDateString();
        }

        // BASE QUERY with optional DATE FILTER
        $specimen = DB::table('finish_transaction_tests')
            ->select('specimen_name', 'id', DB::raw('COUNT(DISTINCT transaction_id) as total'))
            ->whereRaw("date(input_time) between '" . $from . "' and '" . $to . "'")
            ->groupBy('specimen_name')->get();

        $data = [
            'from' => $from,
            'to' => $to,
            'specimens' => $specimen
        ];

        return view('dashboard.report.report_specimen.print', $data);
    }

    //===============================================================================================
    // SUPPORT ACTIVITIES LAB REPORT
    //===============================================================================================
    public function SupportActivitiesReport()
    {
        $data['title'] = 'Support Activities LAB Report';
        return view('dashboard.report.report_support_activities.index', $data);
    }

    public function SupportActivitiesDatatable($startDate = null, $endDate = null, $insurance_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

       
        
        $query_package = DB::table('finish_transactions')
        ->select('finish_transactions.*', 'finish_transaction_tests.package_id', 'finish_transaction_tests.package_name')
            ->leftjoin('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', 'finish_transactions.id')
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('finish_transaction_tests.package_id', '!=', null);
            if($insurance_id != null && $insurance_id != 'null'){
                $query_package->where('finish_transactions.insurance_id', '=', $insurance_id);
        }
            $result_package = $query_package->orderBy('finish_transactions.created_time', 'asc')
            ->groupBy('finish_transactions.id')
            ->get()
            ->toArray();


        $query_single = DB::table('finish_transactions')
        ->select('finish_transactions.*', 'finish_transactions.insurance_id'  ,'finish_transaction_tests.package_id', 'finish_transaction_tests.test_name')
        ->leftjoin('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', 'finish_transactions.id')
   
             ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('finish_transaction_tests.package_id', '=', null);
            if($insurance_id != null & $insurance_id != 'null'){
       
                $query_single->where('finish_transactions.insurance_id', '=', $insurance_id);
        }
        $result_single = $query_single->orderBy('finish_transactions.created_time', 'asc')
            ->groupBy('finish_transaction_tests.id')
            ->get()
            ->toArray();

            $results = array_merge($result_package, $result_single);
            

        return DataTables::of($results)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function SupportActivitiesPrint($startDate = null, $endDate = null, $insurance_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }


        $query_package = DB::table('finish_transactions')
        ->select('finish_transactions.*', 'finish_transaction_tests.package_id', 'finish_transaction_tests.package_name')
            ->leftjoin('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', 'finish_transactions.id')
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('finish_transaction_tests.package_id', '!=', null);
            if($insurance_id != null && $insurance_id != 'null'){
                $query_package->where('finish_transactions.insurance_id', '=', $insurance_id);
        }
            $result_package = $query_package->orderBy('finish_transactions.created_time', 'asc')
            ->groupBy('finish_transactions.id')
            ->get()
            ->toArray();


        $query_single = DB::table('finish_transactions')
        ->select('finish_transactions.*', 'finish_transactions.insurance_id'  ,'finish_transaction_tests.package_id', 'finish_transaction_tests.test_name')
        ->leftjoin('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', 'finish_transactions.id')
   
             ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('finish_transaction_tests.package_id', '=', null);
            if($insurance_id != null & $insurance_id != 'null'){
       
                $query_single->where('finish_transactions.insurance_id', '=', $insurance_id);
        }
        $result_single = $query_single->orderBy('finish_transactions.created_time', 'asc')
            ->groupBy('finish_transaction_tests.id')
            ->get()
            ->toArray();

            $results = array_merge($result_package, $result_single);

        $data['supportData'] = $results;

        if($insurance_id != null & $insurance_id != 'null'){
            $insurances =  DB::table('insurances')->select('insurances.name as insurance_name')->where('insurances.id', $insurance_id)->first();

            $insurance_name = $insurances->insurance_name;
            $data['insurance'] = $insurance_name;
        }else{
            $data['insurance'] = '-';
        }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_support_activities.print', $data);
    }
    public function SupportActivitiesExcel($startDate = null, $endDate = null, $insurance_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }



        $query_package = DB::table('finish_transactions')
        ->select('finish_transactions.*', 'finish_transaction_tests.package_id', 'finish_transaction_tests.package_name')
            ->leftjoin('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', 'finish_transactions.id')
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('finish_transaction_tests.package_id', '!=', null);
            if($insurance_id != null && $insurance_id != 'null'){
                $query_package->where('finish_transactions.insurance_id', '=', $insurance_id);
        }
            $result_package = $query_package->orderBy('finish_transactions.created_time', 'asc')
            ->groupBy('finish_transactions.id')
            ->get()
            ->toArray();


        $query_single = DB::table('finish_transactions')
        ->select('finish_transactions.*', 'finish_transactions.insurance_id'  ,'finish_transaction_tests.package_id', 'finish_transaction_tests.test_name')
        ->leftjoin('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', 'finish_transactions.id')
   
             ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('finish_transaction_tests.package_id', '=', null);
            if($insurance_id != null & $insurance_id != 'null'){
       
                $query_single->where('finish_transactions.insurance_id', '=', $insurance_id);
        }
        $result_single = $query_single->orderBy('finish_transactions.created_time', 'asc')
            ->groupBy('finish_transaction_tests.id')
            ->get()
            ->toArray();

            $results = array_merge($result_package, $result_single);

        $data['supportData'] = $results;

        if($insurance_id != null & $insurance_id != 'null'){
            $insurances =  DB::table('insurances')->select('insurances.name as insurance_name')->where('insurances.id', $insurance_id)->first();

            $insurance_name = $insurances->insurance_name;
            $data['insurance'] = $insurance_name;
        }else{
            $data['insurance'] = '-';
        }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        $fileName = 'SUPPORT ACTIVITIES LAB REPORT.xlsx'; // Nama file Excel yang akan diunduh
    
        return Excel::download(new SupportActivitiesExport($data), $fileName);
    }


    //===============================================================================================
    // PHLEBOTOMY & SAMPLING REPORT
    //===============================================================================================
    public function flebotomiSamplingReport()
    {
        $data['title'] = 'Phlebotomy & Sampling Report';
        return view('dashboard.report.report_flebotomi_sampling.index', $data);
    }

    public function flebotomiSamplingDatatable($startDate = null, $endDate = null, $specimen_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = DB::table('finish_transactions')->selectRaw('finish_transactions.*, finish_transaction_tests.draw_time, finish_transaction_tests.draw_by_name');
        $model->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id');
        $model->where('finish_transactions.created_time', '>=', $from);
        $model->where('finish_transactions.created_time', '<=', $to);
        if ($specimen_id != null && $specimen_id != "null" && $specimen_id != 0) {
            $model->where('finish_transaction_tests.group_id', '=', $specimen_id);
        }
        $model->orderBy('finish_transactions.created_time', 'desc');
        $model->groupBy('finish_transactions.id');
         $data  =  $model->get();
        // $model->groupBy('finish_transaction_tests.specimen_id');
        // dd($data);

        return DataTables::of($data)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function flebotomiSamplingPrint($startDate = null, $endDate = null, $specimen_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')->selectRaw('finish_transactions.*, finish_transaction_tests.draw_time, finish_transaction_tests.draw_by_name');
        $query->leftJoin('finish_transaction_tests', 'finish_transactions.id', '=', 'finish_transaction_tests.finish_transaction_id');
        $query->where('finish_transactions.created_time', '>=', $from);
        $query->where('finish_transactions.created_time', '<=', $to);
        if ($specimen_id != null && $specimen_id != "null" && $specimen_id != 0) {
            $query->where('finish_transaction_tests.group_id', '=', $specimen_id);
        }
        $query->orderBy('finish_transactions.created_time', 'desc');
        $query->groupBy('finish_transactions.id');

        $data["samplingData"] = $query->get();

        if ($specimen_id != null && $specimen_id != "null" && $specimen_id != 0) {
            $query_specimen = DB::table('specimens')->select('name as specimen_name');
            $specimen = $query_specimen->first();
            $data["specimen"] = $specimen->specimen_name;
        } else {
            $data["specimen"] = '-';
        }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_flebotomi_sampling.print', $data);
    }

    //===============================================================================================
    // VERIFICATION & VALIDATION REPORT
    //===============================================================================================
    public function verificationValidationReport()
    {
        $data['title'] = 'Verification & Validation Report';
        return view('dashboard.report.report_verification_validation.index', $data);
    }

    public function verificationValidationDatatable($startDate = null, $endDate = null, $test_id = null)
    {
        if ($startDate == null && $endDate == null) {
            // $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            // $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
            $from = date('Y-m-d');
            $to = date('Y-m-d');
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = "SELECT users.id, users.name, 
            (SELECT COUNT(validate_by)
            FROM finish_transaction_tests tt1
            WHERE tt1.validate_by = users.id AND DATE(tt1.input_time) BETWEEN '" . $from . "' AND  '" . $to . "') AS validator,
            (SELECT COUNT(verify_by)
            FROM finish_transaction_tests tt2
            WHERE tt2.verify_by = users.id AND DATE(tt2.input_time) BETWEEN '" . $from . "' AND  '" . $to . "') AS verifikator
        FROM users
        ORDER BY validator DESC , verifikator desc";

        $model = DB::select(DB::raw($query));


    



        // $package = DB::table('finish_transaction_tests')
        // ->selectRaw('finish_transaction_tests.finish_transaction_id, finish_transaction_tests.test_id, finish_transaction_tests.package_id, 
        // finish_transaction_tests.validate_by_name ,COUNT(finish_transaction_tests.validate_by) as total_validate, COUNT(finish_transaction_tests.validate_by) as total_validate ,finish_transaction_tests.verify_by_name ,COUNT(finish_transaction_tests.verify_by) as total_verify ,finish_transactions.id , finish_transactions.created_time,  finish_transaction_tests.input_time')
        // ->leftJoin('finish_transactions', 'finish_transactions.id', 'finish_transaction_tests.finish_transaction_id')
        //     ->where('created_time', '>=', $from)
        //     ->where('created_time', '<=', $to)
        //     ->whereNotNull('package_id');
        // $package->groupBy('finish_transaction_tests.finish_transaction_id','test_id');
    
        //      $query_package = $package->get()->toArray();
    
        //      $packageExitsVerify = DB::table('finish_transaction_tests')
        //      ->selectRaw('finish_transaction_tests.finish_transaction_id, finish_transaction_tests.package_name, finish_transaction_tests.package_id, 
        //          users.name as verify_name, COUNT(finish_transaction_tests.verify_by) as total_verify, 
        //          COUNT(CASE WHEN finish_transaction_tests.validate_by IS NOT NULL THEN finish_transaction_tests.validate_by END) as total_validate, 
        //          finish_transactions.id, finish_transactions.created_time, finish_transaction_tests.input_time, users.id')
        //      ->leftJoin('finish_transactions', 'finish_transactions.id', 'finish_transaction_tests.finish_transaction_id')
        //      ->leftJoin('users', 'users.id', 'finish_transaction_tests.verify_by')
        //      ->where('created_time', '>=', $from)
        //      ->where('created_time', '<=', $to)
        //      ->where('finish_transaction_tests.verify_by', 'users.id')
        //      ->where('finish_transaction_tests.validate1_by', 'users.id')
        //      ->whereNotNull('package_id')
        //      ->groupBy('finish_transaction_tests.finish_transaction_id', 'package_id', 'users.id');
         
        //  $query_package_exits_verify = $packageExitsVerify->get()->toArray();
         

        //    echo '<pre>';
        //    print_r( $query_package_exits_verify );
        //    die;
           
        //     $packageExits  = DB::table('finish_transaction_tests')
        //     ->selectRaw('finish_transaction_tests.finish_transaction_id, finish_transaction_tests.package_name, finish_transaction_tests.package_id, 
        //     finish_transaction_tests.validate_by_name ,COUNT(finish_transaction_tests.validate_by) as total_validate, COUNT(finish_transaction_tests.verify_by) as total_verify ,finish_transaction_tests.verify_by_name ,COUNT(finish_transaction_tests.verify_by) as total_verify ,finish_transactions.id , finish_transactions.created_time,  finish_transaction_tests.input_time, users.id')
        //     ->leftJoin('finish_transactions', 'finish_transactions.id', 'finish_transaction_tests.finish_transaction_id')
        //         ->where('created_time', '>=', $from)
        //         ->where('created_time', '<=', $to)
        //         ->whereNotNull('package_id');
        //     $packageExits->groupBy('finish_transaction_tests.finish_transaction_id','package_id', 'users.id');
        //     // $package->groupBy('finish_transaction_tests.verrify_by', 'finish_transaction_tests.validate_by');
        //    $query_package_exits =  $packageExits->get()->toArray();


     
        //    foreach ($query_package_exits as $key => $value) {
        //     $query_package_exits[$key]->verify_by_name = $value->verify_by_name;
        //     $query_package_exits[$key]->total_verify = 1;
        //     $query_package_exits[$key]->validate_by_name = $value->validate_by_name;
        //     $query_package_exits[$key]->total_validate = 1;
        //    }

        //    $packages = array_merge($query_package, $query_package_exits);

        //    $data_ver_val = [];
    
        //    foreach($packages as $key => $value){
        //     $isDuplicate = false ;
    
        //     foreach ($data_ver_val as $uniqueKey => $uniqueValue) {
                
        //         if($value->verify_by_name === $uniqueValue->verify_by_name){
        //             $isDuplicate = true ;
        //         $data_ver_val[$uniqueKey]->total_verify += $value->total_verify;
        //         break;
        //         }

          
        //     }
    
        //     if(!$isDuplicate){
        //         $data_ver_val[] = $value;
                
        //     }
    
        //    }
        //    echo '<pre>';
        //    print_r($data_ver_val);
        //    die;

   
       
           return DataTables::of($model)
           ->addIndexColumn()
           ->escapeColumns([])
           ->make(true);

        // query 1
        // $user_query = DB::table('users')->select('users.id as user_id', 'users.name as user_name')->orderBy('name');
        // $user_data = $user_query->get();

        // foreach ($user_data as $key => $value) {
        //     echo 'Id : ' . $value->user_id . '<br>';
        //     echo 'User : ' . $value->user_name . '<br>';
        // }

        // return response()->json(['user' => $user_data, 'verification' => $verification_data, 'validation' => $validation_data]);

        // query 2
        // $query = DB::table('finish_transaction_tests');
        // $query->select('users.name as analyst_name', DB::raw('COUNT(finish_transaction_tests.verify_by) as jumlah_verifikasi'), DB::raw('COUNT(finish_transaction_tests.validate_by) as jumlah_validasi'));
        // $query->leftJoin('users', 'finish_transaction_tests.verify_by', '=', 'users.id');
        // if ($test_id != null) {
        //     $query->where('test_id', $test_id);
        // }
        // $query->groupBy('users.id');
        // $data = $query->get();

        // return response()->json($data);
    }

    public function verificationValidationPrint($startDate = null, $endDate = null, $test_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transaction_tests')->select('finish_transaction_tests.id', 'users.name as user_name', DB::raw('COUNT(finish_transaction_tests.verify_by) as jumlah_verifikasi'), DB::raw('COUNT(finish_transaction_tests.validate_by) as jumlah_validasi'));
        $query->leftJoin('users', 'finish_transaction_tests.verify_by', '=', 'users.id');
        $query->where('finish_transaction_tests.input_time', '>=', $from);
        $query->where('finish_transaction_tests.input_time', '<=', $to);
        if ($test_id != null && $test_id != "null" && $test_id != 0) {
            $query->where('test_id', $test_id);
        }
        $query->orderBy('users.name', 'asc');
        $query->groupBy('users.id');

        $data['verf_val_data'] = $query->get();

        if ($test_id != null && $test_id != "null" && $test_id != 0) {
            $query_test = DB::table('finish_transaction_tests')->select('finish_transaction_tests.test_name')->where('test_id', $test_id);
            $test = $query_test->first();
            $data["test_name"] = $test->test_name;
        } else {
            $data["test_name"] = '-';
        }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_verification_validation.print', $data);
    }

    //===============================================================================================
    // INSURANCE REPORT
    //===============================================================================================
    public function insuranceReport()
    {
        $data['title'] = 'Insurance Report';
        return view('dashboard.report.report_insurance.index', $data);
    }

    public function insuranceDatatable($startDate = null, $endDate = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = DB::table('finish_transactions')->select('id', 'created_time', 'insurance_name', DB::raw('COUNT(insurance_id) as total_pasien'))
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->orderBy('insurance_id', 'asc')
            ->groupBy('insurance_id');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function insurancePrint($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')->select('id', 'created_time', 'insurance_name', DB::raw('COUNT(insurance_id) as total_pasien'))
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->orderBy('insurance_id', 'asc')
            ->groupBy('insurance_id');

        $data['insuranceData'] = $query->get();

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_insurance.print', $data);
    }


    //===============================================================================================
    // INSURANCE REPORT RIKES
    //===============================================================================================
    public function insuranceRikesReport()
    {
        $data['title'] = 'RIKES Report';
        return view('dashboard.report.report_insurance_rikes.index', $data);
    }

    public function insuranceRikesDatatable($startDate = null, $endDate = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = DB::table('finish_transactions')->select('id', 'created_time', 'insurance_name', DB::raw('COUNT(insurance_id) as total_pasien'))
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('insurance_id', '=',  52)
            ->orderBy('insurance_id', 'asc')
            ->get();

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function insuranceRikesPrint($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')->select('id', 'created_time', 'insurance_name', DB::raw('COUNT(insurance_id) as total_pasien'))
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('insurance_id', '=',  52)
            ->orderBy('insurance_id', 'asc')
            ->groupBy('insurance_id');

        $data['insuranceData'] = $query->get();

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_insurance_rikes.print', $data);
    }

    //===============================================================================================
    // INSURANCE REPORT RIKES NIKAH
    //===============================================================================================
    public function insuranceNikahReport()
    {
        $data['title'] = 'RIKES Nikah Report';
        return view('dashboard.report.report_insurance_nikah.index', $data);
    }

    public function insuranceNikahDatatable($startDate = null, $endDate = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = DB::table('finish_transactions')->select('id', 'created_time', 'insurance_name', DB::raw('COUNT(insurance_id) as total_pasien'))
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('insurance_id', '=',  53)
            ->get();

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function insuranceNikahPrint($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')->select('id', 'created_time', 'insurance_name', DB::raw('COUNT(insurance_id) as total_pasien'))
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to)
            ->where('insurance_id', '=',  53)
            ->groupBy('insurance_id');

        $data['insuranceData'] = $query->get();

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_insurance_nikah.print', $data);
    }

    //===============================================================================================
    // BPJS REPORT
    //===============================================================================================
    public function bpjsReport()
    {
        $data['title'] = 'BPJS Report';
        return view('dashboard.report.report_bpjs.index', $data);

        // $from = '2023-04-12';
        // $to = '2023-04-12';

        // $query = DB::table('finish_transactions')
        //     ->select('finish_transactions.id', 'finish_transactions.transaction_id', 'finish_transactions.created_time', 'finish_transactions.patient_medrec', 'finish_transactions.patient_name', 'finish_transactions.room_name', 'finish_transaction_tests.group_id', 'finish_transaction_tests.group_name', 'finish_transaction_tests.package_name')
        //     ->leftJoin('finish_transaction_tests', 'finish_transactions.transaction_id', '=', 'finish_transaction_tests.transaction_id')
        //     ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
        //     ->where('finish_transactions.insurance_id', 2)
        //     ->orderBy('finish_transactions.created_time', 'desc')
        //     ->groupBy('finish_transactions.id')
        //     ->groupBy('finish_transaction_tests.group_id');
        // $patientData = $query->get();

        // $packageNameArray = [];
        // $testNameArray = [];
        // foreach ($patientData as $key => $value) {
        //     $packageNameArray = [];
        //     $testNameArray = [];

        //     $query_package = DB::table('finish_transaction_tests')->selectRaw('finish_transaction_id, group_id, package_name')
        //         ->where('package_id', '!=', NULL)
        //         ->where('group_id', $value->group_id)
        //         ->where('transaction_id', $value->transaction_id)
        //         ->groupBy('package_id');
        //     $packageData = $query_package->get();

        //     $query_single_test = DB::table('finish_transaction_tests')->selectRaw('finish_transaction_id, group_id, test_name')
        //         ->where('package_id', '=', NULL)
        //         ->where('group_id', $value->group_id)
        //         ->where('transaction_id', $value->transaction_id);
        //     $testData = $query_single_test->get();

        //     // echo '<pre>';
        //     // print_r($testData);

        //     // package name 
        //     if ($packageData) {
        //         foreach ($packageData as $packageData) {
        //             if ($value->group_id == $packageData->group_id) {
        //                 array_push($packageNameArray, $packageData->package_name);
        //             }
        //         }
        //     }
        //     if ($testData) {
        //         foreach ($testData as $test_data) {
        //             if ($value->group_id == $test_data->group_id) {
        //                 array_push($testNameArray, $test_data->test_name);
        //             }
        //         }
        //     }

        //     $packageNameString = implode("\n", $packageNameArray);
        //     $testNameString = implode("\n", $testNameArray);
        //     $patientData[$key]->package_name_custom = $packageNameString;
        //     $patientData[$key]->test_name_custom = $testNameString;
        // }

        // echo '<pre>';
        // print_r($patientData);
        // die;
    }

    public function bpjsDatatable($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
            ->select('finish_transactions.id', 'finish_transactions.transaction_id', 'finish_transactions.created_time', 'finish_transactions.patient_medrec', 'finish_transactions.patient_name', 'finish_transactions.room_name', 'finish_transaction_tests.group_id', 'finish_transaction_tests.group_name', 'finish_transaction_tests.package_name')
            ->leftJoin('finish_transaction_tests', 'finish_transactions.transaction_id', '=', 'finish_transaction_tests.transaction_id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->where('finish_transactions.insurance_id', 2)
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transactions.id')
            ->groupBy('finish_transaction_tests.group_id');
        $patientData = $query->get();

        $packageNameArray = [];
        $testNameArray = [];
        foreach ($patientData as $key => $value) {
            $packageNameArray = [];
            $testNameArray = [];

            $query_package = DB::table('finish_transaction_tests')->selectRaw('finish_transaction_id, group_id, package_name, sequence')
                ->where('package_id', '!=', NULL)
                ->where('group_id', $value->group_id)
                ->where('transaction_id', $value->transaction_id)
                ->orderBy('sequence')
                ->groupBy('package_id');
            $packageData = $query_package->get();

            $query_single_test = DB::table('finish_transaction_tests')->selectRaw('finish_transaction_id, group_id, test_name, sequence')
                ->where('package_id', '=', NULL)
                ->where('group_id', $value->group_id)
                ->where('transaction_id', $value->transaction_id)
                ->orderBy('sequence');
            $testData = $query_single_test->get();

            // echo '<pre>';
            // print_r($testData);

            // package name 
            if ($packageData) {
                foreach ($packageData as $packageData) {
                    if ($value->group_id == $packageData->group_id) {
                        array_push($packageNameArray, $packageData->package_name);
                    }
                }
            }
            if ($testData) {
                foreach ($testData as $test_data) {
                    if ($value->group_id == $test_data->group_id) {
                        array_push($testNameArray, $test_data->test_name);
                    }
                }
            }

            // $packageNameString = implode("\n", $packageNameArray);
            // $testNameString = implode("\n", $testNameArray);
            // $patientData[$key]->package_name_custom = $packageNameString;
            // $patientData[$key]->test_name_custom = $testNameString;

            $array_merge = array_merge($packageNameArray, $testNameArray);
            $testNameString = "<p>" . implode("<br>", $array_merge) . "</p>";
            $patientData[$key]->test_name_merge = $testNameString;
        }

        return DataTables::of($patientData)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function bpjsPrint($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transactions')
            ->select('finish_transactions.id', 'finish_transactions.transaction_id', 'finish_transactions.created_time', 'finish_transactions.patient_medrec', 'finish_transactions.patient_name', 'finish_transactions.room_name', 'finish_transaction_tests.group_id', 'finish_transaction_tests.group_name', 'finish_transaction_tests.package_name')
            ->leftJoin('finish_transaction_tests', 'finish_transactions.transaction_id', '=', 'finish_transaction_tests.transaction_id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->where('finish_transactions.insurance_id', 2)
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transactions.id')
            ->groupBy('finish_transaction_tests.group_id');
        $patientData = $query->get();

        $packageNameArray = [];
        $testNameArray = [];
        foreach ($patientData as $key => $value) {
            $packageNameArray = [];
            $testNameArray = [];

            $query_package = DB::table('finish_transaction_tests')->selectRaw('finish_transaction_id, group_id, package_name, sequence')
                ->where('package_id', '!=', NULL)
                ->where('group_id', $value->group_id)
                ->where('transaction_id', $value->transaction_id)
                ->orderBy('sequence')
                ->groupBy('package_id');
            $packageData = $query_package->get();

            $query_single_test = DB::table('finish_transaction_tests')->selectRaw('finish_transaction_id, group_id, test_name, sequence')
                ->where('package_id', '=', NULL)
                ->where('group_id', $value->group_id)
                ->where('transaction_id', $value->transaction_id)
                ->orderBy('sequence');
            $testData = $query_single_test->get();

            // echo '<pre>';
            // print_r($testData);

            // package name 
            if ($packageData) {
                foreach ($packageData as $packageData) {
                    if ($value->group_id == $packageData->group_id) {
                        array_push($packageNameArray, $packageData->package_name);
                    }
                }
            }
            if ($testData) {
                foreach ($testData as $test_data) {
                    if ($value->group_id == $test_data->group_id) {
                        array_push($testNameArray, $test_data->test_name);
                    }
                }
            }

            // $packageNameString = implode("\n", $packageNameArray);
            // $testNameString = implode("\n", $testNameArray);
            // $patientData[$key]->package_name_custom = $packageNameString;
            // $patientData[$key]->test_name_custom = $testNameString;

            $array_merge = array_merge($packageNameArray, $testNameArray);
            $testNameString = "<p>" . implode("<br>", $array_merge) . "</p>";
            $patientData[$key]->test_name_merge = $testNameString;
        }

        $data['patientData'] = $patientData;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_bpjs.print', $data);
    }

    //===============================================================================================
    // BILLING REPORT
    //===============================================================================================
    public function billingReport()
    {
        $data['title'] = 'Billing Report';
        return view('dashboard.report.report_billing.index', $data);
    }

    public function billingDatatable($startDate = null, $endDate = null, $insurance_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->startOfDay();
            $to   = Carbon::today()->endOfDay();
        } else {
            $from = Carbon::parse($startDate)->startOfDay();
            $to   = Carbon::parse($endDate)->endOfDay();
        }

        $model = DB::table('finish_transactions')
        ->leftJoin('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
        ->leftJoin('prices as pp', 'pp.package_id', '=', 'finish_transaction_tests.package_id')
        ->leftJoin('prices as pt', 'pt.test_id', '=', 'finish_transaction_tests.test_id')
        ->selectRaw("
            finish_transactions.id,
            finish_transactions.patient_name,
            finish_transactions.patient_medrec,
            finish_transactions.no_lab,
            finish_transactions.insurance_id,
            finish_transactions.insurance_name,
            finish_transactions.created_time,

            GROUP_CONCAT(DISTINCT finish_transaction_tests.package_id) AS package_id,

            GROUP_CONCAT(DISTINCT 
                CASE
                    WHEN finish_transaction_tests.package_id IS NOT NULL
                        AND finish_transaction_tests.package_id != 0
                    THEN finish_transaction_tests.package_name
                    ELSE finish_transaction_tests.test_name
                END
            ) AS combined_tests,

            GROUP_CONCAT(DISTINCT 
                CASE
                    WHEN finish_transaction_tests.package_id IS NOT NULL
                        AND finish_transaction_tests.package_id != 0
                    THEN pp.price
                    ELSE pt.price
                END
            ) AS total_prices
        ");

        if ($insurance_id && $insurance_id != "null" && $insurance_id != 0) {
            $model->where('finish_transactions.insurance_id', $insurance_id);
        }

        $model->whereBetween('finish_transactions.created_time', [$from, $to])
            ->groupBy('finish_transactions.id')
            ->orderBy('finish_transactions.created_time', 'desc');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function billingPrint($startDate = null, $endDate = null, $insurance_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->startOfDay();
            $to   = Carbon::today()->endOfDay();
        } else {
            $from = Carbon::parse($startDate)->startOfDay();
            $to   = Carbon::parse($endDate)->endOfDay();
        }

        $model = DB::table('finish_transactions')
        ->leftJoin('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
        ->leftJoin('prices as pp', 'pp.package_id', '=', 'finish_transaction_tests.package_id')
        ->leftJoin('prices as pt', 'pt.test_id', '=', 'finish_transaction_tests.test_id')
        ->selectRaw("
            finish_transactions.id,
            finish_transactions.patient_name,
            finish_transactions.patient_medrec,
            finish_transactions.no_lab,
            finish_transactions.insurance_id,
            finish_transactions.insurance_name,
            finish_transactions.created_time,

            GROUP_CONCAT(DISTINCT finish_transaction_tests.package_id) AS package_id,

            GROUP_CONCAT(DISTINCT 
                CASE
                    WHEN finish_transaction_tests.package_id IS NOT NULL
                        AND finish_transaction_tests.package_id != 0
                    THEN finish_transaction_tests.package_name
                    ELSE finish_transaction_tests.test_name
                END
            ) AS combined_tests,

            GROUP_CONCAT(DISTINCT 
                CASE
                    WHEN finish_transaction_tests.package_id IS NOT NULL
                        AND finish_transaction_tests.package_id != 0
                    THEN pp.price
                    ELSE pt.price
                END
            ) AS total_prices
        ");

        if ($insurance_id && $insurance_id != "null" && $insurance_id != 0) {
            $model->where('finish_transactions.insurance_id', $insurance_id);
        }

        $model->whereBetween('finish_transactions.created_time', [$from, $to])
            ->groupBy('finish_transactions.id')
            ->orderBy('finish_transactions.created_time', 'desc');

        $data["billingData"] = $model->get();

        if ($insurance_id != null && $insurance_id != "null" && $insurance_id != 0) {
            $query_insurance = DB::table('insurances')->select('name as insurance_name');
            $insurance = $query_insurance->first();
            $data["insurance"] = $insurance->insurance_name;
        } else {
            $data["insurance"] = '-';
        }

        if (!empty($startDate) && !empty($endDate)) {
            $data["startDate"] = Carbon::parse($startDate)->format('d/m/Y');
            $data["endDate"] = Carbon::parse($endDate)->format('d/m/Y');
        } else {
            $today = Carbon::today();
            $data["startDate"] = $today->format('d/m/Y');
            $data["endDate"] = $today->format('d/m/Y');
        }

        return view('dashboard.report.report_billing.print', $data);
    }

    public function billingExcel($startDate = null, $endDate = null, $insurance_id = null)
    {
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->startOfDay();
            $to   = Carbon::today()->endOfDay();
        } else {
            $from = Carbon::parse($startDate)->startOfDay();
            $to   = Carbon::parse($endDate)->endOfDay();
        }

        $model = DB::table('finish_transactions')
        ->leftJoin('finish_transaction_tests', 'finish_transaction_tests.finish_transaction_id', '=', 'finish_transactions.id')
        ->leftJoin('prices as pp', 'pp.package_id', '=', 'finish_transaction_tests.package_id')
        ->leftJoin('prices as pt', 'pt.test_id', '=', 'finish_transaction_tests.test_id')
        ->selectRaw("
            finish_transactions.id,
            finish_transactions.patient_name,
            finish_transactions.patient_medrec,
            finish_transactions.no_lab,
            finish_transactions.insurance_id,
            finish_transactions.insurance_name,
            finish_transactions.created_time,

            GROUP_CONCAT(DISTINCT finish_transaction_tests.package_id) AS package_id,

            GROUP_CONCAT(DISTINCT 
                CASE
                    WHEN finish_transaction_tests.package_id IS NOT NULL
                        AND finish_transaction_tests.package_id != 0
                    THEN finish_transaction_tests.package_name
                    ELSE finish_transaction_tests.test_name
                END
            ) AS combined_tests,

            GROUP_CONCAT(DISTINCT 
                CASE
                    WHEN finish_transaction_tests.package_id IS NOT NULL
                        AND finish_transaction_tests.package_id != 0
                    THEN pp.price
                    ELSE pt.price
                END
            ) AS total_prices
        ");

        if ($insurance_id && $insurance_id != "null" && $insurance_id != 0) {
            $model->where('finish_transactions.insurance_id', $insurance_id);
        }

        $model->whereBetween('finish_transactions.created_time', [$from, $to])
            ->groupBy('finish_transactions.id')
            ->orderBy('finish_transactions.created_time', 'desc');

        $insurance = DB::table('insurances')->where('id', $insurance_id)->first();

        $data["billingData"] = $model->get();

        if ($insurance_id != null && $insurance_id != "null" && $insurance_id != 0) {
            $query_insurance = DB::table('insurances')->select('name as insurance_name');
            $insurance = $query_insurance->first();
            $data["insurance"] = $insurance->insurance_name;
        } else {
            $data["insurance"] = '-';
        }

        $data["total_patients"] = $data["billingData"]->count();
        $totalIncome = 0;
        foreach ($data["billingData"] as $item) {
            $prices = explode(",", $item->total_prices);
            $totalIncome += array_sum($prices);
        }
        $data["total_incomes"] = $totalIncome;
        $data["startDate"] = $startDate ? date('d/m/Y', strtotime($startDate)) : '-';
        $data["endDate"] = $endDate ? date('d/m/Y', strtotime($endDate)) : '-';

        $fileName = 'Billing Report.xlsx';
        return Excel::download(new BillingExport($data), $fileName);
    }

    //===============================================================================================
    // DOCTOR REPORT
    //===============================================================================================
    public function doctorReport()
    {
        $data['title'] = 'Doctor Report';
        return view('dashboard.report.report_doctor.index', $data);
    }

    public function doctorDatatable($startDate = null, $endDate = null, $doctor_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $model = \App\FinishTransaction::selectRaw('COUNT(finish_transactions.patient_id) as total_patient, finish_transactions.id, finish_transactions.doctor_name, finish_transactions.created_time')
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to);
        if ($doctor_id != null && $doctor_id != "null" && $doctor_id != 0) {
            $model->where('finish_transactions.doctor_id', '=', $doctor_id);
        }
        $model->groupBy('doctor_id');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function doctorPrint($startDate = null, $endDate = null, $doctor_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = \App\FinishTransaction::selectRaw('COUNT(finish_transactions.patient_id) as total_patient, finish_transactions.id, finish_transactions.doctor_name, finish_transactions.created_time')
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to);
        if ($doctor_id != null && $doctor_id != "null" && $doctor_id != 0) {
            $query->where('finish_transactions.doctor_id', '=', $doctor_id);
        }
        $query->groupBy('doctor_id');

        $data["doctorData"] = $query->get();

        if ($doctor_id != null && $doctor_id != "null" && $doctor_id != 0) {
            $query_doctor = DB::table('doctors')->select('doctors.*')->where('id', $doctor_id);
            $doctor = $query_doctor->first();
            $data["doctor"] = $doctor->name;
        } else {
            $data["doctor"] = '-';
        }

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_doctor.print', $data);
    }

    //===============================================================================================
    // VISIT REPORT
    //===============================================================================================
    public function visitReport()
    {
        $data['title'] = 'Visit Report';
        return view('dashboard.report.report_visit.index', $data);
    }

    public function visitDatatable($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $queryPatientData = DB::table('finish_transactions')->selectRaw('id, patient_id, patient_medrec, patient_name, COUNT(patient_id) as total_visit, created_time')
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to);
        $queryPatientData->groupBy('patient_id');
        $patientData = $queryPatientData->get();

        return DataTables::of($patientData)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function visitPrint($startDate = null, $endDate = null, $doctor_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $queryPatientData = DB::table('finish_transactions')->selectRaw('id, patient_id, patient_medrec, patient_name, COUNT(patient_id) as total_visit, created_time')
            ->where('created_time', '>=', $from)
            ->where('created_time', '<=', $to);
        $queryPatientData->groupBy('patient_id');
        $patientData = $queryPatientData->get();

        $data['patientData'] = $patientData;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_visit.print', $data);
    }

    //===============================================================================================
    // ANALYZER REPORT
    //===============================================================================================
    public function analyzerReport()
    {
        $data['title'] = 'Analyzer Report';
        return view('dashboard.report.report_analyzer.index', $data);
        
    }

    public function analyzerDatatable($startDate = null, $endDate = null, $test_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $analyzerDataTest = DB::table('transaction_tests')
            ->selectRaw(
                'transaction_tests.test_id,
                transaction_tests.analyzer_id,
                tests.name as test_name,
                tests.initial as test_initial,
                analyzers.name as analyzer_name,
                COUNT(transaction_tests.test_id) as total_test'
            )
            ->leftJoin('transactions', 'transaction_tests.transaction_id', '=', 'transactions.id')
            ->leftJoin('tests', 'transaction_tests.test_id', '=', 'tests.id')
            ->leftJoin('analyzers', 'transaction_tests.analyzer_id', '=', 'analyzers.id')
            ->whereRaw("DATE(transactions.created_time) BETWEEN ? AND ?", [$from, $to])
            ->whereNotNull('transaction_tests.analyzer_id')
            ->where('transaction_tests.mark_duplo', '!=', 2)
            ->where('transaction_tests.test_id', '!=', 145)
            ->groupBy(
                'test_name',
                'transaction_tests.test_id',
                'transaction_tests.analyzer_id',
                'analyzer_name',
            )
            ->orderBy('analyzer_name', 'asc')
            ->orderBy('test_name', 'asc')
            ->get();

        $test143Totals = $analyzerDataTest
            ->where('test_id', 143)
            ->keyBy('analyzer_id');

            foreach ($analyzerDataTest as $row) {
            if ($row->test_id == 1435) {
                if (isset($test143Totals[$row->analyzer_id])) {
                    $row->total_test = $test143Totals[$row->analyzer_id]->total_test;
                } else {
                    $row->total_test = 0;
                }
            }
        }

        return DataTables::of($analyzerDataTest)
        ->addIndexColumn()
        ->escapeColumns([])
        ->make(true);
    }

    public function analyzerPrint($startDate = null, $endDate = null, $test_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $analyzerDataTest = DB::table('transaction_tests')
            ->selectRaw(
                'transaction_tests.test_id,
                transaction_tests.analyzer_id,
                tests.name as test_name,
                tests.initial as test_initial,
                analyzers.name as analyzer_name,
                COUNT(transaction_tests.test_id) as total_test'
            )
            ->leftJoin('transactions', 'transaction_tests.transaction_id', '=', 'transactions.id')
            ->leftJoin('tests', 'transaction_tests.test_id', '=', 'tests.id')
            ->leftJoin('analyzers', 'transaction_tests.analyzer_id', '=', 'analyzers.id')
            ->whereRaw("DATE(transactions.created_time) BETWEEN ? AND ?", [$from, $to])
            ->whereNotNull('transaction_tests.analyzer_id')
            ->where('transaction_tests.mark_duplo', '!=', 2)
            ->where('transaction_tests.test_id', '!=', 145)
            ->groupBy(
                'test_name',
                'transaction_tests.test_id',
                'transaction_tests.analyzer_id',
                'analyzer_name',
            )
            ->orderBy('analyzer_name', 'asc')
            ->orderBy('test_name', 'asc')
            ->get();

        $data['analyzerData'] =  $analyzerDataTest;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_analyzer.print', $data);
    }

      public function analyzerExport($startDate = null, $endDate = null, $test_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query_test = DB::table('finish_transaction_tests')
        ->selectRaw(
            'finish_transaction_tests.test_name,
            finish_transaction_tests.test_id,
            finish_transaction_tests.analyzer_id, 
            finish_transaction_tests.analyzer_name, 
            COUNT(finish_transaction_tests.test_id) as total_test'
        )
        ->leftJoin('finish_transactions', 'finish_transaction_tests.transaction_id', '=', 'finish_transactions.transaction_id')
        ->whereRaw("DATE(finish_transactions.created_time) BETWEEN ? AND ?", [$from, $to])
        ->whereNotNull('finish_transaction_tests.analyzer_id')
        ->groupBy(
            'finish_transaction_tests.test_name',
            'finish_transaction_tests.test_id',
            'finish_transaction_tests.analyzer_id',
            'finish_transaction_tests.analyzer_name',
        )
        ->orderBy('finish_transaction_tests.analyzer_name', 'asc')
        ->orderBy('finish_transaction_tests.test_name', 'asc');
        $analyzerDataTest = $query_test->get();

        $data['analyzerData'] =  $analyzerDataTest;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        // dd($data);

        return view('dashboard.report.report_analyzer.print', $data);
    }
    //===============================================================================================
    // USER REPORT
    //===============================================================================================
    public function userReport()
    {
        $data['title'] = 'User Report';
        return view('dashboard.report.report_user.index', $data); 
    }

    public function userDatatable($startDate = null, $endDate = null, $test_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('users')->select('id as user_id', 'name as user');
        $userData = $query->get();

        $query_process = DB::table('finish_transaction_tests')
            ->select(
                'finish_transactions.transaction_id',
                'finish_transactions.created_time',
                'finish_transactions.no_lab',
                'finish_transaction_tests.type',
                'finish_transaction_tests.package_id',
                'finish_transaction_tests.package_name',
                'finish_transaction_tests.test_id',
                'finish_transaction_tests.test_name',
                'finish_transactions.checkin_by as checkin_by_id',
                'users.name as checkin_by_name',
                'finish_transaction_tests.draw_by as draw_by_id',
                'finish_transaction_tests.draw_by_name',
                'finish_transaction_tests.verify_by as verify_by_id',
                'finish_transaction_tests.verify_by_name',
                'finish_transaction_tests.validate_by as validate_by_id',
                'finish_transaction_tests.validate_by_name'
            )
            ->leftJoin('finish_transactions', 'finish_transaction_tests.transaction_id', '=', 'finish_transactions.transaction_id')
            ->leftJoin('users', 'finish_transactions.checkin_by', '=', 'users.id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transaction_tests.package_id')
            ->groupBy('finish_transactions.transaction_id');
        $processData = $query_process->get();

        // echo '<pre>';
        // print_r($processData);
        // die;

        foreach($userData as $key => $value){
            
            if(!isset($userData[$key]->checkin)){
                $userData[$key]->checkin = 0;
            }
            if(!isset($userData[$key]->draw)){
                $userData[$key]->draw = 0;
            }
            if(!isset($userData[$key]->process)){
                $userData[$key]->process = 0;
            }
            if(!isset($userData[$key]->verify)){
                $userData[$key]->verify = 0;
            }
            if(!isset($userData[$key]->validate)){
                $userData[$key]->validate = 0;
            }

            foreach($processData as $process_data){
                if($value->user_id == $process_data->checkin_by_id){
                    $userData[$key]->checkin++;
                }
                if($value->user_id == $process_data->draw_by_id){
                    $userData[$key]->draw++;
                }
                if($value->user_id == $process_data->draw_by_id){
                    $userData[$key]->process++;
                }
                if($value->user_id == $process_data->verify_by_id){
                    $userData[$key]->verify++;
                }
                if($value->user_id == $process_data->validate_by_id){
                    $userData[$key]->validate++;
                }
            }
            
        }

        // echo '<pre>';
        // print_r($userData);

        return DataTables::of($userData)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function userPrint($startDate = null, $endDate = null, $test_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('users')->select('id as user_id', 'name as user');
        $userData = $query->get();

        $query_process = DB::table('finish_transaction_tests')
            ->select(
                'finish_transactions.transaction_id',
                'finish_transactions.created_time',
                'finish_transactions.no_lab',
                'finish_transaction_tests.type',
                'finish_transaction_tests.package_id',
                'finish_transaction_tests.package_name',
                'finish_transaction_tests.test_id',
                'finish_transaction_tests.test_name',
                'finish_transactions.checkin_by as checkin_by_id',
                'users.name as checkin_by_name',
                'finish_transaction_tests.draw_by as draw_by_id',
                'finish_transaction_tests.draw_by_name',
                'finish_transaction_tests.verify_by as verify_by_id',
                'finish_transaction_tests.verify_by_name',
                'finish_transaction_tests.validate_by as validate_by_id',
                'finish_transaction_tests.validate_by_name'
            )
            ->leftJoin('finish_transactions', 'finish_transaction_tests.transaction_id', '=', 'finish_transactions.transaction_id')
            ->leftJoin('users', 'finish_transactions.checkin_by', '=', 'users.id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transaction_tests.package_id')
            ->groupBy('finish_transactions.transaction_id');
        $processData = $query_process->get();

        // echo '<pre>';
        // print_r($processData);
        // die;

        foreach($userData as $key => $value){
            
            if(!isset($userData[$key]->checkin)){
                $userData[$key]->checkin = 0;
            }
            if(!isset($userData[$key]->draw)){
                $userData[$key]->draw = 0;
            }
            if(!isset($userData[$key]->process)){
                $userData[$key]->process = 0;
            }
            if(!isset($userData[$key]->verify)){
                $userData[$key]->verify = 0;
            }
            if(!isset($userData[$key]->validate)){
                $userData[$key]->validate = 0;
            }

            foreach($processData as $process_data){
                if($value->user_id == $process_data->checkin_by_id){
                    $userData[$key]->checkin++;
                }
                if($value->user_id == $process_data->draw_by_id){
                    $userData[$key]->draw++;
                }
                if($value->user_id == $process_data->draw_by_id){
                    $userData[$key]->process++;
                }
                if($value->user_id == $process_data->verify_by_id){
                    $userData[$key]->verify++;
                }
                if($value->user_id == $process_data->validate_by_id){
                    $userData[$key]->validate++;
                }
            }
            
        }

        // echo '<pre>';
        // print_r($userData);

        $data['userData'] = $userData;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_user.print', $data);
    }

    //===============================================================================================
    // USER PROCESS REPORT
    //===============================================================================================
    public function userProcessReport()
    {
        $data['title'] = 'User Process Report';
        return view('dashboard.report.report_user_process.index', $data); 
    }

    public function userProcessDatatable($startDate = null, $endDate = null, $test_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transaction_tests')
            ->select(
                'finish_transactions.transaction_id',
                'finish_transactions.created_time',
                'finish_transactions.no_lab',
                'finish_transaction_tests.type',
                'finish_transaction_tests.package_id',
                'finish_transaction_tests.package_name',
                'finish_transaction_tests.test_id',
                'finish_transaction_tests.test_name',
                'users.name as checkin_by_name',
                'finish_transaction_tests.draw_by_name',
                'finish_transaction_tests.verify_by_name',
                'finish_transaction_tests.validate_by_name'
            )
            ->leftJoin('finish_transactions', 'finish_transaction_tests.transaction_id', '=', 'finish_transactions.transaction_id')
            ->leftJoin('users', 'finish_transactions.checkin_by', '=', 'users.id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transaction_tests.package_id')
            ->groupBy('finish_transactions.transaction_id');
        $processData = $query->get();

        return DataTables::of($processData)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function userProcessPrint($startDate = null, $endDate = null, $test_id = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }

        $query = DB::table('finish_transaction_tests')
            ->select(
                'finish_transactions.transaction_id',
                'finish_transactions.created_time',
                'finish_transactions.no_lab',
                'finish_transaction_tests.type',
                'finish_transaction_tests.package_id',
                'finish_transaction_tests.package_name',
                'finish_transaction_tests.test_id',
                'finish_transaction_tests.test_name',
                'users.name as checkin_by_name',
                'finish_transaction_tests.draw_by_name',
                'finish_transaction_tests.verify_by_name',
                'finish_transaction_tests.validate_by_name'
            )
            ->leftJoin('finish_transactions', 'finish_transaction_tests.transaction_id', '=', 'finish_transactions.transaction_id')
            ->leftJoin('users', 'finish_transactions.checkin_by', '=', 'users.id')
            ->whereRaw("date(finish_transactions.created_time) between '" . $from . "' and '" . $to . "'")
            ->orderBy('finish_transactions.created_time', 'desc')
            ->groupBy('finish_transaction_tests.package_id')
            ->groupBy('finish_transactions.transaction_id');
        $processData = $query->get();

        $data['processData'] = $processData;

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_user_process.print', $data);
    }

    //===============================================================================================
    // RL38 REPORT
    //===============================================================================================
    public function rl38Report()
    {
        $data['title'] = 'RL 3.8 Report';
        return view('dashboard.report.report_rl38.index', $data);
    }

    public function rl38Datatable($startDate = null, $endDate = null, $test_id = null)
    {
        // dd($test_id);
        if (!$startDate && !$endDate) {
            $from = Carbon::today()->startOfDay();
            $to = Carbon::today()->endOfDay();
        } else {
            $from = Carbon::parse($startDate)->startOfDay();
            $to = Carbon::parse($endDate)->endOfDay();
        }

        $mapKategori = [
            'hematologi' => [113,114,765,112,110,766,521,837,120],
            'kimia_klinik' => [163,139,540,133,134,151,128,129,137,130,135,136,131,518,695,138,542,132,514,763,140,1283,785,555,165,164,598,162,730,618,144,732,734,735,736,737,1337],
            'imunologi_klinik' => [532,845,995,520,502,690,1171,1249,1133,1187,557,625,972,1058,1262,1276,519,981,787,678,1417,507,898,1089,1184,793,1036,1049,1185,794,1037,1050,1186,1416],
            'urinalisis_dan_analisis_cairan' => [147,139,150,151,853],
            'hemostasis' => [523,522,915,1223,1319,1194,916,809],
        ];

        $allTestIds = collect($mapKategori)->flatten()->unique()->values();

        $normalRows = DB::table('finish_transaction_tests as ftt')
            ->selectRaw('
                ftt.test_id,
                ftt.test_name,

                COUNT(DISTINCT ftt.transaction_id) as total_test,

                COUNT(DISTINCT CASE 
                    WHEN ft.patient_gender = "M" 
                    THEN ftt.transaction_id 
                END) as total_male,

                COUNT(DISTINCT CASE 
                    WHEN ft.patient_gender = "F" 
                    THEN ftt.transaction_id 
                END) as total_female,

                AVG(CASE 
                    WHEN ft.patient_gender = "M"
                    THEN CAST(ftt.global_result AS DECIMAL(10,2))
                END) as avg_male,

                AVG(CASE 
                    WHEN ft.patient_gender = "F"
                    THEN CAST(ftt.global_result AS DECIMAL(10,2))
                END) as avg_female
            ')
            ->join('finish_transactions as ft', 'ftt.transaction_id', '=', 'ft.transaction_id')
            ->whereIn('ftt.test_id', $allTestIds)
            ->whereBetween('ft.created_time', [$from, $to])
            ->groupBy('ftt.test_id', 'ftt.test_name')
        ->get();

        $normalRows = $normalRows->map(function ($row) use ($mapKategori) {
            foreach ($mapKategori as $kategori => $ids) {

                if (in_array($row->test_id, $ids)) {
                    $row->kategori = $kategori;
                    break;
                }

            }

            return $row;

        });

        $tbcRows = DB::table('finish_transaction_tests as ftt')
            ->selectRaw('
                ftt.test_id,
                CONCAT(ftt.test_name, " - ", ftt.global_result) as test_name,

                COUNT(DISTINCT ftt.transaction_id) as total_test,

                COUNT(DISTINCT CASE 
                    WHEN ft.patient_gender = "M"
                    THEN ftt.transaction_id
                END) as total_male,

                COUNT(DISTINCT CASE 
                    WHEN ft.patient_gender = "F"
                    THEN ftt.transaction_id
                END) as total_female,

                NULL as avg_male,
                NULL as avg_female
            ')
            ->join('finish_transactions as ft', 'ftt.transaction_id', '=', 'ft.transaction_id')
            ->where('ftt.test_id', 1270)
            ->whereBetween('ft.created_time', [$from, $to])
            ->groupBy(
                'ftt.test_id',
                'ftt.test_name',
                'ftt.result_label'
            )
        ->get()
        ->map(function ($row) {

            $row->kategori = 'tbc_bakteri';

            return $row;

        });

        // $baseQuery = function ($testIds) use ($from, $to) {
        //     return DB::table('finish_transaction_tests as ftt')
        //         ->selectRaw('
        //             ftt.test_id,
        //             ftt.test_name,
        //             COUNT(DISTINCT ftt.transaction_id) as total_test,
        //             COUNT(DISTINCT CASE WHEN ft.patient_gender = "M" THEN ftt.transaction_id END) as total_male,
        //             COUNT(DISTINCT CASE WHEN ft.patient_gender = "F" THEN ftt.transaction_id END) as total_female,

        //             AVG(CASE 
        //                 WHEN ft.patient_gender = "M" 
        //                 THEN CAST(ftt.global_result AS DECIMAL(10,2)) 
        //             END) as avg_male,

        //             AVG(CASE 
        //                 WHEN ft.patient_gender = "F" 
        //                 THEN CAST(ftt.global_result AS DECIMAL(10,2)) 
        //             END) as avg_female
        //         ')
        //         ->join('finish_transactions as ft', 'ftt.transaction_id', '=', 'ft.transaction_id')
        //         ->whereIn('ftt.test_id', $testIds)
        //         ->whereBetween('ft.created_time', [$from, $to])
        //         ->groupBy('ftt.test_id', 'ftt.test_name');
        // };

        // $mapKategori = [
        //     'hematologi' => [113,114,765,112,110,766,521,837,120],
        //     'kimia_klinik' => [163,139,540,133,134,151,128,129,137,130,135,136,131,518,695,138,542,132,514,763,140,1283,785,555,165,164,598,162,730,618,144,732,734,735,736,737,1337],
        //     'imunologi_klinik' => [532,845,995,520,502,690,1171,1249,1133,1187,557,625,972,1058,1262,1276,519,981,787,678,1417,507,898,1089,1184,793,1036,1049,1185,794,1037,1050,1186,1416],
        //     'urinalisis_dan_analisis_cairan' => [147,139,150,151,853],
        //     'hemostasis' => [523,522,915,1223,1319,1194,916,809],
        // ];

        // $data = collect();

        // foreach ($mapKategori as $kategori => $testIds) {
        //     $rows = $baseQuery($testIds)->get()
        //         ->map(function ($item) use ($kategori) {
        //             $item->kategori = $kategori;
        //             return $item;
        //         });

        //     $data = $data->merge($rows);
        // }

        $data = $normalRows->merge($tbcRows);

        return DataTables::of($data)
        ->addIndexColumn()
        ->escapeColumns([])
        ->make(true);
    }

    public function rl38Print($startDate = null, $endDate = null, $test_id = null)
    {
        if (!$startDate && !$endDate) {
            $from = Carbon::today()->startOfDay();
            $to = Carbon::today()->endOfDay();
        } else {
            $from = Carbon::parse($startDate)->startOfDay();
            $to = Carbon::parse($endDate)->endOfDay();
        }

        $query_package = DB::table('finish_transaction_tests as ftt')
        ->select(
            'ftt.package_id as test_id',
            'ftt.package_name as test_name',
            DB::raw("'package' as type"),
            DB::raw('COUNT(DISTINCT ftt.transaction_id) as total_test')
        )
        ->join('finish_transactions as ft', 'ftt.transaction_id', '=', 'ft.transaction_id')
        ->whereNotNull('ftt.package_id')
        ->whereBetween('ft.created_time', [$from, $to])
        ->groupBy('ftt.package_id', 'ftt.package_name');

        if ($test_id && $test_id != "null") {
            $query_package->where('ftt.package_id', $test_id);
        }

        $packageData = $query_package->get();

        $query_single = DB::table('finish_transaction_tests as ftt')
        ->select(
            'ftt.test_id',
            'ftt.test_name',
            DB::raw("'single' as type"),
            DB::raw('COUNT(*) as total_test')
        )
        ->join('finish_transactions as ft', 'ftt.transaction_id', '=', 'ft.transaction_id')
        ->whereNull('ftt.package_id')
        ->whereBetween('ft.created_time', [$from, $to])
        ->groupBy('ftt.test_id', 'ftt.test_name');

        if ($test_id && $test_id != "null") {
            $query_single->where('ftt.test_id', $test_id);
        }

        $singleData = $query_single->get();

        $dataReport = $packageData->merge($singleData);

        $data['testData'] = $dataReport;

        // dd($data);

        if ($startDate != null && $endDate != null) {
            $data["startDate"] = date('d/m/Y', strtotime($startDate));
            $data["endDate"] = date('d/m/Y', strtotime($endDate));
        } else {
            $data["startDate"] = '-';
            $data["endDate"] = '-';
        }

        return view('dashboard.report.report_test.print', $data);
    }
}
