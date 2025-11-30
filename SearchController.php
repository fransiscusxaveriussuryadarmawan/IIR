<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// use Phpml\FeatureExtraction\TokenCountVectorizer;
// use Phpml\Tokenization\WhitespaceTokenizer;
// use Phpml\FeatureExtraction\TfIdfTransformer;
// use Phpml\Math\Distance\Minkowski;
// use Phpml\Math\Distance\Canberra;

include_once base_path('simple_html_dom.php');

class SearchController extends Controller
{
    public function result(Request $request)
    {
        $author = $request->input('author');
        $keyword = $request->input('keyword');
        // $method  = $request->input('method');
        $limit = $request->input('limit', 5);

        $proxy = '';
        $url_ke_1 = "https://scholar.google.com/scholar?q=" . urlencode($author);

        $result = $this->extract_html($url_ke_1, $proxy);

        $i = 0;
        $data_crawling = [];
        // $sample_data   = [];

        // Di Halaman Pencarian
        if ($result['code'] == 200) {

            $html = new \simple_html_dom();
            $html->load($result['message']);

            $cari_profile = $html->find('h4.gs_rt2 a', 0)->href ?? "-";

            $url_ke_2 = "https://scholar.google.com/" . $cari_profile;

            $detail = $this->extract_html($url_ke_2, $proxy);

            // Dapat Halaman Profile
            if ($detail['code'] == 200) {

                $html = new \simple_html_dom();
                $html->load($detail['message']);

                foreach ($html->find('tr.gsc_a_tr') as $item) {

                    if ($i >= $limit) break;

                    $cari_link = trim(htmlspecialchars_decode(
                        $item->find('a.gsc_a_at', 0)->href ?? "-"
                    ));
                    $url_ke_3 = "https://scholar.google.com" . $cari_link;
                    $hasil = $this->extract_html($url_ke_3, $proxy);

                    // Set default values agar tidak error jika crawling gagal
                    $title = "-";
                    $authors = "-";
                    $release_date = "-";
                    $journal = "-";
                    $citations = "-";
                    $link = "-";

                    if ($hasil['code'] == 200) {    

                        $html_art = new \simple_html_dom(); // Gunakan variabel baru agar aman
                        $html_art->load($hasil['message']);

                        $title = $html_art->find('a.gsc_oci_title_link', 0)->plaintext ?? "-";

                        $authors = $html_art->find('div.gsc_oci_value', 0)->plaintext ?? "-";

                        $release_date = $html_art->find('div.gsc_oci_value', 1)->plaintext ?? "-";

                        $journal = $html_art->find('div.gsc_oci_value', 2)->plaintext ?? "-";

                        $citations = $html_art->find('div[style=margin-bottom:1em] a', 0)->plaintext ?? "-";
                        $clean = str_replace("Dirujuk", "", $citations);
                        $clean = str_replace("kali", "", $clean);
                        $citations = trim($clean);

                        $link = $html_art->find('a.gsc_oci_title_link', 0)->href ?? "-";
                    }

                    //cosine similarity
                    $similarity_score = $this->calculateSimilarity($keyword, $title);
                    
                    if($similarity_score >0 ){
                        $data_crawling[] = [
                        "title" => $title,
                        "authors" => $authors,
                        "release_date" => $release_date,
                        "journal_name" => $journal,
                        "citations" => $citations,
                        "link" => $link,
                        "similarity" => $similarity_score // Masukkan nilai similaritas
                    ];
                    }
                    $i++;
                }
            }

            // $sample_data[] = $keyword;

            // $tf = new TokenCountVectorizer(new WhitespaceTokenizer());
            // $tf->fit($sample_data);
            // $tf->transform($sample_data);

            // $tfidf = new TfIdfTransformer($sample_data);
            // $tfidf->transform($sample_data);

            // $total = count($sample_data);

            // if ($method == 'Minkowski') {
            //     $minkowski = new Minkowski(count(explode(" ", $keyword)));
            //     $minkowski = new Minkowski(count(explode(" ", $keyword)));
            //     for ($i = 0; $i < $total - 1; $i++) {
            //         $dist = $minkowski->distance($sample_data[$i], $sample_data[$total - 1]);
            //         $data_crawling[$i]['similarity'] = $dist;
            //     }
            // } else {
            //     $canberra = new Canberra();
            //     for ($i = 0; $i < $total - 1; $i++) {
            //         $dist = $canberra->distance($sample_data[$i], $sample_data[$total - 1]);
            //         $data_crawling[$i]['similarity'] = $dist;
            //     }
            // }

            // // Sorting ascending
            // usort($data_crawling, function ($a, $b) {
            //     return $a['similarity'] <=> $b['similarity'];
            // });
        }

        // --- SORTING (DESCENDING) BERDASARKAN SKOR TERTINGGI ---
        usort($data_crawling, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return view('result', [
            'author' => $author,
            'keyword' => $keyword,
            'limit' => $limit,
            'data_crawling' => $data_crawling
        ]);
    }

    function extract_html($url, $proxy)
    {

        $response = array();

        $response['code'] = '';

        $response['message'] = '';

        $response['status'] = false;

        $agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1';

        // Some websites require referrer

        $host = parse_url($url, PHP_URL_HOST);

        $scheme = parse_url($url, PHP_URL_SCHEME);

        $referrer = $scheme . '://' . $host;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_HEADER, false);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($curl, CURLOPT_URL, $url);

        curl_setopt($curl, CURLOPT_PROXY, $proxy);

        curl_setopt($curl, CURLOPT_USERAGENT, $agent);

        curl_setopt($curl, CURLOPT_REFERER, $referrer);

        curl_setopt($curl, CURLOPT_COOKIESESSION, 0);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); // Timeout dinaikkan sedikit biar aman

