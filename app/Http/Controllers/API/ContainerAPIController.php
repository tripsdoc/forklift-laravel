<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ContainerView;
use App\ShifterUser;
use App\TemporaryPark;
use App\History;
use DB;

class ContainerAPIController extends Controller
{
    function debug() {
        $data = ContainerView::all();
        return response($data);
    }

    // -----------------------------------------  Park List Function -----------------------------------------------------------
    function getAll() {
        $data = ContainerView::paginate(20);

        $response['status'] = !$data->isEmpty();
        $response['current'] = $data->currentPage();
        $response['last'] = $data->lastPage();
        $response['data'] = $data->items();
        return response($response);
    }

    function getOverview($id) {
        $data = ContainerView::where('Dummy', '=', $id)->first();

        $response['status'] = (!empty($data));
        $response['data'] = [$data];
        return response($response);
    }

    function getContainerSearch(Request $request) {
        $search = $request->search;
        $data = ContainerView::where('Client','LIKE',"%{$search}%")
        ->orWhere('Number', 'LIKE',"%{$search}%")
        ->orWhere('Prefix', 'LIKE',"%{$search}%")
        ->orWhere('VesselName', 'LIKE',"%{$search}%")
        ->paginate(20);

        $response['status'] = !$data->isEmpty();
        $response['current'] = $data->currentPage();
        $response['last'] = $data->lastPage();
        $response['data'] = $data->items();
        return response($response);
    }

    // --------------------------------------------------------------------------------------------------------------------------

    //Call function to request parking lots
    //or function to retrieve where container at
    function informShifter(Request $request) {
        $data = ContainerView::where('Dummy', '=', $request->id)->first();
        $shifter = ShifterUser::where('warehouse', '=', $data->DeliverTo)->get();
        $ongoing = TemporaryPark::where('ShifterId', '=', $shifter->id)->get();

        //If shifter is still do the job ?
        
        //If shifter available
        //When inform show dialog to choice where to park in warehouse
        if($ongoing->isEmpty()) {
            $temp = new TemporaryPark();
            $temp->ShifterId = $shifter->id;
            $temp->CntrId = $data->Dummy;
            $temp->requestIn = date('Y-m-d H:i:s');
            $temp->createdBy = $request->user;
            $temp->status = $request->status; //1: Inform Shifter, 2: Request Position, 3: Return to Shifter, 4: Return to Driver

            $temp->save();

            //Call socket io events Container come to KD

        } else {
            
        }

        //Set container ETA (Estimated Time of Arrival)

    }

    //Function to send parking lots location to driver
    function shifterSetPark(Request $request) {
        $data = ContainerView::where('Dummy', '=', $request->CntrId)->first();

        $temp = TemporaryPark::find($request->tempId);

        //inform driver to park on selected parking lots
        $temp->parkId = $request->parkId;
        $temp->updatedBy = $request->user;
        $temp->status = $request->status; //1: Inform Shifter, 2: Request Position, 3: Return to Shifter, 4: Return to Driver

        $temp->save();
        //Call socket io events return parking lots data

    }

    function finishContainer(Request $request) {
        $temp = TemporaryPark::find($request->tempId);

        $history = new History();
        $driver = "Shifter : " . $request->shifter . ", Driver : " . $request->driver;
        $park = "Parking Lots : " . $request->lots . ", Warehouse : " . $request->warehouse;
        $history->Driver = $driver;
        $history->Park = $park;
        $history->CntrId = $temp->CntrId;
        $history->requestIn = $temp->requestIn;
        $history->finishTime = date('Y-m-d H:i:s'); //When container is in warehouse or went outside of KD

        $history->save();
    }
}
