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
                ],
                'category_description'  =>  [
                    'category_id'           => $result->cd_category_id,
                    'language_id'           => $result->cd_language_id,
                    'name'                  => $result->cd_name,
                    'description'           => $result->cd_description,
                    'meta_title'            => $result->cd_meta_title,
                    'meta_description'      => $result->cd_meta_description,
                    'meta_keyword'          => $result->cd_meta_keyword,
                ],
                'category_filter'       =>  [
                    'category_id'           => $result->cf_category_id,
                    'filter_id'             => $result->cf_filter_id,
                ],
                'oc_category_path'      =>  [
                    'category_id'           => $result->cp_category_id,
                    'path_id'               => $result->cp_path_id,
                    'level'                 => $result->cp_level,
                ],
                'category_to_layout'    =>  [
                    'category_id'           => $result->cl_category_id,
                    'store_id'              => $result->cl_store_id,
                    'layout_id'             => $result->cl_layout_id,
                ],
                'category_to_store'  =>  [
                    'category_id'           => $result->cs_category_id,
                    'store_id'              => $result->cs_store_id,
                ]

            ];

        }

        return response()->json($data, 200);
    }


    public function store(Request $request)
    {
        $data = $request->all();

        //Insert data table oc_category
        $category_id = DB::table($this->config['db_prefix'].'category')->insertGetId([
            'parent_id'     =>  isset($data['parent_id']) ? (int)$data['parent_id'] : 0,
            'top'           =>  isset($data['top']) ? (int)$data['top'] : 1,
            'column'        =>  isset($data['column']) ? (int)$data['column'] : 1,
            'sort_order'    =>  isset($data['sort_order']) ? (int)$data['sort_order'] : 1,
            'status'        =>  (int)$data['status'],
            'date_modified' =>  NOW(),
            'date_added'    =>  NOW()

        ]);

        //update image table category
        if (isset($data['image'])) {
            DB::table($this->config['db_prefix'].'category')->where('category_id',$category_id)->update([
                'image'     =>  $data['image']

            ]);
		}

        //Insert data table oc_category_description
            DB::table($this->config['db_prefix'].'category_description')->insert([
                'category_id'       =>  (int)$category_id,
                'language_id'       =>  $this->config['language_id'],
                'name'              =>  $data['name'],
                'description'       =>  isset($data['description']) ? $data['description'] : $data['name'],
                'meta_title'        =>  isset($data['meta_title']) ? $data['meta_title'] : $data['name'],
                'meta_description'  =>  isset($data['meta_description']) ? $data['meta_description'] : $data['name'],
                'meta_keyword'      =>  isset($data['meta_keyword']) ? $data['meta_keyword'] : str_replace(' ',',',$data['name'])
            ]);

        $level = 0;

        $query = DB::table($this->config['db_prefix'].'category_path')->where('category_id',isset($data['parent_id']) ? (int)$data['parent_id'] : 0)->orderby('level','ASC')->get();

		foreach ($query as $result) {
            DB::table($this->config['db_prefix'].'category_path')->insert([
                'category_id'   =>  (int)$category_id,
                'path_id'       =>  (int)$result->path_id,
                'level'         =>  (int)$level
            ]);

			$level++;
		}

        DB::table($this->config['db_prefix'].'category_path')->insert([
            'category_id' =>  (int)$category_id,
            'path_id'     =>  (int)$category_id,
            'level'       =>  (int)$level
        ]);


        if (isset($data['category_filter'])) {
			foreach ($data['category_filter'] as $filter_id) {
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



        if (isset($data['category_seo_url']) && !empty($data['category_seo_url'])) {
                        DB::table($this->config['db_prefix'].'seo_url')->insert([
                            'store_id'      =>  $this->config['store_id'],
                            'language_id'   =>  $this->config['language_id'],
                            'query'         =>  "category_id=".(int)$category_id,
                            'keyword'       =>  $seo_url['keyword']
                        ]);

		} else {

            DB::table($this->config['db_prefix'].'seo_url')->insert([
                'store_id'      =>  $this->config['store_id'],
                'language_id'   =>  $this->config['language_id'],
                'query'         =>  "category_id=".(int)$category_id,
                'keyword'       =>  Str::slug($data['name'])
            ]);

        }

        return response()->json(['status' => 'ok', 'data' => ['category_id' => $category_id]], 200);
    }


    public function show(string $id)
    {

        $oc_category                =   DB::table('oc_category')->where('category_id',$id)->first();
        $oc_category_descriptions   =   DB::table('oc_category_description')->where('category_id',$oc_category->category_id)->get();
        $oc_category_filters        =   DB::table('oc_category_filter')->where('category_id',$oc_category->category_id)->get();
        $oc_category_paths          =   DB::table('oc_category_path')->where('category_id',$oc_category->category_id)->get();
        $oc_category_to_layouts     =   DB::table('oc_category_to_layout')->where('category_id',$oc_category->category_id)->get();
        $oc_category_to_stores      =   DB::table('oc_category_to_store')->where('category_id',$oc_category->category_id)->get();

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


    public function update(Request $request, string $id)
    {
        //
    }


    public function destroy(string $id)
    {
        //
    }
}
