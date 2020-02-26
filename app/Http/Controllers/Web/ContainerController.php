<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use App\ContainerInfo;
use DataTables;
use Validator;
use Redirect;
use Session;
use View;
use DB;

class ContainerController extends Controller
{
    function index() {
        return view('container/index');
    }

    function debug() {
        $data = DB::table('job_items')->get();
        return response($data);
    }

    function jsonAll(Request $request) {
        $columns = array( 
            0 =>'ContainerPrefix', 
            1 =>'ContainerNumber',
            2 =>'ContainerSize',
            3 =>'JobNumber',
            4 =>'Dummy'
        );

        $totalData = ContainerInfo::count();

        $totalFiltered = $totalData; 

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        if(empty($request->input('search.value')))
        {            
        $posts = ContainerInfo::offset($start)
                ->limit($limit)
                ->orderBy($order,$dir)
                ->get();
        }
        else {
        $search = $request->input('search.value'); 

        $posts =  ContainerInfo::where('Dummy','LIKE',"%{$search}%")
                    ->orWhere('ContainerNumber', 'LIKE',"%{$search}%")
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order,$dir)
                    ->get();

        $totalFiltered = ContainerInfo::where('Dummy','LIKE',"%{$search}%")
                    ->orWhere('ContainerNumber', 'LIKE',"%{$search}%")
                    ->count();
        }

        $data = array();
        if(!empty($posts))
        {
            foreach ($posts as $post)
            {
                $nestedData['ContainerPrefix'] = $post->ContainerPrefix;
                $nestedData['ContainerNumber'] = $post->ContainerNumber;
                $nestedData['ContainerSize'] = $post->ContainerSize;
                $nestedData['JobNumber'] = $post->JobNumber;
                $nestedData['Action'] = "<a href='container/$post->Dummy' class='edit btn btn-primary btn-sm'>View</a>
                                         <form id='form-delete-$post->Dummy' style='display: inline-block;' class='pull-left' action='../container/$post->Dummy' method='POST'>
                                         ".csrf_field()."
                                            <input type='hidden' name='_method' value='DELETE'>
                                            <button class='jquery-postback btn btn-danger btn-sm'>Delete</button>
                                         </form>";
                $data[] = $nestedData;
            }
        }

        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
            );

        echo json_encode($json_data); 
    }

    function create() {

    }

    function store() {
        
    }

    function show($id) {
        $data = ContainerInfo::find($id);
        return View::make('container/show')->with('data', $data);
    }

    function edit() {

    }

    function update() {

    }

    function destroy($id) {
        $data = ContainerInfo::find($id);
        return response($data);
    }
}
