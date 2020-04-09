<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Email_model extends CI_Model
{
    public function __construct(){
        parent::__construct();
    }

    

    public function EmailTemplateList($data)
    {
        $this->db->select('*');
        $this->db->from('email_template e');
        $this->db->join('email_template_language el','e.id_email_template=el.email_template_id','left');
        if(isset($data['language_id']))
            $this->db->where('el.language_id',$data['language_id']);
        if(isset($data['customer_id']))
            $this->db->where('e.customer_id',$data['customer_id']);
        if(isset($data['module_key']))
            $this->db->where('e.module_key',$data['module_key']);
        if(isset($data['parent_email_template_id']))
            $this->db->where('e.parent_email_template_id',$data['parent_email_template_id']);
        /*if(isset($data['search']))
            $this->db->where('(l.relationship_category_name like "%'.$data['search'].'%"
        or r.relationship_category_quadrant like "%'.$data['search'].'%")');*/
        if(isset($data['status']))
            $this->db->where_in('e.status',explode(',',$data['status']));
        else
            $this->db->where('e.status',1);
        /* results count start */
        $all_clients_db = clone $this->db;
        $all_clients_count = $all_clients_db->count_all_results();
        /* results count end */

        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
            $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        if(isset($data['sort']['predicate']) && $data['sort']['predicate']!='' && isset($data['sort']['reverse']))
            $this->db->order_by($data['sort']['predicate'],$data['sort']['reverse']);
        else
            $this->db->order_by('e.id_email_template','ASC');
        $query = $this->db->get();
        // if(isset($data['customer_id']) && $data['customer_id']>0 && isset($data['module_key']) && $data['module_key']!='' && $all_clients_count<=0){
        //     $data['customer_id']=0;
        //     return $this->EmailTemplateList($data);
        // }
        $final_result=$query->result_array();
        /*foreach($final_result as $k=>$v){
            $final_result[$k]['template_content']=EMAIL_HEADER_CONTENT.$v['template_content'].EMAIL_FOOTER_CONTENT;
        }*/
        return array('total_records' => $all_clients_count,'data' => $final_result);
    }

    public function addMailer($data)
    {
        $this->db->insert('mailer', $data);
        return $this->db->insert_id();
    }

    public function getMailer($data=array())
    {
        $this->db->select('*');
        $this->db->from('mailer m');
        $this->db->where('m.is_cron',1);
        if(isset($data['limit']))
            $this->db->limit($data['limit']);
        $this->db->where('m.cron_status',0);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function updateMailer($data)
    {
        if(isset($data['mailer_id'])) {
            $this->db->where('mailer_id', $data['mailer_id']);
            $this->db->update('mailer', $data);
            return 1;
        }
    }

    public function addNotification($data)
    {
        $this->db->insert('notification', $data);
        return $this->db->insert_id();
    }

    public function getNotifications($data)
    {
        $this->db->select('*');
        $this->db->from('notification n');
        if(isset($data['user_id']))
            $this->db->where('n.assigned_to',$data['user_id']);
        if(isset($data['module_id']))
            $this->db->where('n.module_id',$data['module_id']);
        if(isset($data['module_type']))
            $this->db->where('n.module_type',$data['module_type']);
            
        //$this->db->where('n.notification_type !=','notification');
        if(isset($data['notification_status']))
            $this->db->where('n.notification_status',strtolower($data['notification_status']));
        if(isset($data['search_key']) && $data['search_key']!=''){
            // $this->db->where("(CONCAT(u.first_name, \" \", u.last_name) LIKE '%".$data['search_key']."%' || n.notification_type LIKE '%".$data['search_key']."%' || ar.approval_name LIKE '%".$data['search_key']."%' || n.notification_subject LIKE '%".$data['search_key']."%')");
        }        
        $this->db->where('status','1');
        $this->db->order_by('n.id_notification','DESC');

        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        if(isset($data['start']) && $data['number']!='')
            $this->db->limit($data['number'],$data['start']);
        $query = $this->db->get();
        return array('total_records' => $all_clients_count,'data' => $query->result_array());        
    }

    public function getNotificationsCount($data)
    {
        $this->db->select('count(*) as total');
        $this->db->from('notification n');
        $this->db->join('user u','n.assigned_by=u.id_user','left');
        if(isset($data['user_id']))
            $this->db->where('n.assigned_to',$data['user_id']);
        if(isset($data['module_id']))
            $this->db->where('n.module_id',$data['module_id']);
        if(isset($data['module_type']))
            $this->db->where('n.module_type',$data['module_type']);
        if(isset($data['notification_status']))
            $this->db->where('n.notification_status',$data['notification_status']);
        if(isset($data['filter']))
        {
            if($data['filter'] == 'new')
                $this->db->where('n.notification_status','unread');
            if($data['filter'] == 'workflow' || $data['filter'] == 'meeting')
                $this->db->where('n.notification_type',$data['filter']);
            elseif($data['filter'] == 'task' || $data['filter'] == 'alert')
                $this->db->where('n.notification_type',$data['filter']);

        }
        //$this->db->where('n.notification_type !=','notification');

        if(isset($data['search_key']) && $data['search_key']!=''){
            $this->db->where("(CONCAT(u.first_name, \" \", u.last_name) LIKE '%".$data['search_key']."%' || n.notification_type LIKE '%".$data['search_key']."%' || ar.approval_name LIKE '%".$data['search_key']."%' || n.notification_subject LIKE '%".$data['search_key']."%')");
        }
        $this->db->where('notification_status !=','deleted');
        $query = $this->db->get();
        //echo $this->db->last_query(); exit;
        return $query->result_array();
    }

    public function updateNotification($data)
    {
        $this->db->where('id_notification', $data['id_notification']);
        $this->db->update('notification', $data);
    }
}