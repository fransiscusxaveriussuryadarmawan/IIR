<h3>SEARCHING DATA</h3>

<form action="/result" method="POST">
    @csrf

    <b>Keyword :</b>
    <input type="text" name="keyword"><br><br>

    <b>Similarity Method :</b>
    <input type="radio" name="method" value="Minkowski" checked> Minkowski
    <input type="radio" name="method" value="Canberra"> Canberra<br><br>

    <button type="submit">Search</button>
</form>