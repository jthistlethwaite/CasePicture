<?php

$composer =  dirname(__FILE__) . DIRECTORY_SEPARATOR. '..'. DIRECTORY_SEPARATOR. 
	'vendor'. DIRECTORY_SEPARATOR. 'autoload.php';

if ( is_file($composer) ) {
	require_once $composer;
}


function url() {

	if(isset($_SERVER['HTTPS'])){
		 $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
	}
	else {
		$protocol = 'http';
	}

	return $protocol . "://" . $_SERVER['HTTP_HOST'];

}
