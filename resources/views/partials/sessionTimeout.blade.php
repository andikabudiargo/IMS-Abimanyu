
<div class="modal fade text-left modal-primary" id="mdlSessionTimeout" tabindex="-1" role="dialog" aria-hidden="true" data-keyboard="false" data-backdrop="static">
  <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
          <div class="modal-header">
              <h5 class="modal-title">Session Timeout</h5>
          </div>
          <div class="modal-body">
            <h5 class="textoverlay" >Your session will expired <br> <span id="textoverlay"> </span> <br> Do you want to extend the session?</h5>
            <div class="progress progress-bar-danger">
              <div id="progressTimeout" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                  aria-valuenow="60" aria-valuemin="60" aria-valuemax="100"></div>
            </div>
          </div>
          <div class="modal-footer">
              {{-- <button type="button" class="btn btn-primary" data-dismiss="modal">Accept</button> --}}
              <button id="cmdHelpLogout" class="btn btn-outline-primary" type="button">
                <i data-feather="log-out"></i>
                <span>Logout</span>
              </button>
              <button id="cmdHelpOk" class="btn btn-success btn-cons" type="button">
                <i data-feather="log-in"></i>
                  <span>Yes!</span>
              </button>
              
          </div>
      </div>
  </div>
</div>

{{-- <div class="boxOverlay" id="boxOverlay">    
  <div class="boxAlert">
    <div class="textBox">
      <h5 class="textoverlay" >Your session will expired <br> <span id="textoverlay"> </span> <br> Do you want to extend the session?</h5>
    </div>
    <div class="textBox" style="margin-left:12%">
      <button id="cmdHelpLogout" class="btn btn-primary btn-cons" type="button">
          <span>Logout</span>
      </button>
      <button id="cmdHelpOk" class="btn btn-success btn-cons" type="button">
          <span>OK</span>
      </button>
    </div>
  </div>
</div> --}}