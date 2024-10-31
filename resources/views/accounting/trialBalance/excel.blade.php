<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body>
    <table>
        <thead>
            <tr>
                <td>
                    <div class="header-space">
                        <table width="100%">
                            <tr>
                                <td colspan="2" style="text-align:center;">LABA RUGI</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align:center;">PT ABIMANYU SEKAR NUSANTARA</td><td></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="text-align:center;">PERIODE {{ $tanggal }}</td><td></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="content">
                        <table class="table table-condensed table-striped">
                            <tbody>
                                <tr>
                                    <td>Akun</td><td >Balance</td>
                                </tr>
                            @foreach($mains as $key => $main)
                                @foreach($groups as $keyGroup => $group)
                                    @if($group->main == $main->main)
                                    <tr><td></td><td></td></tr>
                                        <tr>
                                            <td><b>{{ $group ->group_name}}</b></td><td></td>
                                        </tr>
                                        @foreach($details as $keyDetail => $detail)
                                            @if($detail->group_code == $group->group_data)
                                            <tr>
                                                <td>{{ $detail->sub_group_name }} ({{ $detail->account }})</td>
                                                <td>Rp.{{ number_format($detail->saldo,2) }} </td>
                                            </tr>
                                            @endif
                                        @endforeach
                                        @foreach($totalGroups as $keyTotalGroup => $totalGroup)
                                            @if($totalGroup->group_code == $group->group_data)
                                            <tr>
                                                <td><b>Total {{ $totalGroup->group_name }}</b></td>
                                                <td><b>Rp.{{ number_format($totalGroup->jumlah,2) }}</b></td> 
                                            </tr>
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                                @foreach($totalMains as $keyTotalMain => $totalMain)
                                    @if($totalMain->main == $main->main)
                                        <tr><td></td><td></td></tr>
                                        <tr>
                                            <td><b>{{ $totalMain->main_name }}</b></td>
                                            @if($totalMain->main == 'lababersih')
                                              <td><b>Rp.{{ $labaBersih }}</b></td>
                                            @endif
                                            @if($totalMain->main == 'labakotor')
                                              <td><b>Rp.{{ $labaKotor }}</b></td>
                                            @endif
                                        </tr>
                                    @endif                                    
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
            <td>
            <div class="footer-space">
            </div>
            </td>
            </tr>
        </tfoot>
    </table>
</body>
</html>