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

        $data = $this->input->post(); //print_r($data);exit;
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
        
        
        $data['password'] = base64_decode($data['password']);//print_r($data['password']);exit;
        $mailCheck = $this->User_model->check_email(array('email_id'=>$data['username']));

        $result = $this->User_model->login($data);//echo $this->db->last_query();exit;
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

                
        $server = $_SERVER;
        /* User log start */
         $this->User_model->addUserLog(array(
                    'user_id' => $result->user_id,
                    'client_browser' => $server['HTTP_USER_AGENT'],
                    'client_os' => getUserOS($server['HTTP_USER_AGENT']),
                    'client_remote_address' => $server['REMOTE_ADDR'],
                    'logged_on' => currentDate()
        ));
         $this->User_model->addUserLogin(array(
                'parent_user_id' => $result->user_id,
                'child_user_id' => NULL,
                'access_token' => isset($token->access_token)?$token->access_token:NULL
        ));
            $this->User_model->update_data('user',array('last_login'=>currentDate()),array('id'=>$result->user_id));    
        if(isset($result->user_id))
            $result->user_id= $result->user_id;
    
        if(isset($result->user_role_id))
            $result->user_role_id=$result->user_role_id;
            $menu=$this->User_model->menuList(array('user_role_id'=>$result->user_role_id,'type'=>'menu','parent_module_id'=>0,'is_menu'=>1));
            foreach($menu as $k=>$v){
                $sub_menus=$this->User_model->menuList(array('user_role_id'=>$result->user_role_id,'parent_module_id'=>$v['app_module_id'],'type'=>'menu','is_menu'=>2));
                $menu[$k]['sub_menus']=$sub_menus;
        }
        $check_franchise=$this->User_model->check_record('franchise',array('id'=>$result->franchise_id));
        if($check_franchise[0]['status']==1){
            $result = array('status'=>TRUE, 'message' => $this->lang->line('success'), 'data'=>array('data' => $result), 'access_token' => $access_token,'menu'=>$menu);
            header('Content-Type: application/json');
            echo json_encode($result);exit;
        }
        else{
            $result = array('status'=>FALSE,'error'=>array('message'=>$this->lang->line('franchise_status_inactive')),'data'=>'');
            echo json_encode($result);exit;
        }

    }

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