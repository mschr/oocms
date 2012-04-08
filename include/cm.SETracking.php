<?php
if(!class_exists("SQL")) include_once "mysqlport.php";
function logSearchEngine() {
	$sql = new SQL("admin");
	$url = parse_url($_SERVER['HTTP_REFERER']);
	$get = array();
	if(strstr($url['query'], "&amp;")) 
		foreach(explode("&amp;", $url['query']) as $pair) { 
			list($key,$value)=explode("=", $pair); $get[$key]=$value;
		}
	else 
		foreach(explode("&", $url['query']) as $pair) { 
			list($key,$value)=explode("=", $pair); $get[$key]=$value;
		}

	if(!empty($get['q'])) $sw = $get['q'];
	else if(!empty($get['search_word'])) $sw = $get['search_word'];
	else if(!empty($get['p'])) $sw = $get['p'];

	if(isset($sw)) {

		$sw=urldecode($sw);
		$sql->doQuery("INSERT INTO `oocms_setracking` ( `id` , `user_ip` , `engine`, `search_word` , `target` )".
			"VALUES (NULL , '".$_SERVER['REMOTE_ADDR']."', '".$url['host']."', '$sw', '".$_SERVER['QUERY_STRING']."')");
	}
	unset($sql);
}
function searchCloud() {
	$sql = new SQL("reader");
	$sql->doQuery("SELECT search_word from `oocms_setracking` WHERE 1=1");
	while($row = $sql->getNextRow()) {
		$ret[] = $row['search_word'];
	}
	return $ret;
}
function searchCloudByIp() {
	$sql = new SQL("reader");
	$sql->doQuery("SELECT user_ip,search_word from `oocms_setracking` WHERE 1=1 ORDER BY `user_ip`");
	while($row = $sql->getNextRow()) {
		$ret[$row['user_ip']] = $row['search_word'];
	}
	return $ret;
}
function searchCloudByEngine() {
	$sql = new SQL("reader");
	$sql->doQuery("SELECT engine,search_word from `oocms_setracking` WHERE 1=1 ORDER BY `engine`");
	while($row = $sql->getNextRow()) {
		$ret[$row['engine']] = $row['search_word'];
	}
	return $ret;
}
?>
