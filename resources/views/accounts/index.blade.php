@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="accounts-index">
    <div class="row">
      <div class="col-12">
        <div class="card">
          {{-- <div class="card-header">  
            <div class="card-title">@yield('title')
            </div>
          </div> --}}
          <div class="card-body">
            <form class="needs-validation" novalidate>
                <div class="form-row">
                    <div class="col-md-4"> 
                        <div class="form-group">
                        <label for="searchAccCode">Account</label>
                        <input type="text" class="form-control text-uppercase" id="searchAccCode" name="searchAccCode" placeholder="" />
                        </div>
                    </div>
                    <div class="col-md-4"> 
                    <div class="form-group">
                        <label for="searchAcc">Description</label>
                        <input type="text" class="form-control text-uppercase" id="searchAcc" name="searchAcc" placeholder="" />
                    </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        @can('account-create')
                        <a href="{{ route('account.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#moveCoaModal">
                            <i class="fa fa-exchange"></i> Move COA
                        </button>
                    </div>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>
<section id="table-accounts">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">List @yield('title')</h4>
        <div class="heading-elements">
            <ul class="list-inline mb-0">
                <li>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="viewFlatBtn">Tabel</button>
                        <button type="button" class="btn btn-outline-primary" id="viewTreeBtn">Hierarki</button>
                    </div>
                </li>
                <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
            </ul>
        </div>
      </div>
      <div class="card-content collapse show">
        <div class="card-body">
          <div class="row">
              <div class="col-sm-12">
                <div id="coaFlatWrap" class="card-datatable table-responsive pt-0">
                  <table id="coaTableFlat" class="table">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:5%">Action</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-right">Opening Balance</th>
                            <th>Account Type</th>
                            <th>Sub Account</th>
                            <th>Category</th>
                            <th>Header</th>
                            <th>Normal Balance</th>
                            <th>Final Statement</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($flat as $acc)
                            <tr>
                                <td class="coa-actions">
                                    @can('account-edit')
                                    <a href="{{ route('account.edit', ['id' => Crypt::encryptString($acc->id)]) }}" title="Edit">
                                        <i data-feather="file-text"></i>
                                    </a>
                                    @endcan
                                    @can('account-delete')
                                    <a href="javascript:;" id="deleteButton" data-toggle="modal" data-target="#smallModal"
                                       data-href="{{ route('account.destroy', ['id' => Crypt::encryptString($acc->id)]) }}" title="Delete">
                                        <i data-feather="trash-2" class="feather-14-red"></i>
                                    </a>
                                    @endcan
                                </td>
                                <td>{{ $acc->account }}</td>
                                <td>{{ $acc->description }}</td>
                                <td class="text-right">{{ number_format($acc->opening_balance, 2) }}</td>
                                <td>{{ $acc->type_label }}</td>
                                <td>{{ $acc->sub_label }}</td>
                                <td>{{ $acc->acc_header }}</td>
                                <td>{{ $acc->header_label }}</td>
                                <td>{{ $acc->debit_credit }}</td>
                                <td>{{ $acc->final_statement }}</td>
                                <td>{{ $acc->note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                  </table>
                </div>
                <div id="coaTreeWrap" class="card-datatable table-responsive pt-0" style="display:none;">
                  <table id="coaTable" class="table coa-table">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:5%">Action</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-right">Opening Balance</th>
                            <th>Normal Balance</th>
                            <th>Final Statement</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tree as $type)
                            <tr class="coa-group coa-type-row" data-level="0">
                                <td colspan="7"><strong>{{ $type->code }} - {{ $type->name }}</strong></td>
                            </tr>
                            @foreach($type->subs as $sub)
                                <tr class="coa-group coa-sub-row" data-level="1">
                                    <td colspan="7" style="padding-left: 1.5rem">{{ $sub->sub_code }} - {{ $sub->description }}</td>
                                </tr>
                                @include('accounts.partials.account-node', ['accounts' => $sub->accounts, 'level' => 2])
                            @endforeach
                        @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
          </div>
        </div>
      </div>
    </div>
</section>
@include('partials.delete-modal')

<div class="modal fade" id="moveCoaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Move COA</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    Pindah/merge COA: semua dokumen yang masih memakai kode akun <b>Source</b> akan diubah
                    menjadi <b>Target</b>, termasuk dokumen di periode yang sudah di-lock. Tidak bisa dibatalkan
                    otomatis &mdash; cek checklist di bawah dengan teliti sebelum klik Move.
                </div>
                <div class="form-row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Source account (akan dipindah)</label>
                            <select id="sourceAccount" class="select2 w-100">
                                <option value=""></option>
                                @foreach($flat as $acc)
                                    <option value="{{ $acc->account }}">{{ $acc->account }} - {{ $acc->description }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Target account (tujuan)</label>
                            <select id="targetAccount" class="select2 w-100">
                                <option value=""></option>
                                @foreach($flat as $acc)
                                    <option value="{{ $acc->account }}">{{ $acc->account }} - {{ $acc->description }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" id="scanBtn" class="btn btn-primary form-control">Scan</button>
                        </div>
                    </div>
                </div>

                <div id="scanResult" style="display:none;">
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <a href="javascript:void(0)" id="checkAll">Check all</a> |
                            <a href="javascript:void(0)" id="uncheckAll">Uncheck all</a>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="deactivateSource">
                            <label class="form-check-label" for="deactivateSource">Deactivate source account after move</label>
                        </div>
                    </div>
                    <table class="table table-sm table-bordered" id="scanTable">
                        <thead>
                            <tr><th style="width:40px"></th><th>Table</th><th>Column</th><th>Rows affected</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" id="moveBtn" class="btn btn-danger">Move selected</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('styles')
<style>
    .coa-table .coa-group { background-color: rgba(0,0,0,.03); }
    .coa-table .coa-type-row { font-weight: 600; }
    .coa-table .coa-actions a, .coa-table td:first-child a { margin-right: .5rem; }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){
      let href;
      $(document).on('click', '#deleteButton', function(event) {
          event.preventDefault();
          href = $(this).data('href');
          $('#modalConfirmation').attr("action", href);
      });
      feather.replace();
    });

    $('a[data-action="reload"]').on('click', function () {
        location.reload();
    });

    //flat table / hierarchical view toggle
    $('#viewFlatBtn').on('click', function () {
        $(this).addClass('active').siblings().removeClass('active');
        $('#coaFlatWrap').show();
        $('#coaTreeWrap').hide();
    });
    $('#viewTreeBtn').on('click', function () {
        $(this).addClass('active').siblings().removeClass('active');
        $('#coaTreeWrap').show();
        $('#coaFlatWrap').hide();
    });

    $("#btnSearch").click(function(e){
        filterFlatTable($("#searchAcc").val(), $("#searchAccCode").val());
        filterTree($("#searchAcc").val(), $("#searchAccCode").val());
    });

    const filterFlatTable = (nama, code) => {
        nama = (nama || '').toLowerCase();
        code = (code || '').toLowerCase();
        const $rows = $('#coaTableFlat tbody tr');

        if (!nama && !code) {
            $rows.show();
            return;
        }

        $rows.each(function () {
            const acct = $(this).find('td').eq(1).text().trim().toLowerCase();
            const desc = $(this).find('td').eq(2).text().trim().toLowerCase();
            const match = (!code || acct.includes(code)) && (!nama || desc.includes(nama));
            $(this).toggle(match);
        });
    }

    const filterTree = (nama, code) => {
        nama = (nama || '').toLowerCase();
        code = (code || '').toLowerCase();

        const $rows = $('#coaTable tbody tr');

        if (!nama && !code) {
            $rows.show();
            return;
        }

        $rows.filter('.coa-group').hide();
        $rows.filter('.coa-detail-row').each(function () {
            const acct = $(this).find('td').eq(1).text().trim().toLowerCase();
            const desc = $(this).find('td').eq(2).text().trim().toLowerCase();
            const match = (!code || acct.includes(code)) && (!nama || desc.includes(nama));
            $(this).toggle(match);
        });

        //reveal every ancestor group row (type/sub/header) that still has a visible detail under it
        const ancestorsByLevel = {};
        $rows.each(function () {
            if ($(this).hasClass('coa-group')) {
                ancestorsByLevel[$(this).data('level')] = $(this);
            } else if ($(this).is(':visible')) {
                Object.values(ancestorsByLevel).forEach($row => $row.show());
            }
        });
    }

    (function () {
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const tbody = document.querySelector('#scanTable tbody');
        let currentSource = '', currentTarget = '';

        function post(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            }).then(r => r.json().then(data => ({ ok: r.ok, data })));
        }

        document.getElementById('scanBtn').addEventListener('click', function () {
            currentSource = document.getElementById('sourceAccount').value.trim();
            currentTarget = document.getElementById('targetAccount').value.trim();
            if (!currentSource || !currentTarget) { alert('Isi source dan target.'); return; }

            post("{{ route('coaMove.scan') }}", { source: currentSource, target: currentTarget }).then(function (res) {
                if (!res.ok) { alert(res.data.message); return; }
                tbody.innerHTML = '';
                res.data.rows.forEach(function (row) {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td><input type="checkbox" class="rowChk" data-pair="' + row.table + '.' + row.column + '"' +
                        (row.checked ? ' checked' : '') + '></td>' +
                        '<td>' + row.table + '</td><td>' + row.column + '</td><td>' + row.count + '</td>';
                    tbody.appendChild(tr);
                });
                document.getElementById('scanResult').style.display = 'block';
            });
        });

        document.getElementById('checkAll').addEventListener('click', () => document.querySelectorAll('.rowChk').forEach(c => c.checked = true));
        document.getElementById('uncheckAll').addEventListener('click', () => document.querySelectorAll('.rowChk').forEach(c => c.checked = false));

        document.getElementById('moveBtn').addEventListener('click', function () {
            const selected = Array.from(document.querySelectorAll('.rowChk:checked')).map(c => c.dataset.pair);
            if (selected.length === 0) { alert('Pilih minimal satu baris.'); return; }
            if (!confirm('Pindahkan ' + currentSource + ' -> ' + currentTarget + ' untuk ' + selected.length + ' tabel/kolom? Tidak bisa dibatalkan otomatis.')) return;

            post("{{ route('coaMove.execute') }}", {
                source: currentSource,
                target: currentTarget,
                selected: selected,
                deactivateSource: document.getElementById('deactivateSource').checked,
            }).then(function (res) {
                alert(res.data.message + (res.data.summary ? '\n\n' + res.data.summary.join('\n') : ''));
                if (res.ok) location.reload();
            });
        });
    })();
</script>
@endsection
