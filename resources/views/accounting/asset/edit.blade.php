@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="accountPayable-create">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" action="{{ route('asset.update',['id'=> $header->id])}}" method="post" autocomplete="off" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-8 col-12">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="assetNumber">Nomor Asset</label> <small class="text-muted"> automatic</small>
                                            <input type="text" id="assetNumber" name="assetNumber" class="form-control text-hitam disabled-el" value="{{ old('assetNumber',$header->asset_number) }}" disabled />
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="assetCoa">COA*</label>
                                            <select class="select2 form-control" id="assetCoa" name="assetCoa" required>
                                                <option value=""></option>
                                                @foreach($accounts as $val)
                                                    <option value="{{ $val->account }}" {{ old('assetCoa') == $val->account ? 'selected' : '' }} >{{$val->account}} - {{$val->description}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="voucherNumber">Voucher Number*</label>
                                            <select class="select2 form-control" id="voucherNumber" name="voucherNumber" required>
                                            </select>
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <input type="hidden" id="voucherId" name="voucherId" class="disabled-el" value="{{ old('voucherId') }}" />
                                            <input type="hidden" id="assetDescription" name="assetDescription" class="disabled-el" value="{{ old('assetDescription') }}" />
                                            <label for="assetName">Asset Name</label>
                                            <select class="select2 form-control" id="assetName" name="assetName" required>
                                            </select>
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label for="tanggalPembelian">Tanggal Pembelian*</label>
                                            <input type="text" id="tanggalPembelian" name="tanggalPembelian" class="form-control disabled-el" value="{{ old('tanggalPembelian') }}" disabled />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="hargaBeli">Harga Beli*</label>
                                            <input type="text" id="hargaBeli" name="hargaBeli" class="form-control numeral-mask disabled-el" value="{{ old('hargaBeli') }}" disabled />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="qtyBeli">QTY*</label>
                                            <input type="text" id="qtyBeli" name="qtyBeli" class="form-control numeral-mask disabled-el" value="{{ old('qtyBeli') }}" disabled />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="statusBeli">Status*</label>
                                            <select class="select2 form-control" id="statusBeli" name="statusBeli" required>
                                                <option value=""></option>
                                                <option value="baru">Baru</option>
                                                <option value="bekas">Bekas</option>
                                            </select>
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label class="form-label" for="supplier">Supplier</label>
                                            <select class="select2 form-control disabled-el" id="supplier" name="supplier" disabled>
                                                <option value=""></option>
                                                @foreach($supps as $val)
                                                    <option value="{{ $val->kode }}" {{ old('supplier') == $val->kode ? 'selected' : '' }}>{{$val->kode}} - {{$val->nama}}</option>
                                                @endforeach
                                            </select>
                                        </div> 
                                        <div class="form-group col-md-6">
                                            <label for="departement">Departement</label>
                                            <select class="select2 form-control disabled-el" id="departement" name="departement" disabled>
                                                <option value=""></option>
                                                @foreach($depts as $val)
                                                    <option value="{{ $val->code }}" {{ old('departement') == $val->code ? 'selected' : '' }} >{{$val->name}}</option>
                                                @endforeach
                                            </select>
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="invoiceNumber">No Invoice</label>
                                            <input type="text" id="invoiceNumber" name="invoiceNumber" class="form-control disabled-el" value="{{ old('invoiceNumber') }}" disabled />
                                        </div> 
                                        <div class="form-group col-md-6">
                                            <label for="akunAssetTetap">Akun Asset Tetap</label>
                                            <input type="text" id="akunAssetTetap" name="akunAssetTetap" class="form-control disabled-el" value="{{ old('akunAssetTetap') }}" disabled />
                                        </div> 
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input opsiPenyusutan" id="penyusutan" name="penyusutan" {{ old('penyusutan') == 't' ? 'checked' : '' }} disabled/>
                                        <label class="custom-control-label" for="penyusutan">Dengan Penyusutan</label>
                                    </div>
                                    <br>
                                    <div id="penyusutanForm" class="d-none">
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="akunAkumulasiPenyusutan">Akun Akumulasi Penyusutan*</label>
                                                <select class="select2 form-control disabled-el" id="akunAkumulasiPenyusutan" name="akunAkumulasiPenyusutan" disabled>
                                                    <option value=""></option>
                                                    @foreach($accountPenyusutan as $val)
                                                        <option value="{{ $val->account }}" {{ old('akunAkumulasiPenyusutan') == $val->account ? 'selected' : '' }} >{{$val->account}} - {{$val->description}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="akunPenyusutan">Akun Penyusutan*</label>
                                                <select class="select2 form-control disabled-el" id="akunPenyusutan" name="akunPenyusutan" disabled>
                                                    <option value=""></option>
                                                    @foreach($accountPenyusutan as $val)
                                                        <option value="{{ $val->account }}" {{ old('akunPenyusutan') == $val->account ? 'selected' : '' }} >{{$val->account}} - {{$val->description}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="kelompokPenyusutan">Kelompok Penyusutan*</label>
                                                <select class="select2 form-control" id="kelompokPenyusutan" name="kelompokPenyusutan">
                                                    <option value=""></option>
                                                    @foreach($kelompoks as $val)
                                                        <option value="{{ $val->kode }}" 
                                                            data-nilai-penyusutan = "{{ $val->nilai_penyusutan }}" 
                                                            data-masa-manfaat = "{{ $val->masa_manfaat }}" 
                                                            {{ old('kelompokPenyusutan') == $val->kode ? 'selected' : '' }} >{{$val->nama}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="nilaiPenyusutanPerTahun">Nilai Penyusutan/tahun</label>
                                                <input type="text" id="nilaiPenyusutanPerTahun" name="nilaiPenyusutanPerTahun" value="{{ old('nilaiPenyusutanPerTahun') }}" class="form-control disabled-el numeral-mask-digit" disabled/>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="masaManfaat">Masa Manfaat(tahun)</label>
                                                <input type="text" id="masaManfaat" name="masaManfaat" value="{{ old('masaManfaat') }}" class="form-control disabled-el numeral-mask" disabled/>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="invoiceDate">Tanggal Invoice</label>
                                                <input type="text" id="invoiceDate" name="invoiceDate" class="form-control tanggalInput disabled-el" placeholder="DD-MM-YYYY" value="" disabled/>
                                            </div> 
                                            <div class="form-group col-md-4">
                                                <label for="lastDate">Tanggal Akhir Penyusutan</label>
                                                <input type="text" id="lastDate" name="lastDate" class="form-control tanggalInput disabled-el" placeholder="DD-MM-YYYY" value="" disabled/>
                                            </div> 
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="metodePenyusutan">Metode Penyusutan</label>
                                                <input type="text" id="metodePenyusutan" name="metodePenyusutan" class="form-control disabled-el"  value="Straight Line" disabled/>
                                            </div> 
                                            <div class="form-group col-md-4">
                                                <label for="akumulasiPenyusutan">Akumulasi Penyusutan</label>
                                                <input type="text" id="akumulasiPenyusutan" name="akumulasiPenyusutan" class="form-control disabled-el numeral-mask-digit" value="" disabled/>
                                            </div> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('asset.index') }}" class="btn btn-light">Back</a>
                                    {{-- <button class="btn btn-info" type="reset" id="cmdNew" name="cmdCancel">New</button> --}}
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                </div>
                            </div>
                        </form>
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
</style>
@endsection
@section('scripts')
<script type="text/javascript">    
    
    $(document).ready(function(){
        validateFormToast("frmAdd");
        let opsiPenyusutan = $("input[name='opsiPenyusutan']:checked"). val();            
    });

    $('#assetCoa').change(function(){
        let value = $(this).val();
        let edit = 'false';
        $('#assetName').empty().append('<option selected="selected" value=""></option>');
        $('#penyusutan').attr('disabled','disabled');
        clearData();
        if(value){
            $.ajax({
                url:"{{ route('get.list.ap') }}",
                method:"GET",
                data:{
                    value:value,
                    edit:edit
                },
                success:function(result){
                    $('#voucherNumber').html(result);
                },
                error: function (response) {
                    Swal.fire("Warning","Get list AP failed","warning");
                }
            })
        }
    });

    $('#voucherNumber').change(function(){
        let value = $(this).val();
        let account = $('#assetCoa').val();
        let edit = 'false';
        $('#assetName').empty().append('<option selected="selected" value=""></option>');
        $('#voucherId').val('');
        $('#assetDescription').val('');
        $('#penyusutan').attr('disabled','disabled');
        clearData();
        if(value){
            $.ajax({
                url:"{{ route('get.list.asset') }}",
                method:"GET",
                data:{
                    value:value,
                    account:account,
                    edit:edit
                },
                success:function(result){
                    $('#assetName').html(result);
                },
                error: function (response) {
                    Swal.fire("Warning","Get list AP failed","warning");
                }
            })
        }
    });

    $('#assetName').change(function(){
        let kode = $(this).val();
        let apNumber = $(this).find(":selected").data("ap-number"); 
        let apId = $(this).find(":selected").data("id");
        let apAccount = $(this).find(":selected").data("account");
        let apValue = $(this).find(":selected").data("value");
        let supplier = $(this).find(":selected").data("supplier");
        let dept = $(this).find(":selected").data("dept");
        let apDate = $(this).find(":selected").data("ap-date");
        let invNumber = $(this).find(":selected").data("inv-number");
        let assetDescA = $(this).find(":selected").data("asset-description");
        let qty = $(this).find(":selected").data("qty");

        $('#tanggalPembelian').val(apDate);
        $('#hargaBeli').val(apValue);
        $('#qtyBeli').val(qty);
        $('#supplier').val(supplier).trigger('change');
        $('#departement').val(dept).trigger('change');
        $('#akunAssetTetap').val(apAccount);
        $('#invoiceNumber').val(invNumber);
        $('#voucherId').val(apId);
        $('#assetDescription').val(assetDescA);

        if(kode){
            $('#penyusutan').removeAttr('disabled');
        }else{
            $('#penyusutan').attr('disabled','disabled');
        }

        mask_thousand();        
    });

    clearData = () => {
        $('#tanggalPembelian').val('');
        $('#hargaBeli').val('');
        $('#qtyBeli').val('');
        $('#supplier').val('').trigger('change');
        $('#departement').val('').trigger('change');
        $('#akunAssetTetap').val('');
        $('#invoiceNumber').val('');
        $('#voucherId').val('');
        $('#assetDescription').val('');
        $('#penyusutan').prop('checked', false);
        clearDataPenyusutan();
    }


    $('#kelompokPenyusutan').change(function(){
        let nilaiPenyusutan = $(this).find(":selected").data("nilai-penyusutan");
        let masaManfaat = $(this).find(":selected").data("masa-manfaat"); 
        let tanggalPembelian = $('#tanggalPembelian').val(); 
        $('#nilaiPenyusutanPerTahun').val(nilaiPenyusutan);
        $('#masaManfaat').val(masaManfaat);
        $('#invoiceDate').val(tanggalPembelian);

        let dates = tanggalPembelian.split("-");
        let dt = new Date(dates[1]+"-"+dates[0]+"-"+dates[2]);
        let newYear = parseInt(dt.getFullYear())+parseInt(masaManfaat); 
        let lastDate = dates[0]+"-"+dates[1]+"-"+ newYear; 
        $('#lastDate').val(lastDate);
        let hargaBeli = $('#hargaBeli').val().replace(/,/gi, '') || 0;
        $('#akumulasiPenyusutan').val((hargaBeli/masaManfaat).toFixed(2));

        mask_thousand();       
        mask_thousand_digit(2);        
    });

    getDetailPenyusutan = () => {
        let akunAsetTetap = $('#akunAssetTetap').val();
        let edit = 'false';
        if(akunAsetTetap){
            $.ajax({
                url:"{{ route('get.akun.mapping') }}",
                method:"GET",
                data:{
                    value:akunAsetTetap,
                    edit:edit
                },
                success:function(result){
                    $('#akunAkumulasiPenyusutan').val(result[0].akun_akumulasi_penyusutan).trigger('change');
                    $('#akunPenyusutan').val(result[0].akun_penyusutan).trigger('change');
                },
                error: function (response) {
                    Swal.fire("Warning","Get list AP failed","warning");
                }
            })
        }
    }

    clearDataPenyusutan = () => {
        $('#akunAkumulasiPenyusutan').val('').trigger('change');
        $('#akunPenyusutan').val('').trigger('change');
        $('#kelompokPenyusutan').val() ? $('#kelompokPenyusutan').val('').trigger('change') : '';
        $('#nilaiPenyusutanPerTahun').val('');
        $('#masaManfaat').val('');
        $('#invoiceDate').val('');
        $('#lastDate').val('');
        // $('#penyusutanForm').addClass('d-none');
        $('#kelompokPenyusutan').removeAttr('required');
    }

    $('.opsiPenyusutan').click(function(){
        let id = $(this).attr("id");
        if( $('#'+id).is(':checked') ){
            $('#penyusutanForm').removeClass('d-none');
            $('#kelompokPenyusutan').attr('required','required');
            getDetailPenyusutan();
        }else{
            $('#penyusutanForm').addClass('d-none');
            $('#kelompokPenyusutan').removeAttr('required');
            clearDataPenyusutan();
        }
    })

    // apDate = $('.tanggalInput');
    // if (apDate.length) {
    //     apDate.flatpickr({
    //         dateFormat: "d-m-Y"
    //     });
    // }

    $("#cmdSave").click(function(){  
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
            $('#cmdSave').removeAttr('disabled');
        }else{
            $('#cmdSave').attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit();
        }
    })
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection