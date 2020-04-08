<?php


class ClassName extends AnotherClass implements Interface
{
    
    public function ReviewActionItemUpdate_post()
        {
            $data = $this->input->post();
            //echo '<pre>';print_r($data);exit;
            if(empty($data)){
                $result = array('status'=>FALSE,'error'=>$this->lang->line('invalid_data'),'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
    
            $this->form_validator->add_rules('id_contract_review_action_item', array('required'=>$this->lang->line('id_contract_review_action_item_req')));
            $this->form_validator->add_rules('updated_by', array('required'=>$this->lang->line('updated_by_req')));
            $this->form_validator->add_rules('is_finish', array('required'=>$this->lang->line('is_finish_req')));
            $validated = $this->form_validator->validate($data);
            if($validated != 1)
            {
                $result = array('status'=>FALSE,'error'=>$validated,'data'=>'');
                $this->response($result, REST_Controller::HTTP_OK);
            }
            if(isset($data['id_contract_review_action_item'])) {
                $data['id_contract_review_action_item'] = pk_decrypt($data['id_contract_review_action_item']);
                if(!in_array($data['id_contract_review_action_item'],$this->session_user_contract_action_items)){
                    $result = array('status'=>FALSE, 'error' =>array('message'=>$this->lang->line('permission_not_allowed')), 'data'=>'1');
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
            if(isset($data['updated_by'])) {
                $data['updated_by'] = pk_decrypt($data['updated_by']);
                if($data['updated_by']!=$this->session_user_id){
                    $result = array('status'=>FALSE, 'error' =>array('message'=>$this->lang->line('permission_not_allowed')), 'data'=>'2');
                    $this->response($result, REST_Controller::HTTP_OK);
                }
            }
            if(isset($data['contract_id'])) {
                $data['contract_id'] = pk_decrypt($data['contract_id']);
                if(!in_array($data['contract_id'],$this->session_user_contracts)){
                    $result = array('status'=>FALSE, 'error' =>array('message'=>$this->lang->line('permission_not_allowed')), 'data'=>'3');
                    //$this->response($result, REST_Controller::HTTP_OK);
                }
            }
            $update = array();
    
            if(isset($data['id_contract_review_action_item'])){
                $update['id_contract_review_action_item'] = $data['id_contract_review_action_item'];
                $update['updated_by'] = $data['updated_by'];
                $update['updated_on'] = currentDate();
                if($data['is_finish']==1)
                    $update['status'] = 'completed';
                if(isset($data['comments']))
                    $update['comments'] = $data['comments'];
    
                //$current_records = $this->Contract_model->getActionItemDetails(array('id_contract_review_action_item' => $data['id_contract_review_action_item']));
    
    
                $this->Contract_model->updateContractReviewActionItem($update);
                $msg = $this->lang->line('contract_review_action_item_update');
            }
            $action_item_info = $this->Contract_model->getContractReviewActionItems(array('id_contract_review_action_item'=>$data['id_contract_review_action_item']));
            //$module_info = $this->Module_model->getModuleName(array('language_id'=>1,'module_id'=>$data['module_id']));
            $contract_info = $this->Contract_model->getContractDetails(array('id_contract' => $data['contract_id']));
            //$topic_info = $this->Topic_model->getTopicName(array('topic_id'=>$data['topic_id']));
            $cust_admin_info = $this->User_model->getUserInfo(array('user_id' => $contract_info[0]['created_by']));
            $customer_details = $this->Sample_mode->getCustomer(array('id_customer' => $cust_admin_info->customer_id));
            /*$cust_admin = $this->Sample_mode->getCustomerAdminList(array('customer_id' => $customer_details[0]['id_customer']));
            $cust_admin = $cust_admin['data'][0];*/
            $action_item = $action_item_info['data'][0];
             /*echo 'action_info'.'<pre>';print_r($action_item);
             echo 'contract_info'.'<pre>';print_r($contract_info);
             echo 'cust_admin'.'<pre>';print_r($cust_admin_info);
             echo 'customer_detail'.'<pre>';print_r($customer_details);
             echo 'to_id'.'<pre>';print_r($topic_info);exit;*/
            if($customer_details[0]['company_logo']=='') {
                $customer_logo = getImageUrlSendEmail($customer_details[0]['company_logo'], 'company');
            }
            else{
                $customer_logo = getImageUrlSendEmail($customer_details[0]['company_logo'], 'profile', SMALL_IMAGE);
            }
            if(!empty($customer_details)){ $customer_name = $customer_details[0]['company_name']; }
    
            $To = $this->Contract_model->getActionItemDetails(array('id_contract_review_action_item' => $data['id_contract_review_action_item']));
            $user_info = $this->User_model->getUserInfo(array('user_id' => $To[0]['created_by'],'user_status'=>1));
            $commented_by = $this->User_model->getUserInfo(array('user_id' => $To[0]['updated_by'],'user_status'=>1));
            $resoponsible_user_info = $this->User_model->getUserInfo(array('user_id' => $To[0]['responsible_user_id'],'user_status'=>1));
          /* echo '<pre>';print_r($To);
            echo '<pre>';print_r($user_info);
            echo '<pre>';print_r($resoponsible_user_info);exit;*/
            if($data['is_finish']!=1){
                if($To[0]['is_workflow'] == 1){                
                    $template_configurations_parent=$this->Sample_mode->EmailTemplateList(array('customer_id' => $cust_admin_info->customer_id,'language_id' =>1,'module_key'=>'CONTRACT_WORKFLOW_ACTION_ITEM_COMMENT'));
                }else{                
                    $template_configurations_parent=$this->Sample_mode->EmailTemplateList(array('customer_id' => $cust_admin_info->customer_id,'language_id' =>1,'module_key'=>'CONTRACT_REVIEW_ACTION_ITEM_COMMENT'));
                }
                if($template_configurations_parent['total_records']>0 && !empty($user_info)){
                    $template_configurations=$template_configurations_parent['data'][0];
                    $wildcards=$template_configurations['wildcards'];
                    $wildcards_replaces=array();
                    $wildcards_replaces['first_name']=$user_info->first_name;
                    $wildcards_replaces['last_name']=$user_info->last_name;
                    $wildcards_replaces['contract_name']=$contract_info[0]['contract_name'];
                    $wildcards_replaces['action_item_responsible_user']=$resoponsible_user_info->first_name.' '.$resoponsible_user_info->last_name.' ('.$resoponsible_user_info->user_role_name.')';
                    // $wildcards_replaces['contract_review_module_name']=$action_item['module_name'];
                    $wildcards_replaces['action_item_name']=$action_item['action_item'];
                    if(isset($To[0]['description']))
                        $wildcards_replaces['action_item_description']=$To[0]['description'];
                    $wildcards_replaces['action_item_due_date']=dateFormat($To[0]['due_date']);
                    $wildcards_replaces['action_item_comment']=$To[0]['comments'];
                    $wildcards_replaces['action_item_comment_user_name']=$commented_by->first_name.' '.$commented_by->last_name.' ('.$commented_by->user_role_name.')';
                    $wildcards_replaces['action_item_comment_date']=dateFormat($To[0]['updated_on']);
                    // $wildcards_replaces['contract_review_topic_name']=$action_item['topic_name'];
                    if($To[0]['is_workflow'] == 1){
                        $wildcards_replaces['contract_workflow_topic_name']=$action_item['topic_name'];
                        $wildcards_replaces['contract_workflow_module_name']=$action_item['module_name'];
                    }
                    else{
                        $wildcards_replaces['contract_review_topic_name']=$action_item['topic_name'];
                        $wildcards_replaces['contract_review_module_name']=$action_item['module_name'];
                    }
                    $wildcards_replaces['logo']=$customer_logo;
                    $wildcards_replaces['year'] = date("Y");
                    $wildcards_replaces['url']=WEB_BASE_URL.'html';
                    $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
                    $subject = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_subject']);
                    /*$from_name=SEND_GRID_FROM_NAME;
                    $from=SEND_GRID_FROM_EMAIL;
                    $from_name=$cust_admin['name'];
                    $from=$cust_admin['email'];*/
                    $from_name=$template_configurations['email_from_name'];
                    $from=$template_configurations['email_from'];
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
                    //print_r($mailer_data);
                    $mailer_id=$this->Sample_mode->addMailer($mailer_data);
                    if($mailer_data['is_cron']==0) {
                        //$mail_sent_status=sendmail($to, $subject, $body, $from);
                        $this->load->library('sendgridlibrary');
                        $mail_sent_status=$this->sendgridlibrary->sendemail($from_name,$from,$subject,$body,$to_name,$to,array(),$mailer_id);
                        if($mail_sent_status==1)
                            $this->Sample_mode->updateMailer(array('status'=>1,'mailer_id'=>$mailer_id));
                    }
    
                }
                ////start action Item Comment Email for external users
                if(isset($data['external_users']) && count(explode(',', $data['external_users']))>0){   
                    if($To[0]['is_workflow'] == 1){                    
                        $template_configurations_parent = $this->Sample_mode->EmailTemplateList(array('customer_id' => $cust_admin_info->customer_id, 'language_id' => 1, 'module_key' => 'CONTRACT_WORKFLOW_ACTION_ITEM_COMMENT_EXTERNAL_USER'));
                    }else{
                        $template_configurations_parent = $this->Sample_mode->EmailTemplateList(array('customer_id' => $cust_admin_info->customer_id, 'language_id' => 1, 'module_key' => 'CONTRACT_REVIEW_ACTION_ITEM_COMMENT_EXTERNAL_USER'));
                    }
                    if ($template_configurations_parent['total_records'] > 0) {
                        $template_configurations = $template_configurations_parent['data'][0];
                        $wildcards = $template_configurations['wildcards'];
                        $wildcards_replaces = array();
                        $external_users = explode(',', $data['external_users']);
                        foreach($external_users as $v){
                            $wildcards_replaces['first_name'] = $v;
                            //$wildcards_replaces['last_name'] = $user_info->last_name;
                            $wildcards_replaces['contract_name'] = $contract_info[0]['contract_name'];
                            $wildcards_replaces['action_item_responsible_user'] = $resoponsible_user_info->first_name.' '.$resoponsible_user_info->last_name.' ('.$resoponsible_user_info->user_role_name.')';
                            // $wildcards_replaces['contract_review_module_name'] = $action_item['module_name'];
                            $wildcards_replaces['action_item_name'] = $action_item['action_item'];
                            if(isset($To[0]['description']))
                                $wildcards_replaces['action_item_description']=$To[0]['description'];
                            $wildcards_replaces['action_item_due_date']=dateFormat($To[0]['due_date']);
                            $wildcards_replaces['action_item_comment']=$To[0]['comments'];
                            $wildcards_replaces['action_item_comment_user_name']=$commented_by->first_name.' '.$commented_by->last_name.' ('.$commented_by->user_role_name.')';
                            $wildcards_replaces['action_item_comment_date']=dateFormat($To[0]['updated_on']);
                            // $wildcards_replaces['contract_review_topic_name']=$action_item['topic_name'];
                            if($To[0]['is_workflow'] == 1){
                                $wildcards_replaces['contract_workflow_topic_name']=$action_item['topic_name'];
                                $wildcards_replaces['contract_workflow_module_name']=$action_item['module_name'];
                            }
                            else{
                                $wildcards_replaces['contract_review_topic_name']=$action_item['topic_name'];
                                $wildcards_replaces['contract_review_module_name']=$action_item['module_name'];
                            }
                            $wildcards_replaces['logo']=$customer_logo;
                            $wildcards_replaces['year'] = date("Y");
                            $wildcards_replaces['url']=WEB_BASE_URL.'html';
                            $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
                            $subject = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_subject']);
                            /*$from_name=SEND_GRID_FROM_NAME;
                            $from=SEND_GRID_FROM_EMAIL;
                            $from_name=$cust_admin['name'];
                            $from=$cust_admin['email'];*/
                            $from_name = $template_configurations['email_from_name'];
                            $from = $template_configurations['email_from'];
                            $to = $v;
                            //$to_name = $To->first_name . ' ' . $To->last_name;
                            $mailer_data['mail_from_name'] = $from_name;
                            $mailer_data['mail_to_name'] = '';
                            $mailer_data['mail_to_user_id'] = '';
                            $mailer_data['mail_from'] = $from;
                            $mailer_data['mail_to'] = $to;
                            $mailer_data['mail_subject'] = $subject;
                            $mailer_data['mail_message'] = $body;
                            $mailer_data['status'] = 0;
                            $mailer_data['send_date'] = currentDate();
                            $mailer_data['is_cron'] = 0;
                            $mailer_data['email_template_id'] = $template_configurations['id_email_template'];
                            //print_r($mailer_data);
                            $mailer_id = $this->Sample_mode->addMailer($mailer_data);
                            //sending mail to bu owner
                            if ($mailer_data['is_cron'] == 0) {
                                //$mail_sent_status=sendmail($to, $subject, $body, $from);
                                $this->load->library('sendgridlibrary');
                                $mail_sent_status = $this->sendgridlibrary->sendemail($from_name, $from, $subject, $body, $to_name, $to, array(), $mailer_id);
                                if ($mail_sent_status == 1)
                                    $this->Sample_mode->updateMailer(array('status' => 1, 'mailer_id' => $mailer_id));
                            }
                        }
            
                    }
                }
            ////end actino Item Comment Email for external users
            }
            
            if($data['is_finish']==1){
                $finish_user = $this->User_model->getUserInfo(array('user_id' => $data['updated_by'],'user_status'=>1));
                if($To[0]['is_workflow'] == 1){
                    $template_configurations_parent=$this->Sample_mode->EmailTemplateList(array('customer_id' => $cust_admin_info->customer_id,'language_id' =>1,'module_key'=>'CONTRACT_WORKFLOW_ACTION_ITEM_FINISH'));
                }else{
                    $template_configurations_parent=$this->Sample_mode->EmailTemplateList(array('customer_id' => $cust_admin_info->customer_id,'language_id' =>1,'module_key'=>'CONTRACT_REVIEW_ACTION_ITEM_FINISH'));
                }
                if($template_configurations_parent['total_records']>0 && !empty($finish_user)){
                    $template_configurations=$template_configurations_parent['data'][0];
                    $wildcards=$template_configurations['wildcards'];
                    $wildcards_replaces=array();
                    $wildcards_replaces['first_name']=$user_info->first_name;
                    $wildcards_replaces['last_name']=$user_info->last_name;
                    $wildcards_replaces['contract_name']=$contract_info[0]['contract_name'];
                    $wildcards_replaces['action_item_responsible_user']=$resoponsible_user_info->first_name.' '.$resoponsible_user_info->last_name.' ('.$resoponsible_user_info->user_role_name.')';
                    // $wildcards_replaces['contract_review_module_name']=$action_item['module_name'];
                    $wildcards_replaces['action_item_name']=$action_item['action_item'];
                    if(isset($To[0]['description']))
                        $wildcards_replaces['action_item_description']=$To[0]['description'];
                    $wildcards_replaces['action_item_due_date']=dateFormat($To[0]['due_date']);
                    $wildcards_replaces['action_item_comment']=$data['comments'];
                    $wildcards_replaces['action_item_finish_user_name']=$finish_user->first_name.' '.$finish_user->last_name.' ('.$finish_user->user_role_name.')';
                    $wildcards_replaces['action_item_finish_date']=dateFormat($update['updated_on']);
                    // $wildcards_replaces['contract_review_topic_name']=$action_item['topic_name'];
                    if($To[0]['is_workflow'] == 1){
                        $wildcards_replaces['contract_workflow_topic_name']=$action_item['topic_name'];
                        $wildcards_replaces['contract_workflow_module_name']=$action_item['module_name'];
                    }
                    else{
                        $wildcards_replaces['contract_review_topic_name']=$action_item['topic_name'];
                        $wildcards_replaces['contract_review_module_name']=$action_item['module_name'];
                    }
                    $wildcards_replaces['logo']=$customer_logo;
                    $wildcards_replaces['year'] = date("Y");
                    $wildcards_replaces['url']=WEB_BASE_URL.'html';
                    $body = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_content']);
                    $subject = wildcardreplace($wildcards,$wildcards_replaces,$template_configurations['template_subject']);
                    /*$from_name=SEND_GRID_FROM_NAME;
                    $from=SEND_GRID_FROM_EMAIL;
                    $from_name=$cust_admin['name'];
                    $from=$cust_admin['email'];*/
                    $from_name=$template_configurations['email_from_name'];
                    $from=$template_configurations['email_from'];
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
                    //print_r($mailer_data);
                    $mailer_id=$this->Sample_mode->addMailer($mailer_data);
                    if($mailer_data['is_cron']==0) {
                        //$mail_sent_status=sendmail($to, $subject, $body, $from);
                        $this->load->library('sendgridlibrary');
                        $mail_sent_status=$this->sendgridlibrary->sendemail($from_name,$from,$subject,$body,$to_name,$to,array(),$mailer_id);
                        if($mail_sent_status==1)
                            $this->Sample_mode->updateMailer(array('status'=>1,'mailer_id'=>$mailer_id));
                    }
    
                }
                ////start action Item Finish Email for external users
                if(isset($data['external_users']) && count(explode(',', $data['external_users']))>0){  
                    if($To[0]['is_workflow'] == 1){
                        $template_configurations_parent = $this->Sample_mode->EmailTemplateList(array('customer_id' => $cust_admin_info->customer_id, 'language_id' => 1, 'module_key' => 'CONTRACT_WORKFLOW_ACTION_ITEM_FINISH_EXTERNAL_USER'));
                    }else{
                        $template_configurations_parent = $this->Sample_mode->EmailTemplateList(array('customer_id' => $cust_admin_info->customer_id, 'language_id' => 1, 'module_key' => 'CONTRACT_REVIEW_ACTION_ITEM_FINISH_EXTERNAL_USER'));
                    }           
                    if ($template_configurations_parent['total_records'] > 0) {
                        $template_configurations = $template_configurations_parent['data'][0];
                        $wildcards = $template_configurations['wildcards'];
                        $wildcards_replaces = array();
                        $external_users = explode(',', $data['external_users']);
                        foreach($external_users as $v){
                            $wildcards_replaces['first_name'] = $v;
                            //$wildcards_replaces['last_name'] = $To->last_name;
                            $wildcards_replaces['contract_name'] = $contract_info[0]['contract_name'];
                            $wildcards_replaces['action_item_responsible_user']=$resoponsible_user_info->first_name.' '.$resoponsible_user_info->last_name.' ('.$resoponsible_user_info->user_role_name.')';
                            // $wildcards_replaces['contract_review_module_name']=$action_item['module_name'];
                            $wildcards_replaces['action_item_name']=$action_item['action_item'];
                            if(isset($To[0]['description']))
                                $wildcards_replaces['action_item_description']=$To[0]['description'];
                            $wildcards_replaces['action_item_due_date']=dateFormat($To[0]['due_date']);
                            $wildcards_replaces['action_item_comment']=$data['comments'];
                            $wildcards_replaces['action_item_finish_user_name']=$finish_user->first_name.' '.$finish_user->last_name.' ('.$finish_user->user_role_name.')';
                            $wildcards_replaces['action_item_finish_date']=dateFormat($update['updated_on']);
                            // $wildcards_replaces['contract_review_topic_name']=$action_item['topic_name'];
                            if($To[0]['is_workflow'] == 1){
                                $wildcards_replaces['contract_workflow_topic_name']=$action_item['topic_name'];
                                $wildcards_replaces['contract_workflow_module_name']=$action_item['module_name'];
                            }
                            else{
                                $wildcards_replaces['contract_review_topic_name']=$action_item['topic_name'];
                                $wildcards_replaces['contract_review_module_name']=$action_item['module_name'];
                            }
                            $wildcards_replaces['logo']=$customer_logo;
                            $wildcards_replaces['year'] = date("Y");
                            $wildcards_replaces['url'] = WEB_BASE_URL . 'html';
                            $body = wildcardreplace($wildcards, $wildcards_replaces, $template_configurations['template_content']);
                            $subject = wildcardreplace($wildcards, $wildcards_replaces, $template_configurations['template_subject']);
                            /*$from_name=SEND_GRID_FROM_NAME;
                            $from=SEND_GRID_FROM_EMAIL;
                            $from_name=$cust_admin['name'];
                            $from=$cust_admin['email'];*/
                            $from_name = $template_configurations['email_from_name'];
                            $from = $template_configurations['email_from'];
                            $to = $v;
                            //$to_name = $To->first_name . ' ' . $To->last_name;
                            $mailer_data['mail_from_name'] = $from_name;
                            $mailer_data['mail_to_name'] = '';
                            $mailer_data['mail_to_user_id'] = '';
                            $mailer_data['mail_from'] = $from;
                            $mailer_data['mail_to'] = $to;
                            $mailer_data['mail_subject'] = $subject;
                            $mailer_data['mail_message'] = $body;
                            $mailer_data['status'] = 0;
                            $mailer_data['send_date'] = currentDate();
                            $mailer_data['is_cron'] = 0;
                            $mailer_data['email_template_id'] = $template_configurations['id_email_template'];
                            //print_r($mailer_data);
                            $mailer_id = $this->Sample_mode->addMailer($mailer_data);
                            //sending mail to bu owner
                            if ($mailer_data['is_cron'] == 0) {
                                //$mail_sent_status=sendmail($to, $subject, $body, $from);
                                $this->load->library('sendgridlibrary');
                                $mail_sent_status = $this->sendgridlibrary->sendemail($from_name, $from, $subject, $body, $to_name, $to, array(), $mailer_id);
                                if ($mail_sent_status == 1)
                                    $this->Sample_mode->updateMailer(array('status' => 1, 'mailer_id' => $mailer_id));
                            }
                        }
            
                    }
                }
            ////end actino Item Finish Email for external users
    
            }
    
            $result = array('status'=>TRUE, 'message' => $msg, 'data'=>'');
            $this->response($result, REST_Controller::HTTP_OK);
        }
}
