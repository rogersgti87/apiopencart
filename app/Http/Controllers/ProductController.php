<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Image;
use Illuminate\Support\Facades\Config;

class ProductController extends Controller
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config   = $config;
        $this->config = Config('opencart');
    }


    public function index()
    {

        $columns = [
            //Columns Table oc_product
            'p.product_id as p_product_id','p.model as p_model','p.sku as p_sku',
            'p.upc as p_upc','p.ean as p_ean','p.jan as p_jan','p.isbn as p_isbn',
            'p.mpn as p_mpn','p.location as p_location','p.quantity as p_quantity',
            'p.stock_status_id as p_stock_status_id','p.image as p_image','p.manufacturer_id as p_manufacturer_id',
            'p.shipping as p_shipping','p.price as p_price','p.points as p_points','p.tax_class_id as p_tax_class_id',
            'p.date_available as p_date_available','p.weight as p_weight','p.weight_class_id as p_weight_class_id',
            'p.length as p_length','p.width as p_width','p.height as p_height','p.length_class_id as p_length_class_id',
            'p.subtract as p_subtract','p.minimum as p_minimum','p.sort_order as p_sort_order',
            'p.status as p_status','p.viewed as p_viewed','p.date_added as p_date_added','p.date_modified as p_date_modified',
            //Columns table oc_product_description
            'pd.product_id as pd_product_id','pd.language_id as pd_language_id','pd.name as pd_name',
            'pd.description as pd_description','pd.tag as pd_tag','pd.meta_title as pd_meta_title',
            'pd.meta_description as pd_meta_description','pd.meta_keyword as pd_meta_keyword',
            //Columns table oc_product_image
            'pi.product_image_id as pi_product_image_id','pi.product_id as pi_product_id','pi.image as pi_image',
            'pi.sort_order as pi_sort_order',
            //Columns table oc_product_special
            'ps.product_special_id as ps_product_special_id','ps.product_id as ps_product_id',
            'ps.customer_group_id as ps_customer_group_id','ps.priority as ps_priority','ps.price as ps_price',
            'ps.date_start as ps_date_start','ps.date_end as ps_date_end'
        ];

        $data  = DB::table('oc_product as p')
                        ->select($columns)
                        ->join('oc_product_description as pd','p.product_id','pd.product_id')
                        ->leftjoin('oc_product_image as pi','p.product_id','pi.product_id')
                        ->leftjoin('oc_product_special as ps','p.product_id','ps.product_id')
                        ->paginate(20);

        $data = $data->toArray();

        foreach($data['data'] as $key => $result){

            $data['data'][$key] = [
                'product'  =>  [
                    'product_id'            => $result->p_product_id,
                    'model'                 => $result->p_model,
                    'sku'                   => $result->p_sku,
                    'upc'                   => $result->p_upc,
                    'ean'                   => $result->p_ean,
                    'jan'                   => $result->p_jan,
                    'isbn'                  => $result->p_isbn,
                    'mpn'                   => $result->p_mpn,
                    'location'              => $result->p_location,
                    'quantity'              => $result->p_quantity,
                    'stock_status_id'       => $result->p_stock_status_id,
                    'image'                 => $result->p_image,
                    'manufacturer_id'       => $result->p_manufacturer_id,
                    'shipping'              => $result->p_shipping,
                    'price'                 => $result->p_price,
                    'points'                => $result->p_points,
                    'tax_class_id'          => $result->p_tax_class_id,
                    'date_available'        => $result->p_date_available,
                    'weight'                => $result->p_weight,
                    'weight_class_id'       => $result->p_weight_class_id,
                    'length'                => $result->p_length,
                    'width'                 => $result->p_width,
                    'height'                => $result->p_height,
                    'length_class_id'       => $result->p_length_class_id,
                    'subtract'              => $result->p_subtract,
                    'minimum'               => $result->p_minimum,
                    'status'                => $result->p_status,
                    'viewed'                => $result->p_viewed,
                    'date_added'            => $result->p_date_added,
                    'date_modified'         => $result->p_date_modified,
                    'name'                  => $result->pd_name,
                    'description'           => $result->pd_description,
                    'tag'                   => $result->pd_tag,
                    'meta_title'            => $result->pd_meta_title,
                    'meta_description'      => $result->pd_meta_description,
                    'meta_keyword'          => $result->pd_meta_keyword,
                    'product_image'       =>  [
                    'product_image_id'      => $result->pi_product_image_id,
                    'product_id'            => $result->pi_product_id,
                    'image'                 => $result->pi_image,
                    'sort_order'            => $result->pi_sort_order
                    ],
                    'product_special'       =>  [
                    'product_special_id'    => $result->ps_product_special_id,
                    'customer_group_id'     => $result->ps_customer_group_id,
                    'priority'              => $result->ps_priority,
                    'price'                 => $result->ps_price,
                    'date_start'            => $result->ps_date_start,
                    'date_end'              => $result->ps_date_end
                    ]
                ]
            ];

        }

        return response()->json($data, 200);


    }


    public function store(Request $request)
    {

        $data = $request->getContent();

        $response_products = [];

        foreach(json_decode($data , true) as $result){
        if(!isset($result['product_category']) || $result['product_category'] == ''){
            return response()->json('O campo product_category é obrigatório!', 422);
        }

        if(!isset($result['model']) || $result['model'] == ''){
            return response()->json('O campo model é obrigatório!', 422);
        }

        if(!isset($result['name']) || $result['name'] == ''){
            return response()->json('O campo name é obrigatório!', 422);
        }

        if(!isset($result['status']) || $result['status'] == ''){
            return response()->json('O campo status é obrigatório!', 422);
        }

        $product_id = DB::table($this->config['db_prefix'].'product')->insertGetId([
            'model'                 =>  $result['model'],
            'sku'                   =>  isset($result['sku']) ? (int)$result['sku'] : '',
            'upc'                   =>  isset($result['upc']) ? (int)$result['upc'] : '',
            'ean'                   =>  isset($result['ean']) ? (int)$result['ean'] : '',
            'jan'                   =>  isset($result['jan']) ? (int)$result['jan'] : '',
            'isbn'                  =>  isset($result['isbn']) ? (int)$result['isbn'] : '',
            'mpn'                   =>  isset($result['mpn']) ? (int)$result['mpn'] : '',
            'location'              =>  isset($result['location']) ? (int)$result['location'] : '',
            'quantity'              =>  isset($result['quantity']) ? (int)$result['quantity'] : 0,
            'minimum'               =>  isset($result['minimum']) ? (int)$result['minimum'] : 1,
            'subtract'              =>  isset($result['subtract']) ? (int)$result['subtract'] : 1,
            'stock_status_id'       =>  isset($result['stock_status_id']) ? (int)$result['stock_status_id'] : 7,
            'date_available'        =>  isset($result['date_available']) ? (int)$result['date_available'] : date('Y-m-d', strtotime('-1 day')),
            'manufacturer_id'       =>  isset($result['manufacturer_id']) ? (int)$result['manufacturer_id'] : 0,
            'shipping'              =>  isset($result['shipping']) ? (int)$result['shipping'] : 1,
            'price'                 =>  isset($result['price']) ? (float)$result['price'] : 0.00,
            'points'                =>  isset($result['points']) ? (int)$result['points'] : 0,
            'weight'                =>  isset($result['weight']) ? (float)$result['weight'] : 0.00,
            'weight_class_id'       =>  isset($result['weight_class_id']) ? (int)$result['weight_class_id'] : 1,
            'length'                =>  isset($result['length']) ? (float)$result['length'] : 0.00,
            'width'                 =>  isset($result['width']) ? (float)$result['width'] : 0.00,
            'height'                =>  isset($result['height']) ? (float)$result['height'] : 0.00,
            'length_class_id'       =>  isset($result['length_class_id']) ? (int)$result['length_class_id'] : 1,
            'status'                =>  (int)$result['status'],
            'tax_class_id'          =>  isset($result['tax_class_id']) ? (int)$result['tax_class_id'] : 0,
            'sort_order'            =>  isset($result['sort_order']) ? (int)$result['sort_order'] : 0,
            'date_added'            =>  NOW(),
            'date_modified'         =>  NOW()

        ]);


 //update image table category
 if (isset($result['image'])) {

        $base64 = 'base64,'.$result['image'];
        $base64_str = substr($base64, strpos($base64, ",")+1);
        $image = base64_decode($base64_str);

        $imageName = Str::slug($result['name']) . '.jpg';
        if(isset($result['path_image'])){
            if(!file_exists($this->config['path_image'].$result['path_image'])){
                \File::makeDirectory($this->config['path_image'].$result['path_image'], $mode = 0777, true, true);
            }
            if(file_exists($this->config['path_image'].$result['path_image'].'/'.$imageName)){
                unlink($this->config['path_image'].$result['path_image'].'/'.$imageName);
            }
            $folder = isset($result['path_image']) ? $result['path_image'] : '';
            $path = $this->config['path_image'] . $folder.'/'. $imageName;
            $imageName = $folder.'/'.$imageName;
        }else{
            if(file_exists($this->config['path_image'].'/'.$imageName)){
                unlink($this->config['path_image'].'/'.$imageName);
            }
            $path = $this->config['path_image'] . $imageName;
        }

        $input = \File::put($path, $image);
        $image = Image::make($path)->resize(1000, 1000);
        $image->save($path);

        DB::table($this->config['db_prefix'].'product')->where('product_id',$product_id)->update([
            'image'     =>  'catalog/'.$imageName
        ]);
    }

        DB::table($this->config['db_prefix'].'product_description')->insert([
            'product_id'                =>  (int)$product_id,
            'language_id'               =>  $this->config['language_id'],
            'name'                      =>  $result['name'],
            'description'               =>  isset($result['description']) ? $result['description'] : $result['name'],
            'tag'                       =>  isset($result['tag']) ? $result['tag'] : str_replace(" ",",",$result['name']),
            'meta_title'                =>  isset($result['meta_title']) ? $result['meta_title'] : $result['name'],
            'meta_description'          =>  isset($result['meta_description']) ? $result['meta_description'] : $result['name'],
            'meta_keyword'              =>  isset($result['meta_keyword']) ? $result['meta_keyword'] : str_replace(" ",",",$result['name'])
        ]);


        DB::table($this->config['db_prefix'].'product_to_store')->insert([
            'product_id'                =>  (int)$product_id,
            'store_id'                  =>  $this->config['store_id']
        ]);


		if (isset($result['product_special'])) {
			foreach (DB::table($this->config['db_prefix'].'customer_group')->get() as $cg) {
                 DB::table($this->config['db_prefix'].'product_special')->insert([
                    'product_id'                =>  (int)$product_id,
                    'customer_group_id'         =>  (int)$cg->customer_group_id,
                    'priority'                  =>  1,
                    'price'                     =>  (float)$result['product_special']['price'],
                    'date_start'                =>  $result['product_special']['date_start'],
                    'date_end'                  =>  $result['product_special']['date_end']
                ]);
			}
		}

		if (isset($result['product_image'])) {
			foreach ($result['product_image'] as $key => $product) {

                $base64 = 'base64,'.$product['image'];
                $base64_str = substr($base64, strpos($base64, ",")+1);
                $image = base64_decode($base64_str);

                $imageName = Str::slug($result['name']) . '.jpg';
                if(isset($result['path_image'])){
                    if(!file_exists($this->config['path_image'].$result['path_image'])){
                        \File::makeDirectory($this->config['path_image'].$result['path_image'], $mode = 0777, true, true);
                    }
                    if(file_exists($this->config['path_image'].$result['path_image'].'/'.$imageName)){
                        unlink($this->config['path_image'].$result['path_image'].'/'.$imageName);
                    }
                    $folder = isset($result['path_image']) ? $result['path_image'] : '';
                    $path = $this->config['path_image'] . $folder.'/'. $imageName;
                    $imageName = $folder.'/'.$imageName;
                }else{
                    if(file_exists($this->config['path_image'].'/'.$imageName)){
                        unlink($this->config['path_image'].'/'.$imageName);
                    }
                    $path = $this->config['path_image'] . $imageName;
                }

                $input = \File::put($path, $image);
                $image = Image::make($path)->resize(1000, 1000);
                $image->save($path);
                    DB::table($this->config['db_prefix'].'product_image')->insert([
                        'product_id'    =>  (int)$product_id,
                        'image'         =>  'catalog/'.$imageName,
                        'sort_order'    =>  $key
                    ]);

			}
		}


		if (isset($result['product_category'])) {
			foreach ($result['product_category'] as $p_category_id) {
                DB::table($this->config['db_prefix'].'product_to_category')->insert([
                    'product_id'    =>  (int)$product_id,
                    'category_id'   =>  (int)$p_category_id
                ]);

			}
		}


        if (isset($result['product_seo_url']) && !empty($result['product_seo_url'])) {
            DB::table($this->config['db_prefix'].'seo_url')->insert([
                'store_id'      =>  $this->config['store_id'],
                'language_id'   =>  $this->config['language_id'],
                'query'         =>  "product_id=".(int)$product_id,
                'keyword'       =>  $result['product_seo_url']
            ]);

        } else {

        DB::table($this->config['db_prefix'].'seo_url')->insert([
            'store_id'      =>  $this->config['store_id'],
            'language_id'   =>  $this->config['language_id'],
            'query'         =>  "product_id=".(int)$product_id,
            'keyword'       =>  Str::slug($result['name'])
        ]);

        }

        $response_products[] = $product_id;

    }
        return response()->json(['status' => 'ok', 'data' => ['product_id' => $response_products]], 200);

    }


    public function show(string $id)
    {

        if($id == null){
            return response()->json('O parametro ID do produto é obrigatório!', 422);
        }


        $oc_product               =   DB::table('oc_product')->where('product_id',$id)->first();

        if(!$oc_product){
            return response()->json('Produto não existe!', 422);
        }


        $oc_product_descriptions  =   DB::table('oc_product_description')->where('product_id',$oc_product->product_id)->get();
        $oc_product_images        =   DB::table('oc_product_image')->where('product_id',$oc_product->product_id)->get();
        $oc_product_specials      =   DB::table('oc_product_special')->where('product_id',$oc_product->product_id)->get();
        $seo_url                  =   DB::table($this->config['db_prefix'].'seo_url')->where('query','product_id='.$oc_product->product_id)->first();

        $oc_product->seo_url      =  $seo_url != '' ? $seo_url->keyword : '';

        $product_descriptions = [];
        foreach($oc_product_descriptions as $oc_product_description){
            $product_descriptions[] = $oc_product_description;
        }

        $product_images       = [];
        foreach($oc_product_images as $oc_product_image){
            $product_images[]     = $oc_product_image;
        }

        $product_specials         = [];
        foreach($oc_product_specials as $oc_product_special){
            $product_specials[]     = $oc_product_special;
        }

        $product = [
            'product'              =>  $oc_product,
            'product_description'  =>  $product_descriptions,
            'product_images'       =>  $product_images,
            'product_special'      =>  $product_specials
        ];

    return response()->json($product, 200);


    }


    public function update(Request $request)
    {

        $data = $request->getContent();

        $response_products = [];

        foreach(json_decode($data , true) as $result){

        if(!isset($result['product_id']) || $result['product_id'] == ''){
            return response()->json('O campo product_id é obrigatório!', 422);
        }

        // if(!isset($result['product_category']) || $result['product_category'] == ''){
        //     return response()->json('O campo product_category é obrigatório!', 422);
        // }

        // if(!isset($result['model']) || $result['model'] == ''){
        //     return response()->json('O campo model é obrigatório!', 422);
        // }

        // if(!isset($result['name']) || $result['name'] == ''){
        //     return response()->json('O campo name é obrigatório!', 422);
        // }

        // if(!isset($result['status']) || $result['status'] == ''){
        //     return response()->json('O campo status é obrigatório!', 422);
        // }

        $product = DB::table($this->config['db_prefix'].'product')->where('product_id',$result['product_id'])->first();

        DB::table($this->config['db_prefix'].'product')->where('product_id',$result['product_id'])->update([
            'model'                 =>  isset($result['model']) ? $result['model'] : $product->model,
            'sku'                   =>  isset($result['sku']) ? (int)$result['sku'] : $product->sku,
            'upc'                   =>  isset($result['upc']) ? (int)$result['upc'] : $product->upc,
            'ean'                   =>  isset($result['ean']) ? (int)$result['ean'] : $product->ean,
            'jan'                   =>  isset($result['jan']) ? (int)$result['jan'] : $product->jan,
            'isbn'                  =>  isset($result['isbn']) ? (int)$result['isbn'] : $product->isbn,
            'mpn'                   =>  isset($result['mpn']) ? (int)$result['mpn'] : $product->mpn,
            'location'              =>  isset($result['location']) ? (int)$result['location'] : $product->location,
            'quantity'              =>  isset($result['quantity']) ? (int)$result['quantity'] : $product->quantity,
            'minimum'               =>  isset($result['minimum']) ? (int)$result['minimum'] : $product->minimum,
            'subtract'              =>  isset($result['subtract']) ? (int)$result['subtract'] : $product->subtract,
            'stock_status_id'       =>  isset($result['stock_status_id']) ? (int)$result['stock_status_id'] : $product->stock_status_id,
            'date_available'        =>  isset($result['date_available']) ? (int)$result['date_available'] : $product->date_available,
            'manufacturer_id'       =>  isset($result['manufacturer_id']) ? (int)$result['manufacturer_id'] : $product->manufacturer_id,
            'shipping'              =>  isset($result['shipping']) ? (int)$result['shipping'] : $product->shipping,
            'price'                 =>  isset($result['price']) ? (float)$result['price'] : $product->price,
            'points'                =>  isset($result['points']) ? (int)$result['points'] : $product->points,
            'weight'                =>  isset($result['weight']) ? (float)$result['weight'] : $product->weight,
            'weight_class_id'       =>  isset($result['weight_class_id']) ? (int)$result['weight_class_id'] : $product->weight_class_id,
            'length'                =>  isset($result['length']) ? (float)$result['length'] : $product->length,
            'width'                 =>  isset($result['width']) ? (float)$result['width'] : $product->width,
            'height'                =>  isset($result['height']) ? (float)$result['height'] : $product->height,
            'length_class_id'       =>  isset($result['length_class_id']) ? (int)$result['length_class_id'] : $product->length_class_id,
            'status'                =>  isset($result['status']) ? (int)$result['status'] : $product->status,
            'tax_class_id'          =>  isset($result['tax_class_id']) ? (int)$result['tax_class_id'] : $product->tax_class_id,
            'sort_order'            =>  isset($result['sort_order']) ? (int)$result['sort_order'] : $product->sort_order,
            'date_modified'         =>  NOW()
        ]);



        if (isset($result['image'])) {

            $base64 = 'base64,'.$result['image'];
            $base64_str = substr($base64, strpos($base64, ",")+1);
            $image = base64_decode($base64_str);

            $imageName = Str::slug($result['name']) . '.jpg';
            if(isset($result['path_image'])){
                if(!file_exists($this->config['path_image'].$result['path_image'])){
                    \File::makeDirectory($this->config['path_image'].$result['path_image'], $mode = 0777, true, true);
                }
                if(file_exists($this->config['path_image'].$result['path_image'].'/'.$imageName)){
                    unlink($this->config['path_image'].$result['path_image'].'/'.$imageName);
                }
                $folder = isset($result['path_image']) ? $result['path_image'] : '';
                $path = $this->config['path_image'] . $folder.'/'. $imageName;
                $imageName = $folder.'/'.$imageName;
            }else{
                if(file_exists($this->config['path_image'].'/'.$imageName)){
                    unlink($this->config['path_image'].'/'.$imageName);
                }
                $path = $this->config['path_image'] . $imageName;
            }

            $input = \File::put($path, $image);
            $image = Image::make($path)->resize(1000, 1000);
            $image->save($path);

                DB::table($this->config['db_prefix'].'product')->where('product_id',$result['product_id'])->update([
                    'image'     =>  'catalog/'.$imageName
                ]);
            }

        DB::table($this->config['db_prefix'].'product_description')->where('product_id',$result['product_id'])->delete();

          DB::table($this->config['db_prefix'].'product_description')->insert([
            'product_id'                =>  (int)$result['product_id'],
            'language_id'               =>  $this->config['language_id'],
            'name'                      =>  $result['name'],
            'description'               =>  isset($result['description']) ? $result['description'] : $result['name'],
            'tag'                       =>  isset($result['tag']) ? $result['tag'] : str_replace(" ",",",$result['name']),
            'meta_title'                =>  isset($result['meta_title']) ? $result['meta_title'] : $result['name'],
            'meta_description'          =>  isset($result['meta_description']) ? $result['meta_description'] : $result['name'],
            'meta_keyword'              =>  isset($result['meta_keyword']) ? $result['meta_keyword'] : str_replace(" ",",",$result['name'])
        ]);

        DB::table($this->config['db_prefix'].'product_to_store')->where('product_id',$result['product_id'])->delete();

        DB::table($this->config['db_prefix'].'product_to_store')->insert([
            'product_id'                =>  (int)$result['product_id'],
            'store_id'                  =>  $this->config['store_id']
        ]);





        if (isset($result['product_special'])) {
            DB::table($this->config['db_prefix'].'product_special')->where('product_id',$result['product_id'])->delete();
			foreach (DB::table($this->config['db_prefix'].'customer_group')->get() as $cg) {
                 DB::table($this->config['db_prefix'].'product_special')->insert([
                    'product_id'                =>  (int)$result['product_id'],
                    'customer_group_id'         =>  (int)$cg->customer_group_id,
                    'priority'                  =>  1,
                    'price'                     =>  (float)$result['product_special']['price'],
                    'date_start'                =>  $result['product_special']['date_start'],
                    'date_end'                  =>  $result['product_special']['date_end']
                ]);
			}
		}

		if (isset($result['product_image'])) {

            DB::table($this->config['db_prefix'].'product_image')->where('product_id',$result['product_id'])->delete();

			foreach ($result['product_image'] as $key => $product) {

                $base64 = 'base64,'.$product['image'];
                $base64_str = substr($base64, strpos($base64, ",")+1);
                $image = base64_decode($base64_str);

                $imageName = Str::slug($result['name']) . '.jpg';
                if(isset($result['path_image'])){
                    if(!file_exists($this->config['path_image'].$result['path_image'])){
                        \File::makeDirectory($this->config['path_image'].$result['path_image'], $mode = 0777, true, true);
                    }
                    if(file_exists($this->config['path_image'].$result['path_image'].'/'.$imageName)){
                        unlink($this->config['path_image'].$result['path_image'].'/'.$imageName);
                    }
                    $folder = isset($result['path_image']) ? $result['path_image'] : '';
                    $path = $this->config['path_image'] . $folder.'/'. $imageName;
                    $imageName = $folder.'/'.$imageName;
                }else{
                    if(file_exists($this->config['path_image'].'/'.$imageName)){
                        unlink($this->config['path_image'].'/'.$imageName);
                    }
                    $path = $this->config['path_image'] . $imageName;
                }

                $input = \File::put($path, $image);
                $image = Image::make($path)->resize(1000, 1000);
                $image->save($path);

                    DB::table($this->config['db_prefix'].'product_image')->insert([
                        'product_id'    =>  (int)$result['product_id'],
                        'image'         =>  'catalog/'.$imageName,
                        'sort_order'    =>  $key
                    ]);

			}
		}

        if (isset($result['product_category'])) {
            DB::table($this->config['db_prefix'].'product_to_category')->where('product_id',$result['product_id'])->delete();
			foreach ($result['product_category'] as $p_category_id) {
                DB::table($this->config['db_prefix'].'product_to_category')->insert([
                     'product_id'    =>  (int)$result['product_id'],
                    'category_id'   =>  (int)$p_category_id
                ]);

			}
		}

    DB::table($this->config['db_prefix'].'seo_url')->where('query','product_id='.(int)$result['product_id'])->delete();

  if (isset($result['product_seo_url']) && !empty($result['product_seo_url'])) {
            DB::table($this->config['db_prefix'].'seo_url')->insert([
                'store_id'      =>  $this->config['store_id'],
                'language_id'   =>  $this->config['language_id'],
                'query'         =>  "product_id=".(int)$result['product_id'],
                'keyword'       =>  $result['product_seo_url']
            ]);

        } else {

        DB::table($this->config['db_prefix'].'seo_url')->insert([
            'store_id'      =>  $this->config['store_id'],
            'language_id'   =>  $this->config['language_id'],
            'query'         =>  "product_id=".(int)$result['product_id'],
            'keyword'       =>  Str::slug($result['name'])
        ]);

        }

        $response_products[] = $result['product_id'];

    }
        return response()->json(['status' => 'ok', 'data' => ['product_id' => $response_products]], 200);

    }



    public function updateCustom(Request $request)
    {

        $data = $request->getContent();

        $response_products = [];

        foreach(json_decode($data , true) as $result){

        if(!isset($result['product_id']) || $result['product_id'] == ''){
            return response()->json('O campo product_id é obrigatório!', 422);
        }


        $product                = DB::table($this->config['db_prefix'].'product')->where('product_id',$result['product_id'])->first();
        $product_description    = DB::table($this->config['db_prefix'].'product_description')->where('product_id',$result['product_id'])->first();

        DB::table($this->config['db_prefix'].'product')->where('product_id',$result['product_id'])->update([
            'quantity'              =>  isset($result['quantity']) ? (int)$result['quantity'] : $product->quantity,
            'price'                 =>  isset($result['price']) ? (float)$result['price'] : $product->price,
            'date_modified'         =>  NOW()
        ]);


        if (isset($result['product_special'])) {
            DB::table($this->config['db_prefix'].'product_special')->where('product_id',$result['product_id'])->delete();
			foreach (DB::table($this->config['db_prefix'].'customer_group')->get() as $cg) {
                 DB::table($this->config['db_prefix'].'product_special')->insert([
                    'product_id'                =>  (int)$result['product_id'],
                    'customer_group_id'         =>  (int)$cg->customer_group_id,
                    'priority'                  =>  1,
                    'price'                     =>  (float)$result['product_special']['price'],
                    'date_start'                =>  $result['product_special']['date_start'],
                    'date_end'                  =>  $result['product_special']['date_end']
                ]);
			}
		}


        if (isset($result['image'])) {

            $base64 = 'base64,'.$result['image'];
            $base64_str = substr($base64, strpos($base64, ",")+1);
            $image = base64_decode($base64_str);

            $imageName = Str::slug($product_description->name) . '.jpg';
            if(isset($result['path_image'])){
                if(!file_exists($this->config['path_image'].$result['path_image'])){
                    \File::makeDirectory($this->config['path_image'].$result['path_image'], $mode = 0777, true, true);
                }
                if(file_exists($this->config['path_image'].$result['path_image'].'/'.$imageName)){
                    unlink($this->config['path_image'].$result['path_image'].'/'.$imageName);
                }
                $folder = isset($result['path_image']) ? $result['path_image'] : '';
                $path = $this->config['path_image'] . $folder.'/'. $imageName;
                $imageName = $folder.'/'.$imageName;
            }else{
                if(file_exists($this->config['path_image'].'/'.$imageName)){
                    unlink($this->config['path_image'].'/'.$imageName);
                }
                $path = $this->config['path_image'] . $imageName;
            }

            $input = \File::put($path, $image);
            $image = Image::make($path)->resize(1000, 1000);
            $image->save($path);

                DB::table($this->config['db_prefix'].'product')->where('product_id',$result['product_id'])->update([
                    'image'     =>  'catalog/'.$imageName
                ]);
            }

        if (isset($result['product_image'])) {

            DB::table($this->config['db_prefix'].'product_image')->where('product_id',$result['product_id'])->delete();

			foreach ($result['product_image'] as $key => $product) {

                $base64 = 'base64,'.$product['image'];
                $base64_str = substr($base64, strpos($base64, ",")+1);
                $image = base64_decode($base64_str);

                $imageName = Str::slug($product_description->name) . '.jpg';
                if(isset($result['path_image'])){
                    if(!file_exists($this->config['path_image'].$result['path_image'])){
                        \File::makeDirectory($this->config['path_image'].$result['path_image'], $mode = 0777, true, true);
                    }
                    if(file_exists($this->config['path_image'].$result['path_image'].'/'.$imageName)){
                        unlink($this->config['path_image'].$result['path_image'].'/'.$imageName);
                    }
                    $folder = isset($result['path_image']) ? $result['path_image'] : '';
                    $path = $this->config['path_image'] . $folder.'/'. $imageName;
                    $imageName = $folder.'/'.$imageName;
                }else{
                    if(file_exists($this->config['path_image'].'/'.$imageName)){
                        unlink($this->config['path_image'].'/'.$imageName);
                    }
                    $path = $this->config['path_image'] . $imageName;
                }

                $input = \File::put($path, $image);
                $image = Image::make($path)->resize(1000, 1000);
                $image->save($path);

                    DB::table($this->config['db_prefix'].'product_image')->insert([
                        'product_id'    =>  (int)$result['product_id'],
                        'image'         =>  'catalog/'.$imageName,
                        'sort_order'    =>  $key
                    ]);

			}
		}



        $response_products[] = $result['product_id'];

    }
        return response()->json(['status' => 'ok', 'data' => ['product_id' => $response_products]], 200);




    }

    public function destroy(string $product_id)
    {

        if($product_id == null){
            return response()->json('O parametro ID do produto é obrigatório!', 422);
        }

        DB::table($this->config['db_prefix'].'product')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_attribute')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_description')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_discount')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_filter')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_image')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_option')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_option_value')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_related')->where('related_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_reward')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_special')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_to_category')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_to_download')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_to_layout')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_to_store')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'product_recurring')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'review')->where('product_id',(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'seo_url')->where('query','product_id='.(int)$product_id)->delete();
        DB::table($this->config['db_prefix'].'coupon_product')->where('product_id',(int)$product_id)->delete();

        return response()->json(['status' => 'ok', 'data' => ['product_id' => $product_id]], 200);

    }


}
