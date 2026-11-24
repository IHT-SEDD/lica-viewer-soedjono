<?php

namespace App\Http\Controllers;

use App\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class ViewerController extends Controller
{
    const STATUS = 2;

    protected $masters = [
        'test' => 'App\Test',
        'grand-package' => 'App\GrandPackage',
        'package' => 'App\Package',
        'patient' => 'App\Patient',
        'group' => 'App\Group',
        'analyzer' => 'App\Analyzer',
        'specimen' => 'App\Specimen',
        'doctor' => 'App\Doctor',
        'insurance' => 'App\Insurance',
        'price' => 'App\Price',
        'room' => 'App\Room',
        'range' => 'App\Range',
        'interfacing' => 'App\Interfacing',
        'general_code_test' => 'App\GeneralCodeTest',
        'result' => 'App\Result',
        'formula' => 'App\Formula',
        'product' => 'App\Product',
    ];

    public function index()
    {
        $data['title'] = 'Portal';
        return view('viewer.index', $data);
    }

    public function indexResult($medrec, Request $request)
    {
        try {
            if ($request->query('granted') !== 'true') {
                return redirect()->route('viewer')
                    ->with('error', 'Akses tidak valid.');
            }

            try {
                $realMedrec = decrypt($medrec);
            } catch (\Exception $e) {
                return redirect()->route('viewer')
                    ->with('error', 'Data medrec tidak valid.');
            }

            $patient = Patient::where('medrec', $realMedrec)->first();

            if (!$patient) {
                return redirect()->route('viewer')
                    ->with('error', 'Pasien tidak ditemukan.');
            }

            $data['title'] = 'Result List';
            $data['patient'] = $patient;

            return view('viewer.result', $data);
        } catch (\Exception $e) {
            return redirect()->route('viewer')
                ->with('error', $e->getMessage());
        }
    }

    public function access(Request $request)
    {
        try {
            $request->validate([
                'medrec' => 'required',
                'birthdate' => 'required|date',
            ]);

            $patient = Patient::where('medrec', $request->medrec)
                ->whereDate('birthdate', $request->birthdate)
                ->first();

            if (!$patient) {
                throw new \Exception("Pasien dengan medrec " . $request->medrec . " dan tanggal lahir " . $request->birthdate . " tidak ditemukan.");
            }

            $hashedMedrec = encrypt($patient->medrec);

            return response()->json([
                'message' => 'Access granted',
                'redirect_url' => route('viewer.result', [
                    'medrec' => $hashedMedrec,
                    'granted' => 'true'
                ])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function datatable($startDate = null, $endDate = null, $group_id = null)
    {
        try {
            $medrec = request('medrec');
            $granted = request('granted');

            // $from = $startDate
            //     ? Carbon::parse($startDate)->startOfDay()
            //     : Carbon::today()->startOfDay();

            // $to = $endDate
            //     ? Carbon::parse($endDate)->endOfDay()
            //     : Carbon::today()->endOfDay();

            if ($granted !== 'true') {
                return redirect()->route('viewer')
                    ->with('error', 'Hak akses tidak valid!');
            }

            try {
                $realMedrec = decrypt($medrec);
            } catch (\Exception $e) {
                return redirect()->route('viewer')
                    ->with('error', 'Data medrec tidak valid!');
            }

            $patientResultData = DB::table('finish_transactions as ft')
                ->selectRaw('ft.*, p.nik as patient_nik')
                ->leftJoin('finish_transaction_tests as ftt', 'ftt.finish_transaction_id', '=', 'ft.id')
                ->leftJoin('patients as p', 'ft.patient_id', '=', 'p.id')
                ->where('ft.patient_medrec', $realMedrec)
                // ->whereBetween('ft.created_time', [$from, $to])
                ->where('ft.status', '>=', ViewerController::STATUS);

            if ($group_id != null && $group_id != "null" && $group_id != 0) {
                $patientResultData->where('ftt.group_id', '=', $group_id);
            }

            $patientResultData->orderBy('ft.post_time', 'desc');
            $patientResultData->groupBy('ftt.finish_transaction_id');

            return DataTables::of($patientResultData)
                ->addIndexColumn()
                ->escapeColumns([])
                ->make(true);
        } catch (\Exception $e) {
            return redirect()->route('viewer')
                ->with('error', $e->getMessage());
        }
    }

    public function datatableProcessTime($transactionId)
    {
        $model = \App\FinishTransactionTest::selectRaw('finish_transaction_tests.*, finish_transactions.checkin_time, finish_transactions.checkin_by_name, finish_transactions.analytic_time')
            ->where('finish_transaction_id', $transactionId)
            ->leftJoin('finish_transactions', 'finish_transaction_tests.transaction_id', 'finish_transactions.transaction_id')
            ->orderBy('sequence', 'asc')
            ->groupByRaw('IFNULL(package_id, finish_transaction_tests.id)');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function datatableTest($transactionId)
    {
        $model = \App\FinishTransactionTest::selectRaw('finish_transaction_tests.*')
            ->leftJoin('tests', 'tests.id', 'finish_transaction_tests.test_id')
            ->where('finish_transaction_id', $transactionId)
            ->orderBy('group_id', 'asc')
            ->orderBy('tests.sequence', 'asc')
            ->get();

        $transaction = \App\FinishTransaction::findOrFail($transactionId);

        $data['table'] = $model;
        $data['transaction'] = $transaction;
        $html = view('dashboard.viewer.result-test-table', $data)->render();
        return response()->json(['html' => $html, 'data' => $model]);
    }

    public function getFirstPrint($transactionId)
    {
        $first_print = DB::table('log_print')
            ->where('finish_transaction_id', $transactionId)->first();

        if ($first_print) {
            $first_print_date = date('d/m/Y H:i', strtotime($first_print->printed_at));
        } else {
            $first_print_date = null;
        }
        return response()->json(['data' => $first_print_date]);
    }

    public function printPackageName(Request $request, $transactionId, $packageId)
    {
        try {
            $value = $request->value;

            $query = DB::table('finish_transaction_tests')
                ->where('transaction_id', $transactionId)
                ->where('package_id', $packageId);
            $transactionTest = $query->get();

            if ($value == 1) {
                $updateData = array('print_package_name' => 1);
            } else {
                $updateData = array('print_package_name' => 0);
            }

            DB::table('finish_transaction_tests')
                ->where('transaction_id', $transactionId)
                ->where('package_id', $packageId)
                ->update($updateData);

            return response()->json(['message' => 'Success']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getVerificatorName($transactionId)
    {
        $finish_transaction = DB::table('finish_transactions')
            ->where('id', $transactionId)->first();

        return response()->json(['data' => $finish_transaction->verficator_name]);
    }

    public function hasilTest($id)
    {
        $judul = "Print Result";
        $data['title'] = $judul;

        DB::table('finish_transactions')
            ->where('id', $id)
            ->update([
                'status' => 4,
                'print' => DB::raw('print + 1'),
            ]);

        $transaction = DB::table('finish_transactions')
            ->select('finish_transactions.*')
            ->where('finish_transactions.id', $id)->first();

        $query_tests = DB::table('finish_transaction_tests')
            ->where('finish_transaction_id', '=', $id)
            ->where('is_print', '=', 1)
            ->orderBy('sequence', 'asc')
            ->orderBy('sub_group', 'asc');

        $query_group = DB::table('finish_transaction_tests')
            ->select('group_name')
            ->where('finish_transaction_id', '=', $id)
            ->groupBy('group_name')
            ->get();

        $query_subgroup = DB::table('finish_transaction_tests')
            ->selectRaw("CASE WHEN sub_group IS NULL OR sub_group = '' THEN '' ELSE sub_group END as sub_group_grouped")
            ->where('finish_transaction_id', '=', $id)
            ->groupBy('sub_group_grouped')
            ->get();

        $reg_time = Carbon::parse($transaction->created_time)->format('d/m/Y H:i');
        $analitik_time = Carbon::parse($transaction->analytic_time)->format('d/m/Y H:i');

        $print_time_query = DB::table('finish_transaction_tests')
            ->select(DB::raw('MIN(validate_time) AS validate_time'))
            ->where('finish_transaction_id', '=', $id)->first();
        if ($print_time_query) {
            $print_time = $print_time_query->validate_time;
        } else {
            $print_time = Carbon::now();
        }

        $tests = $query_tests->get();

        $bulan_1 = Carbon::now()->format('F');
        $tgl = Carbon::now()->format('d');
        $tahun = Carbon::now()->format('Y');

        if ($bulan_1 == "January") {
            $bulan_st = "Januari";
        } else if ($bulan_1 == "February") {
            $bulan_st = "Februari";
        } else if ($bulan_1 == "March") {
            $bulan_st = "Maret";
        } else if ($bulan_1 == "April") {
            $bulan_st = "April";
        } else if ($bulan_1 == "May") {
            $bulan_st = "Mei";
        } else if ($bulan_1 == "June") {
            $bulan_st = "Juni";
        } else if ($bulan_1 == "July") {
            $bulan_st = "Juli";
        } else if ($bulan_1 == "August") {
            $bulan_st = "Agustus";
        } else if ($bulan_1 == "September") {
            $bulan_st = "September";
        } else if ($bulan_1 == "October") {
            $bulan_st = "Oktober";
        } else if ($bulan_1 == "November") {
            $bulan_st = "November";
        } else if ($bulan_1 == "December") {
            $bulan_st = "Desember";
        }

        $copy = $transaction->print;
        $user = $transaction->patient_id;
        $printBy = $transaction->patient_name;

        DB::table('log_print')
            ->insert([
                'finish_transaction_id' => $id,
                'printed_at' => Carbon::now()->toDateTimeString(),
                'copied' => $copy,
                'print_by' => $user,
            ]);

        $tgl_result = $tgl . " " . $bulan_st . " " . $tahun;
        $sorted_test = [];
        $group_array = [];

        foreach ($query_group as $group) {
            $group_array[$group->group_name] = [];
            array_push($group_array[$group->group_name], $group->group_name);
            foreach ($query_subgroup as $subgroup) {
                foreach ($tests as $test) {
                    if ($test->result_status == AnalyticController::RESULT_STATUS_NORMAL) {
                        $status = "Normal";
                    } else if ($test->result_status == AnalyticController::RESULT_STATUS_LOW || $test->result_status == AnalyticController::RESULT_STATUS_HIGH || $test->result_status == AnalyticController::RESULT_STATUS_ABNORMAL) {
                        $status = "Abnormal";
                    } else if ($test->result_status == AnalyticController::RESULT_STATUS_CRITICAL) {
                        $status = "Critical";
                    } else {
                        $status = "-";
                    }
                    $test->result_status_label = $status;
                    if (($test->sub_group == $subgroup->sub_group_grouped) && ($test->group_name == $group->group_name)) {
                        if (($subgroup->sub_group_grouped != '') && (!in_array($subgroup->sub_group_grouped, $group_array[$group->group_name]))) {
                            array_push($group_array[$group->group_name], $subgroup->sub_group_grouped);
                        }
                        array_push($group_array[$group->group_name], $test->test_name);
                        array_push($sorted_test, $test);
                    }
                }
            }
        }

        $born = Carbon::createFromFormat('Y-m-d', $transaction->patient_birthdate);
        $age = $born->diff(Carbon::now())->format('%y Thn %m Bln %d Hr');

        $first_draw_time_query = DB::table('finish_transaction_tests')
            ->select(DB::raw('MIN(draw_time) AS draw_time'))
            ->where('finish_transaction_id', '=', $id)->first();

        if ($first_draw_time_query) {
            $first_draw_time = $first_draw_time_query->draw_time;
        } else {
            $first_draw_time = Carbon::now();
        }

        $last_draw_time_query = DB::table('finish_transaction_tests')
            ->select(DB::raw('MAX(draw_time) AS draw_time'))
            ->where('finish_transaction_id', '=', $id)->first();
        if ($last_draw_time_query) {
            $last_draw_time = $last_draw_time_query->draw_time;
        } else {
            $last_draw_time = Carbon::now();
        }

        $data = [
            'tests' => $tests,
            'groups' => $group_array,
            'reg_time' => $reg_time,
            'analitik_time' => $analitik_time,
            'print_time' => $print_time,
            'tgl_result' => $tgl_result,
            'last_draw_time' => $last_draw_time,
            "transaction" => $transaction,
            "age" => $age,
            "print_by" => $printBy,
        ];

        return view('prints.hasil-test-viewer', $data);
    }

    public function exportPdfHasil($id)
    {
        $judul = "Export PDF Result";
        $data['title'] = $judul;

        $transaction = DB::table('finish_transactions')
            ->select('finish_transactions.*')
            ->where('finish_transactions.id', '=', $id)->first();

        $query_tests = DB::table('finish_transaction_tests')
            ->where('finish_transaction_id', '=', $id)
            ->where('is_print', '=', 1)
            ->orderBy('sequence', 'asc')
            ->orderBy('sub_group', 'asc');

        $query_group = DB::table('finish_transaction_tests')
            ->select('group_name')
            ->where('finish_transaction_id', '=', $id)
            ->groupBy('group_name')
            ->get();

        $query_subgroup = DB::table('finish_transaction_tests')
            ->selectRaw("CASE WHEN sub_group IS NULL OR sub_group = '' THEN '' ELSE sub_group END as sub_group_grouped")
            ->where('finish_transaction_id', '=', $id)
            ->groupBy('sub_group_grouped')
            ->get();

        $reg_time = Carbon::parse($transaction->created_time)->format('d/m/Y H:i');
        $analitik_time = Carbon::parse($transaction->analytic_time)->format('d/m/Y H:i');

        $print_time_query = DB::table('finish_transaction_tests')
            ->select(DB::raw('MIN(validate_time) AS validate_time'))
            ->where('finish_transaction_id', '=', $id)->first();
        if ($print_time_query) {
            $print_time = $print_time_query->validate_time;
        } else {
            $print_time = Carbon::now();
        }

        $tests = $query_tests->get();

        $bulan_1 = Carbon::now()->format('F');
        $tgl = Carbon::now()->format('d');
        $tahun = Carbon::now()->format('Y');

        if ($bulan_1 == "January") {
            $bulan_st = "Januari";
        } else if ($bulan_1 == "February") {
            $bulan_st = "Februari";
        } else if ($bulan_1 == "March") {
            $bulan_st = "Maret";
        } else if ($bulan_1 == "April") {
            $bulan_st = "April";
        } else if ($bulan_1 == "May") {
            $bulan_st = "Mei";
        } else if ($bulan_1 == "June") {
            $bulan_st = "Juni";
        } else if ($bulan_1 == "July") {
            $bulan_st = "Juli";
        } else if ($bulan_1 == "August") {
            $bulan_st = "Agustus";
        } else if ($bulan_1 == "September") {
            $bulan_st = "September";
        } else if ($bulan_1 == "October") {
            $bulan_st = "Oktober";
        } else if ($bulan_1 == "November") {
            $bulan_st = "November";
        } else if ($bulan_1 == "December") {
            $bulan_st = "Desember";
        }

        $copy = $transaction->print;
        $user = $transaction->patient_id;
        $printBy = $transaction->patient_name;

        $tgl_result = $tgl . " " . $bulan_st . " " . $tahun;
        $sorted_test = [];
        $group_array = [];

        foreach ($query_group as $group) {
            $group_array[$group->group_name] = [];
            array_push($group_array[$group->group_name], $group->group_name);
            foreach ($query_subgroup as $subgroup) {
                foreach ($tests as $test) {
                    if ($test->result_status == AnalyticController::RESULT_STATUS_NORMAL) {
                        $status = "Normal";
                    } else if ($test->result_status == AnalyticController::RESULT_STATUS_LOW || $test->result_status == AnalyticController::RESULT_STATUS_HIGH || $test->result_status == AnalyticController::RESULT_STATUS_ABNORMAL) {
                        $status = "Abnormal";
                    } else if ($test->result_status == AnalyticController::RESULT_STATUS_CRITICAL) {
                        $status = "Critical";
                    } else {
                        $status = "-";
                    }
                    $test->result_status_label = $status;
                    if (($test->sub_group == $subgroup->sub_group_grouped) && ($test->group_name == $group->group_name)) {
                        if (($subgroup->sub_group_grouped != '') && (!in_array($subgroup->sub_group_grouped, $group_array[$group->group_name]))) {
                            array_push($group_array[$group->group_name], $subgroup->sub_group_grouped);
                        }
                        array_push($group_array[$group->group_name], $test->test_name);
                        array_push($sorted_test, $test);
                    }
                }
            }
        }

        $born = Carbon::createFromFormat('Y-m-d', $transaction->patient_birthdate);
        $age = $born->diff(Carbon::now())->format('%y Thn %m Bln %d Hr');
        $first_draw_time_query = DB::table('finish_transaction_tests')
            ->select(DB::raw('MIN(draw_time) AS draw_time'))
            ->where('finish_transaction_id', '=', $id)->first();
        if ($first_draw_time_query) {
            $first_draw_time = $first_draw_time_query->draw_time;
        } else {
            $first_draw_time = Carbon::now();
        }

        $last_draw_time_query = DB::table('finish_transaction_tests')
            ->select(DB::raw('MAX(draw_time) AS draw_time'))
            ->where('finish_transaction_id', '=', $id)->first();
        if ($last_draw_time_query) {
            $last_draw_time = $last_draw_time_query->draw_time;
        } else {
            $last_draw_time = Carbon::now();
        }

        $data = [
            'tests' => $tests,
            'groups' => $group_array,
            'reg_time' => $reg_time,
            'analitik_time' => $analitik_time,
            'print_time' => $print_time,
            'tgl_result' => $tgl_result,
            'last_draw_time' => $last_draw_time,
            "transaction" => $transaction,
            "age" => $age,
            "print_by" => $printBy,
        ];

        $pdf = Pdf::loadView('exports.pdf-test-viewer', $data);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('Hasil pemeriksaan ' . $transaction->no_lab . ' - ' . $transaction->patient_name . ' - ' . $tgl_result . ' .pdf');
    }

    public function updatePrintTest($testtransactionId)
    {
        try {

            $transactionTests = \App\FinishTransactionTest::findOrFail($testtransactionId);

            if (empty($transactionTests)) {
                throw new \Exception("FInish Transaction Not Found, ID:" . $testtransactionId);
            }

            DB::table('finish_transaction_tests')
                ->where('id', $testtransactionId)
                ->update(
                    [
                        'is_print' => !$transactionTests->is_print,
                    ]
                );

            return response()->json(['message' => 'update status print success']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    public function updatePrintMemo($transactionId)
    {
        try {

            $transactionTests = \App\FinishTransaction::findOrFail($transactionId);

            if (empty($transactionTests)) {
                throw new \Exception("FInish Transaction Not Found, ID:" . $transactionId);
            }

            DB::table('finish_transactions')
                ->where('id', $transactionId)
                ->update(
                    [
                        'is_print_memo' => !$transactionTests->is_print_memo,
                    ]
                );

            return response()->json(['message' => 'update status print success']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function selectOptions($masterData, $searchKey, $params = null, Request $request)
    {
        try {
            switch ($masterData) {
                case 'room':
                    $data = $this->masters[$masterData]::selectRaw("id, " . $searchKey . " as name, class")
                        ->where('room', 'LIKE', '%' . $request->input('query') . '%')
                        ->where('type', $params)
                        ->take(150)->get();
                    break;
                case 'test':
                    $data = $this->masters[$masterData]::selectRaw("tests.id as id, tests.name as name, GROUP_CONCAT(class SEPARATOR ', ') as classes, tests.initial")
                        ->leftJoin('prices', 'tests.id', '=', 'prices.test_id')
                        ->where($searchKey, 'LIKE', '%' . $request->input('query') . '%')
                        ->groupBy(['tests.id', 'tests.name', 'tests.initial'])->take(150)->get();
                    break;
                case 'package':
                    $data = $this->masters[$masterData]::selectRaw("packages.id as id, packages.name as name, GROUP_CONCAT(class SEPARATOR ', ') as classes")
                        ->leftJoin('prices', 'packages.id', '=', 'prices.test_id')
                        ->where($searchKey, 'LIKE', '%' . $request->input('query') . '%')
                        // ->where(function ($q) {
                        //     $q->where('prices.class', '=', 0)->orWhereNull('prices.class');
                        // })
                        ->groupBy(['packages.id', 'packages.name'])
                        ->take(150)->get();
                    break;
                case 'test_package':
                    $query = $request->input('query');
                    $tests = DB::table('tests')
                        ->selectRaw("tests.id as id, tests.name as name, 'single' as type")
                        ->where('tests.name', 'LIKE', "%$query%");
                    $packages = DB::table('packages')
                        ->selectRaw("packages.id as id, packages.name as name, 'package' as type")
                        ->where('packages.name', 'LIKE', "%$query%");
                    $data = $tests->unionAll($packages)
                        ->limit(150)
                        ->get();
                    break;
                case 'patient':
                    $req = $request->input('query');
                    $data = $this->masters[$masterData]::selectRaw("id, " . $searchKey . " as name, medrec")
                        ->where(function ($q) use ($req) {
                            $q->where('name', 'LIKE', '%' . $req . '%')
                                ->orwhere('medrec', 'LIKE', '%' . $req . '%');
                        })
                        ->take(150)->get();
                    break;
                default:
                    $data = $this->masters[$masterData]::selectRaw('id, ' . $searchKey . ' as name')
                        ->where($searchKey, 'LIKE', '%' . $request->input('query') . '%')
                        ->take(150)->get();
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
