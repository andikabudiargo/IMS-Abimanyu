@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="edit-form">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusPrd }}</span> — Revisi {{ $header->num_revision }}</h4>
                    <input type="hidden" id="oEdit" value="{{ $oEdit }}">
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="prdNumber">Production Number</label>
                                    <input type="text" id="prdNumber" name="prdNumber" value="{{ $header->prod_code }}" class="form-control form-control-sm disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="loadingDate">Loading Date*</label>
                                    <input type="text" id="loadingDate" name="loadingDate" value="{{ $header->loading_date_fmt }}" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="sprayBooth">Spray Booth*</label>
                                    <select class="select2 form-control" id="sprayBooth" name="sprayBooth" required>
                                        <option value=""></option>
                                        @foreach($sprayBooths as $val)
                                            <option value="{{ $val->location_code }}" {{ $header->spray_booth == $val->location_code ? 'selected' : '' }}>{{ $val->location_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-10">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control" rows="1">{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive main-table">
                        <table class="table table-bordered w-100" id="tblArticle">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Article Code</th>
                                    <th>Article Desc</th>
                                    <th class="text-right">Qty Fresh</th>
                                    <th class="text-right">Qty Repaint</th>
                                    <th class="text-right">Qty Total</th>
                                    <th>Note</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="article_row">
                                @foreach($details as $item)
                                <tr data-article-code="{{ $item->article_code }}">
                                    <td>{{ $item->article_alternative_code ?? $item->article_code }}</td>
                                    <td>{{ $item->article_desc }}</td>
                                    <td class="text-right">
                                        <input type="text" class="form-control text-right qty-fresh numeral-mask-satuan" value="{{ $item->qty_fresh }}">
                                    </td>
                                    <td class="text-right">
                                        <input type="text" class="form-control text-right qty-repaint numeral-mask-satuan" value="{{ $item->qty_repaint }}">
                                    </td>
                                    <td class="text-right qty-total">{{ number_format($item->qty) }}</td>
                                    <td>
                                        <input type="text" class="form-control note-item" value="{{ $item->note }}">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger btn-remove-row"><i data-feather="trash-2"></i></button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <hr>
                    <a href="{{ route('production.actualLoading.index') }}" class="btn btn-light">Back</a>
                    @if($statusPrd == 'NEW')
                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                    @endif
                    @if($approveValidate ? $approveValidate[0]->validate : '')
                        <input type="text" id="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                        <input type="text" id="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                    @endif

                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            <div class="statistics-body">
                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                    <div class="media">
                                        <div class="avatar bg-light-{{ $val->status ? 'success' : 'danger' }} mr-2">
                                            <div class="avatar-content">
                                                <i data-feather="{{ $val->status ? 'check' : 'x' }}" class="avatar-icon"></i>
                                            </div>
                                        </div>
                                        <div class="media-body my-auto">
                                            <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                            <p class="card-text mb-0">{{ $val->name ?? $val->petugas }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ── PANEL HISTORY / LOG PERUBAHAN ── --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">History Perubahan</h4>
                </div>
                <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                    @forelse($history as $rev)
                        <div class="mb-2 pb-2 border-bottom">
                            <div class="d-flex justify-content-between">
                                <strong>Revisi {{ $rev['revision'] }}</strong>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($rev['created_at'])->format('d-m-Y H:i') }}</small>
                            </div>
                            <small class="text-muted">oleh {{ $rev['created_by'] }}</small>
                            <ul class="mt-1 mb-0 pl-3" style="font-size: 0.85rem;">
                                @foreach($rev['changes'] as $c)
                                    <li>
                                        @if($c->ref_type == 'det')
                                            <span class="text-primary">[{{ $c->article_code }}]</span>
                                        @endif
                                        <strong>{{ $c->field_name }}</strong>:
                                        @if($c->field_name == 'Article Added')
                                            <span class="text-success">ditambahkan — {{ $c->new_value }}</span>
                                        @elseif($c->field_name == 'Article Removed')
                                            <span class="text-danger">dihapus — {{ $c->old_value }}</span>
                                        @else
                                            <span class="text-danger">{{ $c->old_value ?: '(kosong)' }}</span>
                                            →
                                            <span class="text-success">{{ $c->new_value ?: '(kosong)' }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @empty
                        <p class="text-muted">Belum ada perubahan tercatat.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('styles')
<style>
    textarea { resize: none; }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){
        mask_thousand_digit(numberOfDecimalDigit);
    });

    function recalcRowTotal($row) {
        let fresh = parseFloat($row.find('.qty-fresh').val().replace(/,/g,'')) || 0;
        let repaint = parseFloat($row.find('.qty-repaint').val().replace(/,/g,'')) || 0;
        $row.find('.qty-total').text((fresh + repaint).toLocaleString());
    }

    $(document).on('change keyup', '.qty-fresh, .qty-repaint', function(){
        recalcRowTotal($(this).closest('tr'));
    });

    $(document).on('click', '.btn-remove-row', function(){
        $(this).closest('tr').remove();
    });

    const approveBtn = document.querySelector('#cmdApprove');
    if (approveBtn) {
        approveBtn.addEventListener('click', () => {
            let prdNumber = $('#prdNumber').val();
            approve(prdNumber, 'cmdApprove');
        }, { once: true });
    }

    const saveBtn = document.querySelector('#cmdSave');
    if (saveBtn) {
        saveBtn.addEventListener('click', function(){
            let articles = [];
            $('#article_row tr').each(function(){
                let $row = $(this);
                let qtyFresh = parseFloat($row.find('.qty-fresh').val().replace(/,/g,'')) || 0;
                let qtyRepaint = parseFloat($row.find('.qty-repaint').val().replace(/,/g,'')) || 0;
                articles.push({
                    article_code: $row.data('article-code'),
                    qty_fresh: qtyFresh,
                    qty_repaint: qtyRepaint,
                    qty: qtyFresh + qtyRepaint,
                    note: $row.find('.note-item').val()
                });
            });

            $.ajax({
                url: "{{ route('production.actualLoading.update') }}",
                method: "POST",
                data: {
                    prdNumber: $('#prdNumber').val(),
                    loadingDate: $('#loadingDate').val(),
                    sprayBooth: $('#sprayBooth').val(),
                    note: $('#note').val(),
                    articles: JSON.stringify(articles)
                },
                success: function(res){
                    toastNotif(res.title, res.message, res.alert);
                    if (res.status == 1) {
                        setTimeout(() => location.reload(), 1000);
                    }
                }
            });
        });
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection