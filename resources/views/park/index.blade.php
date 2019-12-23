@extends('adminlte::page')

@section('css')
  <link href="{{ asset('assets/pagination.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/css/tempusdominus-bootstrap-4.min.css" />
@stop

@section('content_header')
    <h1>Park List</h1>
@stop
@section('content')
<div class="row">
  <!-- @if($data->isEmpty())
    <h4>Currently there is no Park Data. You can create the new park from 'Create' Function on menu!</h4>
  @else
  @foreach($data as $datas)
  <div class="col-lg-6">
  <div class="card">
    <div class="card-header card-header-primary">
      <div class="d-flex justify-content-between">
        <input type="hidden" value="{{ $datas->id }}"/>
        <h4 class="card-title ">{{$datas->name}}-{{$datas->place}}</h4>
        <div class="card-tools">
          <a href="../park/{{ $datas->id }}" class="btn btn-tool" data-toggle="tooltip" title="Detail"><i class="fas fa-external-link-alt"></i></button></a>
          <a href="#" class="btn btn-tool" data-toggle="tooltip" title="Remove"><i class="fas fa-times"></i></button></a>
        </div>
      </div>
      <div class="card-body">
      <table id="{{ $datas->id }}" class="table table-hover park-table" style="width:100%">
        <thead>
          <tr>
            <th> Name</th>
            <th> Detail</th>
            <th> Action  </th>
          </tr>
        </thead>
      </table>
      </div>
    </div>
  </div>
  </div>
  @endforeach
  @endif -->
  
  <div class="col-12">
    <div class="card">
      @if(Session::has('message'))
      <div class="card-header">
        <p class="alert {{ Session::get('alert-class', 'alert-info') }}">{{ Session::get('message') }}</p>
      </div>
      @endif     
      <div class="card-body">
      <table class="table table-hover park-table" style="width:100%">
        <thead>
          <tr>
            <th> Name</th>
            <th> Place</th>
            <th> Type</th>
            <th> Action  </th>
          </tr>
        </thead>
      </table>
      </div>
    </div>
  </div>
</div>
@stop

@section('js')
<script src="{{ asset('vendor/moment/moment.js') }}"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/js/tempusdominus-bootstrap-4.min.js"></script>
<script type="text/javascript" src="{{ asset('datatables/dataTables.bootstrap4.js') }}"></script>
<script type="text/javascript">
$(function () {
  /* $('.park-table').each(function(i, obj){
    var id = $(this).attr('id');
    var routeurl = "{{ route('TemporaryDataByPark', ":id") }}";
    routeurl = routeurl.replace(':id', id);
    console.log(routeurl);
    $(this).DataTable({
      responsive: true,
      processing: true,
      ajax: routeurl,
      columns: [
          { data: 'clientId', name: 'Client ID' },
          { data: 'containerNumber', name: 'Container Number' },
          { data: 'action', name: 'action', orderable: false, searchable: false },
      ]
    });
  }); */
  $('.park-table').each(function(i, obj){
    var id = $(this).attr('id');
    var routeurl = "{{ route('DataPark', ":id") }}";
    routeurl = routeurl.replace(':id', id);
    console.log(routeurl);
    $(this).DataTable({
      responsive: true,
      processing: true,
      ajax: routeurl,
      columns: [
          { data: 'name', name: 'Name' },
          { data: 'place', name: 'Place' },
          { data: 'type', name: 'Type' },
          { data: 'action', name: 'action', orderable: false, searchable: false },
      ]
    });
  });
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