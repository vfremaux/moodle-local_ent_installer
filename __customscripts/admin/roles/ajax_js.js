function refreshuserlist(wwwroot, textobj){
	
	params = "filter=" + textobj.value;
    var url = wwwroot + "/local/admin/ajax/get_users.php?" + params;

	var responseSuccess = function(o){
		selectelm = document.getElementById('id_users');
		divelm = selectelm.parentElement;
		// Mozilla fallback
		if (!divelm) {
			divelm = selectelm.parentNode;
		}
		divelm.innerHTML = o.responseText;
	}

    var responseFailure = function(o){
     	alert("responseFailure " + o);
    };

    var AjaxObject = {
    	handleSuccess:function(o){
            this.processResult(o);
        },
    
        handleFailure:function(o){
            alert('Ajax failure');
        },
    
        processResult:function(o){
        },
    
    	startRequest:function(){
    		YAHOO.util.Connect.asyncRequest('GET', url, callback, params);
    	}
    };
    
    var callback = {
        success:responseSuccess,
        failure:responseFailure,
        scope: AjaxObject
    };
    
     AjaxObject.startRequest();   	
}