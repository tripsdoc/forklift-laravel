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

        {{ Form::model($data, array('route' => array('shifter.update', $data->ShifterID), 'method' => 'PUT')) }}

            <div class="form-group">
                {{ Form::label('name', 'Name') }}
                {{ Form::text('name', $data->Name, array('class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('username', 'Username') }}
                {{ Form::text('username', $data->UserName, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('password', 'Password') }}
                {{ Form::password('password', array('class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('warehouse', 'Warehouse') }}
                {{ Form::text('warehouse', $data->Warehouse, array('class' => 'form-control')) }}
            </div>

            {{ Form::submit('Update', array('class' => 'btn btn-primary')) }}

        {{ Form::close() }}
      </div>
    </div>
  </div>
</div>
@stop