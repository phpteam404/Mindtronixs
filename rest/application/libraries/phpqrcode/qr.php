<?php 
    include('qrlib.php'); 
    define('EXAMPLE_TMP_URLRELPATH', 'temp/');
    $tempDir = EXAMPLE_TMP_URLRELPATH; 
	//$codeContents='http://192.168.0.63/Mindtronixs/rest/uploads/ticket/CCT2_(2)_1584082732.png';
	//$codeContents='http://192.168.0.63/Mindtronixs/rest/uploads/digitalcontent/30th_test_project__Initial_Assessment_30-Dec-2019%20(1)';
	$codeContents='http://bit.ly/2uwV5jv';
	QRcode::png($codeContents, $tempDir.'qrcode.png', QR_ECLEVEL_H, 5); 
 echo '<img src="'.$tempDir.'qrcode.png" />'; 