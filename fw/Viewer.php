<?php

class Viewer {
	
	private $html;
	
	public function __construct(){		
		
	}
	
	public function __tostring(){
		global $dbName;
		
		$this->addHtmlLine("<!doctype html>");
		$this->addHtmlLine('<html ng-app="AmpersandApp">');
		$this->addHtmlLine('<head>');
				
		$this->addHtmlLine('<title>'.Config::get('contextName').'</title>');
		
		// Meta tags
		$this->addHtmlLine('<meta name="viewport" content="width=device-width, initial-scale=1.0"/>');
		$this->addHtmlLine('<meta charset="UTF-8">');
		
		// TODO: onderstaande caching meta tags nodig?		
		$this->addHtmlLine('<meta name="Pragma" content="no-cache"/>');
		$this->addHtmlLine('<meta name="no-cache"/>');
		$this->addHtmlLine('<meta name="Expires" content="-1"/>');
		$this->addHtmlLine('<meta name="cache-Control" content="no-cache"/>');
		
		// initSessionId
		session_start();
		$this->addHtmlLine('<script type="text/javascript">var initSessionId = \'' . session_id() . '\';</script>');
		
		// JQuery
		$this->addHtmlLine('<script src="app/lib/jquery/jquery-1.11.0.min.js"></script>');
		$this->addHtmlLine('<script src="app/lib/jquery/jquery-migrate-1.2.1.js"></script>');
		$this->addHtmlLine('<script src="app/lib/jquery/jquery-ui-1.10.4.custom.js"></script>');		
		
		// Bootstrap (requires Jquery, loaded above)
		$this->addHtmlLine('<link href="app/lib/bootstrap-3.3.5-dist/css/bootstrap.min.css" rel="stylesheet" media="screen">'); // load boostrap.css before app specific css files that overwrite bootstrap.css
		$this->addHtmlLine('<script src="app/lib/bootstrap-3.3.5-dist/js/bootstrap.min.js"></script>');
		
		// Angular
		$this->addHtmlLine('<script src="app/lib/angular/angular.min.js"></script>');
		$this->addHtmlLine('<script src="app/lib/angular/angular-resource.min.js"></script>');
		$this->addHtmlLine('<script src="app/lib/angular/angular-route.min.js"></script>');
		$this->addHtmlLine('<script src="app/lib/angular/angular-sanitize.min.js"></script>');
		// Third party directives for angular
			// angular-ui-switch
			$this->addHtmlLine('<script src="app/lib/angular/angular-ui-switch/angular-ui-switch-adapted.js"></script>');	
			$this->addHtmlLine('<link href="app/lib/angular/angular-ui-switch/angular-ui-switch.css" rel="stylesheet" media="screen" type="text/css">');
			
			// angular-busy
			$this->addHtmlLine('<script src="app/lib/angular/angular-busy/angular-busy.min.js"></script>');
			$this->addHtmlLine('<link href="app/lib/angular/angular-busy/angular-busy.min.css" rel="stylesheet" media="screen" type="text/css">');
			
			// si-table
			$this->addHtmlLine('<script src="app/lib/angular/si-table/si-table.js"></script>');
			
			// angular-code-mirror
			$this->addHtmlLine('<script src="app/lib/angular/angular-code-mirror/angular-code-mirror.min.js"></script>');
			$this->addHtmlLine('<link href="app/lib/angular/angular-code-mirror/angular-code-mirror.css" rel="stylesheet" media="screen" type="text/css">');
			
			// ng-storage
			$this->addHtmlLine('<script src="app/lib/angular/angular-ng-storage/ngStorage.min.js"></script>');
			
			// angular-file-upload
			$this->addHtmlLine('<script src="app/lib/angular/angular-file-upload/angular-file-upload.min.js"></script>');
			
			// angular-grid
			$this->addHtmlLine('<script src="app/lib/angular/angular-grid/ag-grid.min.js"></script>');
			$this->addHtmlLine('<link href="app/lib/angular/angular-grid/ag-grid.min.css" rel="stylesheet" media="screen" type="text/css">');
			$this->addHtmlLine('<link href="app/lib/angular/angular-grid/theme-dark.min.css" rel="stylesheet" media="screen" type="text/css">');
			$this->addHtmlLine('<link href="app/lib/angular/angular-grid/theme-fresh.min.css" rel="stylesheet" media="screen" type="text/css">');
			
		// Restangular (with depency for lodash)
		$this->addHtmlLine('<script src="app/lib/restangular/restangular.min.js"></script>');
		$this->addHtmlLine('<script src="app/lib/restangular/lodash.min.js"></script>');
		
		// jquery UI & bootstrap in native AngularJS
		$this->addHtmlLine('<script src="app/lib/ui-bootstrap/ui-bootstrap-tpls-0.12.0.min.js"></script>');		

		$this->addHtmlLine('<script src="app/lib/json-patch/json-patch-duplex.min.js"></script>');
		
		// CSS files
		$files = getDirectoryList(__DIR__ . '/../app/css');
		foreach ((array)$files as $file){ 
			if (substr($file,-3) !== 'css') continue;
			$this->addHtmlLine('<link href="app/css/'.$file.'" rel="stylesheet" media="screen" type="text/css">');
		}
		foreach ((array)$GLOBALS['hooks']['after_Viewer_load_cssFiles'] as $cssFile) $this->addHtmlLine('<link href="'.$cssFile.'" rel="stylesheet" media="screen" type="text/css">');
		
		// AmpersandApp
		$this->addHtmlLine('<script src="app/AmpersandApp.js"></script>');
		$this->addHtmlLine('<script src="generics/app/RouteProvider.js"></script>');
		
		// AngularApp static controler files
		$files = getDirectoryList(__DIR__ . '/../app/controllers');
		foreach ((array)$files as $file){ 
			if (substr($file,-2) !== 'js') continue;
			$this->addHtmlLine('<script src="app/controllers/'.$file.'"></script>');
		}
		// AngularApp generic controler files (generated by Ampersand)
		$files = getDirectoryList(__DIR__ . '/../generics/app/controllers');
		foreach ((array)$files as $file){
			if (substr($file,-2) !== 'js') continue;
			$this->addHtmlLine('<script src="generics/app/controllers/'.$file.'"></script>');
		}
		foreach ((array)$GLOBALS['hooks']['after_Viewer_load_angularScripts'] as $angularScript) $this->addHtmlLine('<script src="'.$angularScript.'"></script>');
		
		// Javascript files
		$files = getDirectoryList(__DIR__ . '/../app/js');
		foreach ((array)$files as $file){
			if (substr($file,-2) !== 'js') continue;
			$this->addHtmlLine('<script src="app/js/'.$file.'"></script>');
		}
		
		$this->addHtmlLine('</head>');
		
		$this->addHtmlLine('<body>');
		
		$this->addHtmlLine(file_get_contents(__DIR__ . '/../app/AmpersandApp.html'));
		
		$this->addHtmlLine('</body>');
		
		$this->addHtmlLine('</html>');
		
		return $this->html;
	
	}
	
	private function addHtmlLine($htmlLine){
		$this->html .= $htmlLine;
	}

}

?>