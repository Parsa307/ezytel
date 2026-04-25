<?php
    include_once(__DIR__.'/libs.php');

    if (isset($_GET['next']) && isset($_GET['chid'])){
        html_parse($_GET['chid'],$_GET['next']);
    }
    if (isset($_GET['info'])){
        json_info($_GET['info']);
    }
    if (!isset($_GET['url'])) {
        http_response_code(404);die;
    }
    $pic = hex2bin($_GET['url']);
    $hash = md5($pic);
    if (curl_download("https://".$pic,$hash.'.jpg')===false){
        http_response_code(400);
    }else{
        header('Content-Type: image/jpeg');
        header('Location: cache/'.$hash.'.jpg');
    }