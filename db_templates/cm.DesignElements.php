<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class DesignAPI {

	var $iconpath = "";
	function __construct() {
		global $CONFIG;
		if(!isset($CONFIG)) require_once "../include/cm.CommonFunctions.php";

		$this->iconpath = $CONFIG['icons'];
	}
	function getAvailableForms() {
		global $CONFIG;
		if(!class_exists("SQL")) require_once "../include/mysqlport.php";
		$return = array();
		$sql = new SQL("reader", true);
		$sql->doQuery("SELECT * FROM ".$CONFIG['db_elementstable']." WHERE `form`<>''", "object");
		while(($row = $sql->getNextRow("array")) != null) {
			/*
				// old_admin
				if(in_array($row["form"], $CONFIG['active_forms'])) array_push($return, $row);
			*/
			array_push($return, $row);
		}
		return $return;
	}
	function getIcon($filename) {
		return $this->iconpath.$filename;
	}
	function getAvailableFormChoiceHTML() {
		$forms = $this->getAvailableForms();
		$html = "<div id=\"formlist\" class=\"choices\">\n";
		//$eval = "<script type=\"text/javascript\">var choiceDiv = dojo.byId('formlist');var forms = dojo.query('a.formChoice', choiceDiv);";
		foreach($forms as $f) {
			$html .= "<div class=\"line\" onclick=\"javascript:selectForm('".$f['form']."');\" style=\"background-image:url(".$this->getIcon($f['icon']).")\"".
			" onmouseover=\"this.style.backgroundColor='lightBlue'\" onmouseout=\"this.style.backgroundColor=''\">" .
			"<div href=\"javascript:selectForm('".$f['form']."');\" title=\"".ucfirst($f['form'])."\" class=\"formChoice\">".
				$f['title']."<div class=\"description\">".$f['description']."</div></div></div>";
		}
		return $html;
	}
	
}

class DesignElement {
	public $id;
	public $form;
	public $description;
	public $elements;
	public $help;
	public $modified;

	function loadById($id) {
		if(!class_exists("SQL")) require_once "../include/mysqlport.php";
		$this->id = $id;
		$sql = new SQL("reader", true);
		$data = $sql->doQueryGetFirstRow("SELECT * FROM ".$CONFIG['db_elementstable']." WHERE `id`='$id'", "object");

		$this->form = $data->form;
		$this->description = $data->description;
		$this->elements = $data->elements;
		$this->help = $data->help;
		$this->modified = $data->modified;
		//$this->attachId = $data->attachId;
	}
	function loadByName($form) {
		if(!class_exists("SQL")) require_once "../include/mysqlport.php";
		$this->form = $form;
		$sql = new SQL("reader", true);
		$data = $sql->doQueryGetFirstRow("SELECT * FROM ".$CONFIG['db_elementstable']." WHERE `form`='$form'", "object");

		$this->form = $data->form;
		$this->description = $data->description;
		$this->elements = $data->elements;
		$this->help = $data->help;
		$this->modified = $data->modified;
		//$this->attachId = $data->attachId;
	}
	function create($form, $description,$help, $elementsarray) {
		if(!class_exists("SQL")) require_once "../include/mysqlport.php";
		$sql = new SQL("admin");
		$msg = $sql->doQuery("INSERT INTO ".$CONFIG['db_elementstable']." (".
			"`id` ,`form`, `description` , `help` ,".$CONFIG['db_elementstable']." ,`modified`".
			") VALUES (".
			"NULL, '$form', '$description', '$help', '".join("|", $elementsarray)."',   NOW( )".
			");");

		if($sql->errMessage() != "") {
			$msg = $sql->errMessage();
			if(strstr($msg, "Duplicate") !== false) {
				$_SESSION['error'] = "Type eksisterer";
				return false;
			}
		}
		
		$this->form = $form;
		$this->description = $description;
		$this->help = $help;
		$this->elements = $elementsarray;

		$sql->doQuery("SELECT * FROM ".$CONFIG['db_elementstable']." ORDER BY modified DESC LIMIT 3");

		$r = $sql->getNextRow("object");
		$this->id = $r->id;
		return true;
	}
	function updateDb() {
		if(!class_exists("SQL")) require_once "../include/mysqlport.php";
		$sql = new SQL("admin");
		$sql->doQuery("UPDATE ".$CONFIG['db_elementstable']."".
			" SET `help` = '".$this->help."',".
			"`form` = '".$this->form."',".
			"`description` = '".$this->description."',".
			"`help` = '".$this->help."',".
			"`elements` = '".join("|", $this->elements)."',".
			" WHERE `id` = '".$this->id."'LIMIT 1 ;");
	}


}
?>
