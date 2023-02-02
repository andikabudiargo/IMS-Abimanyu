<!-- Modal delete-->
<div class="modal fade text-left bisa-geser" id="smallModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Delete<span class="semi-bold"> @yield('title')</span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="modalConfirmation" action="" method="post" >
              @csrf
              <div class="modal-body  text-center">
                <i data-feather='alert-circle' class='feather-72-red'></i>
                <h1 class="text-center">
                     Are you sure you want to delete?
                </h1>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-dark waves-effect" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger waves-effect">Yes, Delete</button>
              </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade text-left bisa-geser" id="smallModalCancel" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h4>Delete<span class="semi-bold"> @yield('title')</span></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <form id="modalConfirmationCancel" action="" method="post" >
            @csrf
            <div class="modal-body text-center">
              <i data-feather='alert-circle' class='feather-72-red'></i>
              <h1 class="text-center">
                   Are you sure you want to cancel?
              </h1>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-dark waves-effect" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger waves-effect">Yes, Cancel It</button>
            </div>
          </form>
      </div>
  </div>
</div>

<div class="modal fade text-left bisa-geser" id="reasonModalCancel" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h4>Cancel<span class="semi-bold"> @yield('title')</span></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <form id="modalReasonCancel" action="" method="post" >
            @csrf
            <div class="modal-body">
              <div class="text-center">
                <i data-feather='alert-circle' class='feather-72-red'></i>
                <h1 class="text-center">
                    Are you sure you want to cancel?
                </h1>
              </div>
              <br>
              <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="account">Reason</label>
                    <input type="text" id="reason" name="reason" class="form-control" value="{{ old('reason') }}"  required maxlength="100"/>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-dark waves-effect" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-danger waves-effect">Yes, Cancel It</button>
            </div>
          </form>
      </div>
  </div>
</div>

<div class="modal fade text-left bisa-geser" id="reasonModalRevision" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h4>Revision<span class="semi-bold"> @yield('title')</span></h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <form id="modalReasonRevision" action="" method="post" >
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
                <div class="form-group col-md-12">
                    <label for="account">Reason</label>
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