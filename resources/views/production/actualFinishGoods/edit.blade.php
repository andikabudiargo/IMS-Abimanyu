@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusPrd }}</h4>
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
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="prdNumber">Production Number</label>
                                    <input type="text" id="prdNumber" name="prdNumber" value="{{ $header->prod_code }}" class="form-control form-control-sm disabled-el" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="wosNumber">WOS Number</label>
                                    <input type="text" id="wosNumber" name="wosNumber" value="{{ $header->wo_code }}" class="form-control form-control-sm disabled-el" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="wosDate">Date*</label>
                                    <input type="text" id="wosDate" name="wosDate" value="{{ $header->prod_date }}" class="form-control" placeholder="DD-MM-YYYY" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="shift">Shift*</label>
                                    <input type="text" id="shift" name="shift" value="{{ $header->prod_shift }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="group">Group*</label>
                                    <input type="text" id="group" name="group" value="{{ $header->prod_group }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="wosTime">Start Time*</label>
                                    <input type="text" id="wosTime" name="wosTime" value="{{ $header->start_time }}" class="form-control" placeholder="HH:MM" disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="workingHour">Working Hour*</label>
                                    <input type="text" id="workingHour" name="workingHour" value="{{ $header->working_hour }}" class="form-control numeral-mask-satuan text-right" maxlength="2" disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="efficiency">Efficiency*</label>
                                    <input type="text" id="efficiency" name="efficiency" value="{{ $header->efficiency ? $header->efficiency : '95' }}" class="form-control numeral-mask-satuan text-right" maxlength="3" disabled />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sprayBooth">Spray booth</label>
                                        <select class="select2 form-control" id="sprayBooth" name="sprayBooth" required disabled>
                                            <option value=""></option>
                                            @foreach($arrSprayBooth as $key=>$val)
                                                <option value="{{ $key }}" {{ $header->spray_booth == $key ? 'selected' : '' }}>{{ $val }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                        <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" id="aprdNumber" name="aprdNumber" value="oki"/>
                            <div class="form-row">
                                <div class="col-lg-3 col-md-12">
                                    <div class="form-group">
                                        <button type="button" class="btn btn-info" id ="cmdDownloadTemplate" name="cmdDownloadTemplate"><i data-feather="download"></i> Downlod Template</button>
                                    </div>
                                </div>
                            </div>
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
                            <br>
                            @include('production.actualFinishGoods.headerColumn')
                            <div class="" id="article_row" style="max-height: 20rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-start align-items-end mt-75">
                    </div>
                    {{-- @include('production.actualFinishGoods.summary') --}}
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('production.actualFinishGoods.index') }}" class="btn btn-light">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusPrd =='POSTED WO' || $statusPrd =='INPUT FG')
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && ($statusPrd =='POSTED WO' || $statusPrd =='INPUT FG'))
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            @if($val->status == true)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-{{ $val->statusapprove == 1 ? 'success':'warning' }} mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="{{ $val->statusapprove == 1 ? 'check':'x' }}" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">{{ $val->statusapprove == 1 ? 'Approve':'Decline' }}-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-danger mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="x" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->petugas }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
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
      
    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

