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
            <td> {{ $startDate }} - {{ $endDate }} </td>
        </tr>
    </table>

    <br>
    <table>
        <thead>
            <tr>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">No</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Tanggal</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Pasien</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Rekam Medis</th>
                <th class="border-bottom" style="text-align: center; border: 1px solid black; border-collapse: collapse;">Test</th>
            </tr>
        </thead>
        <tbody>
            @php
            $index = 1;
            @endphp
            @foreach ($transactions as $testDetail)
                <tr>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $index }}</td>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ date('d/m/Y', strtotime($testDetail->created_time)) }}</td>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $testDetail->patient_name }}</td>
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">{{ $testDetail->patient_medrec }}</td>      
                    <td style="text-align: center; border: 1px solid black; border-collapse: collapse;">
                        {{ $testDetail->test_names }}
                    </td>
                </tr>
                @php
                $index++;
                @endphp
            @endforeach
        </tbody>
    </table>
</body>
</html>
