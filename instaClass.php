<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of instaClass
 *
 * @author Daniel Eliasson - joomla at stilero.com
 */
class instaClass {
    var $clientId;
    var $clientSecret;
    var $authCode;
    var $accessToken;
    var $debug;
    var $config;
    var $error;
    var $info;
    var $notice;
    const HTTP_STATUS_OK = '200';
    const ERROR_RETURNURL_NOT_SPECIFIED = '10';
    const ERROR_AUTHTOKENURL_NOT_SPECIFIED = '11';
    const ERROR_URL_NOT_VALID = '12';
    const ERROR_POST_FAIL = '13';
    const ERROR_COMMUNICATION_FAULT = '14';
    const ERROR_OAUTH_EXCEPTION = '50';
    const ERROR_OAUTH_OTHER = '55';
    
    public function __construct($clientId, $clientSecret, $authCode='', $accessToken = '', $config='') {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->authCode = $authCode;
        $this->accessToken = $accessToken;
        $this->config = array_merge(  
            array(
                'authURL'               =>  'https://api.instagram.com/oauth/authorize/',
                'accessTokenURL'        =>  'https://api.instagram.com/oauth/access_token',
                'subscriptionURL'       =>  'https://api.instagram.com/v1/subscriptions',
                'instaBaseURL'          =>  'https://api.instagram.com/v1',
                'redirectURI'           =>  '',
                'fbOauthToken'          =>  '',
                'fbPageID'              =>  '',
                'authScope'             =>  '',
                'authResponseType'      =>  'response_type=token',
                'curlUserAgent'         =>  'instaClass - www.stilero.com',
                'curlConnectTimeout'    =>  20,
                'curlTimeout'           =>  20,
                'curlReturnTransf'      =>  true,
                'curlSSLVerifyPeer'     =>  false,
                'curlFollowLocation'    =>  false,
                'curlProxy'             =>  false,
                'curlProxyPassword'     =>  false,
                'curlEncoding'          =>  false,
                'curlHeader'            =>  false,
                'curlHeaderOut'         =>  true,
                'debug'                 =>  false,
                'eol'                   =>  "<br /><br />"
            ),
        $config
        );
    }
    
    public function authenticate(){
        print "<script> top.location.href='".
                $this->authURL().
                "'</script>";
        return;
    }
    
    private function authURL(){
        $authURL = $this->config['authURL'].
                '?client_id=' . $this->clientId.
                '&redirect_uri='.$this->config['redirectURI'].
                '&response_type=code';
        return $authURL;
    }
    
    public function requestAccessToken(){
        $url = $this->config['accessTokenURL'];
        $postVars = array(
            'client_id'     =>  $this->clientId,
            'client_secret' =>  $this->clientSecret,
            'grant_type'    =>  'authorization_code',
            'redirect_uri'  =>  $this->config['redirectURI'],
            'code'          =>  $this->authCode
        );
        return $this->doQuery($url, $postVars);
    }
    
    public function listSubscriptions(){
        $subUrl = $this->config['subscriptionURL'];
        $postVars = array(
            'client_secret'     => $this->clientSecret,
            'client_id'         => $this->clientId
        );
        $url = $subUrl ."?". http_build_query($postVars);
        return $this->doQuery($url, $postVars, FALSE, $this->HTTPHeader());
    }
    
    public function userFeed($userID='self', $count=''){
        $subURL = $this->config['instaBaseURL'].'/users/'.$userID.'/feed/';
        $postVars = array(
            'access_token'      => $this->accessToken,
            'count'             =>  $count
        );
        print $url = $subURL ."?". http_build_query($postVars);
        return $this->doQuery($url, $postVars, FALSE, $this->HTTPHeader());
    }
    
    public function recentUserMedia($userID='self', $count='60'){
        $subURL = $this->config['instaBaseURL'].'/users/'.$userID.'/media/recent/';
        $postVars = array(
            'access_token'      => $this->accessToken,
            'count'             =>  $count
        );
        $url = $subURL ."?". http_build_query($postVars);
        return $this->doQuery($url, $postVars, FALSE, $this->HTTPHeader());
    }
    
    public function userMediaLiked($userID='self', $count=''){
        $subURL = $this->config['instaBaseURL'].'/users/'.$userID.'/media/liked/';
        $postVars = array(
            'access_token'      => $this->accessToken,
            'count'             =>  $count
        );
        $url = $subURL ."?". http_build_query($postVars);
        return $this->doQuery($url, $postVars, FALSE, $this->HTTPHeader());
    }
    
