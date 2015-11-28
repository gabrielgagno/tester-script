<?php
/**
 * Created by PhpStorm.
 * User: gabrielgagno
 * Date: 11/27/15
 * Time: 5:12 PM
 */

$resArray = array();
$link = mysqli_connect('localhost', 'root');
if (!$link) {
    die('Not connected : ' . mysql_error());
}

// make foo the current db
$db_selected = mysqli_select_db($link, 'nutch_11162015');
if (!$db_selected) {
    die ('Can\'t use foo : ' . mysql_error());
}
$sampArray = ['business.csv', 'community.csv', 'shop.csv', 'tattoo.csv', 'www.csv'];

# curl the URL
$ch = curl_init();
$successCtr = 0;
$ctr = 0;
//foreach($sampArray as $sRow) {
    $file = fopen('shop.csv', 'r');
    $data = fgetcsv($file);
    while(!feof($file)){
        $data = fgetcsv($file);
        $curlUrl = 'http://50.18.169.52/search-data?q='.urlencode($data[0]);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_URL => $curlUrl,
            CURLOPT_RETURNTRANSFER => 1,
        ));
        $response = curl_exec($ch);
        $decodedResponse = json_decode($response);
        $urlRes = $decodedResponse->result[0]->_source->url;
        echo "SEARCH TERM: ".$data[0]."\n";
        if(strcmp($urlRes, $data[1])==0) {
            echo "SUCCESS\n";
            $successCtr++;
        }
        else {
            if(substr($urlRes, 0, strlen('http://')) === 'http://'){
                $string = str_replace('http://', '', $urlRes);
            }
            else if(substr($urlRes, 0, strlen('https://')) === 'https://'){
                $string = str_replace('https://', '', $urlRes);
            }
            $sql = 'select * from webpage where baseUrl like \'%'.$data[1].'%\'';
            $result = $link->query($sql);
            if(!$result->num_rows > 0){
                echo "FAIL (logic problem)\n";
                $ctr++;
            }
            else{
                echo "FAIL (not crawled)\n";
            }
        }
        echo "EXPECTED: $data[1]'\n";
        echo "ACTUAL: $urlRes'\n";
        echo "...\n";
    }
    fclose($file);
//}

echo "FINAL RESULTS: \n".($successCtr/$ctr)*100 ."%";