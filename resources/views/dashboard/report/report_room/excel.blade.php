<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <table>
        <tr>
            <td>Periode Tanggal</td>
            <td > : </td>
            <td> {{ $tanggalStart }} - {{ $tanggalEnd }} </td>
        </tr>
    </table>

    <br>
    <table>
        <thead>
            <tr>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">No</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Tanggal</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Ruangan</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Tipe</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Pasien</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Medrek</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Jenis Kelamin</th>
            </tr>
        </thead>
        <tbody>
            @php
            $index = 1;
            @endphp
            @foreach ($roomDetails as $roomDetail)
                <tr>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $index }}</td>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ date('d/m/Y', strtotime($roomDetail->created_time)) }}</td>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $roomDetail->room_name }}</td>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                        <?php
                            if ($roomDetail->type == 'rawat_inap') {
                                echo 'Rawat Inap';
                            } else if ($roomDetail->type == 'rawat_jalan') {
                                echo 'Rawat Jalan';
                            } else {
                                echo 'IGD';
                            }
                            ?>
                    </td>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $roomDetail->patient_medrec }}</td>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $roomDetail->patient_name }}</td>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $roomDetail->patient_medrec }}</td>
                </tr>
                @php
                $index++;
                @endphp
            @endforeach
        </tbody>
    </table>
</body>
</html>
