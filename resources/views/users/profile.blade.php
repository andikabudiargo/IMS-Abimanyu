@extends('layouts.app')
@section('title', 'Users')
@section('content')
@include('layouts.breadcrumb')
<section id="page-profile-settings">
    <div class="row">
        <!-- left menu section -->
        <div class="col-md-3 mb-2 mb-md-0">
            <ul class="nav nav-pills flex-column nav-left" id="profile-tab">
                <!-- general -->
                <li class="nav-item">
                    <a class="nav-link active" id="account-pill-general" data-toggle="pill" href="#account-vertical-general" data-toggle="tab" aria-expanded="true">
                        <i data-feather="user" class="font-medium-3 mr-1"></i>
                        <span class="font-weight-bold">General</span>
                    </a>
                </li>
                <!-- change password -->
                <li class="nav-item">
                    <a class="nav-link" id="account-pill-password" data-toggle="pill" href="#account-vertical-password" data-toggle="tab" aria-expanded="false">
                        <i data-feather="lock" class="font-medium-3 mr-1"></i>
                        <span class="font-weight-bold">Change Password</span>
                    </a>
                </li>
                <!-- information -->
                {{-- <li class="nav-item">
                    <a class="nav-link" id="account-pill-info" data-toggle="pill" href="#account-vertical-info" aria-expanded="false">
                        <i data-feather="info" class="font-medium-3 mr-1"></i>
                        <span class="font-weight-bold">Information</span>
                    </a>
                </li> --}}
            </ul>
        </div>
        <!--/ left menu section -->

        <!-- right content section -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div class="tab-content">
                        <!-- general tab -->
                        <div role="tabpanel" class="tab-pane active" id="account-vertical-general" aria-labelledby="account-pill-general" aria-expanded="true">
                            <!-- header media -->
                            <div class="media">
                                <a href="javascript:void(0);" class="mr-25">
                                    <img class="round" src="{{ asset(Auth::user()->filename) }}" onerror="this.src='{{ asset('app-assets/images/avatars/default.png')}}';" id="accountUploadImg" class="rounded mr-50 img-thumnbnail rounded-circle" alt="profile image" height="80" width="80">
                                </a>
                                <form action="{{ route('file.upload.post') }}" method="POST" id="upload-image" enctype="multipart/form-data">
                                    @csrf
                                    <div class="media-body mt-75 ml-1">
                                        <label for="imageUpload" class="btn btn-sm btn-primary mb-75 mr-75">Upload</label>
                                        <input type="file" name="imageUpload" id="imageUpload" hidden accept=".jpg,.png" />
                                        @error('image')
                                        <div class="alert alert-danger mt-1 mb-1">{{ $message }}</div>
                                        @enderror
                                        <button type="submit" class="btn btn-sm btn-outline-secondary mb-75">Save Image</button>
                                        <p>Allowed JPG or PNG. Max size of 5Mb</p>
                                    </div>
                                </form>
                            </div>
                            <!-- form -->
                            <form id="updateProfile" class="validate-form mt-2">
                                <p class="text-danger" id="responMessageProfile"></p>
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="account-username">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="{{ $user->username }}" disabled/>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="account-name">Name</label>
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Name" value="{{ $user->name }}" />
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="account-e-mail">E-mail</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Email" value="{{ $user->email }}" />
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="account-company">Company</label>
                                            <input type="text" class="form-control" id="company" name="company" placeholder="Company name" value="Crystal Technologies" disabled>
                                        </div>
                                    </div>
                                </div>
                                    <button type="button" id="cmdSaveProfile" class="btn btn-primary mr-1 mt-1">Save changes</button>
                                    <button type="reset" class="btn btn-outline-secondary mt-1">Cancel</button>
                            </form>
                            <!--/ form -->
                        </div>
                        <!--/ general tab -->
                        <!-- change password -->
                        <div class="tab-pane fade" id="account-vertical-password" role="tabpanel" aria-labelledby="account-pill-password" aria-expanded="false">
                            <!-- form -->
                            <form id="changPassword" enctype="multipart/form-data">
                                <p class="text-danger" id="responMessagePassword"></p>
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="oldPassword">Old Password</label>
                                            <div class="input-group form-password-toggle input-group-merge">
                                                <input type="password" class="form-control" id="oldPassword" name="oldPassword" placeholder="Old Password" />
                                                <div class="input-group-append">
                                                    <div class="input-group-text cursor-pointer">
                                                        <i data-feather="eye"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="newPassword">New Password</label>
                                            <div class="input-group form-password-toggle input-group-merge">
                                                <input type="password" id="newPassword" name="newPassword" class="form-control" placeholder="New Password" />
                                                <div class="input-group-append">
                                                    <div class="input-group-text cursor-pointer">
                                                        <i data-feather="eye"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="retypePassword">Retype New Password</label>
                                            <div class="input-group form-password-toggle input-group-merge">
                                                <input type="password" class="form-control" id="retypeNewPassword" name="retypeNewPassword" placeholder="New Password" />
                                                <div class="input-group-append">
                                                    <div class="input-group-text cursor-pointer"><i data-feather="eye"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="button" id="cmdSavePassword" class="btn btn-primary mr-1 mt-1">Save changes</button>
                                        <button type="reset" class="btn btn-outline-secondary mt-1">Cancel</button>
                                    </div>
                                </div>
                            </form>
                            <!--/ form -->
                        </div>
                        <!--/ change password -->
                        <!-- information -->
                        <div class="tab-pane fade" id="account-vertical-info" role="tabpanel" aria-labelledby="account-pill-info" aria-expanded="false">
                            <!-- form -->
                            <form class="validate-form">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="accountTextarea">Bio</label>
                                            <textarea class="form-control" id="accountTextarea" rows="4" placeholder="Your Bio data here..."></textarea>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="account-birth-date">Birth date</label>
                                            <input type="text" class="form-control flatpickr" placeholder="Birth date" id="account-birth-date" name="dob" />
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="accountSelect">Country</label>
                                            <select class="form-control" id="accountSelect">
                                                <option>USA</option>
                                                <option>India</option>
                                                <option>Canada</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="account-website">Website</label>
                                            <input type="text" class="form-control" name="website" id="account-website" placeholder="Website address" />
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-group">
                                            <label for="account-phone">Phone</label>
                                            <input type="text" class="form-control" id="account-phone" placeholder="Phone number" value="(+656) 254 2568" name="phone" />
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary mt-1 mr-1">Save changes</button>
                                        <button type="reset" class="btn btn-outline-secondary mt-1">Cancel</button>
                                    </div>
                                </div>
                            </form>
                            <!--/ form -->
                        </div>
                        <!--/ information -->
                    </div>
                </div>
            </div>
        </div>
        <!--/ right content section -->
    </div>
