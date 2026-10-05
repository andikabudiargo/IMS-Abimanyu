@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusTso }}</span></h4>
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
                                    <label for="tsoCode">Target SO Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="tsoCode" name="tsoCode" class="form-control disabled-el" value="{{ $header->tso_code }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="tsoDate">Date*</label>
                                    <input type="text" id="tsoDate" name="tsoDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->tso_date }}" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="tsoPeriode">Periode TSO*</label>
                                    <input type="month" id="tsoPeriode" name="tsoPeriode" class="form-control"
                                        value="{{ $header->tso_periode && $header->tso_tahun ? sprintf('%04d-%02d', $header->tso_tahun, $header->tso_periode) : '' }}" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label for="tsoName">Target SO Name*</label>
                                    <input type="text" id="tsoName" name="tsoName" class="form-control" value="{{ $header->tso_name }}" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
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
                <div class="card-body">
                    @if( (strtoupper($statusTso) != 'APPROVED') && (strtoupper($statusTso) != 'VALIDATED') )
                    <form id="frmExcel" name="frmExcel" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-row align-items-center">
                            <div class="col-lg-3 col-md-12">
                                <div class="form-group">
                                    <input type="file" class="custom-file-input" name="file"
                                        id="file" accept=".xls,.xlsx" required />
                                    <label class="custom-file-label" for="file" id="fileLabel">Choose file</label>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12 mb-1">
                                <a href="{{ route('targetSo.export.template') }}" class="btn btn-light">
                                    <i class="fa fa-download"></i> Download Template
                                </a>
                                <button type="button" class="btn btn-primary" id="uploadExcel">
                                    <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Upload Excel</span>
                                </button>
                            </div>
                        </div>
                    </form>
                    <hr style="margin-top:0">
                    @endif
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('targetSo.headerColumn')
                            <div class="" id="article_row" style="max-height: 30rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        @if( (strtoupper($statusTso) != 'APPROVED') && (strtoupper($statusTso) != 'VALIDATED') )
                        <div class="form-row mt-75">
                            <div class="col-md-12">
                                <button class="btn btn-success btn-prev" type="button" id="addNewList" onclick="listItem()">
                                    <i data-feather="upload" class="align-middle mr-sm-25 mr-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Add by customer</span>
                                </button>
    
                                <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();hitungGrandTotal();">
                                    <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                                </button>
                            </div>
                        </div>
                        {{-- <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button> --}}
                        @endif
                    </div>

                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-5 offset-md-7">
                            <div class="form-group row mb-03">
                                <label for="totalConversion" class="col-sm-5 col-form-label titik-dua">Total Konversi</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalConversion" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQtyTarget" class="col-sm-5 col-form-label titik-dua">Total QTY Target</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQtyTarget" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQtyForcast" class="col-sm-5 col-form-label titik-dua">Total QTY Forcast</label>
                                <div class="col-sm-7">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQtyForcast" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('targetSo.index') }}" class="btn btn-light">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        {{-- <button class="btn btn-danger" type="button" id="cmdDecline" name="cmdDecline">Decline</button> --}}
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( strtoupper($statusTso) == 'NEW' )
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                        @endif
                                    @else
                                        @if( strtoupper($statusTso) == 'NEW' )
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
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
</style>
@endsection
@section('scripts')
@include('targetSo.listItem')
@include('targetSo.addArticle')
<script type="text/javascript">
    const updateBtn = document.querySelector('#cmdUpdate');
    const approveBtn = document.querySelector('#cmdApprove');

    $(document).ready(function(){
        validateForm('frmAdd');
        isiArticle('tsoArticle');
        setTimeout(function () {
            $(".loading-spinner-container").addClass("-show");
        }, 500);
        timerId= setInterval(() => checkVariable(), 1000);

        // let timerId= setInterval(() => checkVariable(), 1000);
        // function checkVariable() {
        //     if (dataArticle.length > 0) {
        //         // clearInterval(timerId);
        //         $(".loading-spinner-container").addClass("-show");
        //         let detail = {!!  $details !!};
        //         let nilai = 0;
        //         for(let i=0;i<detail.length;i++){
        //             article = detail[i].article_code;
        //             qtyTarget = detail[i].qty_target;
        //             qtyForcast =  detail[i].qty_forcast;
        //             add_new_row_edit(article,qtyTarget,qtyForcast)
        //             nilai++;
        //         }
        //         if (nilai == detail.length ){
        //             $(".loading-spinner-container").removeClass("-show");
        //         }
        //     }
        // }
    });

    let detail = {!! $details !!};
    function checkVariable() {
        if (dataArticle.length > 0) {
            clearInterval(timerId);
            isiData(detail);
        }
    }

    isiData = (data) =>{
        if (data){
            for(let i=0;i<detail.length;i++){
                article = detail[i].article_code;
                qtyTarget = detail[i].qty_target;
                qtyForcast =  detail[i].qty_forcast;
                add_new_row_edit(article,qtyTarget,qtyForcast)
                if (i==(data.length-1)){
                    $(".loading-spinner-container").removeClass("-show");
                    mask_thousand_satuan();
                }
            }
        }
    }

    if (updateBtn) {
        updateBtn.addEventListener('click',() =>{
            updateData('update','cmdUpdate');
        });
    }

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            let tsoCode = $('#tsoCode').val();
            approve(tsoCode,'cmdApprove');
        },{ once:true});
    }

    $('#frmExcel').on('submit', function (e) {
        e.preventDefault();
        if (!$('#file').val()) { Swal.fire('Error..', 'File is empty!', 'error'); return; }

        $(".loading-spinner-container").addClass("-show");
        $('#uploadExcel').attr('disabled', 'disabled');

        $.ajax({
            url: "{{ route('targetSo.import.excel') }}",
            method: "POST",
            data: (() => { let fd = new FormData(this); fd.append('tsoDate', $('#tsoDate').val()); return fd; })(),
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            success: function (data) {
                $('#uploadExcel').removeAttr('disabled');
                $(".loading-spinner-container").removeClass("-show");
                if (data.status == 1 && data.dataDetail.length > 0) {
                    importRowsTargetSo(data.dataDetail);
                    clearFileInput('file');
                } else if (data.status == 0) {
                    data.message.forEach(m => show_msg(data.title, m, data.alert));
                    Swal.fire('Warning', data.pesan || 'Ada error pada data yang diupload.', 'warning');
                } else {
                    Swal.fire('Warning', 'Excel file is empty!', 'warning');
                }
            },
            error: function (xhr) {
                $('#uploadExcel').removeAttr('disabled');
                $(".loading-spinner-container").removeClass("-show");
                Swal.fire('Error..', 'Gagal mengupload file.', 'error');
            }
        });
    });

    $('#uploadExcel').on('click', function () {
        $('#frmExcel').submit();
    });

    $('#file').on('change', function () {
        let name = $(this).val().split('\\').pop() || 'Choose file';
        $('#fileLabel').text(name);
    });

    function clearFileInput(id) {
        let inp = $('#' + id);
        inp.wrap('<form>').closest('form').get(0).reset();
        inp.unwrap();
        $('#fileLabel').text('Choose file');
    }

</script>
@endsection