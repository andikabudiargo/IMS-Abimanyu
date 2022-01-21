<li class="nav-item dropdown dropdown-notification mr-25">
    <a class="nav-link" href="javascript:void(0);" data-toggle="dropdown">
        <i class="ficon" data-feather="bell"></i>
        @can('purchaseOrder-authorize')
            <span class="badge badge-pill badge-danger badge-up">5</span>
        @endcan
    </a>
    <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
        @can('purchaseOrder-authorize')
        <li class="dropdown-menu-header">
            <div class="dropdown-header d-flex">
                <h4 class="notification-title mb-0 mr-auto">PO needs to be authorizhed </h4>
                <div class="badge badge-pill badge-light-primary">6 New</div>
            </div>
        </li>
        @endcan
        @can('purchaseOrder-authorize')
        @endcan
        <li class="scrollable-container media-list">
            @foreach($listPo2 as $val)
                <a class="d-flex" href="{{ route('purchaseOrder.show', ['id'=>$val->id]) }}">
                    <div class="media d-flex align-items-start">
                        <div class="media-left">
                            <div class="avatar">
                                <div class="avatar-content">PO</div>
                                {{--  <img src="{{ asset('app-assets/images/icons/file-icons/document.png') }}" alt="avatar" width="32" height="32"> --}}
                            </div>
                        </div>
                        <div class="media-body">
                            <p class="media-heading">
                                <span class="font-weight-bolder">{{ $val->po_number }}</span>
                            </p>
                            <p class="media-heading">
                                <small class="notification-text">Po Date: {{ $val->po_date }}</small>
                            </p>
                            <p class="media-heading">
                                <small class="notification-text">Supplier: {{ $val->supplier_id }}</small>
                            </p>
                            <p class="media-heading">
                                <small class="notification-text">Amount: Rp.{{ number_format($val->po_amount) }},-</small>
                            </p>
                        </div>
                    </div>
                </a>
            @endforeach
            
            <a class="d-flex" href="javascript:void(0)">
                <div class="media d-flex align-items-start">
                    <div class="media-left">
                        <div class="avatar bg-light-danger">
                            <div class="avatar-content">MD</div>
                        </div>
                    </div>
                    <div class="media-body">
                        <p class="media-heading"><span class="font-weight-bolder">Revised Order 👋</span>&nbsp;checkout</p><small class="notification-text"> MD Inc. order updated</small>
                    </div>
                </div>
            </a>
            <div class="dropdown-header d-flex">
                <h4 class="notification-title mb-0 mr-auto">Invoice needs to be authorizhed </h4>
                <div class="badge badge-pill badge-light-primary">6 New</div>
            </div>
        </li>
        {{-- <li class="dropdown-menu-footer">
            <a class="btn btn-primary btn-block" data-toggle="dropdown" href="javascript:void(0)">Close</a>
        </li> --}}
    </ul>
</li>


<script type="text/javascript">
    // let username = "{{ Auth::user()->name }}";
    // getNotification = () => {
    //     $.ajax({
    //         url:"{{ route('dynamic.dependent') }}",
    //         method:"POST",
    //         cache: false,
    //         data:{
    //             value:value,
    //             type:type,
    //             dependent:dependent
    //         },
    //         success:function(result){
                
    //         }
    //     })
    // }
    
    // getNotification();

</script>