<?php

use League\OAuth2\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\ClientInterface;

class ClientStorage extends AbstractStorage implements ClientInterface
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
    public function get($clientId, $clientSecret = null, $redirectUri = null, $grantType = null)
    {
        $this->db->select('c.*');
        $this->db->from('oauth_clients c');
        $this->db->where('c.id', $clientId);
        if ($clientSecret !== null) {
            $this->db->where('c.secret', $clientSecret);
        }
        if ($redirectUri) {
            $this->db->join('oauth_client_redirect_uris ocru', 'oauth_clients.id=oauth_client_redirect_uris.client_id', 'left');
            $this->db->select('ocru.*');
            $this->db->where('ocru.redirect_uri', $redirectUri);
        }
        $query = $this->db->get();
        $result = $query->result_array();
        /*$query = Capsule::table('oauth_clients')
                          ->select('oauth_clients.*')
                          ->where('oauth_clients.id', $clientId);

        if ($clientSecret !== null) {
            $query->where('oauth_clients.secret', $clientSecret);
        }

        if ($redirectUri) {
            $query->join('oauth_client_redirect_uris', 'oauth_clients.id', '=', 'oauth_client_redirect_uris.client_id')
                  ->select(['oauth_clients.*', 'oauth_client_redirect_uris.*'])
                  ->where('oauth_client_redirect_uris.redirect_uri', $redirectUri);
        }

        $result = $query->get();*/

        if (count($result) === 1) {
            $client = new ClientEntity($this->server);
            $client->hydrate([
                'id'    =>  $result[0]['id'],
                'name'  =>  $result[0]['name'],
            ]);

            return $client;
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getBySession(SessionEntity $session)
    {
        $this->db->select('oauth_clients.id', 'oauth_clients.name');
        $this->db->from('oauth_clients');
        $this->db->join('oauth_sessions', 'oauth_clients.id=oauth_sessions.client_id','left');
        $this->db->where('oauth_sessions.id', $session->getId());
        $query = $this->db->get();
        $result = $query->result_array();

        /*$result = Capsule::table('oauth_clients')
                            ->select(['oauth_clients.id', 'oauth_clients.name'])
                            ->join('oauth_sessions', 'oauth_clients.id', '=', 'oauth_sessions.client_id')
                            ->where('oauth_sessions.id', $session->getId())
                            ->get();*/

        if (count($result) === 1) {
            $client = new ClientEntity($this->server);
            $client->hydrate([
                'id'    =>  $result[0]['id'],
                'name'  =>  $result[0]['name'],
            ]);

            return $client;
        }

        return;
    }
}
