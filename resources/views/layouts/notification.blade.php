<li class="nav-item dropdown dropdown-notification mr-25">
    <a class="nav-link" href="javascript:void(0);" data-toggle="dropdown">
        <i class="ficon" data-feather="bell"></i>
        <span class="badge badge-pill badge-danger badge-up">{!! count($listSo2) + count($listPo2) !!}</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right">
        @if( count($listSo2)>0 )
            <li class="dropdown-menu-header">
                <div class="dropdown-header d-flex">
                    <h4 class="notification-title mb-0 mr-auto">SO needs to be approved </h4>
                    <div class="badge badge-pill badge-light-primary">{!! count($listSo2) !!} New</div>
                </div>
            </li>   
            <li class="scrollable-container media-list">
                @foreach($listSo2 as $val)
                    <a class="d-flex" href="{{ route('salesOrder.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left">
                                <div class="avatar">
                                    <div class="avatar-content">SO</div>
                                    {{--  <img src="{{ asset('app-assets/images/icons/file-icons/document.png') }}" alt="avatar" width="32" height="32"> --}}
                                </div>
                            </div>
                            <div class="media-body">
                                <p class="media-heading">
                                    <span class="font-weight-bolder">{{ $val->so_code }}</span>
                                </p>
                                <p class="media-heading">
                                    <small class="notification-text">So Date: {{ $val->so_date }}</small>
                                </p>
                                <p class="media-heading">
                                    <small class="notification-text">Supplier: {{ $val->customer_name }}</small>
                                </p>
                                <p class="media-heading">
                                    <small class="notification-text">#Approve: {{ $val->sudah_approve }}</small>
                                </p>
                                {{-- <p class="media-heading">
                                    <small class="notification-text">Amount: Rp.{{ number_format($val->po_amount) }},-</small>
                                </p> --}}
                            </div>
                        </div>
                    </a>
                @endforeach
                
                {{-- <a class="d-flex" href="javascript:void(0)">
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
                    <h4 class="notification-title mb-0 mr-auto">Invoice needs to be approved </h4>
                    <div class="badge badge-pill badge-light-primary">6 New</div>
                </div> --}}
            </li>
        @endif
        @if( count($listPo2)>0 )
            <li class="dropdown-menu-header">
                <div class="dropdown-header d-flex">
                    <h4 class="notification-title mb-0 mr-auto">PO needs to be approved </h4>
                    <div class="badge badge-pill badge-light-primary">{!! count($listPo2) !!} New</div>
                </div>
            </li>   
            <li class="scrollable-container media-list">
                @foreach($listPo2 as $key=>$val)
                    {{-- <a class="d-flex" href="{{ route('purchaseOrder.edit', ['id'=>Crypt::encryptString($val->id)]) }}"> --}}
                        <div class="media d-flex align-items-start">
                            <div class="media-left">
                                <div class="avatar">
                                    <div class="avatar-content">PO</div>
                                    {{--  <img src="{{ asset('app-assets/images/icons/file-icons/document.png') }}" alt="avatar" width="32" height="32"> --}}
                                </div>
                            </div>
                            <div class="media-body">
                                <div class="col-12">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder">{{ $val->po_number }}</span>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">PO Date: {{ $val->po_date }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Supplier: {{ $val->supplier_name }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Amount: Rp{{ number_format($val->po_amount) }},-</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">#Approved: {{ $val->current_level }} of {{ $val->max_level }}</small>
                                    </p>
                                </div>
                                <div class="col-12 mt-50">
                                    <a class="btn btn-outline-info btn-sm" id="cmdDetail{{ $key }}" name="cmdDetail{{ $key }}" href="{{ route('purchaseOrder.edit', ['id'=>Crypt::encryptString($val->id)]) }}"> 
                                        <i data-feather='list'></i>
                                        Detail
                                    </a>
                                    <a href='javascript:;'
                                        onclick="action(this)"
                                        id = 'btnDecline{{ $key }}'
                                        class="btn btn-outline-danger btn-sm"
                                        data-key = '{{ $key }}'
                                        data-doc-number='{{ $val->po_number }}'
                                        data-url='{{ route("purchaseOrder.approve", ["poNumber"=>$val->po_number]) }}'>
                                        <i data-feather='x-circle'></i>
                                        Decline
                                    </a>
                                    <a href='javascript:;'
                                        onclick="action(this)"
                                        id = 'button{{ $key }}'
                                        class="btn btn-outline-success btn-sm"
                                        data-key = '{{ $key }}'
                                        data-doc-number='{{ $val->po_number }}'
                                        data-url='{{ route("purchaseOrder.approve", ["poNumber"=>$val->po_number]) }}'>
                                        <i data-feather='check-circle'></i>
                                        Approve
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                    {{-- </a> --}}
                @endforeach
            </li>
            {{-- <li class="dropdown-menu-footer">
                <a class="btn btn-primary btn-block" data-toggle="dropdown" href="javascript:void(0)">Close</a>
            </li> --}}
        @endif
    </ul>
</li>


<script type="text/javascript">

    action=(me)=>{
        let meId=me.getAttribute('id'),    
        meDocNumber=me.getAttribute("data-doc-number"),
        meUrl=me.getAttribute("data-url"),
        meKey= me.getAttribute("data-key");

        fetch(meUrl, {
            method: "GET",
            headers: {"Content-type": "application/json;charset=UTF-8"}
        })
        .then(response => response.json())
        .then((responseData) => {
            // console.log(responseData);
            // return responseData;
            document.getElementById("btnDecline"+meKey).classList.add('d-none')
            document.getElementById(meId).classList.add('d-none')
        })
        .catch(err => console.log(err));
    }

    // $(document).on('click', 'a[data-ajax-approve="true"]', function () {
    //     console.log("oki");
    //     let me = $(this),
    //         me_doc_number = me.data('doc-number'),
    //         me_href = me.data('url'),
    //         me_id = me.attr('id');
    //         console.log(me_doc_number);
    // });

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