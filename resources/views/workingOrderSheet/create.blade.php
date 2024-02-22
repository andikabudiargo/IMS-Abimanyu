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
                                    <label for="wosNumber">Wos Number</label>
                                    <input type="text" id="wosNumber" name="wosNumber" class="form-control form-control-sm disabled-el" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="wosDate">Date*</label>
                                    <input type="text" id="wosDate" name="wosDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="shift">Shift*</label>
                                    <select class="select2 form-control" id="shift" name="shift" required>
                                        <option value=""></option>
                                        <option value="pagi">Pagi</option>
                                        <option value="siang">Siang</option>
                                        <option value="malam">Malam</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="group">Group*</label>
                                    <select class="select2 form-control" id="group" name="group" required>
                                        <option value=""></option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="O">OFFLINE</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="wosTime">Start Time*</label>
                                    <input type="text" id="wosTime" name="wosTime" class="form-control" placeholder="HH:MM" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="workingHour">Working Hour*</label>
                                    <input type="text" id="workingHour" name="workingHour" value="9" class="form-control numeral-mask-satuan text-right" maxlength="2" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="efficiency">Efficiency*</label>
                                    <input type="text" id="efficiency" name="efficiency" value="95" class="form-control numeral-mask-satuan text-right" maxlength="3" required />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="sprayBooth">Spray booth</label>
                                        <select class="select2 form-control" id="sprayBooth" name="sprayBooth" required>
                                            <option value=""></option>
                                            <option value="sb1">Spray Booth 1</option>
                                            <option value="sb2">Spray Booth 2</option>
                                            <option value="sb3">Spray Booth 3</option>
                                            <option value="sb4">Spray Booth 4</option>
                                        </select>
                                    </div>
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
                    <button class="btn btn-success btn-sm" type="button" id="cmdSort" name="cmdSort">Sort</button>
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            <br>
                            @include('workingOrderSheet.headerColumn')
                            <div class="" id="article_row" style="max-height: 24rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-start align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();" data-toggle="tooltip" data-placement="top" title="Article yang sudah ada di BOM dan di SO">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                        {{-- <button class="btn btn-primary btn-prev ml-1" type="button" id="prosesWO" onclick="prosesWO();">
                            <span class="align-middle d-sm-inline-block d-none">Proses</span>
                        </button> --}}
                    </div>
                    @include('workingOrderSheet.summary')
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
@include('workingOrderSheet.addArticle')
<script type="text/javascript">
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });


</script>
@endsection