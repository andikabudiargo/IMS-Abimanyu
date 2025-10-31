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
                                    <label for="trNumber">Transfer Out Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="trNumber" name="trNumber" class="form-control disabled-el" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="trDate">Date*</label>
                                    <input type="text" id="trDate" name="trDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ old('trDate') }}" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3 d-none">
                                    <label class="form-label" for="trInNumber">Transfer In Number</label>
                                    <select class="select2 form-control" id="trInNumber" name="trInNumber">
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="locationFrom">Location from</label>
                                    <select class="select2 form-control" id="locationFrom" name="locationFrom" required>
                                        <option value=""></option>
                                        @foreach($locations as $val)
                                            <option value="{{$val->location_code}}" >{{$val->location_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-row d-none">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="noteSelect">Notes</label>
                                    <select class="select2 form-control" id="noteSelect" name="noteSelect">
                                        <option value=""></option>
                                        <option value="WOS SHIFT A" {{ old('noteSelect')== 'WOS SHIFT A' ? 'selected' : '' }} >WOS SHIFT A</option>
                                        <option value="WOS SHIFT B" {{ old('noteSelect')== 'WOS SHIFT A' ? 'selected' : '' }}>WOS SHIFT B</option>
                                        <option value="WOS BOOTH WERATE" {{ old('noteSelect')== 'WOS BOOTH WERATE' ? 'selected' : '' }}>WOS BOOTH WERATE</option>
                                        <option value="WOS SHIFT 2" {{ old('noteSelect')== 'WOS SHIFT 2' ? 'selected' : '' }}>WOS SHIFT 2</option>
                                        <option value="WOSH BOOTH 3-B" {{ old('noteSelect')== 'WOSH BOOTH 3-B' ? 'selected' : '' }}>WOSH BOOTH 3-B</option>
                                        <option value="Consumable SHIFT A" {{ old('noteSelect')== 'Consumable SHIFT A' ? 'selected' : '' }}>Consumable SHIFT A</option>
                                        <option value="Consumable SHIFT B" {{ old('noteSelect')== 'Consumable SHIFT B' ? 'selected' : '' }}>Consumable SHIFT B</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>

                            {{-- <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="thirdParty">Supplier/Customer*</label>
                                    <select class="select2 form-control" id="thirdParty" name="thirdParty" required>
                                        <option value=""></option>
                                        @foreach($thirdParties as $val)
                                            <option value="{{$val->kode}}">{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div> --}}
                            
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                </div>
                            </div>                            
                        </form>
                        <div class="d-none">
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
                                        <a href="{{ route('transferOut.export.excel') }}" class="btn btn-light"><i data-feather="download"></i> Downlod Template</a>
                                    </div>
                                </div>
                            </form>
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
                <div class="card-body" >
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('transfer.transferOut.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;">
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
                            <div class="form-group row mb-03 d-none">
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
                            <a href="{{ route('transferOut.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-info" type="reset" id="cmdNew" name="cmdCancel" data-trType="TROUT">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" data-trType="TROUT">Save</button>
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
@include('transfer.transferOut.addArticle')
<script type="text/javascript">
    let objToType = $('#toType');
    let objTsoCode = $('#tsoCode');
    let objTsoBox = $('#tsoBox');
    // let objNoteSelect = $('#noteSelect');
    let objNote = $('#note');
    let locationTo = $('#locationTo');
    // let thirdParty = $('#thirdParty');

    document.querySelector('#cmdSave').addEventListener('click',() =>{
        let element = document.getElementById('cmdSave');
        let oEdit = document.getElementById('oEdit');
        simpanData(oEdit.value);
    });

    $(document).ready(function(){           
        validateFormToast("frmAdd");
        $('#trDate').val(currentDate);
        isiArticle('trArticle');
        objTsoBox.hide();
        // objNote.attr('disabled','disabled');
        locationTo.html("{!! $locationTo !!}");

        $('#frmExcel').on('submit', function(event){
            $('#message').html('');
            event.preventDefault();
            $.ajax({
                url:"{{ route('transferOut.import.excel') }}",
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
                                            console.log("oki"+i)
                                            $("#uploadExcel").removeAttr('disabled');
                                            // swal.close();
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

        setTimeout(function () {
            $(".loading-spinner-container").addClass("-show");
        }, 500);
        timerId= setInterval(() => checkVariable(), 1000);

        function checkVariable() {
            if (locationTo.length > 0) {
                $(".loading-spinner-container").removeClass("-show");
            }
        }

    });


    // thirdParty.change(function(e){
    //     isiArticleByThirdParty('trArticleThirdParty',thirdParty.val());
    // })
    
    objToType.change(function(e){
        let toType=$(this).val();
        objTsoBox.hide();
        if (toType ==='prd'){
            objTsoBox.show();
            dependent = 'wos_list'
            changeSelect({
                dependent:dependent,
                obj:'tsoCode',
                url:"{{ route('dynamic.dependent') }}"            
            });
        }
    });

    objTsoCode.change(function(e){
        let tsoCode = $(this).val();    
        if (tsoCode){        
            $.ajax({
                type: "GET",
                url: "{{ route('transferOut.article.tso') }}",
                data: {
                    tsoCode:tsoCode
                },
                dataType: "json",
                success: function(data) {
                    if (data){
                        for(let i=0;i<data.length;i++){
                            add_new_row_edit(data[i].article_code,data[i].grand_total,data[i].uom,data[i].uom_member,'');
                        }
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }
    });

    // objNoteSelect.change(function(e){
    //     let optionVal = $(this).val(); 
    //     if (optionVal=='other'){
    //         objNote.removeAttr('disabled');
    //         objNote.val('');
    //     }else{
    //         objNote.attr('disabled','disabled');
    //         objNote.val(optionVal);
    //     }
    // });

</script>
@endsection