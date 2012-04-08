<?php
include "../include/cm.CommonFunctions.php";

// summary
//		Test file to handle image uploads (remove the image size check to upload non-images)
//
//		This file handles both Flash and HTML uploads
//
//		NOTE: This is obviously a PHP file, and thus you need PHP running for this to work
//		NOTE: Directories must have write permissions
//		NOTE: This code uses the GD library (to get image sizes), that sometimes is not pre-installed in a
//				standard PHP build.
//
//require("cLOG.php");

function findTempDirectory() {
	if (isset($_ENV["TMP"]) && is_writable($_ENV["TMP"]))
		return $_ENV["TMP"];
	elseif (is_writable(ini_get('upload_tmp_dir')))
		return ini_get('upload_tmp_dir');
	elseif (isset($_ENV["TEMP"]) && is_writable($_ENV["TEMP"]))
		return $_ENV["TEMP"];
	elseif (is_writable("/tmp"))
		return "/tmp";
	elseif (is_writable("/windows/temp"))
		return "/windows/temp";
	elseif (is_writable("/winnt/temp"))
		return "/winnt/temp";
	else
		return null;
}

function trace($txt, $isArray=false) {
	//creating a text file that we can log to
	// this is helpful on a remote server if you don't
	//have access to the log files
	//
	$log = new cLOG("../tests/upload.txt", false);
	//$log->clear();
	if ($isArray) {
		$log->printr($txt);
	} else {
		$log->write($txt);
	}

	//echo "$txt<br>";
}

function getFileType($filename) {
	return strtolower(substr(strrchr($filename, "."), 1));
}

fb("---------------------------------------------------------");

//
//
//	EDIT ME: According to your local directory structure.
// 	NOTE: Folders must have write permissions
//
$upload_path = "../fileadmin/";  // where image will be uploaded, relative to this file
//$download_path = "../fileadmin/";	// same folder as above, but relative to the HTML file
if (empty($_GET['uploadtype']) || empty($_GET['fieldname'])) {
	header("HTTP/1.1 401 Access Denied");
	exit;
} else if(!isset($_FILES[$_GET['fieldname']])) {
	echo "{}&&{}";
}
$type = $_GET['uploadtype'];
$directory = $_POST['directory'];
if ($directory != "" && $directory != ".") {
	$upload_path .= $directory;
}
// HTML and upload.php are same location
$download_path = $upload_path;
//
// 	Determine if this is a Flash upload, or an HTML upload
//
//
//		First combine relavant postVars
$postdata = array();
$htmldata = array();
$data = "";
//foreach ($_POST as $nm => $val) {
//	$data .= $nm ."=" . $val . ",";	// string for flash
//	$postdata[$nm] = $val;			// array for html
//}
fb($_FILES[$_GET['fieldname']], "files");
$fieldName = $_GET['fieldname'];

