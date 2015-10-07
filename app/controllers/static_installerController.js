AmpersandApp.controller('static_installerController', function ($scope, $rootScope, $routeParams, Restangular, $localStorage) {
	$scope.installing = false;
	$scope.install = function(){
		$scope.installing = true;
		$scope.installed = false;
		Restangular.one('installer').get().then(function(data) {
			$rootScope.updateNotifications(data);
			
			// set roleId back to 0
			$localStorage.roleId = 0;
			
			// refresh navbar
			$rootScope.refreshNavBar();
			
			$scope.installing = false;
			$scope.installed = true;
		}, function(){
			$scope.installing = false;
			$scope.installed = false;
		});
	}
	
});