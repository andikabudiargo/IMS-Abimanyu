{{-- table row untuk di clone--}}  
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-6 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article</label>
                    <select class="form-control" id="articleId" name="articleId[]" data-dependent="articleId">
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="qty" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask-digit text-right tombol-panah" 
                        data-type-el-kiri="select" 
                        data-nama-el-kiri='articleId'
                        data-type-el-kanan='input'
                        data-nama-el-kanan='note'
                        id ="qty" name="qty[]" maxlength="10" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="uom" class="d-block d-md-none">Uom</label>
                    <select class="form-control" id="uom" name="uom[]">
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="note" class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control tombol-panah"
                        data-type-el-kiri="input"
                        data-nama-el-kiri='qty'
                        id = "note" name="note[]"  maxlength="150">
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

<div id="new_row_show" name="new_row_show[]" class="d-none">
    <div id="baru_show">
        <div class="form-row d-flex align-items-center">
            <div class="col-md-6 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article</label>
                    <input type="text" class="form-control" id="articleIdShow" name="articleIdShow[]" disabled>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="qtyShow" class="d-block d-md-none">QTY</label>
                    <div class="input-group input-group-merge">
                        <input type="text" class="form-control numeral-mask-satuan text-right" id = "qtyShow" name="qtyShow[]" maxlength="9" />
                        <div class="input-group-append">
                            <span class="input-group-text" id ="uomShow" name="uomShow[]"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="noteShow" class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control" id = "noteShow" name="noteShow[]"  maxlength="150">
                </div>
            </div>
        </div>
    </div>
</div>

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
            width:100%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px){
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
    const trDate = $('#trDate');
    let dataArticle=""; 
    let lockedAt = "{{ $lockDate }}";

    if (trDate.length) {
        trDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today",
            minDate:lockedAt
        });
    }
  
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel,#cmdNew").click(function(){
        reloadPage();
    });

    simpanData = (oEdit) => {
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            let objQty= $('#article_row input[name="qty[]"]');
            let objUom= $('#article_row select[name="uom[]"]');
            let objNote= $('#article_row input[name="note[]"]');            
            let arrArticles = [];
            let articles;
            let flag=0; 
            let pesan="";

            $("#article_row select[name='articleId[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleName=$this.select2('data')[0].text;
                    let plu=$this.val();
                    let qty=objQty.eq(i).val().replace(/,/gi,'')||0;
                    let note=objNote.eq(i).val();
                    let uom=objUom.eq(i).val();
                
                    // es6
                    // let obj = articles.find(obj => obj.plu == plu);
                    
                    // if(obj) {
                    //     pesan +="Article "+articleName+" entered more than once !! <br>"; 
                    //     flag=1;
                    // } else {
                        if ((plu!=='') && (qty > 0)){
                            arrArticles.push({
                                "article_code":plu,
                                "qty":parseFloat(qty),
                                "uom":uom,
                                "note":note
                            });
                        }
                    // } 
                    if (qty == 0 ){
                        pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                        flag=1;
                    }
                
                }
            });

            if (arrArticles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }else{
                //summary data by article_code
                let obj = {}
                arrArticles.forEach((item)=>{
                    if(obj[item.article_code]){
                        obj[item.article_code].qty = obj[item.article_code].qty + item.qty
                    }else{
                        obj[item.article_code] = item
                    }
                })
                articles = Object.values(obj)
            }

            if (flag==0){
                let trNumber = "";
                if (oEdit){
                    trNumber = $('#trNumber').val();
                    url ="{{ route('transferIn.update') }}";
                }else{
                    url ="{{ route('transferIn.store') }}";
                }
                
                let trDate = $('#trDate').val();
                let note = $('#note').val();
                $.ajax({
                    type: "post",
                    url: url,
                    data: {
                        articles:JSON.stringify(articles),
                        trNumber:trNumber,
                        trDate:trDate,
                        note:note
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#trNumber').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#trNumber').attr('disabled','disabled');
                            $('#trNumber').val(data.trNumber);
                            // $('#oEdit').val(data.oEdit);
                            if(oEdit==false){
                                window.location.href = "{{ route('transferIn.create') }}";
                            }
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
    }

    approve = (trNumber,objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "GET",
            url: "{{ route('transferIn.approve') }}",
            data: {
                trNumber:trNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#trNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#trNumber').attr('disabled','disabled');
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

    let cloneCount=0;
    add_new_row_edit = (article,qty,uom,uomMember,note) => {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        changeselect('trArticle','articleId'+ cloneCount,article);
        $("#new_row"+ cloneCount).find('#qty').attr('id', 'qty'+ cloneCount);
        $("#qty"+ cloneCount).val(humanizeNumber(qty,2));
        $("#new_row"+ cloneCount).find('#note').attr('id', 'note'+ cloneCount);
        $("#note"+ cloneCount).val(note);
        let uomOption="";
        if (uomMember){
            let arrUomMember = uomMember.split(',');
            $.each(arrUomMember, function(index, val) {
                uomOption +=`<option>${val}</option>`;
            });
        }else{
            if(uom){
                uomOption +=`<option>${uom}</option>`;
            }
        }
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $("#uom"+ cloneCount).html(uomOption);
        $("#uom"+ cloneCount).val(uom).trigger('change');
        $('#remove_button').tooltip();
        hitungTotal();
        hitungGrandTotal();
        mask_thousand_digit(2);
    }

    function add_new_row() {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        changeselect('trArticle','articleId'+ cloneCount,'');
        $('#remove_button').tooltip();
        splitArticle();
        hitungTotal();
        hitungGrandTotal();
        mask_thousand_digit(2);
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
        let objQty= $('#article_row input[name="qty[]"]'); 
        let objUom= $('#article_row select[name="uom[]"]'); 
        objArticle.change(function(e){   
            if ($(this).val()){
                let objIndex = objArticle.index(this);
                let uomGroup = objArticle.eq(objIndex).find(":selected").data("uom-group");
                let uomMember = objArticle.eq(objIndex).find(":selected").data("uom-member");
                let uom = objArticle.eq(objIndex).find(":selected").data("uom");
                let uomOption="";
                if (uomMember){
                    let arrUomMember = uomMember.split(',');
                    $.each(arrUomMember, function(index, val) {
                        uomOption +=`<option>${val}</option>`;
                    });
                }else{
                    if(uom){
                        uomOption +=`<option>${uom}</option>`;
                    }
                }
                objUom.eq(objIndex).html(uomOption);
                objUom.eq(objIndex).val(uom).trigger('change');

                if (uomMember){
                    setTimeout(() => {
                        objQty.eq(objIndex).focus().select();
                    }, 5);
                }
            }
		});
    }

    hitungTotal = () => {
        let objQty= $('#article_row input[name="qty[]"]');
        objQty.keyup(function() {
            hitungGrandTotal();
        });    
    }
      
    hitungGrandTotal = ()=>{
        let objArticle = $('#article_row select[name="articleId[]"]');
        // let objQtyTiw= $('#article_row input[name="qty_order[]"]');
        // let objQTY= $('#article_row input[name="qty[]"]');
        // let totalQty= 0;
        // let qty = objQTY.map(function(){return $(this).val();}).get();
        // totalQty = sumFromArray(qty);
        $("#totalRow").val(objArticle.length);
        // $("#totalQty").val(humanizeNumber(parseFloat(totalQty).toFixed(2)));
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