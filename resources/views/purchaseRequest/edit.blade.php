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
                    <h4 class="card-title">Status: {{ $statusPr }}</h4>
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
                            <input type="text" id="article" name="article" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="prNumber">Request Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="prNumber" name="prNumber" class="form-control disabled-el" value="{{ $header->pr_number }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="poType">PO Type*</label>
                                    <select class="select2 form-control" id="poType" name="poType" required disabled>
                                        <option value="tso" {{ $header->order_type == 'tso' ? "selected" : ""}}>Target SO</option>
                                        <option value="rm"  {{ $header->order_type == 'rm'  ? "selected" : ""}}>Raw Material</option>
                                        <option value="std" {{ $header->order_type == 'std' ? "selected" : ""}}>Standard</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control disabled-el" placeholder="DD-MM-YYYY" value="{{ $header->date }}"/>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="dept">Department*</label>
                                    <select class="select2 form-control" id="dept" name="dept" required>
                                        <option value=""></option>
                                        @foreach($depts as $val)
                                            <option value="{{$val->code}}" {{$val->code == $header->dept ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- TSO BOX --}}
                            @if($header->order_type == 'tso')
                            <div class="form-row" id="tsoBox">
                                <div class="form-group col-md-2">
                                    <label for="stockDate">Stock Date</label>
                                    <input type="text" id="stockDate" name="stockDate" class="form-control disabled-el"
                                        placeholder="DD-MM-YYYY"
                                        value="{{ date_format(date_create($header->stock_date),'d-m-Y') }}" disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="purchaseType">Purchase Type*</label>
                                    <select class="select2 form-control" id="purchaseType" name="purchaseType" disabled>
                                        <option value="purchase" {{ ($header->purchase_type ?? '') == 'purchase' ? 'selected' : '' }}>Purchase</option>
                                        <option value="np"       {{ ($header->purchase_type ?? '') == 'np'       ? 'selected' : '' }}>Non Purchase</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="tsoCode">Target SO Number</label>
                                    <input type="text" id="tsoCode" name="tsoCode" class="form-control disabled-el" value="{{ $header->tso_code }}" disabled/>
                                </div>
                            </div>
                            @endif

                            {{-- SUPPLIER BOX (Non Purchase) --}}
                            @if(($header->purchase_type ?? '') == 'np')
                            <div class="form-row" id="suppBox">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="suppCode">Supplier*</label>
                                    <select class="select2 form-control" id="suppCode" name="suppCode">
                                        <option value=""></option>
                                        @foreach(($suppliers ?? []) as $s)
                                            <option value="{{ $s->kode }}"
                                                {{ $s->kode == ($header->supp_code ?? '') ? 'selected' : '' }}>
                                                {{ $s->kode }} - {{ $s->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif

                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1">{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ARTICLE DETAIL --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div style="padding-right:10px">
                        @include("purchaseRequest.headerColumn")
                    </div>
                    <div class="" id="article_row" style="max-height: 30rem;overflow-x: hidden;scrollbar-width:thin;margin-top:7px;padding-right:10px">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                        <h6>Line: <span id="records"></span></h6>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <a href="{{ route('purchaseRequests.index') }}" class="btn btn-light">Back</a>
                            @if( $approveValidate ? $approveValidate[0]->validate : '')
                                <input type="text" id="approveLevel" name="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                <input type="text" id="maxLevel"     name="maxLevel"     class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                @if($statusPr == 'NEW')
                                    <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                @endif
                            @else
                                @if(!$approveValidate && $statusPr == 'NEW')
                                    <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                @endif
                            @endif
                        </div>
                    </div>
                    <hr>

                    {{-- APPROVAL HISTORY --}}
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            <div class="statistics-body">
                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                    <div class="media">
                                        <div class="avatar {{ $val->status ? 'bg-light-success' : 'bg-light-danger' }} mr-2">
                                            <div class="avatar-content">
                                                <i data-feather="{{ $val->status ? 'check' : 'x' }}" class="avatar-icon"></i>
                                            </div>
                                        </div>
                                        <div class="media-body my-auto">
                                            <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                            <p class="card-text mb-0">{{ $val->status ? $val->name : $val->petugas }}</p>
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
</style>
@endsection

@section('scripts')
@include('purchaseRequest.addArticlev2')
<script type="text/javascript">

    let detail = {!! $details !!};

    // ✅ Tidak perlu deklarasi ulang — sudah ada di addArticle.blade.php:
    // orderDate, stockDate, objPoType, objTsoBox, objTsoCode, addNewRow, suppBox, suppCode

    function fmtArtLine(item){
        let alt  = item.alternative || item.missing_code || item.fg_code || '-';
        let desc = item.article_desc || '';
        return `<li><b>${alt}</b>${desc ? ' - ' + desc : ''}</li>`;
    }

    $(document).ready(function(){           
        validateFormToast("frmAdd");
        isiArticle('article_pr');

        $(".loading-spinner-container").addClass("-show");

        // ✅ Langsung render, tidak perlu tunggu dataArticle
        if (detail && detail.length > 0) {
            isiData(detail);
            setTimeout(function(){
                $(".loading-spinner-container").removeClass("-show");
            }, 500);
        } else {
            $(".loading-spinner-container").removeClass("-show");
        }
    });

    // ✅ Flatpickr — gunakan variable yang sudah ada dari addArticle.blade.php
    if (orderDate.length) {
        orderDate.flatpickr({ dateFormat: "d-m-Y" });
    }

    isiData = (data) => {
    if (data){
        for (let i = 0; i < data.length; i++){
            add_new_row_edit(
                data[i].article_code,
                data[i].qty * 1,
                data[i].uom,
                data[i].uom_group,
                data[i].note,
                data[i].qty_stock,
                data[i].qty_hitung,
                data[i].article_alternative_code,
                data[i].article_desc,
                data[i].third_party
            );
        }
        recordCount();

        // ✅ Pengaman: isi ulang UOM setelah semua baris ter-render
        setTimeout(function(){
            refillAllUom(data);
            $(".loading-spinner-container").removeClass("-show");
        }, 150);
    }
}

    function loadTsoArticle(tsoCode, dStockDate, confirmExclude){
        $(".loading-spinner-container").addClass("-show");
        $.ajax({
            type: "GET",
            url: "{{ route('purchaseRequest.article.tso2') }}",
            data: {
                tsoCode       : tsoCode,
                stockDate     : dStockDate,
                purchaseType  : $('#purchaseType').val(),
                confirmExclude: confirmExclude ? '1' : '0'
            },
            dataType: "json",
            success: function(res){
                $(".loading-spinner-container").removeClass("-show");

                if (res.status === 'bom_not_approved'){
                    let listFg = res.unapproved_fg.map(fmtArtLine).join('');
                    Swal.fire({
                        title: 'BOM Belum Full Approve',
                        html : `FG berikut BOM-nya belum Full Approve:`
                             + `<ul style="text-align:left;margin-top:8px">${listFg}</ul>`
                             + `Jika dilanjutkan, FG tersebut akan <b>dikecualikan</b>.<br>Lanjutkan?`,
                        icon : 'warning',
                        showCancelButton : true,
                        confirmButtonText: 'Lanjutkan',
                        cancelButtonText : 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) loadTsoArticle(tsoCode, dStockDate, true);
                    });
                    return;
                }

                if (res.status === 'missing_article'){
                    let listArticle = res.missing_articles.map(fmtArtLine).join('');
                    Swal.fire({
                        title: 'Article Tidak Ditemukan!',
                        html : `Article berikut tidak ada di master data:`
                             + `<ul style="text-align:left;margin-top:8px">${listArticle}</ul>`
                             + `Silakan tambahkan article tersebut terlebih dahulu.`,
                        icon : 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                let data = res.data ?? [];
                if (data.length){
                    $('#article_row').empty();
                    cloneCount = 0;
                    for (let i = 0; i < data.length; i++){
                        add_new_row_sto(
                            data[i].article_code,
                            data[i].grand_total,
                            data[i].uom,
                            '',
                            data[i].qty_stock,
                            data[i].alternative,
                            data[i].article_desc,
                            data[i].uom_group,
                            data[i].supp
                        );
                    }
                    isiUom();
                    recordCount();
                } else {
                    Swal.fire('Info','Tidak ada article yang ditemukan','info');
                }
            },
            error: function(){
                $(".loading-spinner-container").removeClass("-show");
                Swal.fire('Error..','Terjadi kesalahan saat memuat data TSO','error');
            }
        });
    }

  $("#cmdUpdate").click(function(){
    $('.disabled-el').removeAttr('disabled');

    let objQty    = $('#article_row input[name="qty_order[]"]');
    let objNote   = $('#article_row input[name="note[]"]');
    let objUom    = $('#article_row select[name="uom[]"]');  // ✅ select, bukan span
    let objHitung = $('#article_row input[name="qtyHitung[]"]');
    let objStock  = $('#article_row input[name="qtyStock[]"]');
    let articles  = [];
    let flag      = 0;
    let pesan     = "";
    let poType    = $('#poType').val();
    let suppHeader = $('#suppCode').val();

    if (poType === 'np' && !suppHeader){
        pesan += "Supplier wajib dipilih <br>";
        flag = 1;
    }

    $("#article_row select[name='article_id[]']").map(function(i) {
        let $this = $(this);
        if ($this.val()){
            let article     = $this.find(":selected").data("detail").split('|');
            let articleName = $this.select2('data')[0].text;
            let plu         = article[0];
            let supp        = poType === 'np' ? suppHeader : article[2];
            let uom         = objUom.eq(i).val();   // ✅ .val() bukan .text()
            let qty         = objQty.eq(i).val().replace(/,/gi, '') || 0;
            let note        = objNote.eq(i).val();
            let qtyHitung   = objHitung.eq(i).val().replace(/,/gi, '') || 0;
            let qtyStock    = objStock.eq(i).val().replace(/,/gi, '') || 0;

            let obj = $.grep(articles, function(o){ return o.article_code === plu; })[0];

            if (obj) {
                pesan += "Article " + articleName + " entered more than once !! <br>";
                flag = 1;
            } else {
                if ((plu !== '') && (qty > 0)){
                    articles.push({
                        "article_code" : plu,
                        "qty"          : qty,
                        "uom"          : uom,
                        "supp"         : supp,
                        "note"         : note,
                        "qty_hitung"   : qtyHitung,
                        "qty_stock"    : qtyStock,
                    });
                }
            }

            if (qty == 0){
                pesan += "QTY of items " + articleName + " cannot be 0 <br>";
                flag = 1;
            }

            if ((poType == 'tso') && (parseFloat(qty) > parseFloat(qtyHitung))){
                pesan += `QTY of items ${articleName} tidak boleh melebihi qty hasil hitung ${qtyHitung} <br>`;
                flag = 1;
            }
        }
    });

    if (articles.length == 0){
        pesan += "Articles must be filled in completely <br>";
        flag = 1;
    }

    if (flag == 0){
        $.ajax({
            type    : "post",
            url     : "{{ route('purchaseRequest.update') }}",
            data    : {
                articles    : JSON.stringify(articles),
                orderDate   : $('#orderDate').val(),
                dept        : $('#dept').val(),
                note        : $('#note').val(),
                prNumber    : $('#prNumber').val(),
                suppCode    : suppHeader,
                purchaseType: $('#purchaseType').val()
            },
            dataType: "json",
            success : function(data) {
                if (data.status == 0){
                    if (Array.isArray(data.message)){
                        for(let i = 0; i < data.message.length; i++){
                            show_msg(data.title, data.message[i], data.alert);
                        }
                    } else {
                        show_msg(data.title, data.message, data.alert);
                    }
                    $('#prNumber').attr('disabled','disabled');
                } else {
                    show_msg(data.title, data.message, data.alert);
                    $('#prNumber').attr('disabled','disabled');
                    $('.disabled-el').attr('disabled','disabled');
                }
            },
            error: function(error){ console.log(error); }
        });
    } else {
        Swal.fire('Warning..', pesan, 'warning');
    }
});

    $("#cmdApprove").click(function(){    
        $.ajax({
            type    : "get",
            url     : "{{ route('purchaseRequest.approve') }}",
            data    : { prNumber: $('#prNumber').val() },
            dataType: "json",
            success : function(data) {
                if (data.status == 0){
                    for(let i = 0; i < data.message.length; i++){
                        show_msg(data.title, data.message[i], data.alert);
                    }
                } else {
                    show_msg(data.title, data.message, data.alert);
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');  
                    $('#cmdUpdate').attr('disabled','disabled');
                    location.reload();       
                }
            },
            error: function(error){ console.log(error); }
        });
    });

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

</script>
@endsection