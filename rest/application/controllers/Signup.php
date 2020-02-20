<?php

defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(0);
require APPPATH . '/third_party/mailer/mailer.php';

class Signup extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $language = 'en';
        if(isset($_SERVER['HTTP_LANG']) && $_SERVER['HTTP_LANG']!=''){
            $language = $_SERVER['HTTP_LANG'];
            if(is_dir('application/language/'.$language)==0){
                $language = $this->config->item('rest_language');
            }
        }
        $this->lang->load('rest_controller', $language);
    }


    public function login()
    {
        $this->load->library('oauth/oauth');
        $this->config->load('rest');

        $data = json_decode(file_get_contents("php://input"), true);
        if($data){ $_POST = $data; }

        if(isset($_POST['requestData']) && DATA_ENCRYPT)
        {
            $aesObj = new AES();
            $data = $aesObj->decrypt($_POST['requestData'],AES_KEY);
            $data = (array) json_decode($data,true);
            $_POST = $data;
        }

        $data = $this->input->post(); 
     //print_r($result);exit;
    // $result=array();
        if(empty($data)){
            $result = array('status'=>FALSE,'message'=>$this->lang->line('login_error'),'data'=>'');
            echo json_encode($result); exit;
        }

        //validating inputs
        $this->form_validator->add_rules('username', array('required'=> $this->lang->line('user_name')
                                                        //    'valid_email' => $this->lang->line('email_invalid')
                                                          ));
        $this->form_validator->add_rules('password', array('required'=> $this->lang->line('password_req')));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            echo json_encode($result);exit;
        }
        

        //decoding password
        $data['password'] = base64_decode($data['password']);
       // print_r($data);exit;
        $mailCheck = $this->User_model->check_email(array('email_id'=>$data['username']));

       // print_r($result);exit;
        $result = $this->User_model->login($data);
        // echo ''.$this->db->last_query();exit;
    //    print_r($result);exit;
        if(count($result)==0){
            $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('text_rest_invalid_credentials')),'data'=>'');
            echo json_encode($result);exit;
        }
        $access_token = '';
           
        $rest_auth = strtolower($this->config->item('rest_auth'));
        if($rest_auth=='oauth'){
                $client_credentials = $this->User_model->createOauthCredentials($result->user_id,$result->first_name,$result->last_name);
                $client_id = $client_credentials["client_id"];
                $secret  =$client_credentials["client_secret"];
                $this->load->library('Oauth');

                $_REQUEST['grant_type'] = 'client_credentials';
                $_REQUEST['client_id'] = $client_id;
                $_REQUEST['client_secret'] = $secret;
                $_REQUEST['scope'] = '';
                $oauth = $this->oauth;
                $token =(object) $oauth->generateAccessToken();
                $access_token = $token->token_type.' '.$token->access_token;
        }

                /* Updating last Login*/
        //$this->User_model->updateUser(array('last_logged_on' => currentDate()),$result->id_user);

                
        $server = $_SERVER;
        /* User log start */
         $this->User_model->addUserLog(array(
                    'user_id' => $result->user_id,
                    'client_browser' => $server['HTTP_USER_AGENT'],
                    'client_os' => getUserOS($server['HTTP_USER_AGENT']),
                    'client_remote_address' => $server['REMOTE_ADDR'],
                    'logged_on' => currentDate()
        ));
        /* User log end */
