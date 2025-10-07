@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusInv }}</span></h4>
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
                            <input type="text" id="ppn" name="ppn" values="{{ $nilaiPPN }}" hidden>
                            <input type="text" id="pph23" name="ppn23" values="{{ $nilaiPPH }}" hidden>
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="invNumber">Invoice Number</label> <small class="text-muted"> automatic </small>
                                            <input type="text" id="invNumber" name="invNumber" value="{{ $header->invoice_number }}" class="form-control text-hitam disabled-el"  disabled />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="invDate">Invoice Date*</label>
                                            <input type="text" id="invDate" name="invDate" value="{{ $header->invoice_date }}" class="form-control" placeholder="DD-MM-YYYY"  disabled />
                                        </div>                               
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="customer">Customer*</label>
                                            <select class="select2 form-control" id="customer" name="customer" required disabled>
                                                <option value="">All</option>
                                                @foreach($customers as $val)
                                                    <option value="{{$val->kode}}" {{$val->kode == $header->customer_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="accountPiutang">COA Piutang*</label>
                                            <input type="text" id="accountPiutang" name="accountPiutang" class="form-control disabled-el" value="{{ old('accountPiutang',$header->account_piutang) }}" disabled />
                                        </div> 
                                        <div class="form-group col-md-6">
                                            <label for="sendingDate">Sending Date</label>
                                            <input type="text" id="sendingDate" name="sendingDate" class="form-control" value="{{ old('sendingDate',$header->sending_date) }}" placeholder="DD-MM-YYYY" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <div class="form-row">
                                                <div class="form-group col-md-12">
                                                    <label for="soDate">SO Date</label>
                                                    <input type="text" id="soDate" name="soDate" value="{{ $soDateRange }}" class="form-control flatpickr-range" placeholder="DD-MM-YYYY" disabled/>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label for="fakturPajak">Tax Number</label>
                                                    <input type="text" id="fakturPajak" name="fakturPajak"  value="{{ $header->faktur_pajak }}"  class="form-control" />
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="buktiPotong">No Bukti Potong</label>
                                                    <input type="text" id="buktiPotong" name="buktiPotong" value="{{ $header->bukti_potong }}" class="form-control" />
                                                </div>
                                            </div>
                                        </div>
                                        {{-- <div class="form-group col-md-6">
                                            <div class="form-group col-md-12" style="padding-right:0px;padding-left:0px">
                                                <label for="soDate">SO Date</label>
                                                <input type="text" id="soDate" name="soDate" value="{{ $soDateRange }}" class="form-control flatpickr-range" placeholder="DD-MM-YYYY" disabled/>
                                            </div>
                                            <div class="form-group col-md-12" style="padding-right:0px;padding-left:0px">
                                                <label for="fakturPajak">Tax Number</label>
                                                <input type="text" id="fakturPajak" name="fakturPajak" value="{{ $header->faktur_pajak }}" class="form-control" />
                                            </div>
                                        </div> --}}
                                        <div class="form-group col-md-6">
                                            <label class="form-label" for="soNumber" disabled>SO Number*</label>
                                            <textarea type="text" id="soNumber" name="soNumber" class="form-control" rows="4"  disabled>{{ $soNumbers }}</textarea>
                                            {{-- <input type="text" id="soNumber" name="soNumber" value="{{ $soNumbers }}" class="form-control" disabled /> --}}
                                        </div>
                                    </div>
                                    {{-- <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="soNumber" disabled>SO Number*</label>
                                            <input type="text" id="soNumber" name="soNumber" value="{{ $header->so_number }}" class="form-control" disabled />
                                        </div>
                                    </div> --}}
                                    <div class="form-row">
                                        {{-- <div class="form-group col-md-3">
                                            <label class="form-label" for="dnNumber"  disabled>DN Number*</label>
                                            <input type="text" id="dnNumber" name="dnNumber" value="{{ $header->dn_number }}" class="form-control" disabled />
                                        </div> --}}
                                        {{-- <div class="form-group col-md-6">
                                            <label for="fakturPajak">Tax Number</label>
                                            <input type="text" id="fakturPajak" name="fakturPajak" value="{{ $header->faktur_pajak }}" class="form-control" disabled />
                                        </div> --}}
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="note">Notes</label>
                                            <textarea type="text" id="note" name="note" class="form-control" rows="1"  disabled>{{ $header->note }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            
                                <div class="col-md-6 col-12">
                                    <div class="form-row">
                                        <div class="col-sm-12">
                                            <p class="mb-0">List DN*</p>
                                            <div class="col-sm-12 scrollable-box" >
                                            {{-- <div class="card-datatable table-responsive pt-0"> --}}
                                                <table class="table table-bordered" id="listOfDn">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" width="30%">DN Number</th>
                                                            <th scope="col" width="30%">Date</th>
                                                            <th scope="col" width="30%">PO Number</th>
                                                            <th scope="col" width="20%">SO Number</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($delivery as $val)
                                                        <tr>
                                                            <td>{{ $val->delivery_number }}</td>
                                                            <td>{{ $val->delivery_date }}</td>
                                                            <td>{{ $val->po_number }}</td>
                                                            <td>{{ $val->so_number }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="col-sm-12">
                            <p class="mb-0">Detail DN</p>
                            <div class="card-datatable table-responsive pt-0">
                                <table class="table table-bordered" id="listOfRec">
                                    <thead>
                                        <tr>
                                            <th scope="col" width="20%">Article Code</th>
                                            <th scope="col" width="40%">Desc</th>
                                            <th scope="col" width="10%">Qty</th>
                                            <th scope="col" width="10%">UOM</th>
                                            <th scope="col" width="10%">Material Price</th>
                                            <th scope="col" width="10%">Service Price</th>
                                            <th scope="col" width="10%">T.Material</th>
                                            <th scope="col" width="10%">T.Service</th>
                                            <th scope="col" width="10%">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($detail as $item)
                                        <tr>
                                            <td>{{ $item->article }}</td>
                                            <td>{{ $item->desc }}</td>
                                            <td class="text-right">{{ number_format($item->qty,2) }}</td>
                                            <td>{{ $item->uom }}</td>
                                            <td class="text-right">{{ number_format($item->price,2) }}</td>
                                            <td class="text-right">{{ number_format($item->price_service,2) }}</td>
                                            <td class="text-right">{{ number_format(($item->qty*$item->price),2) }}</td>
                                            <td class="text-right">{{ number_format(($item->qty*$item->price_service),2) }}</td>
                                            <td class="text-right">{{ number_format((($item->qty*$item->price)+($item->qty*$item->price_service)),2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                    </div> --}}
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-7">
                            {{-- <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua tanpa-padding">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua tanpa-padding">Total QTY</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div> --}}
                        </div>
                        <div class="col-md-6">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-4 col-form-label titik-dua tanpa-padding">Selling Price</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalAmount" value="{{ number_format((($header->grand_total-$header->total_ppn)+$header->total_pph),2) }}" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalDppNilaiLain" class="col-sm-4 col-form-label titik-dua">VAT Object <span id="nilaiDppLain">{{ $header->dpp_lain_value  ? $header->dpp_lain_pembilang."/".$header->dpp_lain_penyebut : '' }}</span></label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" oninput='inputDecimal(this)' value="{{ $header->dpp_lain_value>0 ? number_format($header->dpp_lain_value,2) : 0 }}" id="totalDppNilaiLain"  name="totalDppNilaiLain" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-4 col-form-label titik-dua tanpa-padding">VAT <span id="nilaiPPN">{{ $nilaiPPN }}%</span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPN" value="{{ number_format($header->total_ppn,2) }}"  disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPH" class="col-sm-4 col-form-label titik-dua tanpa-padding">WHT 23 <span id="nilaiPPH23">{{ $nilaiPPH }}%</span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPH" value="{{ number_format($header->total_pph,2) }}" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalNetto" class="col-sm-4 col-form-label titik-dua tanpa-padding">Total Bill</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalNetto" value="{{ number_format($header->grand_total,2) }}" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('invoice.index') }}" class="btn btn-light">Back</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            @if($val->status == true)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-{{ $val->statusapprove == 1 ? 'success':'warning' }} mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="{{ $val->statusapprove == 1 ? 'check':'x' }}" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">{{ $val->statusapprove == 1 ? 'Approve':'Decline' }}-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-danger mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="x" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->petugas }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
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

    textarea {
        resize: none;
    }

    .mb-03{
        margin-bottom: 0.3rem;
    }
    
    label.titik-dua::after{
        content : ":"; 
        position : absolute;
        right : 1px;
    }
    td.isian{
        padding-right:10px;
        padding-left:10px;
    }

    td.isian-satu{
        padding-right:5px;
        padding-left:15px;
        width: 25%;border-top: 1px solid #ffffff !important;
        border-bottom: 1px solid #ffffff !important;
        border-left: 1px solid #ffffff !important;
    }

    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

    label.tanpa-padding{
        padding-top: 5px;
        padding-bottom: 0px;
    }

    .totalLine{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

</style>
@endsection
@section('scripts')
@include('invoice.addArticle')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    $(document).ready(function(){
        hitungTotal();
    });
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection