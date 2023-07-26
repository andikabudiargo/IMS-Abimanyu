<style>
    .jarak-antar-attr{
        padding-left: 0.3rem;
        margin-bottom: 0.3rem;
        padding-right: 0.3rem;
    }

    .jarak-antar-attr-qty-order{
        padding-left: 0.3rem;
        margin-bottom: 1.8rem;
        padding-right: 0.3rem;
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
    
</style>
{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <table class="table-bordered"  style="width: 98%;table-layout: fixed;">
            <tbody>
                <tr id="tabelBaru">
                </tr>
            </tbody>
        </table>
    </div>
</div>
{{-- \.table row --}} 
<script type="text/javascript">

    emptyList=()=>{
        $("#tabelBaru td").remove();
        $("#judulTabel th").remove();
        // $("#judulListTabel th").remove();
        // $("#listTable thead tr").remove();
        // $("#listTable tbody tr").remove();
        $("#cmdSave").hide();
    }

    add_month =(startMonth,endMonth,urutan)=>{
        let list  = "";
        let year=$('#year').val().slice(-2);
        $("#tabelBaru td").remove();
        list=`<td class="nopadding" style="width: 30%">
                <select class="form-control tombol-panah" id="articleId" name="articleId" required>
                </select>
              </td>`

        for(i=parseInt(startMonth);i<=parseInt(endMonth);i++){
            
            list+= `<td class="isian" style="">
                    <input type="text" data-urutan="${urutan}" class="form-control-plaintext tombol-panah numeral-mask text-right data-bulan" 
                    data-type-el-kiri="input" 
                    data-nama-el-kiri='month${i-1}'
                    data-type-el-kanan='input'
                    data-nama-el-kanan='month${i+1}'
                    data-month='${i}'
                    data-year='${year}'
                    id="${year}${i}" 
                    name="month${i}[]"  
                    value="0"
                    maxlength="6" />
                </td>`; 
        }

        activate_angka();
        mask_thousand();
        
        return list;
    }

    add_judul =(startMonth,endMonth)=>{
        let judul = "";
        let year=$('#year').val().slice(-2);
        let bulan=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Okt','Nov','Dec'];
        // $("#judulTabel th").remove();
        // $("#judulListTabel th").remove();
        
        judul = `<th class="isian" style="width: 30%">
                 <label>Article</label>
                 </th>`;

        for(i=parseInt(startMonth);i<=parseInt(endMonth);i++){
            let namaBulan = bulan[i-1];
            judul+=`<th class="isian text-center" >
                        <label>${namaBulan}${year}</label>
                    </th>`;

        }

        return judul;
    }

    listDetailBulan = () =>{
        emptyList();
        let customer = $('#customerCode').val();
        let bulanAwal = $('#bulanAwal').val();
        let bulanAkhir = $('#bulanAkhir').val();
        let year = $('#year').val();
        
        if ((parseInt(bulanAkhir)-parseInt(bulanAwal) >= 0) && year && customer){
            let cloneCount=1;        
            let listJudul = add_judul(bulanAwal,bulanAkhir);
            $("#judulTabel").append(listJudul);
            let isiBulan = add_month(bulanAwal,bulanAkhir,cloneCount);            
            $("#item_row").append($("#new_row").clone().html());
            $("#tabelBaru").append(isiBulan);
            articleList(customer);
            $('#customerId').select2();
            activate_angka();
            mask_thousand();
            $("#cmdSave").show();
        }else{
            emptyList();
            // Swal.fire({
            //     icon: 'warning',
            //     title: "Warning!",
            //     text: "Tahun dan bulan harus dipilih dulu",
            //     type: "warning",
            //     confirmButtonText: 'OK',
            // }).then((result) => {
            //     if (result.isConfirmed) {
            //         $('#customerCode').val('').trigger('change');
            //     }
            // })
        }
        
    }

    function articleList(customer) {
      $.ajax({
        url:"{{route('forecastSales.get.article')}}",
        method:"POST",
        data:{
            customerCode:customer
        },
        success:function(result){
            $('#articleId').html(result);
            $('#articleId').val('').trigger('change');
            $('#articleId').select2();
        }
      })
    }

    deleteArticle = (customerId,articleCode,year,articleDesc) =>{
        Swal.fire({
            icon: 'warning',
            title: `Do you want to delete the article <br> ${articleDesc}?`,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url:"{{route('forecastSales.destroy')}}",
                    method:"POST",
                    data:{
                        customerId:customerId,
                        articleCode:articleCode,
                        year:year,
                        articleDesc:articleDesc
                    },
                    success:function(result){
                        $('#customerCode').val(customerId).trigger('change');
                        Swal.fire(result.message, '', result.alert);
                    }
                })
                
            } else if (result.isDenied) {
                // Swal.fire('Changes are not saved', '', 'info')
            }
        })
    }
</script>