</style>
@endsection
@section('scripts')
@include('production.actualFinishGoods.addArticle')
<script type="text/javascript">
    const dWosNumber = $('#wosNumber');
    const dWosDate = $('#wosDate');
    const dShift = $('#shift');
    const dGroup = $('#group');
    const dWosTime = $('#wosTime');
    const dWorkingHour = $('#workingHour');
    const dEfficiency = $('#efficiency');
    const dNote = $('#note');
    const approveBtn = document.querySelector('#cmdApprove');
    const dPrdNumber = $('#prdNumber');

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            let prdNumber = $('#prdNumber').val();
            approve(prdNumber,'cmdApprove');
        },{ once:true});
    }

    $(document).ready(function(){           
        validateFormToast("frmAdd");
        let detail = {!!  $details !!};
        for(let i=0;i< detail.length;i++){
            soCode = detail[i].so_code;
            articleId= detail[i].article_code;
            articleCode = detail[i].article;
            articleRm = detail[i].article_rm_code;
            qtySo = detail[i].so_qty; //belum ada
            uom = 'PCS';
            planQtyFresh = detail[i].act_qty_fresh;
            planQtyRepaint = detail[i].act_qty_repaint;
            planTime = detail[i].act_time;
            planTag = detail[i].act_tag;
            originTag = detail[i].origin_tag;
            qtyFg=detail[i].act_finish_goods;
            urutan = detail[i].urutan;
            tone = detail[i].tone;
            add_new_row_edit(soCode,articleCode,articleId,articleRm,qtySo,uom,planQtyFresh,planQtyRepaint,planTime,planTag,originTag,qtyFg,urutan,tone);
        }
    });   


    $("#cmdDownloadTemplate").click(function(){
        let id = dWosNumber.val();
        let prdNumber = dPrdNumber.val();
        console.log(prdNumber);
        if(id){
            let url = "{{ route('actualFinishGood.export.excel', ['wos_number'=>':id','prd_number'=>':prdNumber']) }}";
            url = url.replace('%3Aid', id);
            url = url.replace('%3AprdNumber', prdNumber);
            url = url.replace('&amp;','&');  
            window.location.href = url;
        }else{
            Swal.fire("Warning","Pilih dulu WOS number","warning");
        }

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

    $('#frmExcel').on('submit', function(event){
        $('#message').html('');
        $('#article_row').empty();
        event.preventDefault();
        $('#aprdNumber').val($('#prdNumber').val());
        $.ajax({
            url:"{{ route('actualFinishGood.import.excel') }}",
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
                        if (data.dataDetail.length > 0) {
                            clearInterval(timerId);
                            for(let i=0;i<data.dataDetail.length;i++){
                                soCode = data.dataDetail[i].so_code;
                                articleId= data.dataDetail[i].article_code;
                                articleCode = data.dataDetail[i].article;
                                articleRm = data.dataDetail[i].article_rm_code;
                                qtySo = data.dataDetail[i].so_qty; //belum ada
                                uom = 'PCS';
                                planQtyFresh = data.dataDetail[i].act_qty_fresh;
                                planQtyRepaint = data.dataDetail[i].act_qty_repaint;
                                planTime = data.dataDetail[i].act_time;
                                planTag = data.dataDetail[i].act_tag;
                                originTag = data.dataDetail[i].origin_tag;
                                // qtyFg=data.dataDetail[i].act_finish_goods;
                                urutan = data.dataDetail[i].urutan;
                                tone = data.dataDetail[i].tone;
                                qtyFg = data.dataDetail[i].qty_finish_goods;
                                
                                if(soCode == 'other'){
                                    planQtyRepaint = 0;
                                    planTag = 0;
                                }

                                add_new_row_edit(soCode,articleCode,articleId,articleRm,qtySo,uom,planQtyFresh,planQtyRepaint,planTime,planTag,originTag,qtyFg,urutan,tone);
                                if (i==(data.dataDetail.length-1)){
                                        $("#uploadExcel").removeAttr('disabled');
                                        show_msg(data.title, data.message, data.alert);
                                        $(".loading-spinner-container").removeClass("-show");
                                        swal.close();
                                        sumData();
                                }
                            }
                        }else{
                            swal.fire("warning","Data Kosong","warning");
                            $(".loading-spinner-container").removeClass("-show");        
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

    isiDariExcel=()=>{
        let awosNumber= dWosNumber.val();
        let aprdNumber= dPrdNumber.val();
        let dariExcel ='true';
        $.ajax({
            url:"{{ route('production.actualLoading.wos.detail') }}",
            method:"GET",
            data:{
                wosNumber:awosNumber,
                dariExcel:dariExcel
            },
            success:function(result){                
                if(result.length > 0 ){
                    for(let i=0;i< detail.length;i++){
                        soCode = detail[i].so_code;
                        articleId= detail[i].article_code;
                        articleCode = detail[i].article;
                        articleRm = detail[i].article_rm_code;
                        qtySo = detail[i].so_qty; //belum ada
                        uom = 'PCS';
                        planQtyFresh = detail[i].act_qty_fresh;
                        planQtyRepaint = detail[i].act_qty_repaint;
                        planTime = detail[i].act_time;
                        planTag = detail[i].act_tag;
                        originTag = detail[i].origin_tag;
                        tone = detail[i].tone;
                        add_new_row_edit(soCode,articleCode,articleId,articleRm,qtySo,uom,planQtyFresh,planQtyRepaint,planTime,planTag,originTag,tone);
                    }
                }
            },
            error: function (response) {
                Swal.fire("Warning","Get detail PO failed","warning");
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