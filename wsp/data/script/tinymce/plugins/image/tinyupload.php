<?php
/**
 * image upload
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-25
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/usestat.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wsplang.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php");
/* checkParamVar ----------------------------- */
$op = checkParamVar('op', '');
$extern = checkParamVar('extern', 0);
/* define actual system position ------------- */
//	$_SESSION['wspvars']['mgroup'] = 6;
//	$_SESSION['wspvars']['lockstat'] = 'documents';
//	$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
//	$_SESSION['wspvars']['fposcheck'] = false;
//	$_SESSION['wspvars']['menuposition'] = 'download'; // ?? is dieser Eintrag richtig?
/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$mediafolder = "images";
$mediadesc = "Bilder";
// define upload based vars
$_SESSION['wspvars']['upload']['basetarget'] = 'images';
$_SESSION['wspvars']['upload']['extensions'] = 'jpg;jpeg;png;gif'; // leer, da jegliche Dateien hochgeladen werden können
$_SESSION['wspvars']['upload']['scale'] = true;
$_SESSION['wspvars']['upload']['thumbs'] = true;
$_SESSION['wspvars']['upload']['preview'] = true;

$fullmediastructure = array('/media/'.$mediafolder.'/' => array());
$fullmediatmp = getDirList('/media/'.$mediafolder, 1);
$fsizes = array('Byte', 'KB', 'MB', 'GB', 'TB');
foreach ($fullmediatmp AS $fmkey => $fmvalue):
	$fullmediastructure[trim($fmvalue."/")] = array();
endforeach;
unset ($fullmediatmp);

$desc_sql = "SELECT * FROM `mediadesc`"; // check mediadesc
$desc_res = doSQL($desc_sql);
$allmediadesc = array(); // empty mediadetails

if ($desc_res['num']>0) {
    foreach($desc_res['set'] AS $mdk => $mdv) {
        $allmediadesc[trim($mdv["mediafile"])] = trim($mdv["filedesc"]);			
    }
}

$_SESSION['xajaxmedialist'] = array();
foreach ($fullmediastructure AS $fmkey => $fmvalue):
	$d = dir($_SERVER['DOCUMENT_ROOT'].$fmkey);
	while (false !== ($entry = $d->read())):
		if ((substr($entry, 0, 1)!='.') && ($entry!='thumbs') && (is_file($_SERVER['DOCUMENT_ROOT'].$fmkey.'/'.$entry))):
			$thisimgdata = @getimagesize($_SERVER['DOCUMENT_ROOT'].$fmkey.'/'.$entry);
			$thisfiledata = @stat($_SERVER['DOCUMENT_ROOT'].$fmkey.'/'.$entry);
//			if (intval($thisimgdata[0])>0):
			if (intval($thisfiledata[7])>0): // geändert, da bei Download auch andere Dateien als Bilder sein können
				if(intval($thisimgdata[0])>0):
					$size = intval($thisimgdata[0]).' x '.intval($thisimgdata[1]);
				else:
					$size = "";
				endif;
				$mytype = substr($entry,strrpos($entry, ".")+1);
				if (is_file(str_replace("/media/".$mediafolder, "/media/".$mediafolder."/thumbs/", $_SERVER['DOCUMENT_ROOT'].$fmkey.'/'.$entry))):
					$thumbnail = str_replace("//", "/", str_replace("/media/".$mediafolder, "/media/".$mediafolder."/thumbs/", $fmkey.'/'.$entry));
				else:
					$thumbnail = '';
				endif;
				if (fileinuse($_SERVER['DOCUMENT_ROOT'].$fmkey, $entry)):
					$inuse = 'true';
				else:
					$inuse = 'false';
				endif;
				if (intval($thisfiledata[7])>0):
					$filesize = intval($thisfiledata[7]);
					$lastchange = intval($thisfiledata[9]);
				else:
					$filesize = 0;
					$lastchange = 0;
				endif;
				// set empty string to description array if key doesn't exist
				if (!(key_exists(str_replace("//", "/", $fmkey.'/'.$entry), $allmediadesc))) $allmediadesc[str_replace("//", "/", $fmkey.'/'.$entry)] = '';
				$fullmediastructure[trim($fmkey)][$entry] = array(
					'filepath' => trim($fmkey),
					'filename' => trim($entry),
					'filetype' => $mytype,
					'inuse' => fileinuse($_SERVER['DOCUMENT_ROOT'].$fmkey, $entry),
					'description' => $allmediadesc[str_replace("//", "/", $fmkey.'/'.$entry)],
					'keywords' => '',
					'thumbnail' => $thumbnail,
					'size' => $size,
					'filesize' => $filesize,
					'lastchange' => $lastchange,
					'md5key' => md5(str_replace("//", "/", trim($fmkey)."/".trim($entry)))
					);
				$_SESSION['xajaxmedialist'][md5(str_replace("//", "/", trim($fmkey)."/".trim($entry)))] = array('directory' => str_replace("//", "/", trim($fmkey)), 'file' => str_replace("//", "/", trim($entry)));
				unset($size);
			endif;
			unset($thisimgdata);	
		endif;
	endwhile;
	$d->close();
