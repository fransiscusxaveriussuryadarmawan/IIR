<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Library
use Stichoza\GoogleTranslate\GoogleTranslate;
use Sastrawi\Stemmer\StemmerFactory;
use Sastrawi\StopWordRemover\StopWordRemoverFactory;
use Wamania\Snowball\Stemmer\English as EnglishStemmer;

include_once base_path('simple_html_dom.php');

class SearchController extends Controller
{
    public function result(Request $request)
    {
        $author  = $request->input('author');
        $keyword = $request->input('keyword');
        $limit   = $request->input('limit', 5);

        $proxy = '';
        $url_ke_1 = "https://scholar.google.com/scholar?q=" . urlencode($author);

        $result = $this->extract_html($url_ke_1, $proxy);

        $i = 0;
        $data_crawling = [];

        // ===============================
        //         START CRAWLING
        // ===============================
        if ($result['code'] == 200) {

            $html = new \simple_html_dom();
            $html->load($result['message']);

            $cari_profile = $html->find('h4.gs_rt2 a', 0)->href ?? null;

            if ($cari_profile) {

                $url_ke_2 = "https://scholar.google.com/" . $cari_profile;
                $detail = $this->extract_html($url_ke_2, $proxy);

                if ($detail['code'] == 200) {

                    $html2 = new \simple_html_dom();
                    $html2->load($detail['message']);

                    foreach ($html2->find('tr.gsc_a_tr') as $item) {

                        if ($i >= $limit) break;

                        $cari_link = trim(htmlspecialchars_decode(
                            $item->find('a.gsc_a_at', 0)->href ?? "-"
                        ));

                        $url_ke_3 = "https://scholar.google.com" . $cari_link;
                        $hasil = $this->extract_html($url_ke_3, $proxy);

                        // default value
                        $title = "-";
                        $authors = "-";
                        $release_date = "-";
                        $journal = "-";
                        $citations = "-";
                        $link = "-";

                        if ($hasil['code'] == 200) {

                            $html_art = new \simple_html_dom();
                            $html_art->load($hasil['message']);

                            $title        = $html_art->find('a.gsc_oci_title_link', 0)->plaintext ?? "-";
                            $authors      = $html_art->find('div.gsc_oci_value', 0)->plaintext ?? "-";
                            $release_date = $html_art->find('div.gsc_oci_value', 1)->plaintext ?? "-";
                            $journal      = $html_art->find('div.gsc_oci_value', 2)->plaintext ?? "-";

                            $cit = $html_art->find('div[style=margin-bottom:1em] a', 0)->plaintext ?? "-";
                            $cit = str_replace(["Dirujuk", "kali"], "", $cit);
                            $citations = trim($cit);

                            $link = $html_art->find('a.gsc_oci_title_link', 0)->href ?? "-";
                        }

                        // ================================
                        //     PREPROCESSING + SIMILARITY
                        // ================================
                        $prep_keyword = implode(" ", $this->preprocessing($keyword));
                        $prep_title   = implode(" ", $this->preprocessing($title));

                        $similarity_score = $this->calculateSimilarity($prep_keyword, $prep_title);

                        if ($similarity_score > 0) {
                            $data_crawling[] = [
                                "title" => $title,
                                "authors" => $authors,
                                "release_date" => $release_date,
                                "journal_name" => $journal,
                                "citations" => $citations,
                                "link" => $link,
                                "similarity" => $similarity_score,
                                "preprocessed_title" => $this->preprocessing($title)
                            ];
                        }

                        $i++;
                    }
                }
            }
        }

        // SORTING (DESC)
        usort($data_crawling, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return view('result', compact('author', 'keyword', 'limit', 'data_crawling'));
    }


    // ==================================================
    //                PREPROCESSING LENGKAP
    // ==================================================
    private function preprocessing($text)
    {
        // 1. Cleaning
        $clean = strtolower($text);
        $clean = preg_replace('/https?:\/\/\S+/', '', $clean);
        $clean = preg_replace('/[^a-zA-Z\s]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        if ($clean == "") return [];

        // 2. DETEKSI BAHASA
        try {
            $tr = new GoogleTranslate();
            $tr->setSource();  // auto detect
            $tr->setTarget('en');
            $tr->translate($clean);
            $lang = $tr->getLastDetectedSource();  // "id" / "en"
        } catch (\Exception $e) {
            $lang = "en";
        }

        // fallback jika Google gagal
        if (!in_array($lang, ['id', 'en'])) {
            $lang = "en";
        }

        $words = explode(" ", $clean);

        // ==================================================
        //       INDONESIA → SASTRAWI
        // ==================================================
        if ($lang == "id") {

            $stemFactory = new StemmerFactory();
            $stemmer = $stemFactory->createStemmer();

            $stopFactory = new StopWordRemoverFactory();
            $stopword = $stopFactory->createStopWordRemover();

            $clean_no_stop = $stopword->remove($clean);
            $stem = $stemmer->stem($clean_no_stop);

            return explode(" ", $stem);
        }

        // ==================================================
        //       INGGRIS → PORTER STEMMER
        // ==================================================
        $stemmer = new EnglishStemmer();

        // load stopwords english
        $path = storage_path('english_stopwords.txt');

        if (!file_exists($path)) {
            return $words; // fallback jika file tidak ada
        }

        $stopwords = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $filtered = array_filter($words, function ($w) use ($stopwords) {
            return !in_array($w, $stopwords) && strlen($w) > 2;
        });

        $stemmed = array_map(fn($w) => $stemmer->stem($w), $filtered);

        return array_values($stemmed);
    }


    // ==================================================
    //                 COSINE SIMILARITY
    // ==================================================
    private function calculateSimilarity($str1, $str2)
    {
        $tokens1 = array_count_values(explode(" ", $str1));
        $tokens2 = array_count_values(explode(" ", $str2));

        $vocab = array_unique(array_merge(array_keys($tokens1), array_keys($tokens2)));

        $dot = 0; $mag1 = 0; $mag2 = 0;

        foreach ($vocab as $w) {
            $v1 = $tokens1[$w] ?? 0;
            $v2 = $tokens2[$w] ?? 0;

            $dot += $v1 * $v2;
            $mag1 += $v1 * $v1;
            $mag2 += $v2 * $v2;
        }

        if ($mag1 == 0 || $mag2 == 0) return 0;

        return number_format($dot / (sqrt($mag1) * sqrt($mag2)), 4);
    }


    // ==================================================
    //                 EXTRACT HTML
    // ==================================================
    function extract_html($url, $proxy)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_CONNECTTIMEOUT => 10
        ]);

        $content = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return [
            'code' => $code,
            'status' => $content !== false,
            'message' => $content ?: "CURL ERROR"
        ];
    }
}
