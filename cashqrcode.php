<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 17:01
 */
require_once 'db.php';
require 'phpqrcode.php';
$db = DB::getIntance();
$url='http://apii.silutianyu.com/share/';
for ($a=0;$a<10011;$a++) {
    $limit=$a*10;
    $code = $db->getAll(" select id,code from slty_jsxj_coupon_code where uid=0 order by id limit {$limit},10");
    $errorCorrectionLevel = 'L';  //容错级别
    $matrixPointSize = 5;      //生成图片大小
    foreach ($code as $v) {
        //生成二维码图片
        $filename = 'cashqrcode/' . $v['id'] . '.png';
        $value = $url.'?c='.$v['code'];
        QRcode::png($value, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    }
    exit;
}
echo 'success';
exit;