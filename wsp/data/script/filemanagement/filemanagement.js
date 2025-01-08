function setDisplayList(displayValue) {
	document.getElementById('displaylist_list').setAttribute('class', 'bubblemessage orange');
	document.getElementById('displaylist_box').setAttribute('class', 'bubblemessage orange');
	document.getElementById('displaylist_tinybox').setAttribute('class', 'bubblemessage orange');
	document.getElementById('displaylist_' + displayValue).setAttribute('class', 'bubblemessage');
	$('.' + document.getElementById('displaylist').value).removeClass(document.getElementById('displaylist').value).addClass(displayValue);
	document.getElementById('displaylist').value = displayValue;
	}
		
function setSortList(sortValue) {
	document.getElementById('sortlist_name').setAttribute('class', 'bubblemessage orange');
	document.getElementById('sortlist_date').setAttribute('class', 'bubblemessage orange');
	document.getElementById('sortlist_size').setAttribute('class', 'bubblemessage orange');
	document.getElementById('sortlist_' + sortValue).setAttribute('class', 'bubblemessage');
	document.getElementById('sortlist').value = sortValue;
	setSearchList(document.getElementById('searchlist').value);
	var openFolders = document.getElementById('activefolder').value;
	if ($.trim(openFolders)!='') {
		var Folders = openFolders.split(';');
		for (f=0; f<Folders.length; f++) {
			updateSortList(Folders[f]);
			}
		}
	}
	
