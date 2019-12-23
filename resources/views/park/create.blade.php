@extends('adminlte::page')
@section('content')
<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-primary">
            <h4 class="card-title ">Add Park</h4>
            <p class="card-category"></p>
          </div>
          <div class="card-body">
          {{ Html::ul($errors->all()) }}

          {{ Form::open(array('url' => 'park')) }}

              <div class="form-group">
                  {{ Form::label('name', 'Park Name') }}
                  {{ Form::text('name', Request::old('name'), array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('type', 'Park Type') }}
                  {!! Form::select('type',  array(1 => 'Warehouse', 2 => 'Parking Lots'), null, ['class' => 'form-control']) !!}
              </div>

              <div class="form-group">
                  {{ Form::label('place', 'Warehouse / Parking Lots') }}
                  {{ Form::text('place', Request::old('place'), array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('detail', 'Detail') }}
                  {{ Form::text('detail', Request::old('detail'), array('class' => 'form-control')) }}
              </div>

              {{ Form::submit('Create', array('class' => 'btn btn-primary')) }}

          {{ Form::close() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection