<?php
/**
 * Verwaltung von Dateien
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2023-01-05
 * Fixed error with undefined $thisimgdata
 * Fixed error with $used 
 * 2025-01-19
 * Fixed detection of php-files in media folders
 * 2025-05-22
 * Fixed removal of files that had upload-errors
 * 
 */

if (!(function_exists("fileUsage"))):
function fileUsage($path, $file) {
	$used = array();
	$replacefile = str_replace("/media/", "/", $file);
	$replacefile = str_replace("/images/", "/", $replacefile);
	$replacefile = str_replace("/screen/", "/", $replacefile);
	$replacefile = str_replace("/download/", "/", $replacefile);
	$replacefile = str_replace("/fonts/", "/", $replacefile);
	$replacefile = str_replace("/video/", "/", $replacefile);
	$replacefile = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $replacefile))));
    $file = strtolower($replacefile);
        
	// check contents and menupoints
	$sql = "SELECT c.`mid` FROM `content` AS c, `menu` AS m WHERE c.`valuefields` LIKE '%".$file."%' AND c.`trash` = 0 AND m.`trash` = 0 AND c.`mid` > 0 AND c.`mid` = m.`mid`";
	$res = doSQL($sql);
    if ($res['num']>0):
        foreach ($res['set'] AS $rsk => $rsv):
            $used[] = $rsv['mid'];
            $usetype[] = 'content';
        endforeach;
    endif;
    
	$sql = "SELECT c.`mid` FROM `globalcontent` AS g, `content` AS c, `menu` AS m WHERE g.`id` = c.`globalcontent_id` AND (g.`valuefield` LIKE '%" . $file . "%') AND g.`trash` = 0 AND c.`trash` = 0 AND m.`trash` = 0 AND c.`mid` > 0 AND c.`mid` = m.`mid`";
	$res = doSQL($sql);
    if ($res['num']>0):
        foreach ($res['set'] AS $rsk => $rsv):
            $used[] = $rsv['mid'];
            $usetype[] = 'global';
        endforeach;
    endif;
    
	$sql = "SELECT `mid` FROM `menu` WHERE (`imageon`='" . $file . "' OR `imageoff`='" . $file . "' OR `imageakt`='" . $file . "' OR `imageclick`='" . $file . "') AND `trash` = 0";
	$res = doSQL($sql);
    if ($res['num']>0):
        foreach ($res['set'] AS $rsk => $rsv):
            $used[] = $rsv['mid'];
            $usetype[] = 'menu';
        endforeach;
    endif;
    
	// stylesheets pruefen
	$sql = "SELECT `id` FROM `stylesheets` WHERE `stylesheet` LIKE '%" . $file . "%'";
	$res = doSQL($sql);
    if ($res['num']>0):
        foreach ($res['set'] AS $rsk => $rsv):
            $used[] = $rsv['id'];
            $usetype[] = 'style';
        endforeach;
    endif;

    // affected contents prüfen
    $moduleusage_sql = "SELECT `affectedcontent` FROM `modules` WHERE `affectedcontent` != '' && `affectedcontent` IS NOT NULL";
    $moduleusage_res = doSQL($moduleusage_sql);
    if ($moduleusage_res['num']>0) {
        foreach ($moduleusage_res['set'] AS $murk => $murv) {
            $grepdata = unserializeBroken($murv['affectedcontent']);
            foreach ($grepdata AS $table => $fieldnames) {
                $fileval_sql = array();
                foreach ($fieldnames AS $fieldname) {
                    $fileval_sql[] = " `".$fieldname."` LIKE '%".escapeSQL($file)."%' ";
                }
                $filemod_sql = "SELECT * FROM `".$table."` WHERE (".implode(" OR ", $fileval_sql).")";
                $filemod_num = getNumSQL($filemod_sql);
                if ($filemod_num>0) {
                    $used[] = $table;
                    $usetype[] = 'modules';
                }
            }
        }
    }
    
	if(is_array($used) && count($used)>0):
		return true;
	else:
		return false;
	endif;
	}
endif; // fileUsage

// check allowed dirs from user rights
if (str_replace("media/".$mediafolder,"",$_SESSION['wspvars']['rights']['imagesfolder'])!=$_SESSION['wspvars']['rights']['imagesfolder']):
	$allowedtopdir = str_replace("//","/",str_replace("//", "/", str_replace("media/".$mediafolder, "", $_SESSION['wspvars']['rights']['imagesfolder'])));
else:
	$allowedtopdir = "/";
endif; 

$path = checkParamVar('path', $allowedtopdir);
if ($path == "/"):
	$path = str_replace("//", "/", str_replace("//", "/", "/".$allowedtopdir));
