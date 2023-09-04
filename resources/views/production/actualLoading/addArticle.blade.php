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
            width:120%;
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
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-1 col-12" style="max-width: 5%;">
                <div class="form-group margin-nol">
                    <label for="urutan" class="d-block d-md-none">Urutan</label>
                    <input type="text" class="form-control numeral-mask-satuan drop" id="urutan" name="urutan[]" disabled >
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 15%;">
                <div class="form-group margin-nol">
                    <label for="salesOrder" class="d-block d-md-none">NO SPK / SO</label>
                    <input type="text" class="form-control" id="salesOrder" name="salesOrder[]" disabled/>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="form-group margin-nol">
                    <label for="articleName" class="d-block d-md-none">Article</label>
                    <input type="text" class="form-control" id="articleName" name="articleName[]" disabled />
                    <input type="hidden" class="form-control" id="articleId" name="articleId[]" />
                    <input type="hidden" class="form-control" id="articleRm" name="articleRm[]" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="tone" class="d-block d-md-none">Tone</label>
                    <select class="form-control" id="tone" name="tone[]" disabled>
                        <option value=""></option>
                        <option value="t1">Tone 1</option>
                        <option value="t2">Tone 2</option>
                        <option value="t3">Tone 3</option>
                        <option value="t4">Tone 4</option>
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyProdAct" class="d-block d-md-none">Act.Fresh</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id="qtyProdAct" name="qtyProdAct[]" maxlength="9" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyRepaintAct" class="d-block d-md-none">Act.Repaint</label>
                    <input type="text" class="form-control numeral-mask-satuan text-right" id = "qtyRepaintAct" name="qtyRepaintAct[]" maxlength="9" />
                </div>
            </div>
            <div class="col-md-1 col-12" style="max-width: 5%;">
                <div class="form-group margin-nol">
                    <label for="tagAct" class="d-block d-md-none">Act.Tag</label>
                    <input type="text" class="form-control text-right" id="tagAct" name="tagAct[]" >
                </div>
            </div>
            <div class="col-md-2 col-12" style="max-width: 10%;">
                <div class="form-group margin-nol">
                    <label for="waktuAct" class="d-block d-md-none">Act.Jam</label>
                    <input type="text" class="form-control" id="waktuAct" name="waktuAct[]" >
                </div>
            </div>
            <input type="hidden" class="form-control" id="tagAsli" name="tagAsli[]" disabled>
        </div>
    </div>
