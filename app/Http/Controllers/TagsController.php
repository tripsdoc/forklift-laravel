<?php

namespace App\Http\Controllers;

use Storage;
use DB;
use Response;
use Illuminate\Http\Request;

class TagsController extends Controller
{
    private $datatags = array(

        '291e744f3695', '3759d0ba05a6', '3dd459dac965', '528534894fce', '549b892f464e', '574bf60c569c', '66bb4b5c20e4',

        'c30050f8caf4','1607a64028f4','e158dd62d07a', '1678b17d9d98', '937c7dd514ee',

        'a3dc7216517e', '1fb336551a8e', '9d18e19e4a93', 'c0b845f9d74a', 'de77d368f11b',
        //get all tags with POD
        'd10d9b1b0b23', '25f79f873a62', '04f68506e3ac',
        //Get all tags export
        '200eaf299dcb', 'e144cbc223dc',

        '220aaf299dcs'
    );
    private $datapods = array(
        'BANGKOK', 
        'HAMBURG', 
        'HAMBURG', 
        'HAMBURG', 
        'HAMBURG', 
        'MELBOURNE', 
        'BRISBANE'
    );
    private $dataclients = array(
        'PANALPINA', 
        'PANALPINA', 
        'PANALPINA', 
        'PANALPINA', 
        'PANALPINA', 
        'PANALPINA', 
        'PANALPINA'
    );

    private $smoothedposition = array(

        [48.5, 40.5, 31.5],
        [48.5, 20.5, 31.5],
        [28.5, 9.5, 31.5],
        [38.5, 100.5, 31.5],
        [8.5, 50.5, 31.5],
        [8.5, 110.5, 31.5],
        [38.5, 120.5, 31.5],

        [18.5, 70.5, 31.5],
        [0.5, 0.5, 31.5],
        [28.5, 39.5, 31.5],
        [36.9, 65.5, 31.5],
        [2, 19.5, 31.5],

        [7.5, 7.5, 31.5],
        [14.5, 6.5, 31.5],
        [24.5, 1.5, 31.5],
        [6.5, 12.5, 31.5],
        [21, 5.5, 31.5], 
        //get all tags with POD
        [14.5, 62.5, 31.5],
        [6.5, 52.5, 31.5],
        [51, 45.5, 31.5],
        //get all tags export
        [18, 75.5, 31.5],
        [28, 35.5, 31.5],

        [48, 108.5, 31.5]
    );

    function getDeviceTag() {
        $serial = $_GET['serial'];
        $result = DB::table('DeviceInfo')
        ->where('SerialNumber', '=', $serial)->get();
        $response['status'] = (count($result) > 0)? TRUE : FALSE;
        $response['tags'] = $result;
        return response($response);
    }

    function getTagPosition() {
        $i = 0;
        $url = $url = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $response['code'] = 0;
        $response['command'] = $url;
        $response['message'] = "Location";
        $response['responseTS'] = "141231532";
        $response['status'] = "Ok";

        $datarray = array();
        $setdata = $_GET['tag'];
        $reqtagdata = array_map('trim', explode(",", $setdata));

        foreach($reqtagdata as $data) {
            $key = array_search($data, $this->datatags);
            $dataf['no'] = $i;
            $dataf['areaId'] = "TrackingArea1";
            $dataf['areaName'] = "KCM";
            $dataf['color'] = "#000CC";
            $dataf['coordinateSystemId'] = "CoordinateSystem1";
            $dataf['coordinateSystemName'] = "FirstFloor";
            $dataf['covarianceMatrix'] = [8.9, -3.09, -3.09, 5.56];
            $dataf['id'] = $data;
            $dataf['name'] = "Basket";
            $dataf['position'] = $this->smoothedposition[$key];
            $dataf['positionAccuracy'] = 1.55;
            $dataf['positionTS'] = 123142323;
            $dataf['smoothedPosition'] = $this->smoothedposition[$key];
            $zones['id'] = "Zone005";
            $zones['name'] = "Cashier";
            $dataf['zones'] = [$zones];
            array_push($datarray, $dataf);
            $i++;
        }
        
        $response['debug'] = $reqtagdata;
        Storage::put('count.txt', count($reqtagdata));
        Storage::put('file.txt', $url);
        Storage::put('tag.txt', $_GET['tag']);
        $response['tags'] = $datarray;
        $response['version'] = "test";
        return response($response);
    }
}