endif;

if ($allowedtopdir!="" && $extern!=1) { if (str_replace($allowedtopdir, "", $path)==$path) { $path = str_replace("//", "/", str_replace("//", "/", "/".$allowedtopdir)); }} 

// define folders, that should exist
if (isset($requiredstructure) && is_array($requiredstructure)) {
    // do ftp connect
    $ftp = doFTP();
    foreach ($requiredstructure AS $csk => $csv) {
        if (!(is_dir(str_replace("//","/",str_replace("//","/",$_SERVER['DOCUMENT_ROOT']."/".$csv."/"))))) {
			if ($ftp) {
                ftp_mkdir($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$csv)); 
            } else {
				mkdir(str_replace("//","/",str_replace("//","/",$_SERVER['DOCUMENT_ROOT']."/".$csv."/")));
			}
        }
    }
    if ($ftp) { ftp_close($ftp); }
}

$fullmediastructure = array('/media/'.$mediafolder.'/' => array());
$fullmediatmp = getDirList('/media/'.$mediafolder, 1);
$fsizes = array('Byte', 'KB', 'MB', 'GB', 'TB');
foreach ($fullmediatmp AS $fmkey => $fmvalue):
	$fullmediastructure[trim($fmvalue."/")] = array();
endforeach;
unset ($fullmediatmp);

