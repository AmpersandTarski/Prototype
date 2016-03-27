<?php

// Path to API is 'api/v1/excelimport/import'
$app->post('/excelimport/import', function () use ($app){
	$session = Session::singleton();
	
	$roleIds = $app->request->params('roleIds');
	$session->activateRoles($roleIds);
			
	// Check sessionRoles if allowedRolesForExcelImport is specified
	$allowedRoles = Config::get('allowedRolesForExcelImport','excelImport');
	if(!is_null($allowedRoles)){
		$ok = false;
	
		foreach($session->getSessionRoles() as $role){
			if(in_array($role->label, $allowedRoles)) $ok = true;
		}
		if(!$ok) throw new Exception("You do not have access to import excel files", 401);
	}
	
	if (is_uploaded_file($_FILES['file']['tmp_name'])){
		// Parse:
		$parser = new ImportExcel($_FILES['file']['tmp_name']);
		$result = $parser->ParseFile();
		unlink($_FILES['file']['tmp_name']);
	}else{
	    Notifications::addError('No file uploaded');
	}
	
	$result = array('notifications' => $result, 'files' => $_FILES);
	
	print json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

?>