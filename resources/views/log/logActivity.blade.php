@extends('layouts.app')
@section('title', 'Log Activity Lists')
@section('content')
@include('layouts.breadcrumb')

<section id="users-index">
    <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">  
              <div class="card-title">@yield('title')
              </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <h5 class="card-header">Search Filter</h5>
                        <div class="d-flex justify-content-start align-items-center mx-50 row pt-0 pb-2">
                          <div class="col-md-4 subject"></div>
                          <div class="col-md-4 userName"></div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="detailedTable" class="table">
                    </table>
                </div>
            </div>
          </div>
        </div>
    </div>    
</section>

@endsection
@section('styles')
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        showLists();
    });

    function showLists(){
        // let dtdom = '<"card-header border-bottom p-1"><"d-flex justify-content-between align-items-center mx-0 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-4"f><"col-sm-12 col-md-2"<"dt-action-buttons text-right"B>>>t<"d-flex justify-content-between mx-0 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>';
        let dtdom ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"' +
        '<"col-lg-12 col-xl-6" l>' +
        '<"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>' +
        '>t' +
        '<"d-flex justify-content-between mx-2 row mb-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>';
        let arr_col_print =[0,1,2,3,4,6,7]; 
        $(function(){
            var oTable =$("#detailedTable").DataTable({
                ajax:{
                    url:'{{ route("show.log.lists")}}',
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
                    }
                ],
                language: {
                    paginate: {
                        // remove previous & next text from pagination
                        previous: '&nbsp;',
                        next: '&nbsp;'
                    }
                },
                drawCallback: function( ) {
                    feather.replace({
                        width: 14,
                        height: 14
                    });
                },
                order: [[ 7, 'desc' ]],
                bDestroy: true, //pakai ini supaya bisa di load berulang2
                scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
                columns: [
                    {
                    data: 'id',title:'No',
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }, orderable: false, searchable: false
                    },
                    { data: 'subject',name:'subject', title:'Subject'},
                    { data: 'url', name: 'url',title:'URL' },
                    { data: 'method', name: 'method',title:'Method' },
                    { data: 'agent', name: 'agen',title:'User Agent' },
                    { data: 'ip', name: 'ip',title:'IP Address' },
                    { data: 'user_id', name: 'user_id',title:'Username' },
                    { data: 'description', name: 'description',title:'Description' },
                    { data: 'date', name: 'date',title:'Date' },
                    // { data: 'action', name: 'action',title:'action', orderable: false, searchable: false},
                ],
                columnDefs: [
                    {
                        targets: 3,
                        render: function (data, type, row, meta) {
                            return '<span class="badge badge-light-info">' + data + '</span>';
                        }
                        
                    },
                    {
                        targets: 2,
                        render: function (data, type, row, meta) {
                            return '<span style="color:#28c76f">' + data + '</span>';
                        }
                        
                    },
                    {
                        targets: 4,
                        render: function (data, type, row, meta) {
                            return '<span style="color:#ea5455">' + data + '</span>';
                        }
                        
                    }
                    
                ],
                initComplete: function () {
                    // Adding role filter once table initialized
                    $( "#userName" ).remove();
                    $( "#subject" ).remove();
                    this.api()
                        .columns(1)
                        .every(function () {
                            var column = this;
                            var select = $(
                            '<select id="subject" class="form-control text-capitalize mb-md-0 mb-2"><option value=""> Select Subject </option></select>'
                        )
                            .appendTo('.subject')
                            .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            column.search(val ? '^' + val + '$' : '', true, false).draw();
                            });

                        column
                            .data()
                            .unique()
                            .sort()
                            .each(function (d, j) {
                            select.append('<option value="' + d + '" class="text-capitalize">' + d + '</option>');
                            });
                        });
                    // Adding plan filter once table initialized
                    this.api()
                        .columns(5)
                        .every(function () {
                            var column = this;
                            var select = $(
                            '<select id="userName" class="form-control text-capitalize mb-md-0 mb-2"><option value=""> Select Username </option></select>'
                        )
                            .appendTo('.userName')
                            .on('change', function () {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            column.search(val ? '^' + val + '$' : '', true, false).draw();
                            });

                        column
                            .data()
                            .unique()
                            .sort()
                            .each(function (d, j) {
                            select.append('<option value="' + d + '" class="text-capitalize">' + d + '</option>');
                            });
                        });
                }
            });
        });
    }
</script>
@endsection
