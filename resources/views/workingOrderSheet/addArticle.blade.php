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

    .margin-nol{
        margin-bottom:0.5rem;
    }

    .pointer-link {
        cursor: pointer;
        color: #33548a;
    }

    @media screen 
    and (min-device-width: 1200px) 
    and (max-device-width: 1600px) 
    and (-webkit-min-device-pixel-ratio: 1) { 
        .lebar-list-item{
            width:110%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px)
    {
        .lebar-list-item{
            width:220%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }
    
</style>
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-1 col-12" style="max-width: 5%;">
                <div class="form-group margin-nol">
                    <label for="urutan" class="d-block d-md-none">Urutan</label>
                    <input type="text" class="form-control numeral-mask-satuan drop" id="urutan" name="urutan[]" >
                </div>
            </div>
            <div class="col-md-3 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article</label>
                    <select class="dynamicSelect form-control" id="articleId" name="articleId[]" data-dependent="articleId" onchange="getSoCode(this)">
                    </select>
                    <input type="hidden" class="form-control" id="articleRm" name="articleRm[]" />
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 14%;">
                <div class="form-group margin-nol">
                    <label for="salesOrder" class="d-block d-md-none">So Number</label>
                    <select class="dynamicSelect form-control" id="salesOrder" name="salesOrder[]" data-dependent="salesOrder" onchange="getQtySo(this)">
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="tone" class="d-block d-md-none">Tone</label>
                    <select class="form-control" id="tone" name="tone[]" onchange="setTack(this)">
                        {{-- <option value=""></option>
                        <option value="t1">Tone 1</option>
                        <option value="t2">Tone 2</option>
                        <option value="t3">Tone 3</option>
                        <option value="t4">Tone 4</option> --}}
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyOrder" class="d-block d-md-none">QTY SO</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right tooltips" id="qtyOrder" name="qtyOrder[]" disabled data-toggle="tooltip" data-placement="right" title="QTY SO dikurangi LPB"/>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyProd" class="d-block d-md-none">QTY Fresh</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id="qtyProd" name="qtyProd[]" maxlength="9" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyRepaint" class="d-block d-md-none">QTY Repaint</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id = "qtyRepaint" name="qtyRepaint[]" maxlength="9" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="waktu" class="d-block d-md-none">Waktu</label>
                    <input type="text" class="form-control" id="waktu" name="waktu[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12" style="max-width:10%;">
                <div class="form-group margin-nol">
                    <label for="tag" class="d-block d-md-none">Tack</label>
                    <input type="text" class="form-control text-right" id="tag" name="tag[]" disabled>
                    <input type="hidden" class="form-control" id="tagAsli" name="tagAsli[]" disabled>
                </div>
            </div>
            <div class="col-md-1 col-12" style="max-width: 5%;">
                <div class="form-group margin-nol text-center">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    const wosDate = $('#wosDate');
    const wosTime = $('#wosTime');
    const wosShift = $('#shift');
    const wosGroup = $('#group');
    const note = $('#note');
    const cmdSort = $('#cmdSort');
    const cmdSave = $('#cmdSave');
    const workHour = $('#workingHour');
    const efficiency = $('#efficiency');
    const noEfficiency = $('#noEfficiency');
    const sumWorkHour = $('#sumWorkHour');
    const sumAvailableTime = $('#sumAvailableTime');
    const sumTimeRequired = $('#sumTimeRequired');
    const sumRemainTime = $('#sumRemainTime');
    const oEdit = $('#oEdit');
    let listArticle ="";

    $(document).ready(function(){           
        validateFormToast("frmAdd");
        wosDate.val(currentDate);
        let articles = "{{ $articles }}";
        articles= articles.replace(/\&lt;/g, '<')
        articles= articles.replace(/\&quot;/g, '"')
        articles= articles.replace(/\&gt;/g, '>')
        listArticle = articles;
    });   
        
    approve = (woNumber,objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "POST",
            url: "{{ route('workingOrderSheet.approve') }}",
            data: {
                wosNumber:woNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#wosNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#wosNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');      
                    window.location.reload();                 
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    let cloneCountEdit=0;
    function add_new_row_edit(noSo,noArticle,noArticleRm,qtySo,qtySoUom,qtyProd,qtyRepaint,waktu,tag,tagAsli,tone) {
        console.log(noArticle);
        console.log(qtySo);
        let waktuAwal = $('#wosTime').val()+":00";
        $("#article_row").append($("#new_row").clone().html());
        cloneCountEdit++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#urutan').attr('id', 'urutan'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#salesOrder').attr('id', 'salesOrder'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleId').attr('id', 'articleId'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleRm').attr('id', 'articleRm'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyOrder').attr('id', 'qtyOrder'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyProd').attr('id', 'qtyProd'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyRepaint').attr('id', 'qtyRepaint'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#waktu').attr('id', 'waktu'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tag').attr('id', 'tag'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tagAsli').attr('id', 'tagAsli'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tone').attr('id', 'tone'+ cloneCountEdit);
        changeselectEdit('salesOrder','salesOrder'+ cloneCountEdit,noSo,'articleId'+ cloneCountEdit,noArticle,'tone'+ cloneCountEdit,tone,cloneCountEdit);
        addArticleEdit('articleId'+ cloneCountEdit,noArticle);
        // changeSelectArticleEdit('searchFromSO','articleId'+ cloneCountEdit,noSo,noArticle);
        // $("#articleId"+cloneCount).html(listArticle);
        $('#urutan'+ cloneCountEdit).val(cloneCountEdit);
        $('#qtyOrder'+ cloneCountEdit).val(qtySo);
        $('#qtyProd'+ cloneCountEdit).val(qtyProd);
        $('#qtyRepaint'+ cloneCountEdit).val(qtyRepaint);
        $('#waktu'+ cloneCountEdit).val(waktuAwal);
        // $('#tone'+ cloneCountEdit).val(tone);
        // console.log(tone);
        $('#tag'+ cloneCountEdit).val(parseFloat(tagAsli)*(parseInt(qtyProd)+parseInt(qtyRepaint)));
        $('#tagAsli'+ cloneCountEdit).val(tagAsli);
        $('#articleRm'+ cloneCountEdit).val(noArticleRm);
        // changeselectEdit('salesOrder','salesOrder'+ cloneCountEdit,noSo,'articleId'+ cloneCountEdit,noArticle,'tone'+ cloneCountEdit,tone);
        $("#articleId"+cloneCountEdit).select2();
        $("#salesOrder"+cloneCountEdit).select2();
        $("#tone"+cloneCountEdit).select2();
        $("#qtyOrder"+ cloneCountEdit).tooltip();
        // $("#articleId"+cloneCount).val(noArticle);
        tombolPanah('qtyProd');
        mask_thousand_satuan();
        hitungWaktu(); 
        updatQty();
        sumData();
        cloneCount=cloneCountEdit;
        // isiListArticle();
        return 'beres';
    };

    function changeselect(dependent,obj,isiData) {
        $('#'+obj).attr('disabled','disabled');
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                dependent:dependent
            },
            success:function(result){
                result1 ='<option value="other">Others</option>'+result;
                $('#'+obj).html(result1);
                $('#'+obj).val(isiData).trigger('change');
                $('#'+obj).removeAttr('disabled');
            }
        })
    }

    function changeselectEdit(dependent,obj,noSo,objArticle,noArticle,objTone,tone,indexObj) {
        $('#'+obj).attr('disabled','disabled');
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                dependent:dependent
            },
            success:function(result){
                result1 ='<option value="other">Others</option>'+result;
                $('#'+obj).html(result1);
                $('#'+obj).val(noSo).trigger('change');
                $('#'+obj).removeAttr('disabled');
                jumlahTone = $('#articleId'+indexObj).find(":selected").data("jumlah-tone");
                if (jumlahTone){
                    fillToneOptionEdit(jumlahTone,indexObj,tone)
                }
            }
        })
    }

    function addArticleEdit(obj,article) {
        $('#'+obj).attr('disabled','disabled');
        $('#'+obj).html(listArticle);
        $('#'+obj).val(article).trigger('change');
        $('#'+obj).removeAttr('disabled');
    }
     
    let cloneCount=0;
    function add_new_row() {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        $("#new_row"+ cloneCount).find('#salesOrder').attr('id', 'salesOrder'+ cloneCount);
        $("#new_row"+ cloneCount).find('#urutan').attr('id', 'urutan'+ cloneCount);
        $("#new_row"+ cloneCount).find('#tone').attr('id', 'tone'+ cloneCount);
        // $("#new_row"+ cloneCount).find('#qtyOrder').attr('id', 'qtyOrder'+ cloneCount);
        $('#urutan'+ cloneCount).val(cloneCount);
        // changeselect('salesOrder','salesOrder'+ cloneCount,'','');
        $("#articleId"+cloneCount).html(listArticle);
        $("#articleId"+cloneCount).select2();
        $("#salesOrder"+cloneCount).select2();
        $("#tone"+cloneCount).select2();
        $(".tooltips").tooltip();
        tombolPanah('qtyProd');
        activate_angka();
        mask_thousand_satuan();
        // isiListArticle();
        updatQty();
    };   

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objTone = $('#article_row select[name="tone[]"]');
        let objArticleRm = $('input[name="articleRm[]"]');
        let objQtyOrder = $('input[name="qtyOrder[]"]');
        let objQtyProd = $('input[name="qtyProd[]"]');
        let objQtyRepaint = $('input[name="qtyRepaint[]"]');
        let objTag = $('input[name="tag[]"]');
        let objTagAsli = $('input[name="tagAsli[]"]');
        let objWaktu = $('input[name="waktu[]"]');

        // if (!oEdit.val()){
            
            objArticle.change(function(e){        
                let objIndex = objArticle.index(this);
                let detail = objArticle.eq(objIndex).find(":selected").data("detail");
                let articleRm = objArticle.eq(objIndex).find(":selected").data("article-rm");
                let articleCode =  objArticle.eq(objIndex).val();
                
                objTone.eq(objIndex).val('').trigger('change');

                if(articleCode === 'gantiwarna' || articleCode==='istirahat'){
                    objTag.eq(objIndex).val(0) ;
                    objTagAsli.eq(objIndex).val(0) ;
                    objWaktu.eq(objIndex).val($('#wosTime').val()+":00");
                    objQtyRepaint.eq(objIndex).attr('disabled','disabled');
                }else{
                    objQtyRepaint.eq(objIndex).removeAttr('disabled');
                }

                if (detail){
                    let arrDetail = detail.split("|");
                    objArticleRm.eq(objIndex).val(articleRm);
                    objQtyProd.eq(objIndex).val('');
                    objQtyRepaint.eq(objIndex).val('');
                    // objQtyOrder.eq(objIndex).val(arrDetail[3]);
                    // objTag.eq(objIndex).val(arrDetail[2] || 0) ;
                    // objTagAsli.eq(objIndex).val(arrDetail[2] || 0) ;
                    // objWaktu.eq(objIndex).val($('#wosTime').val()+":00");
                    // if (detail){
                    //     setTimeout(() => {
                    //         objQtyProd.eq(objIndex).focus().select();
                    //     }, 5);
                    // }
                    mask_thousand_satuan();
                    // hitungWaktu();   
                }else{
                    objQtyProd.eq(objIndex).val('');
                    // objQtyOrder.eq(objIndex).val('');
                    objQtyRepaint.eq(objIndex).val('');
                    // objTag.eq(objIndex).val('');
                    // objTagAsli.eq(objIndex).val('');
                    // objWaktu.eq(objIndex).val('');
                }
            });

            objTone.change(function(e){
                let objIndex = objTone.index(this);
                let tone = objTone.eq(objIndex).val();
                let sprayBooth = $('#sprayBooth').val();
                let articleCode = objArticle.eq(objIndex).val();

                if(articleCode === 'gantiwarna' || articleCode==='istirahat'){

                }else{
                    if (sprayBooth){
                        // getTack(articleCode,sprayBooth,tone,objIndex);
                        if (tone){
                            getTack(articleCode,sprayBooth,tone,objIndex);
                            // console.log("iko");
                            // objTag.eq(objIndex).val(tack || 0) ;
                            // objTagAsli.eq(objIndex).val(tack || 0);
                            // objWaktu.eq(objIndex).val($('#wosTime').val()+":00");
                        }else{
                            objTag.eq(objIndex).val(0) ;
                            objTagAsli.eq(objIndex).val(0);
                            objWaktu.eq(objIndex).val('');
                        }
                    }else{
                        if (tone){
                            swal.fire('Warning','Spraybooth belum di pilih','warning');
                            objTone.eq(objIndex).val('').trigger('change');
                        }

                    }
                }

            });
        // }
    }

    if (wosDate.length) {
        wosDate.flatpickr({
            dateFormat: "d-m-Y",
            // minDate: currentDate
        });
    }

    if (wosTime.length) {
        wosTime.flatpickr({
            enableTime: true,
            time_24hr: true,
            noCalendar: true,
            defaultDate: "08:00:00",
        });
    }

    workHour.keyup(function(e){
        sumData();
    }); 

    wosTime.change(function(e){
        hitungWaktu();
    });

    cmdSort.click(function(){
        sortData();
    });

    getDuplicateUrutan = (array, key) => {
        const counts = {};
        const duplicateNames = [];

        array.forEach(item => {
            const value = item[key];
            counts[value] = (counts[value] || 0) + 1;

            if (counts[value] === 2) {
                duplicateNames.push(value);
            }
        });

        return duplicateNames;
    }

    sortData=()=>{
        let articles = []; 
        let flag=0;
        let pesan="";
        let objArticle = $("#article_row select[name='articleId[]']");
        let objArticleRm = $("#article_row input[name='articleRm[]']");
        let objQtyOrder = $('#article_row input[name="qtyOrder[]"]');
        let objQtyProd = $('#article_row input[name="qtyProd[]"]');
        let objQtyRepaint = $('#article_row input[name="qtyRepaint[]"]');
        let objSoCode = $('#article_row select[name="salesOrder[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let objTagAsli = $('#article_row input[name="tagAsli[]"]');
        let objUrutan = $('#article_row input[name="urutan[]"]');
        let objWaktu = $('#article_row input[name="waktu[]"]');
        let objTone = $('#article_row select[name="tone[]"]');
        let jumlahUrutan = objUrutan.length;
        oEdit.val('true');

        $("#addNewRow").attr('disabled','disabled')  
        $(".loading-spinner-container").addClass("-show");
   
        objSoCode.map(function(i) {
		    let $this=$(this);
            
            if ($this.val()){
                let soCode=$this.val();
                let articleRm = objArticleRm.eq(i).val();
                let urutan =objUrutan.eq(i).val();
                let article=objArticle.eq(i).val();
                let qtyOrder=objQtyOrder.eq(i).val().replace(/,/gi, '') || 0;
                let qtyProd=objQtyProd.eq(i).val().replace(/,/gi, '') || 0;
                let qtyRepaint=objQtyRepaint.eq(i).val().replace(/,/gi, '') || 0;
                let tag =objTag.eq(i).val()||0;
                let tagAsli = objTagAsli.eq(i).val()||0;
                let waktu = objWaktu.eq(i).val()||'';
                let tone = objTone.eq(i).val();

                // console.log("Index:"+i+" Urutan:"+urutan +" Article:"+article);

                let obj = articles.find(obj => obj.urutan == urutan);

                if(urutan == '' || urutan == 0){
                    pesan +="Nilai urutan tidak boleh kosong atau 0 <br>"; 
                    flag=1;
                }

                if(parseInt(urutan) > parseInt(jumlahUrutan)){
                    pesan +=`Nilai urutan tidak boleh melebihi jumlah baris (${jumlahUrutan}) <br>`; 
                    flag=1;
                }
                
                if( obj && flag==0) {
                    pesan +="Urutan belum sesuai !! <br>"; 
                    flag=1;
                }else{
                    if(soCode){
                        articles.push({
                            "urutan":urutan,
                            "so_code":soCode,
                            "article_code":article,
                            "article_rm":articleRm,
                            "qty_so":qtyOrder,
                            "uom":'PCS',
                            "qty_prod":qtyProd,
                            "qty_repaint":qtyRepaint,
                            "tag":tag,
                            "tag_asli":tagAsli,
                            "waktu":waktu,
                            "status": articleRm == 'none'?'0':'1',
                            "tone": tone
                        });
                    }
                }
            }
        });

        if (flag == 0){
            if (articles.length > 0){

                let duplicateUrutan = getDuplicateUrutan(articles, 'urutan');
                
                // articles.sort((a, b) => (parseInt(a.urutan) > parseInt(b.urutan)) ? 1 : -1);
                // articles.sort((a, b) => a.urutan - b.urutan);

                articles.sort(function(a, b) {
                    return a.urutan - b.urutan;
                });

                console.log(articles);
                // setImmediate(()=>{
                //     articles.sort((a, b) => (parseInt(a.urutan) > parseInt(b.urutan)) ? 1 : -1);
                // },console.log(articles))

                // setTimeout(() => {
                //     console.log(articles);
                    $('#article_row').find('div').remove();
                    cloneCountEdit=0;
                    createSort(articles,function(result){
                        if (result == 'selesai'){
                            setTimeout(() => {
                                splitArticle();
                                oEdit.val('false');
                                console.log("Finish show data, status edit :" + oEdit.val())
                                $("#addNewRow").removeAttr('disabled') 
                            }, 5000);
                            $(".loading-spinner-container").removeClass("-show");
                        }
                    });
                    
                // },5000);

                // articles.map(function(i) {
                //     add_new_row_edit(i.so_code,i.article_code,i.article_rm,i.qty_so,i.uom,i.qty_prod,i.qty_repaint,i.waktu,i.tag,i.tag_asli,i.tone);
                // });                
            }
        }else{
            Swal.fire('Warning..',pesan,'warning');
        }

    }
    
    createSort=(articles,callback) => {
        console.log(articles);
        let angka = articles.length;
        articles.map(function(i) {
            let beres = add_new_row_edit(i.so_code,i.article_code,i.article_rm,i.qty_so,i.uom,i.qty_prod,i.qty_repaint,i.waktu,i.tag,i.tag_asli,i.tone);
            if (beres == 'beres'){
                angka--;
                if (angka == 0 ){
                    callback('selesai');
                }
            }
        });
    }  

    efficiency.keyup(function(e){
        sumData();
    });

    hitungWaktu = (s) => {
        let objWaktu = $('#article_row input[name="waktu[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let waktuAwal = $('#wosTime').val()+":00";
        let waktuAwalDetik = waktuAwal.split(':').reduce((acc,time) => (60 * acc) + +time);
        let nilaiTag = 0;
        let jamBaru = waktuAwal;
        let nilaiSekarang = 0;
        objWaktu.map(function(i) {  
            let $this=$(this);            
            if (i>0){
                let nilaiTag = objTag.eq(i-1).val() || 0;
                nilaiTag = nilaiTag == 'NaN' ? 0 : nilaiTag * 30 ;
                let currentTime = objWaktu.eq(i-1).val();
                let currentTimeDetik = currentTime.split(':').reduce((acc,time) => (60 * acc) + +time);
                nilaiSekarang = currentTimeDetik+nilaiTag;
                let jamBaru = detikKeJam(nilaiSekarang);
                $this.val(jamBaru);
            }else{
                $this.val(jamBaru);
            }
        });
    }

    sumData = ()=>{
        let objTag = $('#article_row input[name="tag[]"]');
        let efficiency = $('#efficiency').val() || 1;
        let dataTag = objTag.map(function(){return $(this).val();}).get();
        let sumTag = sumFromArray(dataTag);
        let timeReq = parseInt((workHour.val())*3600*(parseInt(efficiency)/100)/30);
        noEfficiency.text(efficiency);
        sumWorkHour.text(workHour.val());
        sumTimeRequired.text(timeReq);
        sumAvailableTime.text(sumTag);
        // sumRemainTime.text(parseInt(sumTag)-timeReq-10);
        sumRemainTime.text(parseInt(sumTag)-timeReq)
    }

    updatQty=()=>{
        let objQtyProd = $('#article_row input[name="qtyProd[]"]');
        let objQtyRepaint = $('#article_row input[name="qtyRepaint[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objTone = $('#article_row select[name="tone[]"]');
        
        objQtyProd.keyup(function(e){        
            let objIndex = objQtyProd.index(this);
            let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
            let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
            // let detail = objArticle.eq(objIndex).find(":selected").data("detail");
            // if (detail){
            //     let arrDetail = detail.split("|");
            //     qtyTag = arrDetail[2].replace(/,/gi, '') || 0;

            // }

            let tone = objTone.eq(objIndex).val();
            let sprayBooth = $('#sprayBooth').val();
            let articleCode = objArticle.eq(objIndex).val();
            // let qtyTag = getTack(articleCode,sprayBooth,tone) || 0;

            getTackHitung(articleCode,sprayBooth,tone,objIndex);
            
            // if (qtyProd || qtyRepaint){
            //     objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
            // }else{
            //     objTag.eq(objIndex).val(qtyTag);
            // }
            sumData();
            hitungWaktu();
		});

        objQtyRepaint.keyup(function(e){        
            let objIndex = objQtyRepaint.index(this);
            let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
            let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
            let detail = objArticle.eq(objIndex).find(":selected").data("detail");
            // if (detail){
            //     let arrDetail = detail.split("|");
            //     qtyTag = arrDetail[2].replace(/,/gi, '') || 0;
            // }

            let tone = objTone.eq(objIndex).val();
            let sprayBooth = $('#sprayBooth').val();
            let articleCode = objArticle.eq(objIndex).val();

            getTackHitung(articleCode,sprayBooth,tone,objIndex);

            // let qtyTag = getTack(articleCode,sprayBooth,tone) || 0;
            
            // if (qtyProd || qtyRepaint){
            //     objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
            // }else{
            //     objTag.eq(objIndex).val(qtyTag);
            // }
            sumData();
            hitungWaktu();
		});
    }

    cmdSave.click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            let articles = []; 
            let flag=0;
            let pesan="";
            let objArticle = $("#article_row select[name='articleId[]']");
            let objArticleRm = $("#article_row input[name='articleRm[]']");
            let objQtyOrder = $('#article_row input[name="qtyOrder[]"]');
            let objQtyProd = $('#article_row input[name="qtyProd[]"]');
            let objQtyRepaint = $('#article_row input[name="qtyRepaint[]"]');
            let objSoCode = $('#article_row select[name="salesOrder[]"]');
            let objTag = $('#article_row input[name="tag[]"]');
            let objTagAsli = $('#article_row input[name="tagAsli[]"]');
            let objUrutan = $('#article_row input[name="urutan[]"]');
            let objWaktu = $('#article_row input[name="waktu[]"]');
            let sWosDate = wosDate.val();
            let sWosShift = wosShift.val();
            let sWosGroup = wosGroup.val();
            let sWosTime = wosTime.val();
            let sWorkHour = workHour.val();
            let sEfficiency = efficiency.val();
            let sNote = note.val();
            let sSprayBooth = $('#sprayBooth').val();
            let objTone = $('#article_row select[name="tone[]"]');
            

            objArticle.map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article = $this.val();
                    let articleName = article;
                    let articleRm = objArticleRm.eq(i).val();
                    let urutan = objUrutan.eq(i).val();
                    let soCode = objSoCode.eq(i).val();
                    let qtyOrder = objQtyOrder.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyProd = objQtyProd.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyRepaint = objQtyRepaint.eq(i).val().replace(/,/gi, '') || 0;
                    let tag = objTag.eq(i).val();
                    let tagAsli = objTagAsli.eq(i).val();
                    let waktu = objWaktu.eq(i).val();
                    let tone = objTone.eq(i).val();

                    // cek urutan harus sesuai jangan ada urutan yang double
                    let obj = articles.find(obj => obj.urutan == urutan);
                    
                    if(obj) {
                        pesan +="Urutan belum sesuai !! <br>"; 
                        flag=1;
                    }else{
                        if(article){
                            articles.push({
                                "urutan":urutan,
                                "so_code":soCode,
                                "article_code":article,
                                "article_rm":articleRm,
                                "qty_so":qtyOrder,
                                "uom":'PCS',
                                "qty_prod":qtyProd,
                                "qty_repaint":qtyRepaint,
                                "tag":tag,
                                "tag_asli":tagAsli,
                                "waktu":waktu,
                                "status": articleRm == 'none'?'0':'1',
                                "tone":tone
                            });
                        }
                    }
                    // urutkan data berdasarkan nomor urutan   
                    if ( (qtyProd+qtyRepaint) == 0 ){
                        pesan +="QTY of items "+ articleName +" order ="+urutan +" cannot be 0 <br>"; 
                        flag=1;
                    }
                }
            });

            if (articles.length > 0){
                // articles.sort((a, b) => (a.urutan > b.urutan) ? 1 : -1);
                // $('#article_row').find('div').remove();
                // cloneCountEdit=0;
                // articles.map(function(i) {
                //     add_new_row_edit(i.so_code,i.article_code,i.article_rm,i.qty_so,i.uom,i.qty_prod,i.qty_repaint,i.waktu,i.tag,i.tag_asli,i.tone);
                // })
            }else{
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let wosNumber = "";
                let urlKu="";
                // if (oEdit.val()=='true'){
                wosNumber = $('#wosNumber').val();
                if (wosNumber)
                    urlKu ="{{ route('workingOrderSheet.update') }}";
                }else{
                    urlKu ="{{ route('workingOrderSheet.store') }}";
                }
                $.ajax({
                    type: "POST",
                    url: urlKu,
                    data: {
                        articles:JSON.stringify(articles),
                        wosNumber:wosNumber,
                        wosDate:sWosDate,
                        wosTime:sWosTime,
                        shift:sWosShift,
                        group:sWosGroup,
                        workHour:sWorkHour,
                        efficiency:sEfficiency,
                        note:sNote,
                        sprayBooth:sSprayBooth

                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            let message="";
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#wosNumber').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert)
                            $('#wosNumber').attr('disabled','disabled');
                            // $('#cmdSave').attr('disabled','disabled');
                            // $('#addNewRow').attr('disabled','disabled');
                            $('#wosNumber').val(data.wosNumber);
                            $('#oEdit').val(data.oEdit);
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
    });

    function getTack(articleCode,sprayBooth,tone,objIndex) {
        let objTag = $('input[name="tag[]"]');
        let objTagAsli = $('input[name="tagAsli[]"]');
        let objWaktu = $('input[name="waktu[]"]');

        if(articleCode === 'gantiwarna' || articleCode ==='istirahat'){
            objTag.eq(objIndex).val(0) ;
            objTagAsli.eq(objIndex).val(0);
            objWaktu.eq(objIndex).val($('#wosTime').val()+":00");
        }else{
            $.ajax({
                url:"{{ route('workingOrderSheet.get.tack') }}",
                method:"GET",
                data:{
                    articleCode:articleCode,
                    sprayBooth:sprayBooth,
                    tone:tone
                },
                success:function(result){
                    objTag.eq(objIndex).val(result || 0) ;
                    objTagAsli.eq(objIndex).val(result || 0);
                    objWaktu.eq(objIndex).val($('#wosTime').val()+":00");
                }
            })
        }
    }

    function getTackHitung(articleCode,sprayBooth,tone,objIndex) {
        let objTag = $('input[name="tag[]"]');
        let objTagAsli = $('input[name="tagAsli[]"]');
        let objQtyProd = $('#article_row input[name="qtyProd[]"]');
        let objQtyRepaint = $('#article_row input[name="qtyRepaint[]"]');
        let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
        let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;

        if(articleCode === 'gantiwarna' || articleCode==='istirahat'){
            objTag.eq(objIndex).val(parseInt(qtyProd)*parseFloat(1));
        }else{
            $.ajax({
                url:"{{ route('workingOrderSheet.get.tack') }}",
                method:"GET",
                data:{
                    articleCode:articleCode,
                    sprayBooth:sprayBooth,
                    tone:tone
                },
                success:function(result){
                    let qtyTag = result || 0;
                    if (qtyProd || qtyRepaint){
                        objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
                    }else{
                        objTag.eq(objIndex).val(qtyTag);
                    }
                }
            })
        }
    }

    getSoCode = ($this,value,valTone) => {
        let objArticle = $("#article_row select[name='articleId[]']");
        let articleCode = $this.value;
        let objIndex = objArticle.index($this);
        let objSo = $('#article_row select[name="salesOrder[]"]');
        let objTone = $('#article_row select[name="tone[]"]');
        let objArticleRm = $('input[name="articleRm[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let objTagAsli = $('#article_row input[name="tagAsli[]"]');
        console.log("Status edit "+oEdit.val())
        if(oEdit.val() == 'false'){    
            console.log("nah kesini");
            objArticleRm.eq(objIndex).val('');
            objSo.eq(objIndex).empty();
            objTone.eq(objIndex).empty();

            if (articleCode){
                if ((articleCode == 'gantiwarna') || (articleCode == 'istirahat')){
                    objSo.eq(objIndex).html(`<option value="other">Others</option>'`);
                    objSo.eq(objIndex).select2();
                    objSo.removeAttr('disabled');
                    objTagAsli.eq(objIndex).val(0);
                    objTag.eq(objIndex).val(0);
                }else{
                    let articleRm = objArticle.eq(objIndex).find(":selected").data("article-rm");
                    let jumlahTone = objArticle.eq(objIndex).find(":selected").data("jumlah-tone");

                    fillToneOption(jumlahTone,objIndex,valTone)

                    objArticleRm.eq(objIndex).val(articleRm);
                    objSo.attr('disabled','disabled');
                    $.ajax({
                        url:"{{route('dynamic.dependent')}}",
                        method:"POST",
                        data:{
                            value:articleCode,
                            dependent:'getSoList'
                        },
                        success:function(result){
                            objSo.eq(objIndex).html(result);
                            objSo.eq(objIndex).select2();
                            if(value){
                                objSo.eq(objIndex).val(value).trigger('change');
                            }
                            objSo.removeAttr('disabled');
                        }
                    });
                }
            }
        }
    }

    setTack = ($this,value) => {
        let objTone = $('#article_row select[name="tone[]"]');
        let objArticle = $("#article_row select[name='articleId[]']");
        let objTag = $('#article_row input[name="tag[]"]');
        let objTagAsli = $('#article_row input[name="tagAsli[]"]');
        let objWaktu = $('#article_row input[name="waktu[]"]');

        let tone = $this.value;
        let objIndex = objTone.index($this);
        let sprayBooth = $('#sprayBooth').val();
        let articleCode = objArticle.eq(objIndex).val();

        if(tone){
            if(articleCode === 'gantiwarna' || articleCode==='istirahat'){
                objTagAsli.eq(objIndex).val(0);
                objTag.eq(objIndex).val(0);
            }else{
                if (sprayBooth && articleCode){
                    getTack(articleCode,sprayBooth,tone,objIndex);
                }else{
                    if (tone){
                        swal.fire('Warning','Spraybooth atau article belum di pilih','warning');
                        objTone.eq(objIndex).val('').trigger('change');
                    }
                }
            }
            hitungWaktu();
        }else{
            objTag.eq(objIndex).val(0);
            objTagAsli.eq(objIndex).val(0);
            objWaktu.eq(objIndex).val('');
        }

    }

    getQtySo = ($this) => {
        let objArticle = $("#article_row select[name='articleId[]']");
        let objSoCode = $('#article_row select[name="salesOrder[]"]');
        let objQtyOrder = $('#article_row input[name="qtyOrder[]"]');
        let objIndex = objSoCode.index($this);
        let soCode = $this.value;
        let articleCode = objArticle.eq(objIndex).val();

        $.ajax({
            url:"{{route('workingOrderSheet.get.qty.so')}}",
            method:"GET",
            data:{
                articleCode:articleCode,
                soCode:soCode
            },
            success:function(result){
                if(result){
                    objQtyOrder.eq(objIndex).val(humanizeNumber(parseInt(result)));
                }else{
                    objQtyOrder.eq(objIndex).val(0);
                }
            }
        });       

    }

    fillToneOption = (jumlahTone,objIndex,valTone) =>{
        let toneOption = `<option value=""></option>`;
        let objTone = $('#article_row select[name="tone[]"]');
        jumlahTone = parseInt(jumlahTone.slice(-1));
        if (jumlahTone > 0){
            for(let i=1;i<=jumlahTone;i++){
                toneOption +=`<option value="t${i}">Tone ${i}</option>`;
            }
        }
        objTone.eq(objIndex).html(toneOption);
        objTone.eq(objIndex).val(valTone).trigger('change');
    }

    fillToneOptionEdit = (jumlahTone,objIndex,valTone) =>{
        let toneOption = `<option value=""></option>`;
        jumlahTone = parseInt(jumlahTone.slice(-1));
        if (jumlahTone > 0){
            for(let i=1;i<=jumlahTone;i++){
                toneOption +=`<option value="t${i}">Tone ${i}</option>`;
            }
        }
        $('#tone'+ objIndex).html(toneOption);
        $('#tone'+ objIndex).val(valTone).trigger('change');
    }


// function changeSelectArticle(dependent,objIndex,value) {
    //     let objArticle = $('#article_row select[name="articleId[]"]');
    //     objArticle.attr('disabled','disabled');
    //     if (value ==='other'){
    //         let result = "";
    //         result +='<option value="" data-article-rm="none" data-detail=""></option>';
    //         result +='<option value="gantiwarna" data-article-rm="none" data-detail="gantiwarna|none|1|||">Ganti Warna</option>';
    //         result +='<option value="istirahat"  data-article-rm="none" data-detail="istirahat|none|1|||">Istirahat</option>';
    //         objArticle.eq(objIndex).html(result);
    //         objArticle.eq(objIndex).select2();
    //         objArticle.removeAttr('disabled');
    //     }else{
    //         $.ajax({
    //             url:"{{route('dynamic.dependent')}}",
    //             method:"POST",
    //             data:{
    //                 value:value,
    //                 dependent:dependent
    //             },
    //             success:function(result){
    //                 objArticle.eq(objIndex).html(result);
    //                 objArticle.eq(objIndex).select2();
    //                 objArticle.removeAttr('disabled');
    //             }
    //         });
    //     }
    // }

    // function changeSelectArticleEdit(dependent,obj,value,article,objTone,tone) {
    //     $('#'+obj).attr('disabled','disabled');
    //     if (value ==='other'){
    //         let result = "";
    //         result +='<option value="" data-article-rm="none" data-detail=""></option>';
    //         result +='<option value="gantiwarna" data-article-rm="none" data-detail="gantiwarna|none|1|||">Ganti Warna</option>';
    //         result +='<option value="istirahat"  data-article-rm="none" data-detail="istirahat|none|1|||">Istirahat</option>';
    //         $('#'+obj).html(result);
    //         $('#'+obj).val(article).trigger('change');
    //         $('#'+obj).removeAttr('disabled');
    //         $('#'+objTone).val(tone).trigger('change');
    //     }else{
    //         $.ajax({
    //             url:"{{route('dynamic.dependent')}}",
    //             method:"POST",
    //             data:{
    //                 value:value,
    //                 dependent:dependent
    //             },
    //             success:function(result){
    //                 $('#'+obj).html(result);
    //                 $('#'+obj).val(article).trigger('change');
    //                 $('#'+obj).removeAttr('disabled');
    //                 $('#'+objTone).val(tone).trigger('change');
    //             }
    //         });
    //     }
    // }





 // function changeSelectArticle(dependent,objIndex,value) {
    //     let objSo = $('#article_row select[name="salesOrder[]"]');
    //     objSo.attr('disabled','disabled');

    //     if (value ==='other'){
    //         let result = "";
    //         result +='<option value="" data-article-rm="none" data-detail=""></option>';
    //         result +='<option value="gantiwarna" data-article-rm="none" data-detail="gantiwarna|none|1|||">Ganti Warna</option>';
    //         result +='<option value="istirahat"  data-article-rm="none" data-detail="istirahat|none|1|||">Istirahat</option>';
    //         objArticle.eq(objIndex).html(result);
    //         objArticle.eq(objIndex).select2();
    //         objArticle.removeAttr('disabled');
    //     }else{
    //         $.ajax({
    //             url:"{{route('dynamic.dependent')}}",
    //             method:"POST",
    //             data:{
    //                 value:value,
    //                 dependent:dependent
    //             },
    //             success:function(result){
    //                 objArticle.eq(objIndex).html(result);
    //                 objArticle.eq(objIndex).select2();
    //                 objArticle.removeAttr('disabled');
    //             }
    //         });
    //     }
    // }
</script>