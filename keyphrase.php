<?php
require_once 'goutte.phar';
use Goutte\Client;
error_reporting(E_ERROR);

$yahoo_appid = 'dj0zaiZpPWdrTXBYUlNzNkFQNiZzPWNvbnN1bWVyc2VjcmV0Jng9Y2Q-';
$urls =  array();
$urls[0] = "http://matsu.teraren.com/blog/";
$urls[1] = "https://github.com/matsubo/";
$urls[2] = "http://news.yahoo.co.jp/";
$urls[3] = "http://headlines.yahoo.co.jp/hl?a=20141030-00000002-natiogeog-sctch";
$urls_valid = false;
$words = "";

function escapestring($str) {
    return htmlspecialchars($str, ENT_QUOTES);
}

for ($i = 0; $i < 4; $i++) {
    if(isset($_REQUEST['url_'.$i])) {
        $urls[$i] = $_REQUEST['url_'.$i];
        if(!empty($urls[$i])) {
            $urls_valid = true;
        }
    }
}
function parse_htmls($urls) {
    global $words;
    foreach ($urls as $url) {
        if(!empty($url)) {
            $client = new Client();
            $crawler = $client->request('GET', $url);
            $crawler->filter('title')->each(function($node) {
                global $words;
                $words .= $node->text() . ",";
            });
            $crawler->filter('a')->each(function($node) {
                global $words;
                $words .= $node->text() . ",";
            });
            $crawler->filter('p')->each(function($node) {
                global $words;
                $words .= $node->text() . ",";
            });
        }
    }
    return $words;
}

function cut_sentences($appid, $sentences) {
    $parts_num = (int)(mb_strlen($sentences) / 3000) + 1;
    if($parts_num > 1) {
        $keys = "";
        for ($i = 0; $i < $parts_num; $i++) {
            $sentence = mb_substr($sentences, $i*3000, 3000);
            $keys .= get_keys($appid, $sentence);
        }
        return cut_sentences($appid, $keys);
    } else {
        return $sentences;
    }
}

function get_keys($appid, $sentence) {
    //echo "----------------sentence----------------".$sentence;
    $results = call_yahoo_api($appid, $sentence);
    $results_num = count($results);
    $keys = "";
    if($results_num > 0) {
        for($i = 0; $i < $results_num; $i++) {
            $result = $results[$i];
            $keys .= escapestring($result->Keyphrase) . ",";
        }
    }
    //echo "----------------keys----------------".$keys;
    return $keys;
}

function call_yahoo_api($appid, $sentence) {
    $output = "xml";
    $request  = "http://jlp.yahooapis.jp/KeyphraseService/V1/extract?";
    try {
        $sentence = mb_convert_encoding($sentence, 'utf-8', 'auto');
        $request .= "appid=".$appid."&sentence=".urlencode($sentence)."&output=".$output;
        $responsexml = simplexml_load_file($request);
        return $responsexml->Result;
    } catch (Exception $e) {
        return array();
    }
}

function show_keyphrase($appid, $sentence) {
    $results = call_yahoo_api($appid, $sentence);
    $results_num = count($results);
    if($results_num > 0) {
        echo "<div id='result'>"."<table>";
        echo "<tr class='header'><th>特徴語</th><th>スコア</th></tr>";
        for($i = 0; $i < $results_num; $i++) {
            $result = $results[$i];
            echo "<tr><th>".escapestring($result->Keyphrase)."</th><td>".escapestring($result->Score)."</td></tr>";
        }
        echo "</table></div>";
    }
}

?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<title>HTML解析-特徴語抽出</title>
<style type="text/css">
#result table {
    float:left;
    margin-left:10px;
}
#result table td,
#result table th {
    padding:2px 5px;
    border-bottom:1px solid #666;
    text-align:center;
}
th {
    background-color:#EEE
}
.header th {
    background-color:#DDD;
}
#loading {
    font-size:80%;
    color:#999;
}
</style>
</head>
<body>
<h2 class="title">HTML解析-特徴語抽出</h2>
    <form method="POST" name="qform">
    <input type="text" id="url_0" name="url_0" size="70" value=<?php echo $urls[0] ?>><br>
    <input type="text" id="url_1" name="url_1" size="70" value=<?php echo $urls[1] ?>><br>
    <input type="text" id="url_2" name="url_2" size="70" value=<?php echo $urls[2] ?>><br>
    <input type="text" id="url_3" name="url_3" size="70" value=<?php echo $urls[3] ?>><br><br>
    <span id="loading"></span>
    <input type="submit" name="command_query" value="解析">
    </form>
<?php
    if($urls_valid) {
        $sentences = parse_htmls($urls);
        //echo $sentences;
    }
    if(!empty($sentences)){
        $keys = cut_sentences($yahoo_appid, $sentences);
        if(!empty($keys)){
            show_keyphrase($yahoo_appid, $keys);
        }
    }
?>
</body>
</html>
