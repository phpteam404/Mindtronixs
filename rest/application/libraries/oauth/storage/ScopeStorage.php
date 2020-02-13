<?php

use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\ScopeInterface;

class ScopeStorage extends AbstractStorage implements ScopeInterface
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
    public function get($scope, $grantType = null, $clientId = null)
    {
        $this->db->get_where('oauth_scopes',array('id' => $scope));
        $query = $this->db->get();
        $result = $query->result_array();

        /*$result = Capsule::table('oauth_scopes')
                                ->where('id', $scope)
                                ->get();*/

        if (count($result) === 0) {
            return;
        }

        return (new ScopeEntity($this->server))->hydrate([
            'id'            =>  $result[0]['id'],
            'description'   =>  $result[0]['description'],
        ]);
    }
}
