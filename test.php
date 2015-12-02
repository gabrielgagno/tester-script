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
$db_selected = mysqli_select_db($link, 'nutch');
if (!$db_selected) {
    die ('Can\'t use foo : ' . mysql_error());
}
$sampArray = ['business.csv', 'community.csv', 'shop.csv', 'tattoo.csv', 'www.csv'];

# curl the URL
$ch = curl_init();
$successCtr = 0;
$ctr = 0;
foreach($sampArray as $sRow) {
    echo "NOW OPERATING ON: ".$sRow;
    $file = fopen($sRow, 'r');
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
        if(!isset($data[0])) {
            continue;
        }

        if(substr($urlRes, 0, strlen('http://')) === 'http://'){
            $actual = str_replace('http://', '', $urlRes);
        }
        else if(substr($urlRes, 0, strlen('https://')) === 'https://'){
            $actual = str_replace('https://', '', $urlRes);
        }

        if(substr($data[1], 0, strlen('http://')) === 'http://'){
            $expected = str_replace('http://', '', $data[1]);
        }
        else if(substr($data[1], 0, strlen('https://')) === 'https://'){
            $expected = str_replace('https://', '', $data[1]);
        }
        if(strcmp($actual, $expected)==0) {
            echo "SUCCESS\n";
            $successCtr++;
        }
        else {
            $sql = 'select * from nutch.webpage where baseUrl like \'%'.$expected.'%\'';
            $result = $link->query($sql);
            if($result->num_rows > 0){
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
}

echo "FINAL RESULTS: \n".($successCtr/$ctr)*100 ."%";