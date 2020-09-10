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

        {{ Form::model($data, array('route' => array('forklift.update', $data->UserId), 'method' => 'PUT')) }}

            <div class="form-group">
                {{ Form::label('username', 'Username') }}
                {{ Form::text('username', $data->UserName, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('password', 'Password') }}
                {{ Form::password('password', array('class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('isSupervisor', 'isSupervisor') }}
                <div class="form-control">
                {{ Form::radio('isSupervisor', 1, ($data->isSupervisor == 1) ) }} Yes
                {{ Form::radio('isSupervisor', 0, ($data->isSupervisor == 0) ) }} No
                </div>
            </div>

            {{ Form::submit('Update', array('class' => 'btn btn-primary')) }}

        {{ Form::close() }}
      </div>
    </div>
  </div>
</div>
@stop