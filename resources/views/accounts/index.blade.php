@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="accounts-index">
    <div class="row">
      <div class="col-12">
        <div class="card">
          {{-- <div class="card-header">  
            <div class="card-title">@yield('title')
            </div>
          </div> --}}
          <div class="card-body">
            <form class="needs-validation" novalidate>
                <div class="form-row">
                    <div class="col-md-4"> 
                        <div class="form-group">
                        <label for="searchAccCode">Account</label>
                        <input type="text" class="form-control text-uppercase" id="searchAccCode" name="searchAccCode" placeholder="" />
                        </div>
                    </div>
                    <div class="col-md-4"> 
                    <div class="form-group">
                        <label for="searchAcc">Description</label>
                        <input type="text" class="form-control text-uppercase" id="searchAcc" name="searchAcc" placeholder="" />
                    </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12"> 
                        <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                        @can('account-create')
                        <a href="{{ route('account.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                        @endcan
                    </div>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</section>
<section id="table-accounts">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">List @yield('title')</h4>
        <div class="heading-elements">
            <ul class="list-inline mb-0">
                <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
            </ul>
        </div>
      </div>
      <div class="card-content collapse show">
        <div class="card-body">
          <div class="row">
              <div class="col-sm-12">
                <div id="coaTree" class="coa-tree">
                  @foreach($tree as $type)
                    <details class="coa-node coa-branch coa-type" open>
                        <summary>{{ $type->code }} - {{ $type->name }}</summary>
                        <div class="coa-children">
                            @foreach($type->subs as $sub)
                                <details class="coa-node coa-branch coa-sub">
                                    <summary>{{ $sub->sub_code }} - {{ $sub->description }}</summary>
                                    <div class="coa-children">
                                        @include('accounts.partials.account-node', ['accounts' => $sub->accounts])
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </details>
                  @endforeach
                </div>
              </div>
          </div>
        </div>
      </div>
    </div>
</section>
@include('partials.delete-modal')
@endsection
@section('styles')
<style>
    .coa-tree .coa-node { margin-left: 1.25rem; }
    .coa-tree > .coa-node { margin-left: 0; }
    .coa-tree summary { cursor: pointer; padding: .25rem 0; }
    .coa-tree .coa-detail { padding: .25rem 0 .25rem 1.25rem; }
    .coa-tree .coa-actions a { margin-left: .5rem; }
    .coa-tree .coa-hidden { display: none; }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){
      let href;
      $(document).on('click', '#deleteButton', function(event) {
          event.preventDefault();
          href = $(this).data('href');
          $('#modalConfirmation').attr("action", href);
      });
      feather.replace();
    });

    //expand/collapse all
    $('a[data-action="collapse"]').on('click', function () {
        const anyClosed = $('#coaTree details').is(':not([open])');
        $('#coaTree details').prop('open', anyClosed);
    });

    $('a[data-action="reload"]').on('click', function () {
        location.reload();
    });

    $("#btnSearch").click(function(e){
        filterTree($("#searchAcc").val(), $("#searchAccCode").val());
    });

    const filterTree = (nama, code) => {
        nama = (nama || '').toLowerCase();
        code = (code || '').toLowerCase();

        if (!nama && !code) {
            $('#coaTree .coa-node').removeClass('coa-hidden');
            return;
        }

        $('#coaTree .coa-detail').each(function () {
            const text = $(this).text().toLowerCase();
            const parts = text.split(' - ');
            const acctCode = (parts[0] || '').trim();
            const desc = parts.slice(1).join(' - ').trim();
            const match = (!code || acctCode.includes(code)) && (!nama || desc.includes(nama));
            $(this).toggleClass('coa-hidden', !match);
        });

        //a branch (header/sub/type) stays visible only if it still has a visible leaf underneath;
        //walk deepest-first so a parent's visibility can rely on its children already being decided
        $('#coaTree .coa-branch').get().reverse().forEach(function (branch) {
            const visible = $(branch).find('.coa-detail, .coa-branch').not('.coa-hidden').length > 0;
            $(branch).toggleClass('coa-hidden', !visible).prop('open', visible);
        });
    }
</script>
@endsection
