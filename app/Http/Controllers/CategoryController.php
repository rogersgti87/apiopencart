<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Image;
use Illuminate\Support\Facades\Config;

class CategoryController extends Controller
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
            //Columns Table oc_category
            'c.category_id as c_category_id','c.image as c_image','c.parent_id as c_parent_id','c.top as c_top',
            'c.column as c_column','c.sort_order as c_sort_order','c.status as c_status','c.date_added as c_date_added','c.date_modified as c_date_modified',
            //Columns table oc_category_description
            'cd.category_id as cd_category_id','cd.language_id as cd_language_id','cd.name as cd_name','cd.description as cd_description','cd.meta_title as cd_meta_title',
            'cd.meta_description as cd_meta_description','cd.meta_keyword as cd_meta_keyword',
            //Columns table oc_category_filter
            'cf.category_id as cf_category_id','cf.filter_id as cf_filter_id',
            //Columns table oc_category_path
            'cp.category_id as cp_category_id','cp.path_id as cp_path_id','cp.level as cp_level',
            //Columns table oc_category_to_layout
            'cl.category_id as cl_category_id','cl.store_id as cl_store_id','cl.layout_id as cl_layout_id',
            //Columns table oc_category_to_store
            'cs.category_id as cs_category_id','cs.store_id as cs_store_id',
        ];
        $data  = DB::table('oc_category as c')
                        ->select($columns)
                        ->join('oc_category_description as cd','c.category_id','cd.category_id')
                        ->leftjoin('oc_category_filter as cf','c.category_id','cf.category_id')
                        ->leftjoin('oc_category_path as cp','c.category_id','cp.category_id')
                        ->leftjoin('oc_category_to_layout as cl','c.category_id','cl.category_id')
                        ->leftjoin('oc_category_to_store as cs','c.category_id','cs.category_id')
                        ->paginate(20);

        $data = $data->toArray();

        foreach($data['data'] as $key => $result){

            //$seo_url = DB::table($this->config['db_prefix'].'seo_url')->where('query','category_id='.(int)$result->c_category_id)->first();

            $data['data'][$key] = [
                'category'  =>  [
                    'category_id'           => $result->c_category_id,
                    'image'                 => $result->c_image,
                    'parent_id'             => $result->c_parent_id,
                    'top'                   => $result->c_top,
                    'column'                => $result->c_column,
                    'sort_order'            => $result->c_sort_order,
                    'status'                => $result->c_status,
                    'date_added'            => $result->c_date_added,
                    'date_modified'         => $result->c_date_modified,
                    //'seo_url'               => $seo_url->keyword
                    'name'                  => $result->cd_name,
                    'description'           => $result->cd_description,
                    'meta_title'            => $result->cd_meta_title,
                    'meta_description'      => $result->cd_meta_description,
                    'meta_keyword'          => $result->cd_meta_keyword,
                    'category_filter'       =>  [
                    'category_id'           => $result->cf_category_id,
                    'filter_id'             => $result->cf_filter_id,
                ]
                ],


            ];

        }

        return response()->json($data, 200);
    }


    public function store(Request $request)
    {

        $data = $request->getContent();

        $response_categories = [];


        foreach(json_decode($data , true) as $result){

        if(!isset($result['name']) || $result['name'] == ''){
            return response()->json('O campo name é obrigatório!', 422);
        }

        if(!isset($result['status']) || $result['status'] == ''){
            return response()->json('O campo status é obrigatório!', 422);
        }

        //Insert data table oc_category
        $category_id = DB::table($this->config['db_prefix'].'category')->insertGetId([
            'parent_id'     =>  isset($result['parent_id']) ? (int)$result['parent_id'] : 0,
            'top'           =>  isset($result['top']) ? (int)$result['top'] : 1,
            'column'        =>  isset($result['column']) ? (int)$result['column'] : 1,
            'sort_order'    =>  isset($result['sort_order']) ? (int)$result['sort_order'] : 1,
            'status'        =>  (int)$result['status'],
            'date_modified' =>  NOW(),
            'date_added'    =>  NOW()

        ]);

        //update image table category
        if (isset($result['image'])) {

            $extension = explode('/', mime_content_type($result['image']))[1];
            // if($extension == 'jpeg'){
            //     $extension = 'jpg';
            // } else if($extension == 'png'){
            //     $extension = 'png';
            // } else if($extension == 'gif'){
            //     $extension = 'gif';
            // }else{
            //     $extension = 'jpg';
            // }

            $image = str_replace('data:image/'.$extension.';base64,', '', $result['image']);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::slug($result['name']) .'.'. $extension;
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

            $input = \File::put($path, base64_decode($image));
            $image = Image::make($path)->resize(1000, 1000);
            $image->save($path);

            DB::table($this->config['db_prefix'].'category')->where('category_id',$category_id)->update([
                'image'     =>  'catalog/'.$imageName
            ]);
		}

        //Insert data table oc_category_description
            DB::table($this->config['db_prefix'].'category_description')->insert([
                'category_id'       =>  (int)$category_id,
                'language_id'       =>  $this->config['language_id'],
                'name'              =>  $result['name'],
                'description'       =>  isset($result['description']) ? $result['description'] : $result['name'],
                'meta_title'        =>  isset($result['meta_title']) ? $result['meta_title'] : $result['name'],
                'meta_description'  =>  isset($result['meta_description']) ? $result['meta_description'] : $result['name'],
                'meta_keyword'      =>  isset($result['meta_keyword']) ? $result['meta_keyword'] : str_replace(' ',',',$result['name'])
            ]);

        $level = 0;

        $query = DB::table($this->config['db_prefix'].'category_path')->where('category_id',isset($result['parent_id']) ? (int)$result['parent_id'] : 0)->orderby('level','ASC')->get();

		foreach ($query as $resultcp) {
            DB::table($this->config['db_prefix'].'category_path')->insert([
                'category_id'   =>  (int)$category_id,
                'path_id'       =>  (int)$resultcp->path_id,
                'level'         =>  (int)$level
            ]);

			$level++;
		}

        DB::table($this->config['db_prefix'].'category_path')->insert([
            'category_id' =>  (int)$category_id,
            'path_id'     =>  (int)$category_id,
            'level'       =>  (int)$level
        ]);


        if (isset($result['category_filter'])) {
			foreach ($result['category_filter'] as $filter_id) {
                DB::table($this->config['db_prefix'].'category_filter')->insert([
                    'category_id'   =>  (int)$category_id,
                    'filter_id'     =>  (int)$filter_id
                ]);
			}
		}


        DB::table($this->config['db_prefix'].'category_to_store')->insert([
            'category_id'   =>  (int)$category_id,
            'store_id'      =>  $this->config['store_id']
        ]);



        if (isset($result['category_seo_url']) && !empty($result['category_seo_url'])) {
                        DB::table($this->config['db_prefix'].'seo_url')->insert([
                            'store_id'      =>  $this->config['store_id'],
                            'language_id'   =>  $this->config['language_id'],
                            'query'         =>  "category_id=".(int)$category_id,
                            'keyword'       =>  $result['category_seo_url']
                        ]);

		} else {

            DB::table($this->config['db_prefix'].'seo_url')->insert([
                'store_id'      =>  $this->config['store_id'],
                'language_id'   =>  $this->config['language_id'],
                'query'         =>  "category_id=".(int)$category_id,
                'keyword'       =>  Str::slug($result['name'])
            ]);

        }

        $response_categories[] = $category_id;
    }

    return response()->json(['status' => 'ok', 'data' => ['category_id' => $response_categories]], 200);

    }


    public function show(string $id)
    {

        if($id == null){
            return response()->json('O parametro ID da categoria é obrigatório!', 422);
        }

        $oc_category                =   DB::table('oc_category')->where('category_id',$id)->first();

        if(!$oc_category){
            return response()->json('Categoria não existe!', 422);
        }

        $oc_category_descriptions   =   DB::table('oc_category_description')->where('category_id',$oc_category->category_id)->get();
        $oc_category_filters        =   DB::table('oc_category_filter')->where('category_id',$oc_category->category_id)->get();
        $oc_category_paths          =   DB::table('oc_category_path')->where('category_id',$oc_category->category_id)->get();
        $oc_category_to_layouts     =   DB::table('oc_category_to_layout')->where('category_id',$oc_category->category_id)->get();
        $oc_category_to_stores      =   DB::table('oc_category_to_store')->where('category_id',$oc_category->category_id)->get();
        $seo_url                    =   DB::table($this->config['db_prefix'].'seo_url')->where('query','category_id='.(int)$id)->first();

        $oc_category->seo_url       =  $seo_url != null ? $seo_url->keyword : '';

        $category_descriptions = [];
        foreach($oc_category_descriptions as $oc_category_description){
            $category_descriptions[] = $oc_category_description;
        }

        $category_filters       = [];
        foreach($oc_category_filters as $oc_category_filter){
            $category_filters[]     = $oc_category_filter;
        }

        $category_paths         = [];
        foreach($oc_category_paths as $oc_category_path){
            $category_paths[]     = $oc_category_path;
        }

        $category_to_layouts    = [];
        foreach($oc_category_to_layouts as $oc_category_to_layout){
            $category_to_layouts[]     = $oc_category_to_layout;
        }

        $category_to_stores    = [];
        foreach($oc_category_to_stores as $oc_category_to_store){
            $category_to_stores[]     = $oc_category_to_store;
        }


            $category = [
                'category'              =>  $oc_category,
                'category_description'  =>  $category_descriptions,
                'category_filter'       =>  $category_filters,
                'oc_category_path'      =>  $category_paths,
                'category_to_layout'    =>  $category_to_layouts,
                'category_to_store'     =>  $category_to_stores
            ];

        return response()->json($category, 200);

    }


    public function update(Request $request)
    {

        $data = $request->getContent();


        $response_categories = [];

        foreach(json_decode($data , true) as $result){

            if(!isset($result['category_id']) || $result['category_id'] == ''){
                return response()->json('O campo category_id é obrigatório!', 422);
            }

        if(!isset($result['name']) || $result['name'] == ''){
            return response()->json('O campo name é obrigatório!', 422);
        }

        if(!isset($result['status']) || $result['status'] == ''){
            return response()->json('O campo status é obrigatório!', 422);
        }

        $category = DB::table($this->config['db_prefix'].'category')->where('category_id',$result['category_id'])->first();

        DB::table($this->config['db_prefix'].'category')->where('category_id',$result['category_id'])->update([
            'parent_id'     =>  isset($result['parent_id'])   ? (int)$result['parent_id']   : $category->parent_id,
            'top'           =>  isset($result['top'])         ? (int)$result['top']         : $category->top,
            'column'        =>  isset($result['column'])      ? (int)$result['column']      : $category->column,
            'sort_order'    =>  isset($result['sort_order'])  ? (int)$result['sort_order']  : $category->sort_order,
            'status'        =>  (int)$result['status'],
            'date_modified' =>  NOW()
        ]);

        //update image table category
        if (isset($result['image'])) {

            $extension = explode('/', mime_content_type($result['image']))[1];

            // if($extension == 'jpeg'){
            //     $extension = 'jpg';
            // } else if($extension == 'png'){
            //     $extension = 'png';
            // } else if($extension == 'gif'){
            //     $extension = 'gif';
            // }else{
            //     $extension = 'jpg';
            // }

            $image = str_replace('data:image/'.$extension.';base64,', '', $result['image']);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::slug($result['name']) .'.'. $extension;
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

            $input = \File::put($path, base64_decode($image));
            $image = Image::make($path)->resize(1000, 1000);
            $image->save($path);

            DB::table($this->config['db_prefix'].'category')->where('category_id',$result['category_id'])->update([
                'image'     =>  'catalog/'.$imageName
            ]);
		}

        DB::table($this->config['db_prefix'].'category_description')->where('category_id',(int)$result['category_id'])->delete();

         //Insert data table oc_category_description
         DB::table($this->config['db_prefix'].'category_description')->insert([
            'category_id'       =>  (int)$result['category_id'],
            'language_id'       =>  $this->config['language_id'],
            'name'              =>  $result['name'],
            'description'       =>  isset($result['description']) ? $result['description'] : $result['name'],
            'meta_title'        =>  isset($result['meta_title']) ? $result['meta_title'] : $result['name'],
            'meta_description'  =>  isset($result['meta_description']) ? $result['meta_description'] : $result['name'],
            'meta_keyword'      =>  isset($result['meta_keyword']) ? $result['meta_keyword'] : str_replace(' ',',',$result['name'])
        ]);


        $query = DB::table($this->config['db_prefix'].'category_path')->where('path_id',(int)$result['category_id'])->orderby('level','ASC')->get();

        DB::table($this->config['db_prefix'].'category_path')->where('category_id',(int)$result['category_id'])->delete();

        if (count($query) > 0) {
			foreach ($query as $category_path) {
                DB::table($this->config['db_prefix'].'category_path')->where('category_id',(int)$category_path->category_id)->where('level','<',(int)$category_path->level)->delete();
				$path = array();

                $query = DB::table($this->config['db_prefix'].'category_path')->where('category_id',isset($data['parent_id']) ? (int)$data['parent_id'] : 0)->orderby('level','ASC')->get();

				foreach ($query as $resultpath1) {
					$path[] = $resultpath1->path_id;
				}

                $query = DB::table($this->config['db_prefix'].'category_path')->where('category_id',(int)$category_path->category_id)->orderby('level','ASC')->get();

				foreach ($query as $resultpath) {
					$path[] = $resultpath->path_id;
				}

				$level = 0;

				foreach ($path as $path_id) {
                    DB::table($this->config['db_prefix'].'category_path')->updateOrInsert(
                        [
                            'category_id'   =>  (int)$category_path->category_id,
                        ],
                        [
                        'category_id'   =>  (int)$category_path->category_id,
                        'path_id'       =>  (int)$path_id,
                        'level'         =>  (int)$level
                    ]);

					$level++;
				}
			}
		} else {

            //DB::table($this->config['db_prefix'].'category_path')->where('category_id',(int)$result['category_id'])->delete();

			$level = 0;

            $query = DB::table($this->config['db_prefix'].'category_path')->where('category_id',isset($result['parent_id']) ? (int)$result['parent_id'] : 0)->orderby('level','ASC')->get();

			foreach ($query as $resultcp) {

                DB::table($this->config['db_prefix'].'category_path')->insert([
                    'category_id'   => (int)$result['category_id'],
                    'path_id'       =>  (int)$resultcp['path_id'],
                    'level'         =>  (int)$level
                ]);

				$level++;
			}

            DB::table($this->config['db_prefix'].'category_path')->updateOrInsert([
                'category_id'   =>  (int)$result['category_id'],
                'path_id'       =>  (int)$result['category_id'],
                'level'         =>  (int)$level
            ]);
		}

        DB::table($this->config['db_prefix'].'category_filter')->where('category_id',(int)$result['category_id'])->delete();

        if (isset($result['category_filter'])) {
			foreach ($result['category_filter'] as $filter_id) {
                DB::table($this->config['db_prefix'].'category_filter')->insert([
                    'category_id'   =>  (int)$result['category_id'],
                    'filter_id'     =>  (int)$filter_id
                ]);
			}
		}

        DB::table($this->config['db_prefix'].'category_to_store')->where('category_id',(int)$result['category_id'])->delete();

        DB::table($this->config['db_prefix'].'category_to_store')->insert([
            'category_id'   =>  (int)$result['category_id'],
            'store_id'      =>  $this->config['store_id']
        ]);



        DB::table($this->config['db_prefix'].'seo_url')->where('query','category_id='.(int)$result['category_id'])->delete();

		if (isset($result['category_seo_url']) && !empty($result['category_seo_url'])) {
            DB::table($this->config['db_prefix'].'seo_url')->insert([
                'store_id'      =>  $this->config['store_id'],
                'language_id'   =>  $this->config['language_id'],
                'query'         =>  "category_id=".(int)$result['category_id'],
                'keyword'       =>  $result['category_seo_url']
            ]);

        } else {

        DB::table($this->config['db_prefix'].'seo_url')->insert([
            'store_id'      =>  $this->config['store_id'],
            'language_id'   =>  $this->config['language_id'],
            'query'         =>  "category_id=".(int)$result['category_id'],
            'keyword'       =>  Str::slug($result['name'])
        ]);

        }

        $response_categories[] = $result['category_id'];

    }

    return response()->json(['status' => 'ok', 'data' => ['category_id' => $response_categories]], 200);


    }


    public function destroy(string $category_id)
    {

        if($category_id == null){
            return response()->json('O parametro ID da categoria é obrigatório!', 422);
        }

        DB::table($this->config['db_prefix'].'category_path')->where('category_id',(int)$category_id)->delete();

        $query = DB::table($this->config['db_prefix'].'category_path')->where('path_id',(int)$category_id)->get();

		foreach ($query as $result) {
			$this->destroy($result->category_id);
		}

        DB::table($this->config['db_prefix'].'category')->where('category_id',(int)$category_id)->delete();
        DB::table($this->config['db_prefix'].'category_description')->where('category_id',(int)$category_id)->delete();
        DB::table($this->config['db_prefix'].'category_filter')->where('category_id',(int)$category_id)->delete();
        DB::table($this->config['db_prefix'].'category_to_store')->where('category_id',(int)$category_id)->delete();
        DB::table($this->config['db_prefix'].'category_to_layout')->where('category_id',(int)$category_id)->delete();
        DB::table($this->config['db_prefix'].'product_to_category')->where('category_id',(int)$category_id)->delete();
        DB::table($this->config['db_prefix'].'seo_url')->where('query','category_id='.(int)$category_id)->delete();
        DB::table($this->config['db_prefix'].'coupon_category')->where('category_id',(int)$category_id)->delete();

        return response()->json(['status' => 'ok', 'data' => ['category_id' => $category_id]], 200);

    }


}
