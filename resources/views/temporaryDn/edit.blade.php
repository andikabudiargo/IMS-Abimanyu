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
                    <h4 class="card-title">Status: {{ $status }}</h4>
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
                                    <label for="tDnNumber">Temporary DN Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="tDnNumber" name="tDnNumber" class="form-control disabled-el" value="{{ $header->tdn_number }}" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="deliveryDate">Delivery Date*</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->delivery_date }}" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="cust">Customer*</label>
                                    <select class="select2 form-control" id="cust" name="cust" required>
                                        <option value="">Choose Customer</option>
                                        @foreach($customers as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->customer_id ? "selected" : ""}}>{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                               <div class="form-group col-md-3">
    <label class="form-label" for="armada">Armada*</label>
    <select class="select2 form-control" id="armada" name="armada" required>
        <option value="">Choose Armada</option>
        <option value="CDD PAGI" {{$header->armada == "CDD PAGI" ? "selected" : ""}}>CDD PAGI</option>
        <option value="CDD MALAM" {{$header->armada == "CDD MALAM" ? "selected" : ""}}>CDD MALAM</option>
        <option value="VJB 1 PAGI" {{$header->armada == "VJB 1 PAGI" ? "selected" : ""}}>VJB 1 PAGI</option>
        <option value="VJB 1 MALAM" {{$header->armada == "VJB 1 MALAM" ? "selected" : ""}}>VJB 1 MALAM</option>
        <option value="VJB 2 PAGI" {{$header->armada == "VJB 2 PAGI" ? "selected" : ""}}>VJB 2 PAGI</option>
        <option value="VJB 2 MALAM" {{$header->armada == "VJB 2 MALAM" ? "selected" : ""}}>VJB 2 MALAM</option>
        <option value="GRANDMAX PAGI" {{$header->armada == "GRANDMAX PAGI" ? "selected" : ""}}>GRANDMAX PAGI</option>
        <option value="GRANDMAX MALAM" {{$header->armada == "GRANDMAX MALAM" ? "selected" : ""}}>GRANDMAX MALAM</option>
        <option value="LUXIO" {{$header->armada == "LUXIO" ? "selected" : ""}}>LUXIO</option>
        <option value="MOBIL SEWA PAGI" {{$header->armada == "MOBIL SEWA PAGI" ? "selected" : ""}}>MOBIL SEWA PAGI</option>
        <option value="PICKUP" {{$header->armada == "PICKUP" ? "selected" : ""}}>PICKUP</option>
    </select>
</div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label for="perihal">Hal</label>
                                    <input type="text" id="perihal" name="perihal" class="form-control" value="{{ $header->perihal }}"/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
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
                    <div style="padding-right:10px">
                        @include("temporaryDn.headerColumn")
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
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('suratJalanSementara.index') }}" class="btn btn-light">Back</a>
                                    <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" >Update</button>
                                </div>
                            </div>
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
</style>
@endsection
@section('scripts')
@include('temporaryDn.addArticle')
<script type="text/javascript">
    let timerId="";
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        dataArticle='{!! $articles !!}';
        setTimeout(function () {
            $(".loading-spinner-container").addClass("-show");
        }, 500);
        timerId= setInterval(() => checkVariable(), 1000);
    });

    let detail = {!! $details !!};
    function checkVariable() {
        if (dataArticle.length > 0) {
            clearInterval(timerId);
            isiData(detail);
        }
    }

    isiData = (data) =>{
        if (data){
            for(let i=0;i<data.length;i++){
                article = data[i].article_code;
                qty = data[i].qty*1;
                uom =  data[i].uom;
                add_new_row_edit(article,qty,uom);
                if (i==(data.length-1)){
                    $(".loading-spinner-container").removeClass("-show");
                }
            }
            recordCount();
        }
    }

    orderDate = $('#orderDate');
    if (orderDate.length) {
        orderDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }
    
    $("#cmdUpdate").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let objQty = $('#article_row input[name="qtyOrder[]"]');
            let objUom = $('#article_row span[name="uom[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row select[name='articleCode[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleCode=$this.val();
                    let articleName=$this.select2('data')[0].text;
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let uom=objUom.eq(i).text();

                    let obj = $.grep(articles, function(obj){
                        return obj.article_code === articleCode;
                    })[0];
                    
                    if(obj) {
                        pesan +="Article "+articleName+" entered more than once !! <br>"; 
                        flag=1;
                    } else {
                        if ((articleCode!=='') && (qty> 0)){
                            articles.push({
                                "article_code":articleCode,
                                "qty":qty,
                                "uom":uom
                            });
                        }
                    } 
                    if ( qty == 0 ){
                        pesan +=`QTY of items ${articleName} cannot be 0 <br>`; 
                        flag=1;
                    }
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

           if (flag==0){
    let tDnNumber = $('#tDnNumber').val();
    let deliveryDate = $('#deliveryDate').val();
    let customerId = $('#cust').val();
    let perihal = $('#perihal').val();
    let note = $('#note').val();
    let armada = $('#armada').val();

    $.ajax({
        type: "post",
        url: "{{ route('suratJalanSementara.update') }}",
        data: {
            articles:JSON.stringify(articles),
            deliveryDate:deliveryDate,
            customerId:customerId,
            perihal:perihal,
            note:note,
            tDnNumber:tDnNumber,
            armada:armada
        },
        dataType: "json",
        success: function(data) {
                     if (data.status == 0 ){
    let msgs = Array.isArray(data.message) ? data.message : [data.message];
    for (let i = 0; i < msgs.length; i++) {
        // msgs[i] bisa berupa array (dari validator) atau string
        let m = Array.isArray(msgs[i]) ? msgs[i].join(', ') : msgs[i];
        show_msg(data.title, m, data.alert);
    }
    $('#prNumber').attr('disabled','disabled');
} else {
    show_msg(data.title, data.message, data.alert);
    $('#prNumber').attr('disabled','disabled');
    $('.disabled-el').attr('disabled','disabled');
}
                        
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            }else{
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    });
        
    function changeselect(dependent,obj) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val('').trigger('change');
        }
      })
    }
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection