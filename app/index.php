<?php
/**
 * @author Jason Thistlethwaite (jason.thistlethwaite@gmail.com)
 * @version 2.0
 * @license BSD 3-Clause
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

        while (file_exists( $saveFolder . $filename. '_'. $suffix. '.jpg')) {
            $suffix++;
        }

        $saveFile = $saveFolder . $filename. '_'. $suffix. '.jpg';

        ob_end_clean();
        header("Connection: close");
        header("Content-Encoding: none");
        ignore_user_abort(true);
        ob_start();

        move_uploaded_file($_FILES['webcam']['tmp_name'], $saveFile);

        /*
         * We echo out the name of the file as plaintext
         *
         * The client-side uses this to know the picture was saved and
         * displays it to the user
         *
         * @todo Is there a better way to do this?
         *
         * The code has been used this way in production since 2016, taking
         * thousands of pictures. In that time, no use-case for making this
         * more complex has arisen.
         */
        echo  $filename. '_'. $suffix. '.jpg';

        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush(); flush();
        ob_end_clean();

        /*
         * We have just closed the connection from the client
         *
         * This is known-working with Apache, not sure about other webservers. The idea here is we're closing
         * the HTTP connection with the client while keeping our script running.
         *
         * This is where we handle any post-upload manipulation of the file like uploading it to a remote mount
         *
         * The main idea: we want to let the client know the picture was saved successfully, without making the client
         *  wait on what might be long-running tasks
         */

        /*
         * On the remount mount we save pictures differently
         *
         * Each prefix will get it's own folder, with all pictures sharing the prefix stored within regardless of date
         *
         * Imagine this situation:
         *
         * An insurance agent is taking pictures for insurance claim FOO-ABC. The agent will use FOO-ABC as the prefix
         * for all pictures taken during the claim process. This way, they are all saved in the same folder.
         *
         *
         */
        $safePrefix = basename($prefix);
        fwrite($fh, "Trying to save to: ". $localUploadDir. $safePrefix. "\n");

        if (!is_dir($localUploadDir . $safePrefix)) {
            mkdir($localUploadDir. $safePrefix);
        }


        fwrite($fh, "Saving a copy to ". $localUploadDir. $safePrefix. '/'. $filename. '_'. $suffix. '.jpg'. "\n");

        copy($saveFile, $localUploadDir. $safePrefix. '/'. $filename. '_'. $suffix. '.jpg');

        $access_url = $urlBase . $safePrefix. '/'. $filename. '_'. $suffix. '.jpg';

        /*
         * If a function called notify_picture exists, we call it with the following arguments:
         *
         * 0: The name of the saved file
         * 1: The prefix it was taken under
         * 2: An optional secondary "tag" or something that could be used to reference the picture
         * 3: The public url where it can be accessed (such as after uploaded to a remote mount)
         *
         * Use case:
         *  notify_picture might call some API or webhook, letting an external application know a picture was taken
         *
         *  This exists mainly because of the LAN appliance intended use-case for this program. It's used to let
         *  a public-facing website know a picture of something exists. A typical example would be something like
         *  this:
         *
         *  This has been used in various ways:
         *
         *  a) notify external service
         *      Used to update a warehouse management system about pictures of received inventory
         *
         *      In this case, the prefix was the tracking number of the package. This tool was used in that case for
         *      the following reasons:
         *
         *      1) The receiving process needs to happen quickly. Staff don't have time to use a regular camera
         *          to take pictures and then upload and associate them to deliveries later.
         *
         *      2) Sometimes the pictures can't be taken right away
         *
         *      3) Uploading the pictures directly to the WMS software was a bandwidth and storage constraint
         *
         *  b) Optical character recognition of vendor invoices / delivery receipts
         *
         *      1) Vendor delivers products to a convenience store chain, with a paper invoice. The invoice
         *          has a barcode on it, then a text list of what was delivered.
         *
         *      2) Clerk photographs the delivered items, as well as the invoice/receipt
         *
         *      3) notify_picture tells a microservice to OCR the photographed receipt and associate it in a database
         *          so delivery invoices can be searched by items delivered, and results also contain pictures
         */
        if (function_exists('notify_picture')) {
            notify_picture($filename . '_' . $suffix . '.jpg', $prefix, null, $access_url);
        }

        exit;
    }
