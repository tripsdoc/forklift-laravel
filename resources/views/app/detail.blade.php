@extends('adminlte::page')
@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/min/dropzone.min.css">
@stop
@section('content_header')
<h1>APP List</h1>
@stop
@section('content')
<div class="row">
   <div class="col-12">
      <div class="card">
         @if(Session::has('message'))
         <div class="card-header">
            <p class="alert {{ Session::get('alert-class', 'alert-info') }}">{{ Session::get('message') }}</p>
         </div>
         @endif     
      </div>
      <section class="col-lg-12">
         <div class="card">
            <div class="card-header">
               <h3 class="card-title">
                  <i class="fab fa-android"></i>
                  {{$app_detail[0]['name']}}
               </h3>
               <div class="card-tools">
                  <ul class="nav nav-pills ml-auto">
                     <li class="nav-item">
                        <a class="nav-link " href="#revenue-chart" data-toggle="tab">Info</a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link" href="#sales-chart" data-toggle="tab">Release Management</a>
                     </li>
                     <li class="nav-item">
                        <a class="nav-link active" href="#app-update" data-toggle="tab">Update App</a>
                     </li>
                  </ul>
               </div>
            </div>
            <div class="card-body">
               <div class="tab-content p-0">
                  <div class="chart tab-pane " id="revenue-chart">
                  </div>
                  <div class="chart tab-pane active" id="app-update">
                  <form method="post" action="{{url('app/upload')}}" enctype="multipart/form-data" 
                  class="dropzone" id="dropzone">@csrf
                  </div>
               </div>
            </div>
         </div>
      </section>
   </div>
</div>
@stop
@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/dropzone.js"></script>
    <script type="text/javascript">
        Dropzone.options.dropzone =
         {
            dictDefaultMessage: "Drop APK here, or select a file",
            maxFilesize: 12,
            renameFile: function(file) {
                var dt = new Date();
                var time = dt.getTime();
               return time+file.name;
            },
            acceptedFiles: ".apk",
            addRemoveLinks: true,
            timeout: 5000,
            success: function(file, response) 
            {
                console.log(response);
            },
            error: function(file, response)
            {
               return false;
            }
};
</script>
@stop