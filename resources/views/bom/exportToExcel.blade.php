<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sheet1</title>
<style type="text/css"> 
</style>
</head>
<body>
    <table width="100%">
        <thead>
        <tr>
            <th>BOM Code</th>
            <th>Revision</th>
            <th>Article Finish Good</th>
            <th>Customer</th>
            <th>Group of Material</th>
            <th>UOM</th>
            <th>Part No</th>
            <th>Model</th>
            <th>Note</th>

            @for($i = 1; $i <= $maxSbCount; $i++)
                <th>Spray Booth {{ $i }}</th>
                <th>Tone SB {{ $i }}</th>
                <th>Tack {{ $i }}</th>
                <th>Pass Rate {{ $i }}</th>
                <th>Pass Thru {{ $i }}</th>
                <th>Cycle Time {{ $i }}</th>
            @endfor

            @for($i = 1; $i <= $maxDetCount; $i++)
                <th>Article Det {{ $i }}</th>
                <th>Tone Det {{ $i }}</th>
                <th>Pos {{ $i }}</th>
                <th>Qty {{ $i }}</th>
                <th>UOM Det {{ $i }}</th>
                <th>UOM Con {{ $i }}</th>
            @endfor

            @for($i = 1; $i <= $maxRmCount; $i++)
                <th>Article RM {{ $i }}</th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @foreach($headers as $val)
        <tr>
            <td>{{ $val->bom_code }}</td>
            <td>{{ $val->num_revision }}</td>
            <td>{{ $val->article_finish_good }}</td>
            <td>{{ $val->customer }}</td>
            <td>{{ $val->group_of_material }}</td>
            <td>{{ $val->uom }}</td>
            <td>{{ $val->part_no }}</td>
            <td>{{ $val->model }}</td>
            <td>{{ $val->note }}</td>

            @for($i = 1; $i <= $maxSbCount; $i++)
                <td>{{ $val->{'spray_booth_'.$i} ?? '' }}</td>
                <td>{{ $val->{'tone_sb_'.$i} ?? '' }}</td>
                <td>{{ $val->{'tack_'.$i} ?? '' }}</td>
                <td>{{ $val->{'pass_rate_'.$i} ?? '' }}</td>
                <td>{{ $val->{'pass_thru_'.$i} ?? '' }}</td>
                <td>{{ $val->{'cycle_time_'.$i} ?? '' }}</td>
            @endfor

            @for($i = 1; $i <= $maxDetCount; $i++)
                <td>{{ $val->{'article_det_'.$i} ?? '' }}</td>
                <td>{{ $val->{'tone_det_'.$i} ?? '' }}</td>
                <td>{{ $val->{'pos_det_'.$i} ?? '' }}</td>
                <td>{{ $val->{'qty_det_'.$i} ?? '' }}</td>
                <td>{{ $val->{'uom_det_'.$i} ?? '' }}</td>
                <td>{{ $val->{'uom_con_det_'.$i} ?? '' }}</td>
            @endfor

            @for($i = 1; $i <= $maxRmCount; $i++)
                <td>{{ $val->{'article_rm_'.$i} ?? '' }}</td>
            @endfor
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>