@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')

<section id="bg-filter">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Filter</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form class="needs-validation" novalidate onsubmit="return false;">
          <div class="form-row">
            <div class="form-group col-md-3">
              <label class="form-label" for="fNumber">Budgeting Number</label>
              <input type="text" id="fNumber" class="form-control" autocomplete="off" />
            </div>
            <div class="form-group col-md-3">
              <label class="form-label" for="fDept">Department</label>
              <select id="fDept" class="form-control">
                <option value="">Semua</option>
                @foreach($list->pluck('dept_name')->filter()->unique()->sort() as $dn)
                  <option value="{{ $dn }}">{{ $dn }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-3">
              <label class="form-label" for="fStatus">Status</label>
              <select id="fStatus" class="form-control">
                <option value="">Semua</option>
                <option value="Overbudget">Overbudget</option>
                <option value="Underbudget">Underbudget</option>
                <option value="Sesuai Budget">Sesuai Budget</option>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label class="form-label" for="fFiscalYear">Fiscal Year</label>
              <select id="fFiscalYear" class="form-control">
                <option value="">Semua</option>
                @foreach($list->pluck('fiscal_year')->unique()->sort()->reverse() as $fy)
                  <option value="{{ $fy }}">{{ $fy }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="col-12">
              <a href="{{ route('budgeting.create') }}" class="btn btn-info">
                <i data-feather="plus"></i> Add Budgeting
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<section id="table-bg">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">{{ $title }} List</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
          <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <div class="table-responsive">
          <table id="bgListTable" class="table table-sm table-hover">
            <thead class="thead-light">
              <tr>
                <th>Budgeting Number</th>
                <th>Fiscal Year</th>
                <th>Department</th>
                <th>Status</th>
                <th>Previous Period</th>
                <th>Budget Period</th>
                <th style="min-width:140px">Budget Used</th>
                <th class="text-right">Total Budget</th>
                <th class="text-right">Actual</th>
                <th class="text-right">Margin</th>
                <th>Created By</th>
                <th>Created At</th>
                <th>Updated By</th>
                <th>Updated At</th>
                <th style="width:6%">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($list as $r)
              @php $statusCls = $r->status === 'Overbudget' ? 'danger' : ($r->status === 'Underbudget' ? 'success' : 'secondary'); @endphp
              <tr>
                <td>{{ $r->budgeting_number }}</td>
                <td>{{ $r->fiscal_year }}</td>
                <td>{{ $r->dept_name ?: $r->dept_code }}</td>
                <td><span class="badge badge-pill badge-light-{{ $statusCls }}">{{ $r->status }}</span></td>
                <td>{{ date('d M Y', strtotime($r->previous_from)) }} - {{ date('d M Y', strtotime($r->previous_to)) }}</td>
                <td>{{ date('d M Y', strtotime($r->budget_from)) }} - {{ date('d M Y', strtotime($r->budget_to)) }}</td>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="progress flex-grow-1" style="height:8px">
                      <div class="progress-bar bg-{{ $statusCls }}" style="width:{{ min($r->used_pct, 100) }}%"></div>
                    </div>
                    <small class="text-muted ml-1">{{ $r->used_pct }}%</small>
                  </div>
                </td>
                <td class="text-right">{{ number_format($r->total_budget, 2) }}</td>
                <td class="text-right">{{ number_format($r->actual, 2) }}</td>
                <td class="text-right"><span class="{{ $r->margin < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($r->margin, 2) }}</span></td>
                <td>{{ $r->created_by }}</td>
                <td>{{ $r->created_at ? date('d M Y H:i', strtotime($r->created_at)) : '-' }}</td>
                <td>{{ $r->updated_by }}</td>
                <td>{{ $r->updated_at ? date('d M Y H:i', strtotime($r->updated_at)) : '-' }}</td>
                <td>
                  <div class="d-inline-flex">
                    <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>
                    <div class="dropdown-menu dropdown-menu-right">
                      <a href="{{ route('budgeting.show', $r->id) }}" class="dropdown-item"><i data-feather="eye"></i> Detail</a>
                      <a href="{{ route('budgeting.edit', $r->id) }}" class="dropdown-item"><i data-feather="edit-2"></i> Edit</a>
                      <a href="javascript:;" onclick="deleteBudgeting('{{ $r->id }}','{{ $r->budgeting_number }}')" class="dropdown-item"><i data-feather="trash-2" class="feather-14-red"></i> Delete</a>
                    </div>
                  </div>
                </td>
              </tr>
              @empty
              <tr><td colspan="15" class="text-center text-muted">Belum ada budgeting.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('scripts')
<script type="text/javascript">
  let bgTable;
  $(function () {
    bgTable = $('#bgListTable').DataTable({ order: [[0, 'desc']] });
    if (window.feather) feather.replace({ width: 14, height: 14 });
  });

  $('#fNumber').on('keyup', function () {
    bgTable.column(0).search(this.value).draw();
  });
  $('#fDept').on('change', function () {
    bgTable.column(2).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
  });
  $('#fStatus').on('change', function () {
    bgTable.column(3).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
  });
  $('#fFiscalYear').on('change', function () {
    bgTable.column(1).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
  });

  function deleteBudgeting(id, number) {
    Swal.fire({
      title: 'Are you sure?',
      html: `Delete <b>${number}</b>? This action can not be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#EA5455',
      reverseButtons: true,
    }).then((result) => {
      if (!result.isConfirmed) return;
      $.ajax({
        url: '{{ url("budgeting") }}/' + id,
        type: 'DELETE',
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
      }).done(function (res) {
        Swal.fire('Deleted!', res.message || 'Berhasil dihapus.', 'success').then(() => location.reload());
      }).fail(function (xhr) {
        Swal.fire('Gagal', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus.', 'error');
      });
    });
  }
</script>
@endsection
