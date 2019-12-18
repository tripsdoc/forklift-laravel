@extends('adminlte::page')

@section('css')
  <link href="{{ asset('assets/pagination.css') }}" rel="stylesheet">
  <link href="{{ asset('fullcalendar/core/main.css') }}" rel='stylesheet' />
  <link href="{{ asset('fullcalendar/daygrid/main.css') }}" rel='stylesheet' />
  <link href="{{ asset('fullcalendar/timegrid/main.css') }}" rel='stylesheet' />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/css/tempusdominus-bootstrap-4.min.css" />
@stop
@section('content_header')
    <h1>{{$data->park->name}}</h1>
@stop
@section('content')
<input type="hidden" value="{{ $data->park->id }}"/>
<div class="container-fluid">
    <div class="row">
      <div class="col-md-9">
      <div class="card">
      <div class="card-body">
        <div class="row">

          <div class="col-18 col-sm-6">
            <div class="info-box bg-light">
              <div class="info-box-content">
              <span class="info-box-text text-center text-muted">Ongoing Park</span>
              <span class="info-box-number text-center text-muted mb-0">
              @if(!empty($data->temporary))
               {{ $data->temporary[0]->parkIn }}
              @else
              -
              @endif
              </span>
              </div>
            </div>
          </div>

          <div class="col-18 col-sm-6">
            <div class="info-box bg-light">
              <div class="info-box-content">
              <span class="info-box-text text-center text-muted">Today's Parking</span>
              <span class="info-box-number text-center text-muted mb-0">
              {{ $data->count }}
              </div>
            </div>
          </div>

        </div>

        <div class="row">
          <div class="col-12">
            <h4>Calendar</h4>
          </div>
          <div id="calendar"></div>
        </div>

      </div>
      </div>
      </div>


      <div class="col-md-3">
      <div class="card">
      <div class="card-body">
        <h3 class="text-primary"><i class="fas fa-info-circle"></i> Details</h3>
      </div>
      </div>
      </div>


    </div>
</div>
@stop

@section('js')
<script src="{{ asset('fullcalendar/core/main.js') }}"></script>
<script src="{{ asset('fullcalendar/daygrid/main.js') }}"></script>
<script src="{{ asset('fullcalendar/timegrid/main.js') }}"></script>
<script type="text/javascript">
$(function () {
    var routeurl = "{{ route('CalendarPark', $data->park->id) }}";
    var date = new Date()
    var d    = date.getDate(),
        m    = date.getMonth(),
        y    = date.getFullYear()
    var Calendar = FullCalendar.Calendar;
    var calendarEl = document.getElementById('calendar');
    var calendar = new Calendar(calendarEl, {
        plugins: [ 'bootstrap', 'interaction', 'dayGrid', 'timeGrid' ],
        header    : {
            left  : 'prev,next today',
            center: 'title',
            right : 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        //Random default events
        events    : routeurl
    });
    calendar.render();
})
</script>
@stop