        // allow to crawl https webpages

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        // the download speed must be at least 1 byte per second

        curl_setopt($curl, CURLOPT_LOW_SPEED_LIMIT, 1);

        // if the download speed is below 1 byte per second for more than 30 seconds curl will give up

        curl_setopt($curl, CURLOPT_LOW_SPEED_TIME, 30);

        $content = curl_exec($curl);

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $response['code'] = $code;

        if ($content === false) {

            $response['status'] = false;

            $response['message'] = curl_error($curl);
        } else {

            $response['status'] = true;

            $response['message'] = $content;
        }

        curl_close($curl);

        return $response;
    }

    // --- FUNGSI TAMBAHAN: Preprocessing ---
    private function preprocessing($text)
    {
        $text = strtolower($text); // Huruf kecil
        $text = preg_replace("/[^a-z0-9\s]/", "", $text); // Hapus simbol
        $text = preg_replace('/\s+/', ' ', trim($text)); // Hapus spasi berlebih
        return $text;
    }

    // --- FUNGSI TAMBAHAN: Rumus Cosine Similarity ---
    private function calculateSimilarity($str1, $str2)
    {
        // 1. Bersihkan teks
        $text1 = $this->preprocessing($str1);
        $text2 = $this->preprocessing($str2);

        // 2. Tokenisasi & Hitung Frekuensi Kata (Term Frequency)
        $tokens1 = array_count_values(explode(" ", $text1));
        $tokens2 = array_count_values(explode(" ", $text2));

        // 3. Buat Vocabulary Gabungan
        $vocabulary = array_unique(array_merge(array_keys($tokens1), array_keys($tokens2)));

        // 4. Hitung Dot Product dan Magnitude
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        foreach ($vocabulary as $word) {
            $val1 = $tokens1[$word] ?? 0;
            $val2 = $tokens2[$word] ?? 0;

            $dotProduct += $val1 * $val2;
            $magnitude1 += pow($val1, 2);
            $magnitude2 += pow($val2, 2);
        }

        // 5. Akar kuadrat Magnitude
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        // 6. Hitung Cosine (Hindari pembagian nol)
        if (($magnitude1 * $magnitude2) == 0) {
            return 0;
        }

        return number_format($dotProduct / ($magnitude1 * $magnitude2), 4);
    }
}