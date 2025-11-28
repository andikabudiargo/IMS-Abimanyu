<style>    
    #article_row_sb .form-group {
        margin-bottom: 0.5rem;
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
<div id="new_row_sb" name="new_row_sb[]" class="d-none">
    <div id="baru_sb" class="tanda-baris-sp" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none" for="sprayBooth">Spray booth</label>
                    <select class="select2 form-control" id="sprayBooth" name="sprayBooth[]" required>
                        <option value=""></option>
                        <option value="sb1">Spraybooth 1</option>
                        <option value="sb1a">Spraybooth 1 A</option>
                        <option value="sb1b">Spraybooth 1 B</option>
                        <option value="sb1c">Spraybooth 1 C</option>
                        <option value="sb2">Spraybooth 2</option>
                        <option value="sb2a">Spraybooth 2 A</option>
                        <option value="sb2b">Spraybooth 2 B</option>
                        <option value="sb2c">Spraybooth 2 C</option>
                        <option value="sb3">Spraybooth 3</option>
                        <option value="sb3a">Spraybooth 3 A</option>
                        <option value="sb3b">Spraybooth 3 B</option>
                        <option value="sb3c">Spraybooth 3 C</option>
                        <option value="sb4">Spraybooth 4</option>
                        <option value="sb4a">Spraybooth 4 A</option>
                        <option value="sb4b">Spraybooth 4 B</option>
                        <option value="sb4c">Spraybooth 4 C</option>
                        <option value="sbtoto">Toto</option>
                    </select>
                </div>
            </div>
            {{-- <div class="col-md-2 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none" for="stripping">Stripping</label>
                    <select class="select2 form-control" id="stripping" name="stripping[]" required>
                        <option value=""></option>
                        <option value="t1">Tone 1</option>
                        <option value="t2">Tone 2</option>
                        <option value="t3">Tone 3</option>
                        <option value="t4">Tone 4</option>
                    </select>
                </div>
            </div> --}}
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label class="d-block d-md-none" for="tone">Tone</label>
                    <select class="select2 form-control" id="tone" name="tone[]" required>
                        <option value=""></option>
                        <option value="t1">Tone 1</option>
                        <option value="t2">Tone 2</option>
                        <option value="t3">Tone 3</option>
                        <option value="t4">Tone 4</option>
                    </select>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="tack" class="d-block d-md-none">Tack*</label>
                    <input type="text" id="tack" name="tack[]" value="{{ old('tack') }}" class="form-control numeral-mask-digit tombol-panah" maxlength="5" required/>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group">
                    <label for="passRate" class="d-block d-md-none">Pass Rate*</label>
                    <input type="text" id="passRate" name="passRate[]" value="{{ old('passRate') }}" class="form-control numeral-mask-digit tombol-panah" maxlength="5" required/>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="passThru" class="d-block d-md-none">Pass trough*</label>
                    <div class="input-group input-group-merge">
                        <input type="text" id="passThru" name="passThru[]" value="{{ old('passThru') }}" class="form-control numeral-mask-digit tombol-panah" maxlength="5" required/>
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group">
                    <label for="cycleTime" class="d-block d-md-none">Cycle time buffing</label>
                    <input type="text" id="cycleTime" name="cycleTime[]" value="{{ old('cycleTime') }}" class="form-control numeral-mask-digit tombol-panah" maxlength="5" required/>
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group text-center">
                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris-sp').remove();">
                        <i data-feather="trash-2" class="remove_button feather-24">
                        </i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="d-block d-md-none" />
    </div>
</div>
<script type="text/javascript">
    let cloneCountSb=0;
    add_new_row_edit_sb = (sprayBooth,tone,tack,passRate,passThru,cycleTime,stripping) => {
        $("#article_row_sb").append($("#new_row_sb").clone().html());
        cloneCountSb++;
        console.log(sprayBooth);
        $("#article_row_sb").find('#baru_sb').attr('id', 'new_row_sb'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#sprayBooth').attr('id', 'sprayBooth'+ cloneCountSb);
        // $("#new_row_sb"+ cloneCountSb).find('#stripping').attr('id', 'stripping'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#tone').attr('id', 'tone'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#tack').attr('id', 'tack'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#passRate').attr('id', 'passRate'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#passThru').attr('id', 'passThru'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#cycleTime').attr('id', 'cycleTime'+ cloneCountSb);
        $("#sprayBooth"+cloneCountSb).select2();
        $("#tone"+cloneCountSb).select2();
        // $("#stripping"+cloneCountSb).select2();
        $("#sprayBooth"+ cloneCountSb).val(sprayBooth).trigger('change');
        // $("#stripping"+ cloneCountSb).val(stripping).trigger('change');
        $("#tone"+ cloneCountSb).val(tone).trigger('change');
        $("#tack"+ cloneCountSb).val(tack);
        $("#passRate"+ cloneCountSb).val(passRate);
        $("#passThru"+ cloneCountSb).val(passThru);
        $("#cycleTime"+ cloneCountSb).val(cycleTime);
        
        $('#remove_button').tooltip();
        mask_thousand_digit(numberOfDecimalDigit);
    }
    
    add_new_row_sb = () => {
        $("#article_row_sb").append($("#new_row_sb").clone().html());
        cloneCountSb++;
        $("#article_row_sb").find('#baru_sb').attr('id', 'new_row_sb'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#sprayBooth').attr('id', 'sprayBooth'+ cloneCountSb);
        // $("#new_row_sb"+ cloneCountSb).find('#stripping').attr('id', 'stripping'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#tone').attr('id', 'tone'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#tack').attr('id', 'tack'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#passRate').attr('id', 'passRate'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#passThru').attr('id', 'passThru'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#qtyCon').attr('id', 'qtyCon'+ cloneCountSb);
        $("#new_row_sb"+ cloneCountSb).find('#cycleTime').attr('id', 'cycleTime'+ cloneCountSb);
        $("#sprayBooth"+cloneCountSb).select2();
        $("#tone"+cloneCountSb).select2();
        // $("#stripping"+cloneCountSb).select2();
        $('#remove_button').tooltip();
        mask_thousand_digit(numberOfDecimalDigit);
    };
</script>