endforeach;
ksort($fullmediastructure);
$_SESSION['xajaxmediastructure'] = $fullmediastructure;
$_SESSION['xajaxmediafolder'] = array();
foreach($fullmediastructure AS $fmk => $fmv):
	$_SESSION['xajaxmediafolder'][] = $fmk;
endforeach;

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<meta name="author" content="http://www.covi.de">
	<meta name="copyright" content="http://www.covi.de">
	<meta name="publisher" content="http://www.covi.de">
	<meta name="robots" content="nofollow">
	<title>WSP TinyMCE TinyUpload</title>
	<!-- viewport definitions especially for mobile devices -->
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<!-- get font from google -->
	<link href="http://fonts.googleapis.com/css?family=Open+Sans:300,700,600" rel="stylesheet" type="text/css">
	<link href="http://weloveiconfonts.com/api/?family=fontawesome" rel="stylesheet" type="text/css">
	<!-- base desktop stylesheet -->
	<link rel="stylesheet" href="/wsp/media/layout/flexible.css.php" media="screen" type="text/css">
	<!-- print_screen extensions -->
	<link rel="stylesheet" href="/wsp/media/layout/print.css.php" media="print" type="text/css">
	<!-- self colorize extensions -->
	<link rel="stylesheet" href="/wsp/media/layout/wsp.css.php" media="screen" type="text/css">
	<link rel="shortcut icon" href="/wsp/media/screen/favicon.ico">
	<link rel="apple-touch-icon" href="/wsp/media/screen/iphone_favicon.png">
	<script src="/wsp/data/javascript/basescript.js.php"></script>
	<!-- get jquery -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<!-- get jquery user interface extension -->
	<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
	<!-- get jquery user interface extension css -->
	<link rel="stylesheet" href="/wsp/data/javascript/jquery/css/wsptheme/jquery-ui.custom.css" media="screen" type="text/css">
	<link rel="stylesheet" href="/wsp/media/layout/horizontal.css.php" media="screen" type="text/css">
	<style type="text/css">
	<!--

	li.upload {
		list-style-type: none;
		position: relative;
		border-radius: 0px;
		border: none;
		float: none;
		clear: both;
		margin: 0.2%;
		width: auto;
		max-width: 97%;
		height: auto;
		display: block;
		}

	ul.uploaddata { position: relative; overflow: hidden; list-style-type: none; font-size: 14px; }
	div.qq-upload-failed-text { display: none; }
	div.qq-upload-button { padding: 3px; }
	div.qq-upload-drop-area { position: absolute; background: #d9d9d9; z-index: 10; padding: 3px; height: 100%; width: 100%; }
	li.qq-upload-fail {}
	li.qq-upload-success {}
	ul.uploaditems li div.filegrabber { display: none; }
	ul.uploaditems li div.qq-upload-file { width: 25%; float: left; }
	ul.uploaditems li div.qq-upload-size { width: 25%; float: left; }
	div.qq-upload-file { position: relative; display: block; max-width: 97%; line-height: 1.3em; clear: both; }

	div.qq-upload-file { display: none; }
	
	div.qq-upload-size { position: relative; display: block; max-width: 97%; line-height: 1.3em; clear: both; }
	div.qq-upload-failed-text { position: relative; display: block; max-width: 97%; line-height: 1.3em; clear: both; }

	ul.uploaditems.list { margin: 1%; list-style-type: none; font-size: 12px; background: #d9d9d9; }

	-->
	</style>
</head>
<body style="overflow: hidden;">
<?php
$basedisplay = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'displaymedia'"));
$basesort = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'medialistsort'"));

if($prescale=="" || $prescale=="0"): $prescale = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'autoscalepreselect'")); endif;
if($prescale=="" || $prescale=="0"): $prescale = "400x300"; endif;

$thumbsize = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'thumbsize'"));
if($thumbsize=="" || $thumbsize=="0"): $thumbsize = "100x100"; endif;

?>
<div id="contentholder" style="width: 442px; height: 195px;">
	<pre id="debug" style="clear: both;"></pre>
		<script src="/wsp/data/javascript/filemanagement/filemanagement.js" type="text/javascript"></script>
		<script src="/wsp/data/javascript/filemanagement/fileuploader.js" type="text/javascript"></script>
		<script language="JavaScript1.2" type="text/javascript">
		<!--
		
	
	function createUploader(uid) {
		var setautoscale = document.getElementById('autoscale').value;
		var setthumbsize = document.getElementById('thumbsize').value;
//		var setautoscale = '0';
//		var setthumbsize = '0';
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
				'<div class="filegrabber"><?php echo returnIntLang('str uploader upload', false); //' ?></div>' +
				'<div class="qq-upload-spinner"></div>' +
				'<div class="qq-upload-file"></div>' +
				'<div class="qq-upload-size"></div>' +
				'<a class="qq-upload-cancel" href="#"><?php echo returnIntLang('str cancel upload', false); //' ?></a>' +
				'<div class="qq-upload-failed-text"><?php echo returnIntLang('str error upload', false); //' ?></div>' +
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
		    	folderuid: uid
		    	},
		    onComplete: function() {
		    	complTest();
		    	},
			minSizeLimit: 0, // min size
			debug: true
           });
       }
	
	function showUpload(fkid) {
		$("li.upload.shown").removeClass('shown');
		$(".uploadbutton").addClass('orange');
		$("#upload_" + fkid).toggleClass('shown', 1);
		$("#btn_upload_" + fkid).toggleClass('orange', 1);
		createUploader(fkid);
		document.getElementById('activeupload').value = fkid;
		}
	
	function updateUpload(fkid) {
		if (fkid!='') {
			createUploader(fkid);
			}
		}
		
	function complTest() {
		window.parent.tinymce.activeEditor.plugins.image.testme();
		} 

	// -->
	</script>
	<span id='btn_upload_0' class='uploadbutton bubblemessage orange' onclick='showUpload(0);' style='display: none;'><?php echo returnIntLang('bubble upload', true); ?></span>
	<?php
	// upload area ..
	echo "<ul id=\"uploadholder_0\" class=\"upload ".$basedisplay."\" folder=\"/media/download/\">";
	echo "<li class='upload ".$basedisplay."' id=\"upload_0\"><ul class='uploaddata'>";
	?>
	<li>
	<div id="uploader_0">		
		<noscript>			
			<p>Please enable JavaScript to use file uploader.</p>
			<!-- or put a simple form for upload here -->
		</noscript>         
	</div>
	</li>
	</ul></li>
	</ul>
	<?php
	echo "<ul id=\"uploaditems_0\" class=\"uploaditems ".$basedisplay."\" folder=\"/media/download/\"></ul>";
	?>
	<input type="hidden" id="countlist" value="10000" />
	<input type="hidden" id="displaylist" value="<?php echo $basedisplay; ?>" />
	<input type="hidden" id="sortlist" value="<?php echo $basesort; ?>" />
	<input type="hidden" id="activefolder" value="" />
	<input type="hidden" id="activeupload" value="" />
	<input type="hidden" id="keypress" value="" />
	<input type="hidden" id="activelist" value="/media/<?php echo $mediafolder; ?>/" />
	<input type="hidden" id="autoscale" name="autoscale" value="<?php echo $prescale; ?>" />
	<input type="hidden" id="thumbsize" name="thumbsize" value="<?php echo $thumbsize; ?>" />
	</div>
	<script type="text/javascript" language="javascript" charset="utf-8">
	
	$(document).ready(function(){
		showUpload(0);	
		});
	
	</script>

	</body>
</html>
<?php // EOF ?>