</div>
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    const wosNumber=$('#wosNumber');
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
    const sprayBooth = $('#sprayBooth');
    const oEdit = $('#oEdit');

    let cloneCountEdit=0;
    function add_new_row(noSo,noArticle,noArticleId,noArticleRm,qtySo,qtySoUom,qtyProd,qtyRepaint,waktu,tag,tagAsli,tone) {
        let waktuAwal = $('#wosTime').val()+":00";
        $("#article_row").append($("#new_row").clone().html());
        cloneCountEdit++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#urutan').attr('id', 'urutan'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#salesOrder').attr('id', 'salesOrder'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleName').attr('id', 'articleName'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleId').attr('id', 'articleId'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleRm').attr('id', 'articleRm'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyOrder').attr('id', 'qtyOrder'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyProd').attr('id', 'qtyProd'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyRepaint').attr('id', 'qtyRepaint'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyProdAct').attr('id', 'qtyProdAct'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyRepaintAct').attr('id', 'qtyRepaintAct'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#waktu').attr('id', 'waktu'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tag').attr('id', 'tag'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tagAsli').attr('id', 'tagAsli'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#waktuAct').attr('id', 'waktuAct'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tagAct').attr('id', 'tagAct'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tone').attr('id', 'tone'+ cloneCountEdit);
        $('#urutan'+ cloneCountEdit).val(cloneCountEdit);
        $('#qtyOrder'+ cloneCountEdit).val(qtySo);
        $('#qtyProd'+ cloneCountEdit).val(qtyProd);
        $('#qtyRepaint'+ cloneCountEdit).val(qtyRepaint);
        $('#waktu'+ cloneCountEdit).val(waktuAwal);
        $('#tag'+ cloneCountEdit).val(parseFloat(tagAsli)*(parseInt(qtyProd)+parseInt(qtyRepaint)));
        if(noSo =='other'){
            let nilaiTag = (parseInt(qtyProd)*parseFloat(tagAsli));
            $('#qtyProdAct'+ cloneCountEdit).val(qtyProd);
            $('#tagAct'+ cloneCountEdit).val(nilaiTag);
            $('#qtyRepaintAct'+ cloneCountEdit).attr('disabled','disabled');
        }
        $('#tagAsli'+ cloneCountEdit).val(tagAsli);
        $('#articleRm'+ cloneCountEdit).val(noArticleRm);
        $('#articleName'+ cloneCountEdit).val(noArticle);
        $('#articleId'+ cloneCountEdit).val(noArticleId);
        $('#salesOrder'+ cloneCountEdit).val(noSo);
        $('#tone'+ cloneCountEdit).val(tone);
        $('#tone'+ cloneCountEdit).select2();
        $('#remove_button').tooltip();
        // tombolPanah('qtyProdAct');
        // tombolPanah('qtyRepaintAct');
        mask_thousand_satuan();
        hitungWaktu(); 
        // updatQty();
        sumData();
    };

    function add_new_row_edit(noSo,noArticle,noArticleId,noArticleRm,qtySo,qtySoUom,qtyProd,qtyRepaint,waktu,tag,tagAsli,tone) {
        let waktuAwal = $('#wosTime').val()+":00";
        $("#article_row").append($("#new_row").clone().html());
        cloneCountEdit++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#urutan').attr('id', 'urutan'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#salesOrder').attr('id', 'salesOrder'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleName').attr('id', 'articleName'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleId').attr('id', 'articleId'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#articleRm').attr('id', 'articleRm'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyOrder').attr('id', 'qtyOrder'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyProd').attr('id', 'qtyProd'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyRepaint').attr('id', 'qtyRepaint'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyProdAct').attr('id', 'qtyProdAct'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#qtyRepaintAct').attr('id', 'qtyRepaintAct'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#waktu').attr('id', 'waktu'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tag').attr('id', 'tag'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tagAsli').attr('id', 'tagAsli'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#waktuAct').attr('id', 'waktuAct'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tagAct').attr('id', 'tagAct'+ cloneCountEdit);
        $("#new_row"+ cloneCountEdit).find('#tone').attr('id', 'tone'+ cloneCountEdit);
        $('#urutan'+ cloneCountEdit).val(cloneCountEdit);
        $('#qtyOrder'+ cloneCountEdit).val(qtySo);
        $('#qtyProd'+ cloneCountEdit).val(qtyProd);
        $('#qtyRepaint'+ cloneCountEdit).val(qtyRepaint);
        $('#waktu'+ cloneCountEdit).val(waktuAwal);
        $('#tag'+ cloneCountEdit).val(parseFloat(tagAsli)*(parseInt(qtyProd)+parseInt(qtyRepaint)));
        let nilaiTag = ((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(tagAsli));
        $('#qtyProdAct'+ cloneCountEdit).val(qtyProd);
        $('#qtyRepaintAct'+ cloneCountEdit).val(qtyRepaint);
        $('#tagAct'+ cloneCountEdit).val(nilaiTag);
        $('#tagAct'+ cloneCountEdit).attr('disabled','disabled');
        $('#tone'+ cloneCountEdit).val(tone);

        if(noSo =='other'){
            $('#qtyRepaintAct'+ cloneCountEdit).attr('disabled','disabled');
        }
        $('#tagAsli'+ cloneCountEdit).val(tagAsli);
        $('#articleRm'+ cloneCountEdit).val(noArticleRm);
        $('#articleName'+ cloneCountEdit).val(noArticle);
        $('#articleId'+ cloneCountEdit).val(noArticleId);
        $('#salesOrder'+ cloneCountEdit).val(noSo);
        $('#tone'+ cloneCountEdit).select2();

        tombolPanah('qtyProdAct');
        tombolPanah('qtyRepaintAct');
        mask_thousand_satuan();
        hitungWaktu(); 
        // updatQty();
        sumData();
    };

    approve = (prdNumber,objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "POST",
            url: "{{ route('production.actualLoading.approve') }}",
            data: {
                prdNumber:prdNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#prdNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#prdNumber').attr('disabled','disabled');
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

    if (wosTime.length) {
        wosTime.flatpickr({
            enableTime: true,
            time_24hr: true,
            noCalendar: true,
            // defaultDate: "08:00:00",
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
        let objTone = $("#article_row select[name='tone[]']");
        
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
                let tone = objTone.eq(i).val();

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
            }
        });

        if (articles.length > 0){
            articles.sort((a, b) => (a.urutan > b.urutan) ? 1 : -1);
            $('#article_row').find('div').remove();
            cloneCountEdit=0;
            articles.map(function(i) {
                add_new_row_edit(i.so_code,i.article_code,i.article_rm,i.qty_so,i.uom,i.qty_prod,i.qty_repaint,i.waktu,i.tag,i.tag_asli,i.tone);
            })
        }
    });

    hitungWaktu = (s) => {
        let objWaktu = $('#article_row input[name="waktuAct[]"]');
        let objTag = $('#article_row input[name="tagAct[]"]');
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
        let objTag = $('#article_row input[name="tagAct[]"]');
        let efficiency = $('#efficiency').val() || 1;
        let dataTag = objTag.map(function(){return $(this).val();}).get();
        let sumTag = sumFromArray(dataTag);
        let timeReq = parseInt((workHour.val())*3600*(parseInt(efficiency)/100)/30);
        noEfficiency.text(efficiency);
        sumWorkHour.text(workHour.val());
        sumTimeRequired.text(timeReq);
        sumAvailableTime.text(sumTag);
        // sumRemainTime.text(parseInt(sumTag)-timeReq-10);
        sumRemainTime.text(parseInt(sumTag)-timeReq);
    }

    $(document).on('keyup', "#article_row input[name='qtyProdAct[]']",function () {  
        // do something here
        let objIndex = $("#article_row input[name='qtyProdAct[]']").index(this);
        let objQtyProd = $('#article_row input[name="qtyProdAct[]"]');
        let objTone = $('#article_row select[name="tone[]"]');
        let objQtyRepaint = $('#article_row input[name="qtyRepaintAct[]"]');
        let objTag = $('#article_row input[name="tagAct[]"]');
        let objTagAsli = $('#article_row input[name="tagAsli[]"]');
        let objArticle = $('#article_row input[name="articleId[]"]');

        let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
        let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
        // let qtyTag = objTagAsli.eq(objIndex).val().replace(/,/gi, '') || 0;

        let tone = objTone.eq(objIndex).val();
        let articleCode = objArticle.eq(objIndex).val();
        let aSprayBooth = $('#sprayBooth').val();

        let qtyTag = getTack(articleCode,aSprayBooth,tone) || 0;
        
        if (qtyProd || qtyRepaint){
            objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
        }else{
            objTag.eq(objIndex).val(qtyTag);
        }
        sumData();
        hitungWaktu();
        
    });

    $(document).on('keyup', "#article_row input[name='qtyRepaintAct[]']",function () {  
        // do something here
        let objIndex = $("#article_row input[name='qtyRepaintAct[]']").index(this);
        let objQtyProd = $('#article_row input[name="qtyProdAct[]"]');
        let objTone = $('#article_row select[name="tone[]"]');
        let objQtyRepaint = $('#article_row input[name="qtyRepaintAct[]"]');
        let objTag = $('#article_row input[name="tagAct[]"]');
        let objTagAsli = $('#article_row input[name="tagAsli[]"]');
        let objArticle = $('#article_row input[name="articleId[]"]');

        let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
        let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
        // let qtyTag = objTagAsli.eq(objIndex).val().replace(/,/gi, '') || 0;

        let tone = objTone.eq(objIndex).val();
        let articleCode = objArticle.eq(objIndex).val();
        let aSprayBooth = $('#sprayBooth').val();

        let qtyTag = getTack(articleCode,aSprayBooth,tone) || 0;
        
        if (qtyProd || qtyRepaint){
            objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
        }else{
            objTag.eq(objIndex).val(qtyTag);
        }
        sumData();
        hitungWaktu();
        
    });

    $(document).on('change', "#article_row select[name='tone[]']",function () {  
        // do something here
        let objIndex = $("#article_row select[name='tone[]']").index(this);
        let objQtyProd = $('#article_row input[name="qtyProdAct[]"]');
        let objTone = $('#article_row select[name="tone[]"]');
        let objQtyRepaint = $('#article_row input[name="qtyRepaintAct[]"]');
        let objTag = $('#article_row input[name="tagAct[]"]');
        let objTagAsli = $('#article_row input[name="tagAsli[]"]');
        let objArticle = $('#article_row input[name="articleId[]"]');

        let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
        let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
        // let qtyTag = objTagAsli.eq(objIndex).val().replace(/,/gi, '') || 0;

        let tone = objTone.eq(objIndex).val();
        let articleCode = objArticle.eq(objIndex).val();
        let aSprayBooth = $('#sprayBooth').val();

        let qtyTag = getTack(articleCode,aSprayBooth,tone) || 0;
        
        if (qtyProd || qtyRepaint){
            objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
        }else{
            objTag.eq(objIndex).val(0);
        }
        sumData();
        hitungWaktu();
        
    });

    cmdSave.click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            let articles = []; 
            let flag=0;
            let pesan="";
            let objArticle = $("#article_row input[name='articleId[]']");
            let objArticleRm = $("#article_row input[name='articleRm[]']");
            let objQtyProd = $('#article_row input[name="qtyProdAct[]"]');
            let objQtyRepaint = $('#article_row input[name="qtyRepaintAct[]"]');
            let objSoCode = $('#article_row input[name="salesOrder[]"]');
            let objTag = $('#article_row input[name="tagAct[]"]');
            let objTagAsli = $('#article_row input[name="tagAsli[]"]');
            let objUrutan = $('#article_row input[name="urutan[]"]');
            let objWaktu = $('#article_row input[name="waktuAct[]"]');
            let objTone = $('#article_row select[name="tone[]"]');
            let sWosNumber = wosNumber.val();
            let sWosTime = wosTime.val();
            let sWorkHour = workHour.val();
            let sEfficiency = efficiency.val();
            let sNote = note.val();
            let sSprayBooth = sprayBooth.val();

            objArticle.map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article = $this.val();
                    let articleName = article;
                    let articleRm = objArticleRm.eq(i).val();
                    let urutan = objUrutan.eq(i).val();
                    let soCode = objSoCode.eq(i).val();
                    let qtyProd = objQtyProd.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyRepaint = objQtyRepaint.eq(i).val().replace(/,/gi, '') || 0;
                    let tag = objTag.eq(i).val() || 0;
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
                                "uom":'PCS',
                                "act_qty_prod":qtyProd,
                                "act_qty_repaint":qtyRepaint,
                                "act_tag":tag,
                                "tag_asli":tagAsli,
                                "act_waktu":waktu,
                                "status": articleRm == 'none'?'0':'1',
                                "tone":tone
                            });
                        }
                    }
                }
            });

            if (flag==0){
                let prdNumber = "";
                let urlKu="";
                if (oEdit.val()){
                    prdNumber = $('#prdNumber').val();
                    urlKu ="{{ route('production.actualLoading.update') }}";
                }else{
                    urlKu ="{{ route('production.actualLoading.store') }}";
                }
                $.ajax({
                    type: "POST",
                    url: urlKu,
                    data: {
                        articles:JSON.stringify(articles),
                        prdNumber:prdNumber,
                        wosNumber:sWosNumber,
                        wosTime:sWosTime,
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
                            $('#prdNumber').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert)
                            $('#prdNumber').attr('disabled','disabled');
                            $('#prdNumber').val(data.prdNumber);
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
    
    posting = (prodNumber) =>{
        $.ajax({
            type: "post",
            url: "{{ route('production.actualLoading.posting') }}",
            data: { prodNumber:prodNumber},
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }

                }else{
                    show_msg(data.title, data.message, data.alert)
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    function getTack(articleCode,sprayBooth,tone,objIndex) {
        let hasil = false;
        $.ajax({
            url:"{{ route('workingOrderSheet.get.tack') }}",
            method:"GET",
            data:{
                articleCode:articleCode,
                sprayBooth:sprayBooth,
                tone:tone
            },
            async: false,
            success:function(result){
                hasil = result;
            }
        })

        return hasil;
    }

    function getTackHitung(articleCode,sprayBooth,tone,objIndex) {
        $.ajax({
            url:"{{ route('workingOrderSheet.get.tack') }}",
            method:"GET",
            data:{
                articleCode:articleCode,
                sprayBooth:sprayBooth,
                tone:tone
            },
            success:function(result){
                let objTag = $('input[name="tag[]"]');
                let objTagAsli = $('input[name="tagAsli[]"]');
                let objQtyProd = $('#article_row input[name="qtyProd[]"]');
                let objQtyRepaint = $('#article_row input[name="qtyRepaint[]"]');
                let qtyProd = objQtyProd.eq(objIndex).val().replace(/,/gi, '') || 0;
                let qtyRepaint = objQtyRepaint.eq(objIndex).val().replace(/,/gi, '') || 0;
                let qtyTag = result || 0;

                if (qtyProd || qtyRepaint){
                    objTag.eq(objIndex).val((parseInt(qtyProd)+parseInt(qtyRepaint))*parseFloat(qtyTag));
                }else{
                    objTag.eq(objIndex).val(qtyTag);
                }
            }
        })
    }

</script>