<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hasil Pencarian</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fc;
            padding: 20px;
        }

        a {
            color: #333;
            text-decoration: none;
            font-size: 15px;
        }

        h3 {
            margin-top: 20px;
            color: #2c3e50;
        }

        table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background: #34495e;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background: #f2f4f7;
        }

        button {
            background: #3498db;
            border: none;
            padding: 5px 10px;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }

        button:hover {
            background: #2980b9;
        }

        .preprocess-box {
            background: #eef2f5;
            padding: 8px;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 13px;
            display: none;
        }
    </style>

    <script>
        function togglePreprocess(id) {
            let box = document.getElementById("pre_" + id);
            box.style.display = (box.style.display === "none") ? "block" : "none";
        }
    </script>
</head>
<body>

<a href="/test_1"><b>&lt; Back to Home</b></a>

<br><br>

<p><b>Nama Penulis :</b> {{ $author }}</p>
<p><b>Keyword Artikel :</b> {{ $keyword }}</p>
<p><b>Jumlah data = </b> {{ $limit }}</p>

<br>

<h3>Hasil Pencarian</h3>

@if(count($data_crawling) == 0)

<p><i>Tidak ada data ditemukan.</i></p>

@else

<table border="1" cellpadding="10" cellspacing="0" width="100%">
	<tr>
		<th>Judul Artikel</th>
		<th>Penulis</th>
		<th>Tanggal Rilis</th>
		<th>Nama Jurnal</th>
		<th>Jumlah Sitasi</th>
		<th>Link Jurnal</th>
		<th>Nilai Similaritas</th>
	</tr>

	@foreach($data_crawling as $index => $row)
	<tr>
		<td>
			<b>{{ $row['title'] }}</b>

			<br>

			<button onclick="togglePreprocess({{ $index }})">
				Preprocessing ↓
			</button>

			<div class="preprocess-box" id="pre_{{ $index }}">
				@if(isset($row['preprocessed_title']))
					{{ implode(', ', $row['preprocessed_title']) }}
				@else
					<i>Tidak ada hasil preprocessing.</i>
				@endif
			</div>
		</td>

		<td>{{ $row['authors'] ?? '-' }}</td>
		<td>{{ $row['release_date'] ?? '-' }}</td>
		<td>{{ $row['journal_name'] ?? '-' }}</td>
		<td>{{ $row['citations'] ?? '-' }}</td>

		<td>
			@if(isset($row['link']))
			    <a href="{{ $row['link'] }}" target="_blank">Buka</a>
			@else
			    -
			@endif
		</td>

		<td>{{ $row['similarity'] ?? '-' }}</td>
	</tr>
	@endforeach

</table>

@endif

</body>
</html>
