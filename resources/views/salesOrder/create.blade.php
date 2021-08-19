@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="row">
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
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="orderNum">Order Number</label><small class="text-muted">  automatic</small>
                                        <input type="text" id="orderNum" name="orderNum" class="form-control disabled-el"  disabled />
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="orderDate">Order Date*</label>
                                        <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-basic" placeholder="DD-MM-YYYY" required/>
                                    </div>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="salesman">Salesman*</label>
                                    <select class="select2 form-control" id="salesman" name="salesman" required>
                                        <option label=""></option>
                                        @foreach($employees as $val)
                                        <option value="{{$val->employee_id}}">{{$val->employee_id}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="type">Type*</label>
                                    <select class="select2 form-control" id="type" name="type" required>
                                        <option label=""></option>
                                        @foreach($types as $val)
                                        <option value="{{$val}}">{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        {{-- <option label=""></option> --}}
                                        @foreach($currency as $val)
                                        <option value="{{$val}}">{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="poNumber">PO Number*</label>
                                        <input type="text" id="poNumber" name="poNumber" class="form-control text-uppercase" maxlength="40" autofocus required />
                                    </div>
                                </div>
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="cust">Customer*</label>
                                    <select class="select2 form-control" id="cust" name="cust" required>
                                        <option label=""></option>
                                        @foreach($custs as $val)
                                            <option value="{{$val->kode}}|{{$val->inisial}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "ppn" name="ppn" value="10" maxlength="2" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-10">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
                    <div>
                        <table class="" style="width:98%;table-layout: fixed;">
                            <tbody>
                                <tr>
                                    <td class="isian-satu" style="width: 25%">
                                        <label>Article Code</label>
                                    </td>
                                    <td class="isian" style="width: 15%;">
                                        <label>Group</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Stock</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>QTY</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>UOM</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Price</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Total</label>
                                    </td>
                                    <td class="isian text-center" style="width: 5%">
                                        <label>-</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>      
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="0">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
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
                                <label for="totalAmount" class="col-sm-3 col-form-label titik-dua tanpa-padding">Bruto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalAmount" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-3 col-form-label titik-dua tanpa-padding">PPN <span id="nilaiPPN"></span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPN" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPH" class="col-sm-3 col-form-label titik-dua tanpa-padding">PPH <span>23</span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPH" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalNetto" class="col-sm-3 col-form-label titik-dua tanpa-padding">Netto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalNetto" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('salesOrder.addArticle')
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
    

</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        validateForm('frmAdd');
        $('#orderDate').val(currentDate);
    });
    
    $('#cust').on('change', function() {
        let cust = $(this).val().split("|");
        let customer = cust[0];
        // changeselect('article_id',customer);
    })

    $('#ppn').on('keyup', function() {
        hitungGrandTotal();
    });
    
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel").click(function(){
        reloadPage();
    });

    $("#cmdNew").click(function(){
        reloadPage();
    });

    $("#cmdSave").click(function(){     
        $('.disabled-el').removeAttr('disabled');
        // ambil semua data article
        let objQty= $('input[name="qty_order[]"]');
        let objPrice= $('input[name="price[]"]');
        let objUom= $('span[name="uom[]"]'); 
        let objGroup= $('input[name="group[]"]'); 
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
            let totalPph = $('#totalPPH').val().replace(/[^0-9]/gi, '') || 0;
            let totalPpn = $('#totalPPN').val().replace(/[^0-9]/gi, '') || 0;
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
                    totalPph:totalPph,
                    totalPpn:totalPpn,
                    note:note
                },
                dataType: "json",
                success: function(data) {
                    if (data.status == 0 ){
                        let message="";
                        for(let i = 0; i < data.message.length; i++) {
                            message += "-"+data.message[i]+"<br>";                           
                        }
                        $("#alert-message-success").addClass(data.alert);
                        $("#alert-message-success .alert-body").html(message);
                        $("#alert-message-success").show();
                        $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                            $("#alert-message-success").slideUp(500);
                        });
                        $('#poNumber').focus().select();
                        $('#orderNum').attr('disabled','disabled');


                    }else{
                        $("#alert-message-success").addClass(data.alert);
                        $("#alert-message-success .alert-body").html(data.message);
                        $("#alert-message-success").show();
                        $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                            $("#alert-message-success").slideUp(500);
                        });
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
    
    });

    let cloneCount=1;
    function add_new_row() {
        let customer = $('#cust');
        let cust = customer.val().split("|");
        if (customer.val()){            
            $("#article_row").append($("#new_row").clone().html());
            cloneCount++;
            $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
            $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
            changeselect('article_id','article_id'+ cloneCount,cust[0],'FG');
            $("#article_id"+cloneCount).select2();
            tombolPanah('qty_order');
            tombolPanah('price');
            activate_angka();
            mask_thousand();
            splitArticle();
            hitungTotal();
            hitungGrandTotal();
        }else{
            Swal.fire({
                title: 'Warning',
                text: "Choose customer",
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    customer.select2('open');
                }
            })
        }
    };

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objGroup= $('#article_row input[name="group[]"]');
        let objStock= $('#article_row input[name="qty_stock[]"]');
        let objUom= $('#article_row span[name="uom[]"]'); 
        let objQty= $('#article_row input[name="qty_order[]"]');
        objArticle.change(function(e){        
            let objIndex = objArticle.index(this);
            let detail = objArticle.eq(objIndex).val();
            let arrDetail = detail.split("|");
            objGroup.eq(objIndex).val(arrDetail[1]);
            objStock.eq(objIndex).val(arrDetail[2]||0);
            objUom.eq(objIndex).text(arrDetail[3]);
            objArticle.eq(objIndex).select2('open'); //belum bisa jalan
            if (detail){
                setTimeout(() => {
                    objQty.eq(objIndex).focus().select();
                }, 5);
            }
		});
    }

    function hitungTotal(){
        let objQty= $('#article_row input[name="qty_order[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objTotal= $('#article_row span[name="totalLine[]"]');
        
        objQty.keyup(function() {
            let indexnya= objQty.index(this);
            let qty = objQty.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/[^0-9]/gi, '') ||0;
            let total = qty*price;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            hitungGrandTotal();
        });    

        objPrice.keyup(function() {
            let indexnya= objPrice.index(this);
            let qty = objQty.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/[^0-9]/gi, '')||0;
            let total = qty*price;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            hitungGrandTotal();
        });    
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objQtyTiw= $('#article_row input[name="qty_order[]"]');
        let objQTY= $('#article_row input[name="qty_order[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let ppn= $('#ppn').val() ||0;
        let totalQty= 0;
        let totalAmount=0

        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            let price = parseInt(objPrice.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            totalQty+= qty;
            totalAmount+= qty*price;
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#nilaiPPN").text(ppn+"%");
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount));
        $("#totalPPN").val(humanizeNumber((parseInt(ppn)*totalAmount)/100));
        $("#totalPPH").val(0);
        $("#totalNetto").val(humanizeNumber(totalAmount+((parseInt(ppn)*totalAmount)/100)));
        
        

    }

    function changeselect(dependent,obj,value,type) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            value:value,
            type:type,
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val('').trigger('change');
        }
      })
    }

    function tombolPanah(objname){
        // function kalo mau pindah filed dari atas ke bawah atau sebaliknya
        let obj = $('#article_row input[name="'+objname+'[]"]');
        obj.keyup(function(e) {
            indexnya = obj.index(this);
            indexnya = parseInt(indexnya);
            if (e.keyCode == 38) {
                //panah atas
                indexTarget = indexnya-1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
            if (e.keyCode == 40) {
                //panah bawah
                indexTarget = indexnya+1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
        });
    }
    
    dateLflatPickr = $('.flatpickr-basic');
    if (dateLflatPickr.length) {
        dateLflatPickr.flatpickr({
            dateFormat: "m-d-Y",
            // "setDate": new Date()
        });
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection