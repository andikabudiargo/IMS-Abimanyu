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
                    <div class="form-group row">
                        <label for="woNumber" class="col-sm-4 col-form-label col-form-label-sm">WO Number</label>
                        <div class="col-md-8">
                            <input type="text" id="woNumber" name="woNumber" class="form-control form-control-sm disabled-el"  disabled />
                        </div>
                    </div>                    
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
                                <div class="form-group col-md-2">
                                    <label for="woDate">Date*</label>
                                    <input type="text" id="woDate" name="woDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
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
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body" >
                    <div>
                        <table class="" style="width:98%;table-layout: fixed;">
                            <tbody>
                                <tr>
                                    <td class="isian-satu" style="width: 20%">
                                        <label>NO SPK / SO</label>
                                    </td>
                                    <td class="isian-satu" style="width: 25%">
                                        <label>Article Code</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Stock</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>QTY Order</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>QTY Prod</label>
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
                    <div class="d-flex justify-content-start align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                        <button class="btn btn-primary btn-prev ml-1" type="button" id="prosesWO" onclick="prosesWO();">
                            <span class="align-middle d-sm-inline-block d-none">Proses</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="table-article">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title"> Working order List</h4>
        <div class="heading-elements">
            <ul class="list-inline mb-0">
                <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
            </ul>
        </div>
      </div>
      <div class="card-content collapse show">
        <div class="card-body">
          <div class="row">
              <div class="col-sm-12">
                <div class="card-datatable table-responsive pt-0">
                  <table id="detailedTable" class="table">
                    <thead class="thead-light">
                    </thead>
                  </table>
                </div>
              </div>
          </div>  
        </div>
      </div>
    </div>
</section>

@include('workingOrder.addArticle')
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