if (isset($_REQUEST['op']) && $_REQUEST['op']=='readstructure'):
	$lastcheck = time();
	// resetting all folders	
	$sql = "DELETE FROM `wspmedia` WHERE `mediatype` = '".escapeSQL($mediafolder)."' AND `filename` = ''";
	$res = doSQL($sql);
    foreach ($fullmediastructure AS $fmkey => $fmvalue) {
		$d = dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fmkey)));
		// adding folders to db
		$sql = "INSERT INTO `wspmedia` SET `mediatype` = '".escapeSQL($mediafolder)."', `mediafolder` = '".escapeSQL(trim($fmkey))."', `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$mediafolder."/","/",$fmkey)))))."', `filename` = '', `filetype` = '', `filekey` = '', `filedata` = '', `filesize` = 0, `filedate` = 0, `thumb` = 0, `preview` = 0, `original` = 0, `embed` = 0, `lastchange` = ".$lastcheck;
		$res = doSQL($sql);
		if (!$res['res']) { var_export($res); }
		// adding files to db
		while (false !== ($entry = $d->read())) {
			if ((substr($entry, 0, 1)!='.') && (is_file(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fmkey.'/'.$entry)))) && $entry!='index.php') {
				$thisimgdata = @getimagesize(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fmkey.'/'.$entry)));
				$thisfiledata = @stat(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fmkey.'/'.$entry)));
				
				if (intval($thisfiledata[7]) > 0) {
					$size = (!empty($thisimgdata) && intval($thisimgdata[0])>0) ? (intval($thisimgdata[0]).' x '.intval($thisimgdata[1])) : '';
					$mytype = substr($entry,strrpos($entry, ".")+1);
					
					$checkfthumb = str_replace("//", "/", str_replace("/media/".$mediafolder."/", "/media/".$mediafolder."/thumbs/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].$fmkey.'/'.$entry)));
					if (@is_file(str_replace(".".$mytype, ".jpg", $checkfthumb))) {
						$thumbnail = 1;
					} else if (@is_file(str_replace(".".$mytype, ".jpeg", $checkfthumb))) {
						$thumbnail = 4;
					} else if (@is_file(str_replace(".".$mytype, ".png", $checkfthumb))) {
						$thumbnail = 2;
					} else if (@is_file(str_replace(".".$mytype, ".gif", $checkfthumb))) {
						$thumbnail = 3;
					} else {
						$thumbnail = 0;
					}
					
					$checkfpreview = str_replace("//", "/", str_replace("/media/".$mediafolder."/", "/media/".$mediafolder."/preview/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].$fmkey.'/'.$entry)));
					if (@is_file(str_replace(".".$mytype, ".jpg", $checkfthumb))):
						$preview = 1;
					elseif (@is_file(str_replace(".".$mytype, ".jpeg", $checkfthumb))):
						$preview = 4;
					elseif (@is_file(str_replace(".".$mytype, ".png", $checkfthumb))):
						$preview = 2;
					elseif (@is_file(str_replace(".".$mytype, ".gif", $checkfthumb))):
						$preview = 3;
					else:
						$preview = 0;
					endif;
					
					$checkfthumb = str_replace("//", "/", str_replace("/media/".$mediafolder."/", "/media/".$mediafolder."/originals/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].$fmkey.'/'.$entry)));
					if (@is_file(str_replace(".".$mytype, ".jpg", $checkfthumb))):
						$original = 1;
					elseif (@is_file(str_replace(".".$mytype, ".jpeg", $checkfthumb))):
						$original = 4;
					elseif (@is_file(str_replace(".".$mytype, ".png", $checkfthumb))):
						$original = 2;
					elseif (@is_file(str_replace(".".$mytype, ".gif", $checkfthumb))):
						$original = 3;
					else:
						$original = 0;
					endif;
					
					if (fileUsage($_SERVER['DOCUMENT_ROOT'].$fmkey, trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$mediafolder."/","/",$fmkey."/".$entry)))))):
						$inuse = time();
					else:
						$inuse = 0;
					endif;
					
					$filesize = (intval($thisfiledata[7])>0) ? intval($thisfiledata[7]) : 0;
					$lastchange = (intval($thisfiledata[9])>0) ? intval($thisfiledata[9]) : 0;
					
					$filedata = array(
						'size' => $size,
						'filesize' => $filesize,
						'lastchange' => $lastchange,
						'md5key' => md5(str_replace("//", "/", trim($fmkey)."/".trim($entry)))
					);
					
					$e_sql = "SELECT `lastchange` FROM `wspmedia` WHERE `filekey` = '".md5(str_replace("//", "/", trim($fmkey)."/".trim($entry)))."'";
					$e_res = doSQL($e_sql);
					if ($e_res['num']==0) {
						$sql = "INSERT INTO `wspmedia` SET `mediatype` = '".escapeSQL($mediafolder)."', `mediafolder` = '".escapeSQL(trim($fmkey))."', `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$mediafolder."/","/",$fmkey)))))."', `filename` = '".escapeSQL(trim($entry))."', `filetype` = '".$mytype."', `filekey` = '".md5(str_replace("//", "/", trim($fmkey)."/".trim($entry)))."', `filedata` = '".escapeSQL(serialize($filedata))."', `filesize` = ".intval($filesize).", `filedate` = ".intval($lastchange).", `thumb` = ".intval($thumbnail).", `preview` = ".intval($preview).", `original` = ".intval($original).", `embed` = ".$inuse.", `lastchange` = ".intval($lastcheck);
					} else {
						$sql = "UPDATE `wspmedia` SET `filedata` = '".escapeSQL(serialize($filedata))."', `thumb` = ".intval($thumbnail).", `preview` = ".intval($preview).", `original` = ".intval($original).", `embed` = ".intval($inuse).", `lastchange` = ".intval($lastcheck)." WHERE `filekey` = '".md5(str_replace("//", "/", trim($fmkey)."/".trim($entry)))."'";						
					}
					$res = doSQL($sql);
					// unsetting file facts
					unset($size);
					unset($filedata);
					unset($thisimgdata);	
					unset($thisfiledata);
				} 
				else {
					@unlink(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fmkey."/".$entry))));
					addWSPMsg('errormsg', sprintf(
						returnIntLang('Could not fetch data for "%s". File was removed.'), 
						str_replace("//", "/", str_replace("//", "/", $fmkey.'/'.$entry))) . '<br />'
					);
				}
			}
		}
		$d->close();
	}
	// removing older entries
	$sql = "DELETE FROM `wspmedia` WHERE `mediatype` = '".escapeSQL($mediafolder)."' AND `lastchange` < ".intval($lastcheck-10);
	$res = doSQL($sql);
	if ($res['num']>0) { 
		addWSPMsg('resultmsg', sprintf(
			returnIntLang('Removed %s outdated files with mediatype "%s" from database'), 
			$res['num'],
			$mediafolder)
		);
	}
endif;

if ($extern==1):
	include ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/headerempty.inc.php");
else:
	require ("./data/include/header.inc.php");
	require ("./data/include/wspmenu.inc.php");
endif;

$basedisplay = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'displaymedia'"));
$basesort = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'medialistsort'"));

