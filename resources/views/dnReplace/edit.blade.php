@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $status }}</span></h4>
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
                                <div class="form-group col-md-4">
                                    <label for="replaceNumber">Replace Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="replaceNumber" name="replaceNumber" class="form-control text-hitam disabled-el" value="{{ $header->replace_number }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="replaceDate">Replace Date*</label>
                                    <input type="text" id="replaceDate" name="replaceDate" class="form-control" value="{{ $header->replace_date }}" placeholder="DD-MM-YYYY" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="customer">Customer*</label> <small class="text-muted">tidak dapat diubah saat edit</small>
                                    <select class="select2 form-control" id="customer" name="customer" required disabled>
                                        <option value=""></option>
                                        @foreach($custs as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->customer_id ? "selected" : ""}}>{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="dnReturnNumber">DN Return Number*</label>
                                    <select class="select2 form-control" id="dnReturnNumber" name="dnReturnNumber" required>
                                        {!! $listReturn !!}
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="dnNumber">Customer DN number</label>
                                    <input type="text" id="dnNumber" name="dnNumber" value="{{ $header->dn_number }}" class="form-control" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
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
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('dnReplace.headerColumn')
                            <div class="" id="articleRow" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin">
                                <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4 ">
                            <div class="form-group row mb-04">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua">Total Qty</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            <div class="form-row">
                                <div class="col-12">
                                    <a href="{{ route('dnReplace.index') }}" class="btn btn-light">Back</a>
                                    <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('dnReplace.addArticle')
@endsection
@section('styles')
@endsection
@section('scripts')
<script type="text/javascript">
    const approveBtn = $('#cmdApprove');
    dariEdit = 'true';
    
    
    $(document).ready(function(){   
        validateFormToast("frmAdd");
        let href;
                    
        $(document).on('click', '#deleteButton', function(event) {
            event.preventDefault();
            href = $(this).data('href');
            $('#modalConfirmationCancel').attr("action", href);
        });


        let detail = {!!  $detail !!};
        for(let i=0;i<detail.length;i++){
            let article = detail[i].article_code;
            let articleCode = detail[i].article_alternative_code;
            let articleDesc = detail[i].article_desc;
            let qtyReturn = detail[i].qty_return*1;
            let totQtyReturn = detail[i].tot_qty_return*1;
            let uom = detail[i].uom;
            let qty = detail[i].qty*1;
            let returnNumber = detail[i].return_number;
            addNewRow(article,articleCode,articleDesc,qtyReturn,uom,qty,returnNumber,totQtyReturn);
        }

    });

    function reloadPage(){
        window.location.reload();
    }

    $("#cmdNew").click(function(){
        reloadPage();
    });

    // Catatan: Customer sengaja dikunci (disabled) di halaman Edit -- lihat
    // atribut `disabled` di <select id="customer">. Field select yang disabled
    // tidak memicu event 'change' sama sekali, jadi user tidak akan bisa
    // memicu reset baris artikel lewat ganti customer di halaman ini.
    // Kalau nanti field ini butuh dibuka lagi, INGAT: searchDn() di
    // addArticle.blade.php akan mengosongkan #articleRow begitu dipanggil --
    // pertimbangkan tambah konfirmasi dulu sebelum mengizinkan itu terjadi
    // di halaman Edit (beda dengan Create yang formnya memang masih kosong).
    $('#customer').change(function(){
        let value= $(this).val();
        searchDn('dnReturnNumber',value);
    });

    $('#dnReturnNumber').change(function(){
        $("#dnNumber").val('');
        let value= $(this).val();
        let dnNumber=$(this).find(":selected").data("dn");
        $("#dnNumber").val(dnNumber);
        searchDnDet(value,'false');
    })   

    $("#cmdUpdate").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            let $btnUpdate = $("#cmdUpdate");
            let originalHtml = $btnUpdate.html();

            $btnUpdate.attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let dnReturnNumber = $('#dnReturnNumber').val();
            let objQtyReturn= $('input[name="qtyReturn[]"]');
            let objQty= $('input[name="qtyReplace[]"]');
            let objUom= $('select[name="uom[]"]');           
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#articleRow input[name='articleCode[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleCode = $this.data("code");
                    let articleUom = $this.data("uom");
                    let returnNumber = $this.data("returnNumber");
                    let article=$this.val().split("|");
                    let plu=article[0];
                    let articleName=article[1];
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyUom=objUom.eq(i).val() || articleUom;
                    let qtyReturn=objQtyReturn.eq(i).val().replace(/,/gi, '') || 0;

                    if ((parseFloat(qty) > parseFloat(qtyReturn)) && (parseFloat(qty) != 0)){
                        pesan +=`Articles : ${article} QTY Replace > QTY Return <br>`; 
                        flag=1;
                    }

                    articles.push({
                        "return_number":dnReturnNumber,
                        "article_code":articleCode,
                        "qty_return":qtyReturn,
                        "qty":qty,
                        "uom":qtyUom,
                    });
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if ( $("#totalQTY").val() == 0 ){
                pesan +="Total Qty cannot be 0 <br>"; 
                flag=1;
            }

            if (flag==0){
                // Animasi saving di tombol
                $btnUpdate.html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Saving...');

                let replaceNumber = $('#replaceNumber').val()||0;
                let replaceDate = $('#replaceDate').val();
                let customer = $('#customer').val();
                let note = $('#note').val();
            
                $.ajax({
                    type: "post",
                    url: "{{ route('dnReplace.update') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        replaceNumber:replaceNumber,
                        replaceDate:replaceDate,
                        returnNumber:dnReturnNumber,
                        customer:customer,
                        note:note,
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $btnUpdate.html(originalHtml).removeAttr('disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#statusText').text(data.statusReplace);
                            $('#replaceNumber').val(data.replaceNumber);

                            // Field lain juga dikunci setelah tersimpan, konsisten
                            // dengan pola di halaman Create -- mencegah user
                            // mengira masih bisa lanjut edit padahal sudah tersubmit.
                            $('#dnReturnNumber').attr('disabled','disabled');
                            $('#replaceDate').attr('disabled','disabled');
                            $('.input-qty').attr('disabled','disabled');
                            $btnUpdate.html(originalHtml).attr('disabled','disabled');

                            // FIX: sebelumnya TIDAK ADA reload/refresh sama sekali
                            // setelah update sukses -- halaman diam saja walau data
                            // di server sudah berubah (termasuk status OPEN/CLOSED).
                            // Reload dikasih jeda dulu supaya toast sukses sempat
                            // terlihat oleh user sebelum halaman refresh.
                            setTimeout(reloadPage, 1200);
                        }
                    },
                    error: function(xhr) {
                        console.log(xhr);
                        // FIX: sebelumnya cuma console.log(), tombol Update tetap
                        // disabled selamanya dan user tidak dapat notifikasi apapun
                        // kalau request gagal di level HTTP.
                        $btnUpdate.html(originalHtml).removeAttr('disabled');
                        Swal.fire('Error','Gagal menyimpan perubahan, silakan coba lagi.','error');
                    }
                });
            }else{
                $btnUpdate.html(originalHtml).removeAttr('disabled');
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