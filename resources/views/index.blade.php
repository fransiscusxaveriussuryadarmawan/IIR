<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pencarian Artikel Ilmiah</title>

<style>
    body {
        font-family: "Segoe UI", Arial, sans-serif;
        background: #eef3f9;
        display: flex;
        justify-content: center;
        padding-top: 50px;
    }

    .container {
        background: white;
        width: 500px;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        animation: fadeIn 0.6s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    h2 {
        text-align: center;
        font-weight: bold;
        color: #2c3e50;
    }

    label {
        font-weight: bold;
        margin-bottom: 5px;
        display: block;
        color: #34495e;
    }

    input {
        width: 100%;
        padding: 10px;
        border: 2px solid #dce3eb;
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 15px;
        transition: 0.3s;
    }

    input:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 5px rgba(52,152,219,0.4);
    }

    .btn-search {
        width: 100%;
        padding: 12px;
        background: #3498db;
        border: none;
        border-radius: 8px;
        color: white;
        font-size: 16px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
    }

    .btn-search:hover {
        background: #217dbb;
    }

</style>
</head>
<body>

<div class="container">

    <h2>PENCARIAN DATA ARTIKEL ILMIAH</h2>
    <br>

    <form action="/result" method="POST">
        @csrf

        <label>Nama Penulis</label>
        <input type="text" name="author" placeholder="contoh: joko siswantoro">

        <label>Keyword Artikel</label>
        <input type="text" name="keyword" placeholder="contoh: modeling optimization">

        <label>Jumlah Data</label>
        <input type="number" name="limit" value="5" min="1">

        <button class="btn-search" type="submit">
            🔍 Cari Artikel
        </button>
    </form>

</div>

</body>
</html>
