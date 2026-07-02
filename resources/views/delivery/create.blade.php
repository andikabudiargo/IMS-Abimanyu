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
                    <h4 class="card-title">Status: <span id="statusText"></span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                            <input type="hidden" id="idDn" name="idDn" class="form-control" />
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="dnNumber">Delivery Note Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="dnNumber" name="dnNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="dnDate">Delivery Date*</label>
                                    <input type="text" id="dnDate" name="dnDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="poNumberHdr">PO Number</label>
                                    <input type="text" id="poNumberHdr" name="poNumberHdr" class="form-control" disabled />
                                </div>                          
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <select class="select2 form-control" id="customer" name="customer" required>
                                        <option value=""></option>
                                        @foreach($customers as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="soNumber">SO Number*</label>
                                    <select class="select2 form-control" id="soNumber" name="soNumber" required>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="osNumber">OS/JTC</label>
                                    <input type="text" id="osNumber" name="osNumber" class="form-control" />
                                </div>  
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-9">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
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
                <div class="card-body" >
                    @include('delivery.headerColumn')
                    <input type="text" id ="last_row_number" class="d-none" value="0">
                    <form id="articleRowFrm" name="articleRowFrm" autocomplete="off">
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                    </div>
                    </form>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua tanpa-padding">Row(s)</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua tanpa-padding">Total QTY</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            <a href="{{ route('delivery.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            <button class="btn btn-dark" type="button" id="cmdPrint" name="cmdPrint">Print</button>
                        </div>
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
@include('delivery.addArticle')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    let fromEdit = 'false';
    let lockedAt = "{{ $lockDate }}";
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $('#statusText').text('New');
        $('#dnDate').val(currentDate);
        $('#cmdSave').show();
        $('#cmdPrint').hide();
    });

    dnDate = $('#dnDate');
    if (dnDate.length) {
        dnDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today",
            minDate:lockedAt
        });
    }

    function reloadPage(){
        window.location.reload();
    }

    $("#cmdNew").click(function(){
        reloadPage();
    });

   // ============================================================
// Cek stok Finish Goods
// ============================================================
checkFGStock = (articleCodes, callback) => {
    $.ajax({
        type: "post",
        url: "{{ route('delivery.checkFGStock') }}",
        data: {
            article_codes: JSON.stringify(articleCodes)
        },
        dataType: "json",
        success: function(data) {
            callback(null, data);
        },
        error: function(error) {
            callback(error, null);
        }
    });
}

