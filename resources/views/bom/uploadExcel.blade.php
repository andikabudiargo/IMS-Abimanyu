@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-row">
                                <div class="col-lg-3 col-md-12">
                                    <div class="form-group">
                                        <div>
                                            <input type="file" class="custom-file-input" name="file" id="file" required/>
                                            <label class="custom-file-label" for="file">Choose file</label>
                                        </div>
                                        
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12">
                                    <button type="button" class="btn btn-primary">
                                        <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none" id="uploadExcel">Upload Excel</span>
                                    </button>
                                    <a href="{{ route('bom.export.template') }}" class="btn btn-light"><i data-feather="download"></i> Downlod Template Excel</a>
                                </div>
                            </div>
                            <hr>
                        </form>

                        <!-- Summary dengan Icons -->
                        <div class="row g-3">
                            <div class="col-xl-3 col-md-6">
                                <div class="card border-start border-primary border-4">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="card-title text-primary" id="totalRecords">0</h5>
                                                <p class="card-text text-muted mb-0">
                                                    <i class="fas fa-database me-2"></i>Total Data
                                                </p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-database fa-2x text-primary opacity-25"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card border-start border-danger border-4">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="card-title text-danger" id="totalErrors">0</h5>
                                                <p class="card-text text-muted mb-0">
                                                    <i class="fas fa-exclamation-circle me-2"></i>Total Error
                                                </p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-exclamation-circle fa-2x text-danger opacity-25"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row mt-75">
                            <div class="col-md-12">
                                <a href="{{ route('boms.index') }}" class="btn btn-primary d-none" id="cmdProcess">Process</a>
                                <a href="{{ route('boms.index') }}" class="btn btn-light">Back</a>
                            </div>
                        </div>
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
    </div>
</section>
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">

    $(document).ready(function(){           
        $('#frmExcel').on('submit', function(event){
            $('#message').html('');
            event.preventDefault();
            $.ajax({
                url:"{{ route('bom.upload.excel') }}",
                method:"POST",
                data: new FormData(this),
                dataType:"json",
                contentType:false,
                cache:false,
                processData:false,
                beforeSend:function(){
                    $('#uploadExcel').attr('disabled','disabled');
                },
                success:function(data){
                    if(data.status == 1){
                        showList();

                        // Swal.fire({
                        //     title: "Proses validasi...",
                        //     icon: "warning",
                        //     showConfirmButton: false,
                        //     didOpen: () => {
                        //         Swal.showLoading();
                        //     },
                        // })

                    }

                    if(data.status == 0){
                        for(let i = 0; i < data.message.length; i++) {
                            show_msg(data.title, data.message[i], data.alert);
                        }
                        swal.fire("warning",data.pesan,"warning");
                        $(".loading-spinner-container").removeClass("-show");
                    }
                },
                error: function(xhr, status, error) {
                    let err = JSON.parse(xhr.responseText);

                    if(err.message == 'The given data was invalid.'){
                        let errorText = err.message+"<br>"+err.errors['headers'][0];
                        Swal.fire('Error..',errorText,'error');
                    }else{
                        // Swal.fire('Error..',err.errors.file[0],'error');
                        Swal.fire('Error..',err.message,'error');
                    }

                    $(".loading-spinner-container").removeClass("-show");
                }
            })
        });
    });

    $("#uploadExcel").click(function(){
        if ($('#detailedTable tr').length >0){
            let table= $('#detailedTable').DataTable();
            table.destroy();
            $('#detailedTable tbody > tr').remove();
            $("#detailedTable thead > tr").remove();
        }
        
        if($('#file').val()){
            if (!$("#frmExcel")[0].checkValidity()){
                $("#frmExcel").submit();
            }else{
                $(".loading-spinner-container").addClass("-show");
                $("#uploadExcel").attr('disabled','disabled');
                $('.disabled-el').removeAttr('disabled');
                Swal.fire({
                    title: "Proses validasi...",
                    icon: "warning",
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    },
                })
                $("#frmExcel").submit();
            }
        }
    });

    const showList = (searchBom) => {
        if ($('#detailedTable tr').length >0){
            let table= $('#detailedTable').DataTable();
            table.destroy();
            $('#detailedTable tbody > tr').remove();
            $("#detailedTable thead > tr").remove();
        }
        showDataTables({
            tableId:"detailedTable",
            route:"{{ route('bom.upload.excel.list') }}",
            kolom:{!! $kolom !!},
            // arrColPrint:[1,2,3,4,5,6,7,8,9,10,11,12,13,14],
            columnDefs :[
                // { width: '5%', targets: 0 },
            ],
            dataSearch:  {
                searchBom:searchBom,
            },
            initComplete: function() {
                swal.close();
                $(".loading-spinner-container").removeClass("-show");
                if(!checkSpecificColumnByIndex(7)){
                    $('#cmdProcess').removeClass('d-none');
                }
            },
            orderColumn:[[ 1, 'asc' ],[ 7, 'desc' ]],
            excelFileName:'bom_upload'
        });
    }

    function checkSpecificColumnByIndex(columnIndex) {
        let table = $('#detailedTable').DataTable();
        let columnData = table.column(columnIndex).data();
        let hasData = false;
        let adaIsinya=0;
        let kosong=0;

        columnData.each(function(value, index) {
            if (value !== null && value !== undefined && value.toString().trim() !== '') {
                // hasData = true;
                adaIsinya++;
                // return false; // Keluar dari loop
            }else{
                kosong++;
            }
        });

        $('#totalRecords').text(adaIsinya+kosong);
        $('#totalErrors').text(adaIsinya);

        if (adaIsinya) {
            return true;
        } else {
            return false;
        }
    }

    function getServerSideRowInfo() {

        let table = $('#detailedTable').DataTable();
        let info = table.page.info();

        $('#totalRecords').text(info.recordsTotal);
        $('#displayedRecords').text(info.recordsTotal);
        $('#currentPageRecords').text(table.rows({ page: 'current' }).count());
        $('#selectedRecords').text(table.rows({ selected: true }).count());

        console.log('Data dari server:');
        console.log('- Total records: ' + info.recordsTotal);
        console.log('- Filtered records: ' + info.recordsDisplay);
        console.log('- Current page rows: ' + table.rows().count());
    }
    
</script>
@endsection