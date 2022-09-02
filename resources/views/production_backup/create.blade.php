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
                        <label for="prdNumber" class="col-sm-4 col-form-label col-form-label-sm">Production Number</label>
                        <div class="col-md-8">
                            <input type="text" id="prdNumber" name="prdNumber" class="form-control form-control-sm disabled-el" disabled />
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
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="prdDate">Date*</label>
                                    <input type="text" id="prdDate" name="prdDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="shift">Shift*</label>
                                    <select class="select2 form-control" id="shift" name="shift" required>
                                        {{-- <option value="">All</option> --}}
                                        <option value="pagi">Pagi</option>
                                        <option value="siang">Siang</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="group">Group*</label>
                                    <select class="select2 form-control" id="group" name="group" required>
                                        {{-- <option value="">All</option> --}}
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="prdDate">Time StartDate*</label>
                                    <input type="text" id="prdTime" name="prdTime" class="form-control" placeholder="HH:MM" required />
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    {{-- <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button> --}}
                                    {{-- <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button> --}}
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                    <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
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
                    <button class="btn btn-success" type="button" id="cmdSort" name="cmdSort">Sort</button>
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            <br>
                            @include('production.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
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
        <h4 class="card-title">Material List</h4>
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

@include('production.addArticle')
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
        validateFormToast("frmAdd");
        $('#prdDate').val(currentDate);
        $('#cmdPosting').hide();
        // console.log(detikKeJam(5400));
        // hitungWaktu();
    });
        
    function reloadPage(){
        window.location.reload();
    }

    prdDate = $('#prdDate');
    if (prdDate.length) {
        prdDate.flatpickr({
            dateFormat: "d-m-Y",
            minDate: currentDate
        });
    }

    prdTime = $('#prdTime');
    if (prdTime.length) {
        prdTime.flatpickr({
            enableTime: true,
            time_24hr: true,
            noCalendar: true,
            defaultDate: "08:00:00",
        });
    }

    $("#cmdCancel").click(function(){
        $('#prdNumber').val('');
        reloadPage();
    });

    $("#cmdNew").click(function(){
        $('#prdNumber').val('');
        reloadPage();
    });
    
    $("#cmdSort").click(function(){
        let articles = []; 
        let flag=0;
        let pesan="";
        let objArticle = $("#article_row select[name='articleId[]']");
        let objQtyOrder = $('#article_row input[name="qtyOrder[]"]');
        let objUomQtyOrder = $('#article_row span[name="uomQtyOrder[]"]');
        let objQtyProd = $('#article_row input[name="qtyProd[]"]');
        let objSoCode = $('#article_row select[name="salesOrder[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let objUrutan = $('#article_row input[name="urutan[]"]');
        let objWaktu = $('#article_row input[name="waktu[]"]');
        
        objArticle.map(function(i) {
		    let $this=$(this);
            if ($this.val()){
                let article=$this.val();
                let urutan =objUrutan.eq(i).val();
                let soCode=objSoCode.eq(i).val();
                let qtyOrder=objQtyOrder.eq(i).val().replace(/,/gi, '') || 0;
                let uomOrder=objUomQtyOrder.eq(i).text();
                let qtyProd=objQtyProd.eq(i).val().replace(/,/gi, '') || 0;
                let tag =objTag.eq(i).val();
                let waktu = objWaktu.eq(i).val();
                let obj = articles.find(obj => obj.urutan == urutan);
                
                if(obj) {
                    pesan +="Urutan belum sesuai !! <br>"; 
                    flag=1;
                    console.log(pesan);
                }else{
                    if(article){
                        articles.push({
                            "urutan":urutan,
                            "so_code":soCode,
                            "article_code":article,
                            "qty_so":qtyOrder,
                            "uom":uomOrder,
                            "qty":qtyProd,
                            "tag":tag*qtyProd,
                            "waktu":waktu
                        });
                    }
                }
                articles.sort((a, b) => (a.urutan > b.urutan) ? 1 : -1)
            }
        });

        if (articles.length > 0){
            $('#article_row').find('div').remove();
            cloneCountEdit=0;
            articles.map(function(i) {
                add_new_row_edit(i.so_code,i.article_code,i.qty_so,i.uom,i.qty,i.waktu,i.tag);
            })
        }
    });

    $("#cmdSave").click(function(){     
        $('.disabled-el').removeAttr('disabled');
        // ambil semua data article
        let objArticle = $("#article_row select[name='articleId[]']");
        let objQtyOrder = $('#article_row input[name="qtyOrder[]"]');
        let objQtyProd = $('#article_row input[name="qtyProd[]"]');
        let objSoCode = $('#article_row select[name="salesOrder[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let objUrutan = $('#article_row input[name="urutan[]"]');
        let objWaktu = $('#article_row input[name="waktu[]"]');
        let prdDate = $('#prdDate').val();
        let shift = $('#shift').val();
        let group = $('#group').val();
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
                let qtyOrder=objQtyOrder.eq(i).val().replace(/,/gi, '') || 0;
                let qty=objQtyProd.eq(i).val().replace(/,/gi, '') || 0;
                let soCode=objSoCode.eq(i).val();
                let tag =objTag.eq(i).val();
                let urutan =objUrutan.eq(i).val();
                let waktu = objWaktu.eq(i).val();
                                        
                //es6
                // let obj = ingredient.find(obj => obj.plu == plu);

                //jquery
                //cek apakah article ada yang double input ato ngk
                let obj = $.grep(articles, function(obj){
                    return obj.urutan === urutan;
                })[0];

                if(obj) {
                    pesan +="Urutan belum sesuai !! <br>"; 
                    flag=1;
                    console.log(pesan);
                }
                
                // if(obj) {
                //     pesan +="Article "+articleName+" entered more than once !! <br>"; 
                //     flag=1;
                // } else {
                //     if ((plu!=='') && (qty> 0)){
                //         articles.push({
                //             "urutan":urutan,
                //             "so_code":soCode,
                //             "article_code":plu,
                //             "qty":qty,
                //             "tag":tag
                //         });
                //     }
                // } 

                // articles.sort();
                // add_new_row(noSo,noArticle,qtySo,qtyProd,waktu) {
                // if ((plu!=='') && (qty> 0)){
                    articles.push({
                        "urutan":urutan,
                        "so_code":soCode,
                        "article_code":plu,
                        "qty_so":qtyOrder,
                        "qty":qty,
                        "tag":tag,
                        "waktu":waktu
                    });
                // }

                articles.sort((a, b) => (a.urutan > b.urutan) ? 1 : -1)
                // console.log(articles);
            
                if (qty == 0){
                    pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                    flag=1;
                }
            }
        });

        if (flag==0){
            $('#article_row').find('div').remove();
            cloneCountEdit=0;
            articles.map(function(i) {
                add_new_row_edit(i.so_code,i.article_code,i.qty_so,i.qty,i.waktu,i.tag);
            })
        }

        if (articles.length == 0){
			pesan +="Articles must be filled in completely <br>"; 
			flag=1;
		}

        // if (flag==0){
        //     $.ajax({
        //         type: "post",
        //         url: "{{ route('production.store') }}",
        //         data: {
        //             articles:JSON.stringify(articles),
        //             prdDate:prdDate,
        //             shift:shift,
        //             group:group,
        //             note:note
        //         },
        //         dataType: "json",
        //         success: function(data) {
        //             if (data.status == 0 ){
        //                 let message="";
        //                 for(let i = 0; i < data.message.length; i++) {
        //                     show_msg(data.title, data.message[i], data.alert);
        //                 }
                        
        //                 $('#prdNumber').attr('disabled','disabled');

        //             }else{
        //                 show_msg(data.title, data.message, data.alert)
        //                 $('#prdNumber').attr('disabled','disabled');
        //                 $('#cmdSave').attr('disabled','disabled');
        //                 $('#addNewRow').attr('disabled','disabled');
        //                 $('#prdNumber').val(data.prdNumber);
        //                 $('#cmdPosting').show();
                        
        //             }
                    
        //         },
        //         error: function(error) {
        //             console.log(error);
        //         }
        //     });

        // }else{
        //     Swal.fire('Warning..',pesan,'warning');
        // }
    
    });

    $("#cmdPosting").click(function(){   
        let prodNumber = $('#prdNumber').val();
        $.ajax({
            type: "post",
            url: "{{ route('production.posting') }}",
            data: { prodNumber:prodNumber},
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }

                    $('#prdNumber').attr('disabled','disabled');

                }else{
                    show_msg(data.title, data.message, data.alert)
                    $('#prdNumber').attr('disabled','disabled');
                    $('#cmdSave').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');
                    $('#prdNumber').val(data.prdNumber);
                    $('#cmdPosting').attr('disabled','disabled');
                }
                
            },
            error: function(error) {
                console.log(error);
            }
        });
    });

    function prosesWO(){
        let objArticle = $("#article_row select[name='articleId[]']");
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
                let qty=objQtyProd.eq(i).val().replace(/,/gi, '') || 0;
                                            
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
            // console.log(articles);     
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
                    url:'{{ route("production.detail.list")}}',
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
                    { data: 'qty_total', name: 'qty_total',title:'QTY Total' ,render: $.fn.dataTable.render.number(',','.',4) },
                    { data: 'kelompok', name: 'kelompok',title:'Article Type' ,render: $.fn.dataTable.render.number(',','.',4) },
                ],
            });
        });
        
    }
    
    let cloneCount=0;
    function add_new_row() {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        $("#new_row"+ cloneCount).find('#salesOrder').attr('id', 'salesOrder'+ cloneCount);
        $("#new_row"+ cloneCount).find('#urutan').attr('id', 'urutan'+ cloneCount);
        $('#urutan'+ cloneCount).val(cloneCount);
        changeselect('salesOrder','salesOrder'+ cloneCount,'','');
        $("#articleId"+cloneCount).select2();
        $("#salesOrder"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('qtyProd');
        activate_angka();
        mask_thousand_satuan();
        isiListArticle();
        updatQty();
    };

    let cloneCountEdit=0;
    function add_new_row_edit(noSo,noArticle,qtySo,qtySoUom,qtyProd,waktu,tag) {
        $("#article_row").append($("#new_row").clone().html());
        cloneCountEdit++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#urutan').attr('id', 'urutan'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#salesOrder').attr('id', 'salesOrder'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleId').attr('id', 'articleId'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyOrder').attr('id', 'qtyOrder'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#uomQtyOrder').attr('id', 'uomQtyOrder'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyProd').attr('id', 'qtyProd'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#waktu').attr('id', 'waktu'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tag').attr('id', 'tag'+ cloneCountEdit);
        changeselect('salesOrder','salesOrder'+ cloneCountEdit,noSo);
        changeSelectArticleEdit('searchFromSO','articleId'+ cloneCountEdit,noSo,noArticle);
        $('#urutan'+ cloneCountEdit).val(cloneCountEdit);
        $('#qtyOrder'+ cloneCountEdit).val(qtySo);
        $('#uomQtyOrder'+ cloneCountEdit).text(qtySoUom);
        $('#qtyProd'+ cloneCountEdit).val(qtyProd);
        $('#waktu'+ cloneCountEdit).val(waktu);
        $('#tag'+ cloneCountEdit).val(tag);
        $("#articleId"+cloneCountEdit).select2();
        $("#salesOrder"+cloneCountEdit).select2();
        $('#remove_button').tooltip();
        tombolPanah('qtyProd');
        mask_thousand_satuan();
        hitungWaktu(); 
    };

    function changeSelectArticle(dependent,objIndex,value) {
        let objArticle = $('#article_row select[name="articleId[]"]');
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

    function changeSelectArticleEdit(dependent,obj,value,article) {
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                value:value,
                dependent:dependent
            },
            success:function(result){
                $('#'+obj).html(result);
                $('#'+obj).val(article).trigger('change');
            }
        })
    }

    function isiListArticle(){
        let objSo = $('#article_row select[name="salesOrder[]"]');
        objSo.change(function(e){        
            let objIndex = objSo.index(this);
            let soCode = objSo.eq(objIndex).val();
            if (soCode){
                changeSelectArticle('searchFromSO',objIndex,soCode);
                splitArticle();
            }
        });
    }

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objQtyOrder = $('input[name="qtyOrder[]"]');
        let objQtyProd = $('input[name="qtyProd[]"]');
        let objTag = $('input[name="tag[]"]');
        let objUomQtyOrder = $('span[name="uomQtyOrder[]"]');
        let objWaktu = $('input[name="waktu[]"]');

        objArticle.change(function(e){        
            let objIndex = objArticle.index(this);
            let detail = objArticle.eq(objIndex).find(":selected").data("detail");
            if (detail){
                let arrDetail = detail.split("|");
                objQtyProd.eq(objIndex).val('');
                objQtyOrder.eq(objIndex).val(arrDetail[3]);
                objTag.eq(objIndex).val(arrDetail[2]);
                objWaktu.eq(objIndex).val($('#prdTime').val()+":00");
                objUomQtyOrder.eq(objIndex).text(arrDetail[4]);
                if (detail){
                    setTimeout(() => {
                        objQtyProd.eq(objIndex).focus().select();
                    }, 5);
                }
                mask_thousand_satuan();
                // hitungWaktu();   
            }else{
                objQtyProd.eq(objIndex).val('');
                objQtyOrder.eq(objIndex).val('');
                objTag.eq(objIndex).val('');
                objWaktu.eq(objIndex).val('');
                objUomQtyOrder.eq(objIndex).text('');
            }
		});
    }

    detikKeJam = (s) => {
        let date = new Date(0);
        date.setSeconds(s); // specify value for SECONDS here
        let timeString = date.toISOString().substr(11, 8);
        return timeString;
    }

    hitungWaktu = () => {
        let objWaktu = $('#article_row input[name="waktu[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let waktuAwal = $('#prdTime').val()+":00";
        let waktuAwalDetik = waktuAwal.split(':').reduce((acc,time) => (60 * acc) + +time);
        let nilaiTag = 0;
        let jamBaru = waktuAwal;
        let nilaiSekarang = 0;
        objWaktu.map(function(i) {  
            let $this=$(this);            
            if (i>0){
                let nilaiTag = objTag.eq(i).val()*30;
                let currentTime = objWaktu.eq(i-1).val();
                let currentTimeDetik = currentTime.split(':').reduce((acc,time) => (60 * acc) + +time);
                nilaiSekarang = currentTimeDetik+nilaiTag;
                let jamBaru = detikKeJam(nilaiSekarang);
                $this.val(jamBaru);
            }else{
                $this.val(jamBaru);
            }
        });
    }

    function changeselect(dependent,obj,isiData) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val(isiData).trigger('change');
        }
      })
    }

    

    updatQty = () =>{
        let objQtyProd = $('#article_row input[name="qtyProd[]"]');
        let objQtyRepaint = $('#article_row input[name="qtyRepaint[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let objArticle = $('#article_row select[name="articleId[]"]');
        objQtyProd.keyup(function(e){        
            let objIndex = objQtyProd.index(this);
            let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
            let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
            let detail = objArticle.eq(objIndex).find(":selected").data("detail");
            if (detail){
                let arrDetail = detail.split("|");
                qtyTag = arrDetail[2].replace(/,/gi, '') || 0;
            }
            if (qtyProd || qtyRepaint){
                objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
            }else{
                objTag.eq(objIndex).val(qtyTag);
            }
            hitungWaktu();
		});

        objQtyRepaint.keyup(function(e){        
            let objIndex = objQtyRepaint.index(this);
            console.log(objIndex);
            let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
            let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
            let detail = objArticle.eq(objIndex).find(":selected").data("detail");
            if (detail){
                let arrDetail = detail.split("|");
                qtyTag = arrDetail[2].replace(/,/gi, '') || 0;
            }
            if (qtyProd || qtyRepaint){
                objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
            }else{
                objTag.eq(objIndex).val(qtyTag);
            }
            hitungWaktu();
		});
    }

    // function tombolPanah(objname){
    //     // function kalo mau pindah filed dari atas ke bawah atau sebaliknya
    //     let obj = $('input[name="'+objname+'[]"]');
    //     obj.keyup(function(e) {
    //         indexnya= obj.index(this);
    //         indexnya=parseInt(indexnya);
    //         if (e.keyCode == 38) {
    //             //panah atas
    //             indexTarget = indexnya-1;
    //             obj.eq(indexTarget).focus().select();
    //             return false;
    //         }
    //         if (e.keyCode == 40) {
    //             //panah bawah
    //             indexTarget = indexnya+1;
    //             obj.eq(indexTarget).focus().select();
    //             return false;
    //         }
    //     });
    // }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection