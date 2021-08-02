<!-- Modal delete-->
<div class="modal fade text-left draggable" id="smallModal" tabindex="-1" role="dialog" aria-hidden="true">
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
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
              </div>
            </form>
        </div>
    </div>
</div>