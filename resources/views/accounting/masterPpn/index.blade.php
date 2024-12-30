@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
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
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="year">Tahun*</label>
                                    <select class="select2 form-control" id="year" name="year" required>
                                        <option value=""></option>
                                        @for ($i = 2020; $i <= 2050; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="ppnValue">PPN*</label>
                                    <input type="hidden" id="idKu" name="idKu" class="form-control"/>
                                    <input type="text" id="ppnValue" name="ppnValue" class="form-control numeral-mask" required/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="startDate">Start Date*</label>
                                    <input type="text" id="startDate" name="startDate" class="form-control" placeholder="DD-MM-YYYY" value="" required/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="endDate">End Date*</label>
                                    <input type="text" id="endDate" name="endDate" class="form-control" placeholder="DD-MM-YYYY" value="" required/>
                                </div>
                            </div>                            
                            <div class="form-row">
                                <div class="col-md-12">
                                    {{-- <a href="{{ route('masterPpn.index') }}" class="btn btn-light">< Back</a> --}}
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">
                                        <span class="align-middle d-sm-inline-block">Save</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section id="table-article">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title"> @yield('title') List</h4>
        <div class="heading-elements">
            <ul class="list-inline mb-0">
                <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
            </ul>
        </div>
      </div>
      <div class="card-content collapse show">
        <div class="card-body">
          <div class="row">
              <div class="col-sm-12">
                <div class="card-datatable table-responsive pt-0">
                  <table id="detailedTable" class="table">
                    <thead class="thead-light">
                    </thead>
                  </table>
                </div>
              </div>
          </div>  
        </div>
      </div>
    </div>
</section>
@include('partials.delete-modal')
@endsection
@section('styles')
{{-- <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/jquery-ui.css') }}"> --}}
<style>
    textarea {
        resize: none;
    }
</style>
@endsection
@section('scripts')
{{-- <script src="{{ asset('assets/js/ui.1.13.0.jquery-ui.js') }}"></script> --}}
<script type="text/javascript">
    const currentDate = todayDate('dd-mm-yyyy'); 
    const year = $('#year');
    const aPpnValue = $("#ppnValue");
    const startDate = $('#startDate');
    const idKu = $('#idKu');

    if (startDate.length) {
        startDate.flatpickr({
            dateFormat: "d-m-Y"
        });
    }
    const endDate = $('#endDate');
    if (endDate.length) {
        endDate.flatpickr({
            dateFormat: "d-m-Y"
        });
    }
        
    $(document).ready(function(){           
        validateFormToast('frmAdd');
        mask_thousand();
        showList();

        let href;
        $(document).on('click', '#deleteButton', function(event) {
            event.preventDefault();
            href = $(this).data('href');
            $('#modalConfirmation').attr("action", href);
        });

        feather.replace({
            width: 14,
            height: 14
        });

    });
 
    $("#cmdSave").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            let vYear = year.val();
            let vStartDate = startDate.val(); 
            let vEndDate = endDate.val();
            let vPpnValue = aPpnValue.val();
            let flag = 0;
            let pesan = "";
    
            jStartDate = vStartDate.split('-').reverse().join();
            jEndDate = vEndDate.split('-').reverse().join();
    
            let newStartDate = new Date(jStartDate);
            let newEndDate = new Date(jEndDate);

            if ((vStartDate != '') || (vEndDate != '')){
                if ((vStartDate == '') || (vEndDate == '')){
                    pesan +=`Start Date or End Date cannot be empty !!! <br>`;
                    flag = 1;
                }
    
                if (newStartDate > newEndDate){
                    pesan +=`Start Date cannot be smaller than End Date !!! <br>`;
                    flag = 1;
                }
            }
    
            if (flag == 0){
                $.ajax({
                    type: "post",
                    url: "{{ route('masterPpn.store') }}",
                    data: {
                        aYear:vYear,
                        aStartDate:vStartDate,
                        aEndDate:vEndDate,
                        aPpnValue:vPpnValue,
                        aIdKu:idKu.val()
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            let message="";
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            year.val("");
                            startDate.val(""); 
                            endDate.val("");
                            aPpnValue.val("");
                            idKu.val("");
                            showList();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            }else{
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    })

    const showList = () => {
        if ($('#detailedTable tr').length >0){
            let table= $('#detailedTable').DataTable();
            table.destroy();
            $('#detailedTable tbody > tr').remove();
            $("#detailedTable thead > tr").remove();
        }
        showDataTables({
        tableId:"detailedTable",
        route:"{{ route('masterPpn.list') }}",
        kolom:{!! $kolom !!},
        arrColPrint:[1,2,3,4],
        columnDefs :[
            { width: '5%', targets: 0 },
        ],
        dataSearch:  {
        },
        orderColumn:[[ 1, 'desc' ]],
        excelFileName:'bank_penerimaan'
        });
    }

    validasiEdit=(id)=>{
        $.ajax({
            url: "{{ route('masterPpn.edit') }}",
            method: 'GET',
            data: {
                id:id,
            },
            success: function (response) {
                if(response.year){
                    year.val(response.year).trigger('change');
                    $('#idKu').val(response.id);
                    startDate.val(response.startDate); 
                    endDate.val(response.endDate);
                    aPpnValue.val(response.ppnValue);
                }
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