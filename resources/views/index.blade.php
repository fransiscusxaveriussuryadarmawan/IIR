<h2 style="text-align:center; font-weight:bold;">
    PENCARIAN DATA ARTIKEL ILMIAH
</h2>

<br>

<form action="/result" method="POST">
    @csrf

    <b>Input Nama Penulis :</b>
    <input
        type="text"
        name="author"
        placeholder="joko siswantoro"
        style="width:250px; margin-left:10px;">
    <br><br>

    <b>Input Keyword Artikel :</b>
    <input
        type="text"
        name="keyword"
        placeholder="modeling optimization"
        style="width:250px; margin-left:10px;">
    <br><br>

    <b>Jumlah data = </b>
    <input
        type="number"
        name="limit"
        value="5"
        min="1"
        style="width:80px; margin-left:10px;">
    <br><br>

    <button type="submit" style="padding:5px 15px;">
        Search
    </button>
</form>