<?php
/**
 * Serv00 自动化脚本
 * Author: iBugCode
 * Github: https://github.com/iBugCode/Serv00Auto.git
 */

header('Content-Type: text/html; charset=utf-8');

// DevilWEB webpanel 地址, 如 https://panel5.serv00.com 不要加后面的 /
define('DEVIL_WEB_URL', 'https://panel5.serv00.com');
// Serv00 登陆账号
define('USERNAME', 'you-name');
// Serv00 登陆密码
define('PASSWORD', 'you-password');

// 是否发送邮件, 如果为 false 则不发送
define('IS_MAIL', false);

// 只测试过 outlook 其它邮箱可能不支持
define('SMTP_SERVER', 'smtp-mail.outlook.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'you-name@outlook.com');
define('SMTP_PASSWORD', 'you-password');
define('SMTP_FROM', 'SERV00自动');
define('SMTP_TO', 'you-name@outlook.com');

class serv00{

    protected $httpCode = 0;
    protected $body = '';

    protected $smtpSocket = null;

    protected $cookieJar = null;

    protected function smtpGetResponse() {
        $data = "";
        while ($str = fgets($this->smtpSocket, 512)) {
            $data .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        return $data;
    }

    /**
     * 发送邮件
     *
     * @param [type] $subject
     * @param [type] $message
     * @param string $headers
     * @return void
     */
    protected function sendSMTPMail($subject, $message, $headers = '') {

        $this->smtpSocket = fsockopen(SMTP_SERVER, SMTP_PORT, $errno, $errstr, 30);
        if (!$this->smtpSocket) {
            echo "Could not connect to SMTP host: $errno - $errstr";
            return false;
        }
    
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '220') {
            echo "Connection error: $response";
            return false;
        }
    
        fwrite($this->smtpSocket, 'EHLO '.SMTP_SERVER."\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '250') {
            echo "EHLO error: $response";
            return false;
        }
    
        fwrite($this->smtpSocket, "STARTTLS\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '220') {
            echo "STARTTLS error: $response";
            return false;
        }
    
        stream_socket_enable_crypto($this->smtpSocket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
        fwrite($this->smtpSocket, 'EHLO '.SMTP_SERVER."\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '250') {
            echo "EHLO after STARTTLS error: $response";
            return false;
        }
    
        fwrite($this->smtpSocket, "AUTH LOGIN\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '334') {
            echo "AUTH LOGIN error: $response";
            return false;
        }
    
        fwrite($this->smtpSocket, base64_encode(SMTP_USERNAME) . "\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '334') {
            echo "Username error: $response";
            return false;
        }
    
        fwrite($this->smtpSocket, base64_encode(SMTP_PASSWORD) . "\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '235') {
            echo "Password error: $response";
            return false;
        }
    
        fwrite($this->smtpSocket, "MAIL FROM: <".SMTP_USERNAME.">\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '250') {
            echo "MAIL FROM error: $response";
            return false;
        }
    
        fwrite($this->smtpSocket, "RCPT TO: <".SMTP_TO.">\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '250') {
            echo "RCPT TO error: $response";
            return false;
        }
    
        fwrite($this->smtpSocket, "DATA\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '354') {
            echo "DATA error: $response";
            return false;
        }
    
        $headers .= "From: ".SMTP_FROM."<".SMTP_USERNAME.">\r\n";
        $headers .= "To: <".SMTP_TO.">\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
        fwrite($this->smtpSocket, "$headers\r\n\r\n$message\r\n.\r\n");
        $response = $this->smtpGetResponse();
        if (substr($response, 0, 3) != '250') {
            echo "Message body error: $response";
            return false;
        }
    
        fwrite($this->smtpSocket, "QUIT\r\n");
        fclose($this->smtpSocket);
    
        return true;
    }

    /**
     * curl请求
     */
    protected function whttp($url, $data=null){
        $curl = curl_init();

        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_HTTPHEADER => [
                'accept-language: en-US,en;q=0.9',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'referer: '.DEVIL_WEB_URL.'/login/'
            ],
        ];

        if( $data != null ){
            array_push($opt[CURLOPT_HTTPHEADER], 'content-type: application/x-www-form-urlencoded');
            
            $opt[CURLOPT_CUSTOMREQUEST] = 'POST';
            $opt[CURLOPT_POSTFIELDS] = http_build_query($data);
        }

        curl_setopt_array($curl, $opt);
        
        $this->body = curl_exec($curl);
        $this->httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        
        curl_close($curl);

        if( $err ){
            return false;
        }

        return true;

    }


    public function run(){

        // 使用内存保存 cookie
        $this->cookieJar = fopen('php://memory', 'w+');

        // 获取 CSRF Token
        if( !$this->whttp(DEVIL_WEB_URL.'/login/') ){
            fclose($this->cookieJar);
            echo 'CURL Error 1!', PHP_EOL;
            return false;
        }

        if( $this->httpCode != 200 ){
            fclose($this->cookieJar);
            echo 'HTTP CODE Error ', $this->httpCode, PHP_EOL;
            return false;
        }
        
        if( !preg_match('/name="csrfmiddlewaretoken" value="([^"]+)"/i', $this->body, $matchs) ){
            fclose($this->cookieJar);
            echo 'CSRF token not found', PHP_EOL;
            return false;
        }

        $csrfmiddlewaretoken = $matchs[1];

        echo 'CSRF Token: ', $csrfmiddlewaretoken, PHP_EOL;

        sleep(2);

        if( !$this->whttp(DEVIL_WEB_URL.'/login/', [
            'csrfmiddlewaretoken' => $csrfmiddlewaretoken,
            'username' => USERNAME,
            'password' => PASSWORD,
            'next' => '/info/history/panel',
        ]) ){
            fclose($this->cookieJar);
            echo 'CURL Error 2!', PHP_EOL;
            return false;
        }

        if( $this->httpCode != 302 ){
            fclose($this->cookieJar);
            echo 'Login Code Error ', $this->httpCode, PHP_EOL;
            return false;
        }

        echo 'Login OK', PHP_EOL;

        // 获取登陆历史数据用于判断是否登陆成功
        if( !$this->whttp(DEVIL_WEB_URL.'/info/history/panel') ){
            fclose($this->cookieJar);
            echo 'CURL Error 3!', PHP_EOL;
            return false;
        }

        fclose($this->cookieJar);

        if( $this->httpCode != 200 ){
            echo 'Login history page Error ', $this->httpCode, PHP_EOL;
            return false;
        }

        if( !preg_match('/<td data-order="([0-9]{10})">[^<]+<\/td>\s*<td>([0-9\.]+)<\/td>/is', $this->body, $matchs) ){
            echo 'CSRF token not found', PHP_EOL;
            return false;
        }

        if( isset( $matchs[1] ) ){

            $ct = intval($matchs[1]);

            $tm = time();

            echo $ct, ' - ', $tm, PHP_EOL;

            if( $ct > ($tm-86400) && $ct < ($tm+86400) ){
                echo 'IP: ', $matchs[2], PHP_EOL;
                IS_MAIL && $this->sendSMTPMail('SERV00保号登陆', '<h1>IP: '. $matchs[2] .' 登陆成功</h1>');
            }
        }
    }
}

$s0 = new serv00();

$s0->run();