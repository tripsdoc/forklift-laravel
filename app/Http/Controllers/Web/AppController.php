<?php

namespace App\Http\Controllers\Web;
use Yaml;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AppController extends Controller
{
    public function index()
    {
        $app_meta = array();

        $dir = '../../fdroid/metadata/';

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    $name = basename($file, '.' . $ext );
                    if ($ext == 'yml') {
                        $yamlContents = Yaml::parse(file_get_contents($dir . $file));
                        $appMeta = array(
                            'name' => $yamlContents['Name'],
                            'package_name' => $name
                        );
                        array_push($app_meta, $appMeta);
                    }
                }
                closedir($dh);
            }
        }

        $data = array(
            'app_list' => $app_meta
        );
        return view('app/index', $data);
    }
    public function detail($app)
    {
        $app_detail = array();

        $dir = '../../fdroid/metadata/';

        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    $name = basename($file, '.' . $ext );
                    if ($ext == 'yml' && $app == $name) {
                        $yamlContents = Yaml::parse(file_get_contents($dir . $file));
                        $appMeta = array(
                            'name' => $yamlContents['Name'],
                            'package_name' => $name
                        );
                        array_push($app_detail, $appMeta);
                    }
                }
                closedir($dh);
            }
        }
        $data = array(
            'app_detail' => $app_detail
        );
        return view('app/detail', $data);
    }
    public function upload(Request $request)
    {
        $file = $request->file('file');
        $apk = new \ApkParser\Parser($file);
        $manifest = $apk->getManifest();
        $data = array(
            'package_name' => $manifest->getPackageName(),
            'version' => $manifest->getVersionName(),
            'version_code' => $manifest->getVersionCode()
        );
        dd($data);
        // $imageName = $image->getClientOriginalName();
        // $image->move(public_path('images'),$imageName);
        
        // $imageUpload = new ImageUpload();
        // $imageUpload->filename = $imageName;
        // $imageUpload->save();
        // return response()->json(['success'=>$imageName]);
    }
}
