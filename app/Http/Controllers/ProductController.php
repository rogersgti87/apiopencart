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


        return response()->json($data, 200);
    }


    public function store(Request $request)
    {

        $data = $request->all();

        if(!isset($data['product_category']) || $data['product_category'] == ''){
            return response()->json('O campo product_category é obrigatório!', 422);
        }

        if(!isset($data['model']) || $data['model'] == ''){
            return response()->json('O campo model é obrigatório!', 422);
        }

        if(!isset($data['name']) || $data['name'] == ''){
            return response()->json('O campo name é obrigatório!', 422);
        }

        if(!isset($data['status']) || $data['status'] == ''){
            return response()->json('O campo status é obrigatório!', 422);
        }

        $product_id = DB::table($this->config['db_prefix'].'product')->insertGetId([
            'model'                 =>  $data['model'],
            'sku'                   =>  isset($data['sku']) ? (int)$data['sku'] : '',
            'upc'                   =>  isset($data['upc']) ? (int)$data['upc'] : '',
            'ean'                   =>  isset($data['ean']) ? (int)$data['ean'] : '',
            'jan'                   =>  isset($data['jan']) ? (int)$data['jan'] : '',
            'isbn'                  =>  isset($data['isbn']) ? (int)$data['isbn'] : '',
            'mpn'                   =>  isset($data['mpn']) ? (int)$data['mpn'] : '',
            'location'              =>  isset($data['location']) ? (int)$data['location'] : '',
            'quantity'              =>  isset($data['quantity']) ? (int)$data['quantity'] : 0,
            'minimum'               =>  isset($data['minimum']) ? (int)$data['minimum'] : 1,
            'subtract'              =>  isset($data['subtract']) ? (int)$data['subtract'] : 1,
            'stock_status_id'       =>  isset($data['stock_status_id']) ? (int)$data['stock_status_id'] : 7,
            'date_available'        =>  isset($data['date_available']) ? (int)$data['date_available'] : date('Y-m-d'),
            'manufacturer_id'       =>  isset($data['manufacturer_id']) ? (int)$data['manufacturer_id'] : 0,
            'shipping'              =>  isset($data['shipping']) ? (int)$data['shipping'] : 1,
            'price'                 =>  isset($data['price']) ? (float)$data['price'] : 0.00,
            'points'                =>  isset($data['points']) ? (int)$data['points'] : 0,
            'weight'                =>  isset($data['weight']) ? (float)$data['weight'] : 0.00,
            'weight_class_id'       =>  isset($data['weight_class_id']) ? (int)$data['weight_class_id'] : 1,
            'length'                =>  isset($data['length']) ? (float)$data['length'] : 0.00,
            'width'                 =>  isset($data['width']) ? (float)$data['width'] : 0.00,
            'height'                =>  isset($data['height']) ? (float)$data['height'] : 0.00,
            'length_class_id'       =>  isset($data['length_class_id']) ? (int)$data['length_class_id'] : 1,
            'status'                =>  (int)$data['status'],
            'tax_class_id'          =>  isset($data['tax_class_id']) ? (int)$data['tax_class_id'] : 0,
            'sort_order'            =>  isset($data['sort_order']) ? (int)$data['sort_order'] : 0,
            'date_added'            =>  NOW(),
            'date_modified'         =>  NOW()

        ]);


 //update image table category
 if (isset($data['image'])) {

    $extension = explode('/', mime_content_type($data['image']))[1];

    if($extension == 'jpeg'){
        $extension == 'jpg';
    } else if($extension == 'png'){
        $extension == 'png';
    } else if($extension == 'gif'){
        $extension == 'gif';
    }else{
        $extension == 'jpg';
    }

    $image = str_replace('data:image/'.$extension.';base64,', '', $data['image']);
    $image = str_replace(' ', '+', $image);
    $imageName = Str::slug($data['name']) . '.'.$extension;
        if(isset($data['path_image'])){
            if(!file_exists($this->config['path_image'].$data['path_image'])){
                \File::makeDirectory($this->config['path_image'].$data['path_image'], $mode = 0777, true, true);
            }
            if(file_exists($this->config['path_image'].$data['path_image'].'/'.$imageName)){
                unlink($this->config['path_image'].$data['path_image'].'/'.$imageName);
            }
            $folder = isset($data['path_image']) ? $data['path_image'] : '';
            $path = $this->config['path_image'] . $folder.'/'. $imageName;
            $imageName = $folder.'/'.$imageName;
        }else{
            if(file_exists($this->config['path_image'].'/'.$imageName)){
                unlink($this->config['path_image'].'/'.$imageName);
            }
            $path = $this->config['path_image'] . $imageName;
        }

        $input = \File::put($path, base64_decode($image));
        $image = Image::make($path)->resize(1000, 1000);
        $result = $image->save($path);

        DB::table($this->config['db_prefix'].'product')->where('product_id',$product_id)->update([
            'image'     =>  'catalog/'.$imageName
        ]);
    }

        DB::table($this->config['db_prefix'].'product_description')->insert([
            'product_id'                =>  (int)$product_id,
            'language_id'               =>  $this->config['language_id'],
            'name'                      =>  $data['name'],
            'description'               =>  isset($data['description']) ? $data['description'] : $data['name'],
            'tag'                       =>  isset($data['tag']) ? $data['tag'] : str_replace(" ",",",$data['name']),
            'meta_title'                =>  isset($data['meta_title']) ? $data['meta_title'] : $data['name'],
            'meta_description'          =>  isset($data['meta_description']) ? $data['meta_description'] : $data['name'],
            'meta_keyword'              =>  isset($data['meta_keyword']) ? $data['meta_keyword'] : str_replace(" ",",",$data['name'])
        ]);


        DB::table($this->config['db_prefix'].'product_to_store')->insert([
            'product_id'                =>  (int)$product_id,
            'store_id'                  =>  $this->config['store_id']
        ]);


		if (isset($data['product_special'])) {
			foreach (DB::table($this->config['db_prefix'].'customer_group')->get() as $cg) {
                 DB::table($this->config['db_prefix'].'product_special')->insert([
                    'product_id'                =>  (int)$product_id,
                    'customer_group_id'         =>  (int)$cg->customer_group_id,
                    'priority'                  =>  1,
                    'price'                     =>  (float)$data['product_special']['price'],
                    'date_start'                =>  $data['product_special']['date_start'],
                    'date_end'                  =>  $data['product_special']['date_end']
                ]);
			}
		}

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $key => $product) {

                $extension = explode('/', mime_content_type($product['image']))[1];

                if($extension == 'jpeg'){
                    $extension == 'jpg';
                } else if($extension == 'png'){
                    $extension == 'png';
                } else if($extension == 'gif'){
                    $extension == 'gif';
                }else{
                    $extension == 'jpg';
                }

                $image = str_replace('data:image/'.$extension.';base64,', '', $product['image']);
                $image = str_replace(' ', '+', $image);
                $imageName = Str::slug($data['name'].$key) .'.'. $extension;
                    if(isset($data['path_image'])){
                        if(!file_exists($this->config['path_image'].$data['path_image'])){
                            \File::makeDirectory($this->config['path_image'].$data['path_image'], $mode = 0777, true, true);
                        }
                        if(file_exists($this->config['path_image'].$data['path_image'].'/'.$imageName)){
                            unlink($this->config['path_image'].$data['path_image'].'/'.$imageName);
                        }
                        $folder = isset($data['path_image']) ? $data['path_image'] : '';
                        $path = $this->config['path_image'] . $folder.'/'. $imageName;
                        $imageName = $folder.'/'.$imageName;
                    }else{
                        if(file_exists($this->config['path_image'].'/'.$imageName)){
                            unlink($this->config['path_image'].'/'.$imageName);
                        }
                        $path = $this->config['path_image'] . $imageName;
                    }

                    $input = \File::put($path, base64_decode($image));
                    $image = Image::make($path)->resize(1000, 1000);
                    $result = $image->save($path);

                    DB::table($this->config['db_prefix'].'product_image')->insert([
                        'product_id'    =>  (int)$product_id,
                        'image'         =>  'catalog/'.$imageName,
                        'sort_order'    =>  $key
                    ]);

			}
		}


		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
                DB::table($this->config['db_prefix'].'product_to_category')->insert([
                    'product_id'    =>  (int)$product_id,
                    'category_id'   =>  (int)$category_id
                ]);

			}
		}


        if (isset($data['product_seo_url']) && !empty($data['product_seo_url'])) {
            DB::table($this->config['db_prefix'].'seo_url')->insert([
                'store_id'      =>  $this->config['store_id'],
                'language_id'   =>  $this->config['language_id'],
                'query'         =>  "product_id=".(int)$product_id,
                'keyword'       =>  $data['product_seo_url']
            ]);

        } else {

        DB::table($this->config['db_prefix'].'seo_url')->insert([
            'store_id'      =>  $this->config['store_id'],
            'language_id'   =>  $this->config['language_id'],
            'query'         =>  "product_id=".(int)$product_id,
            'keyword'       =>  Str::slug($data['name'])
        ]);

        }

        return response()->json(['status' => 'ok', 'data' => ['product_id' => $product_id]], 200);

    }


    public function show(string $id)
    {

        if($id == null){
            return response()->json('O parametro ID do produto é obrigatório!', 422);
        }

    }


    public function update(Request $request, string $product_id)
    {

        $data = $request->all();

        $product = DB::table($this->config['db_prefix'].'product')->where('product_id',$product_id)->first();

        DB::table($this->config['db_prefix'].'product')->where('product_id',$product_id)->update([
            'model'                 =>  $data['model'],
            'sku'                   =>  isset($data['sku']) ? (int)$data['sku'] : $product->sku,
            'upc'                   =>  isset($data['upc']) ? (int)$data['upc'] : $product->upc,
            'ean'                   =>  isset($data['ean']) ? (int)$data['ean'] : $product->ean,
            'jan'                   =>  isset($data['jan']) ? (int)$data['jan'] : $product->jan,
            'isbn'                  =>  isset($data['isbn']) ? (int)$data['isbn'] : $product->isbn,
            'mpn'                   =>  isset($data['mpn']) ? (int)$data['mpn'] : $product->mpn,
            'location'              =>  isset($data['location']) ? (int)$data['location'] : $product->location,
            'quantity'              =>  isset($data['quantity']) ? (int)$data['quantity'] : $product->quantity,
            'minimum'               =>  isset($data['minimum']) ? (int)$data['minimum'] : $product->minimum,
            'subtract'              =>  isset($data['subtract']) ? (int)$data['subtract'] : $product->subtract,
            'stock_status_id'       =>  isset($data['stock_status_id']) ? (int)$data['stock_status_id'] : $product->stock_status_id,
            'date_available'        =>  isset($data['date_available']) ? (int)$data['date_available'] : $product->date_available,
            'manufacturer_id'       =>  isset($data['manufacturer_id']) ? (int)$data['manufacturer_id'] : $product->manufacturer_id,
            'shipping'              =>  isset($data['shipping']) ? (int)$data['shipping'] : $product->shipping,
            'price'                 =>  isset($data['price']) ? (float)$data['price'] : $product->price,
            'points'                =>  isset($data['points']) ? (int)$data['points'] : $product->points,
            'weight'                =>  isset($data['weight']) ? (float)$data['weight'] : $product->weight,
            'weight_class_id'       =>  isset($data['weight_class_id']) ? (int)$data['weight_class_id'] : $product->weight_class_id,
            'length'                =>  isset($data['length']) ? (float)$data['length'] : $product->length,
            'width'                 =>  isset($data['width']) ? (float)$data['width'] : $product->width,
            'height'                =>  isset($data['height']) ? (float)$data['height'] : $product->height,
            'length_class_id'       =>  isset($data['length_class_id']) ? (int)$data['length_class_id'] : $product->length_class_id,
            'status'                =>  (int)$data['status'],
            'tax_class_id'          =>  isset($data['tax_class_id']) ? (int)$data['tax_class_id'] : $product->tax_class_id,
            'sort_order'            =>  isset($data['sort_order']) ? (int)$data['sort_order'] : $product->sort_order,
            'date_modified'         =>  NOW()
        ]);



        if (isset($data['image'])) {

            $extension = explode('/', mime_content_type($data['image']))[1];

            if($extension == 'jpeg'){
                $extension == 'jpg';
            } else if($extension == 'png'){
                $extension == 'png';
            } else if($extension == 'gif'){
                $extension == 'gif';
            }else{
                $extension == 'jpg';
            }

            $image = str_replace('data:image/'.$extension.';base64,', '', $data['image']);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::slug($data['name']) . '.'.$extension;
                if(isset($data['path_image'])){
                    if(!file_exists($this->config['path_image'].$data['path_image'])){
                        \File::makeDirectory($this->config['path_image'].$data['path_image'], $mode = 0777, true, true);
                    }
                    if(file_exists($this->config['path_image'].$data['path_image'].'/'.$imageName)){
                        unlink($this->config['path_image'].$data['path_image'].'/'.$imageName);
                    }
                    $folder = isset($data['path_image']) ? $data['path_image'] : '';
                    $path = $this->config['path_image'] . $folder.'/'. $imageName;
                    $imageName = $folder.'/'.$imageName;
                }else{
                    if(file_exists($this->config['path_image'].'/'.$imageName)){
                        unlink($this->config['path_image'].'/'.$imageName);
                    }
                    $path = $this->config['path_image'] . $imageName;
                }

                $input = \File::put($path, base64_decode($image));
                $image = Image::make($path)->resize(1000, 1000);
                $result = $image->save($path);

                DB::table($this->config['db_prefix'].'product')->where('product_id',$product_id)->update([
                    'image'     =>  'catalog/'.$imageName
                ]);
            }

        DB::table($this->config['db_prefix'].'product_description')->where('product_id',$product_id)->delete();

          DB::table($this->config['db_prefix'].'product_description')->insert([
            'product_id'                =>  (int)$product_id,
            'language_id'               =>  $this->config['language_id'],
            'name'                      =>  $data['name'],
            'description'               =>  isset($data['description']) ? $data['description'] : $data['name'],
            'tag'                       =>  isset($data['tag']) ? $data['tag'] : str_replace(" ",",",$data['name']),
            'meta_title'                =>  isset($data['meta_title']) ? $data['meta_title'] : $data['name'],
            'meta_description'          =>  isset($data['meta_description']) ? $data['meta_description'] : $data['name'],
            'meta_keyword'              =>  isset($data['meta_keyword']) ? $data['meta_keyword'] : str_replace(" ",",",$data['name'])
        ]);

        DB::table($this->config['db_prefix'].'product_to_store')->where('product_id',$product_id)->delete();

        DB::table($this->config['db_prefix'].'product_to_store')->insert([
            'product_id'                =>  (int)$product_id,
            'store_id'                  =>  $this->config['store_id']
        ]);





        if (isset($data['product_special'])) {
            DB::table($this->config['db_prefix'].'product_special')->where('product_id',$product_id)->delete();
			foreach (DB::table($this->config['db_prefix'].'customer_group')->get() as $cg) {
                 DB::table($this->config['db_prefix'].'product_special')->insert([
                    'product_id'                =>  (int)$product_id,
                    'customer_group_id'         =>  (int)$cg->customer_group_id,
                    'priority'                  =>  1,
                    'price'                     =>  (float)$data['product_special']['price'],
                    'date_start'                =>  $data['product_special']['date_start'],
                    'date_end'                  =>  $data['product_special']['date_end']
                ]);
			}
		}

		if (isset($data['product_image'])) {

            DB::table($this->config['db_prefix'].'product_image')->where('product_id',$product_id)->delete();

			foreach ($data['product_image'] as $key => $product) {

                $extension = explode('/', mime_content_type($product['image']))[1];

                if($extension == 'jpeg'){
                    $extension == 'jpg';
                } else if($extension == 'png'){
                    $extension == 'png';
                } else if($extension == 'gif'){
                    $extension == 'gif';
                }else{
                    $extension == 'jpg';
                }

                $image = str_replace('data:image/'.$extension.';base64,', '', $product['image']);
                $image = str_replace(' ', '+', $image);
                $imageName = Str::slug($data['name'].$key) .'.'. $extension;
                    if(isset($data['path_image'])){
                        if(!file_exists($this->config['path_image'].$data['path_image'])){
                            \File::makeDirectory($this->config['path_image'].$data['path_image'], $mode = 0777, true, true);
                        }
                        if(file_exists($this->config['path_image'].$data['path_image'].'/'.$imageName)){
                            unlink($this->config['path_image'].$data['path_image'].'/'.$imageName);
                        }
                        $folder = isset($data['path_image']) ? $data['path_image'] : '';
                        $path = $this->config['path_image'] . $folder.'/'. $imageName;
                        $imageName = $folder.'/'.$imageName;
                    }else{
                        if(file_exists($this->config['path_image'].'/'.$imageName)){
                            unlink($this->config['path_image'].'/'.$imageName);
                        }
                        $path = $this->config['path_image'] . $imageName;
                    }

                    $input = \File::put($path, base64_decode($image));
                    $image = Image::make($path)->resize(1000, 1000);
                    $result = $image->save($path);

                    DB::table($this->config['db_prefix'].'product_image')->insert([
                        'product_id'    =>  (int)$product_id,
                        'image'         =>  'catalog/'.$imageName,
                        'sort_order'    =>  $key
                    ]);

			}
		}

        if (isset($data['product_category'])) {
            DB::table($this->config['db_prefix'].'product_to_category')->where('product_id',$product_id)->delete();
			foreach ($data['product_category'] as $category_id) {
                DB::table($this->config['db_prefix'].'product_to_category')->insert([
                    'product_id'    =>  (int)$product_id,
                    'category_id'   =>  (int)$category_id
                ]);

			}
		}

    DB::table($this->config['db_prefix'].'seo_url')->where('query','product_id='.(int)$product_id)->delete();

  if (isset($data['product_seo_url']) && !empty($data['product_seo_url'])) {
            DB::table($this->config['db_prefix'].'seo_url')->insert([
                'store_id'      =>  $this->config['store_id'],
                'language_id'   =>  $this->config['language_id'],
                'query'         =>  "product_id=".(int)$product_id,
                'keyword'       =>  $data['product_seo_url']
            ]);

        } else {

        DB::table($this->config['db_prefix'].'seo_url')->insert([
            'store_id'      =>  $this->config['store_id'],
            'language_id'   =>  $this->config['language_id'],
            'query'         =>  "product_id=".(int)$product_id,
            'keyword'       =>  Str::slug($data['name'])
        ]);

        }


        return response()->json(['status' => 'ok', 'data' => ['product_id' => $product_id]], 200);

    }


    public function destroy(string $product_id)
    {

        if($product_id == null){
            return response()->json('O parametro ID do produto é obrigatório!', 422);
        }

    }


}
