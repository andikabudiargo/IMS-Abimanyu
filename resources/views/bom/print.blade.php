@php
    // RM dari controller (bom_rm)
    $rmList   = isset($rawMaterials) ? $rawMaterials : collect([]);
    $rmCount  = ($rmList && count($rmList) > 0) ? count($rmList) : 1;

    // Tentukan mode dari UoM FG: PCS = single (RM di header), SET = multi (RM tabel di bawah)
    $uomFg    = strtoupper(trim($bomHdr->uom ?? ''));
    $isMulti  = ($uomFg === 'SET');

    // Tinggi header dinamis. Kalau single, RM ikut di header (tambah baris).
    $rmHeaderRows  = $isMulti ? 0 : max($rmCount, 1);
    $headerRows    = 5 + $rmHeaderRows;      // title(2)+PartNo(1)+Customer(1)+FG(1)+RM(opsional)
    $headerHeight  = $headerRows * 22;
    $pageTopMargin = $headerHeight + 30;
    $headerTop     = -($pageTopMargin - 27);

    // rowspan logo = jumlah baris header (di luar kolom logo)
    $logoRowspan   = $headerRows;
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style type="text/css">

    @page { margin: {{ $pageTopMargin }}px 10px 10px 10px; }
    body { margin: 10px;border: 1px solid black; }

    header {
        position: fixed;
        top: {{ $headerTop }}px;
        left: 10px;
        right: 10px;
        height: {{ $headerHeight }}px;
    }

    footer {
        position: fixed;
        bottom: 3%;
        left: 10px;
        right: 10px;
        height: 180px;
    }

    .breakNow { page-break-inside:avoid; page-break-after:always; }

    .pagenum:before { content: counter(page); }

    * { font-family: Verdana, Arial, sans-serif; }

    table{ font-size: x-small; }
    tfoot tr td{ font-size: medium; }

    .gray { background-color: lightgray; font-weight: bold; }

    th { height: 30px; }
    td { height: 20px; }

    th, td { padding-left: 15px; padding-right: 15px; }

    table, th, td { border: 1px solid black; border-collapse: collapse; }

    .font-10 { font-size: 10px; }
    .font-9  { font-size: 9px; }
    .font-8  { font-size: 8px; }

    .header-padding{ padding : 0 2px 0 2px; }
    .detail-padding-bawah{ padding : 0 5px 0 5px; border:none; }

    .h-tengah{ text-align:center; }
    .no-wrap{ white-space: nowrap; }

    #watermark {
        background: url('{{asset('assets/img/lunas-stamp.png')}}') center;
        background-size: 10px 10px;
        background-repeat: no-repeat;
        opacity: 0.1;
    }