</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        validateForm('frmAdd');
        $('#woDate').val(currentDate);
    });
        
    function reloadPage(){
        window.location.reload();
    }

    woDate = $('#woDate');
    if (woDate.length) {
        woDate.flatpickr({
            dateFormat: "d-m-Y",
            minDate: currentDate
        });
    }

    $("#cmdCancel").click(function(){
        $('#woNumber').val('');
        reloadPage();
    });

    $("#cmdNew").click(function(){
        $('#woNumber').val('');
        reloadPage();
    });

    $("#cmdSave").click(function(){     
        $('.disabled-el').removeAttr('disabled');
        // ambil semua data article
        let objArticle = $("#article_row select[name='article_id[]']");
        let qtyOrder = $('input[name="qtyOrder[]"]');
        let qtyProd = $('input[name="qtyProd[]"]');
        let woDate = $('#woDate').val();
        let note = $('#note').val();
        let articles = []; 
        let flag=0; 
        let pesan="";

        objArticle.map(function(i) {  
		    let $this=$(this);
            if ($this.val()){
                let article=$this.val().split("|");
                let articleName=$this.select2('data')[0].text;
                let plu=article[0];
                let qty=objProd.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                            
                //es6
                // let obj = ingredient.find(obj => obj.plu == plu);

                //jquery
                //cek apakah article ada yang double input ato ngk
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
                            "customer_code":customer,
                            "price":price,
                            "type":type
                        });
                    }
                } 
            
                if (qty == 0){
                    pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                    flag=1;
                }
            }
        });

        if (customer == ''){
			pesan +="Customer must be filled in <br>"; 
			flag=1;
		}

        if (articles.length == 0){
			pesan +="Articles must be filled in completely <br>"; 
			flag=1;
		}

        if (flag==0){

            $.ajax({
                type: "post",
                url: "{{ route('bom.store') }}",
                data: {
                    articles:JSON.stringify(articles),
                    articleCode:articleCode,
                    customer:customer,
                    note:note,
                    group:group,
                    uom:uom,
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
                        $('#woNumber').attr('disabled','disabled');

                    }else{
                        $("#alert-message-success").addClass(data.alert);
                        $("#alert-message-success .alert-body").html(data.message);
                        $("#alert-message-success").show();
                        $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                            $("#alert-message-success").slideUp(500);
                        });
                        $('#woNumber').attr('disabled','disabled');
                        $('#cmdSave').attr('disabled','disabled');
                        $('#addNewRow').attr('disabled','disabled');
                        $('#woNumber').val(data.woNumber);
                        
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

    function prosesWO(){
        let objArticle = $("#article_row select[name='article_id[]']");
        let objQtyProd = $('input[name="qtyProd[]"]');
        let articles = []; 
        let pesan="";
        let flag= 0;
        objArticle.map(function(i) {  
		    let $this=$(this);
            if ($this.val()){
                let article=$this.val().split("|");
                let plu=article[0];
                let articleName=$this.select2('data')[0].text;
                let qty=objQtyProd.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                                            
                //es6
                // let obj = ingredient.find(obj => obj.plu == plu);

                //jquery
                //cek apakah article ada yang double input ato ngk
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
                            "qty":qty
                        });
                    }
                } 
            
                if (qty == 0){
                    pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                    flag=1;
                }
            }
        });

        if (flag==0){
            console.log(articles);     
            showList(articles);   
        }else{
            Swal.fire('Warning..',pesan,'warning');
        }
        
    }

    function showList(articles){
        let isidata = $('#detailedTable tr').length;
        if (isidata >0){
            let table= $('#detailedTable').DataTable();
            table.destroy();
            $('#detailedTable tbody > tr').remove();
        }
        
        let dtdom ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"' +
            '<"col-lg-12 col-xl-6" l>' +
            '<"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>' +
            '>t' +
            '<"d-flex justify-content-between mx-2 row mb-1"' +
            '<"col-sm-12 col-md-6"i>' +
            '<"col-sm-12 col-md-6"p>' +
            '>';
        let arr_col_print =[2,3,4,5,6]; 
        $(function(){
            let oTable =$("#detailedTable").DataTable({
                ajax:{
                    url:'{{ route("workingOrder.detail.list")}}',
                    data:{
                        articles:JSON.stringify(articles),
                    }
                },
                processing: true,
                serverSide: true,
                buttons: true,
                dom:dtdom,
                lengthMenu: [
                [ 10, 25, 50, -1 ],
                [ '10', '25', '50', 'all' ]
                ],
                buttons: [
                {
                    extend: 'collection',
                    className: 'btn btn-outline-secondary dropdown-toggle mr-2 mt-07',
                    text: feather.icons['share'].toSvg({ class: 'font-small-4 mr-50' }) + 'Export',
                    buttons: [
                    {
                        extend: 'print',
                        text: feather.icons['printer'].toSvg({ class: 'font-small-4 mr-50' }) + 'Print',
                        className: 'dropdown-item',
                        exportOptions: { columns: arr_col_print }
                    },
                    {
                        extend: 'csv',
                        text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + 'Csv',
                        className: 'dropdown-item',
                        exportOptions: { columns: arr_col_print }
                    },
                    {
                        extend: 'excel',
                        text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + 'Excel',
                        className: 'dropdown-item',
                        exportOptions: { columns: arr_col_print }
                    },
                    {
                        extend: 'pdf',
                        text: feather.icons['clipboard'].toSvg({ class: 'font-small-4 mr-50' }) + 'Pdf',
                        className: 'dropdown-item',
                        exportOptions: { columns: arr_col_print }
                    },
                    {
                        extend: 'copy',
                        text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + 'Copy',
                        className: 'dropdown-item',
                        exportOptions: { columns: arr_col_print }
                    }
                    ],
                    init: function (api, node, config) {
                    $(node).removeClass('btn-secondary');
                    $(node).parent().removeClass('btn-group');
                    setTimeout(function () {
                        $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
                    }, 50);
                    }
                },
                ],
                responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                    header: function (row) {
                        var data = row.data();
                        return 'Details of ' + data['nama'];
                    }
                    }),
                    type: 'column',
                    renderer: function (api, rowIdx, columns) {
                    var data = $.map(columns, function (col, i) {
                        return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                        ? '<tr data-dt-row="' +
                            col.rowIndex +
                            '" data-dt-column="' +
                            col.columnIndex +
                            '">' +
                            '<td>' +
                            col.title +
                            ':' +
                            '</td> ' +
                            '<td>' +
                            col.data +
                            '</td>' +
                            '</tr>'
                        : '';
                    }).join('');
                    return data ? $('<table class="table"/>').append(data) : false;
                    }
                }
                },
                language: {
                paginate: {
                    // remove previous & next text from pagination
                    previous: '&nbsp;',
                    next: '&nbsp;'
                }
                },
                columnDefs: [
                    { width: '5%', targets: 0 },
                    { className: 'text-right','targets': [ 4,5 ] },
                    {
                        "searchable": false,
                        "orderable": false,
                        "targets": 0
                    }
                ],
                drawCallback: function( settings ) {
                    feather.replace({
                            width: 14,
                            height: 14
                    });
                },
                order: [[ 2, 'asc' ]],
                bDestroy: true, //pakai ini supaya bisa di load berulang2
                // scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
                columns: [
                    {
                        data: 'id',title:"#",
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    { data: 'article_alternative_code', name: 'article_alternative_code',title:'Article Code' },
                    { data: 'article_desc', name: 'article_desc',title:'Desc' },
                    { data: 'uom', name: 'uom',title:'UOM' },
                    { data: 'qty', name: 'qty',title:'QTY' },
                    { data: 'qty_total', name: 'qty_total',title:'QTY Total' ,render: $.fn.dataTable.render.number(',','.') },
                    { data: 'kelompok', name: 'kelompok',title:'Article Type' ,render: $.fn.dataTable.render.number(',','.') },
                ],
            });
        });
        
    }

    
    let cloneCount=1;
    function add_new_row() {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        $("#new_row"+ cloneCount).find('#salesOrder').attr('id', 'salesOrder'+ cloneCount);
        changeselect('salesOrder','salesOrder'+ cloneCount,'','');
        $("#article_id"+cloneCount).select2();
        $("#salesOrder"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('qty_prod');
        activate_angka();
        mask_thousand();
        // splitArticle();
        isiListArticle();
    };

    function isiListArticle(){
        // split article with delimiter |
        let objSo = $('#article_row select[name="salesOrder[]"]');
        objSo.change(function(e){        
            let objIndex = objSo.index(this);
            let soCode = objSo.eq(objIndex).val();
            changeSelectArticle('searchFromSO',objIndex,soCode);
            splitArticle();
		});
    }

    function changeSelectArticle(dependent,objIndex,value) {
        let objArticle = $('#article_row select[name="article_id[]"]');
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                value:value,
                dependent:dependent
            },
            success:function(result){
                objArticle.eq(objIndex).html(result);
                objArticle.eq(objIndex).select2();
                // objArticle.eq(objIndex).trigger('change');
            }
        })
    }

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objQtyStock = $('input[name="qtyStock[]"]');
        let objQtyOrder = $('input[name="qtyOrder[]"]');
        let objQtyProd = $('input[name="qtyProd[]"]');
        
        objArticle.change(function(e){        
            let objIndex = objArticle.index(this);
            let detail = objArticle.eq(objIndex).val();
            let arrDetail = detail.split("|");
            objQtyStock.eq(objIndex).val(arrDetail[2]);
            objQtyOrder.eq(objIndex).val(arrDetail[3]);
            if (detail){
                setTimeout(() => {
                    objQtyProd.eq(objIndex).focus().select();
                }, 5);
            }
		});
    }

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

    function tombolPanah(objname){
        // function kalo mau pindah filed dari atas ke bawah atau sebaliknya
        let obj = $('input[name="'+objname+'[]"]');
        obj.keyup(function(e) {
            indexnya= obj.index(this);
            indexnya=parseInt(indexnya);
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

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection