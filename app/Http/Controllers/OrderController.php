<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class OrderController extends Controller
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config   = $config;
        $this->config = Config('opencart');
    }


    public function index(Request $request)
    {

        $field  = $request->input('field');
        $op     = $request->input('op');
        $value  = $request->input('value');

        return $field;

        if($op == 'like'){
            $newValue = "'%$value%'";
        }else{
            $newValue = "'$value'";
        }



        $data  = DB::table($this->config['db_prefix'].'_orders')
                    ->whereraw("$newValueCategory")
                    ->paginate(20);

        return response()->json($data, 200);
    }


    public function store(Request $request)
    {


    }


    public function show(string $id)
    {



    }


    public function update(Request $request, string $category_id)
    {




    }


    public function destroy(string $category_id)
    {



    }


}
