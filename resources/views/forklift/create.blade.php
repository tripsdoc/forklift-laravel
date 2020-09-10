@extends('adminlte::page')
@section('content')
<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header card-header-primary">
            <h4 class="card-title ">Forklift User</h4>
            <p class="card-category"></p>
          </div>
          <div class="card-body">
          {{ Html::ul($errors->all()) }}

          {{ Form::open(array('url' => 'forklift')) }}

              <div class="form-group">
                  {{ Form::label('username', 'Username') }}
                  {{ Form::text('username', Request::old('username'), array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('password', 'Password') }}
                  {{ Form::password('password', array('class' => 'form-control')) }}
              </div>

              <div class="form-group">
                  {{ Form::label('isSupervisor', 'isSupervisor') }}
                  <div class="form-control">
                    {{ Form::radio('isSupervisor', 1) }} Yes
                    {{ Form::radio('isSupervisor', 0) }} No
                  </div>
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