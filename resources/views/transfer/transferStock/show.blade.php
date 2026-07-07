@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusTr }}</span></h4>
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
                                <div class="form-group col-md-2">
                                    <label for="trNumber">Transfer Number</label>
                                    <input type="text" id="trNumber" name="trNumber"
                                        value="{{ $header->tr_number }}"
                                        class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="trDate">Transfer Date</label>
                                    <input type="text" id="trDate" name="trDate"
                                        value="{{ $header->tr_date }}"
                                        class="form-control" disabled />
                                </div>
                                 <div class="form-group col-md-4">
                                    <label for="penerima">Penerima</label>
                                    <input type="text" id="penerima" name="penerima"
                                        value="{{ $header->penerima }}"
                                        class="form-control" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label">Location From</label>
                                    <input type="text" class="form-control"
                                        value="{{ $header->location_name }}" disabled />
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label">Location To</label>
                                    <input type="text" class="form-control"
                                        value="{{ $header->location_name_to }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea id="note" name="note" class="form-control" rows="4" disabled>{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <hr>
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            {{-- Header kolom --}}
                            <div class="form-row d-flex align-items-end d-none d-md-flex">
                                <div class="col-md-3 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold">Article Code</label></div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold text-right d-block">Min Package</label></div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold text-right d-block">QTY</label></div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold text-right d-block">UOM</label></div>
                                </div>
                                @if($header->tr_type === 'Supply')
                                <div class="col-md-2 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold d-block">FG Target</label></div>
                                </div>
                                @endif
                                <div class="col-md-3 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold d-block">Note</label></div>
                                </div>
                            </div>
                            <hr style="margin-top:0;">

                            {{-- Detail rows --}}
                            @foreach($details as $item)
                            <div class="tanda-baris">
                                <div class="form-row d-flex align-items-center">
                                    <div class="col-md-3 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Article</label>
                                            <input type="text" class="form-control"
                                                value="{{ $item->article_alternative_code }} - {{ $item->article_desc }}"
                                                disabled />
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Min Package</label>
                                            <input type="text" class="form-control text-right font-weight-bold"
                                            value="{{ number_format($item->min_package, 2) }}" disabled />
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">QTY</label>
                                            <input type="text" class="form-control text-right"
                                                value="{{ number_format($item->qty, 2) }}" disabled />
                                        </div>
                                    </div>
                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">UOM</label>
                                            <input type="text" class="form-control text-right"
                                                value="{{ $item->uom }}" disabled />
                                        </div>
                                    </div>
                                    @if($header->tr_type === 'Supply')
                                    <div class="col-md-2 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">FG Target</label>
                                            <input type="text" class="form-control"
                                                value="{{ $item->fg_target ?? '-' }}" disabled />
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-md-3 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Note</label>
                                            <input type="text" class="form-control"
                                                value="{{ $item->note }}" disabled />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold"
                                        value="{{ $header->sum_row }}" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('transferStock.index') }}" class="btn btn-light">Back</a>
                            <a href="{{ route('transferStock.print', ['id'=>Crypt::encryptString($header->id)]) }}"
                                target="_blank" class="btn btn-primary">
                                <i data-feather="printer"></i>
                                <span>{{ __("Print") }}</span>
                            </a>
                        </div>
                    </div>
                    <hr>

                    {{-- Approval History --}}
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            <div class="statistics-body">
                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                    <div class="media">
                                        <div class="avatar bg-light-{{ $val->status == true ? ($val->statusapprove == 1 ? 'success' : 'warning') : 'danger' }} mr-2">
                                            <div class="avatar-content">
                                                <i data-feather="{{ $val->status == true ? ($val->statusapprove == 1 ? 'check' : 'x') : 'x' }}"
                                                    class="avatar-icon"></i>
                                            </div>
                                        </div>
                                        <div class="media-body my-auto">
                                            <h4 class="font-weight-bolder mb-0">
                                                {{ $val->status == true ? ($val->statusapprove == 1 ? 'Approve' : 'Decline') : 'Approve' }}-{{ $val->approval_order }}
                                            </h4>
                                            <p class="card-text mb-0">
                                                {{ $val->status == true ? $val->name : $val->petugas }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('styles')
<style>
    textarea { resize: none; }
    .mb-03 { margin-bottom: 0.3rem; }
    label.titik-dua::after { content: ":"; position: absolute; right: 1px; }
    .margin-nol { margin-bottom: 0.5rem; }

    @media screen and (min-device-width: 1200px) and (max-device-width: 1600px) {
        .lebar-list-item { width: 100%; }
        .container-list-item { max-width: 100%; overflow-x: auto; scrollbar-width: thin; margin-top: 7px; }
    }
    @media only screen and (min-width: 600px) and (max-width: 1200px) {
        .lebar-list-item { width: 200%; }
        .container-list-item { max-width: 100%; overflow-x: auto; scrollbar-width: thin; margin-top: 7px; }
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function () {});
</script>
@endsection