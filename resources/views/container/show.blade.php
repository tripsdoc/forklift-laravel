@extends('adminlte::page')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header card-header-primary">
        <div class="d-flex justify-content-between">
            <h1 class="card-title ">{{ $data->ContainerPrefix }}{{ $data->ContainerNumber }}</h1>
            <h4 class="card-title ">{{ $data->Dummy }}</h4>
        </div>
      </div>
      <div class="card-body">
        {{ Html::ul($errors->all()) }}
            <div class="form-group">
                {{ Form::label('jobnumber', 'Job Number') }}
                {{ Form::text('jobnumber', $data->JobNumber, array('disabled' => 'disabled', 'class' => 'form-control')) }}
            </div>

            <div class="form-group">
                {{ Form::label('containerprefix', 'Container') }}
                <div class="row">
                    <div class="col-1">
                        {{ Form::text('containerprefix', $data->ContainerPrefix, array('disabled' => 'disabled', 'class' => 'form-control form-control-inline')) }}
                    </div>
                    <div class="col-2">
                        {{ Form::text('containernumber', $data->ContainerNumber, array('disabled' => 'disabled', 'class' => 'form-control form-control-inline')) }}
                    </div>
                </div>
            </div>

            <div class="form-group">
                {{ Form::label('containersize', 'Container Size') }}
                <div class="row">
                    <div class="col-1">
                        {{ Form::text('containersize', $data->ContainerSize, array('disabled' => 'disabled', 'class' => 'form-control form-control-inline')) }}
                    </div>
                    <div class="col-1">
                        {{ Form::text('containertype', $data->ContainerType, array('disabled' => 'disabled', 'class' => 'form-control form-control-inline')) }}
                    </div>
                </div>
            </div>

            <div class="form-group">
            <a href="../container/{{$data->Dummy}}/edit"  class="btn btn-primary">Edit</a>
            </div>
      </div>
    </div>
  </div>
</div>
@stop