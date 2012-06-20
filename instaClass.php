<?php
/**
*  A simple PHP class for communicating with Instagram API
*
* @version  1.1
* @author Daniel Eliasson - www.stilero.com
* @copyright  (C) 2012 Stilero Webdesign
* @category library
* @license    GPLv2
*
* instaClass is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* instaClass is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with instaClass.  If not, see <http://www.gnu.org/licenses/>.
*
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
    
    public function authURL(){
        $authURL = $this->config['authURL'].
                '?client_id=' . $this->clientId.
                '&redirect_uri='.$this->config['redirectURI'].
                '&response_type=code';
        return $authURL;
    }
    
    public function requestAccessToken($authCode=''){
        $url = $this->config['accessTokenURL'];
        $postVars = array(
            'client_id'     =>  $this->clientId,
            'client_secret' =>  $this->clientSecret,
            'grant_type'    =>  'authorization_code',
            'redirect_uri'  =>  $this->config['redirectURI'],
            'code'          =>  $authCode
        );
        return $this->doQuery($url, $postVars);
    }
    
    public function fetchImages($userID='self', $count='30', $callType='', $callParams=''){
        $path = '';
        switch ($callType) {
            case 'user-feed':
                $path = '/users/'.$userID.'/feed/';
                $postParams = array(
                    'count' =>  $count
                );
                break;
            case 'user-liked':
                $path = '/users/'.$userID.'/media/liked/';               
                 $postParams = array(
                    'count' =>  $count
                );
                break;
            case 'most-popular':
                $path = '/media/popular';
                $postParams = array(
                    'count' =>  $count
                );
                break;
            case 'user-info':
                $path = '/users/'.$userID;
                $postParams = array(
                    'count' =>  $count
                );
                break;
            case 'tags-search':
                $path = '/tags/search';
                $postParams = array(
                    'q' =>  $callParams
                );
                break;
            case 'tags-name':
                $path = '/tags/'.$callParams.'/media/recent/';
                break;
            case 'media-search':
                // Use array with 'longitude', 'latitude' and 'distance' as callParams
                $path = '/media/search';
                $postParams = array(
                    'lng' =>  $callParams['longitude'],
                    'lat' =>  $callParams['latitude'],
                    'distance' =>  $callParams['distance'],
                    'count' =>  $count
                );
                break;
            default:
                $path = '/users/'.$userID.'/media/recent/';
                break;
        }
        $baseUrlAndPath = $this->config['instaBaseURL'].$path;
        $postVars = array(
            'access_token'      => $this->accessToken,
        );
        if(isset($postParams)){
            $postVars = array_merge($postVars, $postParams);
        }
        $requestURI = $baseUrlAndPath ."?". http_build_query($postVars);
        $jsonResponse = $this->doQuery($requestURI, $postVars, FALSE, $this->HTTPHeader());
        return $this->jsonResponseToArray($jsonResponse);
    }
    
    public function fetchUserInfoJSON($userID='self'){
        $path = '/users/'.$userID;
        $baseUrlAndPath = $this->config['instaBaseURL'].$path;
        $postVars = array(
            'access_token'      => $this->accessToken,
            'count'             =>  $count
        );
        $requestURI = $baseUrlAndPath ."?". http_build_query($postVars);
        return $jsonResponse = $this->doQuery($requestURI, $postVars, FALSE, $this->HTTPHeader());
    }
    
    public function fetchUserInfoArray($userID='self'){
        $userJSONResponse = $this->fetchUserInfoJSON($userID);
        return $userInfoArray = $this->jsonResponseToUserArray($userJSONResponse);
    }
    
    private function jsonResponseToUserArray($response){
        $ResponseJSON = json_decode($response);
        $data = $ResponseJSON->data;
        //var_dump($data);exit;
        $user=array();
        if(isset($data)){
            //foreach ($data as $value) {
                $user['id'] = $data->id;
                $user['username'] = $data->username;
                $user['full_name'] = $data->full_name;
                $user['profile_picture'] = $data->profile_picture;
                $user['bio'] = $data->bio;
                $user['website'] = $data->website;
                $user['media'] = $data->counts->media;
                $user['follows'] = $data->counts->follows;
                $user['followed_by'] = $data->counts->followed_by;
            //}
        }
        return $user;
    }
    
    public function jsonResponseToArray($response){
        $ResponseJSON = json_decode($response);
        $data = $ResponseJSON->data;
        $images = array();
        if(isset($data)){
            foreach ($data as $value) {
                $image['thumb'] = $value->images->thumbnail->url;
                $image['full'] = $value->images->standard_resolution->url;
                $image['created'] = $value->created_time;
                $image['caption'] = isset($value->caption->text) ? $value->caption->text : '';
                $image['tags'] = $value->tags;
                $image['latitude'] = isset($value->location->latitude) ? $value->location->latitude : '';
                $image['longitude'] = isset($value->location->longitude) ? $value->location->longitude : '';
                $image['id'] = $value->id;
                $image['likes'] = $value->likes->count;
                $image['comments'] = $value->comments->count;
                $image['link'] = $value->link;
                $image['user-name'] = $value->user->username;
                $image['user-profilepic'] = $value->user->profile_picture;
                $image['user-fullname'] = $value->user->full_name;
                $images[] = $image;
            }
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
        $header = array();
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