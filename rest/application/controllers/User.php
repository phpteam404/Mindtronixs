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
        $getLoggedUserId=$this->User_model->getLoggedUserId();//echo $this->db->last_query();exit;
        $this->session_user_id=$getLoggedUserId[0]['id'];
        $this->session_user_parent_id=$getLoggedUserId[0]['parent_user_id'];
        $this->session_user_id_acting=$getLoggedUserId[0]['child_user_id'];
        $this->session_user_info=$this->User_model->getUserInfo(array('user_id'=>$this->session_user_id));
    }
    public function addUser_post() //this function is to add user data to the user table
    {
        $data = $this->input->post();
        // print_r($data);exit;
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
        $mobile2   = array(
            'min_len-10' => $this->lang->line('std1_phone_num_min_len'),
            'max_len-10' => $this->lang->line('std1_phone_num_max_len'),
        );

        // $this->form_validator->add_rules('password', array('required' => $this->lang->line('password_req')));
        // $this->form_validator->add_rules('franchise_id', array('required' => $this->lang->line('franchise_id_req')));
        $this->form_validator->add_rules('user_role_id', array('required' => $this->lang->line('user_role_id_req')));
        $this->form_validator->add_rules('email', $emailRules);
        $this->form_validator->add_rules('phone_no', $phonennodRules);
        if(empty($data['user_id'])){

            $this->form_validator->add_rules('password', $passwordRules);
        }

        if(isset($data['user_role_id']) && $data['user_role_id']==4){
            $this->form_validator->add_rules('school_id', array('required' => $this->lang->line('school_req')));
            $this->form_validator->add_rules('grade', array('required' => $this->lang->line('grade_req')));
            $this->form_validator->add_rules('parent_name', array('required' => $this->lang->line('parent_req')));
            $this->form_validator->add_rules('fee_structure', array('required' => $this->lang->line('franchise_fee_id_req')));
            $this->form_validator->add_rules('date_of_birth', array('required' => $this->lang->line('date_of_birth_req')));
            $this->form_validator->add_rules('blood_group', array('required' => $this->lang->line('blood_group_req')));
            $this->form_validator->add_rules('nationality', array('required' => $this->lang->line('nationality_req')));
        }
        if(isset($data['user_role_id']) && $data['user_role_id']!=4)
        {
            $this->form_validator->add_rules('first_name', $firstNameRules);
           // $this->form_validator->add_rules('last_name', $lastNameRules);
        }
        if($this->session_user_info->user_role_id==2 && $data['user_role_id']==4){
            $data['franchise_id']=$this->session_user_info->franchise_id;
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
            'first_name' => !empty($data['student_name'])? $data['student_name']:$data['first_name'],
            'last_name' => !empty($data['last_name'])?$data['last_name']:'',
            'email' => !empty($data['email'])?$data['email']:'',
            //'gender' => isset($data['gender']) ? $data['gender'] : '',
            'user_status' => isset($data['status'])?$data['status']:'1',
            // 'profile_image' =>isset($data['profile_image'])?$data['profile_image']:'',
            'address'=>isset($data['address'])?$data['address']:'',
            'phone_no'=>isset($data['phone_no'])?$data['phone_no']:'',
            'franchise_id'=>!empty($data['franchise_id'])?$data['franchise_id']:'0'
        );
        // print_r($user_data);exit;
        if(isset($data['user_role_id']) && $data['user_role_id']==4){
            $student_data=array(
                'school_id'=>isset($data['school_id'])?$data['school_id'] :0,
                'franchise_id'=>isset($data['franchise_id'])?$data['franchise_id'] :2,
                'nationality'=>isset($data['nationality'])?$data['nationality'] :null,
                'place_of_birth'=>isset($data['place_of_birth'])?$data['place_of_birth'] :null,
                'date_of_birth'=>isset($data['date_of_birth'])?$data['date_of_birth'] :null,
                'grade'=>isset($data['grade'])?$data['grade'] :'',
                'mother_tongue'=>isset($data['mother_tongue'])?$data['mother_tongue'] : '',
                'parent'=>isset($data['parent_name'])?$data['parent_name'] : '',
                'mobile_phone1'=>isset($data['mobile_phone1'])?$data['mobile_phone1'] : '',
                'mobile_phone2'=>isset($data['mobile_phone2'])?$data['mobile_phone2'] : '',
                'blood_group'=>isset($data['blood_group'])?$data['blood_group'] : '',
                'history_of_illness'=>isset($data['history_of_illness'])?$data['history_of_illness'] : '',
                'franchise_fee_id'=>isset($data['fee_structure'])?$data['fee_structure'] : '',
                'status'=>isset($data['status'])?$data['status'] :'1',
                'relation_with_student'=>isset($data['relation'])?$data['relation'] :'',
                'occupation'=>isset($data['occupation'])?$data['occupation'] :''

            );
        }
        // print_r($student_data);exit;
        if(isset($data['user_id']) && $data['user_id']>0){
            $user_data['updated_by'] = !empty($this->session_user_id)?$this->session_user_id:'0';
            $user_data['updated_on'] = currentDate();
            $is_update = $this->User_model->update_data('user',$user_data,array('id'=>$data['user_id']));
            if(isset($data['user_role_id']) && $data['user_role_id']==4){
                $student_data['updated_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $student_data['updated_on']=currentDate();
                $this->User_model->update_data('student',$student_data,array('user_id'=>$data['user_id']));
                $this->User_model->update_data('user',array('user_status'=>$data['status']),array('id'=>$data['user_id']));


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
            $user_data['password'] = !empty($data['password'])?md5($data['password']):'';
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
        // $data = tableOptions($data);
        $result=$this->User_model->getuserlist($data);//echo $this->db->last_query();exit;
        foreach($result['data'] as $k=>$v){
            if(!empty($data['user_id'])){
                $result['data'][$k]['status']=getStatusObj($v['status']);
                if($v['user_role_id']==5){
                    $result['data'][$k]['franchise_name']='--';
                }
                else{
                    $result['data'][$k]['franchise_name']=getObjOnId($v['franchise_name'],!empty($v['franchise_name'])?true:false);
                }
                $result['data'][$k]['user_role']=getObjOnId($v['user_role'],!empty($v['user_role'])?true:false);
                
            }
            else{
                $result['data'][$k]['status']=getStatusText($v['status']);
                // if()
                // print_r($v);exit;
                if($v['user_role_id']==5){
                    $result['data'][$k]['franchise_name']='--';
                }
                else{
                    $result['data'][$k]['franchise_name']=getObjOnId($v['franchise_name'],!empty($v['franchise_name'])?false:true);
                }
                $result['data'][$k]['user_role']=getObjOnId($v['user_role'],!empty($v['user_role'])?false:true);
                
            }
        }
        if(!empty($data['user_id'])){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $result['data']));
        }
        else{
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $result['data'],'total_records'=>$result['total_records'],'table_headers'=>getTableHeads('all_users_list')));
            
        }
         $this->response($result, REST_Controller::HTTP_OK); 
    }

    public function rolesManagementList_get()
    {
        $data = $this->input->get();
        $modules=array();
        if(!isset($data['dropdown']))
            $modules= $this->User_model->menuList(array('user_role_id'=>!empty($data['user_role_id'])?$data['user_role_id']:1));//echo $this->db->last_query();exit;
        $user_roles= $this->User_model->getUserRoles(array('dropdown'=>isset($data['dropdown'])?true:false));
        //echo $this->db->last_query();exit;
        foreach($user_roles as $k=>$v){
            $user_roles[$k]['value']=(int)$v['value'];
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('modules' => $modules,'user_roles'=>$user_roles));
        $this->response($result, REST_Controller::HTTP_OK);
    }
    
    public function updateRolesManagement_post(){
        $data=$this->input->post();
            // print_r($data);exit;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $modules=$data['modules'];
        // $modules= $this->User_model->menuList(array('user_role_id'=>2));
        foreach($modules as $m=>$module){
            $update_data[$m]['id']=$module['module_access_id'];
            $update_data[$m]['is_access_status']=$module['is_access_status'];    
        }
        $is_update=$this->User_model->update_data_batch('module_access',$update_data,'id');//echo $this->db->last_query();exit;
        if(isset($is_update)){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('roles_updated'), 'data'=>array());
            $this->response($result, REST_Controller::HTTP_OK); 
        }
        else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
    }

    public function access_post()
    {
        $data=$this->input->post();
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
    
    public function Delete_delete(){
        $data=$this->input->get();
        $table=$data['tablename'];
        $id=$data['id'];
        if($table=='user'){
            $this->User_model->update_data($table,array('user_status'=>2),array('id'=>$id));
            $result = array('status'=>TRUE, 'message' => $this->lang->line('delete_sc'), 'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        else{
            // $this->User_model->update_data($table,array('status'=>2),array('id'=>$id));echo $this->db->last_query();exit;
            if($this->User_model->update_data($table,array('status'=>2),array('id'=>$id))){ 
                $result = array('status'=>TRUE, 'message' => $this->lang->line('delete_sc'), 'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }else{
                $result = array('status'=>FALSE, 'message' => $this->lang->line('invalid_data'), 'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }
    }
    public function addTraineSchedule_post()
    {
        $data = $this->input->post();
        //   print_r($data);exit;
        $data['user_role_id'] = $this->session_user_info->user_role_id;
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        
        $this->form_validator->add_rules('date', array('required'=>$this->lang->line('task_date')));
        if($data['user_role_id'] ==2 || $data['user_role_id'] ==1){
            $this->form_validator->add_rules('user_id', array('required'=>$this->lang->line('user_id_req')));
        }
        $this->form_validator->add_rules('description',array('required' =>$this->lang->line('task_desc')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }

        if(isset($data['created_by'])) {
            $data['created_by'] = $data['created_by'];
            if($data['created_by']!=$this->session_user_id){
                $result = array('status'=>FALSE, 'error' =>array('message'=>$this->lang->line('permission_not_allowed')), 'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }

        $add = array(
            'date' => $data['date'],
            'trainer_id' =>isset($data['user_id'])?$data['user_id']:$this->session_user_id,
            'description'  =>$data['description'],
            'status' =>isset($data['status'])?$data['status']:1,
    
        );
        if(isset($data['id']) && $data['id']>0){
            $add['updated_by'] = $this->session_user_id;
            $add['updated_on'] = currentDate();
            // print_r($add);exit;
            $update = $this->User_model->update_data('task',$add,array('id'=>$data['id']));
            // echo $this->db->last_query(); exit;
            if($update>0){
                $result = array('status'=>TRUE, 'message' => $this->lang->line('task_update'),'data' =>'2');
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'4');
                $this->response($result, REST_Controller::HTTP_OK);
            }

        }
        else{
            $add['created_on'] = currentDate();
            $add['created_by'] = $this->session_user_id;
            $addData = $this->User_model->insertdata('task',$add);
            //echo ''.$this->db->last_query(); exit;
            if($addData >0){
             $result = array('status'=>TRUE, 'message' => $this->lang->line('task_create'), 'data' => '1');
             $this->response($result, REST_Controller::HTTP_OK);  
            }
            else{
                 $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'3');
                 $this->response($result, REST_Controller::HTTP_OK);
            }
        }
       
        $result = array('status'=>TRUE, 'message' => $this->lang->line('Success'), 'data'=>'5');
        $this->response($result, REST_Controller::HTTP_OK);

    }
    public function tasksList_get() //this function is used to get tasks list information
    {
        $data = $this->input->get();
        $data['user_id'] =$this->session_user_id;
        $data['user_role_id'] =$this->session_user_info->user_role_id;
        $data['franchise_id'] =$this->session_user_info->franchise_id;
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        // $data = tableOptions($data);
        $result = $this->User_model->listTasks($data);
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'),'data'=>array('data' =>$result['data'],'total_records' =>$result['total_records']));
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function studentList_get(){//this function is used to get list of students and prepopulate the student details when click student edit
        $data = $this->input->get();
        // $data = tableOptions($data);
        $data['type']='edit';//this key used for filter the select statement
        if($this->session_user_info->user_role_id==2){
            $data['franchise_id']=$this->session_user_info->franchise_id;
        }
        else{
            $data=null;
        }
        $student_list=$this->User_model->getStudentList($data);//echo $this->db->last_query();exit;
        // print_r($student_list);exit;
        foreach($student_list['data'] as $k=>$v){
            if(!empty($data['user_id'])){
                $student_list['data'][$k]['blood_group']=getObjOnIdOfBloodGroup($v['blood_group'],!empty($v['blood_group'])?true:false);//getting the bloodgroup dropdown object values 
                $student_list['data'][$k]['relation']=getObjOnId($v['relation'],!empty($v['relation'])?true:false);
                $student_list['data'][$k]['grade']=getObjOnId($v['grade'],!empty($v['grade'])?true:false);//getting object  of  dropdown grade field
                $student_list['data'][$k]['nationality']=getObjOnId($v['nationality'],!empty($v['nationality'])?true:false);
                $student_list['data'][$k]['mother_tongue']=getObjOnId($v['mother_tongue'],!empty($v['mother_tongue'])?true:false);
                $student_list['data'][$k]['fee_structure']=getObjOnId($v['fee_structure'],!empty($v['fee_structure'])?true:false);
                $student_list['data'][$k]['status']=getStatusObj($v['status'],!empty($v['status'])?true:false);
                $student_list['data'][$k]['date_of_birth']=date('Y-m-d', strtotime($v['date_of_birth']));
                $student_list['data'][$k]['school_id']=getObjOnId($v['school_id'],!empty($v['school_id'])?true:false);



            }
            else{
                $student_list['data'][$k]['grade']=getObjOnId($v['grade'],!empty($v['grade'])?false:true);//getting the garde value for list service
                $student_list['data'][$k]['status']=getStatusText($v['status']);

            }
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data'=>$student_list['data'],'total_records'=>$student_list['total_records'],'table_headers'=>getTableHeads('students_list')));
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function studentInfo_get()
    {
        //this function is used to get the student information
       $data=$this->input->get();
       if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('user_id', array('required'=>$this->lang->line('user_id_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }   
       $data['type']='view';//this key used for filter the select statement
       $student_info=$this->User_model->getStudentList($data);//echo $this->db->last_query();exit;//this model is used to get the student data
        
       $result = array('status'=>TRUE, 'message' =>$this->lang->line('success'), 'data'=>array('data'=>$student_info['data'],'last_invoice_amount'=>'10,000','student_history'=>'student_history'));
       $this->response($result, REST_Controller::HTTP_OK);
    }
     
    public function addTrainerSchedule_post(){
        $data=$this->input->post();
       if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $this->form_validator->add_rules('topic', array('required'=>$this->lang->line('trainer_schedule_id_req')));
        $this->form_validator->add_rules('date', array('required'=>$this->lang->line('date_req')));
        $this->form_validator->add_rules('from_time', array('required'=>$this->lang->line('from_time_req')));
        $this->form_validator->add_rules('to_time', array('required'=>$this->lang->line('to_time_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $trainerschedule=array(
            'topic'=>$data['topic'],
            'date'=>$data['date'],
            'description'=>$data['description'],
            'from_time'=>$data['from_time'],
            'to_time'=>$data['to_time'],
            'status'=>isset($data['status'])?$data['status']:1
        );
        if(isset($data['trainer_schedule_id']) && $data['trainer_schedule_id']){
            $trainerschedule['updated_on']=currentDate();
            $trainerschedule['updated_by']=$this->session_user_id;
            $is_update=$this->User_model->update_data('trainer_schedule',$trainerschedule,array('id'=>$data['trainer_schedule_id']));
            if(isset($is_update)){
                $result = array('status'=>TRUE, 'message' =>$this->lang->line('trainerschedule_update'), 'data'=>array('data'=>$data['trainer_schedule_id']));
                $this->response($result, REST_Controller::HTTP_OK);
            }

        }
        else{
            $trainerschedule['created_on']=currentDate();
            $trainerschedule['created_by']=$this->session_user_id;
            $inserted_id=$this->User_model->insert_data('trainer_schedule',$trainerschedule);
            if($inserted_id>0){
                $result = array('status'=>TRUE, 'message' =>$this->lang->line('trainerschedule_add'), 'data'=>array('data'=>$inserted_id));
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'1');
                $this->response($result, REST_Controller::HTTP_OK);   
            }
        }
    }
    public function trainerScheduleList_get(){//this function is used to get the sudent 
       $data=$this->input->get();
       if(!empty($data['trainer_schedule_id'])){
            $data['type']='edit'; 
            // for
            $trainerschedulelist= $this->User_model->getTrainerScheduleList($data);//echo $this->db->last_query();exit;
            // print_r($trainerschedulelist);exit;
            // print_r($trainerschedulelist['data']);exit;
            // print_r($trainerschedulelist['data'][0]['from_time']);exit;
            // $trainerschedulelist['data'][0]['from_time']=date("h:i A", strtotime($trainerschedulelist['data'][0]['from_time']));
            // $trainerschedulelist['data'][0]['to_time']=date("h:i A", strtotime($trainerschedulelist['data'][0]['to_time']));
            $result = array('status'=>TRUE, 'message' =>$this->lang->line('success'), 'data'=>array('data'=>$trainerschedulelist['data']));

        }
        else{
            $trainerschedulelist= $this->User_model->getTrainerScheduleList($data); 
            foreach($trainerschedulelist['data'] as $k=>$v){
               $trainerschedulelist['data'][$k]['from_time']=date("h:i A", strtotime($v['from_time']));
                $trainerschedulelist['data'][$k]['to_time']=date("h:i A", strtotime($v['to_time']));
            }
            $result = array('status'=>TRUE, 'message' =>$this->lang->line('success'), 'data'=>array('data'=>$trainerschedulelist['data'],'total_records'=>$trainerschedulelist['total_records'],'table_headers'=>getTableHeads('trainer_schedule_list')));
        }
        // echo $this->db->last_query();exit;
        $this->response($result, REST_Controller::HTTP_OK);
    }
    
}


