<?php

session_start();
/*
 * possible lookups via parameters includes
 * dojo.data.store query similar to
 * query={\"name\":\"foo*\"} // only filenames starting with foo
 * options=[\"expand\",\"showHiddenFiles\",\"dirsOnly\"]
 * path=where/to/list
 */

include "../include/cm.CommonFunctions.php";
include "../include/cm.RequireLogin.php";

header("Cache-control: no-cache");
header("Pragma: no-cache");

$q_string = array();
$hideEntries = array(
	 'fileadminicons',
	 'index.php',
	 'Thumbs',
	 '.htaccess',
	 '.htpasswd'
);

//Define the root directory to use for this service.
//All file lookups are relative to this path.
$rootDir = "../fileadmin/";

//Extract the query, if any.
$query = false;
if (array_key_exists("query", $_GET)) {
	$query = $_GET['query'];
	$query = str_replace("\\\"", "\"", $query);
	$query = json_decode($query, true);
}
//Extract relevant query options.
$queryOptions = json_decode("{}");
$deep = false;
$ignoreCase = false;
if (array_key_exists("queryOptions", $_GET)) {
	$queryOptions = $_GET['queryOptions'];
	$queryOptions = str_replace("\\\"", "\"", $queryOptions);
	$queryOptions = json_decode($queryOptions);
	if (property_exists($queryOptions, "deep")) {
		$deep = $queryOptions->deep;
	}
	if (property_exists($queryOptions, "ignoreCase")) {
		$ignoreCase = $queryOptions->ignoreCase;
	}
}

//Extract non-dojo.data spec config options.
$expand = false;
$dirsOnly = false;
$showHiddenFiles = false;
$options = array();
if (array_key_exists("options", $_GET)) {
	$options = $_GET['options'];
	$options = str_replace("\\\"", "\"", $options);
	$options = json_decode($options);
	if (array_search("expand", $options) > -1) {
		$expand = true;
	}
	if (array_search("dirsOnly", $options) > -1) {
		$dirsOnly = true;
	}
	if (array_search("showHiddenFiles", $options) > -1) {
		$showHiddenFiles = true;
	}
}


//See if a specific file was requested, or if it is just a query for files.
$path = false;
if (array_key_exists("path", $_GET)) {
	$path = $_GET['path'];
}

