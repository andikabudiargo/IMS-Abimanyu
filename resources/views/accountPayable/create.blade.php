@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ Session::get('status') ? Session::get('status'): $status }}</span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" action="{{ route('ap.store') }}" method="post" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="apNumber">AP Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="apNumber" name="apNumber" class="form-control text-hitam disabled-el" value="{{ old('apNumber', Session::get('details') ? Session::get('details')->ap_number :"") }}" disabled />
                                    <input type="hidden" id="recNumberSave" name="recNumberSave" class="form-control text-hitam disabled-el" value="" />
                                </div>
                                {{-- <div class="form-group col-md-3">
                                    <label for="profInvoice">Prof Invoice</label>
                                    <input type="text" id="profInvoice" name="profInvoice" class="form-control text-hitam disabled-el" value="{{ old('profInvoice', Session::get('details') ? Session::get('details')->proforma_inv_number :"") }}" disabled />
                                </div> --}}
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required>
                                        <option value="">All</option>
                                        @foreach($supps as $val)
                                            <option value="{{ $val->kode }}" {{ old('supplier',Session::get('details') ? Session::get('details')->supplier_id :"") == $val->kode ? 'selected' : '' }} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="poNumber">PO Number*</label>
                                    <select class="select2 form-control" id="poNumber" name="poNumber" required>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency">
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{ old('currency',Session::get('details') ? Session::get('details')->currency : '' ) == $val ? 'selected' : '' }} >{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="rate">Rate</label>
                                    <input type="text" id="rate" name="rate" value="{{ old('rate',Session::get('details') ? Session::get('details')->kurs :'') }}" class="form-control numeral-mask text-right"/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="accountBasisA">COA Basis Amount*</label>
                                    <select class="select2 form-control w-100" id="accountBasisA" name="accountBasisA" required>
                                        <option value="">Choose option</option>
                                        @foreach($accountBa as $val)
                                            <option value="{{ $val->account }}" {{ old('account',Session::get('details') ? Session::get('details')->account_ba : '') == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                {{-- <p>Rec.Number/LPB*</p> --}}
                                <div class="col-sm-12">
                                    <div class="card-datatable table-responsive pt-0">
                                      <table class="table table-bordered" id="listOfLpb">
                                          <thead>
                                            <tr>
                                                <th scope="col" width="10%">Check</th>
                                                <th scope="col" width="30%">LPB Number</th>
                                                <th scope="col" width="30%">Date</th>
                                                <th scope="col" width="30%">DO Number</th>
                                            </tr>
                                          </thead>
                                          <tbody>
                                          </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button class="btn btn-primary" type="button" id="cmdSubmit" name="cmdSubmit">Submit</button>
                                </div>
                            </div>
                            <hr>
                            <div class="form-row">
                                <h4>Detail receiving</h4>
                                <div class="col-sm-12">
                                    <div class="card-datatable table-responsive pt-0">
                                      <table class="table table-bordered" id="listOfRec">
                                          <thead>
                                            <tr>
                                                <th scope="col" width="20%">Article Code</th>
                                                <th scope="col" width="40%">Description</th>
                                                <th scope="col" width="10%">UOM</th>
                                                <th scope="col" width="10%">Qty</th>
                                                <th scope="col" width="10%">Price</th>
                                                <th scope="col" width="10%">Total</th>
                                            </tr>
                                          </thead>
                                          <tbody>
                                          </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-end mt-75">
                                <div class="col-md-4"></div>
                                <div class="col-md-5">
                                    <div class="form-group row mb-03">
                                        <label for="basisAmount" class="col-sm-3 col-form-label titik-dua">Bruto</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold disabled-el" id="basisAmount" name="basisAmount" disabled />
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPN" class="col-sm-3 col-form-label titik-dua">Discount </label>
                                        {{-- <div class="col-sm-2" style="padding-right: 0rem;">
                                            <input type="text" class="form-control text-right font-weight-bold" id="persenDiscount" maxlength="2"/>
                                        </div> --}}
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold disabled-el" id="totalDiscount" name="totalDiscount" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPN" class="col-sm-3 col-form-label titik-dua">PPN <span id="nilaiPPN"></span> </label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold disabled-el" id="totalPPN"  name="totalPPN" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPH" class="col-sm-3 col-form-label titik-dua">PPH <span>22</span> </label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold disabled-el" id="totalPPH" name="totalPPH" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="grandTotal" class="col-sm-3 col-form-label titik-dua">Netto</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold disabled-el" id="grandTotal" name="grandTotal" disabled/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- <div class="form-row">                                    
                                <div class="form-group col-md-6 d-none">
                                    <label for="suppCode">Supplier</label>
                                    <input type="text" id="suppCode" name="suppCode" class="form-control disabled-el" value="{{ old('suppCode') }}" disabled  />
                                </div>
                                <div class="form-group col-md-6 d-none">
                                    <label for="poNumberDet">PO Number</label>
                                    <input type="text" id="poNumberDet" name="poNumberDet" class="form-control disabled-el" value="{{ old('poNumberDet') }}" disabled />
                                </div>       
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2 d-none">
                                    <label for="totalPO">Total PO</label>
                                    <input type="text" id="totalPO" name="totalPO" class="form-control numeral-mask text-right text-hitam disabled-el" value="{{ old('totalPO') }}" disabled/>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="balance">Balance</label>
                                    <input type="text" id="balance" name="balance" class="form-control numeral-mask text-right text-hitam disabled-el" value="{{ old('balance') }}" disabled/>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="recDate">Receive Date</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control text-hitam disabled-el" value="{{ old('recDate') }}" placeholder="DD-MM-YYYY" disabled/>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="dueDate">Due Date</label>
                                    <input type="text" id="dueDate" name="dueDate" class="form-control text-hitam disabled-el" value="{{ old('dueDate') }}" placeholder="DD-MM-YYYY" disabled/>
                                </div>       
                            </div>
                            <hr>                         
                            <div class="form-row">
                                <div class="form-group col-md-2 d-none">
                                    <label for="invoiceNumber">Invoice Number*</label>
                                    <input type="text" id="invoiceNumber" name="invoiceNumber" class="form-control" value="{{ old('invoiceNumber',Session::get('details') ? Session::get('details')->inv_number :'') }}" />
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="invoiceDate">Invoice Date*</label>
                                    <input type="text" id="invoiceDate" name="invoiceDate" class="form-control" value="{{ old('invoiceDate',Session::get('details') ? Session::get('details')->inv_date :'') }}" placeholder="DD-MM-YYYY" />
                                </div> 
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2 d-none">
                                    <label for="taxInvoiceNumber">Tax Invoice Number</label>
                                    <input type="text" id="taxInvoiceNumber" name="taxInvoiceNumber" class="form-control" value="{{ old('taxInvoiceNumber',Session::get('details') ? Session::get('details')->tax_inv_number : '') }}" />
                                </div>
                            </div>
                            {{-- <div class="form-row d-none">
                                <div class="form-group col-md-2 d-none">
                                    <label for="basisAmount">Basis Amount*</label>
                                    <input type="text" id="basisAmount" name="basisAmount" class="form-control numeral-mask text-right" value="{{ old('basisAmount',Session::get('details') ? Session::get('details')->basis_amount : '') }}" required/>
                                </div>
                                
                            </div> --}}
                            {{-- <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="vat">VAT</label>
                                    <input type="text" id="vat" name="vat" class="form-control numeral-mask text-right" value="{{ old('vat',Session::get('details') ? Session::get('details')->vat : '') }}" />
                                </div>
                            </div> --}}
                            {{-- <div class="form-row d-none">
                                <div class="form-group col-md-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="vatCheck" name="vatCheck" {{ old('vatCheck',Session::get('details') ? Session::get('details')->vat : '') ? 'checked' : '' }} />
                                        <label class="custom-control-label" for="vatCheck">VAT</label>
                                    </div>
                                </div>
                            </div> --}}
                            {{-- <div class="{{ Session::get('details') ? Session::get('details')->vat ? '' : 'd-none' :'d-none' }} " id="tipeVat">
                                <div class="form-row d-flex align-items-end">
                                    <div class="form-group col-md-2">
                                        <label for="vat">VAT</label>
                                        <input type="text" id="vat" name="vat" class="form-control numeral-mask text-right" value="{{ old('vat',Session::get('details') ? Session::get('details')->vat : '') }}" />
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="form-label" for="accounVat">COA*</label>
                                        <select class="select2 form-control w-100" id="accounVat" name="accounVat" disabled>
                                            <option value="1100.73">1100.73 - PPN MASUKAN (SUPPLIER)</option>
                                        </select>
                                    </div>
                                </div>
                            </div> --}}
                            {{-- <div class="form-row d-none">
                                <div class="form-group col-md-2">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="pph23Check" name="pph23Check" {{ old('pph23',Session::get('details') ? Session::get('details')->pph23 : '') ? 'checked' : '' }} />
                                        <label class="custom-control-label" for="pph23Check">PPH23</label>
                                    </div>
                                </div>
                            </div> --}}
                            {{-- <div class="{{ Session::get('details') ? Session::get('details')->pph23 ? '' : 'd-none' :'d-none' }} " id="tipePPH23"> --}}
                            {{-- <div class="d-none" id="tipePPH23">
                                <div class="form-row d-flex align-items-end">
                                    <div class="form-group col-md-2">
                                        <label for="pph23">PPH 23</label>
                                        <input type="text" id="pph23" name="pph23" class="form-control numeral-mask text-right" value="{{ old('pph23',Session::get('details') ? Session::get('details')->pph23 : '') }}" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <div class="demo-inline-spacing">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="sewa" name="pph23Type" value="sewa" class="custom-control-input" {{ old('pph23Type',Session::get('details') ? Session::get('details')->pph23_type : '') == 'sewa' ? 'checked' : '' }} checked />
                                                <label class="custom-control-label" for="sewa">Sewa</label>
                                            </div>
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="jasa" name="pph23Type" value="jasa" class="custom-control-input" {{ old('pph23Type',Session::get('details') ? Session::get('details')->pph23_type : '') == 'jasa' ? 'checked' : '' }} />
                                                <label class="custom-control-label" for="jasa">Jasa</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row d-none">
                                <div class="form-group col-md-2">
                                    <label for="otherDeduct">Other Deductions</label>
                                    <input type="text" id="otherDeduct" name="otherDeduct" class="form-control numeral-mask text-right" value="{{ old('otherDeduct',Session::get('details') ? Session::get('details')->other_deduction : '') }}" />
                                </div>
                            </div> --}}
                            {{-- <div class="form-row d-none">
                                <div class="form-group col-md-2">
                                    <label for="grandTotal">Total*</label>
                                    <input type="text" id="grandTotal" name="grandTotal" class="form-control numeral-mask text-right" value="{{ old('grandTotal') }}" />
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="account">COA*</label>
                                    <select class="select2 form-control w-100" id="account" name="account"  disabled>
                                        <option value="2000.11">2000.11 - HUTANG USAHA (SUPPLIER)</option>
                                    </select>
                                </div>
                            </div> --}}

                            <div class="form-row">
                                <div class="form-group col-md-6  ">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('note',Session::get('details') ? Session::get('details')->note : '') }}</textarea>
                                </div>
                            </div>
                            <br>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    @if( Session::get('status') != 'Saved' )
                                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                    @endif
                                    @can('ap-posting')
                                        @if( Session::get('status') == 'Saved' )
                                            <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                                        @endif
                                    @endcan
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

    .totalLine{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }


</style>
@endsection
@section('scripts')
@include('accountPayable.script')
<script type="text/javascript">    
    $(document).ready(function(){
        validateFormToast("frmAdd");
        let errors = "{{ $errors }}";
        errors=errors.replace(/[{[\]}]/g,'');
        errors=errors.replace(/&quot;/g,'').split(",");
        $.each(errors, function(key, value) {
            if (value)
            show_msg("Validasi Form", value, "warning");
        });

        let supplierAda = "{{ Session::get('details') ? Session::get('details')->supplier_id :"" }}";
        let poAda = "{{ Session::get('details') ? Session::get('details')->po_number :"" }}";
        // let recAda = "{{ Session::get('details') ? Session::get('details')->rec_number :"" }}";

        if(supplierAda){
            $('#supplier').val(supplierAda).trigger('change');
            // $('#recNumber').val(recAda).trigger('change');
        }

        $('#cmdSubmit').attr('disabled','disabled');

        $('#invoiceDate').val(currentDate);
        mask_thousand();
    });

    $("#cmdSubmit").click(function (e) {
        let recNumber="";
        $('input:checkbox[name=customCheck]:checked').each(function(){
            recNumber += $(this).data('rec-number')+",";
        });
        // console.log(recNumber.slice(0,-1));

        recNumber=recNumber.slice(0,-1);

        $("#listOfRec > tbody").empty();

        let poNumber= $('#poNumber').val();
        // let recNumber = recNumber;
        if(recNumber && poNumber){
            $.ajax({
                url:"{{ route('ap.detail.rec') }}",
                method:"GET",
                data:{
                    poNumber:poNumber,
                    recNumber:recNumber
                },
                success:function(result){
                    let isiTabel= "";
                    if(result.detailRec.length>0){
                        for(i=0;i<result.detailRec.length;i++){
                            isiTabel +=`<tr>
                                    <td>${result.detailRec[i].article}</td>
                                    <td>${result.detailRec[i].desc}</td>
                                    <td>${result.detailRec[i].uom}</td>
                                    <td class="text-right">${humanizeNumber(result.detailRec[i].qty)}</td>
                                    <td class="text-right">${humanizeNumber(result.detailRec[i].price)}</td>
                                    <td class="text-right">${humanizeNumber(result.detailRec[i].total)}</td>
                                </tr>`;
                        }

                        $("#listOfRec tbody").append(isiTabel);
                    }

                    // let {po_number,nama,pro_inv_num,total_po,basis_amount,due_date,rec_date,po_balance,currency,kurs} = result;
                    // $('#poNumberDet').val(result.summaryRec[0].po_number);
                    // $('#suppCode').val(result.summaryRec[0].nama);
                    // $('#profInvoice').val(result.summaryRec[0].pro_inv_num);

                    $('#totalPO').val(humanizeNumber(result.summaryRec[0].total_amount_po));
                    $('#basisAmount').val(humanizeNumber(result.summaryRec[0].basis_amount));
                    $('#totalDiscount').val(humanizeNumber(result.summaryRec[0].discount));
                    $('#totalPPN').val(humanizeNumber(result.summaryRec[0].nilai_pajak));
                    $('#nilaiPPN').val(humanizeNumber(result.summaryRec[0].vat));
                    $('#totalPPH').val(humanizeNumber(result.summaryRec[0].pph22));
                    $('#totalNetto').val(humanizeNumber(result.summaryRec[0].total_netto));

                    console.log(result.summaryRec[0].total_netto);
                    
                    // $('#vat').val(result.summaryRec[0].basis_amount*(result.summaryRec[0].vat/100));
                    // $('#dueDate').val(result.summaryRec[0].due_date);
                    // $('#recDate').val(result.summaryRec[0].rec_date);
                    $('#balance').val(result.summaryRec[0].po_balance);
                    // if (status != 'Saved'){
                    //     $('#currency').val(result.summaryRec[0].currency).trigger('change');
                    //     $('#rate').val(result.summaryRec[0].kurs);
                    // }
                    // hitungTotal();
                    // $('#invoiceNumber').focus();

                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list data failed","warning");
                }
            })
        }
        
    });

    $("#cmdPosting").click(function(){        
        let apNumber = $('#apNumber').val();            
        $.ajax({
            type: "post",
            url: "{{ route('ap.posting') }}",
            data: {
                apNumber:apNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    show_msg(data.title, data.message, data.alert);
                    $('#apNumber').attr('disabled','disabled');
                    $('#cmdSave').show();
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#statusText').text(data.statusAp);
                    $('#apNumber').attr('disabled','disabled');                    
                    $('#cmdPosting').hide();
                }
            },
            error: function(error) {
                console.log(error);
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