if ($type == "flash") {

	// The SWF passes one file at a time to the server, so the files come across looking
	// very much like a single HTML file. The SWF remembers the data and returns it to
	// Dojo as an array when all are complete.

	fb("returnFlashdata....");
	fb($_POST, "Flash POST:");
	$returnFlashdata = true; //for dev
	fb($_FILES[$fieldName], "FILES:");
	move_uploaded_file($_FILES[$fieldName]['tmp_name'], $upload_path . $_FILES[$fieldName]['name']);
	$name = $_FILES[$fieldName]['name'];
	$notimage = false;
	$file = $download_path . $name;
	$type = getFileType($file);
	try {
		list($width, $height) = getimagesize($file);
	} catch (Exception $e) {
		$notimage = true;
		$width = 0;
		$height = 0;
	}
	fb("file: " . $file . "  " . $type . " " . $width);
	// 		Flash gets a string back:
	$data .='file=' . $file . ',name=' . $name . ',type=' . $type .
			  (!$notimage ? ',width=' . $width . ',height=' . $height : "");
	if ($returnFlashdata) {
		fb($data, "returnFlashdata:=======================");
		// echo sends data to Flash:
		echo($data);
		exit;
	}
} elseif ($type == "html5" && isset($_POST[$fieldName])) {
	
	// weird stuff may happen
	fb("HTML5 multi file input... CAN'T ACCESS THIS OBJECT! (POST[uploadedfiles])");
	fb(count($_POST[$fieldname]) . " ");
	
} elseif ($type == "html5" && isset($_FILES[$fieldName])) {

	//	The HTML5 input field sends an array of files to the server
	// processing each sequentially while putting together a JSON structure
	// to return to the Dojo Uploader

	fb("HTML5 multi file input");
	$htmldata = array();

	for ($i = 0, $cnt = 0; $i < count($_FILES[$fieldName]['name']); $i++, $cnt++) {
		$moved = move_uploaded_file($_FILES[$fieldName]['tmp_name'][$i], $upload_path . $_FILES[$fieldName]['name'][$i]);
		fb("moved:" . $moved . "  " . $_FILES[$fieldName]['name'][$i]);
		if ($moved) {
			$name = $_FILES[$fieldName]['name'][$i];
			$file = $upload_path . $name;
			$type = getFileType($file);
			$htmldata[$cnt] = array();
			try {
				list($width, $height) = getimagesize($file);
			} catch (Exception $e) {
				$width = 0;
				$height = 0;
				$htmldata[$cnt]['filesInError'] = $name;
			}

			if (!$width) {
				$width = 0;
				$height = 0;
			}

			$htmldata[$cnt]['file'] = $file;
			$htmldata[$cnt]['name'] = $name;
			$htmldata[$cnt]['width'] = $width;
			$htmldata[$cnt]['height'] = $height;
			$htmldata[$cnt]['type'] = $type;
			$htmldata[$cnt]['size'] = filesize($file);

			fb($htmldata[$cnt], "File processed [$file]");
		} elseif (strlen($_FILES[$fieldName]['name'][$i])) {

			$htmldata[$cnt] = array("ERROR" => "File could not be moved: " . $_FILES[$fieldName]['name'][$i]);
		}
	}

	print json_encode($htmldata);
	exit;
} elseif (isset($_FILES['uploadedfile'])) {
	// TODO: rewrite
	// 	If the data passed has 'uploadedfile', then it's HTML.
	//	There may be better ways to check this, but this *is* just a test file.
	//
	$m = move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $upload_path . $_FILES['uploadedfile']['name']);



	fb("HTML single POST:");
	fb($postdata, true);

	$name = $_FILES['uploadedfile']['name'];
	$file = $upload_path . $name;
	$type = getImageType($file);
	try {
		list($width, $height) = getimagesize($file);
	} catch (Exception $e) {
		$width = 0;
		$height = 0;
	}
	fb("file: " . $file);

	$htmldata['file'] = $file;
	$htmldata['name'] = $name;
	$htmldata['width'] = $width;
	$htmldata['height'] = $height;
	$htmldata['type'] = $type;
	$htmldata['size'] = filesize($file);
	$htmldata['additionalParams'] = $postdata;
	
} else {
	fb(array("ERROR" => "Improper data sent - no files found", "files" => $_FILES));
	echo "{}&&{}";
	return;
}

//HTML gets a json array back:
$data = json_encode($htmldata);
fb("Json Data Returned:");
fb($data);
// in a text field:
//elseif (isset($_FILES['uploadedfile0'])) {
//	//
//	//	Multiple files have been passed from HTML
//	//
//	$cnt = 0;
//	fb("HTML multiple POST:");
//	fb($postdata, true);
//
//	$_post = $htmldata;
//	$htmldata = array();
//
//	while (isset($_FILES['uploadedfile' . $cnt])) {
//		fb("HTML multiple POST");
//		$moved = move_uploaded_file($_FILES['uploadedfile' . $cnt]['tmp_name'], $upload_path . $_FILES['uploadedfile' . $cnt]['name']);
//		fb("moved:" . $moved . "  " . $_FILES['uploadedfile' . $cnt]['name']);
//		if ($moved) {
//			$name = $_FILES['uploadedfile' . $cnt]['name'];
//			$file = $upload_path . $name;
//			$type = getFileType($file); // flat out returns extension
//			$notimage = false;
//			try {
//				list($width, $height) = getimagesize($file);
//			} catch (Exception $e) {
//				$width = 0;
//				$height = 0;
//				$notimage = true;
//			}
//			fb("file: " . $file);
//
//			$_post['file'] = $file;
//			$_post['name'] = $name;
//			if (!$notimage) {
//				$_post['width'] = $width;
//				$_post['height'] = $height;
//			}
//			$_post['type'] = $type;
//			$_post['size'] = filesize($file);
//			$_post['additionalParams'] = $postdata;
//			fb($_post, true);
//
//			$htmldata[$cnt] = $_post;
//		} elseif (strlen($_FILES['uploadedfile' . $cnt]['name'])) {
//			$htmldata[$cnt] = array("ERROR" => "File could not be moved: " . $_FILES['uploadedfile' . $cnt]['name']);
//		}
//		$cnt++;
//	}
//	fb("HTML multiple POST done:");
//	foreach ($htmldata as $key => $value) {
//		fb($value, true);
//	}
//}
?>

<textarea style="width:600px; height:150px;"><?php print $data; ?></textarea>