if (!is_string($path)) {

	$files = array();

	//Handle query for files.  Must try to generate patterns over the query 
	//attributes.
	$patterns = array();
	if (is_array($query)) {
		//Generate a series of RegExp patterns as necessary.
		$keys = array_keys($query);
		$total = count($keys);
		if ($total > 0) {
			for ($i = 0; $i < $total; $i++) {
				$key = $keys[$i];
				$pattern = $query[$key];
				if (is_string($pattern)) {
					$patterns[$key] = patternToRegExp($pattern);
				}
			}
			$files = matchFiles($query, $patterns, $ignoreCase, ".", $rootDir, $deep, $dirsOnly, $expand, $showHiddenFiles);
		} else {
			$files = getAllFiles(".", $rootDir, $deep, $dirsOnly, $expand, $showHiddenFiles);
		}
	} else {
		$files = getAllFiles(".", $rootDir, $deep, $dirsOnly, $expand, $showHiddenFiles);
	}

	$total = count($files);

	//Handle the sorting and paging.
	$sortSpec = false;
	if (array_key_exists("sort", $_GET)) {
		$sortSpec = $_GET['sort'];
		$sortSpec = str_replace("\\\"", "\"", $sortSpec);
		$sortSpec = json_decode($sortSpec);
	}

	if ($sortSpec != null) {
		$comparator = createComparator($sortSpec);
		usort($files, array($comparator, "compare"));
	}

	//Page, if necessary.
	if (array_key_exists("start", $_GET)) {
		$start = $_GET['start'];
		if (!is_numeric($start)) {
			$start = 0;
		}
		$files = array_slice($files, $start);
	}
	if (array_key_exists("count", $_GET)) {
		$count = $_GET['count'];
		if (!is_numeric($count)) {
			$count = $total;
		}
		$files = array_slice($files, 0, $count);
	}

	$result = new stdClass();
	$result->total = $total;
	$result->items = $files;
	header("Content-Type", "text/json");
	print("{}&&" . json_encode($result));
} else {
	//Query of a specific file (useful for fetchByIdentity and loadItem)
	//Make sure the path isn't trying to walk out of the rooted directory
	//As defined by $rootDir in the top of the php script.
	$rootPath = realPath($rootDir);
	$fullPath = realPath($rootPath . "/" . $path);

	if ($fullPath !== false) {
		if (strpos($fullPath, $rootPath) === 0) {
			//Root the path into the tree cleaner.
			if (strlen($fullPath) == strlen($rootPath)) {
				$path = ".";
			} else {
				//Fix the path to relative of root and put back into UNIX style (even if windows).
				$path = substr($fullPath, (strlen($rootPath) + 1), strlen($fullPath));
				$path = str_replace("\\", "/", $path);
			}

			if (file_exists($fullPath)) {
				$arr = explode("/", $path);
				$size = count($arr);

				if ($size > 0) {
					$fName = $arr[$size - 1];
					if ($size == 1) {
						print("Setting path to: .");
						$path = ".";
					} else {
						$path = $arr[0];
					}
					for ($i = 1; $i < ($size - 1); $i++) {
						$path = $path . "/" . $arr[$i];
					}
					$file = generateFileObj($fName, $path, $rootDir, $expand, $showHiddenFiles);
					header("Content-Type", "text/json");
					print("{}&&" . json_encode($file));
				} else {
					header("HTTP/1.0 404 Not Found");
					header("Status: 404 Not Found");
					print("<b>Cannot access file: [" . htmlentities($path) . "]<b>");
				}
			} else {
				header("HTTP/1.0 404 Not Found");
				header("Status: 404 Not Found");
				print("<b>Cannot access file: [" . htmlentities($path) . "]<b>");
			}
		} else {
			header("HTTP/1.0 403 Forbidden");
			header("Status: 403 Forbidden");
			print("<b>Cannot access file: [" . htmlentities($path) . "].  It is outside of the root of the file service.<b>");
		}
	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		print("<b>Cannot access file: [" . htmlentities($path) . "]<b>");
	}
}
///*
//  File Icons - If you want to add your own special file icons use
//  this section below. Each entry relates to the extension of the
//  given file, in the form <extension> => <filename>.
//  These files must be located within the dlf directory.
// */
//$filetypes = array(
//	 'png' => 'jpg.gif',
//	 'jpeg' => 'jpg.gif',
//	 'bmp' => 'jpg.gif',
//	 'jpg' => 'jpg.gif',
//	 'gif' => 'gif.gif',
//	 'zip' => 'archive.png',
//	 'rar' => 'archive.png',
//	 'exe' => 'exe.gif',
//	 'setup' => 'setup.gif',
//	 'txt' => 'text.png',
//	 'htm' => 'html.gif',
//	 'html' => 'html.gif',
//	 'fla' => 'fla.gif',
//	 'swf' => 'swf.gif',
//	 'xls' => 'xls.gif',
//	 'doc' => 'doc.gif',
//	 'sig' => 'sig.gif',
//	 'fh10' => 'fh10.gif',
//	 'pdf' => 'pdf.gif',
//	 'psd' => 'psd.gif',
//	 'rm' => 'real.gif',
//	 'mpg' => 'video.gif',
//	 'mpeg' => 'video.gif',
//	 'mov' => 'video2.gif',
//	 'avi' => 'video.gif',
//	 'eps' => 'eps.gif',
//	 'gz' => 'archive.png',
//	 'asc' => 'sig.gif',
//	 'unknown' => 'text.png'
//);
//
//foreach($_GET as $ident => $value) {
//	$q_string[strtolower($ident)] = strtolower($value);
//}
//if($q_string['dir']) {
//	//check this is okay.
//
//	if(substr($_GET['dir'], -1, 1)!='/') {
//		$_GET['dir'] = $_GET['dir'] . '/';
//	}
//
//	$dirok = true;
//	$dirnames = explode('/', $_GET['dir']);
//	for($di=0; $di<sizeof($dirnames); $di++) {
//
//		if($di<(sizeof($dirnames)-2)) {
//			$dotdotdir = $dotdotdir . $dirnames[$di] . '/';
//		}
//
//		if($dirnames[$di] == '..') {
//			$dirok = false;
//		}
//	}
//
//	if(substr($_GET['dir'], 0, 1)=='/') {
//		$dirok = false;
//	}
//}else {
//	$q_string['dir'] = "";
//	$dirok=true;
//}
//$basedirectory = "../fileadmin/".$q_string['dir']."";
//$basedirectory = $basedirectory . ((ord($basedirectory[strlen($basedirectory)-1]) != ord('/')) ? "/" : "");
//if(isset($q_string['format']) && $q_string['format'] == "json" && $dirok) {
//	$ids = 0;
//	$pad = 0;
//
//	echo "{\n";
//	echo ' "label": "filename",'."\n";
//	echo ' "identifier": "id",'."\n";
//	echo ' "items": ['."\n";
//	echo generateDir($basedirectory, $q_string['sort']);
//	echo "  ]\n";
//	echo "}\n";
//
//
//
//
//
//
//
//}
//
//function getDir($opendir, $sort) {
//	global $hide;
//
//	clearstatcache();
//	if ($handle = opendir($opendir)) {
//
//		if( isset($_GET['ext']) && $_GET['ext'] != ""){
//			$ext=split(",", strtolower($_GET['ext']));
//		}
//
//		while (false !== ($file = readdir($handle))) {
//			//first see if this file is required in the listing
//			if ($file == "." || $file == "..")  continue;
//			$discard = false;
//			for($hi=0;$hi<sizeof($hide);$hi++) {
//				if(strpos($file, $hide[$hi])!==false) {
//					$discard = true;
//				}
//			}
//
//			if($discard) continue;
//			if (@filetype($opendir.$file) == "dir") {
//
//				$n++;
//				if($sort=="date") {
//					$key = @filemtime($opendir.$file) . ".$n";
//				}
//				else {
//					$key = $n;
//				}
//				$dirs[$key] = $file . "/";
//			}
//			else {
//				$accept=true;
//				if(isset($ext)) {
//					echo $ext;
//					$accept = false;
//					$fileext = strtolower(substr($file, strrpos($file, '.')+1));
//					foreach($ext as $extension) if($fileext == $extension) $accept = true;
//
//				}
//				if(!$accept) continue;
//
//				$n++;
//				if($sort=="date") {
//					$key = @filemtime($opendir.$file) . ".$n";
//				}
//				elseif($sort=="size") {
//					$key = @filesize($opendir.$file) . ".$n";
//				}
//				else {
//					$key = $n;
//				}
//				$files[$key] = $file;
//
//				if($displayindex) {
//					if(in_array(strtolower($file), $indexfiles)) {
//						header("Location: $file");
//						die();
//					}
//				}
//			}
//		}
//		closedir($handle);
//	}
//	//sort our files
//	if($sort=="date") {
//		@ksort($dirs, SORT_NUMERIC);
//		@ksort($files, SORT_NUMERIC);
//	}
//	elseif($sort=="size") {
//		@natcasesort($dirs);
//		@ksort($files, SORT_NUMERIC);
//	}
//	else {
//		@natcasesort($dirs);
//		@natcasesort($files);
//	}
//	return array($dirs, $files);
//}
//
//
//function generateDir($dirname, $sort) 
//{
//	global $pad;
//	global $ids;
//	global $filetypes;
//	global $CONFIG;
//
//	$json = "";
//	$pad++;
//	$tabs = "";
//	if($pad != 0) for($i=0;$i<$pad;$i++)$tabs.="\t";
//	list($dirs, $files) = getDir($dirname, $sort);
//
//	if(!isset($_GET['spec']) || $_GET['spec'] == "directories") {
//
//		$arsize = sizeof($dirs);
//		$i = 0;
//		if($dirs) foreach($dirs as $dir) { //($i=0;$i<$arsize;$i++) {
//			$absurl = $CONFIG['fileadmin'] . preg_replace("/\.\.\/fileadmin\//", "", $dirname) . $dir;
//			$json .= $tabs."{\"title\":\"".substr($dir,0,strlen($dir)-1)."\", ".
//			"\"abspath\":\"".$absurl."\", ".
//			"\"id\":\"".$ids++."\", \"type\":\"dir\", \"icon\":\"folder.png\", ".
//			"\"modified\": \"". date ("M d Y h:i:s A", filemtime($dirname.$dir))."\"";
//			if("" != $dir) $json .= ", \"children\": [\n" . generateDir($dirname.$dir, $sort) . $tabs."]}";
//			else $json .= "}";
//			$json .= ($i++ < $arsize-1) ? ",\n" : "\n";
//
//		}
//	}
//	if(!isset($_GET['spec']) || $_GET['spec'] == "files") {
//		$arsize = sizeof($files);
//		if(count($dirs) && $arsize > 0) $json .= $tabs . ",\n";
//		$i = 0;
//		if($files) foreach($files as $file) { //($i=0;$i<$arsize;$i++) {
//			$icon = 'unknown.png';
//			$ext = strtolower(substr($file, strrpos($file, '.')+1));
//			$supportedimages = array('gif', 'png', 'jpeg', 'jpg');
//			if($filetypes[$ext]) {
//				$icon = $filetypes[$ext];
//			}
//			$filename = preg_replace("/\+/", " ", urldecode($file));
//			if(strlen($filename)>29) {
//				$filename = substr($file, 0, 26) . '..';
//			}
//			$size = filesize($dirname.$file);
//			if($size < 1024) $size = $size . "b";
//			else if($size / 1024 > 1024) $size = round($size/1024/1024) . "mb";
//			else $size = round($size/1024) . "kb";
//			$absurl = $CONFIG['fileadmin'] . preg_replace("/\.\.\/fileadmin\//", "", $dirname) . $file;
//			$json .= $tabs."{\"title\":\"". $filename ."\", ".
//			"\"filename\":\"".$file."\", ".
//			"\"abspath\": \"".$absurl . "\"," .
//			"\"id\" : \"".($ids++)."\", \"type\":\"file\", \"icon\":\"$ext\", ".
//			"\"size\":\"".$size."\", ".
//			"\"modified\": \"". date ("M d Y h:i:s A", filemtime($dirname.$file))."\"}";
//			$json .= ($i++ < $arsize-1) ? ",\n" : "\n";
//
//		} // for files loop
//	}
//
//	$pad--;
//	return $json;
//
//}

