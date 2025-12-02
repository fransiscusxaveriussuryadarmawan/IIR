<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pencarian Artikel Ilmiah</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #d9e8ff, #eef3f9);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .card {
            width: 100%;
            max-width: 600px;
            background: #ffffff;
            padding: 40px 45px;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            animation: fadeIn 0.7s ease;
        }


        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            text-align: center;
            font-weight: 700;
            color: #2c3e50;
            font-size: 26px;
        }

        p.subtitle {
            text-align: center;
            color: #5d6d7e;
            margin-top: -5px;
            margin-bottom: 25px;
        }

        label {
            font-weight: 600;
            margin-bottom: 6px;
            color: #34495e;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #d0d7e2;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
            transition: 0.3s;
        }

        input:focus {
            border-color: #3498db;
            background: #f4faff;
            outline: none;
            box-shadow: 0 0 6px rgba(52, 152, 219, 0.4);
        }

        .btn-search {
            width: 100%;
            padding: 13px;
            background: #3498db;
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-search:hover {
            background: #217dbb;
        }

        footer {
            margin-top: 25px;
            text-align: center;
            font-size: 13px;
            color: #7f8c8d;
        }
    </style>
</head>

<body>

    <div class="card">

        <h2>Pencarian Artikel Ilmiah</h2>
        <p class="subtitle">From Google Scholar</p>

        <form action="/result" method="POST">
            @csrf

            <label>Nama Penulis</label>
            <input type="text" name="author" placeholder="contoh: joko siswantoro" required>

            <label>Keyword Artikel</label>
            <input type="text" name="keyword" placeholder="contoh: modeling optimization" required>

            <label>Jumlah Data</label>
            <input type="number" name="limit" value="5" min="1" max="20" required>

            <button class="btn-search" type="submit">
                🔍 Cari Artikel
            </button>
        </form>

        <footer>
            IIR PROJECT &copy; 2025
        </footer>

    </div>

</body>

</html>