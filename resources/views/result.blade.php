<h3>Search Results</h3>

<p><b>Keyword:</b> {{ $keyword }}</p>
<p><b>Method:</b> {{ $method }}</p>

<br>

<a href="/test_1"><b>
		< Back to Home</b></a>
<br><br>

@if (count($data_crawling) == 0)
<p><i>No data found.</i></p>
@else

<table border="1" cellpadding="8" cellspacing="0">
	<tr>
		<th>Title</th>
		<th>Link</th>
		<th>Preprocessing Result</th>
		<th>Similarity</th>
	</tr>

	@foreach ($data_crawling as $row)
	<tr>
		<td>{{ $row['title'] }}</td>
		<td>
			<a href="{{ $row['link'] }}" target="_blank">{{ $row['link'] }}</a>
		</td>
		<td>{{ $row['processed'] }}</td>
		<td>{{ $row['similarity'] }}</td>
	</tr>
	@endforeach
</table>

@endif