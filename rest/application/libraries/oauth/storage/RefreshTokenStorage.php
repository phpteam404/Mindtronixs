<?php

use League\OAuth2\Server\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\RefreshTokenInterface;

class RefreshTokenStorage extends AbstractStorage implements RefreshTokenInterface
{
    /**
     * {@inheritdoc}
     */
    private $db;

    function __construct()
    {
        $CI =& get_instance();
        $this->db = $CI->db;
    }

    public function get($token)
    {
        $this->db->get_where('oauth_refresh_tokens',array('refresh_token' => $token));
        $query = $this->db->get();
        $result = $query->result_array();

        /*$result = Capsule::table('oauth_refresh_tokens')
                            ->where('refresh_token', $token)
                            ->get();*/

        if (count($result) === 1) {
            $token = (new RefreshTokenEntity($this->server))
                        ->setId($result[0]['refresh_token'])
                        ->setExpireTime($result[0]['expire_time'])
                        ->setAccessTokenId($result[0]['access_token']);

            return $token;
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function create($token, $expireTime, $accessToken)
    {
        $data = array(
                    'refresh_token'     =>  $token,
                    'access_token'    =>  $accessToken,
                    'expire_time'   =>  $expireTime,
                );
        $this->db->insert('oauth_refresh_tokens',$data);

        /*Capsule::table('oauth_refresh_tokens')
                    ->insert([
                        'refresh_token'     =>  $token,
                        'access_token'    =>  $accessToken,
                        'expire_time'   =>  $expireTime,
                    ]);*/
    }

    /**
     * {@inheritdoc}
     */
    public function delete(RefreshTokenEntity $token)
    {
        $this->db->where('refresh_token', $token->getId());
        $this->db->delete('oauth_refresh_tokens');

        /*Capsule::table('oauth_refresh_tokens')
                            ->where('refresh_token', $token->getId())
                            ->delete();*/
    }

    public function query($query)
    {
       $query = $this->db->query($query);
       $result = $query->result_array();
       return $result;
    }
}
