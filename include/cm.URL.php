<?php

class URL {
	
	var $cookiejar;
	var $domain;
	var $sesskey;
	var $headers = null;
	var $debugging = false;
	var $dumpResponse = false;

	function __construct($domain, $debug = false) {
		$this->debugging = $debug;
		if($this->debugging) echo __CLASS__."::".__FUNCTION__."<br>\n"; 
		$this->cookiejar = dirname(__FILE__) . "/cookiejar.txt";
		$fp = fopen($this->cookiejar,(file_exists($this->cookiejar)?'r':'w'));
		if(!$fp) {
			echo ('The cookie file could not be opened. Make sure this directory has the correct permissions.'."\nNo operations will succeed as the script will not be able to aquire a session.");
			exit();
		}
		fclose($fp);
		$this->domain = $domain;
	}

	function getResponseCookies($header) {
		if($this->debugging) echo __CLASS__."::".__FUNCTION__."<br>\n"; 
		$hdrCookies = array();
		foreach ($header as $key => $value) {

			if (strtolower($key) == 'set-cookie') {

				$hdrCookies = array_merge($hdrCookies, explode("\n", $value));
			}
		}
		//$hdrCookies = explode("\n", $header['Set-Cookie']);
		$cookies = array();

		foreach ($hdrCookies as $cookie) {

			if ($cookie) {

				list($name, $value) = explode('=', $cookie, 2);
				list($value, $domain, $path, $expires) = explode(';', $value);
				$cookies[$name] = array('name' => $name, 'value' => $value);
			}
		}
		if($this->debugging) var_dump($cookies);
		return $cookies;
	}

	function parseRaw($this_response) {
		if($this->debugging) echo __CLASS__."::".__FUNCTION__."<br>\n"; 
		   if (substr_count($this_response, 'HTTP/1.') > 1) { 

			// yet another weird bug. CURL seems to be appending response bodies together
		        $chunks = preg_split('@(HTTP/[0-9]\.[0-9] [0-9]{3}.*\n)@', $this_response, -1, PREG_SPLIT_DELIM_CAPTURE);
		        $this_response = array_pop($chunks);
		        $this_response = array_pop($chunks) . $this_response;
		    }

		    list($response_headers, $response_body) = explode("\r\n\r\n", $this_response, 2);
		    $response_header_lines = explode("\r\n", $response_headers);
			if($this->dumpResponse) {
				echo "\n\n".$response_headers . $response_body."\n\n";
				$this->dumpResponse = false;
			}
		    $http_response_line = array_shift($response_header_lines);
		    //if (preg_match('@^HTTP/[0-9]\.[0-9] 100@',$http_response_line, $matches)) {
		    //    return parseRaw($response_body);
		    //}
		    $response_header_array = array();
		    $response_header_array['status'] = preg_replace('@.*/[^\ ]*\ @', "", $http_response_line);

		    foreach($response_header_lines as $header_line) {

		        list($header,$value) = explode(': ', $header_line, 2);
		        $response_header_array[$header] .= $value;
		    }

			if($this->debugging) {
				echo "\t - Parsed response (HTTP/1.x ".$response_header_array['status'].")[".floor(strlen($response_body)/1024)."kb]<br>\n"; 
				if($response_header_array['status'] == "303 See Other") echo "\t --> ".$response_header_array['Location']."<br>\n";
			}
		    return array($response_header_array, $response_body);

	}
	function setup($isPost, $https) {
		if($this->debugging) echo __CLASS__."::".__FUNCTION__.($isPost?"POST":"")."<br>\n"; 
		$this->sock = curl_init();
		if(!$this->sock) 
			throw new Exception(curl_error());
		curl_setopt($this->sock, CURLOPT_COOKIEJAR, $this->cookiejar);
		curl_setopt($this->sock, CURLOPT_COOKIEFILE, $this->cookiejar);
		curl_setopt($this->sock, CURLOPT_HEADER, true);
		if($this->headers != "") curl_setopt($this->sock, CURLOPT_HTTPHEADER, $this->headers); 
		curl_setopt($this->sock, CURLOPT_POST, $isPost);
		curl_setopt($this->sock, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->sock, CURLOPT_SSL_VERIFYPEER, $https);
		curl_setopt($this->sock, CURLOPT_FOLLOWLOCATION, false);

	}
	function send() {
		if($this->debugging) echo __CLASS__."::".__FUNCTION__."<br>\n"; 
		ob_start(); // collect any output
		$raw = curl_exec($this->sock);
		ob_get_clean();  // erase contents and stop preventing output
		curl_close($this->sock);
		return $this->parseRaw($raw);
	}

