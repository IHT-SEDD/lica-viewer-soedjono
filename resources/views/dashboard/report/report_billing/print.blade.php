<!DOCTYPE html>
<html>

<head>
    <title>Laporan Transaksi Nota Laboratorium</title>
    <style type="text/css">
        body {
            font-family: "Arial", sans-serif;
            font-size: 13px;
        }

        table {
            width: 100%;
            margin: 0 auto;
        }

        tr th {
            background: #eee;
            border: 1px solid;
        }

        .border-bottom {
            border-bottom: 1px solid black;
        }

        /* Wrap text untuk kolom Nama Tes */
        .wrap-text {
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }

        @media print {
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>

<body>

    <div>
        <h3>LAPORAN TRANSAKSI NOTA LABORATORIUM</h3>
        <table>
            <tr>
                <td width="15%">Nama Institusi</td>
                <td width="1%">:</td>
                <td width="84%">Laboratorium Klinik RST Soedjono</td>
            </tr>
            <tr>
                <td width="15%">Periode Tanggal</td>
                <td width="1%">:</td>
                <td width="84%">{{ $startDate }} - {{ $endDate }}</td>
            </tr>
            <tr>
                <td>Nama Asuransi</td>
                <td>:</td>
                <td>{{ $insurance }}</td>
            </tr>
        </table>

        <br>

        <table id="tb_result" style="border:1px solid black;margin:5px;border-collapse:collapse; table-layout: fixed;">
            <thead>
                <tr>
                    <th class="border-bottom" style="text-align: center; width: 2%;">No</th>
                    <th class="border-bottom" style="text-align: center; width: 10%;">Tanggal</th>
                    <th class="border-bottom" style="text-align: start; width: 20%;">Pasien</th>
                    <th class="border-bottom" style="text-align: center; width: 10%;">Rekam Medik</th>
                    <th class="border-bottom" style="text-align: center; width: 10%;">No LAB</th>
                    <th class="border-bottom" style="text-align: start; width: 20%;">Asuransi</th>
                    <th class="border-bottom" style="text-align: start; width: 28%;">Nama Tes</th>
                    <th class="border-bottom" style="text-align: center; width: 10%;">Harga</th>
                </tr>
            </thead>

            <tbody>
                @php $index = 1; @endphp
                @foreach($billingData as $data)
                <tr>
                    <td style="text-align: center; border:1px solid black;">{{ $index }}</td>
                    <td style="text-align: center; border:1px solid black;">
                        {{ date('d/m/Y', strtotime($data->created_time)) }}
                    </td>
                    <td style="text-align: start; border:1px solid black;">{{ $data->patient_name }}</td>
                    <td style="text-align: center; border:1px solid black;">{{ $data->patient_medrec }}</td>
                    <td style="text-align: center; border:1px solid black;">{{ $data->no_lab }}</td>
                    <td style="text-align: start; border:1px solid black;">{{ $data->insurance_name }}</td>
                    <td class="wrap-text" style="text-align: start; border:1px solid black;">
                        {{ $data->combined_tests }}
                    </td>
                    <td style="text-align: center; border:1px solid black;">
                        <?php
                            $prices = explode(",", $data->total_prices);
                            $total = array_sum($prices);
                            echo "Rp " . number_format($total, 0, ",", ".");
                        ?>
                    </td>
                </tr>
                @php $index++; @endphp
                @endforeach
            </tbody>
        </table>

</body>
</html>
