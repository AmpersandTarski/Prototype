/*
Controller for interface "$interfaceName$" (context: "$contextName$"). Generated code, edit with care.
$if(verbose)$Generated using template: $usedTemplate$
Generated by $ampersandVersionStr$

INTERFACE "$interfaceName$" : $expAdl$ :: $source$ * $target$  ($if(!isRoot)$non-$endif$root interface)
Roles: [$roles;separator=", "$]
Editable relations: [$editableRelations;separator=", "$] 
$endif$*/
AmpersandApp.controller('$interfaceName$Controller', function (\$scope, \$rootScope, \$route, \$routeParams, Restangular, \$location, \$timeout, \$localStorage, \$sessionStorage) {	
	\$scope.loadingInterface = []; // array for promises, used by angular-busy module (loading indicator)
	\$scope.\$localStorage = \$localStorage;
	\$scope.\$sessionStorage = \$sessionStorage;
	
	if(typeof \$routeParams.resourceId != 'undefined') resourceId = \$routeParams.resourceId;
	else resourceId = \$scope.\$sessionStorage.session.id;
	
	/**********************************************************************************************
	 * 
	 *	GET INTERFACE
	 * 
	 *********************************************************************************************/
	
	// Set requested resource
	\$scope.resource = Restangular.one('resources').one('$source$', resourceId); // BaseURL to the API is already configured in AmpersandApp.js (i.e. 'http://pathToApp/api/v1/')
	\$scope.resource['_path_'] = '/resources/$source$/' + resourceId;
	\$scope.resource['_ifcEntryResource_'] = true;
	\$scope.entryResource = \$scope.resource; // Used in templates to specify as patchOnResource
	\$scope.resource['$interfaceName$'] = new Array();
	
	// Create new resource
	if(\$routeParams['new']){
		if('$source$' == '$target$'){ // I[$source$] interface
			newResource = Restangular.one('resources').one('$target$', '_NEW_');
		}else{
			newResource = \$scope.resource;
		}
		
		// Create new resource and add data to \$scope.resource['$interfaceName$']
		\$scope.loadingInterface.push( // shows loading indicator
			newResource.post('$interfaceName$', {}, {requestType : \$rootScope.defaultRequestType})
				.then(function(data) { // POST
					\$rootScope.updateNotifications(data.notifications);
					\$scope.resource['$interfaceName$'].push(Restangular.restangularizeElement(\$scope.resource, data.content, '$interfaceName$')); // Add to collection
					showHideButtons(resource, data.invariantRulesHold, data.requestType);
					\$location.url('/$interfaceName$/'+ data.content['_id_'], false);
				})
		);
	
	// Get resource and add data to \$scope.resource['$interfaceName$']
	}else{	
		\$scope.loadingInterface.push( // shows loading indicator
			\$scope.resource.getList('$interfaceName$', {forceList : true}).then(function(data){
				if(\$.isEmptyObject(data.plain())){
					\$rootScope.addInfo('No results found');
				}else{
					\$scope.resource['$interfaceName$'] = data;
					\$scope.\$broadcast('interfaceDataReceived', data);
				}
			})
		);
	}
	
	// Function to change location to create a new resource
	\$scope.newResource = function(){
		\$location.url('/$interfaceName$?new');
	};
	
	/**********************************************************************************************
	 * 
	 *	CRUD functions on resources
	 * 
	 *********************************************************************************************/
	
	// Function to get a resource
	\$scope.getResource = function (resource){
		resource['_loading_'] = new Array();
		resource['_loading_'].push( // shows loading indicator
			Restangular.one(resource['_path_'])
				.get()
				.then(function(data){
					// Update resource data
					resource = data;				
					
					// Empty loading array
					resource['_loading_'] = new Array();
				})
		);
	}
	
	// Function to create a new resource and add to the colletion
	\$scope.createResource = function (obj, ifc, prepend, requestType){		
		if(prepend === 'undefined') var prepend = false;
		requestType = requestType || \$rootScope.defaultRequestType; // set requestType. This does not work if you want to pass in a falsey value i.e. false, null, undefined, 0 or ""
		
		obj['_loading_'] = new Array();
		obj['_loading_'].push( // shows loading indicator
			Restangular.one(obj['_path_']).all(ifc)
				.post({}, {requestType : requestType})
				.then(function(data){					
					// Update visual feedback (notifications and buttons)
					\$rootScope.updateNotifications(data.notifications);
					showHideButtons(data.content, data.invariantRulesHold, data.requestType); // Show/hide buttons on top level resource
					
					// Add new resource to collection/list
					if(prepend) obj[ifc].unshift(data.content);
					else obj[ifc].push(data.content);
					
					// Empty loading array
					obj['_loading_'] = new Array();
				})
		);
	};
	
	// Function to delete a resource
	\$scope.deleteResource = function (obj, ifc, resource, requestType){
		requestType = requestType || \$rootScope.defaultRequestType; // set requestType. This does not work if you want to pass in a falsey value i.e. false, null, undefined, 0 or ""
		
		if(confirm('Are you sure?')){
			resource['_loading_'] = new Array();
			resource['_loading_'].push( // shows loading indicator
				Restangular.one(resource['_path_'])
					.remove({requestType : requestType})
					.then(function(data){
						// Remove resource from collection/list
						index = _getListIndex(obj[ifc], '_id_', resource['_id_']);
						obj[ifc].splice(index, 1);
						
						// Update visual feedback (notifications and buttons)
						\$rootScope.updateNotifications(data.notifications);
					})
			);
		}
	};
	
	// Function to patch only the changed attributes of a Resource
	\$scope.patchResource = function(resource, patches, requestType){		
		if(resource['_patchesCache_'] === undefined) resource['_patchesCache_'] = []; // new array
		resource['_patchesCache_'] = resource['_patchesCache_'].concat(patches); // add new patches
		
		\$scope.saveResource(resource, requestType);
	};
	
	// Function to send all patches
	\$scope.saveResource = function(resource, requestType){
		requestType = requestType || \$rootScope.defaultRequestType; // set requestType. This does not work if you want to pass in a falsey value i.e. false, null, undefined, 0 or ""
		
		resource['_loading_'] = new Array();
		resource['_loading_'].push( // shows loading indicator
			Restangular.one(resource['_path_'])
				.patch(resource['_patchesCache_'], {'requestType' : requestType, 'topLevelIfc' : '$interfaceName$'})
				.then(function(data) {
					// Update resource data
					
					if(resource['_ifcEntryResource_']){
						resource['$interfaceName$'] = data.content;
						//tlResource = resource;
					}
					else resource = \$.extend(resource, data.content);
					
					// Update visual feedback (notifications and buttons)
					\$rootScope.updateNotifications(data.notifications);
					//showHideButtons(tlResource, data.invariantRulesHold, data.requestType); // Show/hide buttons on top level resource
					
					// Empty loading array
					resource['_loading_'] = new Array();					
				})
		);
	};
	
	// Function to cancel edits and reset (get) resource data
	\$scope.cancelResource = function(resource){		
		resource['_loading_'] = new Array();
		resource['_loading_'].push( // shows loading indicator
			resource.get()
				.then(function(data){
					// Update resource data
					resource = data;
					// resource = \$.extend(resource, data.plain());
					
					// Update visual feedback (notifications and buttons)
					\$rootScope.getNotifications(); // get notification again
					// resource['_patchesCache_'] = []; // empty patches cache
					// resource['_showButtons_'] = {'save' : false, 'cancel' : false};
					setResourceStatus(resource, 'default'); // reset status
					
					// Empty loading array
					resource['_loading_'] = new Array();
				})
		);
	};
	
	/**********************************************************************************************
	 * 
	 *	Edit functions on scalar
	 * 
	 *********************************************************************************************/
	
	// Function to save item (non-array)
	\$scope.saveItem = function(resource, ifc, patchResource){		
		if(resource[ifc] === '') value = null;
		else value = resource[ifc];
		
		// Construct path
		pathLength = patchResource['_path_'].length;
		path = resource['_path_'].substring(pathLength) + '/' + ifc;
		
		// Construct patch
		patches = [{ op : 'replace', path : path, value : value}];
		$if(verbose)$console.log(patches);$endif$
		
		// Patch!
		\$scope.patchResource(patchResource, patches);
	};
	
	// Function to add item to array
	\$scope.addItem = function(resource, ifc, selected, patchResource){		
		if(selected.value === undefined){
			console.log('Value undefined');
		}else if(selected.value !== ''){
			// Adapt in js model
			if(resource[ifc] === null) resource[ifc] = [];
			resource[ifc].push(selected.value);
			
			// Construct path
			pathLength = patchResource['_path_'].length;
			path = resource['_path_'].substring(pathLength) + '/' + ifc;
			
			// Construct patch
			patches = [{ op : 'add', path : path, value : selected.value}];
			$if(verbose)$console.log(patches);$endif$
			
			// Reset selected value
			selected.value = '';			
			
			// Patch!
			\$scope.patchResource(patchResource, patches);
		}else{
			console.log('Empty value selected');
		}
	};
	
	// Function to remove item from array
	\$scope.removeItem = function(resource, ifc, key, patchResource){		
		// Adapt js model
		value = resource[ifc][key];
		resource[ifc].splice(key, 1);
		
		// Construct path
		pathLength = patchResource['_path_'].length;
		path = resource['_path_'].substring(pathLength) + '/' + ifc + '/' + value;
		
		// Construct patch
		patches = [{ op : 'remove', path : path}];
		$if(verbose)$console.log(patches);$endif$
		
		// Patch!
		\$scope.patchResource(patchResource, patches);
	};
	
	
	/**********************************************************************************************
	 * 
	 *	Edit functions on objects
	 * 
	 *********************************************************************************************/
	
	// Function to add an object to a certain interface (array) of a resource
	\$scope.addObject = function(resource, ifc, obj, patchResource){
		// If patchResource is undefined, the patchResource equals the patchResource
		if(patchResource === undefined){
			patchResource = resource
		}
		
		if(obj['_id_'] === undefined || obj['_id_'] == ''){
			console.log('Selected object id is undefined');
		}else{
			// Adapt js model
			if(resource[ifc] === null) resource[ifc] = [];
			try {
				resource[ifc].push(obj.plain()); // plain is Restangular function
			}catch(e){
				resource[ifc].push(obj); // when plain() does not exists (i.e. object is not restangular object) 
			}
			
			// Construct path
			pathLength = patchResource['_path_'].length;
			path = resource['_path_'].substring(pathLength) + '/' + ifc;
			
			// Construct patch
			patches = [{ op : 'add', path : path, value : obj['_id_']}];
			$if(verbose)$console.log(patches);$endif$
			
			// Patch!
			\$scope.patchResource(patchResource, patches);
		}
	};
	
	// Function to remove an object from a certain interface (array) of a resource
	\$scope.removeObject = function(resource, ifc, key, pathOnResource){		
		// Adapt js model
		id = resource[ifc][key]['_id_'];
		resource[ifc].splice(key, 1);
		
		// Construct path
		pathLength = pathOnResource['_path_'].length;
		path = resource['_path_'].substring(pathLength) + '/' + ifc + '/' + id;
		
		// Construct patch
		patches = [{ op : 'remove', path : path}];
		$if(verbose)$console.log(patches);$endif$
		
		// Patch!
		\$scope.patchResource(pathOnResource, patches);
	};
	
	// Typeahead functionality
	\$scope.typeahead = {}; // an empty object for typeahead
	\$scope.getTypeahead = function(resourceType){
		// Only if not yet set
		if(\$scope.typeahead[resourceType] === undefined){
			\$scope.typeahead[resourceType] = Restangular.all('resources/' + resourceType).getList().\$object;
		}
	};
	
	/**********************************************************************************************
	 *
	 * Transaction status function
	 *
	 **********************************************************************************************/
	
	// TODO: change check on showSaveButton to check for unsaved patches
	\$scope.\$on("\$locationChangeStart", function(event, next, current){
		$if(verbose)$console.log("location changing to:" + next);$endif$
		checkRequired = false; // default
		for(var item in \$scope.showSaveButton) { // iterate over all properties (resourceIds) in showSaveButton object
			if(\$scope.showSaveButton.hasOwnProperty( item ) ) { // only checks its own properties, not inherited ones
				if(\$scope.showSaveButton[item] == true) checkRequired = true; // if item is not saved, checkRequired before location change
			}
		}
		
		if(checkRequired){ // if checkRequired (see above)
			confirmed = confirm("You have unsaved edits. Do you wish to leave?");
			if (event && !confirmed) event.preventDefault();
		}
	});
	
	/**********************************************************************************************
	 *
	 * Helper functions
	 *
	 **********************************************************************************************/
	
	function _getListIndex(list, attr, val){
		var index;
		list.some(function(item, idx){
			return (item[attr] === val) && (index = idx)
		});
		return index;
	};
	
	// Show/hide save buttons
	function showHideButtons(resource, invariantRulesHold, requestType){
		
		if(invariantRulesHold && requestType == 'feedback'){
			resource['_showButtons_'] = {'save' : true, 'cancel' : true};
			setResourceStatus(resource, 'warning');
			
		}else if(invariantRulesHold && requestType == 'promise'){
			resource['_showButtons_'] = {'save' : false, 'cancel' : false};
			resource['_patchesCache_'] = []; // empty patches cache
			
			setResourceStatus(resource, 'success'); // Set status to success
			\$timeout(function(){ // After 3 seconds, reset status to default
				setResourceStatus(resource, 'default');
			}, 3000);
		}else{
			resource['_showButtons_'] = {'save' : false, 'cancel' : true};
			setResourceStatus(resource, 'danger');
		}
	};
	
	function setResourceStatus(resource, status){
		// Reset all status properties
		resource['_status_'] = { 'warning' : false
							   , 'danger'  : false
							   , 'default' : false
							   , 'success' : false
							   };
		// Set new status property
		resource['_status_'][status] = true;
	};
});