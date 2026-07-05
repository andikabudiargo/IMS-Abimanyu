@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
  $statusTr = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];

  $stClass = function($st) {
    return ['1'=>'st-new','2'=>'st-validated','3'=>'st-approved'][$st] ?? 'st-new';
  };

 $svgTruck = '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
  $svgArrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
  $svgCal   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
  $svgUser  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
  $svgClock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
@endphp

<style>
/* ── Design tokens: steel/slate structure + amber urgency (panel kontrol pabrik) ── */
.ost-wrap{--st9:#1b2330;--st8:#232d3d;--st7:#2f3b4d;--ln:#e3e7ee;--lns:#eef1f5;
  --ink:#2a3342;--inks:#6b7688;--inkf:#97a1b0;--alt:#f7f9fb;
  --amb:#d98a0b;--ambg:#fdf4e3;--grn:#1f8a54;--grng:#e8f4ec;--red:#c0392b;--redg:#fbeae8;
  margin-bottom:1.75rem;}
.ost-cols{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;}
@media(max-width:991px){.ost-cols{grid-template-columns:1fr;}}

.ost-panel{background:#fff;border:1px solid var(--ln);border-radius:6px;overflow:hidden;
  display:flex;flex-direction:column;box-shadow:0 1px 2px rgba(27,35,48,.04);}

/* header — light, senada dengan body */
.ost-head{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1rem;
  background:var(--alt);border-bottom:1px solid var(--ln);}
.ost-head.in{border-top:3px solid var(--grn);}
.ost-head.out{border-top:3px solid var(--amb);}
.ost-head-l{display:flex;align-items:center;gap:.7rem;}
.ost-head-ico{width:34px;height:34px;border-radius:6px;display:flex;align-items:center;
  justify-content:center;flex-shrink:0;}
.ost-head-ico.in{background:var(--grng);}
.ost-head-ico.out{background:var(--ambg);}
.ost-head-ico svg{width:17px;height:17px;}
.ost-head-ico.in svg{color:var(--grn);}
.ost-head-ico.out svg{color:var(--amb);}
.ost-head-titles{line-height:1.2;}
.ost-head-eyebrow{font-size:.62rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;
  color:var(--inkf);margin-bottom:1px;}
.ost-head-title{font-size:.9rem;font-weight:700;color:var(--ink);letter-spacing:.01em;}

/* count chip */
.ost-chip{display:flex;align-items:baseline;gap:4px;padding:.3rem .6rem;border-radius:4px;
  background:#fff;border:1px solid var(--ln);}
.ost-chip-num{font-size:1.05rem;font-weight:800;line-height:1;font-variant-numeric:tabular-nums;color:var(--ink);}
.ost-chip-num.warn{color:var(--amb);}.ost-chip-num.zero{color:var(--grn);}
.ost-chip-lbl{font-size:.58rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--inkf);}