</section>
@endsection
@section('styles')
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function (e) {
        //view image after fiel is selected
        $('#imageUpload').change(function(){
            let reader = new FileReader();
            reader.onload = (e) => { 
              $('#accountUploadImg').attr('src', e.target.result); 
            }
            reader.readAsDataURL(this.files[0]); 
        });
    });

    $('#cmdSavePassword').click(function(e){
        oldPsw= $('#oldPassword').val();
        newPSw= $('#newPassword').val();
        retypeNewPSw= $('#retypeNewPassword').val();

        $.ajax({
            type: "post",
            url: "{{ route('change.password') }}",
            data: {
                oldPassword:oldPsw,
                newPassword:newPSw,
                retypeNewPassword:retypeNewPSw
            },
            dataType: 'json',
            success: function(data){
                //console.log(data);
                $('#responMessagePassword').fadeIn().html(data.message);
            },
            error: function (err) {
                if (err.status == 422) { // when status code is 422, it's a validation issue
                    console.log(err.responseJSON);
                    $('#responMessagePassword').fadeIn().html(err.responseJSON.message);
                    // console.warn(err.responseJSON.errors);
                    $.each(err.responseJSON.errors, function (i, error) {
                        var el = $(document).find('[name="'+i+'"]');
                        $('#responMessagePassword').fadeIn().html(error[0]);
                        // el.after($('<span style="color: red;">'+error[0]+'</span>'));
                    });
                }
            }
        });
    });

    $('#cmdSaveProfile').click(function(e){
        username= $('#username').val();
        name= $('#name').val();
        email= $('#email').val();

        $.ajax({
            type: "post",
            url: "{{ route('change.profile') }}",
            data: {
                username:username,
                name:name,
                email:email,
            },
            dataType: 'json',
            success: function(data){
                //console.log(data);
                $('#responMessageProfile').fadeIn().html(data.message);
            },
            error: function (err) {
                if (err.status == 422) { // when status code is 422, it's a validation issue
                    console.log(err.responseJSON);
                    $('#responMessageProfile').fadeIn().html(err.responseJSON.message);
                    // console.warn(err.responseJSON.errors);
                    $.each(err.responseJSON.errors, function (i, error) {
                        var el = $(document).find('[name="'+i+'"]');
                        $('#responMessageProfile').fadeIn().html(error[0]);
                        // el.after($('<span style="color: red;">'+error[0]+'</span>'));
                    });
                }
            }
        });
    });

    

    $.ajaxSetup({
	    headers: {
	        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	    }
	});



</script>
@endsection