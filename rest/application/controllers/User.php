<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class User extends REST_Controller
{
    public $order_data = array();
    public $cnt =1;

    public $user_id = 0 ;
    public $session_user_id=NULL;
    public $session_user_parent_id=NULL;
    public $session_user_id_acting=NULL;
    public $session_user_info=NULL;

    public function __construct()
    {
        parent::__construct();
        //$this->load->model('Validation_model');
        $getLoggedUserId=$this->User_model->getLoggedUserId();
        $this->session_user_id=$getLoggedUserId[0]['id'];
        $this->session_user_parent_id=$getLoggedUserId[0]['parent_user_id'];
        $this->session_user_id_acting=$getLoggedUserId[0]['child_user_id'];
        $this->session_user_info=$this->User_model->getUserInfo(array('user_id'=>$this->session_user_id));
    }
    // public function userInfo_get() //this function is to get user logged user information
    // {
    //     $data = $this->input->get();
    //     if(empty($data)){
    //         $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
    //         $this->response($result, REST_Controller::HTTP_OK);
    //     }
    //     //validating data
    //     $this->form_validator->add_rules('user_id', array('required'=> $this->lang->line('user_id_req')));
    //     $validated = $this->form_validator->validate($data);
    //     if($validated != 1)
    //     {
    //         $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
    //         $this->response($result, REST_Controller::HTTP_OK);
    //     }
        
    //     if(isset($data['user_id'])) {
    //         $data['user_id'] = $data['user_id'];
    //     }
        
    //     if(isset($data['user_role_id'])) {
    //         $data['user_role_id'] = $data['user_role_id'];
    //     }
        
    //     $result = $this->User_model->getUserInfo($data);
    //     //echo ''.$this->db->last_query(); exit;
    //     if(isset($result->id_user))
    //         $result->id_user= $result->id_user;
        
    //     if(isset($result->user_role_id))
    //         $result->user_role_id= $result->user_role_id;
    //     $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>$result);
    //     $this->response($result, REST_Controller::HTTP_OK);
    // }

    public function addUser_post() //this function is to add user data to the user table
    {
        //print_r($this->session_user_id);exit;
        $data = $this->input->post();
         //print_r($data); exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }

        $firstNameRules   = array(
            'required'=> $this->lang->line('first_name_req'),
            'max_len-100' => $this->lang->line('first_name_len'),
        );
        $lastNameRules    = array(
            'required'=> $this->lang->line('last_name_req'),
            'max_len-100' => $this->lang->line('last_name_len'),
        );
        $emailRules = array(
            'required'=> $this->lang->line('email_req'),
            'valid_email' => $this->lang->line('email_invalid')
        );
        $is_manual_passwordRules = array(
            'required'=> $this->lang->line('is_manual_password_req')
        );
        $passwordRules   = array(
            'required'=> $this->lang->line('password_req'),
            'min_len-8' => $this->lang->line('password_num_min_len'),
            'max_len-20' => $this->lang->line('password_num_max_len'),
        );
        $phonennodRules   = array(
            'required'=> $this->lang->line('phone_num_req'),
            'min_len-10' => $this->lang->line('phone_num_min_len'),
            'max_len-10' => $this->lang->line('phone_num_max_len'),
        );
        $stdentphonennodRules   = array(
            'required'=> $this->lang->line('std_phone_num_req'),
            'min_len-10' => $this->lang->line('std_phone_num_min_len'),
            'max_len-10' => $this->lang->line('std_phone_num_max_len'),
        );
        $this->form_validator->add_rules('password', array('required' => $this->lang->line('password_req')));
        $this->form_validator->add_rules('agency_id', array('required' => $this->lang->line('agency_id_req')));
        $this->form_validator->add_rules('user_role_id', array('required' => $this->lang->line('user_role_id_req')));
        $this->form_validator->add_rules('first_name', $firstNameRules);
        $this->form_validator->add_rules('last_name', $lastNameRules);
        $this->form_validator->add_rules('email', $emailRules);
        $this->form_validator->add_rules('phone_no', $phonennodRules);

        if(isset($data['user_role_id']) && $data['user_role_id']==4){
            $this->form_validator->add_rules('mobile_phone1', $stdentphonennodRules); 
            $this->form_validator->add_rules('school_id', array('required' => $this->lang->line('school_req')));
            $this->form_validator->add_rules('grade', array('required' => $this->lang->line('grade_req')));
            $this->form_validator->add_rules('parent', array('required' => $this->lang->line('parent_req')));
        }


        $validated = $this->form_validator->validate($data);
        if($validated != 1){
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        //print_r($validated);exit;
        $validated = $this->form_validator->validate($data);    
        if($validated != 1){
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        if(isset($data['user_id']) && $data['user_id']>0){
            $email_check = $this->User_model->check_email(array('email' => $data['email'],'id'=>$data['user_id']));//echo $this->db->last_query();exit;
            if(!empty($email_check)){
                $result = array('status'=>FALSE,'error'=>array('email' => $this->lang->line('email_duplicate')),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }else{
            $email_check = $this->User_model->check_email(array('email' => $data['email']));
            if(!empty($email_check)){
                $result = array('status'=>FALSE,'error'=>array('email' => $this->lang->line('email_duplicate')),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }

        $user_data = array(
            'user_role_id' => isset($data['user_role_id'])?$data['user_role_id']:5,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => md5($data['password']),
            'gender' => isset($data['gender']) ? $data['gender'] : '',
            'user_status' => 1,
            'profile_image' =>isset($data['profile_image'])?$data['profile_image']:'',
            'address'=>$data['address'],
            'phone_no'=>$data['phone_no'],
            'agency_id'=>$data['agency_id']
        );

        if(isset($data['user_role_id']) && $data['user_role_id']==4){
            $student_data=array(
                'school_id'=>isset($data['school'])?$data['school'] :'',
                'grade'=>isset($data['grade'])?$data['grade'] :'',
                'mother_tongue'=>isset($data['mother_tongue'])?$data['mother_tongue'] : '',
                'parent'=>isset($data['parent'])?$data['parent'] : '',
                'mobile_phone1'=>isset($data['mobile_phone1'])?$data['mobile_phone1'] : '',
                'mobile_phone2'=>isset($data['mobile_phone2'])?$data['mobile_phone2'] : '',
                'blood_group'=>isset($data['blood_group'])?$data['blood_group'] : '',
                'history_of_illness'=>isset($data['history_of_illness'])?$data['history_of_illness'] : ''  
            );
        }

        if(isset($data['user_id']) && $data['user_id']>0){
            $user_data['updated_by'] = !empty($this->session_user_id)?$this->session_user_id:'0';
            $user_data['updated_on'] = currentDate();
            $is_update = $this->User_model->update_data('user',$user_data,array('id'=>$data['user_id']));
            if(isset($data['user_role_id']) && $data['user_role_id']==4){
                $student_data['updated_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $student_data['updated_on']=currentDate();
                 $this->User_model->update_data('student',$student_data,array('user_id'=>$data['user_id']));
            }
            if($is_update>0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('user_update'), 'data'=>array('data' => $data['user_id']));
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }

        }
        else{
            $user_data['created_on'] = currentDate();
            $user_data['created_by'] = !empty($this->session_user_id)?$this->session_user_id:'0';
            $is_insert = $this->User_model->insertdata('user',$user_data);
            if(isset($data['user_role_id']) && $data['user_role_id']==4){
                $student_data['created_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $student_data['created_on']=currentDate();
                $student_data['user_id']=$is_insert;
                $this->User_model->insertdata('student',$student_data);
            }
            if($is_insert>0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('user_add'), 'data'=>array('data' => $is_insert));
                $this->response($result, REST_Controller::HTTP_OK);   
            }else{
                $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }
    }
    public function getUserList_get(){
        $data = $this->input->get();
        $result=$this->User_model->getuserlist($data);
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $result['data'],'total_records'=>$result['total_records']));
         $this->response($result, REST_Controller::HTTP_OK); 
    }

    public function rolesManagementList_get()
    {
        $data = $this->input->get();
        //print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $this->form_validator->add_rules('user_role_id', array('required' => $this->lang->line('user_role_id_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $modules= $this->User_model->menuList(array('user_role_id'=>$data['user_role_id']));
        $user_roles= $this->User_model->check_record('user_role');
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('modules' => $modules,'user_roles'=>$user_roles));
        $this->response($result, REST_Controller::HTTP_OK);
    }
    
    public function updateRolesManagement_post(){
        $data=$this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('app_module_id', array('required' => $this->lang->line('app_module_id_req')));
        $this->form_validator->add_rules('user_role_id', array('required' => $this->lang->line('user_role_id_req')));
        $this->form_validator->add_rules('is_access_status', array('required' => $this->lang->line('access_status_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $is_update=$this->User_model->update_data('module_access',array('is_access_status'=>$data['is_access_status']),array('app_module_id'=>$data['app_module_id'],'user_role_id'=>$data['user_role_id']));
        if(isset($is_update)){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('roles_updated'), 'data'=>array());
            $this->response($result, REST_Controller::HTTP_OK); 
        }
        else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
    }

    public function access_get()
    {
        $data=$this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }  
        $this->form_validator->add_rules('user_role_id', array('required' => $this->lang->line('user_role_id_req')));
        $this->form_validator->add_rules('module_url', array('required' => $this->lang->line('module_url_req')));
        $validated = $this->form_validator->validate($data);    
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $modules=$this->User_model->menuList(array('user_role_id'=>$data['user_role_id'],'module_url'=>$data['module_url']));
       // print_r($modules);exit;
        if(count($modules)>0){
            $access=TRUE;
        }
        else{
            $access=FALSE;
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('access'=>$access));
        $this->response($result, REST_Controller::HTTP_OK);
        
    }

    

    ///script fro create module access//////
    //  public function addmodule_accessdata_get(){
    //    $modules=$this->User_model->check_record('app_module',array());
    //    $user_roles=$this->User_model->check_record('user_role',array());
    //    //print_r($user_roles);exit;
    //     foreach($modules as $m=>$module){
    //         foreach($user_roles as $u=>$user_role){
    //             $previous_data= $this->User_model->check_record('module_access',array('user_role_id'=>$user_role['id_user_role'],'app_module_id'=>$module['id_app_module']));
    //             if(count($previous_data)==0){
    //                // echo $this->db->last_query();exit;
    //                $id= $this->User_model->insertdata('module_access',array('user_role_id'=>$user_role['id_user_role'],'app_module_id'=>$module['id_app_module'],'created_on'=>currentDate()));
    //                 print_r($id);
    //             }
    //         }
    //     }
    //     echo 'access module tables data inserted';
    // }
    
    public function Delete_delete($table,$id){
        if($this->User_model->update_data($table,array('status'=>2),array('id'=>$id))){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }else{
            $result = array('status'=>FALSE, 'message' => $this->lang->line('invalid_data'), 'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
    }
}


