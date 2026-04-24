<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DataTables;
use DB;
use Illuminate\Support\Carbon;
use Auth;

class IntegrationLogController extends Controller
{
    public function index()
    {
        $data['title'] = 'Pre Analytics';
        return view('dashboard.log_integration.index', $data);
    }

    public function datatablePostData($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
            $model = \App\LogIntegration::selectRaw('log_integrations.*')
                ->where('timestamp', '>=', $from)
                ->where('timestamp', '<=', $to)
                ->where('type', '=', 'POST DATA')
                // ->orderBy('timestamp', 'desc')
                // ->orderBy('id', 'desc')
                ->orderBy('status_sequence', 'asc');

            return DataTables::of($model)
                ->addIndexColumn()
                ->escapeColumns([])
                ->make(true);
        }

        // if the startDate and endDate is set, the query will be depend on the given date.
        $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
        $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        $model = \App\LogIntegration::selectRaw('log_integrations.*')
            ->where('timestamp', '>=', $from)
            ->where('timestamp', '<=', $to)
            ->where('type', '=', 'POST DATA')
            // ->orderBy('timestamp', 'desc')
            // ->orderBy('id', 'desc')
            ->orderBy('status_sequence', 'asc');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function datatableSendData($startDate = null, $endDate = null)
    {
        // if the startDate and the endDate not set, the query will be only for today's transactions
        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
            $model = \App\LogIntegration::selectRaw('log_integrations.*')
                ->where('timestamp', '>=', $from)
                ->where('timestamp', '<=', $to)
                ->where('type', '=', 'SEND DATA')
                ->orderBy('timestamp', 'desc');
                // ->orderBy('id', 'desc')
                // ->orderBy('status_sequence', 'asc');

            return DataTables::of($model)
                ->addIndexColumn()
                ->escapeColumns([])
                ->make(true);
        }

        // if the startDate and endDate is set, the query will be depend on the given date.
        $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
        $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        $model = \App\LogIntegration::selectRaw('log_integrations.*')
            ->where('timestamp', '>=', $from)
            ->where('timestamp', '<=', $to)
            ->where('type', '=', 'SEND DATA')
            ->orderBy('timestamp', 'desc');
            // ->orderBy('id', 'desc')
            // ->orderBy('status_sequence', 'asc');

        return DataTables::of($model)
            ->addIndexColumn()
            ->escapeColumns([])
            ->make(true);
    }

    public function showLog($logId)
    {
        $log_data = DB::table('log_integrations')->where('id', $logId)->first();

        return response()->json($log_data);
    }

    public function doctor($no_order)
    {
       $transaction = \App\Transaction::where('no_order', $no_order)->first();

      return response()->json($transaction);
    }

    public function get_enum_roomtype($kode_jenis)
    {

        if ($kode_jenis == 1) {
            return "rawat_jalan";
        } else if ($kode_jenis == 3) {
            return "igd";
        } else if ($kode_jenis == 2) {
            return "rawat_inap";
        } else if ($kode_jenis == 4) {
            return "rujukan";
        } else {
            return null;
        }
    }

    public function insertPatient(Request $request)
    {
        $log = \App\LogIntegration::findOrfail($request->id);
        $queryDoctor = \App\Doctor::findOrFail($request->doctor_id);
        $data = json_decode($log->return_result, true);
        $data['transaksi']['kode_dokter'] = $queryDoctor->general_code;
        $demografi = $data['demografi'];
        $transaksi = $data['transaksi'];
        $tests = $data['test'];
   
        // store multiple arrays for json_encode purposes
        $store_json['demografi'] = $demografi;
        $store_json['transaksi'] = $transaksi;
        $store_json['test'] = $tests;
 
         // $this->sendTransaction($store_json);
         // die;
 
         //hardcode handle for previous version ok jenis kelamin/gender
         if ($demografi['jk'] == "L") {
             $demografi['jk'] = "M";
         } elseif ($demografi['jk'] == "P") {
             $demografi['jk'] = "F";
         }
 
         //hardcode handle for previous version kode jenis ruangan/rooms
         // $transaksi['kode_jenis'] = $this->get_enum_roomtype($transaksi['kode_jenis']);
         $type = $this->get_enum_roomtype($transaksi['kode_jenis']);
 
             DB::beginTransaction();
 
             // Insert Patient
             $query = DB::table('patients')->where('medrec', $demografi['no_rkm_medis']);
             $patient_data = $query->first();
             if (empty($patient_data)) {
                 DB::table('patients')
                     ->insert([
                         'name' => $demografi['nama_pasien'],
                         'email' => isset($demografi['email']) ? $demografi['email'] : "",
                         'phone' => $demografi['no_telp'],
                         'medrec' => $demografi['no_rkm_medis'],
                         'gender' => $demografi['jk'],
                         'birthdate' => $demografi['tgl_lahir'],
                         'address' => $demografi['alamat']
                     ]);
                 $patient_id = DB::getPdo()->lastInsertId();
             } else {
                 $patient_id = $patient_data->id;
             }
 
             // Insert Transaction
 
             // Insert Rooms
             $query = DB::table('rooms')->where('general_code', $transaksi['kode_ruangan'])->where('type', $type);
             $room_data = $query->first();
 
             if (empty($room_data)) {
                 DB::rollback();

                 return response()->json(['message' => "Ruangan (" . $transaksi['ruangan'] . ") dengan kode (" . $transaksi['kode_ruangan']  . ") tidak terdaftar pada data master LICA", 'status' => 400]);
             }
 
             // Insert Doctors
             $query = DB::table('doctors')->where('general_code', $transaksi['kode_dokter']);
             $doctor_data = $query->first();
             if (empty($doctor_data)) {
                 DB::rollback();

             return response()->json(['message' => "Dokter (" . $transaksi['dokter'] . ") dengan kode (" . $transaksi['kode_dokter'] . ")  tidak terdaftar pada data master LICA", 'status' => 400]);
             }
 
             // Insert Insurances
             $query = DB::table('insurances')->where('general_code', $transaksi['kode_pembayaran']);
             $insurance_data = $query->first();
             if (empty($insurance_data)) {
                 DB::rollback();

                 return response()->json(['message' => "Jenis Pembayaran/Asuransi (" . $transaksi['pembayaran'] . ") dengan kode (" . $transaksi['kode_pembayaran'] . ") tidak terdaftar pada data master LICA", 'status' => 400]);
 
             }
 
             // Check Transaction exist or not
             $exist_query = DB::table('transactions')->where('no_order', $transaksi['no_order']);
             $check_transaction_exist = $exist_query->first();
 
             if (!$check_transaction_exist) {
 
                 $transactionInsertData = [
                     'patient_id' => $patient_id,
                     'room_id' => $room_data->id,
                     'doctor_id' => $doctor_data->id,
                     'insurance_id' => $insurance_data->id,
                     'type' => $type,
                     'transaction_id_label' => $transaksi['no_order'],
                     'no_order' => $transaksi['no_order'],
                     'status' => 0,
                     // 'note' => $transaksi['diagnosis'],
                     'created_time' => Carbon::now()->toDateTimeString(),
                     'created_at' => Carbon::now()->toDateTimeString()
                 ];
 
                 // echo '<pre>';
                 // print_r($transactionInsertData);
                 // die;
 
                 if ($room_data->auto_checkin || $room_data->auto_draw) {
                     $prefixDate = date('ymd');
 
                     
                 DB::transaction(function () use ($prefixDate,   $transactionInsertData) {
                     $countExistingData = DB::table('transactions')
                         ->where('no_lab', 'like', $prefixDate . '%')
                         ->lockForUpdate()
                         ->count();
                 
                     if ($countExistingData == 0) {
                         $countExistingData = 1;
                     } else {
                         $countExistingData++;
                     }
 
                     $trxId = str_pad($countExistingData, 3, '0', STR_PAD_LEFT);
                                         
                     $transactionInsertData['no_lab'] = $prefixDate . $trxId;
                 });
 
                     // $countExistingData = \App\Transaction::where('no_lab', 'like', $prefixDate . '%')->count();
                     // $trxId = str_pad($countExistingData, 3, '0', STR_PAD_LEFT);
                     // $check =  \App\Transaction::where('no_lab', $prefixDate . $trxId)->exists();
 
                     // while ($check) {
                     //     $countExistingData += 1;
                     //     $trxId = str_pad($countExistingData, 3, '0', STR_PAD_LEFT);
                     //     $check =  \App\Transaction::where('no_lab', $prefixDate . $trxId)->exists();
                     // }
                   
                     $transactionInsertData['checkin_time'] = Carbon::now();
                 }
                 DB::table('transactions')
                     ->insert($transactionInsertData);
 
                 // get transaction id
                 $transaction_id = DB::getPdo()->lastInsertId();
 
                 // Transaction Test
                 foreach ($tests as $test => $value) {
                     $query = DB::table('packages')->where('general_code', $value['kode_jenis_tes']);
                     $package_data = $query->first();
 
                     // Package Test
                     if (!empty($package_data)) {
                         $query = DB::table('package_tests')->where('package_id', $package_data->id);
                         $package_tests = $query->get();
 
                         foreach ($package_tests as $package_test) {
 
                             //check have default or not
                             $checkDefaultAnalyzer = \App\Analyzer::where('group_id', $package_data->group_id)->where('is_default', 1)->first();
                             $analyzerFromInterfacing = DB::table('interfacings')->where('test_id', $package_test->test_id)->first();
 
                             if ($checkDefaultAnalyzer) {
 
                                 // if (!empty($analyzerFromInterfacing)) {
 
                                 //     if ($analyzerFromInterfacing->analyzer_id == $checkDefaultAnalyzer->id) {
 
                                 //         $analyzer_id = $checkDefaultAnalyzer->id;
                                 //     } else {
                                 //         $analyzer_id = $analyzerFromInterfacing->analyzer_id;
                                 //     }
                                 // } else {
 
                                 //     $analyzer_id = null;
                                 // }
 
                                 $result_label = null;
                                 $checkDefaultLabel = \App\Result::selectRaw('results.id, results.status as result_status, tests.range_type')->where('test_id', $package_test->test_id)->join('tests', 'tests.id', '=', 'results.test_id')->where('is_default', 1)->where('tests.range_type', '=', 'label')->first();
                                 if ($checkDefaultLabel) {
                                     $result_label = $checkDefaultLabel->id;
                                 }
 
                                 $analyzer_id = $checkDefaultAnalyzer->id;
 
                                 // echo 'test id ' . $package_test->test_id . '<br>';
                                 // echo 'result label ' . $result_label . '<br>';
                                 // die;
 
                                 DB::table('transaction_tests')
                                     ->insert([
                                         'transaction_id' => $transaction_id,
                                         'test_id' => $package_test->test_id,
                                         'check_code' => $value['kode_jenis_tes'],
                                         'package_id' => $package_data->id,
                                         'type' => 'package',
                                         'group_id' => $package_data->group_id,
                                         'analyzer_id' => $analyzer_id,
                                         'result_label' => $result_label,
                                         'created_at' => Carbon::now()
                                     ]);
                             } else {
 
                                 if (!empty($analyzerFromInterfacing)) {
                                     $analyzer_id = $analyzerFromInterfacing->analyzer_id;
                                 } else {
                                     $analyzer_id = null;
                                 }
 
                                 $result_label = null;
                                 $checkDefaultLabel = \App\Result::selectRaw('results.id, results.status as result_status, tests.range_type')->where('test_id', $package_test->test_id)->join('tests', 'tests.id', '=', 'results.test_id')->where('is_default', 1)->where('tests.range_type', '=', 'label')->first();
                                 if ($checkDefaultLabel) {
                                     $result_label = $checkDefaultLabel->id;
                                 }
 
                                 DB::table('transaction_tests')
                                     ->insert([
                                         'transaction_id' => $transaction_id,
                                         'test_id' => $package_test->test_id,
                                         'check_code' => $value['kode_jenis_tes'],
                                         'package_id' => $package_data->id,
                                         'type' => 'package',
                                         'group_id' => $package_data->group_id,
                                         'analyzer_id' => $analyzer_id,
                                         'result_label' => $result_label,
                                         'created_at' => Carbon::now()
                                     ]);
                             }
                         }
                     } else {
                         // Single Test
 
                         $query = DB::table('tests')->where('tests.general_code', $value['kode_jenis_tes']);
                         $test_data = $query->first();
                         if (empty($test_data)) {
                             DB::rollback();
 
                             // delete transactions record
                             DB::table('transactions')->where('id', $transaction_id)->delete();
                             // delete transaction_tests record
                             DB::table('transaction_tests')->where('transaction_id', $transaction_id)->delete();

                             return response()->json(['message' => "Data tes (" . $value['nama_tes'] . ") dengan kode (" . $value['kode_jenis_tes'] . ") tidak terdaftar pada master data LICA", 'status' => 400]);

                         } else {
 
                             //check have default or not
                             $checkDefaultAnalyzer = \App\Analyzer::where('group_id', $test_data->group_id)->where('is_default', 1)->first();
                             $analyzerFromInterfacing = DB::table('interfacings')->where('test_id', $test_data->id)->first();
                             if ($checkDefaultAnalyzer) {
 
                                 // if (!empty($analyzerFromInterfacing)) {
 
                                 //     if ($analyzerFromInterfacing->analyzer_id == $checkDefaultAnalyzer->id) {
 
                                 //         $analyzer_id = $checkDefaultAnalyzer->id;
                                 //     } else {
                                 //         $analyzer_id = $analyzerFromInterfacing->analyzer_id;
                                 //     }
                                 // } else {
 
                                 //     $analyzer_id = null;
                                 // }
 
                                 $result_label = null;
                                 $checkDefaultLabel = \App\Result::selectRaw('results.id, results.status as result_status, tests.range_type')->where('test_id', $test_data->id)->join('tests', 'tests.id', '=', 'results.test_id')->where('is_default', 1)->where('tests.range_type', '=', 'label')->first();
                                 if ($checkDefaultLabel) {
                                     $result_label = $checkDefaultLabel->id;
                                 }
 
                                 $analyzer_id = $checkDefaultAnalyzer->id;
 
                                 DB::table('transaction_tests')
                                     ->insert([
                                         'transaction_id' => $transaction_id,
                                         'test_id' => $test_data->id,
                                         'check_code' => $value['kode_jenis_tes'],
                                         'analyzer_id' => $analyzer_id,
                                         'group_id' => $test_data->group_id,
                                         'result_label' => $result_label,
                                         'created_at' => Carbon::now()
                                     ]);
                             } else {
 
                                 if (!empty($analyzerFromInterfacing)) {
                                     $analyzer_id = $analyzerFromInterfacing->analyzer_id;
                                 } else {
                                     $analyzer_id = null;
                                 }
 
                                 $result_label = null;
                                 $checkDefaultLabel = \App\Result::selectRaw('results.id, results.status as result_status, tests.range_type')->where('test_id', $test_data->id)->join('tests', 'tests.id', '=', 'results.test_id')->where('is_default', 1)->where('tests.range_type', '=', 'label')->first();
                                 if ($checkDefaultLabel) {
                                     $result_label = $checkDefaultLabel->id;
                                 }
 
                                 DB::table('transaction_tests')
                                     ->insert([
                                         'transaction_id' => $transaction_id,
                                         'test_id' => $test_data->id,
                                         'check_code' => $value['kode_jenis_tes'],
                                         'analyzer_id' => $analyzer_id,
                                         'group_id' => $test_data->group_id,
                                         'result_label' => $result_label,
                                         'created_at' => Carbon::now()
                                     ]);
                             }
                         }
                     }
                 }
 
                 DB::table('log_integrations')->where('id', $request['id'])
                     ->update([
                         'no_order' => $transaksi['no_order'],
                         'return_result' => json_encode($store_json),
                         'type' => "POST DATA",
                         'status' => "insert_transaction_success",
                         'status_sequence' => 1,
                         'notes' => "Data transaksi berhasil sinkronisasi",
                         'status_2' => 1,
                         'timestamp' => Carbon::now(),
                     ]);
 
                 DB::table('log_integrations')->where('no_order', $transaksi['no_order'])->where('status_2', '=', 0)->delete();
 
                 DB::commit();
                 return response()->json([
                    'message' => 'Data transaksi berhasil sinkronisasi',
                    'status' => 200
                ]);

             } else {
 
                 // FLOW MAP TRANSACTION HAS BEEN REGISTERED IN LICA
 
                 // if the order_number has been registered in LICA
                 // then check the data from HIS/SIMRS (update or delete data) method
 
                 // (ADD new test)
                 // first, check order_number in LICA, WHETHER the transaction status was 0 
                 // IF transaction status was 0 AND DRAW STATUS was 0
                 // THEN transaction data can be added with new test
 
                 // (UPDATE exist test or DELETE)
                 // second, check order_number in LICA, WHETHER the transaction status was 0 
                 // IF transaction status was 0 AND DRAW STATUS was 0
                 // THEN transaction tests data can be UPDATED or DELETED
 
                 // ELSE IF transaction status was 2 (post position), THEN transaction can not be added new test OR transaction tests data can not be UPDATED or DELETED
 
                 $transaction_data = $check_transaction_exist;
                 $transaction_id = $transaction_data->id;
 
                 $transaction_test_data = DB::table('transaction_tests')->where('transaction_id', $transaction_data->id)->first();
                 $draw_by = $transaction_test_data->draw_by;
                 $draw_time = $transaction_test_data->draw_time;
 
                 // print_r($transaction_test_data);
                 // die;
 
                 // $transaction_test = DB::table('transaction_tests')->where('id', $transaction_data->id)->first();
 
                 if ($transaction_data->status == 0 || $transaction_data->status == 1) {
 
                     foreach ($tests as $test => $value) {
                         $query = DB::table('packages')->where('general_code', $value['kode_jenis_tes']);
                         $package_data = $query->first();
 
                         // Package Test
                         if (!empty($package_data)) {
 
                             $transaction_test = DB::table('transaction_tests')
                                 ->where('transaction_id', $transaction_data->id)
                                 ->where('check_code', $value['kode_jenis_tes'])
                                 ->first();
 
                             if (!$transaction_test) {
                                 $query = DB::table('package_tests')->where('package_id', $package_data->id);
                                 $package_tests = $query->get();
 
                                 foreach ($package_tests as $package_test) {
                                     //check have default or not
                                     $checkDefaultAnalyzer = \App\Analyzer::where('group_id', $package_data->group_id)->where('is_default', 1)->first();
                                     if ($checkDefaultAnalyzer) {
                                         DB::table('transaction_tests')
                                             ->insert([
                                                 'transaction_id' => $transaction_id,
                                                 'test_id' => $package_test->test_id,
                                                 'check_code' => $value['kode_jenis_tes'],
                                                 'package_id' => $package_data->id,
                                                 'group_id' => $package_data->group_id,
                                                 'analyzer_id' => $checkDefaultAnalyzer->id,
                                                 'mark_duplo' => 0,
                                                 'type' => "package",
                                                 'result_number' => NULL,
                                                 'result_label' => NULL,
                                                 'result_text' => NULL,
                                                 'draw' => 1,
                                                 'draw_by' => $draw_by,
                                                 'draw_memo' => NULL,
                                                 'undraw_memo' => NULL,
                                                 'draw_time' => $draw_time,
                                                 'input_time' => NULL,
                                                 'result_status' => NULL,
                                                 'verify' => 0,
                                                 'validate' => 0,
                                                 'report_status' => 0,
                                                 'report_by' => NULL,
                                                 'report_to' => NULL,
                                                 'memo_test' => NULL,
                                                 'verify_by' => NULL,
                                                 'validate_by' => NULL,
                                                 'verify_time' => NULL,
                                                 'validate_time' => NULL,
                                                 'report_time' => NULL,
                                                 'check_code' => $value['kode_jenis_tes'],
                                                 'created_at' => Carbon::now(),
                                                 'updated_at' => Carbon::now()
                                             ]);
                                     } else {
 
                                         $analyzerFromInterfacing = DB::table('interfacings')->where('test_id', $package_test->test_id)->first();
                                         if ($analyzerFromInterfacing) {
                                             $analyzer_id = $analyzerFromInterfacing->analyzer_id;
                                         } else {
                                             $analyzer_id = NULL;
                                         }
 
                                         DB::table('transaction_tests')
                                             ->insert([
                                                 'transaction_id' => $transaction_id,
                                                 'test_id' => $package_test->test_id,
                                                 'check_code' => $value['kode_jenis_tes'],
                                                 'package_id' => $package_data->id,
                                                 'group_id' => $package_data->group_id,
                                                 'analyzer_id' => NULL,
                                                 'mark_duplo' => 0,
                                                 'type' => "package",
                                                 'result_number' => NULL,
                                                 'result_label' => NULL,
                                                 'result_text' => NULL,
                                                 'draw' => 1,
                                                 'draw_by' => $draw_by,
                                                 'draw_memo' => NULL,
                                                 'undraw_memo' => NULL,
                                                 'draw_time' => $draw_time,
                                                 'input_time' => NULL,
                                                 'result_status' => NULL,
                                                 'verify' => 0,
                                                 'validate' => 0,
                                                 'report_status' => 0,
                                                 'report_by' => NULL,
                                                 'report_to' => NULL,
                                                 'memo_test' => NULL,
                                                 'verify_by' => NULL,
                                                 'validate_by' => NULL,
                                                 'verify_time' => NULL,
                                                 'validate_time' => NULL,
                                                 'report_time' => NULL,
                                                 'check_code' => $value['kode_jenis_tes'],
                                                 'created_at' => Carbon::now(),
                                                 'updated_at' => Carbon::now()
                                             ]);
                                     }
                                 }
                             }
                         } else {
                             // Single Test
 
                             $transaction_test = DB::table('transaction_tests')
                                 ->where('transaction_id', $transaction_data->id)
                                 ->where('check_code', $value['kode_jenis_tes'])
                                 ->first();
 
                             if (!$transaction_test) {
 
                                 // echo $value['kode_jenis_tes'] . "<br>";
                                 // echo "belum ada" . "<br>";
 
                                 $queryTest = DB::table('tests')->where('tests.general_code', $value['kode_jenis_tes']);
                                 $test_data = $queryTest->first();
 
                                 if ($test_data) {
                                     //check have default or not
                                     $checkDefaultAnalyzer = \App\Analyzer::where('group_id', $test_data->group_id)->where('is_default', 1)->first();
                                     if ($checkDefaultAnalyzer) {
                                         DB::table('transaction_tests')
                                             ->insert([
                                                 'transaction_id' => $transaction_id,
                                                 'test_id' => $test_data->id,
                                                 'package_id' => NULL,
                                                 'group_id' => $test_data->group_id,
                                                 'analyzer_id' => $checkDefaultAnalyzer->id,
                                                 'mark_duplo' => 0,
                                                 'type' => "single",
                                                 'result_number' => NULL,
                                                 'result_label' => NULL,
                                                 'result_text' => NULL,
                                                 'draw' => 1,
                                                 'draw_by' => $draw_by,
                                                 'draw_memo' => NULL,
                                                 'undraw_memo' => NULL,
                                                 'draw_time' => $draw_time,
                                                 'input_time' => NULL,
                                                 'result_status' => NULL,
                                                 'verify' => 0,
                                                 'validate' => 0,
                                                 'report_status' => 0,
                                                 'report_by' => NULL,
                                                 'report_to' => NULL,
                                                 'memo_test' => NULL,
                                                 'verify_by' => NULL,
                                                 'validate_by' => NULL,
                                                 'verify_time' => NULL,
                                                 'validate_time' => NULL,
                                                 'report_time' => NULL,
                                                 'check_code' => $value['kode_jenis_tes'],
                                                 'created_at' => Carbon::now(),
                                                 'updated_at' => Carbon::now()
                                             ]);
                                     } else {
 
                                         $analyzerFromInterfacing = DB::table('interfacings')->where('test_id', $$test_data->id)->first();
                                         $analyzer_id = $analyzerFromInterfacing->analyzer_id;
 
                                         DB::table('transaction_tests')
                                             ->insert([
                                                 'transaction_id' => $transaction_id,
                                                 'test_id' => $test_data->id,
                                                 'package_id' => NULL,
                                                 'group_id' => $test_data->group_id,
                                                 'analyzer_id' => $analyzer_id,
                                                 'mark_duplo' => 0,
                                                 'type' => "single",
                                                 'result_number' => NULL,
                                                 'result_label' => NULL,
                                                 'result_text' => NULL,
                                                 'draw' => 1,
                                                 'draw_by' => $draw_by,
                                                 'draw_memo' => NULL,
                                                 'undraw_memo' => NULL,
                                                 'draw_time' => $draw_time,
                                                 'input_time' => NULL,
                                                 'result_status' => NULL,
                                                 'verify' => 0,
                                                 'validate' => 0,
                                                 'report_status' => 0,
                                                 'report_by' => NULL,
                                                 'report_to' => NULL,
                                                 'memo_test' => NULL,
                                                 'verify_by' => NULL,
                                                 'validate_by' => NULL,
                                                 'verify_time' => NULL,
                                                 'validate_time' => NULL,
                                                 'report_time' => NULL,
                                                 'check_code' => $value['kode_jenis_tes'],
                                                 'created_at' => Carbon::now(),
                                                 'updated_at' => Carbon::now()
                                             ]);
                                     }
                                 }
                             } else {
                                 // echo $value['kode_jenis_tes'] . "<br>";
                                 // echo "sudah ada" . "<br>";
 
                                 DB::table('log_integrations')->where('id', $request['id'])
                                     ->insert([
                                         'no_order' => $transaksi['no_order'],
                                         'return_result' => json_encode($store_json),
                                         'type' => "POST DATA",
                                         'status_sequence' => 1,
                                         'notes' => "Tidak ada data yang ditambahkan",
                                         'status_2' => 1,
                                         'timestamp' => Carbon::now(),
                                     ]);
 
                                 return response()->json([
                                    'message' => 'Tidak ada data yang ditambahkan',
                                    'status' => 401
                                ]);
                             }
                         }
                     }
 
                     DB::table('log_integrations')->where('id', $request['id'])
                         ->insert([
                             'no_order' => $transaksi['no_order'],
                             'return_result' => json_encode($store_json),
                             'type' => "POST DATA",
                             'status' => "insert_transaction_success",
                             'status_sequence' => 1,
                             'notes' => "Data transaksi berhasil ditambahkan",
                             'status_2' => 1,
                             'timestamp' => Carbon::now(),
                         ]);
 
                     DB::commit();
                     return response()->json([
                        'message' => 'Data tes berhasil ditambahkan',
                        'status' => 200
                    ]);
                 } else {
                     return response()->json([
                        'message' => 'Status transaksi sudah di Post Analytic!',
                        'status' => 401
                    ]);
                }
             }
    }
}
