<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Library untuk DETEKSI BAHASA & STEMMING & STOPWORD REMOVAL
use Stichoza\GoogleTranslate\GoogleTranslate;
use Sastrawi\Stemmer\StemmerFactory;
use Sastrawi\StopWordRemover\StopWordRemoverFactory;
use Wamania\Snowball\Stemmer\English as EnglishStemmer;

//! Library untuk FEATURE WEIGHTING & PREPROCESSING (PHP-ML)
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Tokenization\WhitespaceTokenizer;
use Phpml\FeatureExtraction\StopWords\English as PhpmlEnglishStopwords;

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

        // (1) START CRAWLING
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

                        //! Versinya bryan (SIMILARITY TF-Raw + COSINE, TANPA TF-IDF)
                        //! Disimpan sebagai referensi implementasi awal (tidak dipakai lagi).
                        // ================================
                        //     PREPROCESSING + SIMILARITY
                        // ================================
                        // $prep_keyword = implode(" ", $this->preprocessing($keyword));
                        // $prep_title   = implode(" ", $this->preprocessing($title));
                        //
                        // $similarity_score = $this->calculateSimilarity($prep_keyword, $prep_title);
                        //
                        // if ($similarity_score > 0) {
                        //     $data_crawling[] = [
                        //         "title" => $title,
                        //         "authors" => $authors,
                        //         "release_date" => $release_date,
                        //         "journal_name" => $journal,
                        //         "citations" => $citations,
                        //         "link" => $link,
                        //         "similarity" => $similarity_score,
                        //         "preprocessed_title" => $this->preprocessing($title)
                        //     ];
                        // }

                        //! Versinya darius (PREPROCESSING SAJA di tahap CRAWLING)
                        //! Di tahap ini kita hanya simpan token hasil preprocessing judul.

                        // (2) START PREPROCESSING JUDUL
                        $prep_title_tokens = $this->preprocessing($title);

                        if (empty($prep_title_tokens) || $title == "-") {
                            $i++;
                            continue;
                        }

                        $data_crawling[] = [
                            "title" => $title,
                            "authors" => $authors,
                            "release_date" => $release_date,
                            "journal_name" => $journal,
                            "citations" => $citations,
                            "link" => $link,
                            "similarity" => 0,
                            "preprocessed_title" => $prep_title_tokens
                        ];

                        $i++;
                    }
                }
            }
        }

        //! Versinya darius (LANJUTAN)
        // ================================
        //  FEATURE WEIGHTING + SIMILARITY
        //   (TF-IDF + Cosine Coefficient)
        //   → MENGGUNAKAN LIBRARY PHP-ML
        // ================================

        // (2) START PREPROCESSING KEYWORD
        $prep_keyword_tokens = $this->preprocessing($keyword);

        // (3) START FEATURE WEIGHTING
        if (!empty($data_crawling) && !empty($prep_keyword_tokens)) {

            $all_doc_tokens = array_column($data_crawling, 'preprocessed_title');

            // Hitung similarity berbasis TF-IDF (PHP-ML)
            $similarities = $this->calculateTfidfSimilarities(
                $prep_keyword_tokens,
                $all_doc_tokens
            );

            // Masukkan kembali ke array utama
            foreach ($data_crawling as $idx => &$row) {
                $row['similarity'] = $similarities[$idx] ?? 0;
            }

            // Optionally: buang artikel yang similarity 0
            $data_crawling = array_values(array_filter($data_crawling, function ($row) {
                return $row['similarity'] > 0;
            }));
        }

        // SORTING (DESC)
        usort($data_crawling, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return view('result', [
            'author' => $author,
            'keyword' => $keyword,
            'limit' => $limit,
            'data_crawling' => $data_crawling,
            'prep_keyword_tokens' => $prep_keyword_tokens
        ]);
    }

    // (2) START PREPROCESSING
    private function preprocessing($text)
    {
        // 1. Cleaning
        $clean = strtolower($text);
        $clean = preg_replace('/\bnot\s+(\w+)/', 'not_$1', $clean);
        $clean = preg_replace('/\bno\s+(\w+)/', 'no_$1', $clean);
        $clean = preg_replace('/\bnever\s+(\w+)/', 'never_$1', $clean);

        $clean = preg_replace('/[^a-zA-Z0-9_\-\s]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        if ($clean == "") return [];

        // 2. Deteksi Bahasa (dengan Google Translate)
        try {
            $tr = new GoogleTranslate();
            $tr->setSource();
            $tr->setTarget('en');
            $tr->translate($clean);

            $lang = $tr->getLastDetectedSource();
        } catch (\Exception $e) {
            $lang = "en";
        }

        // Hanya bahasa 'id' atau 'en'
        if (!in_array($lang, ['id', 'en'])) {
            $lang = "en";
        }

        // Tokenized kata
        $words = explode(" ", $clean);

        // Indonesia pakai Sastrawi
        if ($lang == "id") {

            $stemmerFactory = new StemmerFactory();
            $stemmer = $stemmerFactory->createStemmer();

            $stopwordFactory = new StopWordRemoverFactory();
            $stopword = $stopwordFactory->createStopWordRemover();

            $stop = $stopword->remove($clean);
            $stem = $stemmer->stem($stop);

            return array_values(array_filter(explode(" ", $stem)));
        }

        // Inggris pakai Porter + kombinasi stopwords
        $stemmer = new EnglishStemmer();

        // Langsung baca stopwords dari file txt (custom)
        $fileStopwords = [];
        $path = storage_path('english_stopwords.txt');
        if (file_exists($path)) {
            $fileStopwords = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        // Langsung pakai stopwords dari PHP-ML
        $phpmlStop = new PhpmlEnglishStopwords();

        // Filter stopwords gabungan
        $filtered = array_filter($words, function ($w) use ($phpmlStop, $fileStopwords) {
            return !$phpmlStop->isStopWord($w)
                && !in_array($w, $fileStopwords)
                && strlen($w) > 2;
        });

        // Filter stemming
        $stemmed = array_map(function ($w) use ($stemmer) {
            return $stemmer->stem($w);
        }, $filtered);

        $stemmed = array_filter($stemmed, fn($w) => trim($w) !== "");

        return array_values($stemmed);
    }

    //! Versinya bryan
    // ==================================================
    //              COSINE SIMILARITY (TF-Raw)
    //                 (IMPLEMENTASI AWAL)
    // ==================================================
    // private function calculateSimilarity($str1, $str2)
    // {
    //     $tokens1 = array_count_values(explode(" ", $str1));
    //     $tokens2 = array_count_values(explode(" ", $str2));
    //
    //     $vocab = array_unique(array_merge(array_keys($tokens1), array_keys($tokens2)));
    //
    //     $dot = 0; $mag1 = 0; $mag2 = 0;
    //
    //     foreach ($vocab as $w) {
    //         $v1 = $tokens1[$w] ?? 0;
    //         $v2 = $tokens2[$w] ?? 0;
    //
    //         $dot += $v1 * $v2;
    //         $mag1 += $v1 * $v1;
    //         $mag2 += $v2 * $v2;
    //     }
    //
    //     if ($mag1 == 0 || $mag2 == 0) return 0;
    //
    //     return number_format($dot / (sqrt($mag1) * sqrt($mag2)), 4);
    // }

    //! Versinya darius (MANUAL TF-IDF + COSINE)
    //! Disimpan sebagai backup / referensi jika ingin menunjukkan perhitungan manual.
    // ==================================================
    //        FEATURE WEIGHTING (TF-IDF) + COSINE
    //              (IMPLEMENTASI MANUAL)
    // ==================================================
    // private function calculateTfidfSimilaritiesManual(array $queryTokens, array $documentsTokens)
    // {
    //     $N = count($documentsTokens);
    //     if ($N == 0) return [];
    //
    //     // 1. Hitung DF (document frequency)
    //     $df = [];
    //     foreach ($documentsTokens as $tokens) {
    //         $unique = array_unique($tokens);
    //         foreach ($unique as $term) {
    //             if (!isset($df[$term])) $df[$term] = 0;
    //             $df[$term]++;
    //         }
    //     }
    //
    //     // 2. Hitung IDF dengan smoothing kecil
    //     $idf = [];
    //     foreach ($df as $term => $df_t) {
    //         // +1 smoothing supaya tidak dibagi 0, +1 di log supaya tetap positif
    //         $idf[$term] = log(($N + 1) / ($df_t + 1)) + 1;
    //     }
    //
    //     // 3. TF query
    //     $tf_q = array_count_values($queryTokens);
    //
    //     $similarities = [];
    //
    //     // 4. Untuk setiap dokumen → hitung TF-IDF + cosine dengan query
    //     foreach ($documentsTokens as $idx => $docTokens) {
    //
    //         $tf_d = array_count_values($docTokens);
    //
    //         // vocab per pasangan = gabungan term yang muncul di query atau doc
    //         $vocab = array_unique(array_merge(
    //             array_keys($tf_q),
    //             array_keys($tf_d)
    //         ));
    //
    //         $dot = 0; $mag_q = 0; $mag_d = 0;
    //
    //         foreach ($vocab as $term) {
    //             $tf_q_term = $tf_q[$term] ?? 0;
    //             $tf_d_term = $tf_d[$term] ?? 0;
    //
    //             $idf_term = $idf[$term] ?? 0; // jika term hanya muncul di query, idf bisa 0
    //
    //             // bobot TF-IDF
    //             $w_q = $tf_q_term * $idf_term;
    //             $w_d = $tf_d_term * $idf_term;
    //
    //             $dot   += $w_q * $w_d;
    //             $mag_q += $w_q * $w_q;
    //             $mag_d += $w_d * $w_d;
    //         }
    //
    //         if ($mag_q == 0 || $mag_d == 0) {
    //             $similarities[$idx] = 0;
    //         } else {
    //             $similarities[$idx] = round($dot / (sqrt($mag_q) * sqrt($mag_d)), 4);
    //         }
    //     }
    //
    //     return $similarities;
    // }

    private function calculateTfidfSimilarities(array $queryTokens, array $documentsTokens)
    {
        // (3) START FEATURE WEIGHTING
        $queryString = implode(' ', $queryTokens);
        $docStrings  = array_map(function ($tokens) {
            return implode(' ', $tokens);
        }, $documentsTokens);

        $corpus = array_merge([$queryString], $docStrings);

        if (trim($queryString) === '' || empty($docStrings)) {
            return [];
        }

        $vectorizer = new TokenCountVectorizer(new WhitespaceTokenizer());
        $vectorizer->fit($corpus);
        $vectorizer->transform($corpus);

        $tfIdf = new TfIdfTransformer($corpus);
        $tfIdf->transform($corpus);

        // (4) START SIMILARITY CALCULATION
        $queryVector = $corpus[0];
        $docVectors  = array_slice($corpus, 1);

        $similarities = [];
        foreach ($docVectors as $idx => $docVector) {
            $similarities[$idx] = $this->cosineSimilarity($queryVector, $docVector);
        }

        return $similarities;
    }

    // COSINE SIMILARITY HELPER (PHP-ML)
    private function cosineSimilarity(array $vecA, array $vecB)
    {
        $dot  = 0.0;
        $magA = 0.0;
        $magB = 0.0;

        $len = max(count($vecA), count($vecB));

        for ($i = 0; $i < $len; $i++) {
            $a = $vecA[$i] ?? 0.0;
            $b = $vecB[$i] ?? 0.0;

            $dot  += $a * $b;
            $magA += $a * $a;
            $magB += $b * $b;
        }

        if ($magA == 0.0 || $magB == 0.0) {
            return 0.0;
        }

        return round($dot / (sqrt($magA) * sqrt($magB)), 4);
    }

    // EXTRACT HTML
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
