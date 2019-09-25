<?php
require_once 'db.php';
require 'phpqrcode.php';
date_default_timezone_set('PRC');
$GLOBALS['db'] = DB::getIntance();
while (true) {
    $queueLimit = queueLimit();
    if ($queueLimit) {
        $GLOBALS['zipArchive'] = [];
        $GLOBALS['where'] = " 1=1 and box_code_id={$queueLimit['id']} ";
        $GLOBALS['total'] = $queueLimit['number'];//需要生成的二维码的数量
        $GLOBALS['sum'] = 0;
        $GLOBALS['limitSql'] = 100;
        $GLOBALS['limitPage'] = intval($GLOBALS['total'] / $GLOBALS['limitSql']) + 1;
        if (($GLOBALS['total'] % $GLOBALS['limitSql']) == 0) {
            $GLOBALS['limitPage'] = intval($GLOBALS['total'] / $GLOBALS['limitSql']);
        }
        $GLOBALS['offsetSql'] = 0;
        for ($p = 0; $p < $GLOBALS['limitPage']; $p++) {
            if ($GLOBALS['sum'] >= $GLOBALS['total']) {
                break;
            }
            $GLOBALS['offsetSql'] = $p * $GLOBALS['limitSql'];
            if (($p + 1) == $GLOBALS['limitPage'] && $GLOBALS['sum'] < $GLOBALS['total']) {
                $GLOBALS['limitSql'] = $GLOBALS['total'] - $GLOBALS['sum'];
            }
            $getAll = getAll($GLOBALS['where'], $GLOBALS['offsetSql'], $GLOBALS['limitSql']);
            if (!count($getAll)) {
                break;
            }
            outputInit();
            foreach ($getAll as $val) {
                if ($GLOBALS['sum'] == $GLOBALS['total']) {
                    break;
                }
                outputAppend($val['code'], $val['id']);
                $GLOBALS['sum'] ++;
            }
            unset($getAll);
        }
        if (count($GLOBALS['zipArchive'])) {
            $zip = new ZipArchive();
            $zip->open("./boxcode/{$queueLimit['name']}.zip", ZipArchive::CREATE);
            foreach ($GLOBALS['zipArchive'] as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }
        queueUpdate($queueLimit['id'], 1);
    }
    sleep(60);
}

echo "success\n";

function queueLimit() {
    return $GLOBALS['db']->getRow("select * from slty_jsxj_box_code where is_queue=1 and is_download=2 order by add_time desc,id desc;");
}

function queueUpdate($batchId, $status = 0) {
    return $GLOBALS['db']->update('slty_jsxj_box_code', ['is_download' => $status, 'download_time' => date('Y-m-d H:i:s')], ['id' => $batchId]);
}

function getAll($where, $offset, $limit) {
    return $GLOBALS['db']->getAll("select * from slty_jsxj_coupon_code where {$where} limit {$limit} offset {$offset}; ");
}

function outputInit() {
    if (!is_dir('boxcode')) {
        if (!mkdir('boxcode', 0777, true)) {
            echo 'ERROR:mkdir error!';
            return;
        }
    }
}
function outputAppend($code, $batchId) {
    $errorCorrectionLevel = 'L';  //容错级别
    $matrixPointSize = 5;      //生成图片大小
    $url='http://apii.silutianyu.com/share/';
    $filename = 'cashqrcode/' . $batchId . '.png';
    $value = $url.'?c='.$code;         //二维码内容
    QRcode::png($value, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    $GLOBALS['zipArchive'][] = $filename;
}
