@foreach($accounts as $acc)
    @if(strtoupper($acc->acc_header) === 'HEADER')
        <details class="coa-node coa-branch coa-header">
            <summary>{{ $acc->account }} - {{ $acc->description }}</summary>
            <div class="coa-children">
                @include('accounts.partials.account-node', ['accounts' => $acc->children])
            </div>
        </details>
    @else
        <div class="coa-node coa-detail d-flex justify-content-between align-items-center">
            <span>{{ $acc->account }} - {{ $acc->description }}</span>
            <span class="coa-actions">
                @can('account-edit')
                <a href="{{ route('account.edit', ['id' => Crypt::encryptString($acc->id)]) }}" title="Edit">
                    <i data-feather="file-text"></i>
                </a>
                @endcan
                @can('account-delete')
                <a href="javascript:;" id="deleteButton" data-toggle="modal" data-target="#smallModal"
                   data-href="{{ route('account.destroy', ['id' => Crypt::encryptString($acc->id)]) }}" title="Delete">
                    <i data-feather="trash-2" class="feather-14-red"></i>
                </a>
                @endcan
            </span>
        </div>
    @endif
@endforeach
