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

        {{ Form::model($data, array('route' => array('park.update', $data->id), 'method' => 'PUT')) }}

            <div class="form-group">
                {{ Form::label('name', 'Park Name') }}
                {{ Form::text('name', $data->name, array('class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('detail', 'Detail') }}
                {{ Form::text('detail', $data->detail, array('class' => 'form-control')) }}
            </div>

            {{ Form::submit('Update', array('class' => 'btn btn-primary')) }}

        {{ Form::close() }}
      </div>
    </div>
  </div>
</div>
@stop