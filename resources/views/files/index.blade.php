@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
{{-- <section id="suppliers-index">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">  
            <div class="card-title">@yield('title')
            </div>
          </div>
        </div>
      </div>
    </div>
</section> --}}
<section id="list-backup">
  <div class="card">
    <div class="col-lg-6 col-md-12">
      <div class="card">
          <div class="card-header">
              <h4 class="card-title">Backup file list</h4>
          </div>
          <div class="card-body">
              <ul class="list-group">
                @foreach($files as $item)
                <a href="{{route('file.download',['file'=>$item])}}" alt="{{ $item }}">
                  <li class="list-group-item">{{ $item }}</li>
                </a> 
                  {{-- <li class="list-group-item">{{ $item }}</li> --}}
                @endForEach
              </ul>
          </div>
      </div>
    </div>
  </div>
</section>
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">

 
</script>
@endsection