$prescale = str_replace(" ", "", trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'autoscalepreselect'")));
if($prescale=="" || $prescale=="0"): $prescale = "1600x1200"; endif;
$pdfscaleprev=""; if($pdfscaleprev=="" || $pdfscaleprev=="0"): $pdfscaleprev = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'pdfscalepreview'")); endif;
if($pdfscaleprev=="" || $pdfscaleprev=="0"): $pdfscaleprev = "400x300"; endif;
$thumbsize = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'thumbsize'"));
if($thumbsize=="" || $thumbsize=="0"): $thumbsize = "200x200"; endif;

$m_sql = "SELECT `mid` FROM `wspmedia` WHERE `mediatype` = '".escapeSQL(trim($mediafolder))."' AND `filename` != ''";
$m_res = doSQL($m_sql);

$filecount = 0;
foreach ($fullmediastructure AS $fk => $fv) {
	$scandir = scandir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fk);
	foreach ($scandir AS $sv) {
		if (!(is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$fk."/".$sv)) && !($sv=='index.php') && !(substr($sv,0,1)=='.')) {
			$filecount++;
		}
	}
}
$sysinfo = "[SYS:".$filecount."#DB:".$m_res['num']."]";
$sysequal = (intval($filecount) == $m_res['num']);

?>
<div id="contentholder">
	<pre id="debug" style="clear: both;"></pre>
	<?php if ($extern!='1') echo "<fieldset class='text'><h1>".returnIntLang('media '.$mediafolder.' headline')."</h1></fieldset>\n"; ?>
	<script src="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/filemanagement/fileuploader.js" type="text/javascript"></script>
	<script type="text/javascript">
	<!--
	
	function delFile(fileFieldId) {
		if(confirm('<?php echo returnIntLang('msgbox really delete file', false); ?>')) {
			deleteFile(fileFieldId, document.getElementById('frommediafolder').value + document.getElementById('uploadpath').value, document.getElementById('fileimg' + fileFieldId).title);
			}
		}
	
	function createUploader(uid) {
		var uploadtarget = document.getElementById('uploadtarget_' + uid).value;
		var setautoscale = document.getElementById('autoscale').value;
		var setthumbsize = document.getElementById('thumbsize').value;
		var uploader = new qq.FileUploader({
			element: document.getElementById('uploader_' + uid),
			listElement: document.getElementById('uploaditems_' + uid),
			// url of the server-side upload script, should be on the same domain
			action: '/<?php echo $_SESSION['wspvars']['wspbasedir'] ?>/uploadmedia.php',
			template: '<div class="qq-uploader">' + 
				'<div class="qq-upload-drop-area"><span><?php echo returnIntLang('media upload drop files here', false); //' ?></span></div>' +
				'<div class="qq-upload-button"><?php echo returnIntLang('media upload drop or select files here', false); //' ?></div>' +
				'<ul class="qq-upload-list"></ul>' + 
				'</div>',
			fileTemplate: '<li>' +
				'<div class="filegrabber"><?php echo returnIntLang("media uploader upload", false); ?></div>' +
				'<div class="qq-upload-spinner"></div>' +
				'<div class="qq-upload-file"></div>' +
				'<div class="qq-upload-size"></div>' +
				'<a class="qq-upload-cancel" href="#"><?php echo returnIntLang("media cancel upload", false); ?></a>' +
				'</li>',        
	        classes: {
	            // used to get elements from templates
	            button: 'qq-upload-button',
	            drop: 'qq-upload-drop-area',
	            dropActive: 'qq-upload-drop-area-active',
	            list: 'qq-upload-list',
	            file: 'qq-upload-file',
	            spinner: 'qq-upload-spinner',
	            size: 'qq-upload-size',
	            cancel: 'qq-upload-cancel',
	            // added to list item when upload completes
	            // used in css to hide progress spinner
	            success: 'qq-upload-success',
	            fail: 'qq-upload-fail'
	            },
			// additional data to send, name-value pairs
			params: {
		    	prescale: setautoscale,
		    	thumbsize: setthumbsize,
		    	targetfolder: uploadtarget,
		    	mediafolder: '<?php echo $mediafolder; ?>',
		    	},
			minSizeLimit: 0, // min size
			debug: true
           });
       }
	
	function showUpload(fkid) {
		if ($("#btn_upload_" + fkid).hasClass('orange')) {
			$("li.upload.shown").removeClass('shown');
			$(".uploadbutton").addClass('orange');
			$("#upload_" + fkid).addClass('shown');
			$("#btn_upload_" + fkid).toggleClass('orange', 1);
			createUploader(fkid);
			document.getElementById('activeupload').value = fkid;
			}
		else {
			$("#upload_" + fkid).removeClass('shown');
			$("#btn_upload_" + fkid).toggleClass('orange', 1);
			document.getElementById('activeupload').value = fkid;
			}
		}
	
	function updateUpload(fkid) {
		if (fkid!='') { createUploader(fkid); }
		}
	
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
		document.getElementById('detailfilename').value = fileID;
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
	
    function optionDeleteDir(fkid) {
        $.post("xajax/ajax.returnmediafilelist.php", { 'fkid': fkid, 'stat': 'countdelete'}).done (function(data) {
            if (parseInt(data)==0 && fkid!=0) {
                $('#btn_del_' + fkid).show();
            }
        });
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
		var subdirto = document.getElementById('subdirname_'+ fkid).value;
		var newdirname = document.getElementById('newdirname_'+ fkid).value;
		if ($.trim(subdirto)!="" && $.trim(newdirname)!="") {
			$.post("xajax/ajax.createnewdir.php", { 'subdirto': subdirto, 'newdirname': newdirname, 'mediatype': '<?php echo $mediafolder; ?>'})
				.done(function(data) {
					if(data==true || data=='1') {
						document.location.reload();
						}
					else {
						console.info(data);
						alert('media error creating dir');
						}
					})
			}
		}

	function confirmDeleteDir(fkid) {
		if ($.trim(fkid)!="") {
			$.post("xajax/ajax.deletedir.php", { 'dirid': $.trim(fkid) } )
				.done (function(data) {
					var returnData = JSON.parse(data);
					if(returnData['success']) {
						$('#files_'+returnData.id).hide();
						$('#folder_'+returnData.id).fadeOut(500, function() {
							$('#folder_'+returnData.id).remove();
							$('#uploadholder_'+returnData.id).remove();
							$('#uploaditems_'+returnData.id).remove();
							$('#createsubdir_'+returnData.id).remove();
							$('#files_'+returnData.id).remove();
							});
						}
					else {
						alert (returnData['msg']);
						}
					});
			}
		}

	function confirmDeleteFile(fileFieldId) {
		if (fileFieldId!="") {
			$.post("xajax/ajax.deletefile.php", { 'fileid': fileFieldId } )
				.done (function(data) {
					var returnData = JSON.parse(data);
					if(returnData['success']) {
						$('#'+returnData['removedfile']).hide('puff');
					} else {
						alert(returnData['msg']);
						}
					});
			}
		}
		
    // -->
	</script>
	<fieldset>
		<legend><?php echo returnIntLang('media search legend', true); ?></legend>
		<ul style="float: right; list-style-type: none;" id="displaylist_display">
			<li style="float: left;"><?php echo returnIntLang('media displaylist display', false); ?>&nbsp;</li>
			<li style="float: left;"><span class="bubblemessageholder"><span class="bubblemessage <?php if($basedisplay!='list') echo "orange"; ?>" id="displaylist_list" onclick="setDisplayList('list');" style="cursor: pointer;"><?php echo returnIntLang('bubble list', false); ?></span></span>&nbsp;</li>
			<li style="float: left; display: none;"><span class="bubblemessageholder"><span class="bubblemessage <?php if($basedisplay!='box') echo "orange"; ?>" id="displaylist_box" onclick="setDisplayList('box');" style="cursor: pointer;"><?php echo returnIntLang('bubble box', false); ?></span></span>&nbsp;</li>
			<li style="float: left;"><span class="bubblemessageholder"><span class="bubblemessage <?php if($basedisplay!='tinybox') echo "orange"; ?>" id="displaylist_tinybox" onclick="setDisplayList('tinybox');" style="cursor: pointer;"><?php echo returnIntLang('bubble tinybox', false); ?></span></span>&nbsp;</li>
		</ul>
		<ul style="float: right; list-style-type: none;" id="displaylist_sort">
			<li style="float: left;"><?php echo returnIntLang('media displaylist sort', false); ?>&nbsp;</li>
			<li style="float: left;"><span class="bubblemessageholder"><span class="bubblemessage <?php if($basesort!='name') echo "orange"; ?>" id="sortlist_name" onclick="setSortList('name');" style="cursor: pointer;"><?php echo returnIntLang('bubble name', false); ?></span></span>&nbsp;</li>
			<li style="float: left;"><span class="bubblemessageholder"><span class="bubblemessage <?php if($basesort!='date') echo "orange"; ?>" id="sortlist_date" onclick="setSortList('date');" style="cursor: pointer;"><?php echo returnIntLang('bubble date', false); ?></span></span>&nbsp;</li>
			<li style="float: left;"><span class="bubblemessageholder"><span class="bubblemessage <?php if($basesort!='size') echo "orange"; ?>" id="sortlist_size" onclick="setSortList('size');" style="cursor: pointer;"><?php echo returnIntLang('bubble size', false); ?></span></span>&nbsp;</li>
		</ul>
		<ul style="list-style-type: none;">
			<li><input id="searchlist" value="" style="width: 50%;" onchange="setSearchList(this.value);" onblur="setSearchList(this.value);"></li>
		</ul>
		<input type="hidden" id="countlist" value="10000" />
		<input type="hidden" id="displaylist" value="<?php echo $basedisplay; ?>" />
		<input type="hidden" id="sortlist" value="<?php echo $basesort; ?>" />
		<input type="hidden" id="activefolder" value="" />
		<input type="hidden" id="activeupload" value="" />
		<input type="hidden" id="keypress" value="" />
		<input type="hidden" id="activelist" value="/media/<?php echo $mediafolder; ?>/" />
	</fieldset>
	<?php 
    
    $autoscale = false;
    $thumbsize = false;
    
    if(array_key_exists('wspvars', $_SESSION) && array_key_exists('upload', $_SESSION['wspvars']) && ((array_key_exists('scale', $_SESSION['wspvars']['upload']) && $_SESSION['wspvars']['upload']['scale']) || (array_key_exists('thumbs', $_SESSION['wspvars']['upload']) && $_SESSION['wspvars']['upload']['thumbs']) || (array_key_exists('preview', $_SESSION['wspvars']['upload']) && $_SESSION['wspvars']['upload']['preview']))): ?>
		<fieldset id="uploadprefs">
			<legend><?php echo returnIntLang('media uploadprefs', true); ?></legend>
			<table class="tablelist">
				<tr>
					<?php 
                    
                    if(array_key_exists('wspvars', $_SESSION) && array_key_exists('upload', $_SESSION['wspvars']) && ((array_key_exists('scale', $_SESSION['wspvars']['upload']) && $_SESSION['wspvars']['upload']['scale']))): ?>
					<td class="tablecell two"><?php echo returnIntLang('media autoscale', true); ?></td>
					<td class="tablecell two"><input id="autoscale" name="autoscale" onchange="updateUpload(document.getElementById('activeupload').value);" value="<?php echo $prescale; ?>" style="width: 10em;" /> PX x PX</td>
					<?php 
                    
                    $autoscale = true;
                    endif; ?>
                    
					<?php if(array_key_exists('wspvars', $_SESSION) && array_key_exists('upload', $_SESSION['wspvars']) && ((array_key_exists('preview', $_SESSION['wspvars']['upload']) && $_SESSION['wspvars']['upload']['preview']))): ?>
					<td class="tablecell two"><?php echo returnIntLang('media preview size', true); ?></td>
					<td class="tablecell two"><input  id="autoscale" name="autoscale" onchange="updateUpload(document.getElementById('activeupload').value);" value="<?php echo $prescale; ?>" style="width: 10em;" /> PX x PX</td>
					<?php 
                    
                    $autoscale = true;
                    endif; ?>
                    
					<?php if(array_key_exists('wspvars', $_SESSION) && array_key_exists('upload', $_SESSION['wspvars']) && ((array_key_exists('thumbs', $_SESSION['wspvars']['upload']) && $_SESSION['wspvars']['upload']['thumbs']))): ?>
					<td class="tablecell two"><?php echo returnIntLang('media thumbnail size', true); ?></td>
					<td class="tablecell two">max. <input  id="thumbsize" name="thumbsize" onchange="updateUpload(document.getElementById('activeupload').value);" value="<?php echo $thumbsize; ?>" style="width: 10em;" /> PX</td>
					<?php 
                    
                    $thumbsize = true;
                    endif; ?>
				</tr>
			</table>
		</fieldset>
    <?php endif; flush(); flush(); ?>
	<?php if($autoscale===false) { echo "<input type='hidden' id='autoscale' name='autoscale' value='5000x5000' />"; } ?>
    <?php if($thumbsize===false) { echo "<input type='hidden' id='thumbsize' name='thumbsize' value='500x500' />"; } ?>
	<fieldset id="filesearch" style="display: none;"></fieldset>
	<fieldset id="filesystem">
		<legend><?php echo returnIntLang('media filestructure', true); ?></legend>
		<?php
		// init full structure array
		$fullstructure = array();
		// get hidemedia option
		$hide_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddenmedia'";
		$hide_res = doResultSQL($hide_sql);
		// define hidemedia sql statement
		$hidemedia = "";
		if ($hide_res && trim($hide_res!='')): 
			$hiddenmedia = explode(",", trim($hide_res));
			$hideoption = array(" `filefolder` NOT LIKE '/thumbs/%' ");
			foreach ($hiddenmedia AS $k => $v):
				$hideoption[] = " `filefolder` NOT LIKE '/".$v."/%' ";
			endforeach;
			$hidemedia = " AND (".implode(" AND ", $hideoption).") ";	
		endif;
		// read structure from db
		$s_sql = "SELECT DISTINCT `filefolder` FROM `wspmedia` WHERE `mediatype` = '".$mediafolder."' ".$hidemedia." ORDER BY `filefolder`";
		$s_res = doSQL($s_sql);
		if ($s_res['num']>0):
			foreach ($s_res['set'] AS $sresk => $sresv):
				$m_sql = "SELECT `mid` FROM `wspmedia` WHERE `mediatype` = '".$mediafolder."' AND `filefolder` = '".trim($sresv['filefolder'])."' AND `filename` != '' ORDER BY `filename` ";
				$m_res = doSQL($m_sql);		
				$fullstructure[] = array('folder' => str_replace("//", "/", "/media/".$mediafolder."/".trim($sresv['filefolder'])), 'trimfolder' => trim($sresv['filefolder']), 'count' => $m_res['num'] );
			endforeach;
			$_SESSION['fullstructure'] = $fullstructure;
		endif;
		
		if (count($fullstructure)>0):
			foreach ($fullstructure AS $fk => $fv):
				// if restrictions exist
				if (isset($_SESSION['wspvars']['rights'][$mediafolder.'folder']) && (strstr(trim($fv['folder']), $_SESSION['wspvars']['rights'][$mediafolder.'folder']) || intval($_SESSION['wspvars']['rights'][$mediafolder.'folder'])==1) || intval($_SESSION['wspvars']['usertype'])==1):
					// has subdirectories ???
					$errortype = ''; if (str_replace("_", "-", str_replace("/", "-", strtolower($fv['trimfolder'])))!=urltext(str_replace("_", "-", str_replace("/", "-", strtolower($fv['trimfolder']))))): $errortype = 'false'; endif;
					$subdir = false; if ($fk>0 && $fk<(count($fullstructure)-1) && strstr($fullstructure[($fk+1)]['trimfolder'], $fv['trimfolder'])): $subdir = true; endif;
					echo "<ul id=\"folder_".$fk."\" class=\"folder ".$basedisplay." ".$errortype." \">";
					echo "<li class='folder ".$basedisplay;
					if ($fv['count']==0): echo " empty"; endif;
					if ($fv['count']>0): echo " closed"; endif; // add options for returning visitors or reloading page to display open folder
					echo "'><ul class='folderdata ".$basedisplay."'>";
					echo "<li class='foldergrabber ".$basedisplay."'>&nbsp;</li>";
					echo "<li class='foldericon ".$basedisplay."'>&nbsp;</li>";
					echo "<li class='foldername ".$basedisplay."'>".trim($fv['trimfolder'])."</li>";
					echo "<li class='foldersize ".$basedisplay."'>";
					echo "</li>";
					echo "<li class='folderaction ".$basedisplay."'>";
					 // allow to delete folder if no content is used
					if (!($subdir)) {
                        echo "<span id='btn_del_".$fk."' ";
                        if ($fk==0 || $fv['count']>0) {
                            echo " style='display: none;' ";
                        }
                        echo " class='bubblemessage red' onclick='if(confirm(\"". returnIntLang('confirm delete directory', false) ."\")) {confirmDeleteDir(".$fk.");};'>".returnIntLang('bubble delete', false)."</span> ";
                    }
					 // open folder
					echo "<span id='btn_open_".$fk."' class='bubblemessage ";
					echo "orange' onclick='showFiles(".$fk.");'>".$fv['count']." ".returnIntLang('bubble files', false)."</span> ";
					// upload to folder
					echo "<span id='btn_upload_".$fk."' class='uploadbutton bubblemessage orange' onclick='showUpload(".$fk.");'>".returnIntLang('bubble upload', false)."</span> ";
					// create subfolder
					echo "<span id='btn_createdir_".$fk."' class='bubblemessage orange' onclick='showCreateDir(".$fk.");'>".returnIntLang('bubble adddir', false)."</span> ";
					echo "</li>";
					echo "<li class='closefolder ".$basedisplay."'>&nbsp;</li>";
					echo "</ul></li>";
					echo "</ul>";				
					// upload area ..
					echo "<ul id='uploadholder_".$fk."' class='upload ".$basedisplay."' folder='".trim($fv['folder'])."'>";
					echo "<li class='upload ".$basedisplay."' id=\"upload_".$fk."\"><ul class='uploaddata'>";
					echo "<li>";
					?>
					<div id="uploader_<?php echo $fk; ?>"></div>
					<?php
					echo "</li>";
					echo "</ul><input type='hidden' id='uploadtarget_".$fk."' value='".$fv['folder']."' /></li>";
					echo "</ul>";
					// upload area
					echo "<ul id=\"uploaditems_".$fk."\" class=\"uploaditems ".$basedisplay."\" folder=\"".$fv['folder']."\"></ul>";
					// directory ara
					echo "<ul id=\"createsubdir_".$fk."\" class=\"createsubdir ".$basedisplay."\" style=\"display: none;\" ><li><input type=\"text\" name=\"newdirname\" id=\"newdirname_".$fk."\"><input type=\"hidden\" name=\"subdirname\" id=\"subdirname_".$fk."\" value='".$fv['folder']."'> ";
					echo "<span id='btn_makedir_".$fk."' class='bubblemessage green' onclick='createNewDir(".$fk.");'>".returnIntLang('bubble save', false)."</span> ";
					echo "<span id='btn_canceldir_".$fk."' class='bubblemessage red' onclick='showCreateDir(".$fk.");'>".returnIntLang('bubble cancel', false)."</span> ";
					echo "</li></ul>";
					// file area
					echo "<ul id=\"files_".$fk."\" class=\"dropable\" folder=\"".$fv['folder']."\" style=\"display: none;\"></ul>";
                    echo "<script type='text/javascript'>\n";
                    echo "optionDeleteDir(".$fk.");\n\n";
                    echo "</script>";
					if (isset($_SESSION['wspvars']['openmediafolder']) && $_SESSION['wspvars']['openmediafolder']==$fv['folder']): $openmediaid = $fk; $_SESSION['wspvars']['openmediafolder'] = ''; endif;
					flush();flush();flush();
				endif;
			endforeach;
		else:
			echo "<p>".returnIntLang('media filestructure missing')."</p>";
			$sysequal = true;
		endif;
		?>
	</fieldset>
	
	<?php if ($extern!='1' && !$sysequal) { ?>
		<form id="readstructure"><input type="hidden" name="op" value='readstructure'><input type="hidden" name="folder" value="<?php echo $mediafolder; ?>" /></form>
		<fieldset class="options innerfieldset"><p><a onclick="document.getElementById('readstructure').submit();" class="greenfield" title="<?php echo $sysinfo; ?>"><?php echo returnIntLang('filesystem reroll1'); ?> (<?php echo $filecount." ".returnIntLang('str files'); ?>) <?php echo returnIntLang('filesystem reroll2'); ?></a></p></fieldset>
	<?php } ?>

	<input type="hidden" name="savefiledesc" id="savefiledesc" value="" />
	<form name="viewfile" id="viewfile" action="<?php echo "/".$_SESSION['wspvars']['wspbasedir']."/mediadetails.php"; ?>" method="post" enctype="multipart/form-data">
		<input type="hidden" name="showfile" id="detailfilename" value="" />
		<input type="hidden" name="medialoc" id="detailmedialoc" value="<?php echo $_SERVER['PHP_SELF']; ?>" />
		<input type="hidden" name="media_folder" id="media_folder" value="<?php echo "/media/" . $mediafolder ?>" />
	</form>
	
	<?php if ($extern!=1): ?>
	<script type="text/javascript" language="javascript" charset="utf-8">
	
	$(document).ready(function(){
		$( ".dropable" ).sortable({placeholder: "ui-state-highlight", connectWith: ".dropable", handle: '.movehandle', receive: function (event, ui) { 
		$.post("xajax/ajax.dragdropmediafile.php", {'base': '<?php echo "/media/".$mediafolder."/"; ?>', 'fkey': ui.item.attr('id'), 'target': $(this).attr('folder'), 'copykey': document.getElementById('keypress').value})
			.done (function(data) {
				if ($.trim(data)!='') { alert(data); }
				var openFolders = document.getElementById('activefolder').value;
				if ($.trim(openFolders)!='') {
					var Folders = openFolders.split(';');
					for (f=0; f<Folders.length; f++) {
						updateSortList(Folders[f]);
						}
					}
				});
     		}}); $( ".movehandle" ).disableSelection();
	
		$(document).bind('keydown', function(e) {
			if(e.keyCode==16){
				// Ctrl pressed... do anything here...
				document.getElementById('keypress').value = 'copy';
				}
			});	
		$(document).bind('keyup', function(e) {
			if(e.keyCode==16){
				// Ctrl release… do anything here...
				document.getElementById('keypress').value = '';
				}
			});	
		
		<?php if (isset($openmediaid) && $openmediaid!=''): echo "showFiles(".$openmediaid.")"; endif; ?>
		
		});
	
	</script>
	<?php endif; ?>
</div>
<?php

if ($extern==1):
	include ("data/include/footerempty.inc.php");
else:
	include ("data/include/footer.inc.php");
endif;
