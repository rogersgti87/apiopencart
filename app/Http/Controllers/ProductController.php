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

        $this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . (int)$data['tax_class_id'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_store'])) {
			foreach ($data['product_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

		if (!empty($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
				}
			}
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = " . (int)$product_id);

		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $product_recurring) {
				$query = $this->db->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_recurring` WHERE `product_id` = '" . (int)$product_id . "' AND `customer_group_id` = '" . (int)$product_recurring['customer_group_id'] . "' AND `recurring_id` = '" . (int)$product_recurring['recurring_id'] . "'");

				if (!$query->num_rows) {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = '" . (int)$product_id . "', `customer_group_id` = '" . (int)$product_recurring['customer_group_id'] . "', `recurring_id` = '" . (int)$product_recurring['recurring_id'] . "'");
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($product_image['image']) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_download'])) {
			foreach ($data['product_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_filter'])) {
			foreach ($data['product_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $value) {
				if ((int)$value['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$value['points'] . "'");
				}
			}
		}

		// SEO URL
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");

		if (isset($data['product_seo_url'])) {
			foreach ($data['product_seo_url']as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");

		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}


    }


    public function destroy(string $product_id)
    {

        if($product_id == null){
            return response()->json('O parametro ID do produto é obrigatório!', 422);
        }

    }


}
