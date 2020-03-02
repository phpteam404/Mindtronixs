<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Ticket_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }

    public function getChat($data)
    {
        $this->db->select('tc.*,GROUP_CONCAT(d.document_name) as documents,concat(u.first_name," ",u.last_name) as from_user_name,concat(u1.first_name," ",u1.last_name) as to_user_name,if(t.status=1,"open","close") as status');
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
        $this->db->select('`t`.`id` as `ticket_id`, `t`.`ticket_no` as `issue_id`, `t`.`title` as `issue_title`,mc.child_name issue_type, concat(u1.first_name, " ", u1.last_name) as created_by, DATE_FORMAT(t.created_on, "%d-%m-%Y") as created_date, IF(t.status=1, "open", "close")as status');
        $this->db->from('ticket t');
        $this->db->join('user u','t.assigned_to=u.id','left');
        $this->db->join('user u1','t.ticket_rised_by=u1.id','left');
        $this->db->join('master_child mc','t.issue_type=mc.id AND mc.master_id=17','left');
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
            $this->db->like('t.issue_id', $data['search']);
            $this->db->like('t.issue_type', $data['search']);
            $this->db->like('t.created_by', $data['search']);
            $this->db->like('t.status', $data['search']);

            $this->db->group_end();
        }

        if(!empty($data['sort']) && !empty($data['reverse']))
        { 
            $this->db->order_by($data['sort'],$data['reverse']);
        }
        else{
            $this->db->order_by('t.id','desc');
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
    public function getTicketData($data){
        $this->db->select('t.id as ticket_id, t.ticket_no as issue_id, mc.child_name as issue_type,mc1.child_name as status,CONCAT(u.first_name," ",u.last_name) as created_by,concat(u1.first_name," ",u1.last_name) as last_updated_by,DATE_FORMAT(t.created_on,"%d/%m/%Y") as created_date,DATE_FORMAT(t.created_on,"%d/%m/%Y") as last_updated,GROUP_CONCAT(d.document_name) as document_name,mc1.id as status_id');
        $this->db->from('ticket t');
        $this->db->join('user u','t.ticket_rised_by=u.id','left');
        $this->db->join('user u1','t.last_updated_by=u1.id','left');
        $this->db->join('master_child mc1','t.status=mc1.id and mc1.master_id=18','left');
        $this->db->join('master_child mc','t.issue_type=mc.id and mc.master_id=17','left');
        $this->db->join('documents d','t.id=d.module_type_id AND d.module_type=1','left');
        if(isset($data['ticket_id']) && $data['ticket_id']>0){
            $this->db->where('t.id',$data['ticket_id']);
        }
        $this->db->group_by('t.id');
        $query = $this->db->get();
        return $query->result_array();
    }
}