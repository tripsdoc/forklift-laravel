@extends('adminlte::page')

@section('css')
  <link href="{{ asset('assets/pagination.css') }}" rel="stylesheet">
@stop

@section('content_header')
    <h1>Park History</h1>
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
      <table class="table table-hover history-table" style="width:100%">
        <thead>
          <tr>
            <th> Client</th>
            <th> Container Number</th>
            <th> Park On</th>
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
<script type="text/javascript" src="{{ asset('datatables/dataTables.bootstrap4.js') }}"></script>
<script type="text/javascript">
$(function () {
    var table = $('.history-table').DataTable({
        responsive: true,
        processing: true,
        ajax: "{{ route('DataHistory') }}",
        columns: [
            { data: 'clientId', name: 'Client' },
            { data: 'containerNumber', name: 'Container Number' },
            { data: 'parkIn', name: 'Park On' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
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