/**
 *  Helper function to convert a simple pattern to a regular expression for matching.
 * 
 * 	Returns a regular expression object that conforms to the defined conversion rules.
 * 		For example:  
 * 		ca*   -> /^ca.*$/
 * 		*ca*  -> /^.*ca.*$/
 * 		*c\*a*  -> /^.*c\*a.*$/
 * 		*c\*a?*  -> /^.*c\*a..*$/
 * 		and so on.
 *
 * @param pattern: string
 * 		A simple matching pattern to convert that follows basic rules:
 * 			* Means match anything, so ca* means match anything starting with ca
 * 			? Means match single character.  So, b?b will match to bob and bab, and so on.
 *      	\ is an escape character.  So for example, \* means do not treat * as a match, but literal character *.
 *  			To use a \ as a character in the string, it must be escaped.  So in the pattern it should be 
 * 				represented by \\ to be treated as an ordinary \ character instead of an escape.
 */
function patternToRegExp(/* String */$pattern) {
	$rxp = "^";
	$c = "";
	$len = strlen($pattern);
	for ($i = 0; $i < $len; $i++) {
		$c = $pattern[$i];
		switch ($c) {
			case '\\':
				$rxp = $rxp . $c;
				$i++;
				$rxp = $rxp . $pattern[$i];
				break;
			case '*':
				$rxp = $rxp . ".*";
				break;
			case '?':
				$rxp = $rxp . ".";
				break;
			case '$':
			case '^':
			case '/':
			case '+':
			case '.':
			case '|':
			case '(':
			case ')':
			case '{':
			case '}':
			case '[':
			case ']':
				$rxp = $rxp . "\\"; //fallthrough
			default:
				$rxp = $rxp . $c;
		}
	}
	return "(" . $rxp . "$)";
}