/* advisory */
.ost-advisory{display:flex;align-items:flex-start;gap:.5rem;padding:.6rem 1rem;background:var(--ambg);
  border-bottom:1px solid #f3e2c4;font-size:.72rem;line-height:1.45;color:#8a5a08;}
.ost-advisory svg{width:14px;height:14px;flex-shrink:0;margin-top:1px;color:var(--amb);}

/* body */
.ost-body{flex:1;overflow-y:auto;max-height:360px;}
.ost-body::-webkit-scrollbar{width:5px;}
.ost-body::-webkit-scrollbar-thumb{background:#cfd6e0;border-radius:5px;}

/* item */
.ost-item{display:flex;border-bottom:1px solid var(--lns);transition:background .12s;}
.ost-item:last-child{border-bottom:none;}
.ost-item:hover{background:var(--alt);}
.ost-rail{width:4px;flex-shrink:0;background:transparent;}
.ost-rail.success,
.ost-rail.warning,
.ost-rail.danger{background:transparent;}
.ost-item-in{flex:1;display:flex;align-items:center;gap:.9rem;padding:.7rem .9rem;min-width:0;}

/* meta */
.ost-meta{flex:1;min-width:0;}
.ost-meta-hd{display:flex;align-items:center;gap:.5rem;margin-bottom:.32rem;flex-wrap:wrap;}
.ost-docno{font-size:.82rem;font-weight:700;color:var(--ink);font-variant-numeric:tabular-nums;letter-spacing:.01em;}
.ost-tag{font-size:.58rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  padding:.14rem .4rem;border-radius:3px;border:1px solid;}
.ost-tag.st-new{color:#5a6acf;border-color:#d3d9f5;background:#f2f4ff;}
.ost-tag.st-validated{color:#1382a5;border-color:#c6e6f0;background:#eef8fb;}
.ost-tag.st-approved{color:#b07d0a;border-color:#f0e0b8;background:#fdf7e8;}
.ost-age{display:inline-flex;align-items:center;gap:3px;font-size:.62rem;font-weight:700;
  padding:.14rem .42rem;border-radius:3px;font-variant-numeric:tabular-nums;margin-left:auto;}
.ost-age svg{width:10px;height:10px;}
.ost-age.success{color:var(--grn);background:var(--grng);}
.ost-age.warning{color:var(--amb);background:var(--ambg);}
.ost-age.danger{color:var(--red);background:var(--redg);}

/* route */
.ost-route{display:flex;align-items:center;gap:.4rem;font-size:.75rem;color:var(--inks);
  margin-bottom:.28rem;min-width:0;}
.ost-route svg{flex-shrink:0;}
.ost-loc{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:42%;}
.ost-loc.to{font-weight:700;color:var(--ink);}
.ost-route-arrow{color:var(--inkf);flex-shrink:0;}
.ost-route-arrow svg{width:13px;height:13px;}

/* sub */
.ost-sub{display:flex;align-items:center;gap:.45rem;font-size:.68rem;color:var(--inkf);flex-wrap:wrap;}
.ost-sub svg{width:11px;height:11px;opacity:.8;}
.ost-sub-sep{color:#d5dae2;}

/* contact note */
.ost-contact{margin-top:.35rem;font-size:.66rem;color:#8a5a08;background:var(--ambg);
  border:1px dashed #eccf9a;border-radius:3px;padding:.22rem .45rem;display:inline-flex;
  align-items:center;gap:4px;}
.ost-contact svg{width:10px;height:10px;color:var(--amb);}
.ost-contact strong{color:#6d4706;}

/* actions */
.ost-act{display:flex;flex-direction:column;gap:.35rem;flex-shrink:0;align-items:stretch;min-width:82px;}
.ost-btn{font-size:.7rem;font-weight:700;letter-spacing:.02em;padding:.34rem .6rem;border-radius:4px;
  border:1px solid transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;
  gap:5px;white-space:nowrap;text-decoration:none;transition:all .12s;}
.ost-btn svg{width:12px;height:12px;}
.ost-btn:hover{text-decoration:none;transform:translateY(-1px);}
.ost-btn-post{background:var(--grn);color:#fff;box-shadow:0 1px 2px rgba(31,138,84,.3);}
.ost-btn-post:hover{background:#1a7548;color:#fff;}
.ost-btn-detail{background:#fff;color:var(--st7);border-color:var(--ln);}
.ost-btn-detail:hover{background:var(--alt);border-color:#c9d0da;color:var(--st9);}

/* empty */
.ost-empty{padding:2.5rem 1.25rem;text-align:center;}
.ost-empty-ico{width:46px;height:46px;border-radius:50%;background:var(--grng);border:1px solid #cfe6d8;
  display:flex;align-items:center;justify-content:center;margin:0 auto .7rem;}
.ost-empty-ico svg{width:20px;height:20px;color:var(--grn);}
.ost-empty-ttl{font-size:.82rem;font-weight:700;color:var(--ink);margin-bottom:3px;}
.ost-empty-txt{font-size:.73rem;color:var(--inkf);}

/* footer */
.ost-foot{display:flex;align-items:center;justify-content:space-between;padding:.55rem 1rem;
  background:var(--alt);border-top:1px solid var(--ln);font-size:.68rem;}
.ost-foot-l{color:var(--inks);font-variant-numeric:tabular-nums;}
.ost-foot-l strong{color:var(--ink);font-weight:700;}
.ost-foot-warn{display:inline-flex;align-items:center;gap:4px;font-weight:700;color:var(--red);
  text-transform:uppercase;letter-spacing:.04em;font-size:.62rem;}
.ost-foot-warn svg{width:11px;height:11px;}
</style>

<div class="ost-wrap">
  <div class="ost-cols">

    {{-- ══════════ IN — Perlu Diposting ══════════ --}}
    <div class="ost-panel">
      <div class="ost-head in">
        <div class="ost-head-l">
          <div class="ost-head-ico in">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
          </div>
          <div class="ost-head-titles">
            <div class="ost-head-eyebrow">Transfer In &middot; Action Required</div>
            <div class="ost-head-title">Perlu Diposting</div>
          </div>
        </div>
        <div class="ost-chip">
          <span class="ost-chip-num {{ $outstandingInCount > 0 ? 'warn' : 'zero' }}">{{ $outstandingInCount }}</span>
          <span class="ost-chip-lbl">TRF</span>
        </div>
      </div>

      @if($outstandingInCount == 0)
        <div class="ost-empty">
          <div class="ost-empty-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div class="ost-empty-ttl">Semua Bersih</div>
          <div class="ost-empty-txt">Tidak ada transfer masuk yang menunggu posting.</div>
        </div>
      @else
        <div class="ost-body">
          @foreach($outstandingIn as $row)
            <div class="ost-item">
              <div class="ost-rail {{ $row->aging_level }}"></div>
              <div class="ost-item-in">
                <div class="ost-meta">
                  <div class="ost-meta-hd">
                    <span class="ost-docno">{{ $row->tr_number }}</span>
                    <span class="ost-tag {{ $stClass($row->status) }}">{{ $statusTr[$row->status] ?? '-' }}</span>
                    <span class="ost-age {{ $row->aging_level }}">{!! $svgClock !!}{{ $row->aging_label }}</span>
                  </div>
                  <div class="ost-route">
                    {!! $svgTruck !!}
                    <span class="ost-loc" title="{{ $row->location_name }}">{{ $row->location_name }}</span>
                    <span class="ost-route-arrow">{!! $svgArrow !!}</span>
                    <span class="ost-loc to" title="{{ $row->location_name_to }}">{{ $row->location_name_to }}</span>
                  </div>
                  <div class="ost-sub">
                    {!! $svgCal !!}<span>{{ $row->tr_date }}</span>
                    <span class="ost-sub-sep">|</span>
                    {!! $svgUser !!}<span>{{ $row->created_by }}</span>
                    @if($row->note)<span class="ost-sub-sep">|</span><span title="{{ $row->note }}">{{ Str::limit($row->note, 30) }}</span>@endif
                  </div>
                </div>
                <div class="ost-act">
                  <a href="javascript:;" class="ost-btn ost-btn-post"
                     data-size="sm" data-ajax-delete="true"
                     data-confirm="Posting transfer {{ $row->tr_number }}? Stok akan langsung bergerak."
                     data-confirm-yes="document.getElementById('delete-form-{{ $row->id }}').submit();"
                     data-modal-id="{{ $row->id }}"
                     data-url="{{ route('transferStock.posting', ['id'=>Crypt::encryptString($row->id)]) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Posting
                  </a>
                  <a href="{{ route('transferStock.show', ['id'=>Crypt::encryptString($row->id)]) }}" class="ost-btn ost-btn-detail">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Detail
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
        @php $maxIn = $outstandingIn->max('age_seconds'); $oldIn = $outstandingIn->firstWhere('age_seconds', $maxIn); @endphp
        <div class="ost-foot">
          <span class="ost-foot-l"><strong>{{ $outstandingInCount }}</strong> transfer &middot; terlama <strong>{{ $oldIn->tr_number ?? '-' }}</strong></span>
          @if($maxIn >= 259200)
            <span class="ost-foot-warn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>&gt; 3 hari</span>
          @endif
        </div>
      @endif
    </div>

    {{-- ══════════ OUT — Menunggu Penerima ══════════ --}}
    <div class="ost-panel">
      <div class="ost-head out">
        <div class="ost-head-l">
          <div class="ost-head-ico out">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
          </div>
          <div class="ost-head-titles">
            <div class="ost-head-eyebrow">Transfer Out &middot; Monitoring</div>
            <div class="ost-head-title">Menunggu Penerima</div>
          </div>
        </div>
        <div class="ost-chip">
          <span class="ost-chip-num {{ $outstandingOutCount > 0 ? 'warn' : 'zero' }}">{{ $outstandingOutCount }}</span>
          <span class="ost-chip-lbl">TRF</span>
        </div>
      </div>

      @if($outstandingOutCount > 0)
        <div class="ost-advisory">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>Stok <strong>belum terakumulasi</strong> di gudang tujuan sampai transfer diposting. Hubungi dept penerima untuk menyelesaikan posting.</span>
        </div>
      @endif

      @if($outstandingOutCount == 0)
        <div class="ost-empty">
          <div class="ost-empty-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></div>
          <div class="ost-empty-ttl">Semua Terproses</div>
          <div class="ost-empty-txt">Tidak ada transfer keluar yang masih menunggu.</div>
        </div>
      @else
        <div class="ost-body">
          @foreach($outstandingOut as $row)
            <div class="ost-item">
              <div class="ost-rail {{ $row->aging_level }}"></div>
              <div class="ost-item-in">
                <div class="ost-meta">
                  <div class="ost-meta-hd">
                    <span class="ost-docno">{{ $row->tr_number }}</span>
                    <span class="ost-tag {{ $stClass($row->status) }}">{{ $statusTr[$row->status] ?? '-' }}</span>
                    <span class="ost-age {{ $row->aging_level }}">{!! $svgClock !!}{{ $row->aging_label }}</span>
                  </div>
                  <div class="ost-route">
                    {!! $svgTruck !!}
                    <span class="ost-loc" title="{{ $row->location_name }}">{{ $row->location_name }}</span>
                    <span class="ost-route-arrow">{!! $svgArrow !!}</span>
                    <span class="ost-loc to" title="{{ $row->location_name_to }}">{{ $row->location_name_to }}</span>
                  </div>
                  <div class="ost-sub">
                    {!! $svgCal !!}<span>{{ $row->tr_date }}</span>
                    <span class="ost-sub-sep">|</span>
                    {!! $svgUser !!}<span>{{ $row->created_by }}</span>
                    @if($row->note)<span class="ost-sub-sep">|</span><span title="{{ $row->note }}">{{ Str::limit($row->note, 30) }}</span>@endif
                  </div>
                  <div class="ost-contact">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    Hubungi <strong>{{ $row->location_name_to }}</strong> untuk posting
                  </div>
                </div>
                <div class="ost-act">
                  <a href="{{ route('transferStock.show', ['id'=>Crypt::encryptString($row->id)]) }}" class="ost-btn ost-btn-detail">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    Detail
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>
        @php $maxOut = $outstandingOut->max('age_seconds'); $oldOut = $outstandingOut->firstWhere('age_seconds', $maxOut); @endphp
        <div class="ost-foot">
          <span class="ost-foot-l"><strong>{{ $outstandingOutCount }}</strong> transfer &middot; terlama <strong>{{ $oldOut->tr_number ?? '-' }}</strong></span>
          @if($maxOut >= 259200)
            <span class="ost-foot-warn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>&gt; 3 hari</span>
          @endif
        </div>
      @endif
    </div>

  </div>
</div>
{{-- ════════════════════ END OUTSTANDING ════════════════════ --}}
<section id="transfer-index">
  <div class="card">
    <div class="card-header">  
      <h4 class="card-title">Filter</h4>
      <div class="heading-elements">
        <ul class="list-inline mb-0">
            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <form class="needs-validation" novalidate>
            <div class="form-row">
              <div class="form-group col-md-4"> 
                <label for="searchTr">Transfer Number</label>
                <input type="text" class="form-control text-uppercase" id="searchTr" name="searchTr" placeholder=""  />
              </div>
              <div class="col-md-4 form-group">
                <label for="trDate">Transfer Date</label>
                <input type="text" id="trDate" name="trDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
              </div>
              <div class="form-group col-md-4"> 
                <label class="form-label" for="searchStatus">Status</label>
                <select class="select2 form-control" id="searchStatus" name="searchStatus">
                    <option value="">All</option>
                    @foreach($status as $index=>$val)
                        <option value="{{ $index }}">{{ $index }} - {{ $val }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
               <div class="form-group col-md-4">
    <label class="form-label d-block">Transfer Type</label>

  <select class="select2 form-control" id="searchType" name="searchType">
                    <option value="">All</option>
                    <option value="in">IN (Diterima)</option>
                    <option value="out">OUT (Dikirim)</option>
                </select>
</div>
              <div class="form-group col-md-4"> 
                <label class="form-label" for="searchLocFrom">Location From</label>
                <select class="select2 form-control" id="searchLocFrom" name="searchLocFrom">
                    <option value="">All</option>
                    @foreach($locations as $val)
                      <option value="{{$val->location_code}}" >{{$val->location_name}}</option>
                    @endforeach
                </select>
              </div>
              <div class="form-group col-md-4"> 
                <label class="form-label" for="searchLocTo">Location To</label>
                <select class="select2 form-control" id="searchLocTo" name="searchLocTo">
                    <option value="">All</option>
                    @foreach($locations as $val)
                      <option value="{{$val->location_code}}" >{{$val->location_name}}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
                <div class="col-12"> 
                    <button type="button" class="btn btn-primary" id ="btnSearch" name="btnSearch">Search</button>
                    @can('transferIn-create')
                    <a href="{{ route('transferStock.create') }}" class="btn btn-info"><i class="fa fa-plus"></i> Create</a>
                    @endcan
                </div>
            </div>
        </form>
      </div>
    </div>
  </div>
</section>
<section id="table-transfer">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title"> @yield('title') List</h4>
      <div class="heading-elements">
          <ul class="list-inline mb-0">
              <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
              <li><a data-action="reload"><i data-feather="rotate-cw"></i></a></li>
          </ul>
      </div>
    </div>
    <div class="card-content collapse show">
      <div class="card-body">
        <button type="button" class="btn btn-primary d-none" id ="btnDetail" name="btnDetail" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data detail">Detail</button>
        <button type="button" class="btn btn-primary d-none" id ="btnSummary" name="btnSummary" data-toggle="tooltip" data-placement="right" title="Tekan tombol untuk melihat data summary">Summary</button>
        <div class="row">
            <div class="col-sm-12">
              <div class="card-datatable table-responsive pt-0">
                <table id="detailedTable" class="table">
                  <thead class="thead-light">
                  </thead>
                </table>
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
</style>
@endsection
@section('scripts')
<script type="text/javascript">

  let searchTr = document.querySelector("#searchTr");
  let searchType = 'TROUT';
  let searchStatus = document.querySelector("#searchStatus");
  let trDate = document.querySelector("#trDate");
  let search = document.querySelector('#btnSearch');
  let refresh = document.querySelector('a[data-action="reload"]');
  let rangePickr = document.querySelector('.flatpickr-range');
  let searchLocFrom = document.querySelector('#searchLocFrom');
  let searchLocTo = document.querySelector('#searchLocTo');
  let btnSummary = $('#btnSummary');
  let btnDetail = $('#btnDetail');

  let href;
  $(document).on('click', '#cancelReasonButton', function(event) {
      event.preventDefault();
      href = $(this).data('href');
      $('#modalReasonCancel').attr("action", href);
  });

  initDatePicker(rangePickr,{
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
  });

  function dataSearch($type){
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');

    $(".loading-spinner-container").addClass("-show");

    if($type == 'detail'){
      showListDetail(searchTr.value,searchType.value,searchStatus.value,trDate.value,searchLocFrom.value,searchLocTo.value);
    }

    if($type == 'summary'){ 
      showList(searchTr.value,searchType.value,searchStatus.value,trDate.value,searchLocFrom.value,searchLocTo.value); 
    }
    
  }

  //refresh di cards
  refresh.addEventListener("click",function(){
    dataSearch('summary');
  })

  btnDetail.click(function(){
    dataSearch('detail');
  });

  btnSummary.click(function(){
    dataSearch('summary');
  });

  $("#btnSearch").click(function(e){
    btnSummary.addClass('d-none');
    btnDetail.addClass('d-none');
    dataSearch('summary');
  });

  const showList = (searchTr,searchType,searchStatus,trDate,searchLocFrom,searchLocTo) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('transferStock.list') }}",
      kolom:{!! $kolom !!},
      arrColPrint:[1,2,3,4,5,6,7,8,9,10],
      columnDefs :[
        { width: '5%', targets: 0 },
      ],
      dataSearch:  {
        searchTr:searchTr,
        searchType:searchType,
        searchStatus:searchStatus,
        trDate:trDate,
        transferFrom:searchLocFrom,
        transferTo:searchLocTo
      },
      initComplete: function() {
        let api = this.api();
        if (api.data().length > 0) {
          btnDetail.removeClass('d-none');
          btnSummary.addClass('d-none');
        }
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn:[[ 1, 'desc' ]],
      excelFileName:'transfer_out'
    });
  }

  const showListDetail = (searchTr,searchType,searchStatus,trDate,searchLocFrom,searchLocTo) => {
    if ($('#detailedTable tr').length >0){
        let table= $('#detailedTable').DataTable();
        table.destroy();
        $('#detailedTable tbody > tr').remove();
        $("#detailedTable thead > tr").remove();
    }
    showDataTables({
      tableId:"detailedTable",
      route:"{{ route('transferStock.list.detail') }}",
      kolom:{!! $kolomDetail !!},
      arrColPrint:[0,1,2,3,4,5,6,7,8,9,10,11,12,13,14],
      columnDefs :[
        { width: '5%', targets: 0 },
        { className: 'text-right','targets': [4] },
      ],
      dataSearch:  {
        searchTr:searchTr,
        searchType:searchType,
        searchStatus:searchStatus,
        trDate:trDate,
        transferFrom:searchLocFrom,
        transferTo:searchLocTo
      },
      initComplete: function() {
        let api = this.api();
        if (api.data().length > 0) {
          btnSummary.removeClass('d-none');
          btnDetail.addClass('d-none');
        }
        $(".loading-spinner-container").removeClass("-show");
      },
      orderColumn:[[ 0, 'asc' ],[ 1, 'asc' ],[ 2, 'asc' ]],
      excelFileName:'transfer_out'
    });
  }
 
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
    
</script>
@endsection
