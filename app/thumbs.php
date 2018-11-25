<?php


$picDir = 'pictures/';
$thumbDir = $picDir. 'thumbs/';

$convert = "/usr/bin/convert -thumbnail 120 ";

function baseNames($row) {
	$row = basename($row);
	return $row;
}

$allPics = array_map('baseNames', glob($picDir. '*'));

$count = count($allPics);
for ($x = 0; $x < $count; $x++) {
	$name = array_pop($allPics);
	if (is_file($thumbDir. $name)) {
		//echo "Thumb exists...\n";
	} else {

		//echo "Checking for ". $picDir. $name. "\n";

		if ( is_dir($picDir. $name) ) {
			continue;
		}
		$src = escapeshellarg($picDir. $name);
		$dst = escapeshellarg($thumbDir. $name);

		$command = "$convert $src $dst 2> /dev/null";

		@shell_exec($command);
	}

}


?>
