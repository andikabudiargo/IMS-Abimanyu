@foreach($accounts as $acc)
    @if(strtoupper($acc->acc_header) === 'HEADER')
        <tr class="coa-group coa-header-row" data-level="{{ $level }}">
            <td colspan="7" style="padding-left: {{ $level * 1.5 }}rem">{{ $acc->account }} - {{ $acc->description }}</td>
        </tr>
        @include('accounts.partials.account-node', ['accounts' => $acc->children, 'level' => $level + 1])
    @else
        <tr class="coa-detail-row" data-level="{{ $level }}">
            <td class="coa-actions">
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
            </td>
            <td style="padding-left: {{ $level * 1.5 }}rem">{{ $acc->account }}</td>
            <td>{{ $acc->description }}</td>
            <td class="text-right">{{ number_format($acc->opening_balance, 2) }}</td>
            <td>{{ $acc->debit_credit }}</td>
            <td>{{ $acc->final_statement }}</td>
            <td>{{ $acc->note }}</td>
        </tr>
    @endif
@endforeach