function updateSortList(fkid) {
	var displaylist = document.getElementById('displaylist').value;
	var sortlist = document.getElementById('sortlist').value;
	$.post("xajax/ajax.returnmediafilelist.php", { 'fkid': fkid, 'display': displaylist, 'sort': sortlist})
		.done (function(data) {
			$('#files_' + fkid).html(data);
			});
	}
	
	function setSearchList(searchValue) {
		if ($.trim(searchValue).length>2) {
			var displaylist = document.getElementById('displaylist').value;
			var sortlist = document.getElementById('sortlist').value;
			$.post("xajax/ajax.returnmediasearch.php", { 'search': $.trim(searchValue), 'display': displaylist, 'sort': sortlist})
				.done (function(data) {
					$('#filesearch').css('display', 'block');
					$('#filesearch').html(data);
					})
			}
		else {
			$('#filesearch').css('display', 'none');
			}
		}
	
	function showDetails(fileID) {
		document.getElementById('filedir').value = document.getElementById('frommediafolder').value + document.getElementById('frompath').value;
		document.getElementById('fileid').value = fileID;
		document.getElementById('viewfile').submit();
		}
	
	function setFileDesc(fileFieldId) {
		document.getElementById('savefiledesc').value = $('#'+fileFieldId+'_descname').text();
		document.getElementById(fileFieldId+'_descname').innerHTML = '<input type="text" name="newfiledesc" id="'+fileFieldId+'_newfiledesc" value="'+ $('#'+fileFieldId+'_descname').text()+'" onBlur="setNewFileDesc(\''+fileFieldId+'\');" />';
		document.getElementById(fileFieldId+'_newfiledesc').focus();
		}
	
	function setNewFileDesc(fileFieldId) {
		var newdesc = document.getElementById(fileFieldId+'_newfiledesc').value;
		var olddesc = document.getElementById('savefiledesc').value;
		if ($.trim(fileFieldId)!="") {
			$.post("xajax/ajax.setnewfiledesc.php", { 'fileid': $.trim(fileFieldId), 'newdesc': newdesc, 'olddesc': olddesc})
				.done (function(data) {
					$('#'+fileFieldId+'_descname').html(data);
					})
			}
		else {
			$('#'+fileFieldId+'_descname').html(olddesc);
			}
		}
	
	function changeFileName(fileFieldId, fieldType) {
		document.getElementById('changefile').style.display = 'block';
		document.getElementById('fieldset_options').style.display = 'none';
		document.getElementById('newfilename').value = document.getElementById('fileimg' + fileFieldId).value.substr(0,(document.getElementById('fileimg' + fileFieldId).value.length)-(fieldType.length + 1));
		document.getElementById('showrenamefile').innerHTML = document.getElementById('fileimg' + fileFieldId).value;
		document.getElementById('showrenamefileend').innerHTML = '.' + fieldType;
		document.getElementById('changerenfilepath').value=document.getElementById('frommediafolder').value + document.getElementById('uploadpath').value;
		document.getElementById('changeoldfilename').value=document.getElementById('fileimg' + fileFieldId).value;
		document.getElementById('fileid').value=fileFieldId;
		}
	
	function setNewFileName(fileFieldId) {
		saveNewFileName(document.getElementById('newfilename').value, fileFieldId, document.getElementById('frommediafolder').value + document.getElementById('uploadpath').value, document.getElementById('fileimg' + fileFieldId).title);
	}

	function delItemNode(fileLiId) {
		var n=document.getElementById(fileLiId).parentNode;
		for(k=0;k<n.childNodes.length;k++) {
			if(n.childNodes[k].id == fileLiId) {
				n.removeChild(n.childNodes[k]);
			}
		}
		document.getElementById('rebuildpath').value = document.getElementById('uploadpath').value;
		document.getElementById('rebuild').submit();
	}
	
	function showFiles(fkid) {
		var displaylist = document.getElementById('displaylist').value;
		var sortlist = document.getElementById('sortlist').value;
		if ($('#btn_open_' + fkid).hasClass('orange')) {
			$.post("xajax/ajax.returnmediafilelist.php", { 'fkid': fkid, 'display': displaylist, 'sort': sortlist})
					.done (function(data) {
						$('#files_' + fkid).html(data);
						$('#btn_open_' + fkid).removeClass('orange');
						$('#files_' + fkid).show('blind', 400);
						});
			updateOpenClose(fkid, 'add');
			}
		else {
			$('#files_' + fkid).hide('blind', 400);
			$('#btn_open_' + fkid).addClass('orange');
			$('#files_' + fkid).html('');
			updateOpenClose(fkid, 'del');
			}
		}
	
	function updateOpenClose(fkid, fkaction) {
		var openFolders = document.getElementById('activefolder').value;
		if ($.trim(openFolders)!='') {
			if (fkaction=='add') {
				document.getElementById('activefolder').value = openFolders + ';' + $.trim(fkid);
				}
			else if (fkaction=='del') {
				var Folders = openFolders.split(';');
				var oFolder = new Array();
				for (f=0; f<Folders.length; f++) {
					if (Folders[f]!=fkid) {
						oFolder.push(Folders[f]);
						}
					}
				if (oFolder.length>0) {
					document.getElementById('activefolder').value = oFolder.join(';');
					}
				else {
					document.getElementById('activefolder').value = '';
					}
				}
			}
		else if (fkaction=='add') {
			document.getElementById('activefolder').value = $.trim(fkid);
			}
		}
	
	function showCreateDir(fkid) {
		if ($("#btn_createdir_" + fkid).hasClass('orange')) {
			$("li.createdir.shown").removeClass('shown');
			$(".createdirbutton").addClass('orange');
			$("#createsubdir_" + fkid).show('blind', 400);
			$("#createsubdir_" + fkid).toggleClass('shown', 1);
			$("#btn_createdir_" + fkid).toggleClass('orange', 1);
			}
		else {
			$("#createsubdir_" + fkid).hide('blind', 400);
			$("#createsubdir_" + fkid).toggleClass('shown', 1);
			$("#btn_createdir_" + fkid).toggleClass('orange', 1);
			}		
		}
		
	function createNewDir(fkid) {
		var newdirname = document.getElementById('newdirname_'+ fkid).value;
		if ($.trim(fkid)!="" && $.trim(newdirname)!="") {
			$.post("xajax/ajax.createnewdir.php", { 'dirid': $.trim(fkid), 'newdirname': newdirname})
				.done (function(data) {
					if(data) {
						document.location.reload();
					} else {
						
						}
					})
			}
		}

	function confirmDeleteDir(fkid) {
		if ($.trim(fkid)!="") {
			$.post("xajax/ajax.deletedir.php", { 'dirid': $.trim(fkid)}, function(data) { console.log(data) }, 'json')
				.done (function(data) {
					if(data) {
						document.location.reload();
					} else {
						
						}
					})
			}
		}

	function confirmDeleteFile(fileFieldId) {
		if ($.trim(fileFieldId)!="") {
			$.post("xajax/ajax.deletefile.php", {'fileid': $.trim(fileFieldId)}, function(data) { console.log(data) }, 'json')
				.done (function(data) {
					if(data.success) {
						$('#'+data.removedfile).hide('puff');
					} else {
						alert(data.msg);
						}
					}),
				'json'
			}
		}

		