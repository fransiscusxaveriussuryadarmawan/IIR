<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

//! Library untuk DETEKSI BAHASA & STEMMING & STOPWORD REMOVAL
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

        // (2) START PREPROCESSING KEYWORD
        $prep_keyword_tokens = $this->preprocessing($keyword);

        // (3) START FEATURE WEIGHTING
        if (!empty($data_crawling) && !empty($prep_keyword_tokens)) {

            $all_doc_tokens = array_column($data_crawling, 'preprocessed_title');

            $similarities = $this->calculateTfidfSimilarities(
                $prep_keyword_tokens,
                $all_doc_tokens
            );

            foreach ($data_crawling as $idx => &$row) {
                $row['similarity'] = $similarities[$idx] ?? 0;
            }

            $data_crawling = array_values(array_filter($data_crawling, function ($row) {
                return $row['similarity'] > 0;
            }));
        }

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
        $clean = strtolower($text);
        $clean = preg_replace('/\bnot\s+(\w+)/', 'not_$1', $clean);
        $clean = preg_replace('/\bno\s+(\w+)/', 'no_$1', $clean);
        $clean = preg_replace('/\bnever\s+(\w+)/', 'never_$1', $clean);

        $clean = preg_replace('/[^a-zA-Z0-9_\-\s]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        if ($clean == "") return [];

        try {
            $tr = new GoogleTranslate();
            $tr->setSource();
            $tr->setTarget('en');
            $tr->translate($clean);

            $lang = $tr->getLastDetectedSource();
        } catch (\Exception $e) {
            $lang = "en";
        }

        if (!in_array($lang, ['id', 'en'])) {
            $lang = "en";
        }

        $words = explode(" ", $clean);

        if ($lang == "id") {

            $stemmerFactory = new StemmerFactory();
            $stemmer = $stemmerFactory->createStemmer();

            $stopwordFactory = new StopWordRemoverFactory();
            $stopword = $stopwordFactory->createStopWordRemover();

            $stop = $stopword->remove($clean);
            $stem = $stemmer->stem($stop);

            return array_values(array_filter(explode(" ", $stem)));
        }

        $stemmer = new EnglishStemmer();

        $fileStopwords = [];
        $path = storage_path('english_stopwords.txt');
        if (file_exists($path)) {
            $fileStopwords = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        $phpmlStop = new PhpmlEnglishStopwords();

        $filtered = array_filter($words, function ($w) use ($phpmlStop, $fileStopwords) {
            return !$phpmlStop->isStopWord($w)
                && !in_array($w, $fileStopwords)
                && strlen($w) > 2;
        });

        $stemmed = array_map(function ($w) use ($stemmer) {
            return $stemmer->stem($w);
        }, $filtered);

        $stemmed = array_filter($stemmed, fn($w) => trim($w) !== "");

        return array_values($stemmed);
    }

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

    // COSINE SIMILARITY HELPER
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
