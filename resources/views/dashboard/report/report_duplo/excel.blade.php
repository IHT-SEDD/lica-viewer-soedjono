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
            </table>

            <br>

            <table style="border: 1px solid black; margin: 5px; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">No</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Date</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">No Lab</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Patient Name</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Medrec</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Test Name</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Analyzer</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Result</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Analyzer Duplo</th>
                        <th style="text-align: center; border: 1px solid black; border-collapse: collapse;">Result Duplo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $index = 1;
                        foreach ($duploData as $key => $data) {
                    ?>

                    <tr>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $index }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ date('d/m/Y', strtotime($data->input_time)) }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->no_lab }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->patient_name }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->patient_medrec }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->test_name }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->analyzer }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->result }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->analyzer_duplo }}</td>
                        <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $data->result_duplo }}</td>
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
                    <td style="text-align: center; border: 1px solid black; ">Jumlah pemeriksaan</td>
                    <td style="text-align: center; border: 1px solid black; ">Persentase Duplo</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                <td style="text-align: center; border: 1px solid black; ">{{ $total_test }}</td>
                <td style="text-align: center; border: 1px solid black; ">{{ $duplo_percentage }}</td>
                </tr>
            </tbody>
        </table>
    </body>
</html>