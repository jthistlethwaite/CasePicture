<?php

require_once 'config.php';

$picDir = dirname(__FILE__). "/pictures";
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;


if ($_SERVER['REQUEST_METHOD'] == 'GET') {

	switch ($action) {
		case 'search':
			include_once 'resources/header.php';
			include_once './forms/search.php';
			include_once 'resources/footer.php';
		break;

		case 'browse':
		default:
			include_once './forms/browse.php';
		break;

	}



} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {

	switch ($action) {
		case 'search':
			include_once 'resources/header.php';

			include_once 'forms/search.php';

			$fileList = array();
			$searchTerms = isset($_REQUEST['searchTerms']) ? array_filter($_REQUEST['searchTerms']) : array('*');

			$allFiles = glob($picDir. '/*');

			foreach ($searchTerms as $searchTerm) {
				$matches = preg_grep('/'. $searchTerm. '.*/i', $allFiles);

				$fileList = array_merge($fileList, $matches);
			}

			$resultCount = count($fileList);


			$fileNames = array();

			$tableRows = '';

			foreach ($fileList as $file) {
				$fileName = basename($file);
				$fileNames[] = $fileName;

				$info = stat($file);

				$imgUrl = file_exists("pictures/thumbs/$fileName")
					? "pictures/thumbs/$fileName"
					: "pictures/$fileName";

				$tableRows .= "<tr>".
					"<td>".
						"<input type='checkbox' class='form-control' name='selectFiles[]' value='$fileName' />".
					"</td>".
					"<td><a href='pictures/$fileName'><img src='$imgUrl' width='100' /></a></td>".
					"<td>$fileName</td>".

					"<td>". str_replace("T", ' ', date('c', $info['ctime'])). "</td>".

					"<td>". round(($info['size'] / 1024), 2). "K</td>".
					"</tr>";
			}

			$tableRows .= "</tbody></table>";


            $output =<<<EOT
            <script>
            	$('#searchBox').hide();
			</script>
			<h1>Search Results</h1>			

			<form method="POST" id="downloadForm">
			<input type="hidden" name="action" value="download">
			
			<div class="row">
			

				<div class="col-md-9 well well-lg"  style="max-height: 600px; overflow-y: scroll;">
					<p>
						Found $resultCount matching images.
					</p>
					<table class="tablesorter" id="results">
					<thead>
						<th>
							<input type="checkbox" id="toggleCheck" class="form-control" />
						</th>
						<th>Picture</th>
						<th>Filename</th>
						<th>Creation Date</th>
						<th>Size</th>
					</thead>
					<tbody>
					$tableRows								
				</div>
				
				<div class="col-md-3">
					<div class="well well-sm">
						<h4>Download Selected Images</h4>
						<label>
							Zip File Name:
							<input type="text" name="zipname" placeholder="Zip file name..." class="form-control" />					
						</label>
						
						<div class="btn-group">
						
							<button id="dlButton" class="btn btn-primary">Download Zip File</button>
							
							
							<button id="s3Button" class="btn btn-info">Generate Download Link</button>
						</div>						
						
					</div>
					
					<div class="well well-sm">
						<a class="btn btn-success" onClick="$('#searchBox').slideToggle();">New Search</a>
					</div>
				</div>			
			</div>
EOT;

			echo <<<EOO
			<div class="container-fluid">
				$output
			</div>
EOO;


			$more = <<<EOT

			<script>
			$(document).ready(function () {
				$('#results').tablesorter();

				$('#dlButton').click(function (event) {
					//event.preventDefault();
					$('#downloadForm input[name="action"]').val("download");
					//$('#downloadForm').trigger("submit");
				});

				$('#s3Button').click(function (event) {
					//event.preventDefault();
					$('#downloadForm input[name="action"]').val("s3");
					//$('#downloadForm').trigger("submit");

				});

				$('#toggleCheck').click(function () {
					var checkedStatus = $(this).prop('checked');
					$('#results tbody tr').find('td:first :checkbox').each(function() {
						// $(this).prop('checked', checkedStatus);
						$(this).trigger('click');
					});

				});

			});

			</script>
EOT;
			echo $more;

			include_once 'resources/footer.php';
		break;

		case 's3':
                        if (count($_POST['selectFiles']) == 1) {
                                $fileName = basename($_POST['selectFiles'][0]);
                                $filePath = 'pictures/'. $fileName;
                        }

                        $zip = new ZipArchive();

                        $dir = sys_get_temp_dir();
                        $prefix = !empty($_POST['zipname']) ? basename($_POST['zipname']) : 'Pictures';
                        $name = uniqid("$prefix-"). '.zip';

                        $zipfile = $dir. '/'. $name;

                        if ($zip->open($zipfile, ZipArchive::CREATE) !== true) {
                                die("Cannot create $file");
                        }

                        foreach ($_POST['selectFiles'] as $file) {
                                $file = basename($file);

                                $file = 'pictures/'. $file;

                                $zip->addFile($file);
                        }

                        $zip->close();

			$randomFolder = uniqid('dl');

			$s3dir = $localUploadDir. $randomFolder;

			ob_end_clean();
			header("Connection: close");
			header("Content-Encoding: none");
			ignore_user_abort(true);
			ob_start();

			$url = $urlBase . $randomFolder. DIRECTORY_SEPARATOR. baseName($zipfile);

			require_once 'resources/header.php';


			echo <<<EOE
			<div class="container well well-sm">
				<h1><span class="glyphicon glyphicon-ok"></span> Link Generated</h1>
				<h4><a href='$url'>$url</a></h4>
				
				<div class="col-xs-10">
				
					<input id="url" type="text" class="form-control" value="$url" onClick="$(this).select(); document.execCommand('copy');"/>
				</div>
				<div class="col-xs-2">
					<button class="btn btn-primary" onClick="$('#url').select(); document.execCommand('copy');">Copy</button>
				</div>
				<div class="clearfix"></div>
				<br>
				<p class="small">
					Files are being packaged and sent to the above link. It may take a moment before they are available.
				</p>

			</div>
EOE;

			require_once 'resources/footer.php';

			$size = ob_get_length();
			header("Content-Length: $size");
			ob_end_flush(); flush();
			ob_end_clean();

			mkdir($s3dir);
			rename("$zipfile", $s3dir. '/'. basename($zipfile));

                        exit;
		break;

		case 'download':

			if (count($_POST['selectFiles']) == 1) {
				$fileName = basename($_POST['selectFiles'][0]);

				$filePath = 'pictures/'. $fileName;

	                        header("Content-Description: File Transfer");
        	                header("Content-Type: application/octet-stream");
                	        header("Content-Disposition: attachment; filename=$fileName");
                        	header("Content-Length: ". filesize($filePath));
				readfile($filePath);
				exit;

			}

			$zip = new ZipArchive();

			$dir = sys_get_temp_dir();

			$prefix = !empty($_POST['zipname']) ? basename($_POST['zipname']) : 'Pictures';

			$name = uniqid("$prefix-"). '.zip';

			$zipfile = $dir. '/'. $name;


			if ($zip->open($zipfile, ZipArchive::CREATE) !== true) {
				die("Cannot create $file");
			}

			foreach ($_POST['selectFiles'] as $file) {
				$file = basename($file);

				$file = 'pictures/'. $file;

				$zip->addFile($file);

			}

			$zip->close();

			header("Content-Description: File Transfer");
			header("Content-Type: application/octet-stream");
			header("Content-Disposition: attachment; filename=$name");
			header("Content-Length: ". filesize($zipfile));

			readfile($zipfile);

			unlink($zipfile);
			exit;
		break;


	}

}


?>
