@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="msp-index">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">{{ $title }}</h4>
      @can('msp-create')
      <a href="{{ route('msp.create') }}" class="btn btn-primary btn-sm">
        <i data-feather="plus"></i> New Project
      </a>
      @endcan
    </div>
    <div class="card-body">
      <form method="GET" class="form-inline mb-2">
        <input type="text" name="q" value="{{ $q }}" class="form-control mr-2" placeholder="Cari MSP No / Part Name / Customer / Model">
        <button class="btn btn-outline-primary" type="submit">Search</button>
      </form>

      @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>MSP No</th>
            <th>Part Name</th>
            <th>Customer</th>
            <th>Model</th>
            <th>Rev</th>
            <th>Status</th>
            <th>Issue Date</th>
            <th>Last Updated</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($projects as $p)
          <tr>
            <td>{{ $p->msp_no }}</td>
            <td>{{ $p->part_name }}</td>
            <td>{{ $p->customer }}</td>
            <td>{{ $p->model }}</td>
            <td class="text-center">{{ $p->revision_no }}</td>
            <td><span class="badge badge-{{ $p->status == 'ongoing' ? 'warning' : ($p->status == 'done' ? 'success' : 'secondary') }}">{{ ucfirst($p->status) }}</span></td>
            <td>{{ $p->issue_date }}</td>
            <td>{{ $p->updated_at }}</td>
            <td>
              @can('msp-edit')
              <a href="{{ route('msp.edit', $p->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
              @endcan
              <a href="{{ route('msp.history', $p->id) }}" class="btn btn-sm btn-outline-secondary">History</a>
            </td>
          </tr>
          @empty
          <tr><td colspan="9" class="text-center">No data</td></tr>
          @endforelse
        </tbody>
      </table>
      {{ $projects->links() }}
    </div>
  </div>
</section>
@endsection
