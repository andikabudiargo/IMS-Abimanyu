@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusWo }}</h4>
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
                                    <label for="wosNumber">Wo Number</label>
                                    <input type="text" id="wosNumber" name="wosNumber" value="{{ $header->wo_code  }}" class="form-control form-control-sm disabled-el" disabled />
                                </div>
                            </div>
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
                                        <option value="malam" {{ $header->wo_shift == 'malam' ? "selected" : "" }} >Malam</option>
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
                                <div class="form-group col-md-2">
                                    <label for="efficiency">Efficiency*</label>
                                    <input type="text" id="efficiency" name="efficiency" value="{{ $header->efficiency ? $header->efficiency : '95' }}" class="form-control numeral-mask-satuan text-right" maxlength="3" required />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sprayBooth">Spray booth</label>
                                        <select class="select2 form-control" id="sprayBooth" name="sprayBooth" required>
                                            <option value=""></option>
                                            <option value="sb1" {{ $header->spray_booth == 'sb1' ? 'selected' : '' }}>Spray Booth 1</option>
                                            <option value="sb2" {{ $header->spray_booth == 'sb2' ? 'selected' : '' }}>Spray Booth 2</option>
                                            <option value="sb3" {{ $header->spray_booth == 'sb3' ? 'selected' : '' }}>Spray Booth 3</option>
                                            <option value="sb4" {{ $header->spray_booth == 'sb4' ? 'selected' : '' }}>Spray Booth 4</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-10">
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
                            @include('workingOrderSheet.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px;">
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
                    @include('workingOrderSheet.summary')
                    <br>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('workingOrderSheets.index') }}" class="btn btn-light">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusWo =='NEW')
                                            {{-- <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button> --}}
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusWo =='NEW')
                                            {{-- <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button> --}}
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
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
    const approveBtn = document.querySelector('#cmdApprove');

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            let wosNumber = $('#wosNumber').val();
            approve(wosNumber,'cmdApprove');
        },{ once:true});
    }
    
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        let detail1 = {!!  $details !!};   
        $("#addNewRow").attr('disabled','disabled')    

        insertData=(detail,callback) => {
            let jumData = detail.length;
            for(let i=0;i< jumData;i++){
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
                tone = detail[i].tone;
                let beres = add_new_row_edit(soCode,articleCode,articleRm,qtySo,uom,planQtyFresh,planQtyRepaint,planTime,planTag,originTag,tone);
                if (beres == 'beres'){
                    if (jumData - (i+1) == 0 ){
                        callback('selesai');
                    }
                }    
            }
        }

        insertData(detail1,function(result){
            if (result == 'selesai'){
                setTimeout(() => {
                    splitArticle();
                    oEdit.val('false');
                    console.log("Finish show data, status edit :" + oEdit.val())
                    $("#addNewRow").removeAttr('disabled')
                }, 10000);
            }
        });
        
    });   
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection