<!DOCTYPE html>
<html>
<head></head>
<body>

    <div>
        <table>
            <tr>
                <td>Nama Institusi</td>
                <td> : </td>
                <td>Laboratorium Klinik RST Soedjono Magelang</td>
            </tr>
            <tr>
                <td>Periode Tanggal</td>
                <td> : </td>
                <td>{{ $startDate }} - {{ $endDate }}</td>
            </tr>
            <tr>
                <td>Group name</td>
                <td> : </td>
                <td>{{ $group }}</td>
            </tr>
        </table>

        <br>

        <table style="border:1px solid black; margin:5px; border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">No</th>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">Tanggal</th>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">No Lab</th>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">Test name</th>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">Check in Time</th>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">Analytic Time</th>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">Verify Time</th>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">Validate Time</th>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">Post Time</th>
                    <th style="border:1px solid black; text-align:center; font-weight:bold;">TAT Time</th>
                </tr>
            </thead>
            <tbody>
                @php
                    use Carbon\Carbon;
                    $index = 1;
                    $hasilPerHari = [];
                @endphp

                @foreach ($transactions as $data)
                    @php
                        $checkin = Carbon::parse($data->checkin_time);
                        $analytic = Carbon::parse($data->analytic_time);
                        $verify = Carbon::parse($data->verify_time);
                        $validate = Carbon::parse($data->validate_time);
                        $post = $data->post_time ? Carbon::parse($data->post_time) : null;

                        $date = $checkin->format('d-m-Y');

                        // Hitung durasi (dalam detik)
                        $analytic_sec = $analytic->diffInSeconds($checkin);
                        $verify_sec = $verify->diffInSeconds($checkin);
                        $validate_sec = $validate->diffInSeconds($checkin);
                        $post_sec = $post ? $post->diffInSeconds($checkin) : 0;

                        $analytic_time = gmdate('H:i:s', $analytic_sec);
                        $verify_time = gmdate('H:i:s', $verify_sec);
                        $validate_time = gmdate('H:i:s', $validate_sec);
                        $post_time = $post ? gmdate('H:i:s', $post_sec) : '-';

                        $total_raw = $analytic_sec + $verify_sec + $validate_sec + $post_sec;

                        if (!isset($hasilPerHari[$date])) {
                            $hasilPerHari[$date] = ['sum' => 0, 'count' => 0];
                        }

                        $hasilPerHari[$date]['sum'] += $total_raw;
                        $hasilPerHari[$date]['count']++;
                    @endphp

                    <tr>
                        <td style="text-align:center; border:1px solid black;">{{ $index++ }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $date }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $data->no_lab }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $data->test_name }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $checkin->format('d/m/Y H:i:s') }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $analytic->format('d/m/Y H:i:s') }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $verify->format('d/m/Y H:i:s') }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $validate->format('d/m/Y H:i:s') }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $post->format('d/m/Y H:i:s') }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $post_time }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <br>

        <table style="border:1px solid black; border-collapse:collapse; width:50%;">
            <thead>
                <tr>
                    <th style="border:1px solid black;">No</th>
                    <th style="border:1px solid black;">Tanggal</th>
                    <th style="border:1px solid black;">RATA - RATA TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 1; @endphp
                @foreach ($hasilPerHari as $tanggal => $hasil)
                    @php
                        $avg_seconds = $hasil['sum'] / $hasil['count'];
                        $avg_time = gmdate('H:i:s', $avg_seconds);
                    @endphp
                    <tr>
                        <td style="text-align:center; border:1px solid black;">{{ $i++ }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $tanggal }}</td>
                        <td style="text-align:center; border:1px solid black;">{{ $avg_time }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</body>
</html>
