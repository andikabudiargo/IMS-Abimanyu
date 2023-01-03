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
            width:200%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }
    
</style>

{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-1 col-12" style="max-width: 5%;">
                <div class="form-group margin-nol">
                    <label for="urutan" class="d-block d-md-none">Urutan</label>
                    <input type="text" class="form-control numeral-mask-satuan drop" id="urutan" name="urutan[]" >
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 14%;">
                <div class="form-group margin-nol">
                    <label for="salesOrder" class="d-block d-md-none">NO SPK / SO</label>
                    <select class="dynamicSelect form-control" id="salesOrder" name="salesOrder[]" data-dependent="salesOrder">
                    </select>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article</label>
                    <select class="dynamicSelect form-control" id="articleId" name="articleId[]" data-dependent="articleId">
                    </select>
                    <input type="hidden" class="form-control" id="articleRm" name="articleRm[]" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyOrder" class="d-block d-md-none">QTY SO</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id="qtyOrder" name="qtyOrder[]" disabled />
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
            <div class="col-md-1 col-12" style="max-width: 5%;">
                <div class="form-group margin-nol">
                    <label for="tag" class="d-block d-md-none">Tag</label>
                    <input type="text" class="form-control" id="tag" name="tag[]" disabled>
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
{{-- \.table row --}} 

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
    function add_new_row_edit(noSo,noArticle,noArticleRm,qtySo,qtySoUom,qtyProd,qtyRepaint,waktu,tag,tagAsli) {
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
        changeselect('salesOrder','salesOrder'+ cloneCountEdit,noSo);
        changeSelectArticleEdit('searchFromSO','articleId'+ cloneCountEdit,noSo,noArticle);
        $('#urutan'+ cloneCountEdit).val(cloneCountEdit);
        $('#qtyOrder'+ cloneCountEdit).val(qtySo);
        $('#qtyProd'+ cloneCountEdit).val(qtyProd);
        $('#qtyRepaint'+ cloneCountEdit).val(qtyRepaint);
        $('#waktu'+ cloneCountEdit).val(waktuAwal);
        $('#tag'+ cloneCountEdit).val(parseFloat(tagAsli)*(parseInt(qtyProd)+parseInt(qtyRepaint)));
        $('#tagAsli'+ cloneCountEdit).val(tagAsli);
        $('#articleRm'+ cloneCountEdit).val(noArticleRm);
        $("#articleId"+cloneCountEdit).select2();
        $("#salesOrder"+cloneCountEdit).select2();
        $('#remove_button').tooltip();
        tombolPanah('qtyProd');
        mask_thousand_satuan();
        hitungWaktu(); 
        updatQty();
        sumData();
        cloneCount=cloneCountEdit;
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
                result +='<option value="other">Others</option>';
                $('#'+obj).html(result);
                $('#'+obj).val(isiData).trigger('change');
                $('#'+obj).removeAttr('disabled');
            }
        })
    }

    function changeSelectArticle(dependent,objIndex,value) {
        let objArticle = $('#article_row select[name="articleId[]"]');
        objArticle.attr('disabled','disabled');
        if (value ==='other'){
            let result = "";
            result +='<option value="" data-article-rm="none" data-detail=""></option>';
            result +='<option value="gantiwarna" data-article-rm="none" data-detail="gantiwarna|none|6|||">Ganti Warna</option>';
            result +='<option value="istirahat"  data-article-rm="none" data-detail="istirahat|none|120|||">Istirahat</option>';
            objArticle.eq(objIndex).html(result);
            objArticle.eq(objIndex).select2();
            objArticle.removeAttr('disabled');
        }else{
            $.ajax({
                url:"{{route('dynamic.dependent')}}",
                method:"POST",
                data:{
                    value:value,
                    dependent:dependent
                },
                success:function(result){
                    objArticle.eq(objIndex).html(result);
                    objArticle.eq(objIndex).select2();
                    objArticle.removeAttr('disabled');
                }
            });
        }
    }

    function changeSelectArticleEdit(dependent,obj,value,article) {
        $('#'+obj).attr('disabled','disabled');
        if (value ==='other'){
            let result = "";
            result +='<option value="" data-article-rm="none" data-detail=""></option>';
            result +='<option value="gantiwarna" data-article-rm="none" data-detail="gantiwarna|none|6|||">Ganti Warna</option>';
            result +='<option value="istirahat"  data-article-rm="none" data-detail="istirahat|none|120|||">Istirahat</option>';
            $('#'+obj).html(result);
            $('#'+obj).val(article).trigger('change');
            $('#'+obj).removeAttr('disabled');
        }else{
            $.ajax({
                url:"{{route('dynamic.dependent')}}",
                method:"POST",
                data:{
                    value:value,
                    dependent:dependent
                },
                success:function(result){
                    $('#'+obj).html(result);
                    $('#'+obj).val(article).trigger('change');
                    $('#'+obj).removeAttr('disabled');
                }
            });
        }
    }

    let cloneCount=0;
    function add_new_row() {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        $("#new_row"+ cloneCount).find('#salesOrder').attr('id', 'salesOrder'+ cloneCount);
        $("#new_row"+ cloneCount).find('#urutan').attr('id', 'urutan'+ cloneCount);
        $('#urutan'+ cloneCount).val(cloneCount);
        changeselect('salesOrder','salesOrder'+ cloneCount,'','');
        $("#articleId"+cloneCount).select2();
        $("#salesOrder"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('qtyProd');
        activate_angka();
        mask_thousand_satuan();
        isiListArticle();
        updatQty();
    };

    function isiListArticle(){
        let objSo = $('#article_row select[name="salesOrder[]"]');
        objSo.change(function(e){        
            let objIndex = objSo.index(this);
            let soCode = objSo.eq(objIndex).val();
            if (soCode){
                changeSelectArticle('searchFromSO',objIndex,soCode);
                splitArticle();
            }
        });
    }

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objArticleRm = $('input[name="articleRm[]"]');
        let objQtyOrder = $('input[name="qtyOrder[]"]');
        let objQtyProd = $('input[name="qtyProd[]"]');
        let objTag = $('input[name="tag[]"]');
        let objTagAsli = $('input[name="tagAsli[]"]');
        let objWaktu = $('input[name="waktu[]"]');

        objArticle.change(function(e){        
            let objIndex = objArticle.index(this);
            let detail = objArticle.eq(objIndex).find(":selected").data("detail");
            let articleRm = objArticle.eq(objIndex).find(":selected").data("article-rm");
            if (detail){
                let arrDetail = detail.split("|");
                objArticleRm.eq(objIndex).val(articleRm);
                objQtyProd.eq(objIndex).val('');
                objQtyOrder.eq(objIndex).val(arrDetail[3]);
                objTag.eq(objIndex).val(arrDetail[2] || 0) ;
                objTagAsli.eq(objIndex).val(arrDetail[2] || 0) ;
                objWaktu.eq(objIndex).val($('#wosTime').val()+":00");
                if (detail){
                    setTimeout(() => {
                        objQtyProd.eq(objIndex).focus().select();
                    }, 5);
                }
                mask_thousand_satuan();
                // hitungWaktu();   
            }else{
                objQtyProd.eq(objIndex).val('');
                objQtyOrder.eq(objIndex).val('');
                objTag.eq(objIndex).val('');
                objTagAsli.eq(objIndex).val('');
                objWaktu.eq(objIndex).val('');
            }
		});
    }

    if (wosDate.length) {
        wosDate.flatpickr({
            dateFormat: "d-m-Y",
            minDate: currentDate
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
        
        objArticle.map(function(i) {
		    let $this=$(this);
            if ($this.val()){
                let article=$this.val();
                let articleRm = objArticleRm.eq(i).val();
                let urutan =objUrutan.eq(i).val();
                let soCode=objSoCode.eq(i).val();
                let qtyOrder=objQtyOrder.eq(i).val().replace(/,/gi, '') || 0;
                let qtyProd=objQtyProd.eq(i).val().replace(/,/gi, '') || 0;
                let qtyRepaint=objQtyRepaint.eq(i).val().replace(/,/gi, '') || 0;
                let tag =objTag.eq(i).val();
                let tagAsli = objTagAsli.eq(i).val();
                let waktu = objWaktu.eq(i).val();

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
                            "status": articleRm == 'none'?'0':'1'
                        });
                    }
                }
            }
        });

        if (articles.length > 0){
            articles.sort((a, b) => (a.urutan > b.urutan) ? 1 : -1);
            $('#article_row').find('div').remove();
            cloneCountEdit=0;
            articles.map(function(i) {
                add_new_row_edit(i.so_code,i.article_code,i.article_rm,i.qty_so,i.uom,i.qty_prod,i.qty_repaint,i.waktu,i.tag,i.tag_asli);
            })
        }
    });

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
                let nilaiTag = objTag.eq(i-1).val()*30;
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
        sumRemainTime.text(parseInt(sumTag)-timeReq-10);
    }

    updatQty=()=>{
        let objQtyProd = $('#article_row input[name="qtyProd[]"]');
        let objQtyRepaint = $('#article_row input[name="qtyRepaint[]"]');
        let objTag = $('#article_row input[name="tag[]"]');
        let objArticle = $('#article_row select[name="articleId[]"]');
        objQtyProd.keyup(function(e){        
            let objIndex = objQtyProd.index(this);
            let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
            let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
            let detail = objArticle.eq(objIndex).find(":selected").data("detail");
            if (detail){
                let arrDetail = detail.split("|");
                qtyTag = arrDetail[2].replace(/,/gi, '') || 0;
            }
            if (qtyProd || qtyRepaint){
                objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
            }else{
                objTag.eq(objIndex).val(qtyTag);
            }
            sumData();
            hitungWaktu();
		});

        objQtyRepaint.keyup(function(e){        
            let objIndex = objQtyRepaint.index(this);
            console.log(objIndex);
            let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
            let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
            let detail = objArticle.eq(objIndex).find(":selected").data("detail");
            if (detail){
                let arrDetail = detail.split("|");
                qtyTag = arrDetail[2].replace(/,/gi, '') || 0;
            }
            if (qtyProd || qtyRepaint){
                objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
            }else{
                objTag.eq(objIndex).val(qtyTag);
            }
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
                                "status": articleRm == 'none'?'0':'1'
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
                articles.sort((a, b) => (a.urutan > b.urutan) ? 1 : -1);
                $('#article_row').find('div').remove();
                cloneCountEdit=0;
                articles.map(function(i) {
                    add_new_row_edit(i.so_code,i.article_code,i.article_rm,i.qty_so,i.uom,i.qty_prod,i.qty_repaint,i.waktu,i.tag,i.tag_asli);
                })
            }else{
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let wosNumber = "";
                let urlKu="";
                if (oEdit.val()){
                    wosNumber = $('#wosNumber').val();
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
                        note:sNote
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

</script>