<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan untuk Dicetak</title>
    <style>
        /* Gaya untuk tampilan di browser */

        @page { margin: 110px 10px 10px 10px; }
        body { margin: 10px; }
        
        body {
            font-family: Calibri, Arial, Helvetica, sans-serif;;
            /* margin: 0; */
            padding: 20px;
            background-color: #f0f4f8;
            color: #333;
        }
        
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            border-radius: 8px;
        }
        
        .controls {
            text-align: center;
            margin-bottom: 25px;
            padding: 15px;
            color: white;
        }
        
        button {
            background-color: #ffffff;
            color: #2E8B57;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        button:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .info {
            background-color: #e8f5e9;
            padding: 15px;
            border-left: 4px solid #4CAF50;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        /* Gaya khusus untuk cetakan */
        @media print {
            body, .print-container {
                /* margin: 0; */
                margin: 10px;
                padding: 0;
                background: none;
                box-shadow: none;
                width: 100%;
            }
            
            /* Sembunyikan elemen yang tidak perlu dicetak */
            .controls, .info {
                display: none;
            }
            
            /* Atur ukuran kertas A4 */
            @page {
                size: A4 portrait;
                /* margin: 1.5cm 1cm 2cm 1cm; */
                margin: 110px 10px 10px 10px;
            }
            
            /* Header hanya muncul di halaman pertama */
            .header {
                display: block;
                position: running(header);
            }
            
            /* Footer hanya muncul di halaman terakhir */
            .footer {
                position: running(footer);
                width: 100%;
            }
            
            /* Pastikan tabel dapat di-break antar halaman */
            table {
                page-break-inside: auto;
                width: 100%;
                border-collapse: collapse;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            thead {
                display: table-header-group;
            }
            
            tbody tr {
                break-inside: avoid;
            }
            
            /* Header untuk setiap halaman (selain pertama) */
            .page-header {
                display: none;
            }
            
            @page :not(:first) {
                @top-center {
                    content: element(pageHeader);
                }
            }
            
            /* Footer untuk setiap halaman (selain terakhir) */
            .page-footer {
                display: none;
            }
            
            @page :not(:last) {
                @bottom-center {
                    content: element(pageFooter);
                }
            }
            
            /* Sembunyikan header di halaman selain pertama */
            .header:not(.first-page-header) {
                display: none;
            }
            
            /* Sembunyikan footer di halaman selain terakhir */
            .footer:not(.last-page-footer) {
                display: none;
            }
            
            /* Tampilkan nomor halaman */
            @page {
                @bottom-right {
                    content: "Halaman " counter(page) " dari " counter(pages);
                    font-size: 10pt;
                    color: #777;
                }
            }
        }
        
        /* Gaya untuk konten laporan */
        .header {
            text-align: center;
        }
        
        .company-info {
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            /* color: #2E8B57; */
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 20px;
            margin: 15px 0;
            color: #333;
        }
        
        .report-period {
            font-style: italic;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 2px;
            text-align: left;
            padding-left: 5px;
            padding-right: 5px;
        }
        
        th {
            color: rgb(8, 8, 8);
            font-weight: bold;
        }
        
        
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
        }

        table.oki td, table.oki th {
            border: none;
            padding: 5px;
        }

        table.oki {
            border: none;
            margin-bottom: 5px;
        }

        .font-16 {
            font-size: 16pt;
        }

        .font-14 {
            font-size: medium;
        }

        .font-11 {
            font-size: 11pt;
        }

        .huruf-tebal{
            font-weight: bold;
        }

        .font-small {
            font-size: small;
        }

    </style>
</head>
<body>
    <div class="print-container">
        <div class="controls">
            <button onclick="printDoc()" >Print</button>
        </div>
        <!-- Header hanya akan muncul di halaman pertama saat dicetak -->
        <div class="header first-page-header">
            <table width="100%" class="oki">
                <tbody>
                    <tr>
                        <td style="vertical-align: bottom;padding:0px">
                            <div class="huruf-tebal font-16" style="text-align:center">BUKTI BANK KELUAR</div>
                            <div class="huruf-tebal font-14" style="text-align:center">{{ $header->voucher_number }}</div>
                            <br>
                            <table width="100%" class="oki" >
                                <tr class="tanpa-padding">
                                    <td class="tanpa-padding font-14" width="10%">Tanggal</td>
                                    <td class="tanpa-padding font-14" width="43%">: {{ $header->voucher_date }}</td>
                                    <td class="tanpa-padding font-14" width="10%">Departemen</td><td class="font-8">: {{ $costCenter }}</td>
                                </tr>
                                <tr class="tanpa-padding">
                                    <td class="tanpa-padding font-14" width="10%">Kepada</td>
                                    <td class="tanpa-padding font-8" width="43%">: {{ $header->supplier_name }}</td>
                                    <td class="tanpa-padding font-14" width="10%">Halaman</td><td>: <span class="pagenum"></span></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <table class="isiData" class="font-small" style= "table-layout: fixed;">
            <thead class="font-11">
                <tr>
                    <th width="10%">No Account</th>
                    <th width="15%">Account Name</th>
                    <th width="15%">Referensi</th>
                    <th width="">Keterangan</th>
                    <th width="13%">Debet</th>
                    <th width="13%">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <!-- Data tabel akan diisi oleh JavaScript -->
            </tbody>
        </table>
        
        <div class="footer last-page-footer">
            <table width="100%">
                <tr> 
                    <td align="center" style="border-color: white;text-align: center;" width="20%">Dibuat oleh</td>
                    <td align="center" style="border-color: white;text-align: center;" width="5%"></td>
                    <td align="center" style="border-color: white;text-align: center;" width="20%">Diperiksa</td>
                    <td align="center" style="border-color: white;text-align: center;" width="5%"></td>
                    <td align="center" style="border-color: white;text-align: center;" width="20%">Mengetahui</td>
                    <td align="center" style="border-color: white;text-align: center;" width="5%"></td>
                    <td align="center" style="border-color: white;text-align: center;" width="20%">Menyetujui</td>
                    <td align="center" style="border-color: white;text-align: center;" width="5%"></td>
                </tr>
                <tr>
                    <td align="center" style="border-color: white;text-align: center;height='25'">{{ $approval1 ? 'Approval 1':'' }}</td>
                    <td align="center" style="border-color: white;text-align: center;"><br><br></td>
                    <td align="center" style="border-color: white;text-align: center;">{{ $approval2 ? 'Approval 2':'' }}</td>
                    <td align="center" style="border-color: white;text-align: center;"></td>
                    <td align="center" style="border-color: white;text-align: center;">{{ $approval3 ? 'Approval 3':'' }}</td>
                    <td align="center" style="border-color: white;text-align: center;"></td>
                    <td align="center" style="border-color: white;text-align: center;">{{ $approval4 ? 'Approval 4':'' }}</td>
                    <td align="center" style="border-color: white;text-align: center;"></td>
                </tr>
                <tr>
                    <td align="center" style="border-bottom: 1px solid black;border-left: white;text-align: center;border-right: white;text-align: center;">{{ $approval1 ? $approval1->name:'' }}</td>
                    <td align="center" style="border-color: white;text-align: center;"></td>
                    <td align="center" style="border-bottom: 1px solid black;border-left: white;text-align: center;border-right: white;text-align: center;">  {{ $approval2 ? $approval2->name:'' }}  </td>
                    <td align="center" style="border-color: white;text-align: center;"></td>
                    <td align="center" style="border-bottom: 1px solid black;border-left: white;text-align: center;border-right: white;text-align: center;">  {{ $approval3 ? $approval3->name:'' }}  </td>
                    <td align="center" style="border-color: white;text-align: center;"></td>
                    <td align="center" style="border-bottom: 1px solid black;;border-left: white;text-align: center;border-right: white;text-align: center;">  {{ $approval4 ? $approval4->name:'' }}  </td>
                    <td align="center" style="border-color: white;text-align: center;"></td>
                </tr>
            </table>
        </div>
    </div>

    <script>
        function dataSource() {
            const data = [];
            const dataDetails = {!! $details !!};
            
            for (let i = 0; i < dataDetails.length; i++) {
                let noAccount = dataDetails[i].account;
                let accountName = dataDetails[i].account_name;
                let referensi = dataDetails[i].reference ?dataDetails[i].reference : '';
                let keterangan = dataDetails[i].description;
                let debet = dataDetails[i].debit;
                let kredit = dataDetails[i].credit;
                
                data.push({
                    noAccount: noAccount,
                    accountName: accountName,
                    referensi: referensi,
                    keterangan: keterangan,
                    debet: debet,
                    kredit: kredit
                });
            }
            
            return data;
        }

        function humanizeNumber(n) {
            n = n.toString()
            while (true) {
            let n2 = n.replace(/(\d)(\d{3})($|,|\.)/g, '$1,$2$3')
            if (n == n2) break
            n = n2
            }
            return n
        }
        
        // Fungsi untuk mengisi tabel dengan data
        function populateTable() {
            const tableBody = document.querySelector('.isiData');
            const data = dataSource();
            
            data.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td style = "font-size:small">${item.noAccount}</td>
                    <td style = "font-size:small">${item.accountName}</td>
                    <td style = "font-size:small">${item.referensi}</td>
                    <td style = "font-size:small">${item.keterangan}</td>
                    <td style = "text-align: right;font-size:small">${humanizeNumber(item.debet)}</td>
                    <td style = "text-align: right;font-size:small">${humanizeNumber(item.kredit)}</td>
                `;
                tableBody.appendChild(row);
            });

            row = document.createElement('tr');
            const note = '{{ $header->note }}';
            const totalDebit = '{{ number_format($total->total_debit,2) }}';
            const totalKredit = '{{ number_format($total->total_credit,2) }}';

            row.innerHTML = `
                    <td></td>
                    <td></td>
                    <td></td>
                    <td style = "font-size:small">Total</td>
                    <td style = "text-align: right;font-size:small">${totalDebit}</td>
                    <td style = "text-align: right;font-size:small">${totalKredit}</td>
            `;
            tableBody.appendChild(row);
            row = document.createElement('tr');
            row.innerHTML = `<td style = "font-size:small" colspan="6">Note: ${note}</td>`;
            tableBody.appendChild(row);
        }
        window.onload = populateTable;

        function printDoc() {
            window.print();
            window.onafterprint = function () {
                window.close();
            }
            window.onfocus = function () { 
                setTimeout(function () { 
                    window.close(); 
                }, 200); 
            }
        }

    </script>
</body>
</html>