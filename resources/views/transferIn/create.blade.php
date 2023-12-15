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
                    <input type="hidden" id='oEdit' value="{{ $oEdit }}">
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
                                    <label for="trNumber">Transfer In Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="trNumber" name="trNumber" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="trDate">Date*</label>
                                    <input type="text" id="trDate" name="trDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                </div>
                            </div>                            
                        </form>
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
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-lg-3 col-md-12">
                                    <a href="{{ route('transferIn.export.excel') }}" class="btn btn-light"><i data-feather="download"></i> Downlod Template</a>
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
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('transferIn.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width:thin;">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();hitungGrandTotal();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalQty" class="col-sm-3 col-form-label titik-dua">Total QTY</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQty" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('transferIn.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel" data-trType="TRIN">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" data-trType="TRIN">Save</button>
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
@include('transferIn.addArticle')
<script type="text/javascript">
    let lockedAt = "{{ $lockDate }}";
    document.querySelector('#cmdSave').addEventListener('click',() =>{
        let element = document.getElementById('cmdSave');
        let oEdit = document.getElementById('oEdit');
        simpanData(oEdit.value);
    });

    $(document).ready(function(){           
        validateFormToast("frmAdd");
        $('#trDate').val(currentDate);
        isiArticle('trArticle');

        $('#frmExcel').on('submit', function(event){
            $('#message').html('');
            event.preventDefault();
            $.ajax({
                url:"{{ route('transferIn.import.excel') }}",
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
                    // console.log(data.dataDetail);
                    // console.log(data.status);
                    if(data.status == 1){
                        Swal.fire({
                            title: "Proses validasi...",
                            icon: "warning",
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                        })

                        let timerId = setInterval(() => checkVariable(), 1000);
                        function checkVariable() {
                            if (dataArticle.length > 0) {
                                clearInterval(timerId);
                                for(let i=0;i<data.dataDetail.length;i++){
                                    add_new_row_edit(data.dataDetail[i].article_code,data.dataDetail[i].qty,data.dataDetail[i].uom,data.dataDetail[i].uom_member,'');
                                    if (i==(data.dataDetail.length-1)){
                                            $("#uploadExcel").removeAttr('disabled');
                                            show_msg(data.title, data.message, data.alert);
                                            $(".loading-spinner-container").removeClass("-show");
                                            swal.close();
                                    }
                                }
                            }
                        }

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
                    // Swal.fire('Error..',err.errors.file[0],'error');
                    Swal.fire('Error..',err.message,'error');
                    $(".loading-spinner-container").removeClass("-show");
                }
            })
        });
    });

    $("#uploadExcel").click(function(){
        if (!$("#frmExcel")[0].checkValidity()){
            $("#frmExcel").submit();
        }else{
            $(".loading-spinner-container").addClass("-show");
            $("#uploadExcel").attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            $("#frmExcel").submit();
        }
    });
    
</script>
@endsection