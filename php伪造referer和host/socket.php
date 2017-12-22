<?php

$host = 'c.y.qq.com';
$target = '/splcloud/fcgi-bin/fcg_get_diss_by_tag.fcg?g_tk=5381&inCharset=utf-8&outCharset=utf-8&notice=0&format=json&platform=yqq&hostUin=0&sin=0&ein=29&sortId=5&needNewCode=0&categoryId=10000000&rnd=0.16221565846551922';
$referer = 'https://c.y.qq.com'; //伪造HTTP_REFERER地址
$fp = fsockopen($host, 80, $errno, $errstr, 30);
if (!$fp) {
    echo "$errstr($errno)<br />\n";
} else {
    $str = '';
    $out = "
GET $target HTTP/1.1
Host: $host
Referer: $referer
Connection: Close\r\n\r\n";
    fwrite($fp, $out);
    while (!feof($fp)) {
        $str .= fread($fp, 1024);
    }
    fclose($fp);
}

//去除http头信息
$pos = strpos($str, "\r\n\r\n");
$str = substr($str, $pos + 4);

var_export(json_decode($str, true));
