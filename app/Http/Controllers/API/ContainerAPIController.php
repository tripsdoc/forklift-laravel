<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ContainerView;

class ContainerAPIController extends Controller
{
    function getAll() {
        $data = ContainerView::paginate(20);
        $response['status'] = (!$data->isEmpty());
        $response['data'] = $data;
        return response($response);
    }
}