/**
 * Function to load all file info from a particular directory.
 *
 * @param $dir The dir to seach from, relative to $rootDir.
 * @param $rootDir The directory where the file service is rooted, used as separate var to allow easier checking and prevention of ../ing out of the tree.
 * @param $recurse Whether or not to deep scan the dir and return all subfiles, or just return the toplevel files.
 * @param $dirsOnly boolean to enote to only return directory names, not filenames.
 * @param $expand boolean to indicate whether or not to inflate all children files along a path/file, or leave them as stubs.
 * @param $showHiddenFiles boolean to indicate to return hidden files as part of the list.
 */
function getAllfiles($dir, $rootDir, $recurse, $dirsOnly, $expand, $showHiddenFiles) {
	//  summary:
	//      A function to obtain all the files in a particular directory (file or dir)
	$files = array();
	$dirHandle = opendir($rootDir . "/" . $dir);
	if ($dirHandle) {
		while ($file = readdir($dirHandle)) {
			if ($file) {
				if ($file != ".." && $file != ".") {
					$path = $dir . "/" . $file;
					$fileObj = generateFileObj($file, $dir, $rootDir, $expand, $showHiddenFiles);
					if ($fileObj === false) // skip hidden entries
						continue;
					if (is_dir($rootDir . "/" . $path)) {
						if ($recurse) {
							if ($showHiddenFiles || $fileObj["name"][0] != '.') {
								$subfiles = getAllfiles($path, $rootDir, $recurse, $dirsOnly, $expand, $showHiddenFiles);
								$length = count($subfiles);
								for ($i = 0; $i < $length; $i++) {
									$files[] = $subfiles[$i];
								}
							}
						}
					}
					if (!$dirsOnly || $fileObj["directory"]) {
						if ($showHiddenFiles || $fileObj["name"][0] !== '.') {
							$files[] = $fileObj;
						}
					}
				}
			}
		}
	}
	closedir($dirHandle);
	return $files;
}

