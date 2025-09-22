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
                <th>Bom Code</th>
                <th>Num Revision</th>
                <th>Article Finish Good</th>
                <th>Article Raw Material</th>
                <th>Customer</th>
                <th>Group Of Material</th>
                <th>Bom Header</th>
                <th>Part No</th>
                <th>Model</th>
                <th>Note</th>
                <th>Spray Booth 1</th>
                <th>Spray Booth 2</th>
                <th>Spray Booth 3</th>
                <th>Tone 1</th>
                <th>Tone 2</th>
                <th>Tone 3</th>
                <th>Tack 1</th>
                <th>Tack 2</th>
                <th>Tack 3</th>
                <th>Pass Rate 1</th>
                <th>Pass Rate 2</th>
                <th>Pass Rate 3</th>
                <th>Pass Trough 1</th>
                <th>Pass Trough 2</th>
                <th>Pass Trough 3</th>
                <th>Cycle Time Buffing 1</th>
                <th>Cycle Time Buffing 2</th>
                <th>Cycle Time Buffing 3</th>
                <th>Article Code 1</th>
                <th>Article Code 2</th>
                <th>Article Code 3</th>
                <th>Article Code 4</th>
                <th>Tone 1</th>
                <th>Tone 2</th>
                <th>Tone 3</th>
                <th>Tone 4</th>
                <th>Pos 1</th>
                <th>Pos 2</th>
                <th>Pos 3</th>
                <th>Pos 4</th>
                <th>Qty 1</th>
                <th>Qty 2</th>
                <th>Qty 3</th>
                <th>Qty 4</th>
                <th>Uom 1</th>
                <th>Uom 2</th>
                <th>Uom 3</th>
                <th>Uom 4</th>
                <th>Uom Con 1</th>
                <th>Uom Con 2</th>
                <th>Uom Con 3</th>
                <th>Uom Con 4</th>
            </tr>
        </thead>
        <tbody>
            @foreach($headers as $key => $header)
            <tr>
                <td>{{ $header->bom_code }}</td>
                <td>{{ $header->num_revision }}</td>
                <td>{{ $header->article_finish_good }}</td>
                <td>{{ $header->article_raw_material }}</td>
                <td>{{ $header->customer }}</td>
                <td>{{ $header->group_of_material }}</td>
                <td>{{ $header->uom }}</td>
                <td>{{ $header->part_no }}</td>
                <td>{{ $header->model }}</td>
                <td>{{ $header->note }}</td>
                <td>{{ $header->spray_booth_1 }}</td>
                <td>{{ $header->spray_booth_2 }}</td>
                <td>{{ $header->spray_booth_3 }}</td>
                <td>{{ $header->tone_1 }}</td>
                <td>{{ $header->tone_2 }}</td>
                <td>{{ $header->tone_3 }}</td>
                <td>{{ $header->tack_1 }}</td>
                <td>{{ $header->tack_2 }}</td>
                <td>{{ $header->tack_3 }}</td>
                <td>{{ $header->pass_rate_1 }}</td>
                <td>{{ $header->pass_rate_2 }}</td>
                <td>{{ $header->pass_rate_3 }}</td>
                <td>{{ $header->pass_trough_1 }}</td>
                <td>{{ $header->pass_trough_2 }}</td>
                <td>{{ $header->pass_trough_3 }}</td>
                <td>{{ $header->cycle_time_buffing_1 }}</td>
                <td>{{ $header->cycle_time_buffing_2 }}</td>
                <td>{{ $header->cycle_time_buffing_3 }}</td>
                <td>{{ $header->article_code_1 }}</td>
                <td>{{ $header->article_code_2 }}</td>
                <td>{{ $header->article_code_3 }}</td>
                <td>{{ $header->article_code_4 }}</td>
                <td>{{ $header->tone_d_1 }}</td>
                <td>{{ $header->tone_d_2 }}</td>
                <td>{{ $header->tone_d_3 }}</td>
                <td>{{ $header->tone_d_4 }}</td>
                <td>{{ $header->pos_1 }}</td>
                <td>{{ $header->pos_2 }}</td>
                <td>{{ $header->pos_3 }}</td>
                <td>{{ $header->pos_4 }}</td>
                <td>{{ $header->qty_1 }}</td>
                <td>{{ $header->qty_2 }}</td>
                <td>{{ $header->qty_3 }}</td>
                <td>{{ $header->qty_4 }}</td>
                <td>{{ $header->uom_1 }}</td>
                <td>{{ $header->uom_2 }}</td>
                <td>{{ $header->uom_3 }}</td>
                <td>{{ $header->uom_4 }}</td>
                <td>{{ $header->uom_con_1 }}</td>
                <td>{{ $header->uom_con_2 }}</td>
                <td>{{ $header->uom_con_3 }}</td>
                <td>{{ $header->uom_con_4 }}</td>
            </tr>
            @endforeach
        </tbody>
            
    </table>
</body>
</html>