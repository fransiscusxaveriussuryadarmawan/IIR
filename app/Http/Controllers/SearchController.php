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

                    if ($hasil['code'] == 200) {

                        $html = new \simple_html_dom();
                        $html->load($hasil['message']);

                        $title = $html->find('a.gsc_oci_title_link', 0)->plaintext ?? "-";

                        $authors = $html->find('div.gsc_oci_value', 0)->plaintext ?? "-";

                        $release_date = $html->find('div.gsc_oci_value', 1)->plaintext ?? "-";

                        $journal = $html->find('div.gsc_oci_value', 2)->plaintext ?? "-";

                        $citations = $html->find('div[style=margin-bottom:1em] a', 0)->plaintext ?? "-";
                        $clean = str_replace("Dirujuk", "", $citations);
                        $clean = str_replace("kali", "", $clean);
                        $citations = trim($clean);

                        $link = $html->find('a.gsc_oci_title_link', 0)->href ?? "-";
                    }

                    $data_crawling[] = [
                        "title" => $title,
                        "authors" => $authors,
                        "release_date" => $release_date,
                        "journal_name" => $journal,
                        "citations" => $citations,
                        "link" => $link,
                    ];

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

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

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
}