/**
 * Function to generate an associative map of data about a specific file.
 * @param $file The name of the file this object represents.
 * @param $dir The sub-path that contains the file defined by $file
 * @param $rootDir The directory from which to append dir and name to get the full path to the file.
 * @param $expand boolean to denote that if the file is a directory, expand all children in the children attribute 
 *        to a a full object
 * @param $showHiddenFiles boolean to denote if hidden files should be shown in-view or not.
 *
 * @return Associative Map.   The details about the file:
 *  $file["name"] - Returns the shortname of the file.
 *  $file["parentDir"] - Returns the relative path from the service root for the parent directory containing file $file["name"]
 *  $file["path"] - The relative path to the file.
 *  $file["directory"] - Boolean indicator if the file represents a directory.
 *  $file["size"] - The size of the file, in bytes.
 *  $file["modified] - The modified date of the file in milliseconds since Jan 1st, 1970.
 *  $file["children"] - Children files of a directory.  Empty if a standard file.
 */
function generateFileObj($file, $dir, $rootDir, $expand, $showHiddenFiles) {
	//  summary:
	//      Function to generate an object representation of a disk file.
	
	global $CONFIG;

	// globally declared array of entries to skip
	global $hideEntries;
	if (in_array($file, $hideEntries))
		return false;

	$path = $file;
	if ($dir != "." && $dir != "./") {
		$path = $dir . "/" . $file;
	}

	$fullPath = $rootDir . "/" . $path;

	$atts = stat($fullPath);

	$rootPath = realPath($rootDir);
	$resolvedDir = realPath($rootDir . "/" . $dir);
	$resolvedFullPath = realPath($fullPath);

	//Try to normalize down the paths so it does a consistent return.
	if (strcmp($rootPath, $resolvedDir) === 0) {
		$dir = ".";
	} else {
		$dir = substr($resolvedDir, (strlen($rootPath) + 1), strlen($resolvedDir));
		$dir = "./" . str_replace("\\", "/", $dir);
	}
	if (strcmp($rootPath, $resolvedFullPath) === 0) {
		$path = ".";
	} else {
		$path = substr($resolvedFullPath, (strlen($rootPath) + 1), strlen($resolvedFullPath));
		$path = "./" . str_replace("\\", "/", $path);
	}

	$fObj = array();
	$fObj["name"] = $file;
	$fObj["parentDir"] = $dir;
	$fObj["path"] = $path;
	$fObj["directory"] = is_dir($fullPath);
	$fObj["size"] = filesize($fullPath);
	$fObj["modified"] = $atts[9];
	$fObj["extension"] = strtolower(end(explode(".", $file)));
	
	if (is_dir($fullPath)) {
		$children = array();
		$dirHandle = opendir($fullPath);
		while ($cFile = readdir($dirHandle)) {
			if ($cFile) {
				if ($cFile != ".." && $cFile != ".") {
					if ($showHiddenFiles || $cFile[0] != '.') {
						if (!$expand) {
							$children[] = $cFile;
						} else {
							$_f = generateFileObj($cFile, $path, $rootDir, $expand, $showHiddenFiles);
							if ($_f === false) // skip hidden entries
								continue;
							$children[] = $_f;
						}
					}
				}
			}
		}
		closedir($dirHandle);
		$fObj["children"] = $children;
	}
	return $fObj;
}

