@extends('adminlte::page')
@section('css')
<link href="{{ asset('assets/pagination.css') }}" rel="stylesheet">
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
         <div class="card-body">
            <table class="table table-hover app-table" style="width:100%">
               <thead>
                  <tr>
                     <th> App Name</th>
                     <th> Version</th>
                     <th> Active Install</th>
                     <th> Last Update</th>
                     <th> Status</th>
                     <th> Action</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach($app_list as $app)   
                  
                    <tr role="row" class="odd">
                        <td>{{$app['name']}}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>PUBLISHED</td>
                        <td>
                            <a href="{{ url('app') }}/detail/{{$app['package_name']}}">
                                <button type="button" class="btn btn-block btn-primary">Detail</button>
                            </a>
                        </td>
                    </tr>
                  @endforeach    
               </tbody>
            </table>
         </div>
      </div>
   </div>
</div>
@stop
@section('js')
<script type="text/javascript" src="{{ asset('datatables/dataTables.bootstrap4.js') }}"></script>
<script type="text/javascript">
   $(function () {
       var table = $('.app-table').DataTable();
   });
   $(document).on('click', '.jquery-postback', function(e) {
     var form = e.target.parentNode;
     e.preventDefault(); // does not go through with the link.
     var confirmation = confirm("Are you sure want to delete?");
     if(confirmation) {
       form.submit();
     }
   });
</script>
@stop