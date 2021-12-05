@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="form-group row">
                        <label for="woNumber" class="col-sm-4 col-form-label col-form-label-sm">WOS Number</label>
                        <div class="col-md-8">
                            <input type="text" id="woNumber" name="woNumber" class="form-control form-control-sm disabled-el"  disabled />
                        </div>
                    </div>                    
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
                            <div class="row">
                                <div class="form-group col-md-2">
                                    <label for="woDate">Date*</label>
                                    <input type="text" id="woDate" name="woDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="salesman">Shift*</label>
                                    <select class="select2 form-control" id="salesman" name="salesman" required>
                                        <option value="">All</option>
                                        <option value="">Pagi</option>
                                        <option value="">Siang</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="salesman">Group*</label>
                                    <select class="select2 form-control" id="salesman" name="salesman" required>
                                        <option value="">All</option>
                                        <option value="">A</option>
                                        <option value="">B</option>
                                        <option value="">C</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                    <button class="btn btn-primary" type="button" id="cmdGenerate" name="cmdGenerate">Generate</button>
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
                    <h4 class="card-title">Delivery plan list</h4>
                </div>
                <div class="card-body" >
                    <div>
                        <div class="col-sm-12">
                            <div class="card-datatable table-responsive pt-0" id="dataDetail">
                                <table id="tblBaru" class="table table-bordered display w-100 list-plan" >
                                    <thead class="thead-light">
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>         
                            </div>
                        </div>
                    </div>      
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="0">
                    </div>
                    <div class="d-flex justify-content-start align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                        <button class="btn btn-primary btn-prev ml-1" type="button" id="prosesWO" >
                            <span class="align-middle d-sm-inline-block d-none">Proses</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<section id="table-article">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title"> Working order List</h4>
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
@include('deliveryPlan.addArticle')
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

    table.dataTable td {
        /* padding: 0.72rem 1.5rem; */
        padding-top: 0rem;
        padding-right: 1rem;
        padding-bottom: 0rem;
        padding-left: 1rem;
        vertical-align: middle;
    }

    table.dataTable th {
        /* padding: 0.72rem 1.5rem; */
        padding-top: 0.1rem;
        padding-right: 1rem;
        padding-bottom: 0.1rem;
        padding-left: 1rem;
        vertical-align: middle;
    }

    table.list-plan td {
        /* padding: 0.72rem 1.5rem; */
        padding-top: 0rem;
        padding-right: 1rem;
        padding-bottom: 0rem;
        padding-left: 1rem;
        vertical-align: middle;
    }

    table.list-plan th {
        /* padding: 0.72rem 1.5rem; */
        padding-top: 0.1rem;
        padding-right: 1rem;
        padding-bottom: 0.1rem;
        padding-left: 1rem;
        vertical-align: middle;
    }

    .input-name:focus {
        box-shadow:0 0 0 1px red;
        /* background-color: red; */
    }
    .text-color-blue{
        color:rgb(34, 110, 209);
    }

    .form-control-plaintext {
        padding-right:5px;
    }


