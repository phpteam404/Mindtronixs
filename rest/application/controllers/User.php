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
        //  print_r(json_encode($data));exit;
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
        );
        $stdentphonennodRules   = array(
            'required'=> $this->lang->line('std_phone_num_req'),
            'min_len-10' => $this->lang->line('std_phone_num_min_len'),
        );
        $mobile2   = array(
            'min_len-10' => $this->lang->line('std1_phone_num_min_len'),
            'max_len-10' => $this->lang->line('std1_phone_num_max_len'),
        );

        // $this->form_validator->add_rules('password', array('required' => $this->lang->line('password_req')));
        // $this->form_validator->add_rules('franchise_id', array('required' => $this->lang->line('franchise_id_req')));
        $this->form_validator->add_rules('user_role_id', array('required' => $this->lang->line('user_role_id_req')));
        if(isset($data['user_role_id']) && $data['user_role_id']!=4){
            $this->form_validator->add_rules('email', $emailRules);
            $this->form_validator->add_rules('phone_no', $phonennodRules);
        }

        if(empty($data['user_id']) && $data['user_role_id']!=4){

            $this->form_validator->add_rules('password', $passwordRules);
        }

        if(isset($data['user_role_id']) && $data['user_role_id']==4){
            // $this->form_validator->add_rules('school_id', array('required' => $this->lang->line('school_req')));
            // $this->form_validator->add_rules('grade', array('required' => $this->lang->line('grade_req')));
            // $this->form_validator->add_rules('parent_name', array('required' => $this->lang->line('parent_req')));
            // $this->form_validator->add_rules('fee_structure', array('required' => $this->lang->line('franchise_fee_id_req')));
            // $this->form_validator->add_rules('date_of_birth', array('required' => $this->lang->line('date_of_birth_req')));
            $this->form_validator->add_rules('lead_source', array('required' => $this->lang->line('lead_source_req')));
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
            if(!empty($data['email'])){
                $email_check = $this->User_model->check_email(array('email' => $data['email'],'id'=>$data['user_id']));//echo $this->db->last_query();exit;
                if(!empty($email_check)){
                    $result = array('status'=>FALSE,'error'=>array('email' => $this->lang->line('email_duplicate')),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
        }else{
            if(!empty($data['email'])){
                $email_check = $this->User_model->check_email(array('email' => $data['email']));
                if(!empty($email_check)){
                    $result = array('status'=>FALSE,'error'=>array('email' => $this->lang->line('email_duplicate')),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
        }
        // print_r($sudent_data_insert);exit;
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
            'school_id'=>!empty($data['school_id'])?$data['school_id'] :0,
            'franchise_id'=>!empty($data['franchise_id'])?$data['franchise_id']:'0'
        );
        // print_r($user_data);exit;
        if(isset($data['user_role_id']) && $data['user_role_id']==4){
            $student_data=array(
                'school_id'=>!empty($data['school_id'])?$data['school_id'] :0,
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
                'occupation'=>isset($data['occupation'])?$data['occupation'] :'',
                'school_name_text'=>isset($data['school_name'])?$data['school_name'] :'',
                'lead_source'=>isset($data['lead_source'])?$data['lead_source'] :''
                


            );
        }
        // print_r(json_encode($data));exit;
        if(isset($data['user_id']) && $data['user_id']>0){
            $user_data['updated_by'] = !empty($this->session_user_id)?$this->session_user_id:'0';
            $user_data['updated_on'] = currentDate();
            $is_update = $this->User_model->update_data('user',$user_data,array('id'=>$data['user_id']));
            $message="User Updated successfully.";
            if(isset($data['user_role_id']) && $data['user_role_id']==4){
                $student_data['updated_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $student_data['updated_on']=currentDate();
                if(!empty($student_data['franchise_fee_id'])){
                    $next_invoice_date=$this->getInvoiceDate($student_data['franchise_fee_id']);
                }
                $student_data['next_invoice_date']=!empty($next_invoice_date['next_invoice_date'])?$next_invoice_date['next_invoice_date']:'';
                $student_data['remaining_invoice_days']=!empty($next_invoice_date['days'])?$next_invoice_date['days']:0;
                $student_data['subscription_status']=1;
                $this->User_model->update_data('student',$student_data,array('user_id'=>$data['user_id']));
                $this->User_model->update_data('user',array('user_status'=>$data['status']),array('id'=>$data['user_id']));
                $message="Student Updated successfully.";

            }
            if($is_update>0){
                $result = array('status'=>TRUE, 'message' => $message, 'data'=>array('data' => $data['user_id']));
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
            $message="User Created successfully.";
            if(isset($data['user_role_id']) && $data['user_role_id']==4){
                $student_data['created_by']=!empty($this->session_user_id)?$this->session_user_id:'0';
                $student_data['created_on']=currentDate();
                $student_data['user_id']=$is_insert;
                if(!empty($student_data['franchise_fee_id'])){
                    $next_invoice_date=$this->getInvoiceDate($student_data['franchise_fee_id']);
                }
                $student_data['next_invoice_date']=!empty($next_invoice_date['next_invoice_date'])?$next_invoice_date['next_invoice_date']:'';
                $student_data['remaining_invoice_days']=!empty($next_invoice_date['days'])?$next_invoice_date['days']:0;
                $student_data['subscription_status']=1;
                $student_id=$this->User_model->insertdata('student',$student_data);//echo $this->db->last_query();exit;
                $message="Student created successfully.";

            }
            if($is_insert>0){
                $result = array('status'=>TRUE, 'message' => $message, 'data'=>array('data' => $is_insert));
                $this->response($result, REST_Controller::HTTP_OK);   
            }else{
                $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_data')),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }
    }
    
    public function getUserList_get(){
        $data = $this->input->get();
        if($this->session_user_info->user_role_id==2 || $this->session_user_info->user_role_id==5){
            $data['franchise_id']=$this->session_user_info->franchise_id;
        }
        $result=$this->User_model->getuserlist($data);//echo $this->db->last_query();exit;
        foreach($result['data'] as $k=>$v){
            if(!empty($data['user_id'])){
                $result['data'][$k]['status']=getStatusObj($v['status']);
                if($v['user_role_id']==5){
                    $result['data'][$k]['franchise_name']='--';
                }
                else{
                    if(!empty($v['franchise_name'])){
                        $result['data'][$k]['franchise_name']=getObjOnId($v['franchise_name'],!empty($v['franchise_name'])?true:false);
                    }
                    else{
                        $result['data'][$k]['franchise_name']='--'; 
                    }
                }
                $result['data'][$k]['user_role']=getObjOnId($v['user_role'],!empty($v['user_role'])?true:false);
                
            }
            else{
                // print_r( $result);exit;
                $result['data'][$k]['status']=getStatusText($v['status']);
                if($v['user_role_id']==1 || $v['user_role_id']==6 || $v['user_role_id']==7 || $v['user_role_id']==8){
                    $result['data'][$k]['franchise_name']='--';
                }
                else{
                    if(!empty($v['franchise_name'])){

                        $result['data'][$k]['franchise_name']=getObjOnId($v['franchise_name'],!empty($v['franchise_name'])?false:true);
                    }
                    else{
                        $result['data'][$k]['franchise_name']="--";
                    }
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
        if(!isset($data['dropdown'])){
            $modules= $this->User_model->menuList(array('only_menu'=>array(1,2),'user_role_id'=>!empty($data['user_role_id'])?$data['user_role_id']:1));//echo $this->db->last_query();exit;
            // foreach($modules as $mk => $mv){
            //     $modules[$mk]['create'] = (int)$mv['create'];
            //     $modules[$mk]['edit'] = (int)$mv['edit'];
            //     $modules[$mk]['view'] = (int)$mv['view'];
            //     $modules[$mk]['delete'] = (int)$mv['delete'];
            // }

        }
        $user_roles= $this->User_model->getUserRoles(array('dropdown'=>isset($data['dropdown'])?true:false));
        // echo $this->db->last_query();exit;
        if(isset($data['dropdown'])){
            foreach($user_roles as $k=>$v){
                $user_roles[$k]['value']=(int)$v['value'];
            }
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('modules' => $modules,'user_roles'=>$user_roles));
        $this->response($result, REST_Controller::HTTP_OK);
    }
    
    public function updateRolesManagement_post(){
        $data=$this->input->post();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        $modules=$data['modules'];
        // $modules= $this->User_model->menuList(array('user_role_id'=>2));
        foreach($modules as $m=>$module){
            $update_data[$m]['id']=$module['module_access_id'];
            $update_data[$m]['is_access_status']=$module['is_access_status'];    
            $update_data[$m]['create']=$module['create'];    
            $update_data[$m]['edit']=$module['edit'];    
            $update_data[$m]['view']=$module['view'];    
            $update_data[$m]['delete']=$module['delete'];
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
    //    print_r($modules);exit;
        if(count($modules)>0){
            $access = true;
            $role_access=array(
                'create'=>(int)$modules[0]['create'],
                'edit'=>(int)$modules[0]['edit'],
                'view'=>(int)$modules[0]['view'],
                'delete'=>(int)$modules[0]['delete']
            );
        }
        else{
            $access = false;
            $role_access=array(
                'create'=>0,
                'create'=>0,
                'create'=>0,
                'create'=>0
            );
        }
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('access'=>$access,'role_access'=>$role_access));
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
        if($table=='student'){
            $this->User_model->update_data('user    ',array('user_status'=>2),array('id'=>$id));
            $this->User_model->update_data($table,array('status'=>2),array('user_id'=>$id));
            $result = array('status'=>TRUE, 'message' => $this->lang->line('delete_sc'), 'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
        // if($table=='school_master'){
        //    $check_de $this->User_model->check_record();
        //     $this->User_model->update_data($table,array('user_status'=>2),array('id'=>$id));
        //     $result = array('status'=>TRUE, 'message' => $this->lang->line('delete_sc'), 'data'=>'');
        //     $this->response($result, REST_Controller::HTTP_OK);
        // }
        
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
        // else{
        //     $data=null;
        // }
        $student_list=$this->User_model->getStudentList($data);//echo $this->db->last_query();exit;
        // print_r($student_list);exit;
        foreach($student_list['data'] as $k=>$v){
            if(!empty($data['user_id'])){
                // print_r($v);exit;
                $student_list['data'][$k]['blood_group']=getObjOnIdOfBloodGroup($v['blood_group'],!empty($v['blood_group'])?true:false);//getting the bloodgroup dropdown object values 
                $student_list['data'][$k]['relation']=getObjOnId($v['relation'],!empty($v['relation'])?true:false);
                $student_list['data'][$k]['grade']=getObjOnId($v['grade'],!empty($v['grade'])?true:false);//getting object  of  dropdown grade field
                $student_list['data'][$k]['nationality']=getObjOnId($v['nationality'],!empty($v['nationality'])?true:false);
                $student_list['data'][$k]['mother_tongue']=getObjOnId($v['mother_tongue'],!empty($v['mother_tongue'])?true:false);
                $student_list['data'][$k]['fee_structure']=getMultipeObjOnId($v['fee_structure'],!empty($v['fee_structure'])?true:false);
                $student_list['data'][$k]['status']=getStatusObj($v['status'],!empty($v['status'])?true:false);
                // $student_list['data'][$k]['date_of_birth']=date('Y-m-d', strtotime($v['date_of_birth']));
                if($v['type_school_id']==0){
                    $student_list['data'][$k]['school_name']=$v['school_name_text'];
                }
                else{
                    $student_list['data'][$k]['school_id']=getObjOnId($v['school_id'],!empty($v['school_id'])?true:false);
                }



            }
            else{
                if(!empty($student_list['data'][$k]['grade'])){

                    $student_list['data'][$k]['grade']=getObjOnId($v['grade'],!empty($v['grade'])?false:true);//getting the garde value for list service
                }
                else{
                    $student_list['data'][$k]['grade']=''; 
                }
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
       $student_id=$this->User_model->check_record_selected('id  as student_id,franchise_fee_id,','student',array('user_id'=>$data['user_id']));
       $get_last_invoice_amount=$this->User_model->getLastInvoiceamount(array('student_id'=>$student_id[0]['student_id']));
       $result = array('status'=>TRUE, 'message' =>$this->lang->line('success'), 'data'=>array('data'=>$student_info['data'],'last_invoice_amount'=>!empty($get_last_invoice_amount[0]['last_invoice_amount'])?$get_last_invoice_amount[0]['last_invoice_amount']:0,'student_history'=>'student_history'));
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
            $trainerschedulelist= $this->User_model->getTrainerScheduleList($data);//echo $this->db->last_query();exit;
            $result = array('status'=>TRUE, 'message' =>$this->lang->line('success'), 'data'=>array('data'=>$trainerschedulelist['data']));

        }
        else{
            // print_r($this->session_user_info->user_role_id);exit;
            if($this->session_user_info->user_role_id==3){
                $data['created_by']=$this->session_user_info->user_id;
            }
            if($this->session_user_info->user_role_id==2 || $this->session_user_info->user_role_id==5){
                $data['franchise_id']=$this->session_user_info->franchise_id;
            }
            $table_headers=$this->session_user_info->user_role_id==3?getTableHeads('trainer_schedule_list'):getTableHeads('trainer_schedule_list1');
            //print_r(getTableHeads('trainer_schedule_list'));exit;
            $trainerschedulelist= $this->User_model->getTrainerScheduleList($data);// echo $this->db->last_query();exit;
            foreach($trainerschedulelist['data'] as $k=>$v){
               $trainerschedulelist['data'][$k]['from_time']=date("h:i A", strtotime($v['from_time']));
                $trainerschedulelist['data'][$k]['to_time']=date("h:i A", strtotime($v['to_time']));
            }
            $result = array('status'=>TRUE, 'message' =>$this->lang->line('success'), 'data'=>array('data'=>$trainerschedulelist['data'],'total_records'=>$trainerschedulelist['total_records'],'table_headers'=>$table_headers));
        }
        // echo $this->db->last_query();exit;
        $this->response($result, REST_Controller::HTTP_OK);
    }
    // function copyFeeStructureToStudent($student_id,$fee_master_id){
    //     //this function is used to copy fee data to student
    //     $get_fee_details=$this->User_model->check_record('fee_master',array('id'=>$fee_master_id));
    //     if(!empty($get_fee_details)){
    //         $student_fee_data=array(
    //             'student_id'=>$student_id,
    //             'student_fee_name'=>$get_fee_details[0]['name'],
    //             'student_fee_description'=>$get_fee_details[0]['description'],
    //             'student_fee_amount'=>$get_fee_details[0]['amount'],
    //             'term'=>$get_fee_details[0]['term'],
    //             'discount'=>$get_fee_details[0]['discount'],
    //             'discount_details'=>$get_fee_details[0]['discount_details'],
    //             'status'=>1,
    //             'created_by'=>!empty($this->session_user_id)?$this->session_user_id:'0',
    //             'created_on'=>currentDate() 
    //         );
    //         $is_insert=$this->User_model->insert_data('student_fee',$student_fee_data);
    //         return $is_insert;

    //     }
    // }
    public function updateProfile_post(){
        $data=$this->input->post();
        $data['user_id']=!empty($data['user_id'])?$data['user_id']:$this->session_user_info->user_id;
        if(!empty($data['first_name'])  || !empty($data['phone_no'])){
            $upadate_data=array(
                'first_name'=>!empty($data['first_name'])?$data['first_name']:'',
                'last_name'=>!empty($data['last_name'])?$data['last_name']:'',
                'phone_no'=>!empty($data['phone_no'])?$data['phone_no']:''
            );
              $check_phone_no=$this->User_model->check_not_in('user',array('phone_no'=>$data['phone_no']),array('id'=>$data['user_id']));
              if(!empty($check_phone_no)){
                  $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('phono_duplicate')),'data'=>'');
                  $this->response($result, REST_Controller::HTTP_OK);
              }
              $is_update= $this->User_model->update_data('user',$upadate_data,array('id'=>$data['user_id']));
              if(isset($is_update)){
                $result = array('status'=>TRUE, 'message' =>$this->lang->line('update_profile'), 'data'=>array('data'=>''));
                $this->response($result, REST_Controller::HTTP_OK);
             }
        }  
        if(!empty($data['old_password'])){
             $check_password=$this->User_model->check_record('user',array('id'=>$data['user_id']));
            // //  print_r(md5($data['old_password']));
            //  print_r($check_password[0]['password']);exit;
             if($check_password[0]['password']!=md5($data['old_password'])){
                $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invalid_password')),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
            else{
                $is_update= $this->User_model->update_data('user',array('password'=>md5($data['new_password'])),array('id'=>$data['user_id']));
                if(isset($is_update)){
                    $result = array('status'=>TRUE, 'message' =>$this->lang->line('update_profile'), 'data'=>array('data'=>''));
                    $this->response($result, REST_Controller::HTTP_OK);
                 }
            }
        }

        if(!empty($data['grade'])){
            // print_r($this->session_user_info->user_role_id);exit;
            if($this->session_user_info->user_role_id==4){      
                $is_update=$this->User_model->update_data('student',array('grade'=>$data['grade'],'type'=>$data['type'],'school_id'=>$data['school_name']),array('user_id'=>$data['user_id']));//echo $this->db->last_query();exit;
                if(isset($is_update)){
                    $result = array('status'=>TRUE, 'message' =>$this->lang->line('update_profile'), 'data'=>array('data'=>''));
                    $this->response($result, REST_Controller::HTTP_OK);
                 }
                 else{
                    $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
                    $this->response($result, REST_Controller::HTTP_OK);
                 }
            }
            else{
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
        }
    }
    public function profileInfo_get(){
        $data=$this->input->get();
        $data['user_id']=!empty($data['user_id'])?$data['user_id']:$this->session_user_info->user_id;
        $profile_data=$this->User_model->getProfileInfo($data);//echo $this->db->last_query();exit;
        if(!empty($profile_data)){
            $profile_data[0]['grade']= getObjOnId($profile_data[0]['grade'],!empty($profile_data[0]['grade'])?true:false);
            $profile_data[0]['type']= getObjOnId($profile_data[0]['type'],!empty($profile_data[0]['type'])?true:false);
            $profile_data[0]['school_name']= getObjOnId($profile_data[0]['school_name'],!empty($profile_data[0]['school_name'])?true:false);
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('profile_info'=>!empty($profile_data)?$profile_data:array()));
        }
        else{
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
        }
        $this->response($result, REST_Controller::HTTP_OK);
    }
    public function adminDashboard_get(){
        if(in_array($this->session_user_info->user_role_id,array(1,6,7,8))){
            $all_tickets = $this->User_model->check_record_selected('count(*) all_ticket_count','ticket',false);
            $pending_tickets = $this->User_model->check_record_selected('count(*) pending_tickets_count','ticket','status <> 48');
            $student_invoice_amounts = $this->User_model->check_record_selected('ROUND(SUM(total_amount)) total_amount, ROUND(SUM(paid_amount)) collected_amount','student_invoice',array('invoice_type' => 1));
            $franchise_invoice_amounts = $this->User_model->check_record_selected('ROUND(SUM(total_amount)) total_amount, ROUND(SUM(paid_amount)) collected_amount','student_invoice',array('invoice_type' => 3));
            $active_students = $this->User_model->check_record_selected('count(*) active_students','user',array('user_role_id'=>4,'user_status'=>1));
            $all_students = $this->User_model->check_record_selected('count(*) all_students','user',array('user_role_id'=>4));
            
            $data['number'] = 5; $data['start'] = 0;
            $data['sort'] = 'ticket_id'; $data['order'] = 'DESC';
            $ticket_list=$this->Ticket_model->getTickets($data);
            $result_array = array(
                'ticket' => array(
                    'all_tickets'=> (int)isset($all_tickets[0])?$all_tickets[0]['all_ticket_count']:0,
                    'pending_tickets'=> (int)isset($pending_tickets[0])?$pending_tickets[0]['pending_tickets_count']:0
                ),
                'student_invoice' => array(
                    'total_amount'=> (int)isset($student_invoice_amounts[0])?$student_invoice_amounts[0]['total_amount']:0,
                    'collected_amount'=> (int)isset($student_invoice_amounts[0])?$student_invoice_amounts[0]['collected_amount']:0
                ),
                'lc_invoice' => array(
                    'total_amount'=> (int)isset($franchise_invoice_amounts[0])?$franchise_invoice_amounts[0]['total_amount']:0,
                    'collected_amount'=> (int)isset($franchise_invoice_amounts[0])?$franchise_invoice_amounts[0]['collected_amount']:0
                ),
                'school_invoice' => array(
                    'total_amount'=> 0,
                    'collected_amount'=> 0
                ),
                'orders' => array(
                    'all_orders'=> 0,
                    'collected_amount'=> 0
                ),
                'students' => array(
                    'active_students'=> (int)isset($active_students[0])?$active_students[0]['active_students']:0,
                    'all_students'=> (int)isset($all_students[0])?$all_students[0]['all_students']:0
                ),
                'ticket_list' => $ticket_list['data']
            );
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>$result_array,'table_headers'=>getTableHeads('ticket_list'));
            $this->response($result, REST_Controller::HTTP_OK);
        }else if(in_array($this->session_user_info->user_role_id,array(2,5))){
            $all_tickets = $this->Ticket_model->getTickets(array('franchise_id' => $this->session_user_info->franchise_id));
            $pending_tickets = $this->Ticket_model->getTickets(array('franchise_id' => $this->session_user_info->franchise_id,'custom_where' => 't.status <> 48'));
            $student_invoice_amounts = $this->User_model->check_record_selected('ROUND(SUM(total_amount)) total_amount, ROUND(SUM(paid_amount)) collected_amount','student_invoice',array('invoice_type' => 1,'franchise_id' => $this->session_user_info->franchise_id));
            $franchise_invoice_amounts = $this->User_model->check_record_selected('ROUND(SUM(total_amount)) total_amount, ROUND(SUM(paid_amount)) collected_amount','student_invoice',array('invoice_type' => 3,'franchise_id' => $this->session_user_info->franchise_id));
            $active_students = $this->User_model->check_record_selected('count(*) active_students','user',array('franchise_id'=>$this->session_user_info->franchise_id,'user_role_id'=>4,'user_status'=>1));
            $all_students = $this->User_model->check_record_selected('count(*) inactive_students','user',array('franchise_id'=>$this->session_user_info->franchise_id,'user_role_id'=>4));

            $data['number'] = 5; $data['start'] = 0;
            $data['sort'] = 'ticket_id'; $data['order'] = 'DESC';
            $data['franchise_id'] = $this->session_user_info->franchise_id;
            $data['status'] = 46;
            $ticket_list=$this->Ticket_model->getTickets($data);
            $result_array = array(
                'ticket' => array(
                    'all_tickets'=> (int)isset($all_tickets[0])?$all_tickets[0]['all_ticket_count']:0,
                    'pending_tickets'=> (int)isset($all_tickets[0])?$pending_tickets[0]['pending_tickets_count']:0
                ),
                'student_invoice' => array(
                    'total_amount'=> (int)isset($student_invoice_amounts[0])?$student_invoice_amounts[0]['total_amount']:0,
                    'collected_amount'=> (int)isset($student_invoice_amounts[0])?$student_invoice_amounts[0]['collected_amount']:0
                ),
                'lc_invoice' => array(
                    'total_amount'=> (int)isset($franchise_invoice_amounts[0])?$franchise_invoice_amounts[0]['total_amount']:0,
                    'collected_amount'=> (int)isset($franchise_invoice_amounts[0])?$franchise_invoice_amounts[0]['collected_amount']:0
                ),
                'school_invoice' => array(
                    'total_amount'=> 0,
                    'collected_amount'=> 0
                ),
                'orders' => array(
                    'all_orders'=> 0,
                    'collected_amount'=> 0
                ),
                'students' => array(
                    'active_students'=> (int)isset($active_students[0])?$active_students[0]['active_students']:0,
                    'all_students'=> (int)isset($all_students[0])?$all_students[0]['all_students']:0
                ),
                'ticket_list' => $ticket_list['data']
            );
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>$result_array,'table_headers'=>getTableHeads('ticket_list'));
            $this->response($result, REST_Controller::HTTP_OK);
        }else if(in_array($this->session_user_info->user_role_id,array(10))){
            $all_tickets = $this->Ticket_model->getTickets(array('school_id' => $this->session_user_info->school_id));
            $pending_tickets = $this->Ticket_model->getTickets(array('school_id' => $this->session_user_info->school_id,'custom_where' => 't.status <> 48'));
            
            $student_invoice_amounts = $this->User_model->check_record_selected('ROUND(SUM(total_amount)) total_amount, ROUND(SUM(paid_amount)) collected_amount','student_invoice',array('invoice_type' => 1,'school_id' => $this->session_user_info->school_id));
            $school_invoice_amounts = $this->User_model->check_record_selected('ROUND(SUM(total_amount)) total_amount, ROUND(SUM(paid_amount)) collected_amount','student_invoice',array('invoice_type' => 3,'school_id' => $this->session_user_info->school_id));
            $active_students = $this->User_model->check_record_selected('count(*) active_students','user',array('school_id' => $this->session_user_info->school_id,'user_role_id'=>4,'user_status'=>1));
            $all_students = $this->User_model->check_record_selected('count(*) all_students','user',array('school_id' => $this->session_user_info->school_id,'user_role_id'=>4));

            $data['number'] = 5; $data['start'] = 0;
            $data['sort'] = 'ticket_id'; $data['order'] = 'DESC';
            $data['school_id'] = $this->session_user_info->school_id;
            $data['status'] = 46;
            $ticket_list=$this->Ticket_model->getTickets($data);
            $result_array = array(
                'ticket' => array(
                    'all_tickets'=> (int)isset($all_tickets[0])?$all_tickets[0]['all_ticket_count']:0,
                    'pending_tickets'=> (int)isset($all_tickets[0])?$pending_tickets[0]['pending_tickets_count']:0
                ),
                'student_invoice' => array(
                    'total_amount'=> (int)isset($student_invoice_amounts[0])?$student_invoice_amounts[0]['total_amount']:0,
                    'collected_amount'=> (int)isset($student_invoice_amounts[0])?$student_invoice_amounts[0]['collected_amount']:0
                ),
                'lc_invoice' => array(
                    'total_amount'=> 0,
                    'collected_amount'=> 0
                ),
                'school_invoice' => array(
                    'total_amount'=> (int)isset($school_invoice_amounts[0])?$school_invoice_amounts[0]['total_amount']:0,
                    'collected_amount'=> (int)isset($school_invoice_amounts[0])?$school_invoice_amounts[0]['collected_amount']:0
                ),
                'orders' => array(
                    'all_orders'=> 0,
                    'collected_amount'=> 0
                ),
                'students' => array(
                    'active_students'=> (int)isset($active_students[0])?$active_students[0]['active_students']:0,
                    'all_students'=> (int)isset($all_students[0])?$all_students[0]['all_students']:0
                ),
                'ticket_list' => $ticket_list['data']
            );
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>$result_array,'table_headers'=>getTableHeads('ticket_list'));
            $this->response($result, REST_Controller::HTTP_OK);
        }else{
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>[]);
            $this->response($result, REST_Controller::HTTP_OK);
        }
    }
    function getInvoiceDate($franchise_fee_id){
        $date=date("Y-m-d");
        $day=date("d");
        $term_type=$this->User_model->getTermTypeKey(array('fee_master_id'=>$franchise_fee_id));
        // print_r($term_type);exit;
        if(!empty($term_type[0]['child_key'])){
            if($term_type[0]['child_key']==MONTHLY_TERM_KEY){
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+1 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
                else{
                    $curren_date=date_create(date("Y-m-d"));
                    $end_of_month_date=date_create(date("Y-m-t"));
                    $diff_days=date_diff($curren_date,$end_of_month_date);//it will return the no of days b/w current and endof month day 
                    $next_invoice_date= date('Y-m-01', strtotime($date .'+2 month'));
                    return array('days'=>$diff_days->days,'next_invoice_date'=>$next_invoice_date);
                }
            }
            if($term_type[0]['child_key']==HALFYEARLY_TERM_KEY){
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+6 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
                else{
                   
                    $next_invoice_date= date('Y-m-01', strtotime($date .'+7 month')); 
                    return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
            }
            if($term_type[0]['child_key']==QUARTERYL_TERM_KEY){
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+3 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+4 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
            }
            if($term_type[0]['child_key']==ANNUAL_TERM_KEY){
                if($day<=10){
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+12 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
                else{
                     $next_invoice_date= date('Y-m-01', strtotime($date .'+13 month'));
                     return array('next_invoice_date'=>$next_invoice_date,'days'=>'0');
                }
            }
        }
    }
    
}