/**
 * A field comparator class, whose role it is to define which fields on an associaive map to compare on
 * and provide the comparison function to do so.
 */
class FieldComparator {

	var $field;
	var $descending = false;

	/**
	 * Constructor.
	 * @param $f The field of the item to compare.
	 * @param $d Parameter denoting whether it should be ascending or descending.  Default is ascending.
	 */
	function FieldComparator($f, $d) {
		$this->field = $f;
		$this->descending = $d;
	}

	/**
	 * Function to compare file objects A and B on the field defined by $this->field.
	 * @param $fileA The first file to compare.
	 * @param #fileB The second file to compare.
	 */
	function compare($fileA, $fileB) {
		$f = $this->field;
		$a = $fileA[$f];
		$b = $fileB[$f];

		$ret = 0;
		if (is_string($a) && is_string($b)) {
			$ret = strcmp($a, $b);
		} else if ($a > $b || $a === null) {
			$ret = 1;
		} else if ($a < $b || $b === null) {
			$ret = -1;
		}

		if (property_exists($this, "descending") && $this->descending == true) {
			$ret = $ret * -1;
		}

		if ($ret > 0) {
			$ret = 1;
		} else if ($ret < 0) {
			$ret = -1;
		}
		return $ret; //int, {-1,0,1}
	}

}

/**
 * A compound comparator class, whose role it is to sequentially call a set of comparators on two objects and 
 * return the combined result of the comparison.
 */
class CompoundComparator {

	//Comparator chain.
	var $comparators = array();

	/**
	 * Function to compare two objects $a and $b, using the chain of comparators.
	 * @param $a The first object to compare.  
	 * @param $b The second object to compare.
	 * @returns -1, 0, 1.  -1 if a < b, 1 if a > b, and 0 if a = b.
	 */
	function compare($a, $b) {
		$ret = 0;
		$size = count($this->comparators);
		for ($i = 0; $i < $size; $i++) {
			$comp = $this->comparators[$i];
			$ret = $comp->compare($a, $b);
			if ($ret != 0) {
				break;
			}
		}
		return $ret;
	}

