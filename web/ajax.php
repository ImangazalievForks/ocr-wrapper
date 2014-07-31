<?php
require '../OCR.php';
if(isset($_POST['get_available_programs'])){
	echo json_encode(OCR::getInstalledPrograms());
}
elseif(isset($_POST['data'])){
	$program = isset($_POST['program']) && $_POST['program'] != 'all' ? $_POST['program'] : null;
	try{
		echo json_encode(OCR::run($_POST['data'], $program));
	}
	catch(Exception $e){
		$error = $e->getMessage();
	}
}
