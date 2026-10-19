@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section>
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Create Trial Balance</h4>
    </div>
    <div class="card-content">
      <div class="card-body">
        <form id="frmCreate" method="post" action="{{ route('trialBalance.saved.store') }}" autocomplete="off">
          @csrf
          <div class="form-row">
            <div class="form-group col-md-2">
              <label class="form-label">Year *</label>
              <input type="number" name="year" class="form-control" value="{{ date('Y') }}" min="2000" max="2100" required>
            </div>
            <div class="form-group col-md-5">
              <label class="form-label">Description</label>
              <input type="text" name="description" class="form-control">
            </div>
            <div class="form-group col-md-5">
              <label class="form-label">Note</label>
              <input type="text" name="note" class="form-control">
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Save</button>
          <a href="{{ route('trialBalance.saved.list') }}" class="btn btn-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
