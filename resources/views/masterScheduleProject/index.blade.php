@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="msp-index">
  <div class="card msp-card">
    <div class="card-header">
      <h4 class="card-title"><i data-feather="calendar" class="font-medium-2 mr-1 text-primary"></i>{{ $title }}</h4>
      @can('msp-create')
      <a href="{{ route('msp.create') }}" class="btn btn-primary btn-sm">
        <i data-feather="plus" class="mr-50"></i>New Project
      </a>
      @endcan
    </div>
    <div class="card-body">
      <form method="GET" class="form-inline mb-2">
        <input type="text" name="q" value="{{ $q }}" class="form-control mr-2" placeholder="Cari MSP No / Part Name / Customer / Model" style="min-width:280px;">
        <button class="btn btn-outline-primary" type="submit"><i data-feather="search" class="mr-50"></i>Search</button>
      </form>

      @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <div class="table-responsive">
      <table class="table table-hover msp-list-table">
        <thead class="thead-light">
          <tr>
            <th>MSP No</th>
            <th>Part Name</th>
            <th>Customer</th>
            <th>Model</th>
            <th>Project Type</th>
            <th class="text-center">Rev</th>
            <th>Status</th>
            <th>Issue Date</th>
            <th>Last Updated</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($projects as $p)
          <tr>
            <td class="font-weight-600">{{ $p->msp_no }}</td>
            <td>{{ $p->part_name }}</td>
            <td>{{ $p->customer }}</td>
            <td>{{ $p->model }}</td>
            <td><span class="badge badge-light-primary">{{ $projectTypes[$p->project_type] ?? ucfirst($p->project_type ?? 'Regular Order') }}</span></td>
            <td class="text-center">{{ $p->revision_no }}</td>
            <td><span class="badge msp-status-badge badge-pill" data-status="{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
            <td>{{ $p->issue_date }}</td>
            <td>{{ $p->updated_at }}</td>
            <td>
              @can('msp-edit')
              <a href="{{ route('msp.edit', $p->id) }}" class="btn btn-sm btn-outline-primary"><i data-feather="edit-2"></i></a>
              @endcan
              <a href="{{ route('msp.history', $p->id) }}" class="btn btn-sm btn-outline-secondary"><i data-feather="clock"></i></a>
            </td>
          </tr>
          @empty
          <tr><td colspan="10" class="text-center text-muted py-3">No data</td></tr>
          @endforelse
        </tbody>
      </table>
      </div>
      {{ $projects->links() }}
    </div>
  </div>
</section>
@endsection

@section('styles')
<style>
.msp-card { border: 1px solid #e4e7ec; box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06); border-radius: 8px; }
.msp-card > .card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f1f4; }
.msp-card > .card-header .card-title { font-size: 1rem; font-weight: 600; color: #1d2939; margin-bottom: 0; display: flex; align-items: center; }
.msp-list-table thead th { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .02em; color: #667085; border-top: none; }
.msp-list-table td { vertical-align: middle; }
.font-weight-600 { font-weight: 600; color: #1d2939; }
.msp-status-badge[data-status="ongoing"] { background: #fff3cd; color: #946200; }
.msp-status-badge[data-status="done"] { background: #d1f7df; color: #0a7a3c; }
.msp-status-badge[data-status="cancelled"] { background: #eceff1; color: #52606d; }
</style>
@endsection
