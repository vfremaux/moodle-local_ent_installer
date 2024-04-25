function all_exclude(checkboxobj){

	chks = window.document.getElementById('chk_userfilter_eleve');
	chkt = window.document.getElementById('chk_userfilter_enseignant');
	chkc = window.document.getElementById('chk_userfilter_cdt');
	chka = window.document.getElementById('chk_userfilter_administration');
	chkp = window.document.getElementById('chk_userfilter_parent');
	
	if (checkboxobj.checked){
		chks.disabled = 1;
		chkt.disabled = 1;
		chkc.disabled = 1;
		chka.disabled = 1;
		chkp.disabled = 1;
	} else {
		chks.disabled = 0;
		chkt.disabled = 0;
		chkc.disabled = 0;
		chka.disabled = 0;
		chkp.disabled = 0;
	}
}