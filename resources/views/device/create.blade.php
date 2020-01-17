@extends('adminlte::page')
@section('content')
<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-primary">
            <h4 class="card-title ">Device Info</h4>
            <p class="card-category"></p>
          </div>
          <div class="card-body">
          {{ Html::ul($errors->all()) }}

          {{ Form::open(array('url' => 'device')) }}

              <div class="form-group">
                  {{ Form::label('devicename', 'Device Name') }}
                  {{ Form::text('devicename', Request::old('devicename'), array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('serialnumber', 'Serial Number') }}
                  {{ Form::text('serialnumber', Request::old('serialnumber'), array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('warehouses', 'WareHouses') }}
                  {{ Form::text('warehouses', Request::old('warehouses'), array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('isactive', 'IsActive') }}
                  <div class="form-control">
                    {{ Form::radio('isactive', 1) }} Yes
                    {{ Form::radio('isactive', 0) }} No
                  </div>
              </div>

              <div class="form-group">
                  {{ Form::label('tag', 'Tag') }}
                  {{ Form::text('tag', Request::old('tag'), array('class' => 'form-control')) }}
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