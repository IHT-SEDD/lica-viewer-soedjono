<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use DB;
use Auth;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['title'] = 'Inventory';
        return view('dashboard.inventory.index', $data);
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
     * @return \Illuminate\Http\Response
     */
    public function updateField(Request $request)
    {
        try {

            $id = $request->id;
            $value = $request->value;
            $field = $request->field;

        $validator = Validator::make($request->all(),
        [
            'id' => 'required|integer|exists:inventories,id',
            'field' => 'required|in:stock,test',
            'value' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
            
        $inventory = \App\Inventory::with(['product'])->findOrFail($id);

        $inventory->$field = $value;
        $inventory->test = $value * $inventory->product->test;
        $inventory->save();

        return response()->json(['message' => 'Sucessfully updated '. $field, 'status' => 200, 'id' => $id, 'test' => $inventory->test ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function datatableInventory()
    {
        
        $model = \App\Inventory::with(['product'])->get();
        $count = \App\Inventory::count();

        return DataTables::of($model)
        ->setTotalRecords($count)
        // ->skipTotalRecords()
        ->addIndexColumn()
        ->escapeColumns([])
        ->make(true);
    }

    public function datatableLogInStock($inventoryId)
    {
        
        $model = \App\LogInProduct::with(['product', 'inventory', 'user'])->where('inventory_id', $inventoryId)->get();

        return DataTables::of($model)
        // ->skipTotalRecords()
        ->addIndexColumn()
        ->escapeColumns([])
        ->make(true);
    }

    public function datatableProductOut($startDate = null, $endDate = null)
    {

        if ($startDate == null && $endDate == null) {
            $from = Carbon::today()->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::today()->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        } else {
            $from = Carbon::parse($startDate)->addHours(0)->addMinutes(0)->addSeconds(0)->toDateTimeString();
            $to = Carbon::parse($endDate)->addHours(23)->addMinutes(59)->addSeconds(59)->toDateTimeString();
        }
    
        $model = \App\ProductOut::with(['product', 'analyzer', 'user'])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $count = \App\Inventory::whereBetween('created_at', [$from, $to])->count();

        return DataTables::of($model)
        ->setTotalRecords($count)
        // ->skipTotalRecords()
        ->addIndexColumn()
        ->escapeColumns([])
        ->make(true);
    }

    public function createProductOut(Request $request)
    {

        try{
            DB::beginTransaction();

            [$from, $to] = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
            $analyzer_id = $request->analyzer_id;
            $inventoryId = $request->id;
            $qty = (float) $request->qty;
            $inventoryStock = 0;
            $totalTest = 0;
            $remainder_test = 0;
            $usage_stock = 0;
            $stock = 0;
            $beforeStock = 0;
            $status = 0;
  
            $validator = Validator::make($request->all(),
            [
                'analyzer_id' => 'required',
                'qty' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $usage = \App\FinishTransactionTest::where('analyzer_id', $analyzer_id)
                    ->whereBetween('validate_time', [$from, $to])
                    ->groupBy('analyzer_id')
                    ->count();

            $inventory = \App\Inventory::with('product')->findOrFail($inventoryId);

             $inventoryStock = $inventory->stock;
             $totalTest = $qty * $inventory->product->test; 
             if($usage != 0){
                 $remainder_test = $totalTest - $usage;
             }
            $usage_stock = $remainder_test / $inventory->product->test;
            // $usage_stock =  round($usage_stock, 1);
            $usage_stock = floor($usage_stock * 10) / 10;
            $stock = (float) $inventory->stock - $qty;
            $beforeStock = $stock + $usage_stock;
            // dd($stock, $usage_stock);
            if($beforeStock == $inventory->product->stock_buffer)
            {
                $status = 1;
            }
            elseif ($beforeStock >= 0 && $beforeStock <= 5) 
            {
                $status = 2;
            }

            \App\ProductOut::create([
                'product_id' => $inventory->product->id,
                'analyzer_id' => $analyzer_id,
                'stock' => $stock,
                'qty' => $qty,
                'test' => $totalTest,
                'usage' => $usage,
                'usage_stock' => $usage_stock,
                'status' => $status,
                'remainder_test' => $remainder_test,
                'remainder_stock' => $beforeStock
            ]);

            $inventory->before_stock = $beforeStock;
            $inventory->stock = $beforeStock;
            $inventory->status = $status;
            $inventory->test = $beforeStock * $inventory->product->test;
            $inventory->save();
            DB::commit();

            return response()->json(['message' => 'Successfully Created Product Out', 'status' => 200]);
        }catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }


    public function selectOptionProduct(Request $request, $withoutIds = null)
    {
       
        $query = $request->get('query', '');
        $exceptIds = $request->get('except_ids', []);
    
        $products = \App\Product::query()
            ->when($query, function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->when(!empty($exceptIds), function ($q) use ($exceptIds) {
                $q->whereNotIn('id', $exceptIds);
            })
            ->limit(50)
            ->get(['id', 'name']);
    
        return response()->json($products);

    }

    public function createAddStock(Request $request)
    {
      
        try {
            DB::beginTransaction();

            $products = $request->products;

            foreach ($products as $key => $value) {
                
             $inventory = \App\Inventory::where('product_id', $value['product_id'])->first();

                if($inventory){
                    $inventory->update(['stock' => DB::raw('stock + ' . $value['stock']), 'status' => 0]);
                }else{
                   $inventory = \App\Inventory::create(['product_id' => $value['product_id'], 'stock' => $value['stock'] ]);
                }

                \App\LogInProduct::create([
                    'product_id' => $value['product_id'],
                     'inventory_id' => $inventory->id, 
                     'user_id' => Auth::user()->id,
                     'note' => 'Menambahkan produk ' . $inventory->product->name . ' dengan jumlah stock '. $value['stock']
                    ]);

            }

            DB::commit();
            return response()->json(['message' => 'Successfully Add Product', 'status' => 200]);
        }catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function productOut(Request $request)
    {

        try {
            DB::beginTransaction();

            $products = $request->product_outs;

            foreach ($products as $key => $value) {

            $stock = 0;
                
             $inventory = \App\Inventory::where('product_id', $value['product_id'])->first();

             $stock = $inventory->stock - $value['qty'];

             if($stock == $inventory->product->stock_buffer)
             {
                 $status = 1;
             }
             elseif ($stock >= 0 && $stock <= 5) 
             {
                 $status = 2;
             }

            $inventory->update(['stock' => $stock, 'status' => $status]);
         
             \App\ProductOut::create([
                'product_id' => $value['product_id'], 
                'stock' => $stock,
                'qty' => $value['qty'],
                'status' => $status,
                'user_id' => Auth::user()->id]);
             
            }

            DB::commit();
            return response()->json(['message' => 'Successfully Product Out', 'status' => 200]);
        }catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
}