// ============================================================
// Build tabel tampilan stok FG
// ============================================================
buildStockTable = (stockData) => {
    let rows = '';
    stockData.forEach(item => {
        let rowClass = item.stock <= 0 ? 'table-danger' : (item.stock < item.qty ? 'table-warning' : '');
        let stockLabel = item.stock <= 0 
            ? `<span class="badge badge-danger">OUT OF STOCK</span>` 
            : (item.stock < item.qty 
                ? `<span class="badge badge-warning">${item.stock}</span>` 
                : `<span class="badge badge-success">${item.stock}</span>`);
        rows += `
            <tr class="${rowClass}">
                <td>${item.article_code}</td>
                <td>${item.article_desc}</td>
                <td class="text-right">${item.qty}</td>
                <td class="text-right">${stockLabel}</td>
                <td class="text-center">
                    ${item.stock <= 0 
                        ? '<i class="feather icon-x-circle text-danger"></i> Tidak Bisa Dikirim' 
                        : (item.stock < item.qty 
                            ? '<i class="feather icon-alert-triangle text-warning"></i> Stok Kurang' 
                            : '<i class="feather icon-check-circle text-success"></i> OK')}
                </td>
            </tr>`;
    });

    return `
        <div class="text-left">
            <table class="table table-bordered table-sm mt-1" style="font-size:13px">
                <thead class="thead-dark">
                    <tr>
                        <th>Article Code</th>
                        <th>Description</th>
                        <th class="text-right">QTY DN</th>
                        <th class="text-right">Stok FG</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
}

// ============================================================
// checkBeforeSave — tambah pengecekan stok FG
// ============================================================
checkBeforeSave = () => {
    if (!$("#frmAdd")[0].checkValidity()) {
        $("#frmAdd").submit();
        return;
    }

    $("#cmdSave").attr('disabled', 'disabled');
    $('.disabled-el').removeAttr('disabled');

    let objQtySo = $('#article_row input[name="qtySo[]"]');
    let objQty   = $('#article_row input[name="qtyInv[]"]');
    let objStock = $('#article_row input[name="qtyStock[]"]');

    let articles     = [];
    let articleCodes = [];   // untuk cek stok FG
    let flag  = 0;
    let pesan = "";

    $("#article_row input[name='articleId[]']").map(function(i) {
        let $this = $(this);
        if ($this.val()) {
            let articleCode = $this.data("code");
            let articleDesc = $this.data("desc");
            let articleUom  = $this.data("uom");
            let articleSoCode = $this.data("so-code");
            let poNumber    = $this.data("po-number");
            let qty   = objQty.eq(i).val().replace(/,/gi, '') || 0;
            let qtySo = objQtySo.eq(i).val().replace(/,/gi, '') || 0;

            let stock = objStock.eq(i).val().replace(/,/gi, '') || 0;

            if (parseFloat(qty) > parseFloat(qtySo)){
                pesan += "Items " + articleDesc + " - Qty Delivery (" + qty + ") melebihi Qty SO (" + qtySo + ")<br>";
                flag = 1;
            }

            // Validasi "qty melebihi Stock Finish Goods" DIHAPUS sesuai permintaan —
            // overstock sekarang tidak lagi memblokir Save. Info stok tetap ditampilkan
            // sebagai peringatan non-blocking lewat checkFGStock (Swal warning di bawah).

            if ((articleCode !== '') && (qty > 0)) {
                articles.push({ "article_code": articleCode });
                articleCodes.push({ 
                    "article_code": articleCode, 
                    "article_desc": articleDesc,
                    "qty": parseInt(qty) 
                });
            }

        }
    });

    if (articles.length === 0) {
        pesan += "Articles must be filled in completely <br>";
        flag = 1;
    }

    if (flag === 1) {
        $('#cmdSave').removeAttr('disabled');
        $('#cmdPrint').hide();
        Swal.fire('Warning..', pesan, 'warning');
        return;
    }

    // ── STEP 1: Cek Stok Finish Goods dulu ──────────────────
    checkFGStock(articleCodes, function(err, stockResult) {
        if (err) {
            console.log(err);
            $('#cmdSave').removeAttr('disabled');
            Swal.fire('Error', 'Gagal mengecek stok Finish Goods', 'error');
            return;
        }

        // FIX: dulu hasZeroStock hard-block (Swal cuma tombol "Tutup", langsung return
        // tanpa opsi lanjut). Sekarang digabung sama hasLowStock jadi satu peringatan
        // non-blocking — stok 0 ataupun kurang tetap dikasih pilihan "Lanjutkan Save".
        let hasInsufficientStock = stockResult.some(item => item.stock < item.qty);
        let stockTable = buildStockTable(stockResult);

        if (hasInsufficientStock) {
            Swal.fire({
                width: 900,
                title: '<strong class="text-warning">⚠️ Perhatian: Stok Finish Goods Tidak Mencukupi</strong>',
                icon: 'warning',
                html: `<p>Terdapat artikel dengan stok <b>kurang atau kosong</b> di Gudang Finish Goods. 
                        Anda tetap bisa melanjutkan Save.</p>${stockTable}`,
                showDenyButton: true,
                denyButtonText: 'Batal',
                confirmButtonText: 'Lanjutkan Save',
            }).then((result) => {
                if (result.isConfirmed) {
                    runPreStore(articles);
                } else {
                    $('#cmdSave').removeAttr('disabled');
                }
            });
            return;
        }

        Swal.fire({
            width: 900,
            title: '<strong class="text-success">✅ Stok Finish Goods Tersedia</strong>',
            icon: 'success',
            html: stockTable,
            showDenyButton: false,
            confirmButtonText: 'Lanjutkan Save',
            timer: 3000,
            timerProgressBar: true,
        }).then((result) => {
            if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                runPreStore(articles);
            } else {
                $('#cmdSave').removeAttr('disabled');
            }
        });
    });
}

// ── STEP 2: Panggil preStore (duplikat SO check) ─────────────
runPreStore = (articles) => {
    $.ajax({
        type: "post",
        url: "{{ route('delivery.preStore') }}",
        data: { articles: JSON.stringify(articles) },
        dataType: "json",
        success: function(data) {
            if (data.status == 0) {
                for (let i = 0; i < data.message.length; i++) {
                    show_msg(data.title, data.message[i], data.alert);
                }
                $('#dnNumber').attr('disabled', 'disabled');
                $('#cmdSave').removeAttr('disabled');
            } else {
                Swal.fire({
                    width: 1050,
                    title: '<strong>Terdapat Article serupa yang belum dikirim di SO Berikut:</strong>',
                    icon: 'info',
                    html: data.table,
                    showDenyButton: true,
                    denyButtonText: 'Batal',
                    confirmButtonText: 'Lanjutkan Save',
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveData();
                    } else if (result.isDenied) {
                        $('#cmdSave').removeAttr('disabled');
                    }
                });
            }
        },
        error: function(error) {
            console.log(error);
            $('#cmdSave').removeAttr('disabled');
        }
    });
}

    saveData = () => {
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $("#cmdSave").attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            let objQtySo= $('#article_row input[name="qtySo[]"]');
            let objQty= $('#article_row input[name="qtyInv[]"]');
            let objUom= $('#article_row span[name="uom[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row input[name='articleId[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleCode = $this.data("code");
                    let articleDesc = $this.data("desc");
                    let articleUom = $this.data("uom");
                    let articleSoCode = $this.data("so-code");
                    let poNumber = $this.data("po-number");
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qtySo=objQtySo.eq(i).val().replace(/,/gi, '') || 0;
                    
                    if ((articleCode!=='') && (qty> 0)){
                        articles.push({
                            "article_code":articleCode,
                            "qty":qty,
                            "uom":articleUom,
                            "so_number":articleSoCode,
                            "po_number":poNumber,
                            "qty_so":qtySo
                        });
                    }

                    if (parseInt(qty) > parseInt(qtySo)){
                        pesan +="Items "+ articleDesc +"-"+qty+"-"+qtySo+" QTY Delivery is higher than QTY SO<br>"; 
                        flag=1;
                    }
                    
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){

                let dnDate = $('#dnDate').val();
                let customer = $('#customer').val();
                let soNumber = $('#soNumber').val();
                let poNumber = $('#soNumber').find(":selected").data("po-number");
                let note = $('#note').val();
                let osNumber = $('#osNumber').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('delivery.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        dnDate:dnDate,
                        customer:customer,
                        soNumber:soNumber,
                        poNumber:poNumber,
                        note:note,
                        osNumber:osNumber

                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#dnNumber').attr('disabled','disabled');
                            $('#cmdSave').removeAttr('disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#dnNumber').val(data.dnNumber);
                            $('#statusText').val('NEW');
                            // FIX: response store() balikin field 'idKu' (id ter-encrypt),
                            // bukan 'id'. Sebelumnya di sini pakai data.id yang undefined,
                            // jadi jQuery .val(undefined) gak nge-set apa-apa dan #idDn
                            // tetap kosong -> tombol Print gagal manggil posting() -> hilang
                            // tanpa pesan sama sekali.
                            $('#idDn').val(data.idKu);
                            $('#dnNumber').attr('disabled','disabled');
                            $('#cmdSave').hide();
                            $('#cmdPrint').show();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                        $('#cmdSave').removeAttr('disabled');
                        Swal.fire('Error', 'Gagal menyimpan data, silakan coba lagi.', 'error');
                    }
                });

            }else{
                $('#cmdSave').removeAttr('disabled');
                $('#cmdPrint').hide();
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    }

    $("#cmdSave").click(function(){
        checkBeforeSave();
    });

    $("#cmdPrint").click(function(){
        /* Posting langsung print */

        // GUARD: pastikan #idDn sudah kebentuk (hasil dari Save yang sukses) sebelum
        // manggil delivery.posting. Kalau kosong, jangan lanjut request ke server
        // (yang tadinya bakal gagal di Crypt::decryptString('') dan bikin tombol
        // hilang tanpa pesan).
        let idDn = $('#idDn').val();
        if (!idDn) {
            Swal.fire('Error', 'ID dokumen belum ada. Silakan Save terlebih dahulu sebelum Print.', 'error');
            return;
        }

        $("#cmdPrint").attr('disabled','disabled');
        $("#cmdSave").attr('disabled','disabled');

        let objQty= $('input[name="qtyInv[]"]');
        let objUom= $('select[name="uom[]"]');       
        let dnNumber = $('#dnNumber').val();
        let dariNew = 'true';     
        $.ajax({
            type: "post",
            url: "{{ route('delivery.posting') }}",
            data: {
                dnNumber:dnNumber,
                id:idDn,
                dariNew:dariNew
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#dnNumber').attr('disabled','disabled');
                    // FIX: dulu cmdPrint tetap hidden kalau posting gagal (status 0),
                    // padahal cmdPrint sudah di-hide/disable di awal klik. Sekarang
                    // dikembalikan supaya user bisa coba Print lagi.
                    $('#cmdSave').removeAttr('disabled').show();
                    $('#cmdPrint').removeAttr('disabled').show();

                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#cmdSave').hide();
                    $('#statusText').text('POSTED');
                    $('#cmdPrint').hide();
                    $('#dnNumber').attr('disabled','disabled');
                    $('#soNumber').attr('disabled','disabled');
                    $('#customer').attr('disabled','disabled');
                    $('#dnDate').attr('disabled','disabled');
                    objQty.attr('disabled','disabled');
                    objUom.attr('disabled','disabled');

                    let id = data.idKu;
                    let url = "{{ route('delivery.print', ['id'=>':id']) }}";
                    url = url.replace('%3Aid', id);
                    window.open(url, '_blank');
                }
            },
            error: function(error) {
                console.log(error);
                // FIX: dulu di sini cuma console.log tanpa mengembalikan tombol/kasih
                // pesan, jadi kalau request gagal (mis. id invalid, expired, error
                // server), tombol Save & Print SAMA-SAMA hilang tanpa penjelasan.
                $("#cmdPrint").removeAttr('disabled').show();
                $("#cmdSave").removeAttr('disabled');
                Swal.fire('Error', 'Gagal melakukan posting/print, silakan coba lagi.', 'error');
            }
        });
    });
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection