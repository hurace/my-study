<?php
//file_get_contents方法：

$referer = 'https://c.y.qq.com';
$opt = array('http' => array('header' => "Referer: $referer"));

$context = stream_context_create($opt);
$url = 'https://c.y.qq.com/splcloud/fcgi-bin/fcg_get_diss_by_tag.fcg?g_tk=5381&inCharset=utf-8&outCharset=utf-8&notice=0&format=json&platform=yqq&hostUin=0&sin=0&ein=29&sortId=5&needNewCode=0&categoryId=10000000&rnd=0.16221565846551922';
$file_contents = file_get_contents($url, false, $context);

var_export(json_decode($file_contents, true));