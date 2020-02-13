<?php
/**
 * Created by PhpStorm.
 * User: VENKATESH.B
 * Date: 28/12/16
 * Time: 4:54 PM
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH."/third_party/PHPExcel.php";

class Excel extends PHPExcel {
    public function __construct() {
        parent::__construct();
    }
}