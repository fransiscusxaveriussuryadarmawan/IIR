<a href="/test_1"><b>
		< Back to Home</b></a>

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

	@foreach($data_crawling as $row)
	<tr>
		<td>{{ $row['title'] }}</td>
		<td>{{ $row['authors'] ?? '-' }}</td>
		<td>{{ $row['release_date'] ?? '-' }}</td>
		<td>{{ $row['journal_name'] ?? '-' }}</td>
		<td>{{ $row['citations'] ?? '-' }}</td>
		<td>
			@if(isset($row['link']))
			<a href="{{ $row['link'] }}" target="_blank">{{ $row['link'] }}</a>
			@else
			-
			@endif
		</td>
		<td>{{ $row['similarity'] ?? '-' }}</td>
	</tr>
	@endforeach

</table>

@endif