</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        validateForm('frmAdd');
        $('#woDate').val(currentDate);
        
    });
    
        
    function reloadPage(){
        window.location.reload();
    }

    woDate = $('#woDate');
    if (woDate.length) {
        woDate.flatpickr({
            dateFormat: "d-m-Y",
            minDate: currentDate
        });
    }

    $("#cmdCancel").click(function(){
        $('#woNumber').val('');
        reloadPage();
    });

    $("#cmdNew").click(function(){
        $('#woNumber').val('');
        reloadPage();
    });

    $("#cmdGenerate").click(function(){
        let kolom="";
        let baris="";
        $.ajax({
            type: "get",
            url: "{{ route('deliveryPlan.generate') }}",
            data: {
            },
            dataType: "json",
            success: function(result) {
                let tahun,hari,tanggal;
                let numberOfDate=result.kolom.length;
                let numOfKolomHeader = 6;
                let totKolom = numberOfDate+numOfKolomHeader;
                for(let i =0;i<result.kolom.length;i++){
                    tahun=`<th class="" colspan="`+result.kolom[i].countday+1+`" >
                                <label>`+result.kolom[i].dateyear+`</label>
                            </th>`;
                    hari+=`<th class="" >
                                <label>`+result.kolom[i].dy+`</label>
                            </th>`;
                    tanggal+=`<th class="" >
                                <label>`+result.kolom[i].datemon+`</label>
                            </th>`;
                }

                $('#tblBaru > thead').append("<tr><th rowspan='3'>No</th><th rowspan='3'>Code</th><th rowspan='3'>Name</th><th rowspan='3'>Col. Code</th><th rowspan='3'>Variant</th><th rowspan='3'>Remarks</th>"+tahun+"</tr>");
                $('#tblBaru > thead').append("<tr>"+hari+"</tr>");
                $('#tblBaru > thead').append("<tr>"+tanggal+"</tr>");
                
                let article="";
                let articleKolom="";
                let artCode,plan,act,balance,kolom;
                let dataRows="";
                let nomorCount=0;
                let judulGroup="";
                let rowGroup="";
                let totalPlan="";
                let totalAct="";
                let totalBalMin="";
                let totalPlan1="";
                let totalAct1="";
                let totalBalMin1="";
                let totalPlanDate="";
                let totalActDate="";
                let totalBalDate="";
                let arrayPlan=[];
                let arrayAct=[];
                let arrayBal=[];
                let finalArrayPlan=[];
                let finalArrayAct=[];
                let finalArrayBal=[];
                for(let i =0;i<numberOfDate;i++){
                    finalArrayPlan.push(0);
                    finalArrayAct.push(0);
                    finalArrayBal.push(0);
                }
                let jumlahData = result.data.length; 
                for(let i=0;i < jumlahData;i++){                  
                    if (article != result.data[i].article_code){
                        if (article){
                            // console.log(article+"-"+judulGroup+"-"+result.data[i].group_of_material);
                            if (judulGroup != judulGroup2){
                                rowGroup = `<tr><td colspan="`+totKolom+`">`+result.data[i].group_of_material+`</td></tr>`;
                                judulGroup = result.data[i].group_of_material;
                                nomorCount = 0;
                                
                                if ( i!= numberOfDate ){
                                    for(let i =0; i < finalArrayPlan.length ; i++){
                                        totalPlanDate +=` <td class="" style="width: 10%">
                                        <input type="text" class="form-control-plaintext text-color-blue text-right"
                                            value="`+finalArrayPlan[i]+`" 
                                            id="totalPlan`+i+`" name="totalPlan[]">
                                        </td>`;
                                    }
                                    
                                    for(let i =0; i < finalArrayAct.length ; i++){
                                        totalActDate +=` <td class="" style="width: 10%">
                                        <input type="text" class="form-control-plaintext text-color-blue text-right"
                                            value="`+finalArrayAct[i]+`" 
                                            id="totalAct`+i+`" name="totalAct[]">
                                        </td>`;
                                    }

                                    for(let i =0; i < finalArrayBal.length ; i++){
                                        totalBalDate +=` <td class="" style="width: 10%">
                                        <input type="text" class="form-control-plaintext text-color-blue text-right"
                                            value="`+finalArrayBal[i]+`" 
                                            id="totalBal`+i+`" name="totalBal[]">
                                        </td>`;
                                    }
                                
                                    totalPlan = `<tr><td colspan="`+numOfKolomHeader+`">Total Plan</td>`+totalPlanDate+`</tr>`;
                                    totalAct = `<tr><td colspan="`+numOfKolomHeader+`">Total Act</td>`+totalActDate+`</tr>`;
                                    totalBalMin = `<tr><td colspan="`+numOfKolomHeader+`">Total Balance Minus</td>`+totalBalDate+`</tr>`;

                                    finalArrayPlan =[];
                                    finalArrayAct =[];
                                    finalArrayBal =[];
                                    for(let i =0;i<numberOfDate;i++){
                                        finalArrayPlan.push(0);
                                        finalArrayAct.push(0);
                                        finalArrayBal.push(0);
                                    }
                                    totalPlanDate="";
                                    totalActDate="";
                                    totalBalDate="";
                                }
                                
                            }
                  
                            // console.log(arrayPlan.length);
                            
                            nomorCount++;
                            //menjumlahkan 2 array
                            
                            finalArrayPlan = arrayPlan.map((a, i) => a + finalArrayPlan[i]);
                            finalArrayAct = arrayAct.map((a, i) => a + finalArrayAct[i]);
                            finalArrayBal = arrayBal.map((a, i) => a + finalArrayBal[i]);

                            console.log(finalArrayAct);

                            if ( i+numberOfDate == jumlahData){
                                // console.log(finalArrayPlan.length);
                                for(let i =0; i < finalArrayPlan.length ; i++){
                                        totalPlanDate +=` <td class="" style="width: 10%">
                                        <input type="text" class="form-control-plaintext text-color-blue text-right"
                                            value="`+finalArrayPlan[i]+`" 
                                            id="totalPlan`+i+`" name="totalPlan[]">
                                        </td>`;
                                    }
                                    
                                    for(let i =0; i < finalArrayAct.length ; i++){
                                        totalActDate +=` <td class="" style="width: 10%">
                                        <input type="text" class="form-control-plaintext text-color-blue text-right"
                                            value="`+finalArrayAct[i]+`" 
                                            id="totalAct`+i+`" name="totalAct[]">
                                        </td>`;
                                    }

                                    for(let i =0; i < finalArrayBal.length ; i++){
                                        totalBalDate +=` <td class="" style="width: 10%">
                                        <input type="text" class="form-control-plaintext text-color-blue text-right"
                                            value="`+finalArrayBal[i]+`" 
                                            id="totalBal`+i+`" name="totalBal[]">
                                        </td>`;
                                    }
                                
                                    totalPlan1 = `<tr><td colspan="`+numOfKolomHeader+`">Total Plan</td>`+totalPlanDate+`</tr>`;
                                    totalAct1 = `<tr><td colspan="`+numOfKolomHeader+`">Total Act</td>`+totalActDate+`</tr>`;
                                    totalBalMin1 = `<tr><td colspan="`+numOfKolomHeader+`">Total Balance Minus</td>`+totalBalDate+`</tr>`;
                            }

                            // console.log(article+"-"+nomorCount);
                            nomor1=`<td class="" rowspan="3" style="width: 20%">
                                    <label>`+nomorCount+`</label>
                                 </td>`;

                            nomor=`<td class="d-none" style="width: 20%">
                                    <label>`+nomorCount+`</label>
                                </td>`;
                            dataRows= totalPlan+totalAct+totalBalMin+rowGroup+` <tr>`+nomor1+artCode1+artName1+coloCode1+variant1+`
                                            <td>Plan</td>`+plan+`
                                        </tr>
                                        <tr>`+nomor+artCode+artName+coloCode+variant+`
                                            <td>Act</td>`+act+`
                                        </tr>
                                        <tr>`+nomor+artCode+artName+coloCode+variant+`
                                            <td>Bal. Minus</td>`+balance+`
                                        </tr>`+totalPlan1+totalAct1+totalBalMin1;
                            $('#tblBaru > tbody').append(dataRows);
                        }
                        // console.log(result.data[i].article_code);
                        kolom = "";
                        plan = "";
                        act = "";
                        balance = "";
                        rowGroup = "";
                        totalPlan = "";
                        totalAct = "";
                        totalBalMin ="";
                        arrayPlan=[];
                        arrayAct=[];
                        arrayBal=[];
                        article = result.data[i].article_code;
                        
                    }
                    
                    if (article == result.data[i].article_code){
                        // artCode=result.data[i].article_code;
                        //supaya article bisa di rowspan, baris selanjut nya di hide
                        judulGroup2=result.data[i].group_of_material;
                        
                        artCode1=`<td class="" rowspan="3" style="width: 20%">
                                    <label>`+result.data[i].article_alternative_code+`</label>
                                 </td>`;

                        artCode=`<td class="d-none" style="width: 20%">
                                    <label>`+result.data[i].article_alternative_code+`</label>
                                </td>`;
                        //supaya article bisa di rowspan, baris selanjut nya di hide
                        artName1=`<td class="" rowspan="3" style="width: 20%">
                                    <label>`+result.data[i].article_desc+`</label>
                                 </td>`;

                        artName=`<td class="d-none" style="width: 20%">
                                    <label>`+result.data[i].article_desc+`</label>
                                </td>`;

                        coloCode1=`<td class="" rowspan="3" style="width: 20%">
                                    <label>`+result.data[i].color_code+`</label>
                                 </td>`;

                        coloCode=`<td class="d-none" style="width: 20%">
                                    <label>`+result.data[i].color_code+`</label>
                                </td>`;

                        variant1=`<td class="" rowspan="3" style="width: 20%">
                                    <label>`+result.data[i].variant+`</label>
                                 </td>`;

                        variant=`<td class="d-none" style="width: 20%">
                                    <label>`+result.data[i].variant+`</label>
                                </td>`;

                        group1=`<td class="" rowspan="3" style="width: 20%">
                                    <label>`+result.data[i].group_of_material+`</label>
                                 </td>`;

                        group=`<td class="d-none" style="width: 20%">
                                    <label>`+result.data[i].group_of_material+`</label>
                                </td>`;

                        arrayPlan.push(result.data[i].plan)
                        arrayAct.push(result.data[i].act)
                        arrayBal.push(result.data[i].balance)
                        
                        plan+=` <td class="" style="width: 10%">
                                  <input type="text" class="form-control-plaintext pindah-cell input-name text-color-blue text-right" 
                                    data-tanggal="`+result.data[i].day+`" 
                                    data-article-id="`+result.data[i].article_code+`" 
                                    data-max-coloumn= "`+numberOfDate+`"
                                    value="`+result.data[i].plan+`" 
                                    id="plan`+i+`" name="plan[]">
                                </td>`;

                        act+=`<td class="" style="width: 10%">
                                <input type="text" class="form-control-plaintext pindah-cell input-name text-right" 
                                    data-tanggal="`+result.data[i].day+`" 
                                    data-article-id="`+result.data[i].article_code+`" 
                                    value="`+result.data[i].act+`" 
                                    id="act`+i+`" name="act[]" disabled>
                              </td>`;

                        balance+=`<td class="" style="width: 10%">
                                    <input type="text" class="form-control-plaintext pindah-cell input-name text-right" 
                                        data-tanggal="`+result.data[i].day+`" 
                                        data-article-id="`+result.data[i].article_code+`" 
                                        value="`+result.data[i].balance+`" 
                                        id="balance`+i+`" name="balance[]" disabled>
                                  </td>`;
                        
                    }

                }

            },
            error: function(error) {
                console.log(error);
            }
        });
    });
    

    // $("#cmdGenerate").click(function(){
    //     let kolom="";
    //     let baris="";
    //     $.ajax({
    //         type: "get",
    //         url: "{{ route('deliveryPlan.generate') }}",
    //         data: {
    //         },
    //         dataType: "json",
    //         success: function(result) {
    //             let tahun,hari,tanggal;
    //             let numberOfDate=result.kolom.length;
    //             for(let i =0;i<result.kolom.length;i++){
    //                 tahun=`<th class="" colspan="`+result.kolom[i].countday+1+`" >
    //                             <label>`+result.kolom[i].dateyear+`</label>
    //                         </th>`;
    //                 hari+=`<th class="" >
    //                             <label>`+result.kolom[i].dy+`</label>
    //                         </th>`;
    //                 tanggal+=`<th class="" >
    //                             <label>`+result.kolom[i].datemon+`</label>
    //                         </th>`;
    //             }

    //             $('#tblBaru > thead').append("<tr><th rowspan='3'>group</th><th rowspan='3'>No</th><th rowspan='3'>Code</th><th rowspan='3'>Name</th><th rowspan='3'>Col. Code</th><th rowspan='3'>Variant</th><th rowspan='3'>Remarks</th>"+tahun+"</tr>");
    //             $('#tblBaru > thead').append("<tr>"+hari+"</tr>");
    //             $('#tblBaru > thead').append("<tr>"+tanggal+"</tr>");
                
    //             let article="";
    //             let articleKolom="";
    //             let artCode,plan,act,balance,kolom;
    //             let dataRows="";
    //             let nomorCount=0;
    //             for(let i=0;i<result.data.length;i++){
    //                 if (article != result.data[i].article_code){
    //                     if (article){
    //                         nomor1=`<td class="" rowspan="3" style="width: 20%">
    //                                 <label>`+nomorCount+`</label>
    //                              </td>`;

    //                         nomor=`<td class="d-none" style="width: 20%">
    //                                 <label>`+nomorCount+`</label>
    //                             </td>`;
    //                         dataRows= ` <tr>`+group+nomor1+artCode1+artName1+coloCode1+variant1+`
    //                                         <td>Plan</td>`+plan+`
    //                                     </tr>
    //                                     <tr>`+group+nomor+artCode+artName+coloCode+variant+`
    //                                         <td>Act</td>`+act+`
    //                                     </tr>
    //                                     <tr>`+group+nomor+artCode+artName+coloCode+variant+`
    //                                         <td>Bal. Minus</td>`+balance+`
    //                                     </tr>`;
    //                         $('#tblBaru > tbody').append(dataRows);
    //                     }
    //                     // console.log(result.data[i].article_code);
    //                     kolom="";
    //                     // artCode = "";
    //                     plan = "";
    //                     act = "";
    //                     balance = "";
    //                     nomorCount=0;
    //                     article = result.data[i].article_code;
    //                 }
                    
    //                 if (article == result.data[i].article_code){
    //                     // artCode=result.data[i].article_code;
    //                     //supaya article bisa di rowspan, baris selanjut nya di hide
                        
    //                     artCode1=`<td class="" rowspan="3" style="width: 20%">
    //                                 <label>`+result.data[i].article_alternative_code+`</label>
    //                              </td>`;

    //                     artCode=`<td class="d-none" style="width: 20%">
    //                                 <label>`+result.data[i].article_alternative_code+`</label>
    //                             </td>`;
    //                     //supaya article bisa di rowspan, baris selanjut nya di hide
    //                     artName1=`<td class="" rowspan="3" style="width: 20%">
    //                                 <label>`+result.data[i].article_desc+`</label>
    //                              </td>`;

    //                     artName=`<td class="d-none" style="width: 20%">
    //                                 <label>`+result.data[i].article_desc+`</label>
    //                             </td>`;

    //                     coloCode1=`<td class="" rowspan="3" style="width: 20%">
    //                                 <label>`+result.data[i].color_code+`</label>
    //                              </td>`;

    //                     coloCode=`<td class="d-none" style="width: 20%">
    //                                 <label>`+result.data[i].color_code+`</label>
    //                             </td>`;

    //                     variant1=`<td class="" rowspan="3" style="width: 20%">
    //                                 <label>`+result.data[i].variant+`</label>
    //                              </td>`;

    //                     variant=`<td class="d-none" style="width: 20%">
    //                                 <label>`+result.data[i].variant+`</label>
    //                             </td>`;

    //                     // group1=`<td class="" rowspan="3" style="width: 20%">
    //                     //             <label>`+result.data[i].group_of_material+`</label>
    //                     //          </td>`;

    //                     group=`<td class="d-none" style="width: 20%">
    //                                 <label>`+result.data[i].group_of_material+`</label>
    //                             </td>`;

    //                     plan+=` <td class="" style="width: 10%">
    //                               <input type="text" class="form-control-plaintext pindah-cell input-name text-color-blue text-right" 
    //                                 data-tanggal="`+result.data[i].day+`" 
    //                                 data-article-id="`+result.data[i].article_code+`" 
    //                                 data-max-coloumn= "`+numberOfDate+`"
    //                                 value="`+result.data[i].plan+`" 
    //                                 id="plan`+i+`" name="plan[]">
    //                             </td>`;

    //                     act+=`<td class="" style="width: 10%">
    //                             <input type="text" class="form-control-plaintext pindah-cell input-name text-right" 
    //                                 data-tanggal="`+result.data[i].day+`" 
    //                                 data-article-id="`+result.data[i].article_code+`" 
    //                                 value="`+result.data[i].act+`" 
    //                                 id="act`+i+`" name="act[]" disabled>
    //                           </td>`;

    //                     balance+=`<td class="" style="width: 10%">
    //                                 <input type="text" class="form-control-plaintext pindah-cell input-name text-right" 
    //                                     data-tanggal="`+result.data[i].day+`" 
    //                                     data-article-id="`+result.data[i].article_code+`" 
    //                                     value="`+result.data[i].balance+`" 
    //                                     id="balance`+i+`" name="balance[]" disabled>
    //                               </td>`;
                        
    //                 }

    //             }

    //             let groupColumn = 0;
    //             let foot="";
    //             let coloumn="";
    //             let colNum=38;
    //             $('#tblBaru').DataTable( {
    //                 order: [[0, 'asc']],
    //                 rowGroup: {
    //                     // endRender: function ( rows, group ) {
    //                     //     var avg = rows
    //                     //         .data()
    //                     //         .pluck(7)
    //                     //         .reduce( function (a, b) {
    //                     //             return a + b.replace(/[^\d]/g, '')*1;
    //                     //         }, 0) ;
    //                     //         // / rows.count()
            
    //                     //     return 'Total plan '+group+': '+
    //                     //         $.fn.dataTable.render.number(',', '.', 0, '$').display( avg );
    //                     // },
    //                     dataSrc: groupColumn
    //                 },
    //                 scrollY:        "500px",
    //                 scrollX:        true,
    //                 scrollCollapse: true,
    //                 paging:         false,
    //                 fixedColumns:   {
    //                     left: 3
    //                 },
    //                 columnDefs: [
    //                 {
    //                     targets: [ 0 ],
    //                     visible: false,
    //                     searchable: false
    //                 },
    //             ]
    //             } );
    //         },
    //         error: function(error) {
    //             console.log(error);
    //         }
    //     });
    // });

    $("#prosesWO").click(function(){
        ambilData();
    });

    function ambilData(){
        let plan,act,balance;
        let articles=[];
        let objPlan= $('#dataDetail input[name="plan[]"]');
        let objAct= $('#dataDetail input[name="act[]"]');
        let objBalance= $('#dataDetail input[name="balance[]"]');
        objPlan.map(function(i) {  
		    let $this=$(this);
            // console.log($this);
            if ($this.val()){
                let date=$this.data('tanggal');
                let articleCode=$this.data('article-id');
                let plan=$this.val().replace(/,/gi, '') || 0;
                let act=objAct.eq(i).val().replace(/,/gi, '') || 0;
                let balance=objBalance.eq(i).val().replace(/,/gi, '') || 0;
                articles.push({
                    "article_code":articleCode,
                    "date":date,
                    "plan":plan,
                    "act" :act,
                    "balance" : balance
                });
            }
        });
        console.log(articles);
    }

    // $("#cmdSave").click(function(){     
    //     $('.disabled-el').removeAttr('disabled');
    //     // ambil semua data article
    //     let objArticle = $("#article_row select[name='articleId[]']");
    //     let qtyOrder = $('input[name="qtyOrder[]"]');
    //     let qtyProd = $('input[name="qtyProd[]"]');
    //     let woDate = $('#woDate').val();
    //     let note = $('#note').val();
    //     let articles = []; 
    //     let flag=0; 
    //     let pesan="";

    //     objArticle.map(function(i) {  
	// 	    let $this=$(this);
    //         if ($this.val()){
    //             let article=$this.val().split("|");
    //             let articleName=$this.select2('data')[0].text;
    //             let plu=article[0];
    //             let qty=objProd.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                            
    //             //es6
    //             // let obj = ingredient.find(obj => obj.plu == plu);

    //             //jquery
    //             //cek apakah article ada yang double input ato ngk
    //             let obj = $.grep(articles, function(obj){
    //                 return obj.article_code === plu;
    //             })[0];
                
    //             if(obj) {
    //                 pesan +="Article "+articleName+" entered more than once !! <br>"; 
    //                 flag=1;
    //             } else {
    //                 if ((plu!=='') && (qty> 0)){
    //                     articles.push({
    //                         "article_code":plu,
    //                         "qty":qty,
    //                         "uom":uom,
    //                         "customer_code":customer,
    //                         "price":price,
    //                         "type":type
    //                     });
    //                 }
    //             } 
            
    //             if (qty == 0){
    //                 pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
    //                 flag=1;
    //             }
    //         }
    //     });

    //     if (customer == ''){
	// 		pesan +="Customer must be filled in <br>"; 
	// 		flag=1;
	// 	}

    //     if (articles.length == 0){
	// 		pesan +="Articles must be filled in completely <br>"; 
	// 		flag=1;
	// 	}

    //     if (flag==0){

    //         $.ajax({
    //             type: "post",
    //             url: "{{ route('bom.store') }}",
    //             data: {
    //                 articles:JSON.stringify(articles),
    //                 articleCode:articleCode,
    //                 customer:customer,
    //                 note:note,
    //                 group:group,
    //                 uom:uom,
    //             },
    //             dataType: "json",
    //             success: function(data) {
    //                 if (data.status == 0 ){
    //                     let message="";
    //                     for(let i = 0; i < data.message.length; i++) {
    //                         message += "-"+data.message[i]+"<br>";                           
    //                     }
    //                     $("#alert-message-success").addClass(data.alert);
    //                     $("#alert-message-success .alert-body").html(message);
    //                     $("#alert-message-success").show();
    //                     $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
    //                         $("#alert-message-success").slideUp(500);
    //                     });
    //                     $('#woNumber').attr('disabled','disabled');

    //                 }else{
    //                     $("#alert-message-success").addClass(data.alert);
    //                     $("#alert-message-success .alert-body").html(data.message);
    //                     $("#alert-message-success").show();
    //                     $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
    //                         $("#alert-message-success").slideUp(500);
    //                     });
    //                     $('#woNumber').attr('disabled','disabled');
    //                     $('#cmdSave').attr('disabled','disabled');
    //                     $('#addNewRow').attr('disabled','disabled');
    //                     $('#woNumber').val(data.woNumber);
                        
    //                 }
                    
    //             },
    //             error: function(error) {
    //                 console.log(error);
    //             }
    //         });

    //     }else{
    //         Swal.fire('Warning..',pesan,'warning');
    //     }
    
    // });

    // function prosesWO(){
    //     let objArticle = $("#article_row select[name='articleId[]']");
    //     let objQtyProd = $('input[name="qtyProd[]"]');
    //     let articles = []; 
    //     let pesan="";
    //     let flag= 0;
    //     objArticle.map(function(i) {  
	// 	    let $this=$(this);
    //         if ($this.val()){
    //             let article=$this.val().split("|");
    //             let plu=article[0];
    //             let articleName=$this.select2('data')[0].text;
    //             let qty=objQtyProd.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                                            
    //             //es6
    //             // let obj = ingredient.find(obj => obj.plu == plu);

    //             //jquery
    //             //cek apakah article ada yang double input ato ngk
    //             let obj = $.grep(articles, function(obj){
    //                 return obj.article_code === plu;
    //             })[0];
                
    //             if(obj) {
    //                 pesan +="Article "+articleName+" entered more than once !! <br>"; 
    //                 flag=1;
    //             } else {
    //                 if ((plu!=='') && (qty> 0)){
    //                     articles.push({
    //                         "article_code":plu,
    //                         "qty":qty
    //                     });
    //                 }
    //             } 
            
    //             if (qty == 0){
    //                 pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
    //                 flag=1;
    //             }
    //         }
    //     });

    //     if (flag==0){
    //         console.log(articles);     
    //         showList(articles);   
    //     }else{
    //         Swal.fire('Warning..',pesan,'warning');
    //     }
        
    // }

    // function showList(articles){
    //     let isidata = $('#detailedTable tr').length;
    //     if (isidata >0){
    //         let table= $('#detailedTable').DataTable();
    //         table.destroy();
    //         $('#detailedTable tbody > tr').remove();
    //     }
        
    //     let dtdom ='<"d-flex justify-content-between align-items-center header-actions mx-1 row mt-75"' +
    //         '<"col-lg-12 col-xl-6" l>' +
    //         '<"col-lg-12 col-xl-6 pl-xl-75 pl-0"<"dt-action-buttons text-xl-right text-lg-left text-md-right text-left d-flex align-items-center justify-content-lg-end align-items-center flex-sm-nowrap flex-wrap mr-1"<"mr-1"f>B>>' +
    //         '>t' +
    //         '<"d-flex justify-content-between mx-2 row mb-1"' +
    //         '<"col-sm-12 col-md-6"i>' +
    //         '<"col-sm-12 col-md-6"p>' +
    //         '>';
    //     let arr_col_print =[2,3,4,5,6]; 
    //     $(function(){
    //         let oTable =$("#detailedTable").DataTable({
    //             ajax:{
    //                 url:'{{ route("workingOrder.detail.list")}}',
    //                 data:{
    //                     articles:JSON.stringify(articles),
    //                 }
    //             },
    //             processing: true,
    //             serverSide: true,
    //             buttons: true,
    //             dom:dtdom,
    //             lengthMenu: [
    //             [ 10, 25, 50, -1 ],
    //             [ '10', '25', '50', 'all' ]
    //             ],
    //             buttons: [
    //             {
    //                 extend: 'collection',
    //                 className: 'btn btn-outline-secondary dropdown-toggle mr-2 mt-07',
    //                 text: feather.icons['share'].toSvg({ class: 'font-small-4 mr-50' }) + 'Export',
    //                 buttons: [
    //                 {
    //                     extend: 'print',
    //                     text: feather.icons['printer'].toSvg({ class: 'font-small-4 mr-50' }) + 'Print',
    //                     className: 'dropdown-item',
    //                     exportOptions: { columns: arr_col_print }
    //                 },
    //                 {
    //                     extend: 'csv',
    //                     text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + 'Csv',
    //                     className: 'dropdown-item',
    //                     exportOptions: { columns: arr_col_print }
    //                 },
    //                 {
    //                     extend: 'excel',
    //                     text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + 'Excel',
    //                     className: 'dropdown-item',
    //                     exportOptions: { columns: arr_col_print }
    //                 },
    //                 {
    //                     extend: 'pdf',
    //                     text: feather.icons['clipboard'].toSvg({ class: 'font-small-4 mr-50' }) + 'Pdf',
    //                     className: 'dropdown-item',
    //                     exportOptions: { columns: arr_col_print }
    //                 },
    //                 {
    //                     extend: 'copy',
    //                     text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + 'Copy',
    //                     className: 'dropdown-item',
    //                     exportOptions: { columns: arr_col_print }
    //                 }
    //                 ],
    //                 init: function (api, node, config) {
    //                 $(node).removeClass('btn-secondary');
    //                 $(node).parent().removeClass('btn-group');
    //                 setTimeout(function () {
    //                     $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
    //                 }, 50);
    //                 }
    //             },
    //             ],
    //             responsive: {
    //             details: {
    //                 display: $.fn.dataTable.Responsive.display.modal({
    //                 header: function (row) {
    //                     var data = row.data();
    //                     return 'Details of ' + data['nama'];
    //                 }
    //                 }),
    //                 type: 'column',
    //                 renderer: function (api, rowIdx, columns) {
    //                 var data = $.map(columns, function (col, i) {
    //                     return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
    //                     ? '<tr data-dt-row="' +
    //                         col.rowIndex +
    //                         '" data-dt-column="' +
    //                         col.columnIndex +
    //                         '">' +
    //                         '<td>' +
    //                         col.title +
    //                         ':' +
    //                         '</td> ' +
    //                         '<td>' +
    //                         col.data +
    //                         '</td>' +
    //                         '</tr>'
    //                     : '';
    //                 }).join('');
    //                 return data ? $('<table class="table"/>').append(data) : false;
    //                 }
    //             }
    //             },
    //             language: {
    //             paginate: {
    //                 // remove previous & next text from pagination
    //                 previous: '&nbsp;',
    //                 next: '&nbsp;'
    //             }
    //             },
    //             columnDefs: [
    //                 { width: '5%', targets: 0 },
    //                 { className: 'text-right','targets': [ 4,5 ] },
    //                 {
    //                     "searchable": false,
    //                     "orderable": false,
    //                     "targets": 0
    //                 }
    //             ],
    //             drawCallback: function( settings ) {
    //                 feather.replace({
    //                         width: 14,
    //                         height: 14
    //                 });
    //             },
    //             order: [[ 2, 'asc' ]],
    //             bDestroy: true, //pakai ini supaya bisa di load berulang2
    //             // scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
    //             columns: [
    //                 {
    //                     data: 'id',title:"#",
    //                     render: function (data, type, row, meta) {
    //                         return meta.row + meta.settings._iDisplayStart + 1;
    //                     }
    //                 },
    //                 { data: 'article_alternative_code', name: 'article_alternative_code',title:'Article Code' },
    //                 { data: 'article_desc', name: 'article_desc',title:'Desc' },
    //                 { data: 'uom', name: 'uom',title:'UOM' },
    //                 { data: 'qty', name: 'qty',title:'QTY' },
    //                 { data: 'qty_total', name: 'qty_total',title:'QTY Total' ,render: $.fn.dataTable.render.number(',','.') },
    //                 { data: 'kelompok', name: 'kelompok',title:'Article Type' ,render: $.fn.dataTable.render.number(',','.') },
    //             ],
    //         });
    //     });
        
    // }

    // let cloneCount=1;
    // function add_new_row() {
    //     $("#article_row").append($("#new_row").clone().html());
    //     cloneCount++;
    //     $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
    //     $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
    //     $("#new_row"+ cloneCount).find('#salesOrder').attr('id', 'salesOrder'+ cloneCount);
    //     changeselect('article_wos','articleId'+ cloneCount);
    //     $("#articleId"+cloneCount).select2();
    //     // $("#salesOrder"+cloneCount).select2();
    //     $('#remove_button').tooltip();
    //     tombolPanah('planTime');
    //     tombolPanah('actionTime');
    //     tombolPanah('qtyFresh');
    //     tombolPanah('qtyRepaint');
    //     activate_angka();
    //     mask_thousand();
    //     mask_time();
    //     splitArticle();
    //     // isiListArticle();
    // };

    // function mask_time(){
    //     $('.time-mask').toArray().forEach(function(field){
    //         new Cleave(field, {
    //             time: true,
    //             timePattern: ['h', 'm', 's']
    //         });
    //     });   
    // }

    // function mask_time(){
    //     timeMask = $('.time-mask');
    //     if (timeMask.length) {
    //         new Cleave(timeMask, {
    //         time: true,
    //         timePattern: ['h', 'm', 's']
    //         });
    //     }    
    // }
    

    // function isiListArticle(){
    //     // split article with delimiter |
    //     let objSo = $('#article_row select[name="salesOrder[]"]');
    //     objSo.change(function(e){        
    //         let objIndex = objSo.index(this);
    //         let soCode = objSo.eq(objIndex).val();
    //         changeSelectArticle('searchFromSO',objIndex,soCode);
    //         splitArticle();
	// 	});
    // }

    // function changeSelectArticle(dependent,objIndex,value) {
    //     let objArticle = $('#article_row select[name="articleId[]"]');
    //     $.ajax({
    //         url:"{{route('dynamic.dependent')}}",
    //         method:"POST",
    //         data:{
    //             value:value,
    //             dependent:dependent
    //         },
    //         success:function(result){
    //             objArticle.eq(objIndex).html(result);
    //             objArticle.eq(objIndex).select2();
    //             // objArticle.eq(objIndex).trigger('change');
    //         }
    //     })
    // }

    // function splitArticle(){
    //     // split article with delimiter |
    //     let objArticle = $('#article_row select[name="articleId[]"]');
    //     let objArticleRm = $('#article_row input[name="articleIdRm[]"]');
    //     let objQtyStockRm = $('#article_row input[name="qtyStockRm[]"]');
    //     let planTime = $('#article_row input[name="planTime[]"]');
        
    //     objArticle.change(function(e){        
    //         let objIndex = objArticle.index(this);
    //         let detail = objArticle.eq(objIndex).val();
    //         let arrDetail = detail.split("|");
    //         objArticleRm.eq(objIndex).val(arrDetail[4]);
    //         objQtyStockRm.eq(objIndex).val(parseInt(arrDetail[5] || 0 ));
    //         if (detail){
    //             setTimeout(() => {
    //                 planTime.eq(objIndex).focus().select();
    //             }, 5);
    //         }
	// 	});
    // }

    // function changeselect(dependent,obj) {
    //   $.ajax({
    //     url:"{{route('dynamic.dependent')}}",
    //     method:"POST",
    //     data:{
    //         dependent:dependent
    //     },
    //     success:function(result){
    //         $('#'+obj).html(result);
    //         $('#'+obj).val('').trigger('change');
    //     }
    //   })
    // }

    // function tombolPanah(objname){
    //     // function kalo mau pindah filed dari atas ke bawah atau sebaliknya
    //     let obj = $('input[name="'+objname+'[]"]');
    //     obj.keyup(function(e) {
    //         indexnya= obj.index(this);
    //         indexnya=parseInt(indexnya);
    //         if (e.keyCode == 38) {
    //             //panah atas
    //             indexTarget = indexnya-1;
    //             obj.eq(indexTarget).focus().select();
    //             return false;
    //         }
    //         if (e.keyCode == 40) {
    //             //panah bawah
    //             indexTarget = indexnya+1;
    //             obj.eq(indexTarget).focus().select();
    //             return false;
    //         }
    //     });
    // }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection