<?php

use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\SessionInterface;

class SessionStorage extends AbstractStorage implements SessionInterface
{
    private $db;

    function __construct()
    {
        $CI =& get_instance();
        $this->db = $CI->db;
    }
    /**
     * {@inheritdoc}
     */

    public function getByAccessToken(AccessTokenEntity $accessToken)
    {
        $this->db->select('oauth_sessions.id', 'oauth_sessions.owner_type', 'oauth_sessions.owner_id', 'oauth_sessions.client_id', 'oauth_sessions.client_redirect_uri');
        $this->db->from('oauth_sessions');
        $this->db->join('oauth_access_tokens', 'oauth_access_tokens.session_id=oauth_sessions.id','left');
        $this->db->where('oauth_access_tokens.access_token', $accessToken->getId());
        $query = $this->db->get();
        $result = $query->result_array();

        /*$result = Capsule::table('oauth_sessions')
                            ->select(['oauth_sessions.id', 'oauth_sessions.owner_type', 'oauth_sessions.owner_id', 'oauth_sessions.client_id', 'oauth_sessions.client_redirect_uri'])
                            ->join('oauth_access_tokens', 'oauth_access_tokens.session_id', '=', 'oauth_sessions.id')
                            ->where('oauth_access_tokens.access_token', $accessToken->getId())
                            ->get();*/

        if (count($result) === 1) {
            $session = new SessionEntity($this->server);
            $session->setId($result[0]['id']);
            $session->setOwner($result[0]['owner_type'], $result[0]['owner_id']);

            return $session;
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getByAuthCode(AuthCodeEntity $authCode)
    {
        $this->db->select('oauth_sessions.id', 'oauth_sessions.owner_type', 'oauth_sessions.owner_id', 'oauth_sessions.client_id', 'oauth_sessions.client_redirect_uri');
        $this->db->from('oauth_sessions');
        $this->db->join('oauth_auth_codes', 'oauth_auth_codes.session_id=oauth_sessions.id','left');
        $this->db->where('oauth_auth_codes.auth_code', $authCode->getId());
        $query = $this->db->get();
        $result = $query->result_array();

        /*$result = Capsule::table('oauth_sessions')
                            ->select(['oauth_sessions.id', 'oauth_sessions.owner_type', 'oauth_sessions.owner_id', 'oauth_sessions.client_id', 'oauth_sessions.client_redirect_uri'])
                            ->join('oauth_auth_codes', 'oauth_auth_codes.session_id', '=', 'oauth_sessions.id')
                            ->where('oauth_auth_codes.auth_code', $authCode->getId())
                            ->get();*/

        if (count($result) === 1) {
            $session = new SessionEntity($this->server);
            $session->setId($result[0]['id']);
            $session->setOwner($result[0]['owner_type'], $result[0]['owner_id']);

            return $session;
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopes(SessionEntity $session)
    {
        $this->db->select('oauth_scopes.*');
        $this->db->from('oauth_sessions');
        $this->db->join('oauth_session_scopes', 'oauth_sessions.id=oauth_session_scopes.session_id','left');
        $this->db->join('oauth_scopes', 'oauth_scopes.id=oauth_session_scopes.scope_id','left');
        $this->db->where('oauth_sessions.id', $session->getId());
        $query = $this->db->get();
        $result = $query->result_array();

        /*$result = Capsule::table('oauth_sessions')
                            ->select('oauth_scopes.*')
                            ->join('oauth_session_scopes', 'oauth_sessions.id', '=', 'oauth_session_scopes.session_id')
                            ->join('oauth_scopes', 'oauth_scopes.id', '=', 'oauth_session_scopes.scope')
                            ->where('oauth_sessions.id', $session->getId())
                            ->get();*/

        $scopes = [];

        foreach ($result as $scope) {
            $scopes[] = (new ScopeEntity($this->server))->hydrate([
                'id'            =>  $scope['id'],
                'description'   =>  $scope['description'],
            ]);
        }

        return $scopes;
    }

    /**
     * {@inheritdoc}
     */
    public function create($ownerType, $ownerId, $clientId, $clientRedirectUri = null)
    {
        $browser = $client = '';
        if(isset($_SERVER['HTTP_USER_AGENT'])){
            $browser = $_SERVER['HTTP_USER_AGENT'];
        }

        if ( function_exists( 'apache_request_headers' ) ) {
            $headers = apache_request_headers();
        }
        else{
            $headers = $_SERVER;
        }

        if(isset($headers['HTTP_USER_AGENT'])){
            $browser = $headers['HTTP_USER_AGENT'];
        }


        if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            $client = $headers['X-Forwarded-For'];
        }
        elseif( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 )){
            $client = $headers['HTTP_X_FORWARDED_FOR'];
        }
        else{
            $client = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
        }
        /*$ubrowser=getUserBrowser($browser);
        $browser = $ubrowser['name'].','.$ubrowser['version'].','.$ubrowser['platform'];*/
        $user_agent=$browser;
        $browser = getUserBrowser($browser);

        $data = array(
                    'owner_type'  =>    $ownerType,
                    'owner_id'    =>    $ownerId,
                    'client_id'   =>    $clientId,
                    'client_browser' => $browser,
                    'user_agent' => $user_agent,
                    'client_remote_address' => $client,
                    'created_at' => currentDate()
                );
        $this->db->insert('oauth_sessions',$data);
        $id = $this->db->insert_id();
        /*$id = Capsule::table('oauth_sessions')
                        ->insertGetId([
                            'owner_type'  =>    $ownerType,
                            'owner_id'    =>    $ownerId,
                            'client_id'   =>    $clientId,
                        ]);*/

        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function associateScope(SessionEntity $session, ScopeEntity $scope)
    {
        $this->db->insert('oauth_session_scopes',array('session_id' => $session->getId(),'scope' => $scope->getId()));
        /*Capsule::table('oauth_session_scopes')
                            ->insert([
                                'session_id'    =>  $session->getId(),
                                'scope'         =>  $scope->getId(),
                            ]);*/
    }
}
