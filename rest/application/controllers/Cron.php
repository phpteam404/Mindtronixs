<?php

defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(0);
require APPPATH . '/third_party/mailer/mailer.php';

class Cron extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load
    }

    public function studentinvoicegeneration(){
        //this function is used to generate student invoice
        $student_detais=$this->Invoices_model->getStudentData($data=null);
        print_r($student_detais);exit;
    }
}   