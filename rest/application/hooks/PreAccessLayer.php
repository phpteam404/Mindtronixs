<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PreAccessLayer {
    var $CI;

    function __construct() {
        // Construct the parent class
        $this->CI =& get_instance();
    }

    function sample()
    {
        echo "this is pre controller";
    }

}
