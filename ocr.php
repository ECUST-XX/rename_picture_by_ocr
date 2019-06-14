<?php

function curl($opt)
{
    $curl = curl_init();
    curl_setopt_array($curl, $opt);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return "cURL Error #:" . $err;
    } else {
        return $response;
    }
}

function getToken($client_id, $client_secret, $grant_type = 'client_credentials')
{
    $res = curl([
        CURLOPT_URL => "https://aip.baidubce.com/oauth/2.0/token?grant_type={$grant_type}&client_id={$client_id}&client_secret={$client_secret}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
    ]);
    $res = json_decode($res, true);
    return $res['access_token'];
}

function getOcr($token, $imageBase64)
{
    $res = curl([
        CURLOPT_URL => "https://aip.baidubce.com/rest/2.0/ocr/v1/general_basic?access_token={$token}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "image={$imageBase64}&language_type=CHN_ENG",
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/x-www-form-urlencoded",
        ],
    ]);

    $res = json_decode($res, true);

    $ocrName = '';
    if (isset($res['words_result'])) {
        $ocrName = implode('xx', array_map(
                function ($value) {
                    return $value['words'];
                },
                $res['words_result'])
        );
        $ocrName = preg_replace( '/[^\x{4e00}-\x{9fa5}\w]/u', '', $ocrName);
    }

    return $ocrName;
}

function getImageBase64UrlEncode($imagePath)
{
    return urlencode(base64_encode(file_get_contents($imagePath)));
}

function changeName($imagePath)
{
    $fileType = (explode(".", basename($imagePath)))[1];
    $fileName = getOcr($GLOBALS['token'], getImageBase64UrlEncode($imagePath));
    if (empty($fileName)) {
        $newImagePath = $GLOBALS['errorDir'] . $GLOBALS['num'] . '.' . $fileType;
    } else {
        $newImagePath = $GLOBALS['saveDir'] . $GLOBALS['num'] . "_" . $fileName . '.' . $fileType;
    }
    copy($imagePath, $newImagePath);
    return $newImagePath;
}

// 百度ocr token
$token = getToken('client_id', 'client_secret');
// 原图片路径
$dir = '/Users/xinxin/Pictures/new/';
// 保存新图片路径
$saveDir = "/Users/xinxin/Pictures/QQ_Images/";
// 异常图片路径
$errorDir = "/Users/xinxin/Pictures/error/";

// 图片起始数
$num = 798;

if (is_dir($dir) && is_dir($saveDir) && is_dir($errorDir)) {
    $dh = opendir($dir);
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                echo $num, " ", $file, " ", changeName($dir . $file), "\n";
                $num++;
            }
            closedir($dh);
        }
    }
} else {
    echo "路径错误\n";
}


