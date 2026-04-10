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
                                <div class="form-group col-md-2">
                                    <label for="trNumber">Transfer In Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="trNumber" name="trNumber" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="trDate">Date*</label>
                                    <input type="text" id="trDate" name="trDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="noReference">Reference No*</label>
                                    <input type="text" id="noReference" name="noReference" class="form-control" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2 d-none">
                                    <label class="form-label" for="trOutNumber">Transfer Out Number</label>
                                    <select class="select2 form-control" id="trOutNumber" name="trOutNumber">
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="locationCode">Location from</label>
                                    <select class="select2 form-control" id="locationCode" name="locationCode" required>
                                        <option value=""></option>
                                        @foreach($locations as $val)
                                            <option value="{{$val->location_code}}" >{{$val->location_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="thirdParty">Supplier/Customer*</label>
                                    <select class="select2 form-control" id="thirdParty" name="thirdParty" required>
                                        <option value=""></option>
                                        @foreach($thirdParties as $val)
                                            <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
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
                    <h4 class="card-title">Article</h4>
                </div>
                
                <div class="card-body" >
                    <hr>
                    {{-- <div class="d-none"> --}}
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
                                        <button type="button" class="btn btn-light" id ="cmdDownload" name="cmdDownload"><i class="fa fa-download"></i> Download Template</button>
                                        <button type="button" class="btn btn-primary" id="uploadExcel">
                                            <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                            <span class="align-middle d-sm-inline-block d-none" >Upload Excel</span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                    {{-- </div> --}}
                    <hr style="margin-top: 0px;">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('transfer.transferIn.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width:thin;">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev d-none" type="button" id="addNewRow" onclick="add_new_row();hitungGrandTotal();disabledEnabledSelect2();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                {{-- <label for="totalQty" class="col-sm-3 col-form-label titik-dua">Total QTY</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQty" disabled />
                                </div> --}}
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
@include('transfer.transferIn.addArticle')
<script type="text/javascript">
    
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        $('#trDate').val(currentDate);
        // isiArticle('trArticle');

        // locationTo.html("{!! $locationTo !!}");
        dataLocationTo = "{!! $locationTo !!}";

        setTimeout(function () {
            $(".loading-spinner-container").addClass("-show");
        }, 500);

        timerId1= setInterval(() => checkVariable(), 1000);

        function checkVariable() {
            if (dataLocationTo.length > 0) {
                clearInterval(timerId1);
                $(".loading-spinner-container").removeClass("-show");
            }
        }

        $('#frmExcel').on('submit', function(event){
            event.preventDefault();
            
            $('#message').html('');
            let thirdPartyExcel = thirdParty.val();

            let formData = new FormData(this);
            
            if (thirdPartyExcel) {
                formData.append('thirdPartyExcel', thirdPartyExcel);
            }

            $.ajax({
                url:"{{ route('transferIn.import.excel') }}",
                method:"POST",
                data: formData,
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
                                    add_new_row_edit(data.dataDetail[i].article_code,data.dataDetail[i].qty,data.dataDetail[i].uom,data.dataDetail[i].uom_member,'',data.dataDetail[i].location_code);
                                    if (i==(data.dataDetail.length-1)){
                                            $("#uploadExcel").removeAttr('disabled');
                                            show_msg(data.title, data.message, data.alert);
                                            $(".loading-spinner-container").removeClass("-show");
                                            swal.close();
                                    }
                                }
                                thirdParty.attr('disabled','disabled');
                            }
                        }

                    }

                    if(data.status == 0){
                        for(let i = 0; i < data.message.length; i++) {
                            show_msg(data.title, data.message[i], data.alert);
                        }
                        $("#uploadExcel").removeAttr('disabled');
                        swal.fire("warning",data.pesan,"warning");
                        $(".loading-spinner-container").removeClass("-show");
                    }
                },
                error: function(xhr, status, error) {
                    let err = JSON.parse(xhr.responseText);
                    // Swal.fire('Error..',err.errors.file[0],'error');
                    $("#uploadExcel").removeAttr('disabled');
                    Swal.fire('Error..',err.message,'error');
                    $(".loading-spinner-container").removeClass("-show");
                }
            })
        });
        
    });

    thirdParty.change(function(e){
        e.preventDefault();
        $("#addNewRow").addClass('d-none');
        isiArticleByThirdParty('trArticleThirdParty',thirdParty.val());
    })

    document.querySelector('#cmdSave').addEventListener('click',() =>{
        let element = document.getElementById('cmdSave');
        let oEdit = document.getElementById('oEdit');
        simpanData(oEdit.value);
    });
    
    $("#uploadExcel").click(function(){
        if(thirdParty.val()){
            if (!$("#frmExcel")[0].checkValidity()){
                $("#frmExcel").submit();
            }else{
                $(".loading-spinner-container").addClass("-show");
                $("#uploadExcel").attr('disabled','disabled');
                $('.disabled-el').removeAttr('disabled');
                $("#frmExcel").submit();
                // removeAllChildDivs("article_row");
            }
        }else{
            Swal.fire("Warning","Pilih dulu supplier/customer","warning");
        }
    });

    $("#cmdDownload").click(function(){
        let thirdPartyExcel = thirdParty.val();
        if(thirdPartyExcel){
            let url = "{{ route('transferIn.export.excel', ['thirdParty'=>':thirdPartyExcel']) }}";
            url = url.replace('%3AthirdPartyExcel', thirdPartyExcel);
            url = url.replace(/\amp;/g,'');
            window.location.href = url;
        }else{
            Swal.fire("Warning","Pilih dulu supplier/customer","warning");
        }
    });

    function removeAllChildDivs(objId) {
        const parentElement = document.getElementById(objId);
        if (parentElement) {
            parentElement.innerHTML = ""; // This removes all child elements
        }
    }
    
</script>
@endsection