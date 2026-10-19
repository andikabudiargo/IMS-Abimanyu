@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section>
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Saved Trial Balance</h4>
      <div class="heading-elements">
        <a href="{{ route('trialBalance.saved.create') }}" class="btn btn-primary btn-sm">Create New</a>
      </div>
    </div>
    <div class="card-content">
      <div class="card-body">
        <table class="table">
          <thead>
            <tr>
              <th>TB Number</th>
              <th>Year</th>
              <th>Description</th>
              <th>Note</th>
              <th>Created At</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @foreach($list as $row)
              <tr>
                <td>{{ $row->tb_number }}</td>
                <td>{{ $row->year }}</td>
                <td>{{ $row->description }}</td>
                <td>{{ $row->note }}</td>
                <td>{{ $row->created_at }}</td>
                <td>
                  <a href="{{ route('trialBalance.saved.edit', ['id'=>\Crypt::encryptString($row->id)]) }}" class="btn btn-sm btn-outline-primary">Open</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
@endsection