</style>
</head>
<body>

    <header>
        <table width="100%" border="0">
            {{-- Logo + Judul + No Dokumen / Tgl Berlaku --}}
            <tr>
                <td width="6%" rowspan="{{ $logoRowspan }}" class="no-wrap h-tengah">
                    <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" width="80" height="50" />
                </td>
                <td colspan="4" rowspan="2" valign="middle" class="header-padding h-tengah"><h2>BILL OF MATERIALS</h2></td>
                <td class="font-10 header-padding no-wrap">No Dokumen</td>
                <td class="font-10 header-padding no-wrap">: ENG-02.08-FM</td>
            </tr>
            <tr>
                <td class="font-10 header-padding no-wrap">Tgl Berlaku</td>
                <td class="font-10 header-padding no-wrap">: 25 Nov 2021</td>
            </tr>

            {{-- Part No / Model / No Revisi --}}
            <tr>
                <td class="font-10 header-padding no-wrap">Part No</td>
                <td class="font-9 header-padding">{{ $bomHdr->part_no }}</td>
                <td class="font-10 header-padding no-wrap">Model</td>
                <td class="font-9 header-padding">{{ $bomHdr->model }}</td>
                <td class="font-10 header-padding no-wrap">No Revisi</td>
                <td class="font-10 header-padding">{{ $bomHdr->num_revision ?? 1 }}</td>
            </tr>

            {{-- Customer / Halaman --}}
            <tr>
                <td class="font-10 header-padding no-wrap">Customer</td>
                <td colspan="3" class="font-9 header-padding">{{ $bomHdr->nama }}</td>
                <td class="font-10 header-padding no-wrap">Halaman</td>
                <td class="font-10 header-padding"><span class="pagenum"></span></td>
            </tr>

            {{-- Part FG (selalu di header) --}}
            <tr>
                <td class="font-10 header-padding no-wrap">Part FG</td>
                <td colspan="5" class="font-9 header-padding">
                    {{ $bomHdr->article_code_fg_alt }} - {{ $bomHdr->article_desc }}
                </td>
            </tr>

            {{-- Part RM di header HANYA jika SINGLE (uom FG = PCS) --}}
            @if(!$isMulti)
                @if($rmList && count($rmList) > 0)
                    @foreach($rmList as $rm)
                        @php
                            $rmDesc = is_object($rm) ? ($rm->article_desc_rm ?? '') : ($rm['article_desc_rm'] ?? '');
                            $rmAlt  = is_object($rm) ? ($rm->article_code_rm_alt ?? null) : ($rm['article_code_rm_alt'] ?? null);
                        @endphp
                        <tr>
                            <td class="font-10 header-padding no-wrap">@if($loop->first) Part RM @endif</td>
                            <td colspan="5" class="font-9 header-padding">
                                {{ $rmDesc }}
                                @if(!empty($rmAlt))
                                    <br><span class="font-8">Code: {{ $rmAlt }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="font-10 header-padding no-wrap">Part RM</td>
                        <td colspan="5" class="font-9 header-padding">
                            {{ $bomHdr->article_desc_rm }}
                            @if(!empty($bomHdr->article_code_rm_alt))
                                <br><span class="font-8">Code: {{ $bomHdr->article_code_rm_alt }}</span>
                            @endif
                        </td>
                    </tr>
                @endif
            @endif
        </table>
    </header>

    <main>

        {{-- ===== Tabel RM (hanya MULTI / uom FG = SET) ===== --}}
        @if($isMulti)
            <table width="100%" style="margin-bottom:6px;">
                <thead style="background-color: lightgray;">
                    <tr>
                        <th width="6%" align="left">No</th>
                        <th align="left">Part Raw Material</th>
                        <th width="30%" align="left">Kode Barang</th>
                    </tr>
                </thead>
                <tbody>
                    @if($rmList && count($rmList) > 0)
                        @foreach($rmList as $key => $rm)
                            @php
                                $rmDesc = is_object($rm) ? ($rm->article_desc_rm ?? '') : ($rm['article_desc_rm'] ?? '');
                                $rmAlt  = is_object($rm) ? ($rm->article_code_rm_alt ?? null) : ($rm['article_code_rm_alt'] ?? null);
                            @endphp
                            <tr>
                                <td class="font-9" align="center">{{ $key + 1 }}</td>
                                <td class="font-9">{{ $rmDesc }}</td>
                                <td class="font-9">{{ $rmAlt ?: '-' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="font-9" align="center">1</td>
                            <td class="font-9">{{ $bomHdr->article_desc_rm }}</td>
                            <td class="font-9">{{ $bomHdr->article_code_rm_alt ?: '-' }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endif

        {{-- ===== Tabel Material ===== --}}
        <table width="100%">
            <thead style="background-color: lightgray;">
                <tr>
                    <th width="5%">No</th>
                    <th>Material</th>
                    <th width="25%">Brand</th>
                    <th width="5%">Consumption</th>
                    <th width="5%">Unit</th>
                    <th width="10%">Kode Barang</th>
                </tr>
            </thead>
            <tbody>
                {!! $barisDetail !!}
            </tbody>
        </table>
    </main>

    @if($jumlahBaris > 32 )
        <div class="breakNow"></div>
    @endif

    <footer>
        <textarea class="font-9" type="text" style="height:20%;padding-left:10px;padding-bottom:10px;border:none">Note:<br>{{ $bomHdr->note_hdr }}<br></textarea>
        <table style="border:none;">
            <tr>
                <td align="left" class="detail-padding-bawah">No Revisi</td>
                <td align="left" class="detail-padding-bawah">:{{ $bomHdr->num_revision }}</td>
            </tr>
            <tr>
                <td align="left" class="detail-padding-bawah">Enginering</td>
                <td align="left" class="detail-padding-bawah">:{{ $bomHdr->tanggal_revisi ? date_format(date_create($bomHdr->tanggal_revisi),"d F Y"): '' }}</td>
            </tr>
        </table>
        <table width="100%" border="0">
            <tr>
                <td width="10%" align="center">Prepared By</td>
                <td width="10%" align="center">Checked By</td>
                <td width="10%" align="center">Approved By</td>
                <td width="10%" align="center">Approved By</td>
                <td width="10%" align="center">Approved By</td>
            </tr>
            <tr>
                <td align="center" style="height: 40px;"></td>
                <td align="center" style="height: 40px;"></td>
                <td align="center" style="height: 40px;"></td>
                <td align="center" style="height: 40px;"></td>
                <td align="center" style="height: 40px;"></td>
            </tr>
            <tr>
                @foreach($approvalHistory as $key=>$val)
                    @if($key<5)
                        @if($val->status == true)
                            <td align="center">{{ $val->name }}</td>
                        @else
                            <td align="center"></td>
                        @endif
                    @endif
                @endforeach
            </tr>
        </table>
    </footer>

</body>
</html>