// print_r($result);exit;
            
         $this->User_model->addUserLogin(array(
                'parent_user_id' => $result->user_id,
                'child_user_id' => NULL,
                'access_token' => isset($token->access_token)?$token->access_token:NULL
        ));
            
        if(isset($result->user_id))
            $result->user_id= $result->user_id;
    
        if(isset($result->user_role_id))
            $result->user_role_id=$result->user_role_id;
            $menu=$this->User_model->menuList(array('user_role_id'=>$result->user_role_id,'type'=>'menu','parent_module_id'=>0));
            // echo $this->db->last_query();exit;
        foreach($menu as $k=>$v){
        $sub_menus=$this->User_model->menuList(array('user_role_id'=>$result->user_role_id,'parent_module_id'=>$v['app_module_id'],'is_access_status'=>1));
                $menu[$k]['sub_menus']=$sub_menus;
        }
        $check_agency=$this->User_model->check_record('agency',array('id'=>$result->agency_id));
        // print_r($check_agency[0]['status']);exit;
        if($check_agency[0]['status']==1){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $result), 'access_token' => $access_token,'menu'=>$menu);
            header('Content-Type: application/json');
            echo json_encode($result);exit;
        }
        else{
            $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('agency_status_inactive')),'data'=>'');
            echo json_encode($result);exit;
        }

        //echo 'dta';exit;
    }

    // public function login()
    // {
    //     $this->load->library('oauth/oauth');
    //     $this->config->load('rest');

    //     $data = json_decode(file_get_contents("php://input"), true);
    //     if($data){ $_POST = $data; }

    //     if(isset($_POST['requestData']) && DATA_ENCRYPT)
    //     {
    //         $aesObj = new AES();
    //         $data = $aesObj->decrypt($_POST['requestData'],AES_KEY);
    //         $data = (array) json_decode($data,true);
    //         $_POST = $data;
    //     }

    //     $data = $this->input->post();
    //     if(empty($data)){
    //         $result = array('status'=>FALSE,'message'=>$this->lang->line('login_error'),'data'=>'');
    //         echo json_encode($result); exit;
    //     }

    //     //validating inputs
    //     $this->form_validator->add_rules('email_id', array('required'=> $this->lang->line('email_req'),
    //                                                        'valid_email' => $this->lang->line('email_invalid')
    //                                                       ));
    //     $this->form_validator->add_rules('password', array('required'=> $this->lang->line('password_req')));
    //     $validated = $this->form_validator->validate($data);
    //     if($validated != 1)
    //     {
    //         $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
    //         echo json_encode($result);exit;
    //     }

    //     //decoding password
    //     $data['password'] = base64_decode($data['password']);

    //     $customer = $this->User_model->check_email(array('email'=>$data['email_id']));//echo '<pre>'.$this->db->last_query();exit;
    //     if(count($customer)==0){
    //         $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('text_rest_invalid_credentials')),'data'=>'');
    //         echo json_encode($result); exit;
    //     }
    //     $ldap_status = $this->User_model->check_record('customer_ldap',array('customer_id'=>$customer->customer_id,'status'=>1));

    //         if(isset($data['login_with_ldap']) && $data['login_with_ldap']==1){
    //             if(count($ldap_status)>0){
    //                 $params=array('host'=>$ldap_status[0]['host'],'port'=>$ldap_status[0]['port'],'dc'=>$ldap_status[0]['dc']);
    //                 $this->load->library('LdapAuthentication',$params);
    //                 $is_login=$this->ldapauthentication->login($data['email_id'],$data['password']);
    //                 if($is_login['status']===true){
    //                     $result = $this->User_model->ldap_login($data);
    //                 }
    //                 else{
    //                     //echo 'invalid ';
    //                     $result = array('status'=>FALSE,'error'=>array('message'=>$is_login['message']),'data'=>'');
    //                     //echo json_encode($result); exit;
    //                 }
    //             }else{
    //                 $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('text_rest_invalid_credentials')),'data'=>'');
    //                 echo json_encode($result); exit;
    //             }
    //         }else{
    //             $result = $this->User_model->login($data);
    //         }//echo '<pre>'.print_r($result);exit;
    //     $access_token = '';
    //     if(empty($result) || (isset($data['login_with_ldap']) && $data['login_with_ldap']==1 && $is_login['status']===false))
    //     {
    //         $user_info = $this->User_model->check_email(array('email'=>$data['email_id']));
    //         if(empty($user_info)){
    //             $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('invaid_user')),'data'=>'');
    //             echo json_encode($result);exit;
    //         }
    //         $is_blocked=$user_info->is_blocked;
    //         $last_password_attempt_date=$user_info->last_password_attempt_date;
    //         $no_of_password_attempts=$user_info->no_of_password_attempts;
    //         //echo '$is_blocked'.$is_blocked.' '.'$last_password_attempt_date'.$last_password_attempt_date.' '.'$no_of_password_attempts'.' '.$no_of_password_attempts;
    //         if($last_password_attempt_date==null){
    //             // || $last_password_attempt_date != date("Y-m-d")
    //             $attempt_date = date("Y-m-d");
    //             $no_of_password_attempts=1;
    //             $this->User_model->updateUser(array('no_of_password_attempts'=>1,'last_password_attempt_date'=>$attempt_date,'is_blocked'=>0),$user_info->id_user);
    //             $this->User_model->addLoginAttempts(array('email'=>$data['email_id'],'password'=>md5($data['password']),'client_browser'=>$_SERVER['HTTP_USER_AGENT'],'client_remote_address'=>filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ),'user_agent'=>$_SERVER['HTTP_USER_AGENT']));
    //             //$result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('two_more_attempts')),'data'=>'');
    //             //echo json_encode($result);exit;
    //         }
    //         else{
    //             //$last_password_attempt_date == date("Y-m-d")
    //             $no_of_password_attempts=$no_of_password_attempts+1;
    //             $this->User_model->updateUser(array('no_of_password_attempts'=>$no_of_password_attempts,'last_password_attempt_date'=>date("Y-m-d"),'is_blocked'=>0),$user_info->id_user);
    //             $this->User_model->addLoginAttempts(array('email'=>$data['email_id'],'password'=>md5($data['password']),'client_browser'=>$_SERVER['HTTP_USER_AGENT'],'client_remote_address'=>filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ),'user_agent'=>$_SERVER['HTTP_USER_AGENT']));
    //             /*if($no_of_password_attempts<3){
    //                 if($no_of_password_attempts >= 2){
    //                     $this->User_model->updateUser(array('no_of_password_attempts'=>$no_of_password_attempts+1,'last_password_attempt_date'=>date("Y-m-d"),'is_blocked'=>1),$user_info->id_user);
    //                     $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('account_block_error')),'data'=>'');
    //                     echo json_encode($result);exit;
    //                 }else{
    //                     $this->User_model->updateUser(array('no_of_password_attempts'=>$no_of_password_attempts+1,'last_password_attempt_date'=>date("Y-m-d")),$user_info->id_user);
    //                     $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('one_more_attempts')),'data'=>'');
    //                     echo json_encode($result);exit;
    //                 }
    //             }else{
    //                 $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('account_block_error')),'data'=>'');
    //                 echo json_encode($result);exit;
    //             }*/
    //         }
    //         if($no_of_password_attempts>=MAX_INVALID_PASSWORD_ATTEMPTS){
    //             $this->User_model->updateUser(array('is_blocked'=>1),$user_info->id_user);
    //             $client_browser = getUserBrowser($_SERVER['HTTP_USER_AGENT']);
    //             $this->User_model->addLoginAttempts(array('email'=>$data['email_id'],'password'=>md5($data['password']),'client_browser'=>$client_browser,'client_remote_address'=>filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ),'user_agent'=>$_SERVER['HTTP_USER_AGENT']));
    //             $result = array('status'=>FALSE,'error'=>array('message'=>str_replace('%s',MAX_INVALID_PASSWORD_ATTEMPTS,$this->lang->line('account_block_error'))),'data'=>'');
    //             echo json_encode($result);exit;
    //         }
    //         else{
    //             $result = array('status'=>FALSE,'error'=>array('message'=>str_replace('%s',MAX_INVALID_PASSWORD_ATTEMPTS-$no_of_password_attempts,$this->lang->line('one_more_attempts'))),'data'=>'');
    //             echo json_encode($result);exit;
    //         }
    //         /*if($last_password_attempt_date != date("Y-m-d")){
    //             $this->User_model->updateUser(array('no_of_password_attempts'=>1,'last_password_attempt_date'=>date("Y-m-d"),'is_blocked'=>0),$user_info->id_user);
    //             $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('two_more_attempts')),'data'=>'');
    //             echo json_encode($result);exit;
    //         }*/

    //     }
    //     else
    //     {
    //         if($result->user_status!=1) {
    //             $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('login_inactive_error')),'data'=>'');
    //             echo json_encode($result);exit;
    //         }
    //         if($result->is_blocked==1) {
    //             // && $result->last_password_attempt_date==date("Y-m-d")
    //             $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('account_block_error')),'data'=>'');
    //             echo json_encode($result);exit;
    //         }
    //         else{
    //             if($result->contribution_type=='2' || $result->contribution_type=='3'){   
    //                 $result->user_type = "external";
    //             }else{
    //                 $result->user_type = "internal";
    //             }
    //             $this->User_model->updateUser(array('no_of_password_attempts'=>0,'last_password_attempt_date'=>NULL,'is_blocked'=>0),$result->id_user);
    //             if($result->profile_image!='') {
    //                 $result->profile_image_medium = getImageUrl($result->profile_image, 'profile', MEDIUM_IMAGE,'profile_images/');
    //                 $result->profile_image_small = getImageUrl($result->profile_image, 'profile', SMALL_IMAGE,'profile_images/');
    //                 $result->profile_image = getImageUrl($result->profile_image, 'profile','','profile_images/');
    //             }

    //             if($result->user_role_id!=1) {
    //                 $customer = $this->Customer_model->getCustomer(array('id_customer' => $result->customer_id));
    //                 if(!empty($customer)){
    //                     if($customer[0]['company_logo']=='') {
    //                         $result->customer_logo_medium = getImageUrl($customer[0]['company_logo'], 'company');
    //                         $result->customer_logo_small = getImageUrl($customer[0]['company_logo'], 'company');
    //                         $result->customer_logo = getImageUrl($customer[0]['company_logo'], 'company');
    //                     }
    //                     else{
    //                         $result->customer_logo_medium = getImageUrl($customer[0]['company_logo'], 'profile', MEDIUM_IMAGE);
    //                         $result->customer_logo_small = getImageUrl($customer[0]['company_logo'], 'profile', SMALL_IMAGE);
    //                         $result->customer_logo = getImageUrl($customer[0]['company_logo'], 'profile');
    //                     }
    //                 }
    //             }

    //             if(!in_array($result->user_role_id,array(1,2))) {
    //                 $business_unit = $this->Business_unit_model->getBusinessUnitUser(array('user_id' => $result->id_user));
    //                 $result->business_unit = array();
    //                 for($s=0;$s<count($business_unit);$s++)
    //                 {
    //                     $result->business_unit[] = array(
    //                         'business_unit_id' => $business_unit[$s]['id_business_unit'],
    //                         'bu_name' => $business_unit[$s]['bu_name']
    //                     );
    //                 }
    //             }


    //             $menu = $this->User_model->menu(array('user_role_id' => $result->user_role_id));
    //             //echo $this->db->last_query(); exit;
    //             //echo $this->db->last_query(); exit;
    //             //$result->menu = $menu;
    //             $rest_auth = strtolower($this->config->item('rest_auth'));
    //             if($rest_auth=='oauth'){
    //                 $client_credentials = $this->User_model->createOauthCredentials($result->id_user,$result->first_name,$result->last_name);
    //                 $client_id = $client_credentials["client_id"];
    //                 $secret  =$client_credentials["client_secret"];
    //                 $this->load->library('Oauth');

    //                 $_REQUEST['grant_type'] = 'client_credentials';
    //                 $_REQUEST['client_id'] = $client_id;
    //                 $_REQUEST['client_secret'] = $secret;
    //                 $_REQUEST['scope'] = '';
    //                 $oauth = $this->oauth;
    //                 $token =(object) $oauth->generateAccessToken();
    //                 $access_token = $token->token_type.' '.$token->access_token;
    //             }

    //             /* Updating last Login*/
    //             $this->User_model->updateUser(array('last_logged_on' => currentDate()),$result->id_user);

    //             /* User log start */
    //             $server = $_SERVER;
    //             $this->User_model->addUserLog(array(
    //                 'user_id' => $result->id_user,
    //                 'client_browser' => $server['HTTP_USER_AGENT'],
    //                 'client_os' => getUserOS($server['HTTP_USER_AGENT']),
    //                 'client_remote_address' => $server['REMOTE_ADDR'],
    //                 'logged_on' => currentDate()
    //             ));
    //             /* User log end */
    //             $result->iroori='annus';
    //             if($result->user_role_id==6) {
    //                 $result->iroori="itako";
    //             }
    //             /*if(!empty($this->session->userdata('session_user_id_acting')))
    //                 $this->session->unset_userdata('session_user_id_acting');
    //             if(!empty($this->session->userdata('session_user_id')))
    //                 $this->session->unset_userdata('session_user_id');
    //             $this->session->set_userdata('session_user_id',$result->id_user);*/
    //             $this->User_model->addUserLogin(array(
    //                 'parent_user_id' => $result->id_user,
    //                 'child_user_id' => NULL,
    //                 'access_token' => isset($token->access_token)?$token->access_token:NULL
    //             ));
    //         }
    //     }
    //     if(isset($result->id_user))
    //         $result->id_user=pk_encrypt($result->id_user);
    //     if(isset($result->customer_id)){
    //         $result->import_subscription = (int)$this->User_model->check_record_selected('import_subscription','customer',array('id_customer'=>$result->customer_id))[0]['import_subscription'];
    //         $result->customer_id=pk_encrypt($result->customer_id);
    //     }
    //     if(isset($result->user_role_id))
    //         $result->user_role_id=pk_encrypt($result->user_role_id);
    //     $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $result,'menu' => $menu), 'access_token' => $access_token);
    //     echo json_encode($result);exit;
    // }

    public function forgetPassword()
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if($data){ $_POST = $data; }
        if(isset($_POST['requestData']) && DATA_ENCRYPT)
        {
            $aesObj = new AES();
            $data = $aesObj->decrypt($_POST['requestData'],AES_KEY);
            $data = (array) json_decode($data,true);
            $_POST = $data;
        }
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            echo json_encode($result);exit;
        }

        //validating data
        $this->form_validator->add_rules('email', array('required'=> $this->lang->line('email_req'),
                                                           'valid_email' => $this->lang->line('email_invalid')
                                                           ));
        $validated = $this->form_validator->validate($data);
        if($validated != 1)
        {
            $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
            echo json_encode($result);exit;
        }
        $result = $this->User_model->check_email(array('email' => $data['email']));
        if(empty($result)){
            //Message should not be shown
            $result = array('status'=>FALSE, 'message' => $this->lang->line('email_wrong'), 'data'=>'');
            echo json_encode($result);exit;
        }
        else
        {
            $new_password = generatePassword(8);
            $this->User_model->updatePassword($new_password,$result->id_user);

            $user_info = $this->User_model->getUserInfo(array('user_id' => $result->id_user));
            $template_configurations=$this->User_model->EmailTemplateList(array('customer_id' => $user_info->customer_id,'language_id' =>1,'module_key'=>'FORGOT_PASSWORD'));
            if($template_configurations['total_records']>0){
                $template_configurations=$template_configurations['data'][0];
                $wildcards=$template_configurations['wildcards'];
                $wildcards_replaces=array();
                $wildcards_replaces['first_name']=$user_info->first_name;
                $wildcards_replaces['last_name']=$user_info->last_name;
                $wildcards_replaces['customer_name']=$customer_name;
                $wildcards_replaces['logo']=$customer_logo;
                $wildcards_replaces['email']=$user_info->email;
                $wildcards_replaces['role']=$user_info->user_role_name;
                $wildcards_replaces['password']=$new_password;
                $wildcards_replaces['year'] = date("Y");
                $wildcards_replaces['url']=WEB_BASE_URL.'html';
                $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
                $subject=$template_configurations['template_subject'];
                $from_name=SEND_GRID_FROM_NAME;
                $from=SEND_GRID_FROM_EMAIL;
                $to=$user_info->email;
                $to_name=$user_info->first_name.' '.$user_info->last_name;
                $mailer_data['mail_from_name']=$from_name;
                $mailer_data['mail_to_name']=$to_name;
                $mailer_data['mail_to_user_id']=$user_info->id_user;
                $mailer_data['mail_from']=$from;
                $mailer_data['mail_to']=$to;
                $mailer_data['mail_subject']=$subject;
                $mailer_data['mail_message']=$body;
                $mailer_data['status']=0;
                $mailer_data['send_date']=currentDate();
                $mailer_data['is_cron']=0;
                $mailer_data['email_template_id']=$template_configurations['id_email_template'];
                $mailer_id=$this->User_model->addMailer($mailer_data);
                if($mailer_data['is_cron']==0) {
                    //$mail_sent_status=sendmail($to, $subject, $body, $from);
                    $this->load->library('sendgridlibrary');
                    $mail_sent_status=$this->sendgridlibrary->sendemail($from_name,$from,$subject,$body,$to_name,$to,array(),$mailer_id);
                    if($mail_sent_status==1)
                        $this->User_model->updateMailer(array('status'=>1,'mailer_id'=>$mailer_id));
                }
            }

            $result = array('status'=>TRUE, 'message' => $this->lang->line('new_password'), 'data'=>'');
            echo json_encode($result);exit;
        }
    }

    public function activeAccount($code)
    {
        $user = $this->User_model->activeAccount($code);
        if($user==1){
            echo "<h3>Account activated successfully.</h3>";
        }
        else{
            echo "<h3>Invalid request.</h3>";
        }
        redirect(WEB_BASE_URL);
    }
    public function getEncryptionSettings()
    {
        $data['AES_KEY']=AES_KEY;
        $data['DATA_ENCRYPT']=DATA_ENCRYPT;
        $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>$data);
        echo base64_encode(json_encode($result));exit;
    }

    public function renewalToken()
    {
        $data = $this->input->get();
        if(empty($data)){
            $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
            echo json_encode($result);exit;
        }
        $access_token = $data['Authorization'];
        $user_id = $data['User'];
        $res = $this->User_model->getTokenDetails($access_token,$user_id);
        if(empty($res)){
            $result = array('status'=>FALSE,'error'=>'Invalid token','data'=>'');
            echo json_encode($result);exit;
        }
        if(((time() - $res[0]['expire_time']) > 0)){
            $new_token = file_get_contents(REST_API_URL.'welcome/oauth?grant_type=client_credentials&client_id='.$res[0]['client_id'].'&client_secret='.$res[0]['secret'].'&scope=');
            $new_token = json_decode($new_token);
            $access_token = $new_token->token_type.' '.$new_token->access_token;
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>'', 'access_token' => $access_token);
        }
        else{
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>'', 'access_token' => $res[0]['access_token']);
        }
        echo json_encode($result);exit;
    }
    public function test(){
        $path='uploads/';
        $data['customer_id']='test';
        $path=FILE_SYSTEM_PATH.'uploads/';
        if(!is_dir($path.$data['customer_id'])){ mkdir($path.$data['customer_id']); }
    }
    function ldaptest(){
        $params=array('host'=>'ldaps://ldaps.with-services.com','port'=>'636','dc'=>'with-services,com');
        $this->load->library('LdapAuthentication',$params);
        //testuserscp@with-services.com
        $is_login=$this->ldapauthentication->logintest('testuserscp@with-services.com','Source2018!');
        var_dump($is_login);
        if($is_login===true){
            echo 'valid';
        }
        else{
            echo 'invalid';
        }
    }
    

}