	/**
	 * Function to add a comparator to the chain.
	 * @param $comp The comparator to add.
	 */
	function addComparator($comp) {
		$this->comparators[] = $comp;
	}

}

/**
 * A function to create a Comparator class with chained comparators based off the sort specification passed into the store.
 * @param $sortSpec The Sort specification, which is an array of sort objects containing ( attribute: "someStr": descending: true|fase}
 * @returns The constructed comparator.
 */
function createComparator($sortSpec) {
	//Function to construct the class that handles chained comparisons.
	$comparator = new CompoundComparator();
	$size = count($sortSpec);
	for ($i = 0; $i < $size; $i++) {
		$sort = $sortSpec[$i];
		$desc = false;
		if (property_exists($sort, "descending")) {
			$desc = $sort->descending;
		}
		$fileComp = new FieldComparator($sort->attribute, $desc);
		$comparator->addComparator($fileComp);
	}
	return $comparator;
}

/**
 * Function to match a set of queries against a directory and possibly all subfiles.
 * @param query The Query send in to process and test against.
 * @param patterns The set of regexp patterns generated off the query.
 * @param dir the directory to search in.
 * @param recurse Whether or not to recurse into subdirs and test files there too.
 *
 * @return Array.  Returns an array of all matches of the query.
 */
function matchFiles($query, $patterns, $ignoreCase, $dir, $rootDir, $recurse, $dirsOnly, $expand, $showHiddenFiles) {
	$files = array();
	$fullDir = $rootDir . "/" . $dir;

	if ($fullDir != null && is_dir($fullDir)) {

		$dirHandle = opendir($fullDir);
		while ($file = readdir($dirHandle)) {
			if ($file != "." && $file != "..") {
				$item = generateFileObj($file, $dir, $rootDir, $expand, $showHiddenFiles);
				if ($item === false) // skip hidden entries
					continue;
				$keys = array_keys($patterns);
				$total = count($keys);
				for ($i = 0; $i < $total; $i++) {
					$key = $keys[$i];
					$pattern = $query[$key];
					$matched = containsValue($item, $key, $query[$key], $patterns[$key], $ignoreCase);
					if (!$matched) {
						break;
					}
				}
				if ($matched) {
					if (!$dirsOnly || $item["directory"]) {
						if ($showHiddenFiles || $item["name"][0] != '.') {
							$files[] = $item;
						}
					}
				}

				if (is_dir($rootDir . "/" . $item["path"]) && $recurse) {
					if ($showHiddenFiles || $item["name"][0] != '.') {
						$files = array_merge($files, matchFiles($query, $patterns, $ignoreCase, $item["path"], $rootDir, $recurse, $dirsOnly, $expand, $showHiddenFiles));
					}
				}
			}
		}
		closedir($dirHandle);
	}
	return $files;
}

/**
 * Function to handle comparing the value of an attribute on a file item.
 * @param item  The item to examine.
 * @param attr The attribute of the tem to examine.
 * @parma value The value to compare it to.
 * @param rExp A regular Expression pattern object generated off 'value' if any.
 * 
 * @returns boolean denoting if the value was matched or not.
 */
function containsValue($item, $attr, $value, $rExp, $ignoreCase) {
	$matched = false;
	$possibleValue = $item[$attr];
	if ($possibleValue === null && $value === null) {
		$matched = true;
	} else {
		if ($rExp != null && is_string($possibleValue)) {
			if ($ignoreCase) {
				$matched = eregi($rExp, $possibleValue);
			} else {
				$matched = ereg($rExp, $possibleValue);
			}
		} else {
			if ($value != null && $possibleValue != null) {
				$matched = ($value == $possibleValue);
			}
		}
	}
	return $matched;
}

?>
