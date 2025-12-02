<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Hasil Pencarian Artikel</title>

    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #d9e8ff, #eef3f9);
            padding: 30px;
        }

        .back {
            display: inline-block;
            color: #2980b9;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .header-box {
            background: white;
            padding: 22px 28px;
            border-radius: 18px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            margin-bottom: 25px;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            margin: 0;
            color: #2c3e50;
            font-weight: 700;
        }

        p {
            color: #34495e;
            font-size: 15px;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            animation: fadeIn 0.7s ease;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #34495e;
            color: white;
            padding: 10px;
            font-weight: bold;
        }

        td {
            padding: 10px;
            background: #fafbfd;
            border-bottom: 1px solid #e1e7ef;
        }

        tr:hover td {
            background: #f1f5fb;
        }

        .btn-pre {
            background: #3498db;
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-pre:hover {
            background: #217dbb;
        }

        .preprocess-box {
            margin-top: 8px;
            background: #eef2f7;
            border-left: 4px solid #3498db;
            padding: 8px;
            font-size: 13px;
            border-radius: 6px;
            display: none;
        }

        .sim-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: bold;
            color: white;
            font-size: 13px;
            display: inline-block;
        }

        .sim-high {
            background: #27ae60;
        }

        .sim-mid {
            background: #f1c40f;
            color: #333;
        }

        .sim-low {
            background: #e74c3c;
        }
    </style>

    <script>
        function togglePreprocess(id) {
            let box = document.getElementById("pre_" + id);
            if (box.style.display === "none" || box.style.display === "") {
                box.style.display = "block";
            } else {
                box.style.display = "none";
            }
        }
    </script>

</head>

<body>

    <a href="/" class="back">← Kembali ke Pencarian</a>

    <div class="header-box">
        <h2>Hasil Pencarian Artikel</h2>
        <p><b>Nama Penulis:</b> {{ $author }}</p>
        <p><b>Keyword:</b> {{ $keyword }}</p>
        <p><b>Jumlah Data:</b> {{ $limit }}</p>

        <button class="btn-pre" onclick="togglePreprocess('keyword')">
            Lihat Preprocessing Keyword ▼
        </button>

        <div class="preprocess-box" id="pre_keyword">
            {{ implode(', ', $prep_keyword_tokens) }}
        </div>
    </div>

    <div class="table-container">

        @if(count($data_crawling) == 0)

        <p><i>Tidak ada data ditemukan.</i></p>

        @else

        <table>
            <tr>
                <th>Judul Artikel</th>
                <th>Penulis</th>
                <th>Jurnal</th>
                <th>Sitasi</th>
                <th>Similarity</th>
                <th>Link</th>
            </tr>

            @foreach($data_crawling as $index => $row)
            <tr>
                <td>
                    <b>{{ $row['title'] }}</b><br>

                    <button class="btn-pre" onclick="togglePreprocess({{ $index }})">
                        Lihat Preprocessing ▼
                    </button>

                    <div class="preprocess-box" id="pre_{{ $index }}">
                        {{ implode(', ', $row['preprocessed_title']) }}
                    </div>
                </td>

                <td>{{ $row['authors'] }}</td>
                <td>{{ $row['journal_name'] }}</td>
                <td>{{ $row['citations'] }}</td>

                <td>
                    @php
                    $sim = $row['similarity'];
                    if ($sim >= 0.6) $cls = "sim-high";
                    elseif ($sim >= 0.3) $cls = "sim-mid";
                    else $cls = "sim-low";
                    @endphp

                    <span class="sim-badge {{ $cls }}">
                        {{ $sim }}
                    </span>
                </td>

                <td>
                    <a href="{{ $row['link'] }}" target="_blank">Buka</a>
                </td>
            </tr>
            @endforeach
        </table>

        @endif

    </div>

</body>

</html>