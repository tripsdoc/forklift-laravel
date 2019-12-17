@extends('adminlte::page')

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
        {{ Html::ul($errors->all()) }}


            <div class="form-group">
                {{ Form::label('devicename', 'DeviceName') }}
                {{ Form::text('devicename', $data->DeviceName, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('serialnumber', 'Serial Number') }}
                {{ Form::text('serialnumber', $data->SerialNumber, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('warehouses', 'WareHouses') }}
                {{ Form::text('warehouses', $data->WareHouses, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('isactive', 'IsActive') }}
                @if($data->IsActive == 1)
                {{ Form::text('warehouses', "Yes", array('disabled' => 'disabled', 'class' => 'form-control')) }}
                @else
                {{ Form::text('warehouses', "No", array('disabled' => 'disabled', 'class' => 'form-control')) }}
                @endif
            </div>

            <div class="form-group">
                {{ Form::label('tag', 'Tag') }}
                {{ Form::text('tag', $data->tag, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('timestamp', 'Timestamp') }}
                {{ Form::text('timestamp', $data->timestamp, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('lastUsed', 'Last Used') }}
                {{ Form::text('lastused', $data->lastUsed, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('ipaddress', 'IP Address') }}
                {{ Form::text('ipaddress', $data->ipAddress, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
            <a href="../device/{{$data->DeviceInfoId}}/edit"  class="btn btn-primary">Edit</a>
            </div>
      </div>
    </div>
  </div>
</div>
@stop