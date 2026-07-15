@if($diff && $diff['has'])
<div class="diff-box">
    <div class="diff-box-head">
        <i data-feather="git-commit"></i>
        <span>Perubahan dari {{ $diffLabel }}</span>
    </div>
    <div class="diff-box-body">
        @foreach($diff['header'] as $h)
            <span class="diff-pill">
                <span class="diff-pill-lbl">{{ $h['label'] }}</span>
                <span class="diff-old">{{ Str::limit($h['old'], 25) }}</span>
                <i data-feather="arrow-right"></i>
                <span class="diff-new">{{ Str::limit($h['new'], 25) }}</span>
            </span>
        @endforeach

        @if(count($diff['added']))
            <span class="diff-pill diff-pill-add">
                <i data-feather="plus-circle"></i>{{ count($diff['added']) }} article ditambah
            </span>
        @endif
        @if(count($diff['removed']))
            <span class="diff-pill diff-pill-del">
                <i data-feather="minus-circle"></i>{{ count($diff['removed']) }} article dihapus
            </span>
        @endif
        @if(count($diff['changed']))
            <span class="diff-pill diff-pill-mod">
                <i data-feather="edit-2"></i>{{ count($diff['changed']) }} article diubah
            </span>
        @endif
    </div>
</div>
@endif