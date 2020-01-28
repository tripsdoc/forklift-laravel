<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ContainerInfo;
use App\ContainerView;
use App\History;
use App\Park;
use App\TemporaryPark;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Storage;

class CacheController extends Controller
{
    function retrieveFile(Request $request) {
        //Assign File
        /* if($request->hasFile('assign')) {
            $file = $request->file('assign');
            if($file->isValid()) {
                $path = public_path() . '/uploads/images/store/';
                $file->move($path, $file->getClientOriginalName());
            }
        }
        //Remove File
        if($request->hasFile('remove')) {
            $file = $request->file('remove');
            if($file->isValid()) {
                $path = public_path() . '/uploads/images/store/';
                $file->move($path, $file->getClientOriginalName());
            }
        } */

        //Assign Text
        if($request->assign != "" && $request->assign != "[]") {
            $assignObject = json_decode($request->assign);
            foreach($assignObject as $key => $dataAssign) {
                $returnText = $this->assignContainerToPark($dataAssign->dummy, $dataAssign->park, $dataAssign->username);
                Storage::append('assign.log', $returnText);
            }
        }
        //Remove Text
        if($request->remove != "" && $request->remove != "[]") {
            $removeObject = json_decode($request->remove);
            foreach($removeObject as $key => $dataRemove) {
                $returnText = $this->removeContainer($dataRemove->park, $dataRemove->username);
                Storage::append('remove.log', $returnText);
            }
        }
        
        $response['status'] = TRUE;
        $response['message'] = "Complete Process";
        return response($response);
    }

    function assignContainerToPark($dummy, $park, $user) {
        $textToReturn = date('Y-m-d H:i:s') . ", dummy: " . $dummy . ", park: " . $park . ", user: " . $user;
        date_default_timezone_set('Asia/Singapore');
        $check = TemporaryPark::where('ParkingLot', '=', $park)->first();
        if(empty($check)) {
            $temp = new TemporaryPark();

            $temp->ParkingLot = $park;
            $temp->Dummy = $dummy;
            $temp->createdBy = $user;
            $temp->updatedDt = date('Y-m-d H:i:s');
            if($temp->save()) {
                $response['status'] = TRUE;
                $response['data'] = $temp;
                $dataRedis = "1," . $park . "," .  $temp->Dummy;
                $textToReturn = $textToReturn . " Success";
                $this->broadcastRedis($dataRedis);
            }
        } else {
            if($check->Dummy != $dummy) {
                $history = new History();
                $history->SetDt = $check->updatedDt;
                $history->UnSetDt = date('Y-m-d H:i:s');
                $history->ParkingLot = $check->ParkingLot;
                $history->Dummy = $check->Dummy;
                $history->createdBy = $user;
    
                if($history->save()){
                    $check->Dummy = $dummy;
                    $check->updatedBy = $user;
                    $check->updatedDt = date('Y-m-d H:i:s');
    
                    $check->save();
                    $response['status'] = TRUE;
                    $response['data'] = $check;
                    $dataRedis = "1," . $park . "," .  $check->Dummy;
                    $textToReturn = $textToReturn . " Success";
                    $this->broadcastRedis($dataRedis);
                }
            } else {
                $textToReturn = $textToReturn . " Duplicate";
            }
        }
        return $textToReturn;
    }

    function removeContainer($park, $user) {
        $textToReturn = date('Y-m-d H:i:s') . ", park: " . $park . ", user: " . $user;
        date_default_timezone_set('Asia/Singapore');
        $check = TemporaryPark::where('ParkingLot', '=', $park)->first();
        if(!empty($check)) {
            $history = new History();
            $history->SetDt = $check->updatedDt;
            $history->UnSetDt = date('Y-m-d H:i:s');
            $history->ParkingLot = $check->ParkingLot;
            $history->Dummy = $check->Dummy;
            $history->createdBy = $user;
    
            if($history->save()){
                $check->delete();
                $response['status'] = TRUE;
                $response['data'] = $history;
                $dataRedis = "0," . $park . ",0";
                $textToReturn = $textToReturn . " Success";
                $this->broadcastRedis($dataRedis);
            }
        } else {
            $textToReturn = $textToReturn . " Empty/Already Deleted";
        }
        
        return $textToReturn;
    }

    function broadcastRedis($data) {
        $redis = Redis::connection();
        $redis->publish("update-park", $data);
    }
}
