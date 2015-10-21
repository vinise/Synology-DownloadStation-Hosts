<?php
/* 
* @author: mcampbell - Synology Forums
* 
* created 01/11/2013
* credits to metter with rapidgator host file
*/

class SynoFileHostingUptobox {
	private $Url;
	private $Username;
	private $Password;
	private $HostInfo;
	private $UPTO_COOKIE_JAR = '/tmp/uptobox.cookie';
	private $LOGIN_URL = 'https://login.uptobox.com/logarithme';
		
	public function __construct($Url, $Username, $Password, $HostInfo) {
		$this->Url = $Url;
		$this->Username = $Username;
		$this->Password = $Password;
		$this->HostInfo = $HostInfo;
	}
	
	public function Verify() {
		return $this->performLogin();
	}
	
	public function GetDownloadInfo($ClearCookie) {
		if($this->performLogin() === LOGIN_FAIL) {
			return array(DOWNLOAD_ERROR => ERR_FILE_NO_EXIST);
		}
		return $this->getPremiumDownloadLink();
	}
	
	private function performLogin() {
		$postData = array(
			'op'=>'login',
			'redirect'=>'http%3A%2F%2Fuptobox.com%2F',
			'login'=>$this->Username,
			'password'=>$this->Password
		);
		$postData = http_build_query($postData);

		$curlOpt = array(
			CURLOPT_POSTFIELDS => $postData,
			CURLOPT_SSL_VERIFYPEER => FALSE,
			CURLOPT_HEADER => TRUE,
			CURLOPT_COOKIEJAR => $this->UPTO_COOKIE_JAR,
			CURLOPT_USERAGENT => DOWNLOAD_STATION_USER_AGENT,
			CURLOPT_RETURNTRANSFER => TRUE
		);

		$ch = curl_init($this->LOGIN_URL);
		curl_setopt_array($ch, $curlOpt);
		$loginInfo = curl_exec($ch);
		curl_close($ch);
		if (FALSE !== $loginInfo && file_exists($this->UPTO_COOKIE_JAR)) {
			$cookieData = file_get_contents ($this->UPTO_COOKIE_JAR);
			if(strpos($cookieData,'xfss') !== false) {
				return USER_IS_PREMIUM;
			} else {
				return LOGIN_FAIL;
			}
		}
		return LOGIN_FAIL;
	}
	
	private function getPremiumDownloadLink() {
		$curlOpt = array(
			CURLOPT_USERAGENT => DOWNLOAD_STATION_USER_AGENT,
			CURLOPT_HEADER => TRUE,
			CURLOPT_COOKIEFILE => $this->UPTO_COOKIE_JAR,
			CURLOPT_RETURNTRANSFER => true
		);

		$ch = curl_init($this->Url);
		curl_setopt_array($ch, $curlOpt);
		curl_exec($ch);
		$info = curl_getinfo($ch);
		$return_code = $info['http_code'];
		curl_close($ch);

		if ($return_code === 301 || $return_code === 302) {
			return array(DOWNLOAD_URL => $info['redirect_url']);
		}else{
			return array(DOWNLOAD_ERROR => ERR_FILE_NO_EXIST);
		}
	}
}
