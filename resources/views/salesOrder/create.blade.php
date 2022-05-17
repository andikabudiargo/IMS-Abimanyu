@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">@yield('title')</h4>
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
                                    <label for="orderNum">Order Number</label><small class="text-muted">  automatic</small>
                                    <input type="text" id="orderNum" name="orderNum" class="form-control disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-basic" placeholder="DD-MM-YYYY" required disabled/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="salesman">Salesman*</label>
                                    <select class="select2 form-control" id="salesman" name="salesman" required>
                                        <option value="">Choose Salesman</option>
                                        @foreach($employees as $val)
                                        <option value="{{$val->employee_id}}">{{$val->employee_id}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row"> 
                                <div class="form-group col-md-2">
                                    <label for="type">Type*</label>
                                    <select class="select2 form-control" id="type" name="type" required>
                                        @foreach($types as $val)
                                        <option value="{{$val}}">{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        @foreach($currency as $val)
                                        <option value="{{$val}}">{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id="ppn" name="ppn" value="{{ $attribute['ppn'] }}" maxlength="2" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="pph23">PPH23</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "pph23" name="pph23" value="{{ $attribute['pph23'] }}" maxlength="2" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="poNumber">PO Number*</label>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control text-uppercase" maxlength="40" autofocus required />
                                </div>
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="cust">Customer*</label>
                                    <select class="select2 form-control" id="cust" name="cust" required>
                                        <option value="">Choose Customer</option>
                                        @foreach($custs as $val)
                                            <option value="{{$val->kode}}|{{$val->inisial}}" >{{$val->kode}} - {{$val->nama}}</option>
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
                    <h4 class="card-title">Article detail</h4>
                </div>
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('salesOrder.headerColumnOne')
                            <div class="" id="article_row">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block ">Add Article</span>
                        </button>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
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
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-4 col-form-label titik-dua tanpa-padding">Bruto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalAmount" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-4 col-form-label titik-dua tanpa-padding">PPN <span id="nilaiPPN"></span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPN" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPH" class="col-sm-4 col-form-label titik-dua tanpa-padding">PPH23 <span id="nilaiPPH23"></span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPH" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalNetto" class="col-sm-4 col-form-label titik-dua tanpa-padding">Netto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalNetto" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <br/>
                    <div class="form-row">
                        <div class="col-12">
                            {{-- <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button> --}}
                            <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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

    label.tanpa-padding{
        padding-top: 0px;
        padding-bottom: 0px;
    }

    input.tanpa-padding{
        padding: 0;
    }


    @media screen 
    and (min-device-width: 1200px) 
    and (max-device-width: 1600px) 
    and (-webkit-min-device-pixel-ratio: 1) { 
        .lebar-list-item{
            width:150%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px)
    {
        .lebar-list-item{
            width:200%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

</style>
@endsection
@section('scripts')
@include('salesOrder.addArticle')
<script type="text/javascript">    
    let currentDate = todayDate('dd-mm-yyyy');
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        $('#orderDate').val(currentDate);
    });
    
    $("#cmdSave").click(function(){    
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let objQty= $('#article_row input[name="qty_order[]"]');
            let objPrice= $('#article_row input[name="price[]"]');
            let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
            let objUom= $('#article_row span[name="uom[]"]'); 
            let objGroup= $('#article_row input[name="group[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row select[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article=$this.val().split("|");
                    let articleName=$this.select2('data')[0].text;
                    let plu=article[0];
                    let inisial = articleName.substring(2,5); 
                    let qty=objQty.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                    let price=objPrice.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                    let priceJasa=objPriceJasa.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                    let uom=objUom.eq(i).text();
                    let group=objGroup.eq(i).val(); 
                    let cust=$('#cust').val().split("|");
                    let custName = $('#cust').select2('data')[0].text;
                    let customer=cust[1];
                
                    //es6
                    // let obj = ingredient.find(obj => obj.plu == plu);

                    //jquery
                    //cek apakah article ada yang double input ato ngk
                    let obj = $.grep(articles, function(obj){
                        return obj.article_code === plu;
                    })[0];
                    
                    if(obj) {
                        pesan +="Article "+plu+" entered more than once !! <br>"; 
                        flag=1;
                    } else {
                        if ((plu!=='') && (qty> 0)){
                            articles.push({
                                "article_code":plu,
                                "qty":qty,
                                "uom":uom,
                                "price":price,
                                "price_service":priceJasa,
                                "group":group
                            });
                        }
                    } 
                
                    if (qty == 0){
                        pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                        flag=1;
                    }

                    if (inisial !== customer){
                        
                        pesan +="This article "+ articleName +" does not belong to the customer "+custName +" <br>"; 
                        flag=1;
                    }
                
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let orderDate = $('#orderDate').val();
                let currency = $('#currency').val();
                let type = $('#type').val();
                let poNumber = $('#poNumber').val();
                let cust = $('#cust').val().split("|");
                let customer = cust[0];
                let salesman = $('#salesman').val();
                let ppn = $('#ppn').val().replace(/[^0-9]/gi, '') || 0;
                let pph23 = $('#pph23').val().replace(/[^0-9]/gi, '') || 0;
                let totalPpn = $('#totalPPN').val().replace(/[^0-9]/gi, '') || 0;
                let totalPph = $('#totalPPH').val().replace(/[^0-9]/gi, '') || 0;
                let note = $('#note').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('salesOrder.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        orderDate:orderDate,
                        currency:currency,
                        type:type,
                        poNumber:poNumber,
                        customer:customer,
                        salesman:salesman,
                        ppn:ppn,
                        pph23:pph23,
                        totalPpn:totalPpn,
                        totalPph:totalPph,
                        note:note
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }

                            $('#poNumber').focus().select();
                            $('#orderNum').attr('disabled','disabled');

                        }else{
                            show_msg(data.title, data.message, data.alert);

                            $('#orderNum').val(data.soNumber);
                            $('#orderNum').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
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
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection