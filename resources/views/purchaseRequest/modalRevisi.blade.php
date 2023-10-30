<div class="modal fade text-left bisa-geser" id="reasonModalRevisionTso" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Revision<span class="semi-bold"> @yield('title')</span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="modalReasonRevisionTso" action="" method="post" autocomplete="off">
              @csrf
              <div class="modal-body">
                <div class="text-center">
                  <i data-feather='alert-circle' class='feather-72-red'></i>
                  <h1 class="text-center">
                      Are you sure you want to revise this number?
                  </h1>
                </div>
                <br>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="mdlStock">Stock Date</label>
                        <input type="text" id="mdlStockDate" name="mdlStockDate" class="form-control" value="{{ old('mdlStockDate') }}" required/>
                    </div>
                </div>
                <div class="form-row">
                  <div class="form-group col-md-12">
                      <label for="reason">Reason</label>
                      <input type="text" id="reason" name="reason" class="form-control" value="{{ old('reason') }}"  required maxlength="100"/>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark waves-effect" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger waves-effect">Yes, Revise It</button>
              </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">     
    $('#reasonModalRevisionTso').on('shown.bs.modal', function(e) {
        if ($('#mdlStockDate').length) {
        $("#date,#mdlStockDate").flatpickr({
            dateFormat: "d-m-Y",
            // static : true 
        });
        } 
    });
</script>

