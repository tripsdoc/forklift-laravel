@extends('adminlte::page')
@section('content')
<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-primary">
            <div class="d-flex justify-content-between">
                <h1 class="card-title ">{{ $data->number }}</h1> <br/>
                <h4 class="card-title ">From : {{ $data->parkin }} to : {{ $data->parkout }}</h4>
            </div>
          </div>
          <div class="card-body">
              <div class="form-group">
                  {{ Form::label('parkid', 'Park') }}
                  {{ Form::text('parkid', $data->name, array('class' => 'form-control', 'disabled' => 'disabled')) }}
              </div>
              <div class="form-group">
                  {{ Form::label('number', 'Container Number') }}
                  {{ Form::text('number', $data->number, array('class' => 'form-control', 'disabled' => 'disabled')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('client', 'Client') }}
                  {{ Form::text('client', $data->client, array('class' => 'form-control', 'disabled' => 'disabled')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('size', 'Size') }}
                  {{ Form::text('size', $data->size, array('class' => 'form-control', 'disabled' => 'disabled')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('status', 'Note') }}
                  {{ Form::textarea('status', $data->status, array('class' => 'form-control', 'disabled' => 'disabled')) }}
              </div>

              <a href="../temporary/' . $row->id . '/edit" class="edit btn btn-primary btn-sm">Edit</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
@section('js')
<script type="text/javascript">
document.getElementById("parkin").onchange = function () {
    var input = document.getElementById("parkout");
    input.setAttribute("min", this.value);
    if (input.value < this.value) {
        input.value = this.value
    }
    checkDateTime()
}
document.getElementById("parkout").onchange = function () {
    checkDateTime()
}
document.getElementById("timein").onchange = function () {
    checkDateTime()
}
document.getElementById("timeout").onchange = function () {
    checkDateTime()
}

function checkDateTime() {
    var datein = document.getElementById('parkin').value;
    var dateout = document.getElementById('parkout').value;
    if (datein == dateout) {
        var timein = document.getElementById('timein');
        var timeout = document.getElementById('timeout');
        timeout.min = timein.value
        if (timeout.value < timein.value) {
            timeout.value = timein.value
        }
    }
}
</script>
@stop
