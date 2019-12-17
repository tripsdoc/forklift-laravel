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

        {{ Form::model($data, array('route' => array('device.update', $data->DeviceInfoId), 'method' => 'PUT')) }}

            <div class="form-group">
                {{ Form::label('devicename', 'DeviceName') }}
                {{ Form::text('devicename', $data->DeviceName, array('class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('serialnumber', 'Serial Number') }}
                {{ Form::text('serialnumber', $data->SerialNumber, array('class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('warehouses', 'WareHouses') }}
                {{ Form::text('warehouses', $data->WareHouses, array('class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('isactive', 'IsActive') }}
                <div class="form-control">
                {{ Form::radio('isactive', 1, ($data->IsActive == 1) ) }} Yes
                {{ Form::radio('isactive', 0, ($data->IsActive == 0) ) }} No
                </div>
            </div>

            <div class="form-group">
                {{ Form::label('tag', 'Tag') }}
                {{ Form::text('tag', $data->tag, array('class' => 'form-control')) }}
            </div>

            {{ Form::submit('Update', array('class' => 'btn btn-primary')) }}

        {{ Form::close() }}
      </div>
    </div>
  </div>
</div>
@stop