<?php

use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\AccessTokenInterface;

class AccessTokenStorage extends AbstractStorage implements AccessTokenInterface
{
    private $db;
    private $ci;
    function __construct()
    {
        $CI =& get_instance();
        $this->db = $CI->db;
        $this->ci=$CI;
    }

    /**
     * {@inheritdoc}
     */
    public function get($token,$user)
    {
        /*$query = $this->db->query('select * from oauth_access_tokens oct
                                            left join oauth_sessions os on oct.session_id=os.id
                                            left join oauth_clients oc on oc.id=os.client_id
                                            where oct.access_token="'.$token.'" and oc.user_id="'.$user.'"');*/
        /*echo 'select * from oauth_access_tokens oct
                                            left join oauth_sessions os on oct.session_id=os.id
                                            left join oauth_clients oc on oc.id=os.client_id
                                            where oct.access_token="'.$token.'" and oc.user_id="'.$user.'"';*/

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


        $this->db->select('*');
        $this->db->from('oauth_access_tokens oct');
        $this->db->join('oauth_sessions os','oct.session_id=os.id','left');
        $this->db->join('oauth_clients oc','oc.id=os.client_id','left');
        $this->db->where('oct.access_token',$token);
        $this->db->where('oc.user_id',$user);
        $this->db->where('os.client_browser',$browser);
        $this->db->where('os.client_remote_address',$client);
        $this->db->where('os.user_agent',$user_agent);
        $query = $this->db->get();

        $result = $query->result_array();
        //echo "<pre>"; print_r($result); exit;
        if (count($result) === 1)
        {
            $expire_time=$result[0]['expire_time'];
            $login_time=strtotime($result[0]['created_at']);
            $timeGap=SITE_ACCESS_TOKEN_EXPIRY;
            if(((strtotime(currentDate()) - $expire_time) >=-$timeGap) && ((strtotime(currentDate()) - $expire_time) <=0)){
                $expire_time=strtotime(currentDate())+$timeGap;
                $data_update_exptime=array("expire_time"=>$expire_time);
                $this->db->where('access_token',$result[0]['access_token']);
                $this->db->update('oauth_access_tokens',$data_update_exptime);
                /*if(!empty($this->ci->session->userdata('session_user_id')))
                    $this->ci->session->set_userdata('session_user_id',$this->ci->session->userdata('session_user_id'));
                if(!empty($this->ci->session->userdata('session_user_id_acting')))
                    $this->ci->session->set_userdata('session_user_id_acting',$this->ci->session->userdata('session_user_id_acting'));*/
            }
            if(($expire_time-$login_time)<=0)
            {
                $this->db->where('id_user', $user);
                $this->db->set('last_login', currentDate());
                $this->db->update('user');
            }
            $query = $this->db->query('UPDATE oauth_access_tokens oct JOIN oauth_sessions os ON oct.session_id=os.id SET oct.updated_at = "'.currentDate().'",os.updated_at = "'.currentDate().'" WHERE oct.access_token="'.$token.'"');
            //if(!empty($this->ci->session->userdata('session_user_id'))) {
                $token = (new AccessTokenEntity($this->server))
                    ->setId($result[0]['access_token'])
                    ->setExpireTime($expire_time);
                return $token;
            //}
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopes(AccessTokenEntity $token)
    {
        $this->db->select('oauth_scopes.id', 'oauth_scopes.description');
        $this->db->from('oauth_access_token_scopes');
        $this->db->join('oauth_scopes', 'oauth_access_token_scopes.scope_id = oauth_scopes.id');
        $this->db->where('access_token_id', $token->getId());
        $query = $this->db->get();
        $result = $query->result_array();


            /*$result = Capsule::table('oauth_access_token_scopes')
                                        ->select(['oauth_scopes.id', 'oauth_scopes.description'])
                                        ->join('oauth_scopes', 'oauth_access_token_scopes.scope', '=', 'oauth_scopes.id')
                                        ->where('access_token', $token->getId())
                                        ->get();*/

        $response = [];

        if (count($result) > 0) {
            foreach ($result as $row) {
                $scope = (new ScopeEntity($this->server))->hydrate([
                    'id'            =>  $row['id'],
                    'description'   =>  $row['description'],
                ]);
                $response[] = $scope;
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function create($token, $expireTime, $sessionId)
    {
        //getting app config
        /*$this->db->select('*');
        $this->db->from('app_config');
        $this->db->where('key','session_expire_time');
        $query = $this->db->get();
        $app_config = $query->result_array();

        if(!empty($app_config)){
            $expireTime = strtotime(date("Y-m-d H:i:s", time() + ($app_config[0]['value'] * 60)));
        }*/
        //$expireTime = strtotime(date("Y-m-d H:i:s", time() + (120)));
        $this->db->insert('oauth_access_tokens',array('access_token' =>  $token,'session_id'    =>  $sessionId,'expire_time'   =>  $expireTime,'created_at' => currentDate()));
        //echo $this->db->last_query(); exit;
        /*Capsule::table('oauth_access_tokens')
                    ->insert([
                        'access_token'     =>  $token,
                        'session_id'    =>  $sessionId,
                        'expire_time'   =>  $expireTime,
                    ]);*/
    }

    /**
     * {@inheritdoc}
     */
    public function associateScope(AccessTokenEntity $token, ScopeEntity $scope)
    {
        $this->db->insert('oauth_access_token_scopes', array('access_token'  =>  $token->getId(),'scope' =>  $scope->getId()));

        /*Capsule::table('oauth_access_token_scopes')
                    ->insert([
                        'access_token'  =>  $token->getId(),
                        'scope' =>  $scope->getId(),
                    ]);*/
    }

    /**
     * {@inheritdoc}
     */
    public function delete(AccessTokenEntity $token)
    {
        $this->db->where('access_token', $token->getId());
        $this->db->delete('oauth_access_tokens');

        /*Capsule::table('oauth_access_tokens')
                    ->where('access_token', $token->getId())
                    ->delete();*/
    }
    public function doExpire($token)
    {
        /*if(!empty($this->ci->session->userdata('session_user_id')))
            $this->ci->session->unset_userdata('session_user_id');
        if(!empty($this->ci->session->userdata('session_user_id_acting')))
            $this->ci->session->unset_userdata('session_user_id_acting');*/
        $query = $this->db->query('UPDATE oauth_access_tokens oct JOIN oauth_sessions os ON oct.session_id=os.id SET oct.updated_at = "'.currentDate().'",os.updated_at = "'.currentDate().'",oct.expire_time=(0-oct.expire_time),oct.expired_date_time = "'.currentDate().'" WHERE oct.access_token="'.$token.'"');
        return;
    }
}