?>
<html>
    <head>
        <script src="webcam.min.js"></script>
	<title>CasePicture</title>
	<script src="resources/jquery-2.2.0.min.js"></script>
	<link href="resources/bootstrap.css" rel="stylesheet" />
    <link href="resources/bootstrap-theme.min.css" rel="stylesheet" />
        <link href="resources/theme.css" rel="stylesheet" />

    </head>
    <body>
	<div class="container-fluid">
		<div class="btn-group">
			<a class="btn btn-primary" href="pictures/"><span class="glyphicon glyphicon-th-list"></span> View Gallery</a>
			<a class="btn btn-primary" href="search.php?action=search"><span class="glyphicon glyphicon-search"></span> Search &amp; Download Pictures</a>
            <a class="btn btn-info" href="mobile.php"><span class="glyphicon glyphicon-phone"></span> Mobile Mode</a>
		</div>
		<div class="pull-right">

		    <form onSubmit="configCamera(); return false" id="camSettings">
			<label>
			Resolution:
				<select id="resolutionSelect" name="resolution" class="form-control">
	        		        <option value="1280x720">720P</option>
			                <option value="1920x1080">1080P</option>
			                <option value="3264x2448">Ultra High</option>
	            		</select>
			</label>

        		<button class="btn btn-default">Apply</button>
		    </form>

	</div>
	<div class="clearfix"></div>
   <div class="row">

       <div class="col-xs-4 text-center">
           <button class="btn btn-default" onClick="clearPreviews()">Clear Thumbnails</button>
           <div id="results" class="well well-sm">
               <small>Thumbnails</small>
           </div>


       </div>

       <div class="col-xs-8">
           <h4 class="text-center">View Finder</h4>
           <div id="camera_view" style="width: 100%; margin: auto; border: 1px solid #7a7a7a;" onClick="take_snapshot();"></div>



           <div class="clearfix"></div>


        </div> <!-- End main row -->

       <div class="clearfix"></div>

        <div class="row">
            <div class="col-xs-4" id="lControls">
                <div class="text-left">

                    <button onClick="take_snapshot()" class="btn btn-primary btn-lg">
                        <h3><span class="glyphicon glyphicon-picture"></span></h3>
                        Take Snapshot

                    </button>
                </div>
            </div>
            <div class="col-xs-8" id="rControls">

                <div class="well well-sm">

                    <div class="col-xs-8">
                        <input type="text" placeholder="Picture Prefix..." id="prefix" class="form-control" style="font-size: 1.25em; margin-bottom: 1em; height: 2.5em;" value="<?php echo $prefix; ?>" />

                        <p class="small">
                            All pictures will be saved as <i>prefix</i>_<i><?php echo date("Y-m-d"); ?>_##.jpg</i>
                        </p>

                    </div>
                    <div class="col-xs-4">
                        <button onClick="take_snapshot()" class="btn btn-primary btn-lg pull-right">
                            <h3><span class="glyphicon glyphicon-picture"></span></h3>
                            Take Snapshot

                        </button>
                    </div>
                    <div class="clearfix"></div>
                </div>


                <div class="col-sm-12">

                    <div class="container-fluid well well-sm">

                        <h4>Timed / Handsfree Pictures</h4>

                        <label>
                            Seconds Delay: <input class="form-control" type="number" id="snapDelay" placeholder="Delay between snapshots..." value="5" />
                        </label>
                        <label>
                            Number of Pictures: <input class="form-control" type="number" id="shotCount" value="4" />
                        </label>

                        <button class='btn btn-success' onClick="takeTimedShots()">Take Timed Pictures</button>
                    </div>
                </div>



            </div>

            </div>

            <div class="clearfix"></div>
        </div>

   </div> <!--    End main container -->





    <div class="clearfix"></div>




    <audio id="shutter" src="shutter.ogg" preload="auto"></audio>

    <script>

    var shutter = new Audio("shutter.ogg");

    function clearPreviews()
    {
    	document.getElementById('results').innerHTML = '';

    }

    function resetCamera() {
        Webcam.reset();
    }
    
    function takeTimedShots() {
        var delay = document.getElementById('snapDelay').value * 1000;
        var shotCount = document.getElementById('shotCount').value;

        var loopDelay = delay;
        for (var x = 0; x < shotCount; x++) {

            console.log("We are here " + x);
            setTimeout('take_snapshot()', loopDelay);
            loopDelay += delay;
        }
    }
    
    function take_snapshot() {
        Webcam.snap ( function(data_uri) {

            document.getElementById('shutter').play();

//            shutter.play();

            var prefix = document.getElementById('prefix').value;

            Webcam.upload( data_uri, 'index.php?prefix=' + prefix, function (responseCode, rawResponse) {
                // console.log(responseCode);
                // console.log(rawResponse);
			if (responseCode == 200) {
			    
			    console.log(rawResponse);

			    var picUrl = "pictures/" + rawResponse;

				$('#results').prepend("<div class='well well-sm'>" +
                        "<a href='" + picUrl + "' target='_viewPic'>" +

                    "<img width='100%' src='" + picUrl + "' />"
                    + rawResponse +
                    "</a></div>");

			}
			else {
				alert("Server error saving picture. Please contact IT department");
			}
                });
            });
    }

    function setUpCamera(dWidth, dHeight)
    {

        console.log(dWidth, dHeight);

        Webcam.set({
            width: 640,
            height: 360,
            dest_width: dWidth,
            dest_height: dHeight,
            image_format: 'jpeg',
            jpeg_quality: 90
            });
        Webcam.attach("#camera_view");        
    }

    function configCamera()
    {
        var resolution = $("select[name='resolution']").val().split("x");
        
        resetCamera();
        
        setUpCamera(resolution[0], resolution[1]);
        
        console.log(resolution);
    }

<?php

	$resolution = isset($_REQUEST['resolution']) ? $_REQUEST['resolution'] : $defaultResolution;

	$res = explode('x', $resolution);

	$dWidth = $res[0];
	$dHeight = $res[1];

?>

    $(document).ready(function () {
       setUpCamera(<?php echo $dWidth; ?>, <?php echo $dHeight; ?>);

       $('#resolutionSelect').val("<?php echo $resolution; ?>");

	$('#prefix').click( function() {
		$(this).select();
	});

	$('#prefix').focus().select();

    });

    </script>

    <style>
        #camera_view {
            cursor: pointer;
        }



        #results {
            height: 400px;
            overflow-y: auto;
        }

        /*
         * If the screen is smaller than this, there's no reason to have
         * take picture buttons on both sides of the screen
         *
         */
        @media (max-width: 720px) {
            #lControls {
                display: none;
            }

            #rControls {
                width: 100%;
            }
        }

    </style>



    <?php
    require_once 'resources/footer.php';
    ?>
