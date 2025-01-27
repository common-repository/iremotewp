<?php
final class DropboxUploader {
    /**
     * Certificate Authority Certificate source types
     */
    const CACERT_SOURCE_SYSTEM = 0;
    const CACERT_SOURCE_FILE   = 1;
    const CACERT_SOURCE_DIR    = 2;
    /**
     * Dropbox configuration
     */
    const DROPBOX_UPLOAD_LIMIT_IN_BYTES = 314572800;
    const HTTPS_DROPBOX_COM_HOME        = 'https://www.dropbox.com/home';
    const HTTPS_DROPBOX_COM_LOGIN       = 'https://www.dropbox.com/login';
    const HTTPS_DROPBOX_COM_LOGINACTION = 'https://www.dropbox.com/ajax_login';
    const HTTPS_DROPBOX_COM_UPLOAD      = 'https://dl-web.dropbox.com/upload';
    const HTTPS_DROPBOX_COM_LOGOUT      = 'https://www.dropbox.com/logout';
    /**
     * DropboxUploader Error Flags and Codes
     */
    const FLAG_DROPBOX_GENERIC        = 0x10000000;
    const FLAG_LOCAL_FILE_IO          = 0x10010000;
    const CODE_FILE_READ_ERROR        = 0x10010101;
    const CODE_TEMP_FILE_CREATE_ERROR = 0x10010102;
    const CODE_TEMP_FILE_WRITE_ERROR  = 0x10010103;
    const FLAG_PARAMETER_INVALID      = 0x10020000;
    const CODE_PARAMETER_TYPE_ERROR   = 0x10020101;
    const CODE_FILESIZE_TOO_LARGE     = 0x10020201;
    const FLAG_REMOTE                 = 0x10040000;
    const CODE_CURL_ERROR             = 0x10040101;
    const CODE_LOGIN_ERROR            = 0x10040201;
    const CODE_UPLOAD_ERROR           = 0x10040401;
    const CODE_SCRAPING_FORM          = 0x10040801;
    const CODE_SCRAPING_LOGIN         = 0x10040802;
    const CODE_CURL_EXTENSION_MISSING = 0x10080101;
    private $email;
    private $password;
    private $caCertSourceType = self::CACERT_SOURCE_SYSTEM;
    private $caCertSource;
    private $loggedIn = FALSE;
    private $cookies = array();

    /**
     * Constructor
     *
     * @param string $email
     * @param string $password
     * @throws Exception
     */
    public function __construct($email, $password) {
        // Check requirements
        if (!extension_loaded('curl')) {
            throw new Exception('DropboxUploader requires the cURL extension.', self::CODE_CURL_EXTENSION_MISSING);
        }

        if (empty($email) || empty($password)) {
            throw new Exception((empty($email) ? 'Email' : 'Password') . ' must not be empty.', self::CODE_PARAMETER_TYPE_ERROR);
        }

        $this->email    = $email;
        $this->password = $password;
    }

    public function setCaCertificateDir($dir) {
        $this->caCertSourceType = self::CACERT_SOURCE_DIR;
        $this->caCertSource     = $dir;
    }

    public function setCaCertificateFile($file) {
        $this->caCertSourceType = self::CACERT_SOURCE_FILE;
        $this->caCertSource     = $file;
    }

    public function upload($source, $remoteDir = '/', $remoteName = NULL) {
        if (!is_file($source) or !is_readable($source)) {
            throw new Exception("File '$source' does not exist or is not readable.", self::CODE_FILE_READ_ERROR);
        }

        $filesize = filesize($source);
        if ($filesize < 0 or $filesize > self::DROPBOX_UPLOAD_LIMIT_IN_BYTES) {
            throw new Exception("File '$source' too large ($filesize bytes).", self::CODE_FILESIZE_TOO_LARGE);
        }

        if (!is_string($remoteDir)) {
            throw new Exception("Remote directory must be a string, is " . gettype($remoteDir) . " instead.", self::CODE_PARAMETER_TYPE_ERROR);
        }

        if (is_null($remoteName)) {
            # intentionally left blank
        } else if (!is_string($remoteName)) {
            throw new Exception("Remote filename must be a string, is " . gettype($remoteDir) . " instead.", self::CODE_PARAMETER_TYPE_ERROR);
        }

        if (!$this->loggedIn) {
            $this->login();
        }

        $data       = $this->request(self::HTTPS_DROPBOX_COM_HOME);
        $file       = $this->curlFileCreate($source, $remoteName);
        $token      = $this->extractFormValue($data, 't');
        $subjectUid = $this->extractFormValue($data, '_subject_uid');

        	if($token){
	        	$this->DBfilename		= $remoteName;
	        	$this->DBupload_id		= md5(time());
	        	$this->DBfiletotal		= $filesize;
	        	$this->DBsk				= get_option( 'irem_verify_key' );
        	}

        $postData = array(
            'plain'        => 'yes',
            'file'         => $file,
            'dest'         => $remoteDir,
            't'            => $token,
            '_subject_uid' => $subjectUid,
            'mtime_utc'    => filemtime($source),
        );

        $data     = $this->request(self::HTTPS_DROPBOX_COM_UPLOAD, $postData);
        if (strpos($data, 'HTTP/1.1 302 FOUND') === FALSE) {
            throw new Exception('Upload failed!', self::CODE_UPLOAD_ERROR);
        }
    }

    private function curlFileCreate($source, $remoteName) {
        if (function_exists('curl_file_create')) {
            return curl_file_create($source, NULL, $remoteName);
        }

        if ($remoteName !== NULL) {
            $source .= ';filename=' . $remoteName;
        }

        return '@' . $source;
    }

