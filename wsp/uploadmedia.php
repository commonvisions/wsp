<?php
/**
 * Upload Media Files
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-22
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
// define actual system position -------------
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
// define page specific funcs ---------------- 

class qqUploadedFileXhr {
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        if ($realSize != $this->getSize()){            
            return false;
	        }
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        return true;
	    }
    function getName() {
        return $_GET['qqfile'];
    	}
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
	private $sizeLimit = 20485760;
    private $file;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 20485760){        
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;
        
        $this->checkServerSettings();       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));
		}
    
    private function toBytes($str) 
	{
        $val = intval(trim($str));
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
			}
        return $val;
	}
    
    // returns array('success'=>true) or array('error'=>'error message')
    function handleUpload($targetfolder, $replaceOldFile = 0){
        $uploadTargetFolder = $targetfolder;
        $uploadTmpDirectory = str_replace("//", "/", 
			$_SERVER['DOCUMENT_ROOT'] . "/" . 
			$_SESSION['wspvars']['wspbasediradd'] . "/" . $_SESSION['wspvars']['wspbasedir'] . "/tmp/" . 
			$_SESSION['wspvars']['usevar'] . "/");
        
        // error outputs before processing
        if (!is_writable($uploadTmpDirectory)) return array('error' => returnIntLang('upload upload dir not writable 1', false)." \"".$uploadTmpDirectory."\" ".returnIntLang('upload upload dir not writable 2', false));
        if (!$this->file) return array('error' => returnIntLang('upload no files were uploaded', false));
        $size = $this->file->getSize(); if ($size == 0) return array('error' => returnIntLang('upload file is empty', false));
        if ($size > $this->sizeLimit) return array('error' => returnIntLang('upload file is too large', false));
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = removeSpecialChar($pathinfo['filename']);
        $ext = strtolower($pathinfo['extension']);
		
		$uploadFtpTmbDirectory = '';
		$uploadFtpOrgDirectory = '';
		$uploadFtpPrevDirectory = '';
		
		$uploadBaseTarget = $_SESSION['wspvars']['upload']['basetarget'];

		// thumbnail directory if image processing 
		if ($uploadBaseTarget=='screen' || $uploadBaseTarget=='images' || $uploadBaseTarget=='download'):
			$uploadTmbDirectory = str_replace("//", "/", str_replace("//", "/", "/".
				str_replace(
					"/media/".$uploadBaseTarget."/", 
					"/media/".$uploadBaseTarget."/thumbs/", 
					$uploadTargetFolder
				)
			));
        endif;

		// original directory if image processing
        if ($uploadBaseTarget=='images'):
			$uploadOrgDirectory = str_replace("//", "/", str_replace("//", "/", "/".
				str_replace(
					"/media/".$uploadBaseTarget."/", 
					"/media/".$uploadBaseTarget."/originals/", 
					$uploadTargetFolder
				)
			));
		endif;

        // preview directory if pdf processing
		if ($_SESSION['wspvars']['upload']['preview']):
			$uploadPrevDirectory = str_replace("//", "/", str_replace("//", "/", "/".str_replace("/media/download/", "/media/images/preview/", $uploadTargetFolder)));
	    endif;
        
        // check for right extensions
        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions) && !count($this->allowedExtensions)>0) {
            $exts = implode(', ', $this->allowedExtensions);
			return array('error' => sprintf(returnIntLang('upload file with invalid extension <strong>%s</strong>', false), $exts));
        }
        
        if ($replaceOldFile!=1) {
            /// don't overwrite previous files that were uploaded
            while (file_exists(str_replace("//", "/", str_replace("//", "/", 
				$_SERVER['DOCUMENT_ROOT'] . "/" . $_SESSION['wspvars']['wspbasediradd'] . "/" . 
				$uploadTargetFolder . "/" . $filename . "." . $ext)))) {
                $filename .= rand(10, 99);
            }
		}
        
		// tmp save the file
		$fileSave = $this->file->save($uploadTmpDirectory . $filename . '.' . $ext);		
		
        if ($fileSave):

			// optional pdf conversion
			if ($_SESSION['wspvars']['upload']['preview'] && $ext=="pdf" && function_exists('exec')) {
				if ($_SESSION['wspvars']['createimagefrompdf']!='nocheck') {
					// try create image from pdf-file 							
				 	@exec("/usr/bin/gs -q -dNOPAUSE -dBATCH -sDEVICE=jpeg -sOutputFile=".$uploadTmpDirectory.$filename.".jpg ".$uploadTmpDirectory.$filename.'.'.$ext);
				}
			}
			
			// resizing and copying
			if (function_exists('resizeGDimage') && 
				($ext=="gif" || $ext=="png" || $ext=="jpg" || $ext=="jpeg" || $ext=="pdf")) {
				$preview = null;
				$thumb = null;
				$original = null;
				$filedata = array(
					'size' => '', 
					'filesize' => $size, 
					'lastchange' => time(), 
					'md5key' => md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext)))
				);
			}

			$ftp = doFTP();
			if ($ftp) {
				$uploadFtpPrevPath = str_replace("//", "/", str_replace("//", "/", 
					($_SESSION['wspvars']['ftpbasedir'] ?? '') . "/" . $uploadPrevDirectory . 
					'/' . $filename . '.' . $ext));
				$uploadFtpTmbPath = str_replace("//", "/", str_replace("//", "/", 
					($_SESSION['wspvars']['ftpbasedir'] ?? '') . "/" . $uploadTmbDirectory . 
					'/' . $filename . '.' . $ext));
				$uploadFtpOrgPath = str_replace("//", "/", str_replace("//", "/", 
					($_SESSION['wspvars']['ftpbasedir'] ?? '') . "/" . $uploadOrgDirectory. 
					'/' . $filename . '.' . $ext));
				$uploadFtpPath = str_replace("//", "/", str_replace("//", "/", 
					($_SESSION['wspvars']['ftpbasedir'] ?? '') . "/" . $uploadTargetFolder . 
					'/' . $filename . '.' . $ext));
				
				if ($preview) {

				}
				if ($thumb) {
					
				}
				if ($original) {
					
				}
				// finally copy the (processed) upload to folder

			} else {
				$uploadPrevPath = str_replace("//", "/", str_replace("//", "/", 
					$_SERVER['DOCUMENT_ROOT'] . "/" . $_SESSION['wspvars']['wspbasediradd'] . '/' . 
					$uploadPrevDirectory. '/' . $filename . '.' . $ext));
				$uploadTmbPath = str_replace("//", "/", str_replace("//", "/", 
					$_SERVER['DOCUMENT_ROOT'] . "/" . $_SESSION['wspvars']['wspbasediradd'] . "/" . 
					$uploadTmbDirectory. '/' . $filename . '.' . $ext));
				$uploadOrgPath = str_replace("//", "/", str_replace("//", "/", 
					$_SERVER['DOCUMENT_ROOT'] . "/" . $_SESSION['wspvars']['wspbasediradd'] . "/" . 
					$uploadOrgDirectory. '/' . $filename . '.' . $ext));
				$uploadPath = str_replace("//", "/", str_replace("//", "/", 
					$_SERVER['DOCUMENT_ROOT'] . "/" . $_SESSION['wspvars']['wspbasediradd'] . "/" . 
					$uploadTargetFolder . '/' . $filename . '.' . $ext));

				$myFile = $uploadTmpDirectory . $filename . '.' . $ext;

				// finally copy the (processed) upload to folder
				/*
				$directCopy = $this->file->save($uploadPath);
				if ($directCopy) {
					$filedata = array('size' => '', 'filesize' => $size, 'lastchange' => time(), 'md5key' => md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext))));
					// check if file was just overwritten
					$e_sql = "SELECT `mid` FROM `wspmedia` WHERE `mediatype` = '".escapeSQL(trim($_REQUEST['mediafolder']))."' AND `mediafolder` = '".escapeSQL(trim($_REQUEST['targetfolder']))."' AND `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))))."' AND `filename` = '".escapeSQL(trim($filename.'.'.$ext))."' AND `filetype` = '".trim($ext)."'";
					$e_res = doSQL($e_sql);
					if ($e_res['num']>0):
						// use update statement
						$sql = "UPDATE `wspmedia` ";
					else:
						// use insert statement
						$sql = "INSERT INTO `wspmedia` ";
					endif;
					$sql.= " SET `mediatype` = '".escapeSQL($_REQUEST['mediafolder'])."', `mediafolder` = '".escapeSQL(trim($_REQUEST['targetfolder']))."', `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))))."', `filename` = '".escapeSQL(trim($filename.'.'.$ext))."', `filetype` = '".trim($ext)."', `filekey` = '".md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext)))."', `filedata` = '".serialize($filedata)."', `filesize` = ".intval($size).",`filedate` = ".time().", `thumb` = 0, `preview` = 0, `original` = 0, `embed` = 0, `lastchange` = ".time();
					if ($e_res['num']>0):
						// use update statement
						$sql.= " WHERE `mid` = ".intval($e_res['set'][0]['mid']);
					endif;
					doSQL($sql);
					return array('success' => true);
				} else {
					return array('success' => false, 'params' => serialize($_REQUEST), 'state' => 'no ftp, no direct copy');
				}
				*/

			}

			if ()


			/*
			if (function_exists('resizeGDimage') && ($ext=="gif" || $ext=="png" || $ext=="jpg" || $ext=="jpeg" || $ext=="pdf")):
				$ftp = doFTP();
				if ($ftp):
					$preview = 0;
					$thumb = 0;
					$original = 0;
					$filedata = array('size' => '', 'filesize' => $size, 'lastchange' => time(), 'md5key' => md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext))));
					if ($ext=="gif" || $ext=="png" || $ext=="jpg" || $ext=="jpeg"):
						// copy original IMAGES to destination
						if (trim($uploadFtpOrgDirectory)!=''):
							// try to create orig directory
							@ftpCreateDir('', $uploadFtpOrgDirectory);
							if (@ftp_put($ftp, $uploadFtpOrgDirectory."/".$filename.'.'.$ext, $uploadTmpDirectory."/".$filename.'.'.$ext, FTP_BINARY)):
								$original = 1;
							endif;
						endif;
					endif;
					// resize IMAGES if prescale defined
					if(trim($_REQUEST['prescale'])!="" && ($ext=="gif" || $ext=="png" || $ext=="jpg" || $ext=="jpeg")):
						$dimensions = array();
						$org_dimensions = array();
						$dimensions = explode("x", trim($_REQUEST['prescale']));
						$width = intval($dimensions[0]);
						$height = intval($dimensions[1]);
						if($height>0 && $width>0):
							$org_dimensions = @getimagesize($uploadTmpDirectory.$filename.'.'.$ext);
							$filedata['size'] = $org_dimensions[0].' x '.$org_dimensions[1];
							if((intval($org_dimensions[0])>$width) || (intval($org_dimensions[1])>$height)):
								resizeGDimage($uploadTmpDirectory.$filename.'.'.$ext, $uploadTmpDirectory.$filename.'.'.$ext, 0, $width, $height, 1);
								$filedata['size'] = $width.' x '.$height;
							endif;
						endif;
					endif;
					// copy document to destination
					if (@ftp_put($ftp, $uploadFtpDirectory."/".$filename.'.'.$ext, $uploadTmpDirectory."/".$filename.'.'.$ext, FTP_BINARY)):
						// try to copy PDF preview
						if($ext=="pdf" && $_SESSION['wspvars']['createimagefrompdf']!='nocheck'):
							// try create preview directory
							@ftpCreateDir('', $uploadFtpPrevDirectory);
							// copy file to preview directory
							if (@ftp_put($ftp, $uploadFtpPrevDirectory."/".$filename.'.jpg', $uploadTmpDirectory."/".$filename.'.jpg', FTP_BINARY)):
								$preview = 1;
								$prevsize = @ftp_size($ftp, $uploadFtpPrevDirectory."/".$filename.'.jpg');
								// check for existing entry om database
								$p_sql = "SELECT `mid` FROM `wspmedia` WHERE `mediatype` = 'images' AND `mediafolder` = '".escapeSQL(trim($uploadPrevDirectory))."' AND `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/images/","/",trim($uploadPrevDirectory))))))."' AND `filename` = '".escapeSQL(trim($filename.'.jpg'))."' AND `filetype` = 'jpg'";
								$p_res = doSQL($p_sql);
								if (!(isset($_SESSION['wspvars']['showsql']))): $_SESSION['wspvars']['showsql'] = array(); endif;
								if (!(is_array($_SESSION['wspvars']['showsql']))): $_SESSION['wspvars']['showsql'] = array(); endif;
								if ($p_res['num']>0):
									// use update statement
									$sql = "UPDATE `wspmedia` ";
								else:
									// use insert statement
									$sql = "INSERT INTO `wspmedia` ";
								endif;
								$sql.= " SET `mediatype` = 'images', `mediafolder` = '".trim($uploadPrevDirectory)."', `filefolder` = '".trim(str_replace("//","/",str_replace("//","/",str_replace("/media/images/","/",trim($uploadPrevDirectory)))))."', `filename` = '".trim($filename.'.jpg')."', `filetype` = 'jpg', `filekey` = '".md5(str_replace("//", "/", trim($uploadPrevDirectory)."/".trim($filename.'.jpg')))."', `filesize` = ".$prevsize.", `filedate` = ".time().", `embed` = 0, `lastchange` = ".time();
								if ($p_res['num']>0):
									// use update statement
									$sql.= " WHERE `mid` = ".intval($p_res['set'][0]['mid']);
								endif;
								doSQL($sql);
							endif;
						elseif ($ext=="gif" || $ext=="png" || $ext=="jpg" || $ext=="jpeg"):
							if (intval($_REQUEST['thumbsize'])==0):
								$thumbsize = 100;
							else:
								$thumbsize = intval($_REQUEST['thumbsize']);
							endif;
							$dimensions = array();
							$org_dimensions = array();
							$width = intval($thumbsize);
							$height = intval($thumbsize);
							if($height>0 && $width>0):
								$org_dimensions = @getimagesize($uploadTmpDirectory.$filename.'.'.$ext);
								if((intval($org_dimensions[0])>$width) || (intval($org_dimensions[1])>$height)):
									resizeGDimage($uploadTmpDirectory.$filename.'.'.$ext, $uploadTmpDirectory.$filename.'.'.$ext, 0, $width, $height, 1);
								endif;
							endif;
							// try to create tmbdir
							@ftpCreateDir('', $uploadFtpTmbDirectory);
							if(@ftp_put($ftp, $uploadFtpTmbDirectory."/".$filename.'.'.$ext, $uploadTmpDirectory."/".$filename.'.'.$ext, FTP_BINARY)):	
								@unlink($uploadTmpDirectory."/".$filename.'.'.$ext);
								$thumb = 1;
							endif;
						endif;
						
						// check if file was just overwritten
						$e_sql = "SELECT `mid` FROM `wspmedia` WHERE `mediatype` = '".$_REQUEST['mediafolder']."' AND `mediafolder` = '".escapeSQL(trim($_REQUEST['targetfolder']))."' AND `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))))."' AND `filename` = '".escapeSQL(trim($filename.'.'.$ext))."' AND `filetype` = '".trim($ext)."'";
						$e_res = doSQL($e_sql);
						if ($e_res['num']>0):
							// use update statement
							$sql = "UPDATE `wspmedia` ";
						else:
							// use insert statement
							$sql = "INSERT INTO `wspmedia` ";
						endif;
						$sql.= " SET `mediatype` = '".escapeSQL(trim($_REQUEST['mediafolder']))."', `mediafolder` = '".escapeSQL(trim($_REQUEST['targetfolder']))."', `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))))."', `filename` = '".escapeSQL(trim($filename.'.'.$ext))."', `filetype` = '".trim($ext)."', `filekey` = '".md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext)))."', `filedata` = '".escapeSQL(serialize($filedata))."', `filesize` = ".intval($size).",`filedate` = ".time().", `thumb` = ".$thumb.", `preview` = ".$preview.", `original` = ".$original.", `embed` = 0, `lastchange` = ".time();
						if ($e_res['num']>0):
							// use update statement
							$sql.= " WHERE `mid` = ".intval($e_res['set'][0]['mid']);
						endif;
						doSQL($sql);
						return array('success' => true);
					else:  // ftp move of file wasn't possible
						return array('success' => false);
					endif;
				endif;
				ftp_close($ftp);
			else:
			*/
				// resizing isn't possible
				// files could be handled in any other way
				
				if ($ftp):
					if (@ftp_put($ftp, $uploadFtpDirectory."/".$filename.'.'.$ext, $uploadTmpDirectory."/".$filename.'.'.$ext, FTP_BINARY)):
						$filedata = array('size' => '', 'filesize' => $size, 'lastchange' => time(), 'md5key' => md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext))));
						// check if file was just overwritten
						$e_sql = "SELECT `mid` FROM `wspmedia` WHERE `mediatype` = '".escapeSQL(trim($_REQUEST['mediafolder']))."' AND `mediafolder` = '".escapeSQL(trim($_REQUEST['targetfolder']))."' AND `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))))."' AND `filename` = '".escapeSQL(trim($filename.'.'.$ext))."' AND `filetype` = '".trim($ext)."'";
						$e_res = doSQL($e_sql);
						if ($e_res['num']>0):
							// use update statement
							$sql = "UPDATE `wspmedia` ";
						else:
							// use insert statement
							$sql = "INSERT INTO `wspmedia` ";
						endif;
						$sql.= " SET `mediatype` = '".escapeSQL($_REQUEST['mediafolder'])."', `mediafolder` = '".escapeSQL(trim($_REQUEST['targetfolder']))."', `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))))."', `filename` = '".escapeSQL(trim($filename.'.'.$ext))."', `filetype` = '".trim($ext)."', `filekey` = '".md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext)))."', `filedata` = '".serialize($filedata)."', `filesize` = ".intval($size).",`filedate` = ".time().", `thumb` = 0, `preview` = 0, `original` = 0, `embed` = 0, `lastchange` = ".time();
						if ($e_res['num']>0):
							// use update statement
							$sql.= " WHERE `mid` = ".intval($e_res['set'][0]['mid']);
						endif;
						doSQL($sql);
						return array('success'=> true);
					else:
						return array('success'=> false, 'params' => serialize($_REQUEST));
					endif;
					ftp_close($ftp);
				else:
					$targetCopy = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$uploadTargetFolder."/".$filename.".".$ext));
					$directCopy = $this->file->save($targetCopy);
					if ($directCopy) {
						$filedata = array('size' => '', 'filesize' => $size, 'lastchange' => time(), 'md5key' => md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext))));
						// check if file was just overwritten
						$e_sql = "SELECT `mid` FROM `wspmedia` WHERE `mediatype` = '".escapeSQL(trim($_REQUEST['mediafolder']))."' AND `mediafolder` = '".escapeSQL(trim($_REQUEST['targetfolder']))."' AND `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))))."' AND `filename` = '".escapeSQL(trim($filename.'.'.$ext))."' AND `filetype` = '".trim($ext)."'";
						$e_res = doSQL($e_sql);
						if ($e_res['num']>0):
							// use update statement
							$sql = "UPDATE `wspmedia` ";
						else:
							// use insert statement
							$sql = "INSERT INTO `wspmedia` ";
						endif;
						$sql.= " SET `mediatype` = '".escapeSQL($_REQUEST['mediafolder'])."', `mediafolder` = '".escapeSQL(trim($_REQUEST['targetfolder']))."', `filefolder` = '".escapeSQL(trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))))."', `filename` = '".escapeSQL(trim($filename.'.'.$ext))."', `filetype` = '".trim($ext)."', `filekey` = '".md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext)))."', `filedata` = '".serialize($filedata)."', `filesize` = ".intval($size).",`filedate` = ".time().", `thumb` = 0, `preview` = 0, `original` = 0, `embed` = 0, `lastchange` = ".time();
						if ($e_res['num']>0):
							// use update statement
							$sql.= " WHERE `mid` = ".intval($e_res['set'][0]['mid']);
						endif;
						doSQL($sql);
						return array('success' => true);
					} else {
						return array('success' => false, 'params' => serialize($_REQUEST), 'state' => 'no ftp, no direct copy');
					}
				endif;
				return array('success' => false, 'params' => serialize($_REQUEST));
			/* endif; */
		else:
			return array('success' => false, 'params' => serialize($_REQUEST), 'state' => 'tmp saving did not work');
		endif;
	    }
	}

// list of valid extensions, ex. array("jpeg", "xml", "bmp")
$allowedExtensions = explode(";", $_SESSION['wspvars']['upload']['extensions']);
// max file size in bytes
// $sizeLimit = 10 * 1024 * 1024;
$sizeLimit = intval(ini_get('post_max_size') ?? 1) * 1024 * 1024;
// init uploader
$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
// handleUpload to given uid ...
$result = $uploader->handleUpload($_REQUEST['targetfolder'], $_SESSION['wspvars']['overwriteuploads']);
// to pass data through iframe you will need to encode all html tags
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
