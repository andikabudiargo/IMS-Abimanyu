<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sheet1</title>
<style type="text/css"> 
</style>
</head>
<body>
    <table class="oki">
        <tbody>
            <tr>
                <td colspan="5" align='center' > <strong>SO REPORT</strong></td>
            </tr>
            <tr>
                <td valign="" ></td>
                <td valign="" ></td>
                <td></td>
            </tr>
            <tr>
                <td valign="" ></td>
                <td valign="" ></td>
                <td></td>
            </tr>
            <tr>
                <td valign="" >No Order</td>
                <td valign="" >: {{ $soNumber }}</td>
                <td></td>
            </tr>
            <tr>
                <td valign=""  >No PO</td>
                <td valign=""  >: {{ $poNumber }}</td>
                <td></td>
            </tr>
            <tr>
                <td valign=""  >Customer</td>
                <td valign=""  >: {{ $customer }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    <table width="100%">
        <tbody>
            {!! $barisDetail !!}                
        </tbody>
    </table>
</body>
</html>