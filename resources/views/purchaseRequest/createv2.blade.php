@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: New</h4>
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
                                <div class="form-group col-md-4">
                                    <label for="prNumber">Request Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="prNumber" name="prNumber" class="form-control disabled-el"  disabled />
                                </div>
                                 <div class="form-group col-md-4">
                                    <label class="form-label" for="poType">PO Type*</label>
                                    <select class="select2 form-control" id="poType" name="poType" required>
                                        <option value="std">Standard</option>
                                        {{-- <option value="sub">Subcontracting</option> --}}
                                        <option value="tso">Target SO</option>
                                        {{--<option value="rm">Raw Material</option> --}}
                                    </select>
                                </div>
                                
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="dept">Department*</label>
                                    <select class="select2 form-control" id="dept" name="dept" required>
                                        <option value=""></option>
                                        @foreach($depts as $val)
                                            <option value="{{$val->code}}" >{{$val->code}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="orderDate">Request Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" placeholder="DD-MM-YYYY"/>
                                </div>
                                
                               
                            </div>
                            <div class="form-row" id="tsoBox">
                             <div class="form-group col-md-2">
                               <label for="stockDate">Stock Date*</label>
                                <input type="text" id="stockDate" name="stockDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                               </div>
                             <div class="form-group col-md-2">
                              <label class="form-label" for="purchaseType">Purchase Type*</label>
                               <select class="select2 form-control" id="purchaseType" name="purchaseType">
                                <option value="purchase">Purchase</option>
                                <option value="np">Non Purchase</option>
                                </select>
                              </div>
    <div class="form-group col-md-4">
        <label class="form-label" for="tsoCode">Target SO Number</label>
        <select class="select2 form-control" id="tsoCode" name="tsoCode">
        </select>
    </div>
</div>
                            <div class="form-row" id="suppBox">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="suppCode">Supplier*</label>
                                    <select class="select2 form-control" id="suppCode" name="suppCode">
                                        <option value=""></option>
                                        @foreach(($suppliers ?? []) as $s)
                                            <option value="{{ $s->kode }}">{{ $s->kode }} - {{ $s->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
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
                    <h4 class="card-title">Article Detail</h4>
                </div>
                <div class="card-body" >
                    <hr>
                    <label for="upload_excel" class="d-block">Upload Mass Article</label>
                      <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-row">
                                <div class="col-lg-3 col-md-12">
                                    <div class="form-group">
                                        <div>
                                            <input type="file" class="custom-file-input" name="file" id="file" required/>
                                            <label class="custom-file-label" for="file" id="fileLabel">Choose file</label>
                                        </div>
                                        
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <a href="{{ route('transferOut.export.excel') }}" class="btn btn-light"><i class="fa fa-download"></i> Downlod Template</a>
                                    <button type="button" class="btn btn-primary" id="uploadExcel">
                                        <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none" >Upload Excel</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                        <hr style="margin-top:0px"">
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
                        <h6>Line:<span id="records"></span></h6>
                    </div>
                    <hr>
                    <div class="mt-75">
                        <a href="{{ route('purchaseRequests.index') }}" class="btn btn-light">Back</a>
                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
</style>
@endsection
@section('scripts')
@include('purchaseRequest.addArticlev2')
<script type="text/javascript">
    // Cegah bug "Step attribute on input type date is not supported" pada jquery.validate
if (window.jQuery && $.validator) {
    $.validator.methods.step = function () { return true; };
}
    let currentDate = todayDate('dd-mm-yyyy');
   $(document).ready(function(){           


    validateFormToast("frmAdd");
    $('#orderDate').val("{{ $currentDate }}");
    isiArticle('article_pr');
    objTsoBox.hide();
    suppBox.hide();
    add_new_row();
});
    
    if (orderDate.length) {
        orderDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }

    if (stockDate.length) {
        stockDate.flatpickr({
            dateFormat: "d-m-Y",
            // defaultDate:currentDate
        });
    }   

    objPoType.change(function(e){
    let potype=$(this).val();
    $('#article_row').empty();
    oDept.val("").trigger("change");
    cloneCount=0;
    objTsoBox.hide();
    suppBox.hide();
    suppCode.val("").trigger('change');

    if (potype ==='tso'){
        objTsoBox.show();
        stockDate.attr('required','required');   // wajib hanya untuk TSO
        addNewRow.attr('disabled','disabled');
        $('#purchaseType').val("purchase").trigger('change'); // ini akan otomatis refresh tsoCode lewat handler purchaseType.change()
    }else{
        stockDate.val("").removeAttr('required');  // lepas wajib untuk std/rm
        add_new_row();
        addNewRow.removeAttr('disabled');
    }
});

$('#purchaseType').change(function(){
    if ($('#poType').val() !== 'tso') return;
    $('#article_row').empty();
    cloneCount = 0;
    objTsoCode.val('').trigger('change');
    changeSelect({
        dependent:'tso_list',
        obj:'tsoCode',
        url:"{{ route('dynamic.dependent') }}",
        extra:{
             purchaseType: $('#purchaseType').val()
        }
    });
});

    // Saat supplier dipilih (Non Purchase) -> reload daftar article terfilter
    suppCode.change(function(){
        if ($('#poType').val() !== 'np') return;
        $('#article_row').empty();
        cloneCount=0;
        if ($(this).val()){
            addNewRow.removeAttr('disabled');
            add_new_row();
        }else{
            addNewRow.attr('disabled','disabled');
        }
    });

    // Helper format: "ALTERNATIVE - DESC" (fallback ke code kalau kosong)
function fmtArtLine(item){
    let alt  = item.alternative || item.missing_code || item.fg_code || '-';
    let desc = item.article_desc || '';
    return `<li><b>${alt}</b>${desc ? ' - ' + desc : ''}</li>`;
}

    objTsoCode.change(function(e){
    if (!$("#frmAdd")[0].checkValidity() && $(this).val()){
        $("#frmAdd").submit();
        $(this).val("").trigger('change');
    }else{
        let tsoCode = $(this).val();
        let dStockDate = stockDate.val();

        if (tsoCode){
            if (dStockDate){
                loadTsoArticle(tsoCode, dStockDate, false); // ✅ panggil tanpa konfirmasi dulu
            }else{
                objTsoCode.val("");
                stockDate.focus();
                Swal.fire('Warning !!','Tanggal stock belum diisi','warning');
            }
        }else{
            $('#article_row').empty();
            cloneCount=0;
        }
    }
});

// ✅ Fungsi load yang bisa dipanggil ulang dengan flag konfirmasi
function loadTsoArticle(tsoCode, dStockDate, confirmExclude){
    $(".loading-spinner-container").addClass("-show");
    $.ajax({
        type: "GET",
        url: "{{ route('purchaseRequest.article.tso2') }}",
        data: {
            tsoCode: tsoCode,
            stockDate: dStockDate,
            purchaseType: $('#purchaseType').val(),
            confirmExclude: confirmExclude ? '1' : '0'   // ✅ kirim flag
        },
        dataType: "json",
        success: function(res){
            $(".loading-spinner-container").removeClass("-show");

            // BOM belum approve
if (res.status === 'bom_not_approved'){
    let listFg = res.unapproved_fg.map(fmtArtLine).join('');
    Swal.fire({
        title: 'BOM Belum Full Approve',
        html: `FG berikut BOM-nya belum Full Approve:`
            + `<ul style="text-align:left;margin-top:8px">${listFg}</ul>`
            + `Jika dilanjutkan, FG tersebut akan <b>dikecualikan</b> dari perhitungan.<br>Lanjutkan?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed){
            loadTsoArticle(tsoCode, dStockDate, true);
        }else{
            objTsoCode.val("").trigger('change');
        }
    });
    return;
}

// Missing article
if (res.status === 'missing_article'){
    let listArticle = res.missing_articles.map(fmtArtLine).join('');
    Swal.fire({
        title: 'Article Tidak Ditemukan!',
        html: `Article berikut tidak ada di master data:`
            + `<ul style="text-align:left;margin-top:8px">${listArticle}</ul>`
            + `Silakan tambahkan article tersebut terlebih dahulu.`,
        icon: 'error',
        confirmButtonText: 'OK'
    });
    objTsoCode.val("").trigger('change');
    return;
}
            // Case 3: OK -> render baris
            let data = res.data ?? [];
            if (data.length){
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
            }else{
                Swal.fire('Info','Tidak ada article yang ditemukan untuk Target SO dan Purchase Type ini','info');
            }
        },
        error: function(error){
            $(".loading-spinner-container").removeClass("-show");
            Swal.fire('Error..', error, 'error');
        }
    });
}
    
    $("#cmdSave").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('#cmdSave').attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            let objQty = $('#article_row input[name="qty_order[]"]');
            let objNote = $('#article_row input[name="note[]"]');
            let objUom = $('#article_row select[name="uom[]"]'); 
            let objHitung = $('#article_row input[name="qtyHitung[]"]'); 
            let objStock = $('#article_row input[name="qtyStock[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";
            let poType = $('#poType').val();
            let suppHeader = $('#suppCode').val();   // supplier dari header (Non Purchase)

            // Supplier wajib dipilih untuk Non Purchase
            if (poType === 'np' && !suppHeader){
                pesan += "Supplier wajib dipilih <br>";
                flag = 1;
            }

            $("#article_row select[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article=$this.find(":selected").data("detail").split('|');
                    let articleName=$this.select2('data')[0].text;
                    let plu=$this.val();
                    let supp=article[2];
                    // Untuk Non Purchase, supplier diambil dari header
                    if (poType === 'np'){
                        supp = suppHeader;
                    }
                    let uom=objUom.eq(i).val();
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let note=objNote.eq(i).val();
                    let qtyHitung=objHitung.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyStock=objStock.eq(i).val().replace(/,/gi, '') || 0;
                            
                    let obj = $.grep(articles, function(obj){
                        return obj.article_code === plu;
                    })[0];
                    
                    if(obj) {
                        pesan +="Article "+articleName+" entered more than once !! <br>"; 
                        flag=1;
                    } else {
                        if ((plu!=='') && (qty> 0)){
                            articles.push({
                                "article_code":plu,
                                "qty":qty,
                                "uom":uom,
                                "supp":supp,
                                "note":note,
                                "qty_hitung":qtyHitung,
                                "qty_stock":qtyStock,
                            });
                        }
                    } 

                    if ( qty == 0 ){
                        pesan +=`QTY of items ${articleName} cannot be 0 <br>`; 
                        flag=1;
                    }

                    if ( (poType=='tso') && (qty > qtyHitung) ){
                        pesan +=`QTY of items ${articleName} tidak boleh melebihi qty hasil hitung ${qtyHitung} <br>`; 
                        flag=1;
                    }
                }
            });            

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let dOrderDate = $('#orderDate').val();
                let dStockDate = $('#stockDate').val();
                let dept = $('#dept').val();
                
                let tsoCode = $('#tsoCode').val();
                let note = $('#note').val();
                let suppCodeVal = $('#suppCode').val();
                
                $.ajax({
                    type: "post",
                    url: "{{ route('purchaseRequest.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        orderDate:dOrderDate,
                        poType:poType,
                        dept:dept,
                        note:note,
                        tsoCode:tsoCode,
                        stockDate:dStockDate,
                        suppCode:suppCodeVal,
                        purchaseType: $('#purchaseType').val()   // <-- ambil value-nya, bukan referensi elemennya
                    },
                    dataType: "json",
                success: function(data) {
                    if (data.status == 0) {

                     if (Array.isArray(data.message)) {
                         for(let i = 0; i < data.message.length; i++) {
                            show_msg(data.title, data.message[i], data.alert);
                         }
                     } else {
                         show_msg(data.title, data.message, data.alert);
                     }

                 $('#cmdSave').removeAttr('disabled');

                } else {

            Swal.fire({
                title: data.title,
                text: data.message,
                icon: 'success',
                confirmButtonText: 'OK'
             }).then((result) => {
                 if (result.isConfirmed) {
                    window.location.href = "{{ route('purchaseRequests.index') }}";
                 }
                });

                }
            },
                    error: function(error) {
                        Swal.fire('Error..',error,'error');
                    }
                });
            }else{
                $('#cmdSave').removeAttr('disabled');
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    });
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection