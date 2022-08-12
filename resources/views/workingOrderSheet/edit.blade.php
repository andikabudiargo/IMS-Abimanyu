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
                        <label for="wosNumber" class="col-sm-4 col-form-label col-form-label-sm">WOS Number</label>
                        <div class="col-md-8">
                            <input type="text" id="wosNumber" name="wosNumber" value="{{ $header->wo_code  }}" class="form-control form-control-sm disabled-el" disabled />
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
                                    <label for="wosDate">Date*</label>
                                    <input type="text" id="wosDate" name="wosDate" value="{{ $header->wo_date  }}" class="form-control"  placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="shift">Shift*</label>
                                    <select class="select2 form-control" id="shift" name="shift" required>
                                        <option value=""></option>
                                        <option value="pagi" {{ $header->wo_shift == 'pagi' ? "selected" : "" }} >Pagi</option>
                                        <option value="siang" {{ $header->wo_shift == 'siang' ? "selected" : "" }} >Siang</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="group">Group*</label>
                                    <select class="select2 form-control" id="group" name="group" required>
                                        <option value=""></option>
                                        <option value="A" {{ $header->wo_group == 'A' ? "selected" : "" }} >A</option>
                                        <option value="B" {{ $header->wo_group == 'B' ? "selected" : "" }} >B</option>
                                        <option value="C" {{ $header->wo_group == 'C' ? "selected" : "" }} >C</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="wosTime">Start Time*</label>
                                    <input type="text" id="wosTime" name="wosTime" value="{{ $header->start_time  }}" class="form-control"  placeholder="HH:MM" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="workingHour">Working Hour*</label>
                                    <input type="text" id="workingHour" name="workingHour" value="{{ $header->working_hour  }}" class="form-control numeral-mask-satuan text-right" maxlength="2" required />
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" value="{{ $header->note  }}" class="form-control" rows="1" ></textarea>
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
                    <button class="btn btn-success btn-sm" type="button" id="cmdSort" name="cmdSort">Sort</button>
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            <br>
                            @include('production.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-start align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                        {{-- <button class="btn btn-primary btn-prev ml-1" type="button" id="prosesWO" onclick="prosesWO();">
                            <span class="align-middle d-sm-inline-block d-none">Proses</span>
                        </button> --}}
                    </div>
                    <div class="col-md-10" style="padding-left:0">
                        <div class="table-responsive main-table mt-75">
                            <table class="table table-bordered w-100" >
                                <tr>
                                    <td rowspan="2">Total Tag</td>
                                    <td class="text-right" id="sumWorkHour"></td>
                                    <td>x3600"x95% = </td>
                                    <td>Waktu tersedia</td>
                                    <td class="text-right" id="sumAvailableTime"></td>
                                </tr>
                                <tr>
                                    <td>Waktu Dibutuhkan</td>
                                    <td class="text-right" id="sumTimeRequired"></td>
                                    <td>Sisa Waktu</td>
                                    <td class="text-right" id="sumRemainTime"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" >Save</button>
                        </div>
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
@include('workingOrderSheet.addArticle')
<script type="text/javascript">
    
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        let detail = {!!  $details !!};
        for(let i=0;i< detail.length;i++){
            soCode = detail[i].so_code;
            articleCode = detail[i].article_code;
            articleRm = detail[i].article_rm_code;
            qtySo = detail[i].so_qty; //belum ada
            uom = 'PCS';
            planQtyFresh = detail[i].plan_qty_fresh;
            planQtyRepaint = detail[i].plan_qty_repaint;
            planTime = detail[i].plan_time;
            planTag = detail[i].plan_tag;
            originTag = detail[i].origin_tag;
            add_new_row_edit(soCode,articleCode,articleRm,qtySo,uom,planQtyFresh,planQtyRepaint,planTime,planTag,originTag);
        }
    });   

    cmdSave.click(function(){
        $('.disabled-el').removeAttr('disabled');
        let articles = []; 
        let flag=0;
        let pesan="";
        let objArticle = $("#article_row select[name='articleId[]']");
        let objArticleRm = $("#article_row input[name='articleRm[]']");
        let objQtyOrder = $('#article_row input[name="qtyOrder[]"]');
        let objQtyProd = $('#article_row input[name="qtyProd[]"]');
        let objQtyRepaint = $('#article_row input[name="qtyRepaint[]"]');
        let objSoCode = $('#article_row select[name="salesOrder[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let objTagAsli = $('#article_row input[name="tagAsli[]"]');
        let objUrutan = $('#article_row input[name="urutan[]"]');
        let objWaktu = $('#article_row input[name="waktu[]"]');
        let sWosDate = wosDate.val();
        let sWosShift = wosShift.val();
        let sWosGroup = wosGroup.val();
        let sWosTime = wosTime.val();
        let sWorkHour = workHour.val();
        let sNote = note.val();

        objArticle.map(function(i) {  
		    let $this=$(this);
            if ($this.val()){
                let article = $this.val();
                let articleName = article;
                let articleRm = objArticleRm.eq(i).val();
                let urutan = objUrutan.eq(i).val();
                let soCode = objSoCode.eq(i).val();
                let qtyOrder = objQtyOrder.eq(i).val().replace(/,/gi, '') || 0;
                let qtyProd = objQtyProd.eq(i).val().replace(/,/gi, '') || 0;
                let qtyRepaint = objQtyRepaint.eq(i).val().replace(/,/gi, '') || 0;
                let tag = objTag.eq(i).val();
                let tagAsli = objTagAsli.eq(i).val();
                let waktu = objWaktu.eq(i).val();

                // cek urutan harus sesuai jangan ada urutan yang double
                let obj = articles.find(obj => obj.urutan == urutan);
                
                if(obj) {
                    pesan +="Urutan belum sesuai !! <br>"; 
                    flag=1;
                }else{
                    if(article){
                        articles.push({
                            "urutan":urutan,
                            "so_code":soCode,
                            "article_code":article,
                            "article_rm":articleRm,
                            "qty_so":qtyOrder,
                            "uom":'PCS',
                            "qty_prod":qtyProd,
                            "qty_repaint":qtyRepaint,
                            "tag":tag,
                            "tag_asli":tagAsli,
                            "waktu":waktu
                        });
                    }
                }
                // urutkan data berdasarkan nomor urutan   
                if ( (qtyProd+qtyRepaint) == 0 ){
                    pesan +="QTY of items "+ articleName +" order ="+urutan +" cannot be 0 <br>"; 
                    flag=1;
                }
            }
        });

        if (articles.length > 0){
            articles.sort((a, b) => (a.urutan > b.urutan) ? 1 : -1);
            $('#article_row').find('div').remove();
            cloneCountEdit=0;
            articles.map(function(i) {
                add_new_row_edit(i.so_code,i.article_code,i.article_rm,i.qty_so,i.uom,i.qty_prod,i.qty_repaint,i.waktu,i.tag,i.tag_asli);
            })
        }else{
            pesan +="Articles must be filled in completely <br>"; 
			flag=1;
        }

        if (flag==0){
            $.ajax({
                type: "post",
                url: "{{ route('workingOrderSheet.store') }}",
                data: {
                    articles:JSON.stringify(articles),
                    wosDate:sWosDate,
                    wosTime:sWosTime,
                    shift:sWosShift,
                    group:sWosGroup,
                    workHour:sWorkHour,
                    note:sNote
                },
                dataType: "json",
                success: function(data) {
                    if (data.status == 0 ){
                        let message="";
                        for(let i = 0; i < data.message.length; i++) {
                            show_msg(data.title, data.message[i], data.alert);
                        }
                        $('#wosNumber').attr('disabled','disabled');
                    }else{
                        show_msg(data.title, data.message, data.alert)
                        $('#wosNumber').attr('disabled','disabled');
                        $('#cmdSave').attr('disabled','disabled');
                        $('#addNewRow').attr('disabled','disabled');
                        $('#wosNumber').val(data.wosNumber);
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
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection