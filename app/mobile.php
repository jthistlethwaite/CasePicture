<?php
/**
 * @author Jason Thistlethwaite (jason.thistlethwaite@gmail.com)
 * @version 2.0
 * @license BSD 3-Clause
 *
 * This part of the takePicture package is intended for mobile devices or uploading saved files on a device
 *
 * It has a few differences in usage:
 *
 *  - It supports uploading multiple files at once
 *      - capped at 4 because of PHP resource limits / max post and upload size
 *
 *  - The input attribute accept="image/*;capture=camera" triggers most mobile devices to use their camera
 *      - Tested on Android 4+ (up to 7), Windows 10 tablets, Microsoft Surface Pro 4
 *
 *      - This means mobile devices can also upload short videos (currently, only mp4 extension is supported)
 *
 *
 * Copyright 2016 - 2018 Jason Thistlethwaite
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following
 * disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following
 * disclaimer in the documentation and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products
 * derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

if (!is_file('config.php')) {
    die("Config file 'config.php' not found. Make a copy of config.php.dist and edit as-needed");
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $fh = fopen("php://stderr", "w");

    $filename = basename($prefix. '_'. date("Y-m-d"));

	$x = 0;

	$saveFiles = array();

	/*
	 * We save all the uploaded pictures into an array to let the user know what was successfully saved
	 *
	 * This differs from the regular takePicture/index.php in two ways:
	 *
	 *  1) We support .mp4 files (or try to)
	 *
	 *  2) The files are all saved to disk _before_ the browser connection is closed
	 */
	foreach ($_FILES['picture']['tmp_name'] as $tmpname) {
		$ext = ".jpg";

		if ($_FILES['picture']['type'][$x] == "video/mp4") {
			$ext = ".mp4";
		}

        while (file_exists( $saveFolder . $filename. '_'. $suffix. $ext)) {
            $suffix++;
        }

        $saveFile = $saveFolder . $filename. '_'. $suffix. $ext;

        if ( move_uploaded_file($tmpname, $saveFile) === true) {
            $saveFiles[] = [
                "fullPath" => $saveFile,
                "name" => $filename. '_'. $suffix. $ext
            ];
        }

        $x++;
	}

    ob_end_clean();
    header("Connection: close");
    header("Content-Encoding: none");
    ignore_user_abort(true);
    ob_start();

	echo "<h1>Pictures saved successfully...</h1>";

	foreach ($saveFiles as $fileSaved) {
	    echo $fileSaved['name']. "<br>";
    }


	echo <<<EOT
	<script>
		setTimeout('window.location = "mobile.php"', 1500);
	</script>
EOT;

    $size = ob_get_length();
    header("Content-Length: $size");
    ob_end_flush(); flush();
    ob_end_clean();


    $safePrefix = basename($prefix);
    if (!is_dir($localUploadDir . $safePrefix)) {
        mkdir($localUploadDir. $safePrefix);
    }

    foreach ($saveFiles as $fileSaved) {

        fwrite($fh, "Trying to save to: ". $localUploadDir. $safePrefix. "\n");
        fwrite($fh, "Saving a copy to ". $localUploadDir. $safePrefix. '/'. $fileSaved['name']. "\n");

        copy($fileSaved['fullPath'], $localUploadDir. $safePrefix. '/'. $fileSaved['name']);

        $access_url = $urlBase . $safePrefix. '/'. $fileSaved['name'];

        if (function_exists('notify_picture')) {
            notify_picture($fileSaved['name'], $prefix, null, $access_url);
        }
    }

    exit;


}

?>
<html>
<head>
        <script src="resources/jquery-2.2.0.min.js"></script>

        <link href="resources/bootstrap.css" rel="stylesheet" />
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="container-fluid text-center">

    <div class="well">
        <form method="POST" enctype="multipart/form-data" id="picform" class="form-group-lg">

            <label style="width: 100%">
                Prefix:
                <input name="prefix" class="form-control" placeholder="File prefix..." type="<?php echo $mobilePrefixMode; ?>" id="scanner" />
            </label><br>


            <label>
                Take Picture
                <input class="form-control" type="file" accept="image/*;capture=camera" name="picture[]" id="takePic" />
            </label><br />
            <label>
                Take Picture
                <input class="form-control" type="file" accept="image/*;capture=camera" name="picture[]" id="takePic" />
            </label><br />
            <label>
                Take Picture
                <input class="form-control" type="file" accept="image/*;capture=camera" name="picture[]" id="takePic" />
            </label><br />
            <label>
                Take Picture
                <input class="form-control" type="file" accept="image/*;capture=camera" name="picture[]" id="takePic" />
            </label><br />

            <br><br>
            <p>
                <button class="btn btn-primary btn-lg">Upload Pictures</button>
            </p>

        </form>
    </div>

    <div class="btn-group">

        <button class="btn btn-primary" onClick="switchAlpha();">Toggle Input</button>
        <a class="btn btn-default" href="index.php">Desktop Mode</a>
    </div>

</div>


<script>

$(document).ready(function () {

	$("#picform").submit(function (e) {

		var send = confirm("Are you sure you want to upload these pictures?");

		if (send == false) {
			e.preventDefault();
			return false;

		} else {
			return true;
		}

	});

	$("input[name='prefix']").focus().select();

	$('#scanner').keypress(function (e) {
		if (e.keyCode == 13) {
			e.preventDefault();
			return false;
		}
	});


});

function switchAlpha() {

	var state = $('input[name="prefix"').attr("type");

	if (state == 'text') {
		$('input[name="prefix"').attr("type", "number");

	} else {
		$('input[name="prefix"').attr("type", "text");

	}

}

</script>

</body>
</html>
