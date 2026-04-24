<!DOCTYPE html>
<html>
    <body>
        <div>
            <table>
                <tr>
                    <td>Nama Institusi</td>
                    <td> : </td>
                    <td> Laboratorium Klinik RST Soedjono Magelang</td>
                </tr>
                <tr>
                    <td >Periode Tanggal</td>
                    <td > : </td>
                    <td > {{ $startDate }} - {{ $endDate }} </td>
                </tr>
                <tr>
                    <td>Nama Asuransi</td>
                    <td>:</td>
                    <td>{{ $insurance }}</td>
                </tr>
            </table>

            <br>

            <table style="border: 1px solid black; margin: 5px; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">No</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Tanggal</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Pasien</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Rekam Medik</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">No LAB</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Asuransi</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Nama Tes</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $index = 1;
                        foreach ($billingData as $key => $data) {
                    ?>

                    <tr>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $index }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ date('d/m/Y', strtotime($data->created_time)) }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->patient_name }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->patient_medrec }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->no_lab }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->insurance_name }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->combined_tests }}</td>
                        <td style="text-align: center; border:1px solid black; border-collapse: collapse;">
                            <?php
                                $prices = explode(",", $data->total_prices);
                                $total = array_sum($prices);
                                echo "Rp " . number_format($total, 0, ",", ".");
                            ?>
                        </td>
                    </tr>

                    <?php
                        $index++;
                        }
                    ?>
                </tbody>
            </table>
        </div>

        <table  id="tb_result" style="border: 1px solid black; margin-top: 15px; margin-left:0px; border-collapse: collapse; width:50%;">
            <thead>
                <tr>
                    <td style="text-align: center; border: 1px solid black; ">Jumlah Pasien</td>
                    <td style="text-align: center; border: 1px solid black; ">Total Pendapatan</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; border: 1px solid black;">{{ $total_patients }}</td>
                    <td style="text-align: center; border: 1px solid black;">
                        <?php
                            echo "Rp " . number_format($total_incomes, 0, ",", ".");
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>