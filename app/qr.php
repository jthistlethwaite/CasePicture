<?php

require_once 'includes.php';

require_once 'config.php';

use Endroid\QrCode\QrCode;


$baseUrl = url();

$prefix = $_REQUEST['prefix'];


$url = $baseUrl. "/mobile.php?prefix=$prefix";

$qrCode = new QrCode($url);



header('Content-Type: '.$qrCode->getContentType());


echo $qrCode->writeString();
