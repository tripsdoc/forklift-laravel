@extends('adminlte::page')
@section('content')
<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-primary">
            <h4 class="card-title ">Edit Ongoing Park</h4>
            <p class="card-category"></p>
          </div>
          <div class="card-body">
          {{ Html::ul($errors->all()) }}

          {{ Form::model($data, array('route' => array('temporary.update', $data->id), 'method' => 'PUT')) }}
              <div class="form-group">
                  {{ Form::label('parkid', 'Park') }}
                  {!! Form::select('parkid',  $data->park, $data->temporary->parkId, ['class' => 'form-control']) !!}
              </div>
              <div class="form-group">
                  {{ Form::label('number', 'Container Number') }}
                  {{ Form::text('number', $data->number, array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('client', 'Client') }}
                  {{ Form::text('client', $data->client, array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('size', 'Size') }}
                  {{ Form::text('size', $data->size, array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('parkin', 'ParkIn') }}
                  <div class="row">
                    <div class="col">
                        {{ Form::date('parkin', $data->parkIn, array('class' => 'form-control form-control-inline', 'name' => 'parkin', 'min' => \Carbon::now()->toDateString())) }}
                    </div>
                    <div class="col">
                        {{ Form::time('timein', $data->timeIn, array('class' => 'form-control form-control-inline', 'id' => 'timein')) }}
                    </div>
                  </div>
              </div>

              <div class="form-group">
                  {{ Form::label('parkout', 'ParkOut') }}
                  <div class="row">
                    <div class="col">
                        {{ Form::date('parkout', $data->parkOut, array('class' => 'form-control form-control-inline', 'name' => 'parkout', 'min' => $data->parkIn)) }}
                    </div>
                    <div class="col">
                        {{ Form::time('timeout', $data->timeOut, array('class' => 'form-control form-control-inline', 'id' => 'timeout')) }}
                    </div>
                  </div>
              </div>

              {{ Form::submit('Update', array('class' => 'btn btn-primary')) }}

          {{ Form::close() }}
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
