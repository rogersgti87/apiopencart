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


        $data = $request->getContent();
        $result = json_decode($data , true);

        $field  = isset($result['field']) ? $result['field'] : null;
        $op     = isset($result['op']) ? $result['op'] : null;
        $value  = isset($result['value']) ? $result['value'] : null;
        $status = isset($result['status']) ? $result['status'] : null;

        $columns = [
        'order_id','invoice_no','invoice_prefix','firstname','lastname','email','telephone','payment_method',
        'shipping_firstname','shipping_lastname','shipping_address_1','shipping_address_2','shipping_city',
        'shipping_postcode','shipping_country','shipping_zone','shipping_method','shipping_code','total',
        'order_status_id','date_added','date_modified','custom_field','shipping_custom_field','comment',

        ];

        $oc_custom_fields = DB::table($this->config['db_prefix'].'custom_field_description as cfd')
        ->leftjoin($this->config['db_prefix'].'custom_field_value_description as cfvd','cfvd.custom_field_id','cfd.custom_field_id')
        ->select('cfd.custom_field_id', 'cfd.name as field', 'cfvd.name as value', 'cfvd.custom_field_value_id')
        ->get();


        $oc_order_total = DB::table($this->config['db_prefix'].'order_total')->get();

        $oc_order_products = DB::table($this->config['db_prefix'].'order_product as op')
                            ->select('op.order_id','op.product_id','op.name as product_name','op.model','op.quantity',
                            'op.price','op.total','oo.name as option_name','oo.value as option_value')
                            ->leftjoin($this->config['db_prefix'].'order_option as oo','oo.order_product_id','op.order_product_id')
                            ->get();


        if($field && $op && $value){
            if($op == 'like'){
                $newValue = "'%$value%'";
            }else{
                $newValue = "'$value'";
            }

            if(isset($status)){
                $newStatus = "and order_status_id = '$status'";
            } else {
                $newStatus = "and order_status_id > 0";
            }


            $data  = DB::table($this->config['db_prefix'].'order')
                        ->select($columns)
                        ->whereraw("$field  $op $newValue $newStatus")
                        ->orderby('date_modified','DESC')
                        ->paginate(20);

            dd($data);

            } else {
                if(isset($status)){
                    $newStatus = " order_status_id = '$status'";
                } else {
                    $newStatus = ' order_status_id > 0';
                }

                $data  = DB::table($this->config['db_prefix'].'order')
                            ->select($columns)
                            ->orderby('date_modified','DESC')
                            ->whereraw("$newStatus")
                            ->paginate(20);

            }


            $data = $data->toArray();


            foreach($data['data'] as $key => $result){

                foreach($oc_order_total as $ot){
                    if($ot->order_id == $result->order_id){
                        if($ot->code == 'sub_total')
                            $data['data'][$key]->sub_total  = $ot->value;
                        if($ot->code == 'shipping')
                            $data['data'][$key]->shipping   = $ot->value;
                    }
                }


                foreach($oc_order_products as $prod){
                    if($result->order_id == $prod->order_id){
                    $data['data'][$key]->products[] = [
                        'product_id'            =>  $prod->product_id,
                        'product_name'          =>  $prod->product_name,
                        'product_model'         =>  $prod->model,
                        'product_option_name'   =>  $prod->option_name,
                        'product_option_value'  =>  $prod->option_value,
                        'product_quantity'      =>  $prod->quantity,
                        'product_price'         =>  $prod->price,
                        'product_total'         =>  $prod->total,
                    ];
                }
                }

                $custom_fields = json_decode($result->custom_field, true);
                $shipping_custom_fields = json_decode($result->shipping_custom_field, true);

                unset( $data['data'][$key]->custom_field);

                foreach($oc_custom_fields as $ocf){
                    foreach($custom_fields as $k => $cf){
                        if($ocf->custom_field_id == $k){
                            if($ocf->field == 'CPF')
                                $data['data'][$key]->cpf = $cf;
                            if($ocf->field == 'CNPJ')
                                $data['data'][$key]->cnpj = $cf;
                            if($ocf->field == 'Razão Social')
                                $data['data'][$key]->razao_social = $cf;
                            if($ocf->field == 'Inscrição Estadual')
                                $data['data'][$key]->inscricao_estadual = $cf;
                            if($ocf->field == 'Data de Nascimento')
                                $data['data'][$key]->nascimento = $cf != '' ? Carbon::createFromFormat('d/m/Y', $cf)->format('Y-m-d') : '';
                            if($ocf->field == 'Celular')
                                $data['data'][$key]->celular = $cf;
                            if($ocf->field == 'Sexo' && $ocf->custom_field_value_id == $cf ){
                                $data['data'][$key]->sexo = $ocf->value;
                            }
                        }

                         if($ocf->field == 'Número'){
                            foreach($shipping_custom_fields as $key_number => $scf){
                                if($key_number == 3){
                                    $data['data'][$key]->numero = isset($scf) ? $scf : null;
                                }
                            }
                        }

                        if($ocf->field == 'Complemento'){
                            foreach($shipping_custom_fields as $key_number => $scf){
                                if($key_number == 9){
                                    $data['data'][$key]->complemento = isset($scf) ? $scf : null;
                                }
                            }
                        }
                    }

                }

            }

            foreach($data['data'] as $key => $result){
                $data['data'][$key] = [
                    'order'  =>  [
                        'order_id'              =>  $result->order_id,
                        'order_status_id'       =>  $result->order_status_id,
                        'invoice_no'            =>  $result->invoice_no,
                        'invoice_no'            =>  $result->invoice_no,
                        'invoice_prefix'        =>  $result->invoice_prefix,
                        'date_added'            =>  $result->date_added,
                        'date_modified'         =>  $result->date_modified,
                        'invoice_no'            =>  $result->invoice_no,
                        'sub_total'             =>  $result->sub_total,
                        'shipping'              =>  $result->shipping,
                        'total'                 =>  $result->total,
                        'comment'               =>  $result->comment,
                    ],
                    'customer'  =>  [
                        'cpf'                   =>  isset($result->cpf) ? $result->cpf : null,
                        'cnpj'                  =>  isset($result->cnpj) ? $result->cnpj : null,
                        'inscricao_estadual'    =>  isset($result->inscricao_estadual) ? $result->inscricao_estadual : null,
                        'razao_social'          =>  isset($result->razao_social) ? $result->razao_social : null,
                        'firstname'             =>  $result->firstname,
                        'lastname'              =>  $result->lastname,
                        'email'                 =>  $result->email,
                        'telephone'             =>  $result->telephone,
                        'celular'               =>  $result->celular,
                        'nascimento'            =>  $result->nascimento,
                        'sexo'                  =>  $result->sexo,
                    ],
                    'shipping'  =>  [
                        'shipping_method'       =>  $result->shipping_method,
                        'shipping_postcode'     =>  $result->shipping_postcode,
                        'shipping_address_1'    =>  $result->shipping_address_1,
                        'shipping_address_2'    =>  $result->shipping_address_2,
                        'numero'                =>  $result->numero,
                        'complemento'           =>  $result->complemento,
                        'shipping_city'         =>  $result->shipping_city,
                        'shipping_country'      =>  $result->shipping_country,
                        'shipping_zone'         =>  $result->shipping_zone,
                    ],

                    'products' => $result->products,

                ];

            }

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
