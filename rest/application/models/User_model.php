<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model
{

    public function __construct(){
        parent::__construct();
    }


    public $key = '#@WITH-BRO-TOOL$#';
    public function createOauthCredentials($user_id,$first_name,$last_name)
    {
        $query = $this->db->get_where('oauth_clients',array('user_id' => $user_id));
        $result = $query->result_array();
        $key = bin2hex(openssl_random_pseudo_bytes(10));
        if(empty($result))
        {
            $data = array(
                'user_id' => $user_id,
                'secret' => $key,
                'name' => $first_name.' '.$last_name,
                'created_at' => currentDate()
            );
            $this->db->insert('oauth_clients', $data);
            $client_id = $this->db->insert_id();
            return array('client_id' => $client_id, 'client_secret' => $key);
        }
        else
        {
            return array('client_id' => $result[0]['id'], 'client_secret' => $result[0]['secret']);
        }
    }

    public function getTokenDetails($access_token,$user_id)
    {
        /*$query = $this->db->query('select * from oauth_access_tokens oct
                                            left join oauth_sessions os on oct.session_id=os.id
                                            left join oauth_clients oc on oc.id=os.client_id
                                            where oct.access_token="'.$access_token.'" and oc.user_id="'.$user_id.'"');*/
        $this->db->select('*');
        $this->db->from('oauth_access_tokens oct');
        $this->db->join('oauth_sessions os','oct.session_id=os.id','left');
        $this->db->join('oauth_clients oc','oc.id=os.client_id','left');
        $this->db->where('oct.access_token',$access_token);
        $this->db->where('oc.user_id',$user_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getSession($data)
    {
        $this->db->select('oc.name,os.*');
        $this->db->from('oauth_sessions os');
        $this->db->join('oauth_clients oc','oc.id=os.client_id','left');
        $this->db->where('oc.user_id',$data['user_id']);
        if(isset($data['offset']) && $data['offset']!='' && isset($data['limit']) && $data['limit']!='')
            $this->db->limit($data['limit'],$data['offset']);
        $this->db->order_by('os.id','DESC');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getTotalSession($data)
    {
        $this->db->select('*');
        $this->db->from('oauth_sessions os');
        $this->db->join('oauth_clients oc','oc.id=os.client_id','left');
        $this->db->where('oc.user_id',$data['user_id']);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function encode($value)
    {
        return strtr(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($this->key), $value, MCRYPT_MODE_CBC, md5(md5($this->key)))),'+/=', '-_,');
    }
    public function decode($value)
    {
        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($this->key), base64_decode(strtr($value, '-_,', '+/=')), MCRYPT_MODE_CBC, md5(md5($this->key))), "\0");
    }


    public function activeAccount($code)
    {
        $query = $this->db->get_where('user',array('id_user' => $this->decode($code)));
        $data = $query->row();
        if(empty($data)){ return 0; }
        else{
            $update = array('user_status' => '1');
            $this->db->where('id_user', $this->decode($code));
            $this->db->update('user', $update);
            return 1;
        }
    }

    public function login($data)
    {
        $this->db->select('ur.user_role_name,u.user_role_id,u.id as user_id,u.profile_image,u.first_name,u.last_name,u.email,u.user_status,u.is_blocked,
        date_format(u.last_password_attempt_date,"%Y-%m-%d") as last_password_attempt_date,ur.access');
        $this->db->from('user u');        
        $this->db->join('user_role ur','u.user_role_id=ur.id and ur.role_status=1','left');        
        $this->db->where(array('u.email' => $data['username'], 'u.password' => md5($data['password'])));
        $query = $this->db->get();
        //echo $this->db->last_query(); exit;
        //'u.user_status' => 1
        return $query->row();
    }
    public function ldap_login($data)
    {
        $this->db->select('ur.user_role_name,u.customer_id,u.user_role_id,u.id_user,u.profile_image,u.first_name,u.last_name,u.email,u.user_status,u.is_blocked,date_format(u.last_password_attempt_date,"%Y-%m-%d") as last_password_attempt_date,ur.access');
        $this->db->from('user u');
        $this->db->join('user_role ur','u.user_role_id=ur.id_user_role and ur.role_status=1','left');
        $this->db->where(array('u.email' => $data['email_id']));
        $query = $this->db->get();
        //echo $this->db->last_query(); exit;
        //'u.user_status' => 1
        return $query->row();
    }

    public function updateUser($data,$id)
    {
        $this->db->where('id_user', $id);
        $this->db->update('user', $data);
        return 1;
    }

    public function passwordExist($data)
    {
        $this->db->select('*');
        $this->db->from('user');
        $this->db->where('id_user',$data['user_id']);
        $this->db->where('password',md5($data['oldpassword']));
        $query = $this->db->get();
        return $query->row();
    }

    public function check_email($data)
    {
        $this->db->select('u.*,date_format(u.last_password_attempt_date,"%Y-%m-%d") as last_password_attempt_date');
        $this->db->from('user u');
        if(isset($data['id']) && $data['id']!=0 && $data['id']!='')
            $this->db->where('u.id!=',$data['id']);
        $this->db->where('u.email',addslashes($data['email']));
        $query = $this->db->get();
        return $query->row();
    }

    public function changePassword($data)
    {
        $update = array('password' => md5($data['password']));
        $this->db->where('id_user', $data['user_id']);
        $this->db->update('user', $update);
        return 1;
    }

    public function updatePassword($password,$id)
    {
        $update = array('password' => md5($password));
        $this->db->where('id_user', $id);
        $this->db->update('user', $update);
        return 1;
    }

    public function getUsersList($data)
    {
        $query = $this->db->get_where('user',array('user_role_id'=>$data['type']));
        return $query->result_array();
    }

    public function getUserRole($data)
    {
        $query = $this->db->get_where('user_role',array('id_user_role'=>$data['user_role_id']));
        return $query->result_array();
    }

    public function getUserInfo($data)
    {
        $this->db->select('*,u.id as user_id');
        $this->db->from('user u');
        $this->db->join('user_role ur','u.user_role_id=ur.id and ur.role_status=1','left');
        //$this->db->join('business_unit_user buu','u.id_user = buu.user_id','left');
        //$this->db->join('provider p','u.provider = p.id_provider','left');
        //$this->db->join('business_unit bu','bu.id_business_unit = buu.business_unit_id','left');
        if(isset($data['user_role_id']))
            $this->db->where('u.user_role_id',$data['user_role_id']);
        if(isset($data['customer_id']))
            $this->db->where('u.customer_id',$data['customer_id']);
        if(isset($data['user_role_id_not']))
            $this->db->where_not_in('u.user_role_id',$data['user_role_id_not']);
        if(isset($data['user_id']))
            $this->db->where('u.id',$data['user_id']);
        if(isset($data['user_status']))
            $this->db->where('u.user_status',$data['user_status']);
        $query = $this->db->get();
        return $query->row();
    }

    public function createUser($data)
    {
        $this->db->insert('user', $data);
        return $this->db->insert_id();
    }

    public function addLoginAttempts($data)
    {
        $this->db->insert('invalid_login_attempts', $data);
        return $this->db->insert_id();
    }

    public function updateOauthAccessToken($data)
    {
        $this->db->where('id', $data['id']);
        $this->db->update('oauth_access_tokens', $data);
        $query = $this->db->get_where('oauth_access_tokens', array('id' => $data['id']));
        $accesstoken_details = $query->row();

        $data=array('updated_at'=>currentDate());
        $this->db->where('id', $accesstoken_details->session_id);
        $this->db->update('oauth_sessions', $data);
        return 1;
    }

    public function updateAccessToken($data)
    {
        $this->db->where('id', $data['id']);
        $this->db->update('oauth_access_tokens', $data);
        return 1;
    }

    public function check_record($table,$where=null,$orderby=null){
        $this->db->select('*');
        $this->db->from($table);
        if(!empty($where))
            $this->db->where($where);
        if(!empty($orderby))
        $this->db->order_by($orderby['column_name'],$orderby['order_type']);
        $query = $this->db->get();//echo '<pre>'.$this->db->last_query();
        return $query->result_array();
    }

    public function custom_query($q){
        $query = $this->db->query($q);//echo '<pre>'.$this->db->last_query();
        return $query->result_array();
    }
    public function custom_update_query($q){
        $query = $this->db->query($q);//echo '<pre>'.$this->db->last_query();
        //return $query->result_array();
    }
    
    public function check_record_selected($selected,$table,$where){
        $this->db->select($selected!=''?$selected:'*');
        $this->db->from($table);
        if(isset($where))
            $this->db->where($where);
        $query = $this->db->get();//echo '<pre>'.$this->db->last_query();exit;
        return $query->result_array();
    }

    public function check_record_adv($table,$where,$where_not){
        $where_not_sql = '';
        $this->db->select('*');
        $this->db->from($table);
        if(isset($where))
            $this->db->where($where);
        if(isset($where_not)){
            foreach($where_not as $k => $v)
                $where_not_sql .= $k.' != '.$v.' AND ';
            $this->db->where(substr($where_not_sql,0,-4));
        }
        $query = $this->db->get();
        return $query->result_array();
    }

    public function insert_data($table,$data){
        $this->db->insert($table, $data);
        return $this->db->insert_id();
    }

    public function batch_insert($table,$data){
        $this->db->insert_batch($table,$data);
        return 1;
    }

    // public function update_data($table,$data,$where){
    //     $this->db->where($where);
    //     $this->db->update($table, $data);
    //     return $this->db->affected_rows();
    // }

    public function menu($data)
    {
        //echo '<pre>'.print_r($data);exit;
        $select_fields = "m.id_app_module,m.module_name,m.module_icon,m.module_url";

        if($data['user_role_id'] == 2)//only customer admin needs key is_admin_menu
            $select_fields = "m.id_app_module,m.is_admin_menu,m.module_name,m.module_icon,m.module_url";
        
        $menu = $this->getMenu(array('select'=>$select_fields,'user_role_id' => $data['user_role_id'],'menu_type'=>1,'parent_module_id'=>0));
		
        foreach($menu as $k => $v){
            $menu[$k]['sub_menu'] = $this->getMenu(array('select'=>$select_fields,'user_role_id' => $data['user_role_id'],'menu_type'=>2,'parent_module_id'=>$v['id_app_module']));
        }
       //echo $this->db->last_query();
        $menu_array = array();

        /*for ($s = 0; $s < count($menu); $s++) {
            if ($menu[$s]['sub_module'] == 1) {
                if (!isset($menu_array[$menu[$s]['id_app_module']]))
                    $menu_array[$menu[$s]['id_app_module']] = array(
                        'module_name' => $menu[$s]['module_name'],
                        'module_icon' => $menu[$s]['module_icon'],
                        'module_url' => $menu[$s]['module_url']
                    );
                $menu_array[$menu[$s]['id_app_module']]['childs'][] = array(
                    'child_name' => $menu[$s]['child_label'],
                    'child_icon' => $menu[$s]['child_icon'],
                    'url' => $menu[$s]['child_module_url']
                );
            }
            else
            {
                $menu_array[$menu[$s]['id_app_module']] = array(
                    'module_name' => $menu[$s]['module_name'],
                    'module_icon' => $menu[$s]['module_icon'],
                    'module_url' => $menu[$s]['module_url'],
                    'childs' => array()
                );
            }
        }*/
        //echo "<pre>"; print_r($menu_array); exit;
        //$menu = array_values($menu_array);
        $menu = array_values($menu);
       return $menu;
    }

    public function getModules($data)
    {
        $this->db->select('am.*,ama.*,amac.user_role_id,amac.app_module_access_status');
        $this->db->from('app_module am');
        $this->db->join('app_module_action ama','am.id_app_module=ama.app_module_id','left');
        $this->db->join('app_module_access amac','ama.id_app_module_action=amac.app_module_action_id and amac.user_role_id = '.$this->db->escape($data["user_role_id"]).'','left');
        if(isset($data['module_url']))
            $this->db->where('am.module_url',$data['module_url']);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function getMenu($data)
    {
        /*$query = $this->db->query('select m.*,m1.module_name as child_label,m1.module_icon as child_icon, m1.module_name as child_module_name,m1.module_key as child_module_key,m1.parent_module_id as child_parent_module_id,m1.module_url as child_module_url
                          from `app_module` m
                          LEFT JOIN `app_module` m1 on m.id_app_module=m1.parent_module_id
                          where m.is_menu=1 and (m.id_app_module or m1.id_app_module in
	                       (select DISTINCT(ma.app_module_id) from app_module_action ma
		                                        LEFT JOIN app_module_access mc on ma.id_app_module_action=mc.app_module_action_id
		                                        where mc.user_role_id='.$data['user_role_id'].' and mc.app_module_access_status=1))

		                  GROUP BY m.id_app_module ORDER BY m.module_order ASC,m1.module_order ASC',FALSE);*/
        $query = $this->db->query('SELECT '.$data['select'].' FROM `app_module` m
	                               LEFT JOIN app_module_action mc on m.id_app_module=mc.app_module_id
                                   LEFT JOIN app_module_access mac on mc.id_app_module_action=mac.app_module_action_id
                                   WHERE is_menu='.$data['menu_type'].' and mac.user_role_id='.$this->db->escape($data['user_role_id']).' and parent_module_id = '.$data['parent_module_id'].'
                                   GROUP BY m.id_app_module ORDER BY m.module_order');//echo $this->db->last_query();exit;
        return $query->result_array();

    }

    public function addUserLog($data)
    {
        $this->db->insert('user_log', $data);
        return 1;
    }

    public function addAccessLog($data)
    {
        $this->db->insert('access_log', $data);
        return 1;
    }

    public function getUserCount($data){
        $this->db->select('count(*) as count');
        $this->db->from('user');
        $this->db->where('customer_id',$data['customer_id']);
        if(isset($data['role']) && $data['role']==3)
            $this->db->where('user_role_id',$data['role']);
        elseif(isset($data['role']) && $data['role']==4)
            $this->db->where('user_role_id',$data['role']);
        elseif(isset($data['role']) && $data['role']==5)
            $this->db->where('user_role_id',$data['role']);
        elseif(isset($data['role']) && $data['role']==6)
            $this->db->where('user_role_id',$data['role']);
        $result = $this->db->get();
        return $result->row_array();

    }

    public function getActionList($data){
        /*if(isset($data['search']))
            $data['search']=$this->db->escape($data['search']);*/
        $this->db->select('al.*,CONCAT(u.first_name," ",u.last_name) user_name,CONCAT(u1.first_name," ",u1.last_name) acting_user_name');
        $this->db->from('access_log al');
        $this->db->join('user u','u.id_user = al.user_id','left');
        $this->db->join('user u1','u1.id_user = al.acting_user_id','left');
        $this->db->where('al.access_token',$data['access_token']);
        if(isset($data['search'])){
            $this->db->group_start();
            $this->db->like('al.name', $data['search'], 'both');
            $this->db->or_like('al.module_type', $data['search'], 'both');
            $this->db->or_like('al.action_name', $data['search'], 'both');
            $this->db->or_like('al.action_description', $data['search'], 'both');
            $this->db->or_like('al.action_url', $data['search'], 'both');
            $this->db->group_end();
        }
        /*if(isset($data['search']))
            $this->db->where('(al.name like "%'.$data['search'].'%"
            or al.module_type like "%'.$data['search'].'%"
            or al.action_name like "%'.$data['search'].'%"
            or al.action_description like "%'.$data['search'].'%"
            or al.action_url like "%'.$data['search'].'%")');*/
        $all_records_db = clone $this->db;
        $all_records_count = $all_records_db->get()->num_rows();


        if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
            $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        if(isset($data['sort']['predicate']) && $data['sort']['predicate']!='' && isset($data['sort']['reverse']))
            $this->db->order_by($data['sort']['predicate'],$data['sort']['reverse']);
        else
            $this->db->order_by('al.id_access_log','DESC');
        $result = $this->db->get();

        return array('total_records'=>$all_records_count,'data'=>$result->result_array());
    }
    public function getLoggedUserId()
    {
        $this->db->select('IF(child_user_id IS NULL,parent_user_id,child_user_id) as id,child_user_id,parent_user_id');
        $this->db->from('user_login u');
        $this->db->where('access_token', str_replace('Bearer ','',$_SERVER['HTTP_AUTHORIZATION']));
        $query = $this->db->get();
        return $query->result_array();
    }
    public function getUserLogin($data)
    {
        $this->db->select('*');
        $this->db->from('user_login');
        $this->db->where('access_token', $data['access_token']);
        $query = $this->db->get();
        return $query->result_array();
    }
    public function addUserLogin($data)
    {
        $this->db->insert('user_login', $data);
        return 1;
    }
    public function updateUserLogin($data)
    {
        $this->db->where('access_token', $data['access_token']);
        $this->db->update('user_login', $data);
        return 1;
    }
    public function getPreviousUserSessions($data)
    {
        $this->db->select('*,oat.id as access_token_id');
        $this->db->from('oauth_clients oc');
        $this->db->join('oauth_sessions os','oc.id=os.client_id','left');
        $this->db->join('oauth_access_tokens oat','os.id=session_id','left');
        $this->db->where('oc.user_id',$data['user_id']);
        if(isset($data['timestamp']))
            $this->db->where('oat.expire_time>',$data['timestamp']);
        if(isset($data['access_token']))
            $this->db->where('oat.access_token',$data['access_token']);
        if(isset($data['user_id']))
            $this->db->where('oc.user_id',$data['user_id']);
        if(isset($data['expired_null']))
            $this->db->where('expired_date_time',null);

        $query=$this->db->get();
        return $query->result_array();
    }
    public function insertdata($tablename,$data)
    {
        $this->db->insert($tablename,$data);
        return $this->db->insert_id();
    }
    public function insertbatch($tablename,$data)
    {
        $this->db->insert_batch($tablename, $data);
        return $this->db->insert_id();
    }

    public function update_data($table,$data,$where)
    {
        $this->db->where($where);
        $this->db->update($table, $data);
        return $this->db->affected_rows();
    }

    public function delete_data($table,$where)
    {
        $this->db->delete($table, $where);
       return $this->db->affected_rows();
    }
    public function joinfunction($select=null,$table,$joindata=null,$where=null)
    {
        $select=empty($select)?'*':$select;
        $this->db->select($select);
        $this->db->from($table);
        if(!empty($joindata)){
            foreach($joindata as $join){
                $this->db->join($join['join_table'],$join['join_condition'],$join['join_type']);
            }
            if(!empty($where)){
                $this->db->where($where);
            }
            $query = $this->db->get();
            return $query->result_array();
        }    
    }
    public function getuserlist($data=null)
    {
        $this->db->select('u.id as user_id,u.user_role_id,u.agency_id,u.first_name,u.last_name,u.email,u.phone_no,u.gender,a.name as agency_name');
        $this->db->from('user u');
        $this->db->join('agency a','u.agency_id=a.id','left');

        if(isset($data['user_role_id']) && $data['user_role_id']>0){
            if($data['user_role_id']==4){
                $this->db->join('student s','u.id=s.user_id','left');
            }
            $this->db->where('u.user_role_id',$data['user_role_id']);
        }
        if(isset($data['agency_id']) && $data['agency_id']>0){
            $this->db->where('u.agency_id',$data['agency_id']);
        }
        if(!empty($data['user_id'])){
            $this->db->where('u.id',$data['user_id']);
        }
        if(isset($data['user_status'])){
            $this->db->where('u.user_status',$data['user_status']);
        }
        else{
            $this->db->where('u.user_status','1');
        }
        if(isset($data['search_key']) && $data['search_key']!==''){
            $this->db->group_start();
            $this->db->where('u.first_name like "%'.$data['search_key'].'%" or u.last_name like "%'.$data['search_key'].'%" or CONCAT(u.first_name,\' \',u.last_name) like "%'.$data['search_key'].'%" or u.email like "%'.$data['search_key'].'%"  or u.phone_no like "%'.$data['search_key'].'%"');
            if(isset($data['search_key']) && $data['search_key']!=='' && $data['user_role_id']==4){
               // $this->db->or_where('s.stuent like "%'.$data['search_key'].'%"');
                $this->db->or_where('s.mobile_phone1 like "%'.$data['search_key'].'%"');
            }
            $this->db->group_end();
        }
        $count_result_db = clone $this->db;
        $count_result = $count_result_db->get();
        $count_result = $count_result->num_rows();
        if(isset($data['offset']) && isset($data['limit']))
            $this->db->limit($data['limit'],$data['offset']);
        // if(isset($data['pagination']['number']) && $data['pagination']['number']!='')
        // $this->db->limit($data['pagination']['number'],$data['pagination']['start']);
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return array('total_records'=>$count_result,'data'=>$query->result_array());

    }

    public function menuList($data)
    {
        $this->db->select('ap.module_name,ap.module_key,module_url,ur.user_role_name,ap.id as app_module_id,ma.id as module_access_id,ma.user_role_id,ma.is_access_status,ap.module_icon,ma.id as module_access,ap.parent_module_id ');
        $this->db->from('app_module ap');
        $this->db->join('module_access ma','ap.id=ma.app_module_id','left');
        $this->db->join('user_role ur','ma.user_role_id=ur.id','left');
        if(isset($data['user_role_id']) && $data['user_role_id']>0){
            $this->db->where('ma.user_role_id',$data['user_role_id']);
        }
        if(isset($data['type']) && $data['type']=='menu'){
            $this->db->where('ap.is_menu',1);
            $this->db->where('ma.is_access_status',1);
            $this->db->order_by('ap.module_order','asc');

        }
        if(isset($data['module_url']) && $data['module_url']!=''){
            $this->db->where('ap.module_url',$data['module_url']);
            $this->db->where('ma.is_access_status',1);
        }
        // else{
        //     $this->db->where('ap.is_menu',0);  
        // }
        if(isset($data['parent_module_id']) && $data['parent_module_id']!=''){
            $this->db->where('ap.parent_module_id',$data['parent_module_id']);
        }
        if(isset($data['is_access_status']) && $data['is_access_status']!=''){
            $this->db->where('ma.is_access_status',$data['is_access_status']);
        }
        $query = $this->db->get();//echo $this->db->last_query();exit;
        return $query->result_array();
    }


    ///Email Templates:
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

    public function updateMailer($data)
    {
        if(isset($data['mailer_id'])) {
            $this->db->where('mailer_id', $data['mailer_id']);
            $this->db->update('mailer', $data);
            return 1;
        }
    }
    public function listTasks($data)
    {
        $this->db->select('t.id as task_id,t.description,t.date,t.trainer_id,t.status,u.user_role_id');
        $this->db->from('task t');
        $this->db->join('user u','u.id=t.trainer_id','left');
        $this->db->where_in('t.status',array(0,1));
        if($data['user_role_id'] !=1 && $data['user_role_id']!=2 && $data['user_role_id']!=4){
            $this->db->where('t.trainer_id',$data['user_id']);
        }
        else if($data['user_role_id'] ==2){
            $this->db->where('u.user_role_id',2);
            $this->db->where('u.agency_id',$data['agency_id']);
        }
        if(isset($data['date']))
            $this->db->where('t.date',$data['date']);
        if(isset($data['search']))
        {
            $this->db->group_start();
            $this->db->like('t.description', $data['search'], 'both');
            $this->db->group_end();
        }
        $all_clients_count_db=clone $this->db;
        $all_clients_count = $all_clients_count_db->get()->num_rows();

        if(isset($data['limit']) && isset($data['offset']))
           $this->db->limit($data['limit'],$data['offset']);
        
        $query = $this->db->get();
        return array('total_records' => $all_clients_count,'data' => $query->result_array());
    }
    public function update_data_batch($tablename,$data,$where){
        $this->db->update_batch($tablename,$data,$where);
        return $this->db->affected_rows();
    }
    public function update_where_in($table,$data,$wherein){
        if(isset($wherein)){
            foreach($wherein as $columnname=>$values){
                $this->db->where_in($columnname,$values);
            }
        }  

        $this->db->update($table, $data);
         return $this->db->affected_rows();
    }

    // public function getUsermodules($data)
    // {
    //     $this->db->select('ap.module_name,ap.module_key,module_url,ur.user_role_name,ap.id_app_module,ma.id_module_access,ma.user_role_id,ma.is_access_status,ap.module_icon');
    //     $this->db->from('app_module ap');
    //     $this->db->join('module_access ma','ap.id_app_module=ma.app_module_id','left');
    //     $this->db->join('user_role ur','ma.user_role_id=ur.id_user_role','left');
    //     if(isset($data['user_role_id']) && $data['user_role_id']>0){
    //         $this->db->where('ma.user_role_id',$data['user_role_id']);
    //         $this->db->where('ma.is_access_status',1);
    //         $query = $this->db->get();//echo $this->db->last_query();exit;
    //          return $query->result_array();
    //     }
    // }










    // public function getModuleListByRoles($data)
    // {
    //     $this->db->select('*');
    //     $this->db->from('app_module ap');
    //     $this->db->join('module_access ma','ap.id_app_module=ma.app_module_id','left');
    //     $this->db->join('user_role ur','ma.user_role_id=ur.id_user_role','left');
    //     if(isset($data['user_role_id']) && $data['user_role_id']>0){
    //         $this->db->where('ma.user_role_id',$data['user_role_id']);
    //     }
    //     $query = $this->db->get();
    //     return $query->result_array();echo $this
    // }

}


