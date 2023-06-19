<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Image;
use Illuminate\Support\Facades\Config;

class ManufacturerController extends Controller
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config   = $config;
        $this->config = Config('opencart');
    }


    public function index()
    {

        $data  = DB::table('oc_manufacturer')->paginate(20);

        return response()->json($data, 200);
    }


    public function store(Request $request)
    {

        $data = $request->getContent();

        $response_manufacturers = [];


        foreach(json_decode($data , true) as $result){

        if(!isset($result['name']) || $result['name'] == ''){
            return response()->json('O campo name é obrigatório!', 422);
        }

        $manufacturer_id = DB::table($this->config['db_prefix'].'manufacturer')->insertGetId([
            'name'          =>  $result['name'],
            'sort_order'    =>  isset($result['sort_order']) ? (int)$result['sort_order'] : 1,
        ]);

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

            DB::table($this->config['db_prefix'].'manufacturer')->where('manufacturer_id',$manufacturer_id)->update([
                'image'     =>  'catalog/'.$imageName
            ]);
		}


        DB::table($this->config['db_prefix'].'manufacturer_to_store')->insert([
            'manufacturer_id'   =>  (int)$manufacturer_id,
            'store_id'          =>  $this->config['store_id']
        ]);



        if (isset($result['manufacturer_seo_url']) && !empty($result['manufacturer_seo_url'])) {
                        DB::table($this->config['db_prefix'].'seo_url')->insert([
                            'store_id'      =>  $this->config['store_id'],
                            'language_id'   =>  $this->config['language_id'],
                            'query'         =>  "manufacturer_id=".(int)$manufacturer_id,
                            'keyword'       =>  $result['manufacturer_seo_url']
                        ]);

		} else {

            DB::table($this->config['db_prefix'].'seo_url')->insert([
                'store_id'      =>  $this->config['store_id'],
                'language_id'   =>  $this->config['language_id'],
                'query'         =>  "manufacturer_id=".(int)$manufacturer_id,
                'keyword'       =>  Str::slug($result['name'])
            ]);

        }

        $response_manufacturers[] = $manufacturer_id;
    }

    return response()->json(['status' => 'ok', 'data' => ['manufacturer_id' => $response_manufacturers]], 200);

    }


    public function show(string $id)
    {

        if($id == null){
            return response()->json('O parametro ID do manufacturer é obrigatório!', 422);
        }

        $manufacturer =   DB::table($this->config['db_prefix'].'oc_manufacturer')->where('manufacturer_id',$id)->first();

        if(!$manufacturer){
            return response()->json('Manufacturer não existe!', 422);
        }

        return response()->json($manufacturer, 200);

    }


    public function update(Request $request)
    {

        $data = $request->getContent();


        $response_manufacturers = [];

        foreach(json_decode($data , true) as $result){

            if(!isset($result['manufacturer_id']) || $result['manufacturer_id'] == ''){
                return response()->json('O campo manufacturer_id é obrigatório!', 422);
            }

        if(!isset($result['name']) || $result['name'] == ''){
            return response()->json('O campo name é obrigatório!', 422);
        }

        $category = DB::table($this->config['db_prefix'].'manufacturer')->where('manufacturer_id',$result['manufacturer_id'])->first();

        DB::table($this->config['db_prefix'].'manufacturer')->where('manufacturer_id',$result['manufacturer_id'])->update([
            'name'          =>  $result['name'],
            'sort_order'    =>  isset($result['sort_order'])  ? (int)$result['sort_order']  : $category->sort_order
        ]);

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

            DB::table($this->config['db_prefix'].'manufacturer')->where('manufacturer_id',$result['manufacturer_id'])->update([
                'image'     =>  'catalog/'.$imageName
            ]);
		}


        DB::table($this->config['db_prefix'].'manufacturer_to_store')->where('manufacturer_id',(int)$result['manufacturer_id'])->delete();

        DB::table($this->config['db_prefix'].'manufacturer_to_store')->insert([
            'manufacturer_id'   =>  (int)$result['manufacturer_id'],
            'store_id'          =>  $this->config['store_id']
        ]);


        DB::table($this->config['db_prefix'].'seo_url')->where('query','manufacturer_id='.(int)$result['manufacturer_id'])->delete();

		if (isset($result['manufacturer_seo_url']) && !empty($result['manufacturer_seo_url'])) {
            DB::table($this->config['db_prefix'].'seo_url')->insert([
                'store_id'      =>  $this->config['store_id'],
                'language_id'   =>  $this->config['language_id'],
                'query'         =>  "manufacturer_id=".(int)$result['manufacturer_id'],
                'keyword'       =>  $result['manufacturer_seo_url']
            ]);

        } else {

        DB::table($this->config['db_prefix'].'seo_url')->insert([
            'store_id'      =>  $this->config['store_id'],
            'language_id'   =>  $this->config['language_id'],
            'query'         =>  "manufacturer_id=".(int)$result['manufacturer_id'],
            'keyword'       =>  Str::slug($result['name'])
        ]);

        }

        $response_manufacturers[] = $result['manufacturer_id'];

    }

    return response()->json(['status' => 'ok', 'data' => ['manufacturer_id' => $response_manufacturers]], 200);


    }


    public function destroy(string $manufacturer_id)
    {

        if($manufacturer_id == null){
            return response()->json('O parametro ID do manufacturer é obrigatório!', 422);
        }

        DB::table($this->config['db_prefix'].'manufacturer')->where('manufacturer_id',(int)$manufacturer_id)->delete();
        DB::table($this->config['db_prefix'].'manufacturer_to_store')->where('manufacturer_id',(int)$manufacturer_id)->delete();
        DB::table($this->config['db_prefix'].'seo_url')->where('query','manufacturer_id='.(int)$manufacturer_id)->delete();

        return response()->json(['status' => 'ok', 'data' => ['manufacturer_id' => $manufacturer_id]], 200);

    }


}
