@extends('adminlte::page')
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('css')
  <link href="{{ asset('assets/pagination.css') }}" rel="stylesheet">
@stop

@section('content_header')
    <h1>IPS User</h1>
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
      <table class="table table-hover ips-table" style="width:100%">
        <thead>
          <tr>
            <th> UserId</th>
            <th> UserName</th>
            <th> Password  </th>
            <th > Action  </th>
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
  var table = $('.ips-table').DataTable({
    responsive: true,
    processing: true,
    ajax: "{{ route('DataIPS') }}",
    columns: [
      { data: 'UserId', name: 'UserId' },
      { data: 'UserName', name: 'UserName' },
      { data: 'Password', name: 'Password' },
      { data: 'action', name: 'action', orderable: false, searchable: false },
    ]
  });
});
$.ajaxSetup({
  headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
});
$(document).on('click', '.jquery-postback', function(e) {
  var form = document.getElementById("form-delete");
  e.preventDefault(); // does not go through with the link.
  var confirmation = confirm("Are you sure want to delete?");
  if(confirmation) {
    form.submit();
  }
});
</script>
@stop