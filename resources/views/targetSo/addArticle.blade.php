{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">No</label>
                    <span class="input-group-text" id ="number" name="number[]"></span>
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article</label>
                    <select class="form-control" id="articleId" name="articleId[]" data-dependent="articleId">
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyTarget" class="d-block d-md-none">QTY Target</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah" 
                        data-type-el-kiri="select" 
                        data-nama-el-kiri='articleId'
                        data-type-el-kanan='input'
                        data-nama-el-kanan='qtyForcast'
                        id ="qtyTarget" name="qtyTarget[]" maxlength="9" />
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyForcast" class="d-block d-md-none">QTY Forcast</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah" 
                        data-type-el-kiri="input" 
                        data-nama-el-kiri='qtyTarget'
                        data-type-el-kanan='select'
                        data-nama-el-kanan='articleId'
                        id ="qtyForcast" name="qtyForcast[]" maxlength="9" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol text-center">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- \.table row --}} 

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

<script type="text/javascript">
    const currentDate = "{{ $currentDateValue }}";
    const orderDate = $('#orderDate');
    let cloneCount = 0;
        
    if (orderDate.length) {
        orderDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }
  
    $("#cmdNew").click(function(){
        window.location.reload();
    });

    simpanData = (objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
            $('#'+objButton).removeAttr('disabled');
        }else{
            let objQtyTarget= $('#article_row input[name="qtyTarget[]"]');
            let objQtyForcast= $('#article_row input[name="qtyForcast[]"]');
            let objNote= $('#article_row input[name="note[]"]');
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row select[name='articleId[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleName=$this.select2('data')[0].text;
                    let plu=$this.val();
                    let qtyTarget=objQtyTarget.eq(i).val().replace(/,/gi,'')||0;
                    let qtyForcast=objQtyForcast.eq(i).val().replace(/,/gi,'')||0;
                    let note=objNote.eq(i).val();
                    let uom=$this.eq(i).find(":selected").data("uom")||'PCS';
                
                    // es6
                    let obj = articles.find(obj => obj.plu == plu);
                    
                    if(obj) {
                        pesan +="Article "+articleName+" entered more than once !! <br>"; 
                        flag=1;
                    } else {
                        // if ((plu!=='') && (qtyTarget > 0) && (qtyForcast > 0)){
                        if ((plu!=='') && (qtyTarget > 0)){
                            articles.push({
                                "article_code":plu,
                                "qtyTarget":qtyTarget,
                                "qtyForcast":qtyForcast,
                                "uom":uom
                            });
                        }
                    } 
                
                    // if (qtyTarget == 0 || qtyForcast == 0){
                    //     pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                    //     flag=1;
                    // }

                    /* Permintaan dari ASN qty forcast boleh 0*/
                    if ( qtyTarget == 0 ){
                        pesan +="QTY Target of items "+ articleName +" cannot be 0 <br>"; 
                        flag=1;
                    }
                
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                $('.disabled-el').removeAttr('disabled');
                let tsoDate = $('#tsoDate').val();
                let tsoName = $('#tsoName').val();
                let note = $('#note').val();
                let url ="";
                let tsoCode = "";
                $.ajax({
                    type: "post",
                    url: "{{ route('targetSo.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        tsoDate:tsoDate,
                        tsoName:tsoName,
                        note:note,
                        tsoCode:tsoCode
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#tsoCode').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#tsoCode').attr('disabled','disabled');
                            $('#tsoCode').val(data.tsoCode);
                            $('#'+objButton).removeAttr('disabled');
                            $('#oEdit').val(data.oEdit);
                        }
                    },
                    error: function(error) {
                        Swal.fire('Warning..',error,'warning');
                    }
                });

            }else{
                $('#'+objButton).removeAttr('disabled');
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    }

    updateData = (objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{  
            $('.disabled-el').removeAttr('disabled');
            let objQtyTarget= $('#article_row input[name="qtyTarget[]"]');
            let objQtyForcast= $('#article_row input[name="qtyForcast[]"]');
            let objUom= $('#article_row span[name="uom[]"]');
            let objNote= $('#article_row input[name="note[]"]');
            let articles = []; 
            let flag=0; 
            let pesan="";
            
            $("#article_row select[name='articleId[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleName=$this.select2('data')[0].text;
                    let plu=$this.val();
                    let qtyTarget=objQtyTarget.eq(i).val().replace(/,/gi,'')||0;
                    let qtyForcast=objQtyForcast.eq(i).val().replace(/,/gi,'')||0;
                    let note=objNote.eq(i).val();
                    let uom=objUom.eq(i).text();
                
                    // es6
                    let obj = articles.find(obj => obj.plu == plu);
                    
                    if(obj) {
                        pesan +="Article "+articleName+" entered more than once !! <br>"; 
                        flag=1;
                    } else {
                        // if ((plu!=='') && (qtyTarget > 0) && (qtyForcast > 0)){
                        if ((plu!=='') && (qtyTarget > 0)){
                            articles.push({
                                "article_code":plu,
                                "qtyTarget":qtyTarget,
                                "qtyForcast":qtyForcast,
                                "uom":uom
                            });
                        }
                    } 
                    
                    /* Permintaan dari ASN qty forcast boleh 0*/
                    // if (qtyTarget == 0 || qtyForcast == 0){
                    //     pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                    //     flag=1;
                    // }

                    if ( qtyTarget == 0 ){
                        pesan +="QTY Target of items "+ articleName +" cannot be 0 <br>"; 
                        flag=1;
                    }
                
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let tsoDate = $('#tsoDate').val();
                let tsoName = $('#tsoName').val();
                let customer = "none";
                let tsoCode = $('#tsoCode').val();
                let note = $('#note').val();
                $.ajax({
                    type: "post",
                    url: "{{ route('targetSo.update') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        tsoCode:tsoCode,
                        tsoDate:tsoDate,
                        tsoName:tsoName,
                        customer:customer,
                        note:note
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#tsoCode').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#tsoCode').attr('disabled','disabled');
                            $('#tsoCode').val(data.tsoCode);
                            $('#'+objButton).removeAttr('disabled');
                        }
                    },
                    error: function(error) {
                        Swal.fire('Warning..',error,'warning');
                    }
                });
            }else{
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    }

    approve = (tsoCode,objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "GET",
            url: "{{ route('targetSo.approve') }}",
            data: {
                tsoCode:tsoCode
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#tsoCode').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#tsoCode').attr('disabled','disabled');
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

    function add_new_row() {
        let poType = $('#poType').val();
            $("#article_row").append($("#new_row").clone().html());
            cloneCount++;
            $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
            $("#new_row"+ cloneCount).find('#number').attr('id', 'number'+ cloneCount);
            $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
            changeselect('tsoArticle','articleId'+ cloneCount,'','');
            $('#number'+ cloneCount).text(cloneCount);
            $("#articleId"+cloneCount).select2();
            $('#remove_button').tooltip();
            tombolPanah('qtyTarget','','qtyForcast');
            tombolPanah('qtyForcast','qtyTarget','');
            mask_thousand_satuan();
            // splitArticle();
            hitungTotal();
            hitungGrandTotal();
            $('[data-toggle="tooltip"]').tooltip();
    };

    function add_new_row_edit(articleId,qtyTarget,qtyForcast) {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#number').attr('id', 'number'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        changeselect('tsoArticle','articleId'+ cloneCount,'','');
        $("#new_row"+ cloneCount).find('#qtyTarget').attr('id', 'qtyTarget'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyForcast').attr('id', 'qtyForcast'+ cloneCount);
        $('#articleId'+ cloneCount).val(articleId);
        $('#qtyTarget'+ cloneCount).val(qtyTarget);
        $('#qtyForcast'+ cloneCount).val(qtyForcast);
        $('#number'+ cloneCount).text(cloneCount);
        $("#articleId"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('qtyTarget','','qtyForcast');
        tombolPanah('qtyForcast','qtyTarget','');
        mask_thousand_satuan();
        // splitArticle();
        hitungTotal();
        hitungGrandTotal();
        $('[data-toggle="tooltip"]').tooltip();
    };

    function isiArticle(dependent) {
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                dependent:dependent
            },
            success:function(result){
                dataArticle = result;
            }
        })
    }
   
    function changeselect(dependent,obj,article) {
        $('#'+obj).attr('disabled','disabled');
        $('#'+obj).html(dataArticle);
        $('#'+obj).select2();
        $('#'+obj).val(article).trigger('change');
        $('#'+obj).removeAttr('disabled');
        $('#'+obj).select2('focus');
    }

    function splitArticle(){
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objQtyTarget= $('#article_row input[name="qtyTarget[]"]'); 
        let objUomTarget= $('#article_row span[name="uomTarget[]"]'); 
        let objUomForcast= $('#article_row span[name="uomForcast[]"]'); 
        objArticle.change(function(e){   
            let objIndex = objArticle.index(this);
            let uomGroup = objArticle.eq(objIndex).find(":selected").data("uom-group");
            objUomTarget.eq(objIndex).text(objArticle.eq(objIndex).find(":selected").data("uom"));
            objUomForcast.eq(objIndex).text(objArticle.eq(objIndex).find(":selected").data("uom"));
            if (uomGroup){
                setTimeout(() => {
                    objQtyTarget.eq(objIndex).focus().select();
                }, 5);
            }
		});
    }

    hitungTotal = () => {
        let objQtyTarget= $('#article_row input[name="qtyTarget[]"]');
        let objQtyForcast= $('#article_row input[name="qtyForcast[]"]');
        objQtyTarget.keyup(function() {
            hitungGrandTotal();
        });    
        objQtyForcast.keyup(function() {
            hitungGrandTotal();
        });    
    }
      
    hitungGrandTotal = ()=>{
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objQtyTiw= $('#article_row input[name="qty_order[]"]');
        let objQTYTarget= $('#article_row input[name="qtyTarget[]"]');
        let objQTYForcast= $('#article_row input[name="qtyForcast[]"]');
        let totalQtyTarget= 0;
        let totalQtyForcast= 0;
        let qtyTarget = objQTYTarget.map(function(){return $(this).val();}).get();
        let qtyForcast = objQTYForcast.map(function(){return $(this).val();}).get();
        totalQtyTarget = sumFromArray(qtyTarget);
        totalQtyForcast = sumFromArray(qtyForcast);
        // objArticle.length>0 ?$('#customer').attr('disabled','disabled'):$('#customer').removeAttr('disabled');
        $("#totalRow").val(objArticle.length);
        $("#totalQtyTarget").val(humanizeNumber(totalQtyTarget));
        $("#totalQtyForcast").val(humanizeNumber(totalQtyForcast));
    }

    $("input[type='text']").click(function () {
        $(this).select();
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>