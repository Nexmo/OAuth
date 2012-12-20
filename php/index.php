<?php
define('REQUEST',   'request_token');
define('AUTHORIZE', 'authorize');
define('ACCESS',    'access_token');

session_start();
error_log('starting session');

$site = 'https://dashboard.nexmo.com/oauth/';
$url = 'https://rest.nexmo.com/account/get-balance/';
$params = array();

$oauth = new OAuth(trim(getenv('OAUTH_KEY')), trim(getenv('OAUTH_SECRET')));
$token = $oauth->getRequestToken($site.REQUEST);


if(isset($_GET['oauth_token']) AND $_SESSION['secret']){
    error_log("found 'oauth_token' in query, and have a request secret in session");
    error_log('token (from query): ' . $_GET['oauth_token']);
    error_log('secret (from session): ' . $_SESSION['secret']);
    
    $oauth->setToken($_GET['oauth_token'],$_SESSION['secret']);
    //$oauth->setToken($_SESSION['token'],$_SESSION['secret']);
    
    try{
        error_log('requesting access token: ' . $site.ACCESS);
        $token = $oauth->getAccessToken($site.ACCESS);
        error_log('got access token');
        error_log('access token: ' . $token['oauth_token']);
        error_log('access secret: ' . $token['oauth_token_secret']);
    } catch (Exception $e){
        error_log('get error: ' . $e->getMessage());
        return;
    }

    error_log('clearing session');
    $_SESSION['secret'] = null;
    $_SESSION['token']  = null;
    
    try{
        error_log('making request with access token');
        $oauth->setToken($token['oauth_token'], $token['oauth_token_secret']);
        $response = $oauth->fetch($url, $params, OAUTH_HTTP_METHOD_GET, array('Accept' =>  'application/json'));
    } catch(Exception $e) {
        error_log('get error: ' . $e->getMessage());
        return;
    }

    error_log($oauth->getLastResponse());
} else {
    error_log("'oauth_token' not in query, or no request secret in session");
    error_log('getting request token: ' . $site.REQUEST);
    $token = $oauth->getRequestToken($site.REQUEST);
    
    $_SESSION['secret'] = $token['oauth_token_secret'];
    $_SESSION['token'] = $token['oauth_token'];
    
    error_log('token: ' . $token['oauth_token']);
    error_log('secret: ' . $token['oauth_token_secret']);

    $url = $site.AUTHORIZE.'?'.http_build_query(array('oauth_token' => $token['oauth_token']));
    error_log('redirect to: ' . $url);
    header('Location: ' . $url);
}
