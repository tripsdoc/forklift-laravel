@extends('adminlte::page')

@section('css')
  <link href="{{ asset('assets/pagination.css') }}" rel="stylesheet">
  <link type="text/css" href="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/css/dataTables.checkboxes.css" rel="stylesheet" />
@stop

@section('content_header')
    <h1>Ongoing Park List</h1>
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
      <table class="table table-hover temporary-table" style="width:100%">
        <thead>
          <tr>
            <th></th>
            <th> Park Name</th>
            <th> Client</th>
            <th> Container Number</th>
            <th> Park In</th>
            <th> Park Out</th>
            <th> Action  </th>
          </tr>
        </thead>
      </table>
      <!-- <a href="JavaScript:void(0);" id="deleteall" class="edit btn btn-primary">Delete All</a> -->
      </div>
    </div>
  </div>
</div>
@stop

@section('js')
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
<script type="text/javascript" src="{{ asset('datatables/dataTables.bootstrap4.js') }}"></script>
<script type="text/javascript">
$(function () {
    var table = $('.temporary-table').DataTable({
        responsive: true,
        processing: true,
        ajax: "{{ route('TemporaryDataPark') }}",
        columns: [
            { data: 'id'},
            { data: 'parkId', name: 'Park Name' },
            { data: 'clientId', name: 'Client' },
            { data: 'containerNumber', name: 'Container Number' },
            { data: 'parkIn', name: 'Park In' },
            { data: 'parkOut', name: 'Park Out' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order : [[ 1, "asc"]],
        columnDefs: [ {
            targets:   0,
            'checkboxes': {
               'selectRow': true
            }
        } ],
        select: {
            style: 'multi',
        }
    });

    document.getElementById("deleteall").addEventListener("click", myFunction);

    function myFunction() {
      var rows_selected = table.column(0).checkboxes.selected();

      var confirmation = confirm("Are you sure want to delete?");
      if(confirmation) {
        $.each(rows_selected, function(index, rowId){
         // Create a hidden element 
         var data = "../temporary/"
         var value = data.concat(rowId);
          window.location.href=value;
        });
      }
    }
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