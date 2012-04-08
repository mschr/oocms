<?php

/**
 * Opens or reuses a connection from pool
 * with specified  role
  Sample usage:

  include_once "mysqlport.php";
  $sql = new SQL("admin");
  $row = $sql->doQueryGetFirstRow("SELECT * FROM `users` WHERE 1", "object");
  if($row == null)
  echo $sql->errMessage();;
  while($row != null)
  {
  echo $row->field;
  $row = $sql->getNextRow([$datatype = PreviousSet|ASSOC]);
  }
 */
class SQL { /* extends DbLayer { */

	var $z = null;
	var $result = null;
	var $num_rows = 0;
	var $dtype = "array";
	var $error = "";
	var $status = "";
	public $conv = null;
	private $lastQuery = "";
	private $persistent = true;
	private $host = "localhost";
	private $reader = "";
	private $reader_pw = "";
	private $admin = "";
	private $admin_pw = "";
	private $dbname = "";
	private $verboseLog = false;

	public function __construct($role, $debug = false) {
		global $CONFIG;
		if (!isset($CONFIG))
			include "config.inc.php";
		$this->host = $CONFIG['db_host'];
		$this->dbname = $CONFIG['db_dbname'];
		$this->reader = $this->admin = $CONFIG['db_username'];
		$this->reader_pw = $this->admin_pw = $CONFIG['db_password'];
		if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'])
			$_SESSION['db_connections']++;

		$this->conv = new Convert();
		if ($role == "reader") {
			$this->connect($this->host, $this->dbname, $this->reader, $this->reader_pw);
			$this->setEncoding();
		} else if ($role == "admin") {
			$this->connect($this->host, $this->dbname, $this->admin, $this->admin_pw);
			$this->setEncoding();
		}
		$this->verboseLog = $debug;
	}

	function setEncoding($e="utf8") {
		mysql_query("SET NAMES $e", $this->z);
		mysql_query("SET CHARACTER SET $e", $this->z);
	}

	function connect($host, $dbname, $user, $pw) {
		if ($this->persistent)
			$z = mysql_pconnect($host, $user, $pw);
		else
			$z = mysql_connect($host, $user, $pw);
		mysql_select_db($dbname) or die(mysql_error());
		if ($z === false) {
			$this->setError(mysql_error());
			$this->z = null;
		} else {
			$this->z = $z;
		}
	}

	function disconnect() {
		if ($this->persistent)
			return;
		mysql_close($this->z);
	}

	function log($msg) {
		if ($this->verboseLog == false)
			return;

		$this->logstring .= (preg_match("/fail/i", $msg) ? "<font color=\"red\">" . $msg . "</font>" : $msg) . "<br>\n";
	}

	function doQuery($query) {
		$this->log("doQuery:<br> " . $query);
		if ($this->z == null)
			return $this->errMessage();
		if ($query == null) {
			$this->setError("No Query!!");
			return $this->errMessage();
		}
		$this->query($query);
		if ($this->result == null) {
			return $this->errMessage();
		}
		return true;
	}

	function doQueryGetFirstRow($query, $dtype = null) {
		if ($this->z == null)
			return null; // $this->errMessage();
		if ($query == null) {
			$this->setError("No Query!!");
			$this->log("doQuery: Failed, no Query!");
			return null; //$this->errMessage();
		}
		$this->query($query);
		$this->log("doQuery: $query");
		if ($this->result == null) {
			$this->log("Empty result..");
			return null;
		}
		if ($dtype == null)
			$dtype = $this->dtype;
		return $this->getNextRow($dtype);
	}

	/** default dtype is associative array
	 * override pr row here or
	 * set as 'object' in doQueryGetFirstRow
	 */
	function getNextRow($dtype = null) {
		if ($this->result == null) {
			$this->log("getNextRow: Nothing on storage and failed, forgot to doQuery?");
			$this->setError("No queries to process");
			return null;
		}
		global $CONFIG;
		if (isset($_SESSION['isLoggedIn']) && $_SESSION["isLoggedIn"])
			$_SESSION['db_rows']++;
		if ($dtype == null)
			$dtype == $this->dtype;
		if ($dtype == "array") {
			$row = mysql_fetch_assoc($this->result);
		} else {
			$row = mysql_fetch_object($this->result);
		}
		if ($row === false) {
			$this->result = null;
			return null;
		}
		$this->log("getNextRow: returns data");
		return $row;
	}

	function query($query) {
		global $CONFIG;
		if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'])
			$_SESSION['db_sets']++;

		$this->lastQuery = $query;
		$this->result = mysql_query($query, $this->z);
		if ($this->result === false) {
			$this->result = null;

			$this->setStatus("Error in execution of $query...");
			$e = mysql_error();
			$this->setError($e);
			return null;
		}
		if (preg_match("/(SELECT)/", $query)) {
			$this->setCount();
			$this->log("query: Processed " . $this->getCount() . " rows");
		}
		else
			$this->num_rows = $this->result;
		$this->setStatus("Query: $query processed.. \n  Result: " . $this->num_rows . " (rows)");
	}

