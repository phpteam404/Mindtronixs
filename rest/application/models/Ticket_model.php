<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Ticket_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }

    public function getChat($data)
    {
        $this->db->select('tc.ticket_chat_id,tc.message,DATE_FORMAT(tc.created_on,"%d-%m-%Y")as created_date,mc.child_name as status,concat(u.first_name," ",u.last_name) as created_by,DATE_FORMAT(tc.created_on,"%d %b %Y") as date,TIME_FORMAT(tc.created_on, "%h:%i %p")time');
        $this->db->from('ticket_chat tc');
        // $this->db->join('documents d','tc.ticket_chat_id=d.module_type_id','left');
        $this->db->join('user u','tc.created_by=u.id','left');
        $this->db->join('master_child mc','tc.`status`=mc.id and mc.master_id=18','left');
        if(!empty($data['ticket_id'])){
            $this->db->where('tc.ticket_id',$data['ticket_id']);
        }
        $this->db->order_by('tc.created_on','desc');
        $query = $this->db->get();
        // echo $this->db->last_query();exit;
        return $query->result_array();
    }
    public function getTickets($data)
    { 
        $this->db->select('`t`.`id` as `ticket_id`, `t`.`ticket_no` as `issue_id`, `t`.`title` as `issue_title`,mc.child_name issue_type, concat(u1.first_name, " ", u1.last_name) as created_by, DATE_FORMAT(t.created_on, "%Y-%m-%d") as created_date, mc1.child_name as status');
        $this->db->from('ticket t');
        $this->db->join('user u','t.assigned_to=u.id','left');
        $this->db->join('user u1','t.ticket_rised_by=u1.id','left');
        $this->db->join('master_child mc','t.issue_type=mc.id AND mc.master_id=17','left');
        $this->db->join('master_child  mc1','t.status=mc1.id and mc1.master_id=18','left');
        if(isset($data['user_role_id']) && in_array($data['user_role_id'],array('3','4'))){
             
            $this->db->where('t.ticket_rised_by',$data['user_id']);
        }
        if(isset($data['franchise_id'])){
            $this->db->where('u1.franchise_id',$data['franchise_id']);
        }
        if(isset($data['school_id'])){
            $this->db->where('u1.school_id',$data['school_id']);
        }
        // if(isset($data['user_role_id']) && $data['user_role_id']==5){
        //     $this->db->where('t.assigned_to',$data['user_id']);
        // }
        if(!empty($data['franchise_id'])){
            $this->db->where('u1.franchise_id',$data['franchise_id']);
        }
        if(isset($data['ticket_id']) && $data['ticket_id']>0){
            $this->db->where('t.id',$data['ticket_id']);
        }
        if(isset($data['custom_where'])){
            $this->db->where($data['custom_where']);
        }
        if(isset($data['status'])){
            $this->db->where('t.status',$data['status']);
        }
        else{
            // $this->db->where('t.status',1);
        }
        if(isset($data['search_key'])){
            $this->db->group_start();
            $this->db->like('t.ticket_no', $data['search_key'], 'both');
            $this->db->or_like('t.title', $data['search_key'], 'both');
            $this->db->or_like('mc.child_name', $data['search_key'], 'both');
            $this->db->or_like('mc1.child_name', $data['search_key'], 'both');
            // $this->db->or_like('created_by', $data['search_key'], 'both');

            $this->db->group_end();
        }

        if(!empty($data['sort']) && !empty($data['order']))
        { 
            $this->db->order_by($data['sort'],$data['order']);
        }
        else{
            $this->db->order_by('t.id','desc');
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        if(isset($data['number']) && isset($data['start']))
           $this->db->limit($data['number'],$data['start']);
        
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
        
    }
    public function getTicketData($data){
        $this->db->select('t.id as ticket_id,t.title,t.ticket_no as issue_id,t.description, mc.child_name as issue_type,mc1.child_name as status,CONCAT(u.first_name," ",u.last_name) as created_by,concat(u1.first_name," ",u1.last_name) as last_updated_by,t.created_on  created_date,t.last_update_on as last_updated,mc1.id as status_id,CONCAT(mc1.child_name, "-", mc1.id) as status_display,t.ticket_rised_by');
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