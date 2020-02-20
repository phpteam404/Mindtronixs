<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Ticket_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }

    public function getChat($data)
    {
        $this->db->select('tc.*,GROUP_CONCAT(d.document_name) as documents,,concat(u.first_name," ",u.last_name) as from_user_name,concat(u1.first_name," ",u1.last_name) as to_user_name,if(t.status=1,"open","close") as status');
        $this->db->from('ticket_chat tc');
        $this->db->join('documents d','tc.ticket_chat_id=d.module_type_id','left');
        $this->db->join('ticket t','tc.ticket_id=t.id','left');
        $this->db->join('user u','tc.from_user=u.id','left');
        $this->db->join('user u1','tc.to_user=u1.id','left');
        if(isset($data['ticket_id']) && $data['ticket_id']>0){
            $this->db->where('tc.ticket_id',$data['ticket_id']);
        }
        $this->db->group_by('tc.ticket_chat_id');
        $query = $this->db->get();
        // echo $this->db->last_query();exit;
        return $query->result_array();
    }
    public function getTickets($data)
    { 
        $this->db->select('t.id as ticket_id,t.ticket_no,t.assigned_to,t.ticket_rised_by,t.status,t.description,concat(u.first_name," ",u.last_name) as assigned_user_name,concat(u1.first_name," ",u1.last_name) as ticket_created_user_name');
        $this->db->from('ticket t');
        $this->db->join('user u','t.assigned_to=u.id','left');
        $this->db->join('user u1','t.ticket_rised_by=u1.id','left');
        if(isset($data['user_role_id']) && $data['user_role_id']==4){
            $this->db->where('t.ticket_rised_by',$data['user_id']);
        }
        if(isset($data['user_role_id']) && $data['user_role_id']==5){
            $this->db->where('t.assigned_to',$data['user_id']);
        }
        if(isset($data['ticket_id']) && $data['ticket_id']>0){
            $this->db->where('t.id',$data['ticket_id']);
        }
        if(isset($data['status'])){
            $this->db->where('t.status',$data['status']);
        }
        else{
            $this->db->where('t.status',1);
        }
        if(isset($data['search'])){
            $this->db->group_start();
            $this->db->like('t.ticket_no', $data['search']);
            $this->db->group_end();
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        // if(isset($data['limit']) && isset($data['offset']))
        //    $this->db->limit($data['limit'],$data['offset']);
        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
        $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
        
    }
}