	function setError($msg) {
		$this->error = $msg;
	}

	function setStatus($msg) {
		$this->status = $msg;
	}

	function errMessage() {
		return $this->error;
	}

	function statusMessage() {
		return $this->status;
	}

	function getLastQuery() {
		return $this->lastQuery;
	}

	function info() {
		return mysql_info($this->z);
	}

	function dumpLog() {
		print $this->logstring;
	}

	function setCount() {
		$this->num_rows = mysql_num_rows($this->result);
	}

	function getCount() {
		return $this->num_rows;
	}

}

class Convert {

	private $days = array(
		 'en' => array(
			  'Monday',
			  'Tuesday',
			  'Wednesday',
			  'Thursday',
			  'Friday',
			  'Saturday',
			  'Sunday'
		 ),
		 'da' => array(
			  'Mandag',
			  'Tirsdag',
			  'Onsdag',
			  'Torsdag',
			  'Fredag',
			  'Lørdag',
			  "Søndag"
		 )
	);
	private $months = array(
		 'en' => array(
			  'January',
			  'February',
			  'March',
			  'April',
			  'May',
			  'June',
			  'July',
			  'August',
			  'September',
			  'October',
			  'November',
			  'December'
		 ),
		 'da' => array(
			  'januar',
			  'februar',
			  'marts',
			  'april',
			  'maj',
			  'juni',
			  'juli',
			  'august',
			  'september',
			  'oktober',
			  'november',
			  'december'
		 )
	);

	public function timeToTime($sqlstamp) {
		list($date, $time) = explode(" ", $sqlstamp);
		list($year, $month, $day) = explode("-", $date);
		if ($time != null) {
			list($hour, $minute, $second) = explode(":", $time);
			$ts = mktime($hour, $minute, $second, $month, $day, $year);
		} else {
			$ts = mktime(0, 0, 0, $month, $day, $year);
		}
		return $ts;
	}

	public function timeToDate($sqlstamp, $format, $lc = 'da') {
		list($date, $time) = explode(" ", $sqlstamp);
		list($year, $month, $day) = explode("-", $date);
		if ($time != null) {
			list($hour, $minute, $second) = explode(":", $time);
			$ts = mktime($hour, $minute, $second, $month, $day, $year);
		} else {
			$ts = mktime(0, 0, 0, $month, $day, $year);
		}
		//$date = ($date == null)
		$d = date($format, $ts);
		// trunk correctly, utf8 chars fills to 'char' slots (@strlen)
		$MON = date("F", $ts);
		$MON_NUM = date("n", $ts);
		$DAY = date("l", $ts);
		$DAY_NUM = date("N", $ts);
		$daytrunk = $montrunk = 3;
		if ($lc != 'en') {
			for ($i = 0; $i < 3 - 3 % $daytrunk; $i++) {
				if (ord($this->days[$lc][$DAY_NUM - 1][$i]) > 127) {
					$i++;
					if (ord($this->days[$lc][$DAY_NUM - 1][$i]) > 127) {
						$daytrunk++;
					}
				}
			}
			for ($i = 0; $i < 3 - 3 % $montrunk; $i++) {
				if (ord($this->months[$lc][$MON_NUM - 1][$i]) > 127) {
					$i++;
					if (ord($this->months[$lc][$MON_NUM - 1][$i]) > 127) {
						$montrunk++;
					}
				}
			}
		}
		if (strlen($MON) <= 3) {
			$d = preg_replace("/" . substr($DAY, 0, 3) . "/", substr($this->days[$lc][$DAY_NUM - 1], 0, $daytrunk), $d);
		} else {
			$d = preg_replace("/" . $DAY . "/", $this->days[$lc][$DAY_NUM - 1], $d);
		}
		$d = preg_replace("/" . substr($DAY, 0, 3) . "/", substr($this->days[$lc][$DAY_NUM - 1], 0, $daytrunk), $d);
		if (strlen($MON) <= 3) {
			$d = preg_replace("/" . substr($MON, 0, 3) . "/", substr($this->months[$lc][$MON_NUM - 1], 0, $montrunk), $d);
		} else {
			$d = preg_replace("/" . $MON . "/", $this->months[$lc][$MON_NUM - 1], $d);
		}
		$d = preg_replace("/" . substr($MON, 0, 3) . "/", substr($this->months[$lc][$MON_NUM - 1], 0, $montrunk), $d);
		return $d;
	}

}

?>