	function execute($isPost, $url, $query = "", $dump=false) {
		if($this->debugging) echo __CLASS__."::".__FUNCTION__.($isPost?"POST":"")."<br>\n"; 
		$this->dumpResponse = $dump;
		$isPost = strstr(strtoupper($isPost), "POST");
		$https = strstr(strtolower($url), "https");
		$this->setup($isPost, $https);

		if($query != "" && is_array($query)) {

			$form = "";
			foreach($query as $key=>$value)	$form .= "$key=$value&";

		} else {

			$form = $query;

		}
		if($isPost) {

			curl_setopt($this->sock, CURLOPT_URL, $url);
			curl_setopt($this->sock, CURLOPT_POSTFIELDS, true);
			curl_setopt($this->sock, CURLOPT_POSTFIELDS, $form);
			if($this->debugging) echo "\t".$form."<br>\n";

		}else {
			$url = $url.($form!=""?"?".$form:"");
			curl_setopt($this->sock, CURLOPT_URL, $url);

		}
		if($this->debugging) echo "\t".($isPost ? "POST" : "GET")." $url HTTP/1.1<br>\n\tQuery: payload of ".strlen($form)."b being sent<br>\n";

		return $this->send();
	}

	function parseForm($url) {
		if($this->debugging) echo __CLASS__."::".__FUNCTION__."<br>\n"; 
		//if(!$this->sesskey) throw new Exception("Set sesskey prior to talking with server. ".
		//	"If no login is required, do \$obj->sesskey=true");
		list($h, $body) = $this->execute("GET", $url, "");
		
		require_once 'HtmlFormParser.php';

		$parser =& new HtmlFormParser( $body );
		$result = $parser->parseForms();

		foreach($result as $form) {

			echo "<div style=\"border:2px solid;padding:6px;margin: 0 25%;font-size:0.8em;\">";
			if(!empty($form['form_data']['name']))
				echo $form['form_data']['name'].":<br/>";

			echo "<div> &nbsp; Action: ".$form['form_data']['action']."</div>";
			echo "<div> &nbsp; Method: ".$form['form_data']['method']."</div>";
			echo "<hr>\n\n\n";
			foreach($form['form_elemets'] as $key=>$element) {

				echo "<div> &nbsp; &nbsp;";
				echo "\"$key\" => \"".$element['value']."\", // ".$element['type'];
				echo "</div>";

			}
			echo 'oneliner:<br><p>?';
			foreach($form['form_elemets'] as $key=>$element) {
				echo "$key=".$element['value']."&\n";
			}
			echo '</p>';
			echo "</div>";
		}
		echo $body;
	}
}
/* NOTE, use accessible file_path for cookies */

/* SAMPLE: GET-method på 'Opret Forum', hvorefter form udskrives 
$method = false; // true=post
$user="123";
$pass="qwerty";
$form = array(
	"add"=>"forum",
	"type"=>"",
	"course"=>"27",
	"section"=>"1",
	"return"=>"0"
);
$sock = new moodleSock($domain);
$sock->login($user,$pass);

list($headers,$response) =
	$sock->execute($method, "/course/modedit.php", $query);
moodleSock::parseForm($response);
*/



/* keeper && sample login
echo "<textarea cols=80 rows=20>";
for($i = 50; $i < 53; $i++) {

	$form['name'] = "Forum $i";
	$form['intro'] = "Forum $i";
	list($h, $response) = execute($form, $domain, "/course/modedit.php", true);
	echo "$i,\"".trim($h['Location'])."\",\"Forum $i\",,\n";
}
echo "</textarea>";
*/
//$s = new moodleSocket("elsa.moodle.aau.dk");
//$s->login("mschr", "!HUMmes81");
//$s->execute(true, "/", "testvar1=truoneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>oneliner:<br><p>&1");
?>
