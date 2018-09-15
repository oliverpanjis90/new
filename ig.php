<?php
error_reporting(0);
class curl {
	var $ch, $agent, $error, $info, $cookiefile, $savecookie;	
	function curl() {
		$this->ch = curl_init();
		curl_setopt ($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36 OPR/54.0.2952.54');
		curl_setopt ($this->ch, CURLOPT_HEADER, 1);
		curl_setopt ($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($this->ch, CURLOPT_FOLLOWLOCATION,true);
		curl_setopt ($this->ch, CURLOPT_TIMEOUT, 30);
		curl_setopt ($this->ch, CURLOPT_CONNECTTIMEOUT,30);
	}
	function header($header) {
		curl_setopt ($this->ch, CURLOPT_HTTPHEADER, $header);
	}
	function proxy($sock) {
		curl_setopt ($this->ch, CURLOPT_HTTPPROXYTUNNEL, true); 
		curl_setopt ($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4); 
		curl_setopt ($this->ch, CURLOPT_PROXY, $sock);
	}
	function post($url, $data) {
		curl_setopt($this->ch, CURLOPT_POST, 1);	
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
		return $this->getPage($url);
	}
	function data($url, $data, $hasHeader=true, $hasBody=true) {
		curl_setopt ($this->ch, CURLOPT_POST, 1);
		curl_setopt ($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
		return $this->getPage($url, $hasHeader, $hasBody);
	}
	function get($url, $hasHeader=true, $hasBody=true) {
		curl_setopt ($this->ch, CURLOPT_POST, 0);
		return $this->getPage($url, $hasHeader, $hasBody);
	}	
	function getPage($url, $hasHeader=true, $hasBody=true) {
		curl_setopt($this->ch, CURLOPT_HEADER, $hasHeader ? 1 : 0);
		curl_setopt($this->ch, CURLOPT_NOBODY, $hasBody ? 0 : 1);
		curl_setopt ($this->ch, CURLOPT_URL, $url);
		$data = curl_exec ($this->ch);
		$this->error = curl_error ($this->ch);
		$this->info = curl_getinfo ($this->ch);
		return $data;
	}
}

function fetchCurlCookies($source) {
	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $source, $matches);
	$cookies = array();
	foreach($matches[1] as $item) {
		parse_str($item, $cookie);
		$cookies = array_merge($cookies, $cookie);
	}
	return $cookies;
}

function string($length = 15)
{
	$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function nama($length = 15)
{
	$characters = 'abcdefghijklmnopqrstuvwxyz';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function fetch_value($str,$find_start,$find_end) {
	$start = @strpos($str,$find_start);
	if ($start === false) {
		return "";
	}
	$length = strlen($find_start);
	$end    = strpos(substr($str,$start +$length),$find_end);
	return trim(substr($str,$start +$length,$end));
}

function loop ($socks) {

	$curl = new curl();
	$curl->proxy($socks);
	$register = $curl->get('https://www.instagram.com/accounts/emailsignup/');

	$cookies = fetchCurlCookies($register);
	$csrftoken = $cookies['csrftoken'];
	$mid = $cookies['mid'];

	if ($register) {

		$headers = array();
		$headers[] = "accept-language: en-US,en;q=0.9";
		$headers[] = "content-type: application/x-www-form-urlencoded";
		$headers[] = 'cookie: mid='.$mid.'; mcd=3; shbid=13734; rur=FTW; csrftoken='.$csrftoken.'; csrftoken='.$csrftoken.';';
		$headers[] = "referer: https://www.instagram.com/accounts/emailsignup/";
		$headers[] = "user-agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36 OPR/54.0.2952.54";
		$headers[] = "x-csrftoken: ".$csrftoken."";
		$curl->header($headers);

		$page_api = file_get_contents('https://randomuser.me/api/');
		$password = string(8);
		$username = string(10);
		$domain = array ('@gmail.com','@yahoo.com','@mail.com','@yandex.com','@gmx.de','@t-online.de','@yahoo.co.id','@yahoo.co.uk');
		$random = rand(0,7);
		$email  = string(11).$domain[$random];
		$name = fetch_value($page_api, '"first":"','"');


		$page_register = $curl->post('https://www.instagram.com/accounts/web_create_ajax/', 'email='.$email.'&password='.$password.'&username='.$username.'&first_name='.$name.'&seamless_login_enabled=1&tos_version=row&opt_into_one_tap=false');

		if (strpos($page_register, '"account_created": true')) {
			echo "SUCCESS| ".$socks." | ".$email." | ".$username." | ".$password."\n";
			$data =  "SUCCESS| ".$socks." | ".$email." | ".$username." | ".$password."\r\n";
			$fh = fopen("success.txt", "a");
			fwrite($fh, $data);
			fclose($fh);
			flush();
			ob_flush();
		} elseif(strpos($page_register, '"account_created": false')) {
			$ip = fetch_value($page_register, '"ip": ["','"]');
			echo "FAILED | ".$socks." | ".$email." | ".$username." | ".$password." | ".$ip."\n";
			flush();
			ob_flush();
		}

	} else {
		echo "SOCKS DIE | ".$socks."\n";
		flush();
		ob_flush();
	}
}

echo "INSTAGRAM ACCOUNT CREATOR BY: YUDHA TIRA PAMUNGKAS\n";
sleep(1);
echo "Name File Socks (ex: socks.txt): ";
$namefile = trim(fgets(STDIN));
sleep(1);
echo "Please Wait";
sleep(1);
echo ".";
sleep(1);
echo ".";
sleep(1);
echo ".\n";
$file = file_get_contents($namefile) or die ("File Not Found\n");
$socks = explode("\r\n",$file);
$total = count($socks);
echo "Total Socks: ".$total."\n";

foreach ($socks as $value) {
	loop($value);
}

?>
