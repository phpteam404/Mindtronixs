<?php
/**
 * Created by PhpStorm.
 * User: BHASHA
 * Date: 28/12/15
 * Time: 5:50 PM
 */
include  APPPATH.'third_party/vendor/autoload.php';
include APPPATH.'libraries/oauth/storage/SessionStorage.php';
include APPPATH.'libraries/oauth/storage/AccessTokenStorage.php';
include APPPATH.'libraries/oauth/storage/AuthCodeStorage.php';
include APPPATH.'libraries/oauth/storage/ClientStorage.php';
include APPPATH.'libraries/oauth/storage/RefreshTokenStorage.php';
include APPPATH.'libraries/oauth/storage/ScopeStorage.php';

use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use Symfony\Component\HttpFoundation\Request;

class Oauth
{

    function __construct()
    {

    }

    public function generateAccessToken()
    {
        $sessionStorage = new SessionStorage();
        $accessTokenStorage = new AccessTokenStorage();
        $clientStorage = new ClientStorage();
        $scopeStorage = new ScopeStorage();

        $server = new AuthorizationServer();

        // Authorization server
        $server->setSessionStorage(new SessionStorage);
        $server->setAccessTokenStorage(new AccessTokenStorage);
        $server->setRefreshTokenStorage(new RefreshTokenStorage);
        $server->setClientStorage(new ClientStorage);
        $server->setScopeStorage(new ScopeStorage);
        $server->setAuthCodeStorage(new AuthCodeStorage);

        $clientCredentials = new ClientCredentialsGrant();
        $server->addGrantType($clientCredentials);

        $request = $this->setRequest();
        $server->setRequest($request);
        $response = $server->issueAccessToken();
        return $response;
    }

    private function setRequest()
    {
        $attributes = [];
        foreach($_REQUEST as $key=>$val)
        {
            $attributes[$key] = $val;
        }

        $headers = [];//changing '' to []
        foreach ($_SERVER as $name => $value)
        {
            $headers[$name] = $value;
        }

        $request = new Request([],[],$attributes,[],[],$headers);
        return $request;
    }

    public function verifyRefreshToken()
    {
        $sessionStorage = new SessionStorage();
        $accessTokenStorage = new AccessTokenStorage();
        $clientStorage = new ClientStorage();
        $scopeStorage = new ScopeStorage();

        $server = new ResourceServer(
            $sessionStorage,
            $accessTokenStorage,
            $clientStorage,
            $scopeStorage
        );

        $request = $this->setRequest();
        $server->setRequest($request);
        $user_id = '';
        if($_SERVER['HTTP_User'])
        $user_id = $_SERVER['HTTP_User'];
        //print_r($_SERVER);exit;

        try {
            if($this->checkAccessPermissions($request))
            {
                return $server->isValidRequest($user_id);
            }
            else
            {
                return false;
            }
        }catch (OAuthException $e){
            // print_r($e);exit;
            return false;
        }
    }

    private function checkAccessPermissions($request)
    {
        $currentAccessToken = $request->headers->get('Authorization');
        $oauthRefreshTokens = new RefreshTokenStorage();

        $refreshToken = $oauthRefreshTokens->query('select * from oauth_refresh_tokens where access_token_id="'.$currentAccessToken.'"');
        return count($refreshToken);
    }

    public function verifyAccessToken()
    {

        $sessionStorage = new SessionStorage();
        $accessTokenStorage = new AccessTokenStorage();
        $clientStorage = new ClientStorage();
        $scopeStorage = new ScopeStorage();

        $server = new ResourceServer(
            $sessionStorage,
            $accessTokenStorage,
            $clientStorage,
            $scopeStorage
        );
        $request = $this->setRequest();
        $user_id = '';
        if(isset($_SERVER['HTTP_USER'])){
            $user_id = $_SERVER['HTTP_USER'];
        }

        if(isset($_SERVER['HTTP_User'])){
            $user_id = $_SERVER['HTTP_User'];
        }
        //print_r($user_id);exit;
       // $user_id=pk_decrypt($user_id);
        try {
            return $server->isValidRequest($user_id);
        }catch (Exception $e){
            return false;
        }
    }
}
