<li class="nav-item dropdown dropdown-notification mr-25">
    <a class="nav-link" href="javascript:void(0);" data-toggle="dropdown">
        <i class="ficon" data-feather="bell"></i>
        <span class="badge badge-pill badge-danger badge-up">{!! count($listSo2) + count($listPoNotif) + count($listBomNotif) + count($listPrNotif) + count($listTsoNotif) !!}</span>
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
                @foreach($listSo2 as $key=>$val)
                    <a class="d-flex" href="{{ route('salesOrder.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                        <div class="media d-flex align-items-start">
                            <div class="media-left">
                                <div class="avatar">
                                    <div class="avatar-content">SO</div>
                                    {{--  <img src="{{ asset('app-assets/images/icons/file-icons/document.png') }}" alt="avatar" width="32" height="32"> --}}
                                </div>
                            </div>
                            <div class="media-body">
                                <div class="col-12">
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
                                        <small class="notification-text">#Approve: {{ $val->current_level }} of {{ $val->max_level }}</small>
                                    </p>
                                </div>
                                <div class="col-12 mt-50">
                                    <a class="btn btn-outline-info btn-sm" 
                                        id="cmdDetailSo{{ $key }}" 
                                        name="cmdDetailSo{{ $key }}" 
                                        href="{{ route('salesOrder.show', ['id'=>Crypt::encryptString($val->id)]) }}"> 
                                        <i data-feather='list'></i>
                                        Detail
                                    </a>
                                    <a href='javascript:;'
                                        onclick="action(this)"
                                        id = 'buttonSo{{ $key }}'
                                        class="btn btn-outline-success btn-sm buttonSo-{{ $val->id }}"
                                        data-id-class = "buttonSo-{{ $val->id }}"
                                        data-doc-number='{{ $val->so_code }}'
                                        data-url='{{ route("salesOrder.approve", ["soCode"=>$val->so_code]) }}'>
                                        <i data-feather='check-circle'></i>
                                        Approve
                                    </a>
                                </div>
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
        @if( count($listPoNotif)>0 )
            <li class="dropdown-menu-header">
                <div class="dropdown-header d-flex">
                    <h4 class="notification-title mb-0 mr-auto">PO needs to be approved </h4>
                    <div class="badge badge-pill badge-light-primary">{!! count($listPoNotif) !!} New</div>
                </div>
            </li>   
            <li class="scrollable-container media-list">
                @foreach($listPoNotif as $key=>$val)
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
                                    <a class="btn btn-outline-info btn-sm" 
                                        id="cmdDetailPo{{ $key }}" 
                                        name="cmdDetailPo{{ $key }}" 
                                        href="{{ route('purchaseOrder.edit', ['id'=>Crypt::encryptString($val->id)]) }}"> 
                                        <i data-feather='list'></i>
                                        Detail
                                    </a>
                                    <a href='javascript:;'
                                        onclick="action(this)"
                                        id = 'btnDeclinePo{{ $key }}'
                                        class="btn btn-outline-danger btn-sm  buttonPoDecline-{{ $val->id }}"
                                        data-id-class = "buttonPo-{{ $val->id }}"
                                        data-id-class-decline = "buttonPoDecline-{{ $val->id }}"
                                        data-doc-number='{{ $val->po_number }}'
                                        data-url='{{ route("purchaseOrder.decline", ["poNumber"=>$val->po_number]) }}'>
                                        <i data-feather='x-circle'></i>
                                        Decline
                                    </a>
                                    <a href='javascript:;'
                                        onclick="action(this)"
                                        id = 'buttonPo{{ $key }}'
                                        class="btn btn-outline-success btn-sm buttonPo-{{ $val->id }}"
                                        data-id-class = "buttonPo-{{ $val->id }}"
                                        data-id-class-decline = "buttonPoDecline-{{ $val->id }}"
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
        @if( count($listBomNotif)>0 )
            <li class="dropdown-menu-header">
                <div class="dropdown-header d-flex">
                    <h4 class="notification-title mb-0 mr-auto">BOM needs to be approved </h4>
                    <div class="badge badge-pill badge-light-primary">{!! count($listBomNotif) !!} New</div>
                </div>
            </li>   
            <li class="scrollable-container media-list">
                @foreach($listBomNotif as $key=>$val)
                    {{-- <a class="d-flex" href="{{ route('purchaseOrder.edit', ['id'=>Crypt::encryptString($val->id)]) }}"> --}}
                        <div class="media d-flex align-items-start">
                            <div class="media-left">
                                <div class="avatar">
                                    <div class="avatar-content">BOM</div>
                                    {{--  <img src="{{ asset('app-assets/images/icons/file-icons/document.png') }}" alt="avatar" width="32" height="32"> --}}
                                </div>
                            </div>
                            <div class="media-body">
                                <div class="col-12">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder">{{ $val->bom_code }}</span>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Article FG: {{ $val->article_fg }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Article RM: {{ $val->article_rm }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Supplier: {{ $val->customer_name }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">#Approved: {{ $val->current_level }} of {{ $val->max_level }}</small>
                                    </p>
                                </div>
                                <div class="col-12 mt-50">
                                    <a class="btn btn-outline-info btn-sm" 
                                        id="cmdDetailBom{{ $key }}" 
                                        name="cmdDetailBom{{ $key }}" 
                                        href="{{ route('bom.show', ['id'=>Crypt::encryptString($val->id)]) }}"> 
                                        <i data-feather='list'></i>
                                        Detail
                                    </a>
                                    {{-- <a href='javascript:;'
                                        onclick="action(this)"
                                        id = 'btnDeclineBom{{ $key }}'
                                        class="btn btn-outline-danger btn-sm  buttonBomDecline-{{ $val->id }}"
                                        data-id-class-decline = "buttonBomDecline-{{ $val->id }}"
                                        data-id-class = "buttonBom-{{ $val->id }}"
                                        data-doc-number='{{ $val->bom_code }}'
                                        data-url='{{ route("bom.approve", ["bomNumber"=>$val->bom_code]) }}'>
                                        <i data-feather='x-circle'></i>
                                        Decline
                                    </a> --}}
                                    <a href='javascript:;'
                                        onclick="action(this)"
                                        id = 'buttonBom{{ $key }}'
                                        class="btn btn-outline-success btn-sm buttonBom-{{ $val->id }}"
                                        data-id-class = "buttonBom-{{ $val->id }}"
                                        data-id-class-decline = "buttonBomDecline-{{ $val->id }}"
                                        data-doc-number='{{ $val->bom_code }}'
                                        data-url='{{ route("bom.approve", ["bomNumber"=>$val->bom_code]) }}'>
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
        @if( count($listTsoNotif)>0 )
            <li class="scrollable-container media-list">
                <li class="dropdown-menu-header">
                    <div class="dropdown-header d-flex">
                        <h4 class="notification-title mb-0 mr-auto">TSO needs to be approved </h4>
                        <div class="badge badge-pill badge-light-primary">{!! count($listPrNotif) !!} New</div>
                    </div>
                </li>   
                <li class="scrollable-container media-list">
                    @foreach($listTsoNotif as $key=>$val)
                        <div class="media d-flex align-items-start">
                            <div class="media-left">
                                <div class="avatar">
                                    <div class="avatar-content">TSO</div>
                                </div>
                            </div>
                            <div class="media-body">
                                <div class="col-12">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder">{{ $val->tso_code }}</span>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Desc: {{ $val->tso_name }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Date: {{ $val->tso_date }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Note: {{ $val->note }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">#Approved: {{ $val->current_level }} of {{ $val->max_level }}</small>
                                    </p>
                                </div>
                                <div class="col-12 mt-50">
                                    <a class="btn btn-outline-info btn-sm" 
                                        id="cmdDetailTso{{ $key }}" 
                                        name="cmdDetailTso{{ $key }}" 
                                        href="{{ route('targetSo.show', ['id'=>Crypt::encryptString($val->id)]) }}"> 
                                        <i data-feather='list'></i>
                                        Detail
                                    </a>
                                    <a href='javascript:;'
                                        onclick="action(this)"
                                        id = 'buttonTso{{ $key }}'
                                        class="btn btn-outline-success btn-sm buttonTso-{{ $val->id }}"
                                        data-id-class = "buttonTso-{{ $val->id }}"
                                        data-doc-number='{{ $val->tso_code }}'
                                        data-url='{{ route("targetSo.approve", ["tsoCode"=>$val->tso_code]) }}'>
                                        <i data-feather='check-circle'></i>
                                        Approve
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </li>
            </li>
        @endif
        @if( count($listPrNotif) > 0 )
            <li class="dropdown-menu-header">
                <div class="dropdown-header d-flex">
                    <h4 class="notification-title mb-0 mr-auto">PR needs to be approved </h4>
                    <div class="badge badge-pill badge-light-primary">{!! count($listPrNotif) !!} New</div>
                </div>
            </li>   
            <li class="scrollable-container media-list">
                @foreach($listPrNotif as $key=>$val)
                    {{-- <a class="d-flex" href="{{ route('purchaseOrder.edit', ['id'=>Crypt::encryptString($val->id)]) }}"> --}}
                        <div class="media d-flex align-items-start">
                            <div class="media-left">
                                <div class="avatar">
                                    <div class="avatar-content">PR</div>
                                    {{--  <img src="{{ asset('app-assets/images/icons/file-icons/document.png') }}" alt="avatar" width="32" height="32"> --}}
                                </div>
                            </div>
                            <div class="media-body">
                                <div class="col-12">
                                    <p class="media-heading">
                                        <span class="font-weight-bolder">{{ $val->pr_number }}</span>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Order Type: {{ $val->order_type }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Date: {{ $val->date }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">Note: {{ $val->note }}</small>
                                    </p>
                                    <p class="media-heading">
                                        <small class="notification-text">#Approved: {{ $val->current_level }} of {{ $val->max_level }}</small>
                                    </p>
                                </div>
                                <div class="col-12 mt-50">
                                    <a class="btn btn-outline-info btn-sm" 
                                        id="cmdDetailPr{{ $key }}" 
                                        name="cmdDetailPr{{ $key }}" 
                                        href="{{ route('purchaseRequest.show', ['id'=>Crypt::encryptString($val->id)]) }}"> 
                                        <i data-feather='list'></i>
                                        Detail
                                    </a>
                                    <a href='javascript:;'
                                        onclick="action(this)"
                                        id = 'buttonPr{{ $key }}'
                                        class="btn btn-outline-success btn-sm buttonPr-{{ $val->id }}"
                                        data-id-class = "buttonPr-{{ $val->id }}"
                                        data-doc-number='{{ $val->pr_number }}'
                                        data-url='{{ route("purchaseRequest.approve", ["prNumber"=>$val->pr_number]) }}'>
                                        <i data-feather='check-circle'></i>
                                        Approve
                                    </a>
                                </div>
                            </div>
                        </div>
                    {{-- </a> --}}
                @endforeach
            </li>
        @endif
    </ul>
</li>


<script type="text/javascript">
    action=(me)=>{
        let meId=me.getAttribute('id'),    
        meDocNumber=me.getAttribute("data-doc-number"),
        meUrl=me.getAttribute("data-url"),
        meClassId = me.getAttribute("data-id-class");
        meClassIdDecline = me.getAttribute("data-id-class-decline");
        
        fetch(meUrl, {
            method: "GET",
            headers: {"Content-type": "application/json;charset=UTF-8"}
        })
        .then(response => response.json())
        .then((responseData) => {
            const ele = document.getElementsByClassName(meClassId);
            if (ele){
                for (let i=0; i< ele.length; i++ ) {
                    const idButtoHide = document.getElementById(ele[i].id);
                    if (idButtoHide){
                        idButtoHide.classList.add('d-none');
                    }
                }
            }
            
            const eleDecline = document.getElementsByClassName(meClassIdDecline);
            if (eleDecline){
                for (let i=0; i< eleDecline.length; i++ ) {
                    const idButtoHideDecline = document.getElementById(eleDecline[i].id);
                    if (idButtoHideDecline){
                        idButtoHideDecline.classList.add('d-none');
                    }
                }
            }

            show_msg(responseData.title, responseData.message, responseData.alert);
        })
        .catch(err => console.log(err));
    }

</script>