    public function recentUserImages($userID='self', $count=''){
        $response = $this->recentUserMedia($userID='self', $count='');
        $ResponseJSON = json_decode($response, TRUE);
        $ResponseJSON = json_decode($response);
        $data = $ResponseJSON->data;
        $images = array();
        foreach ($data as $value) {
            $image['thumb'] = $value->images->thumbnail->url;
            $image['full'] = $value->images->standard_resolution->url;
            $image['created'] = $value->created_time;
            $images[] = $image;
        }
        return $images;
    }
    
    public function likedUserImages($userID='self', $count=''){
        $response = $this->userMediaLiked($userID='self', $count='');
        $ResponseJSON = json_decode($response, TRUE);
        $ResponseJSON = json_decode($response);
        $data = $ResponseJSON->data;
        $images = array();
        foreach ($data as $value) {
            $image['thumb'] = $value->images->thumbnail->url;
            $image['full'] = $value->images->standard_resolution->url;
            $image['created'] = $value->created_time;
            $images[] = $image;
        }
        return $images;
    }
    
    private function doQuery($url, $postVars, $post=TRUE, $header = null){
        $ch = curl_init(); 
         curl_setopt_array($ch, array(
            CURLOPT_USERAGENT       =>  $this->config['curlUserAgent'],
            CURLOPT_CONNECTTIMEOUT  =>  $this->config['curlConnectTimeout'],
            CURLOPT_TIMEOUT         =>  $this->config['curlTimeout'],
            CURLOPT_RETURNTRANSFER  =>  $this->config['curlReturnTransf'],
            CURLOPT_SSL_VERIFYPEER  =>  $this->config['curlSSLVerifyPeer'],
            CURLOPT_FOLLOWLOCATION  =>  $this->config['curlFollowLocation'],
            CURLOPT_PROXY           =>  $this->config['curlProxy'],
            CURLOPT_ENCODING        =>  $this->config['curlEncoding'],
            CURLOPT_URL             =>  $url,
            //CURLOPT_POST            =>  $post,
            CURLOPT_HEADER          =>  $this->config['curlHeader'],
            CURLINFO_HEADER_OUT     =>  $this->config['curlHeaderOut'],
        ));
        if($post){
            curl_setopt($ch, CURLOPT_POST, $post);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postVars));
        }
        if($header){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        if ($this->config['curlProxyPassword'] !== false) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config['curl_proxyuserpwd']);
        } 
        return $response = curl_exec ($ch);
        $responses = curl_getinfo($ch); 
        curl_close ($ch);
        if($responses['http_code'] == 0){
            $this->setError(self::ERROR_COMMUNICATION_FAULT);
            return false;
        }else if ($responses['http_code'] != self::HTTP_STATUS_OK) {
            $this->handleResponse($response);
            return false;
        }
        return $response;
    }
    
    private function HTTPHeader(){
        $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,"; 
        $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5"; 
        $header[] = "Cache-Control: max-age=0"; 
        $header[] = "Connection: keep-alive"; 
        $header[] = "Keep-Alive: 300"; 
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7"; 
        $header[] = "Accept-Language: en-us,en;q=0.5"; 
        $header[] = "Pragma: ";  
        return $header;
    }
    
    private function handleResponse($response){
        if(!isset($response)){
            return;
        }
        $ResponseJSON = json_decode($response);
        if(isset($ResponseJSON->error)){
            if($ResponseJSON->error->type == 'OAuthException'){
                $this->setError(self::ERROR_OAUTH_EXCEPTION, $ResponseJSON->error->message);
            }else{
                $this->setError(self::ERROR_OAUTH_OTHER);
            }
        }else if (isset($ResponseJSON->id)){
            $this->setInfo(self::HTTP_STATUS_OK); 
        }
    }
    
    public function setError($errorCode, $errorMessage=""){
        $this->error['code'] = $errorCode;
        if($errorMessage!=""){
            $this->error['message'] = $errorMessage;
        }
    }
    
    public function getError(){
        return $this->error;
    }
    
    public function getErrorCode(){
        return (isset($this->error['code']) ) ? $this->error['code'] : false;
    }
    
    public function hasErrorOccured(){
        if(!$this->error){
            return false;
        }else{
            return true;
        }
    }
    public function getErrorMessage(){
        return ( isset($this->error['message']) )? $this->error['message'] : false;
    }
    
    public function resetErrors(){
        $this->error = false;
    }
    
    public function setInfo($infomessage){
        $this->info = $infomessage;
    }
    
    public function getInfo(){
        return $this->info;
    }
    
    public function setNotice($message){
        $this->notice[] = $message;
    }
    
    public function getNotice(){
        return $this->notice;
    }
}

?>