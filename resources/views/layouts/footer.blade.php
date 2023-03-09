<!-- BEGIN: Footer-->
<footer class="footer footer-static footer-light">
	<p class="clearfix mb-0">
		<span class="float-md-left d-block d-md-inline-block mt-25">COPYRIGHT &copy; {{ env('APP_YEAR_CREATED') }}
			<span class="d-sm-inline-block">{{ env('APP_COMPANY') }}, All rights Reserved</span>
			@include('layouts.version')			
		</span>
		<span class="float-md-right d-none d-md-block">Server proccess time:
			<span class="hint-text">{{ number_format((microtime(true) - LARAVEL_START), 3) }} secs.</span> 
			{{-- <span class="font-montserrat">Version {{ env('APP_VERSION') }}</span> --}}
		</span>
	</p>
</footer>
<button class="btn btn-primary btn-icon scroll-top" type="button"><i data-feather="arrow-up"></i></button>
<!-- END: Footer-->