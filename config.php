<?php
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set('display_errors','1');
ini_set('memory_limit','256M');
define('ENV','NARESH');
$base_host = "http://192.168.0.19/Mindtronixs/";
define('WEB_BASE_URL', $base_host);
define('REST_API_URL', $base_host.'rest/');
define('PAGING_LIMIT', '10');
define('LOG_AUTH_KEY', '');
define('AES_KEY', '');
define('DATA_ENCRYPT',FALSE);
define('EXCEL_UPLOAD_SIZE','5097152');
define('IMAGE_UPLOAD_SIZE','5097152');
define('PASSWORD_EXPIRY_DAYS',17);
define('PASSWORD_NOTIFICATION_DAYS',10);
define('SITE_ACCESS_TOKEN_EXPIRY',600);//in seconds
define('SEND_GRID_API_KEY', '');//new code
define('SEND_GRID_FROM_EMAIL', 'no-reply@mindtronix.com');
define('SEND_GRID_FROM_NAME', 'Mindtronix');
define('DOCUMENT_PATH', 'http://192.168.0.63/Mindtronixs/rest/uploads/');

switch(ENV)
{
    case 'DEV':
        define('DB_HOST', '');//Here you need to mention your ip address
        define('DB_USERNAME', 'root');//UserName
        define('DB_PASSWORD', '');//Password
        define('DB_NAME', '');// Here  you need to give your database name
        define('LOG_DB_NAME', '');
    break;

    case 'PROD':
        define('DB_HOST', '');//Here you need to mention your ip address  
        define('DB_USERNAME', '');//UserName
        define('DB_PASSWORD', '');//Password
        define('DB_NAME', ''); // Here  you need to give your database name
        define('LOG_DB_NAME', '');
    break;


    case 'PRASAD':
        define('DB_HOST', '');//Here you need to mention your ip address  
        define('DB_USERNAME', '');//UserName
        define('DB_PASSWORD', '');//Password
        define('DB_NAME', ''); // Here  you need to give your database name
        define('LOG_DB_NAME', '');
    break;

    case 'PARVATHI':
        define('DB_HOST', '');//Here you need to mention your ip address  
        define('DB_USERNAME', '');//UserName
        define('DB_PASSWORD', '');//Password
        define('DB_NAME', ''); // Here  you need to give your database name
        define('LOG_DB_NAME', '');
    break;

    case 'NARESH':
       define('DB_HOST', '192.168.0.63:3308');//Here you need to mention your ip address  
       define('DB_USERNAME', 'naresh');//UserName
       define('DB_PASSWORD', '123456');//Password
       define('DB_NAME', 'mindtronics_13th'); // Here  you need to give your database name
       define('LOG_DB_NAME', '');
    break;

}

?>
