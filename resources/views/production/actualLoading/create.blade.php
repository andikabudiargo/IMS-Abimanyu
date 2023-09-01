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
                                    <input type="text" id="prdNumber" name="prdNumber" class="form-control form-control-sm disabled-el" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="wosNumber">WOS Number</label>
                                    <select class="select2 form-control" id="wosNumber" name="wosNumber" required>
                                        <option value=""></option>
                                        @foreach($listWo as $val)
                                            <option value="{{ $val->wo_code }}"   
                                                data-shift="{{ $val->wo_shift }}"
                                                data-group="{{ $val->wo_group }}"
                                                data-date="{{ $val->tanggal }}"
                                                data-start-time="{{ $val->start_time }}"
                                                data-working-hour="{{ $val->working_hour }}"
                                                data-efficiency="{{ $val->efficiency }}"
                                                data-note="{{ $val->note }}"
                                                >{{$val->wo_code}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="wosDate">Date*</label>
                                    <input type="text" id="wosDate" name="wosDate" class="form-control" placeholder="DD-MM-YYYY" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="shift">Shift*</label>
                                    <input type="text" id="shift" name="shift" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="group">Group*</label>
                                    <input type="text" id="group" name="group" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="wosTime">Start Time*</label>
                                    <input type="text" id="wosTime" name="wosTime" class="form-control" placeholder="HH:MM" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="workingHour">Working Hour*</label>
                                    <input type="text" id="workingHour" name="workingHour" class="form-control numeral-mask-satuan text-right" maxlength="2" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="efficiency">Efficiency*</label>
                                    <input type="text" id="efficiency" name="efficiency" value="95" class="form-control numeral-mask-satuan text-right" maxlength="3" required />
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-8">
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
                    {{-- <button class="btn btn-success btn-sm" type="button" id="cmdSort" name="cmdSort">Sort</button> --}}
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            <br>
                            @include('production.actualLoading.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    @include('production.actualLoading.summary')
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
@include('production.actualLoading.addArticle')
<script type="text/javascript">

    const dWosNumber=$('#wosNumber');
    const dWosDate=$('#wosDate');
    const dShift=$('#shift');
    const dGroup=$('#group');
    const dWosTime=$('#wosTime');
    const dWorkingHour=$('#workingHour');
    const dEfficiency=$('#efficiency');
    const dNote=$('#note');
    $(document).ready(function(){           
        validateFormToast("frmAdd");
    });   

    dWosNumber.change(function(){
        let value= $(this).val();
        dWosDate.val($(this).find(":selected").data("date"));
        dShift.val($(this).find(":selected").data("shift"));
        dGroup.val($(this).find(":selected").data("group"));
        dWosTime.val($(this).find(":selected").data("start-time"));
        dWorkingHour.val($(this).find(":selected").data("working-hour"));
        dEfficiency.val($(this).find(":selected").data("efficiency"));
        dNote.val($(this).find(":selected").data("note"));
        sumData();

        $.ajax({
            url:"{{ route('production.actualLoading.wos.detail') }}",
            method:"GET",
            data:{
                wosNumber:value,
            },
            success:function(result){                
                if(result.length > 0 ){
                    for(let i=0;i< result.length;i++){
                        let soCode = result[i].so_code;
                        let articleId= result[i].article_code;
                        let articleCode = result[i].article;
                        let articleRm = result[i].article_rm_code;
                        let qtySo = result[i].so_qty; //belum ada
                        let uom = 'PCS';
                        let planQtyFresh = result[i].plan_qty_fresh;
                        let planQtyRepaint = result[i].plan_qty_repaint;
                        let planTime = result[i].plan_time;
                        let planTag = result[i].plan_tag;
                        let originTag = result[i].origin_tag;
                        let tone = result[i].tone;
                        add_new_row(soCode,articleCode,articleId,articleRm,qtySo,uom,planQtyFresh,planQtyRepaint,planTime,planTag,originTag,tone);
                    }
                }
            },
            error: function (response) {
                Swal.fire("Warning","Get detail PO failed","warning");
            }
        })

    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection