<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Ticket_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }


    public function getChat($data)
    {
        $this->db->select('tc.*,GROUP_CONCAT(d.document_name) as documents');
        $this->db->from('ticket_chat tc');
        $this->db->join('documents d','tc.ticket_chat_id=d.ticket_chat_id','left');
        if(isset($data['ticket_id']) && $data['ticket_id']>0){
            $this->db->where('tc.ticket_id',$data['ticket_id']);
        }
        $this->db->group_by('tc.ticket_chat_id');
        $query = $this->db->get();
        // echo $this->db->last_query();exit;
        return $query->result_array();
    }
}