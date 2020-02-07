<?php

namespace App\Http\Controllers\Web;
use Yaml;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
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
        $dir = '../../fdroid/';
        $file = $request->file('file');
        $apk = new \ApkParser\Parser($file);
        $manifest = $apk->getManifest();
        if ($manifest->getPackageName() == $request->post('package')) {
            $data = array(
                'package_name' => $manifest->getPackageName(),
                'version' => $manifest->getVersionName(),
                'version_code' => $manifest->getVersionCode()
            );        

            // rename file as version code
            $temp = $_FILES["file"]["tmp_name"];
            $finalName = $manifest->getPackageName() . '-' . $manifest->getVersionName() . '.apk';
            move_uploaded_file($temp, public_path() . '/temp/'."\\{$finalName}");

            // move file to fdroid folder
            copy(public_path() . '/temp/'."\\{$finalName}", $dir . '/repo/' . $finalName);

            //Create meta data
            $changelogFile = fopen($dir . 'metadata/' . $manifest->getPackageName() . '/en-US/changelogs/' . $manifest->getVersionCode() . ".txt", "w");
            $txt = "*";
            fwrite($changelogFile, $txt);
            // Modify yml file
            $fileYml = $dir . 'metadata/' . $manifest->getPackageName() . '.yml';
            $yamlContents = Yaml::parse(file_get_contents($fileYml));
            $array = [
                'AuthorName' => $yamlContents['AuthorName'],
                'Categories' => $yamlContents['Categories'],
                'CurrentVersionCode' => $manifest->getVersionCode(),
                'IssueTracker' => $yamlContents['IssueTracker'],
                'Name' => $yamlContents['Name'],
                'SourceCode' => $yamlContents['SourceCode'],
                'Summary' => $yamlContents['Summary'],
                'WebSite' => $yamlContents['WebSite'],
            ];
            
            $yaml = Yaml::dump($array);
            file_put_contents($dir . 'metadata/' . $manifest->getPackageName() .'.yml', $yaml);
            // running command line
            exec('cd /var/www/html/fdroid && fdroid update --create-metadata');
            sleep(20);
            exec('cd /var/www/html/fdroid && fdroid server update');
            $data = array(
                'error' => false,
                'message' => 'Success'
            );
            return response()->json($data);
        } else {
            $data = array(
                'error' => true,
                'message' => 'Package name not match'
            );
            return response()->json($data);
        }
    }
}