    public function uploadString($string, $remoteName, $remoteDir = '/') {
        $exception = NULL;

        $file = tempnam(sys_get_temp_dir(), 'DBUploadString');
        if (!is_file($file)) {
            throw new Exception("Can not create temporary file.", self::CODE_TEMP_FILE_CREATE_ERROR);
        }

        $bytes = file_put_contents($file, $string);
        if ($bytes === FALSE) {
            unlink($file);
            throw new Exception("Can not write to temporary file '$file'.", self::CODE_TEMP_FILE_WRITE_ERROR);
        }

        try {
            $this->upload($file, $remoteDir, $remoteName);
        } catch (Exception $exception) {
            # intentionally left blank
        }

        unlink($file);

        if ($exception) {
            throw $exception;
        }
    }

    private function login() {
        $data  = $this->request(self::HTTPS_DROPBOX_COM_LOGIN);
        $token = $this->extractTokenFromLoginForm($data);

        $postData = array(
            'login_email'    => (string) $this->email,
            'login_password' => (string) $this->password,
            't'              => $token
        );
        $data     = $this->request(self::HTTPS_DROPBOX_COM_LOGINACTION, http_build_query($postData));

        if (stripos($data, '{"status": "OK",') === FALSE) {
            throw new Exception('Login unsuccessful.', self::CODE_LOGIN_ERROR);
        }

        $this->loggedIn = TRUE;
    }

    private function logout() {
        $data = $this->request(self::HTTPS_DROPBOX_COM_LOGOUT);

        if (!empty($data) && strpos($data, 'HTTP/1.1 302 FOUND') !== FALSE) {
            $this->loggedIn = FALSE;
        }
    }

    private function request($url, $postData = NULL) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (string) $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

        if($postData['t']){
  			curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        	curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array(&$this, '__progressCallback'));
        }

        switch ($this->caCertSourceType) {
            case self::CACERT_SOURCE_FILE:
                curl_setopt($ch, CURLOPT_CAINFO, (string) $this->caCertSource);
                break;
            case self::CACERT_SOURCE_DIR:
                curl_setopt($ch, CURLOPT_CAPATH, (string) $this->caCertSource);
                break;
        }
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if (NULL !== $postData) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        // Send cookies
        $rawCookies = array();
        foreach ($this->cookies as $k => $v)
            $rawCookies[] = "$k=$v";
        $rawCookies = implode(';', $rawCookies);
        curl_setopt($ch, CURLOPT_COOKIE, $rawCookies);

        $data  = curl_exec($ch);
        $error = sprintf('Curl error: (#%d) %s', curl_errno($ch), curl_error($ch));
        curl_close($ch);

        if ($data === FALSE) {
            throw new Exception($error, self::CODE_CURL_ERROR);
        }

        // Store received cookies
        preg_match_all('/Set-Cookie: ([^=]+)=(.*?);/i', $data, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $this->cookies[$match[1]] = $match[2];
        }

        return $data;
    }

    private function extractFormValue($html, $name) {
        $action  = self::HTTPS_DROPBOX_COM_UPLOAD;
        $pattern = sprintf(
            '/<form [^>]*%s[^>]*>.*?(?:<input [^>]*name="%s" [^>]*value="(.*?)"[^>]*>).*?<\/form>/is'
            , preg_quote($action, '/')
            , preg_quote($name, '/')
        );
        if (!preg_match($pattern, $html, $matches)) {
            throw new Exception(sprintf("Cannot extract '%s'! (form action is '%s')", $name, $action), self::CODE_SCRAPING_FORM);
        }

        return $matches[1];
    }

    private function extractTokenFromLoginForm($html) {
        $pattern = '~
            (?J)
            # HEADER cookie: set-cookie: js_csrf=JDAyWg55Y_xItHN_LB8KJ3d5; Domain=
            set-cookie:\ js_csrf=(?P<token>[A-Za-z0-9_-]+);\ Domain=

            # HTML: <input type="hidden" name="t" value="UJygzfv9DLLCS-is7cLwgG7z" />
            |<input\ type="hidden"\ name="t"\ value="(?P<token>[A-Za-z0-9_-]+)"\ />

            # JSON: , "TOKEN": "gCvxU6JVukrW0CUndRPruFvY",
            |,\ "TOKEN":\ "(?P<token>[A-Za-z0-9_-]+)",\
        ~x';
        if (!preg_match($pattern, $html, $matches)) {
            throw new Exception('Cannot extract login CSRF token.', self::CODE_SCRAPING_LOGIN);
        }

        return $matches['token'];
    }

    public function __destruct() {
        if ($this->loggedIn) {
            $this->logout();
        }
    }

	public function __progressCallback(&$download_size, &$downloaded, &$upload_size, &$uploaded){

		if($_SESSION['whenDB']['time'] < time()){

        	$_SESSION['whenDB']['time'] = time() + 15;

					wp_remote_post( IREM_API_URL . 'dropbox/upload.php', array(
						'method' => 'POST',
						'timeout' => 15,
						'sslverify'   => false,
						'redirection' => 5,
						'httpversion' => '1.0',
						'blocking' => true,
						'headers' => array(),
						'body' => array( 'sk'=> $this->DBsk,
										 'filename' => $this->DBfilename,
										 'upload_id' => $this->DBupload_id ,
										 'offset' => $uploaded,
										 'filetotal' => $this->DBfiletotal,
										 'parameter' => 'Dropbox' ),
						'cookies' => array()
					    )
					);

		}
		//return $length;
	}
}
?>