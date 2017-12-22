<?php

$ch = curl_init();

$url = 'https://c.y.qq.com/splcloud/fcgi-bin/fcg_get_diss_by_tag.fcg?g_tk=5381&inCharset=utf-8&outCharset=utf-8&notice=0&format=json&platform=yqq&hostUin=0&sin=0&ein=29&sortId=5&needNewCode=0&categoryId=10000000&rnd=0.1622156584655191';
curl_setopt($ch, CURLOPT_URL, $url);
//将curl_exec()获取的信息以字符串返回，而不是直接输出。
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
////让其不验证ssl证书
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//在HTTP请求头中"Referer: "的内容。
curl_setopt($ch, CURLOPT_REFERER, "https://c.y.qq.com");

$data = curl_exec($ch);
//var_export(curl_error($ch));
curl_close($ch);
var_export(json_decode($data, true));
