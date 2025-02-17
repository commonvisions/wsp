<?php
/**
 * Upload Media Files
 * @author stefan@covi.de
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
		$uploadTmbDirectory = $uploadOrgDirectory = $uploadPrevDirectory = $uploadTargetFolder;

		// thumbnail directory if image processing 
		if ($uploadBaseTarget=='screen' || $uploadBaseTarget=='images' || $uploadBaseTarget=='download') {
			$uploadTmbDirectory = str_replace("//", "/", str_replace("//", "/", "/".
				str_replace(
					"/media/".$uploadBaseTarget."/", 
					"/media/".$uploadBaseTarget."/thumbs/", 
					$uploadTargetFolder
				)
			));
        }

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
		if ($_SESSION['wspvars']['upload']['preview']) {
			$uploadPrevDirectory = str_replace("//", "/", str_replace("//", "/", "/".str_replace("/media/download/", "/media/download/preview/", $uploadTargetFolder)));
	    }
        
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
        
		$handleFile = md5($filename);
		// tmp save the file
		$fileSave = $this->file->save($uploadTmpDirectory . $handleFile . '.' . $ext);

		$fileData = array(
			'name' => $filename . '.' . $ext,
			'src' => str_replace("//", "/", $uploadTmpDirectory . '/' . $handleFile . '.' . $ext)
		);

		if ($fileSave) {

			$prvData = null;
			$tmbData = null;
			$orgData = null;

			// optional pdf conversion
			if ($_SESSION['wspvars']['upload']['preview'] && $ext=="pdf" && function_exists('exec')) {
				@exec("/usr/bin/gs -q -dNOPAUSE -dBATCH -sDEVICE=jpeg -sOutputFile=".$uploadTmpDirectory.$handleFile.".jpg " . $fileData['src']);
				if (file_exists($uploadTmpDirectory.$handleFile.".jpg")) {
					$prvData = [
						'name' => $filename . '.jpg',
						'src' => str_replace("//", "/", $uploadTmpDirectory . '/' . $handleFile . '.jpg')
					];
				}
			}
			
			// resizing, preview, thumbnail
			if (function_exists('resizeGDimage') && ($ext=="gif" || $ext=="png" || $ext=="jpg" || $ext=="jpeg")) {
				$fileInfo = @getimagesize($fileData['src']);
				$orgScale = $orgCheck = (($fileInfo[0] ?? 0)>0 && ($fileInfo[1] ?? 0)> 0) ? [
					intval($fileInfo[0]), intval($fileInfo[1])
				] : [0,0];
				$defThumb = intval($_REQUEST['thumbsize'] ?? '300');
				$preScale = explode('x', trim($_REQUEST['prescale'] ?? '1600x900'));
				// switch sides on portrait mode
				asort($orgCheck);
				if ($orgCheck===$orgScale) asort($preScale);
				// check for resizing option
				$sizeFactor = 1;
				if (count($orgScale) == 2 && count($preScale) == 2) {
					$widthFactor = ($orgScale[0]>$preScale[0]) ? ($preScale[0] / $orgScale[0]) : 1;
					$heightFactor = ($orgScale[1]>$preScale[1]) ? ($preScale[1] / $orgScale[1]) : 1;
					$sizeFactor = $widthFactor < $heightFactor ? $widthFactor : $heightFactor;
				}
				$thumbFactor = 1;
				if (count($orgScale) == 2) {
					$widthFactor = ($orgScale[0]>$defThumb) ? ($defThumb / $orgScale[0]) : 1;
					$heightFactor = ($orgScale[1]>$defThumb) ? ($defThumb / $orgScale[1]) : 1;
					$thumbFactor = $widthFactor < $heightFactor ? $widthFactor : $heightFactor;
				}
				// do resizing (if factor)
				if ($sizeFactor < 1 && $sizeFactor > 0) {
					$resized = resizeGDimage(
						($uploadTmpDirectory. $handleFile.'.'.$ext), 
						($uploadTmpDirectory.$handleFile.'-org.'.$ext), 
						round($sizeFactor, 4), null, null, true
					);
					if ($resized) {
						$orgData = array(
							'name' => $filename . '.' . $ext,
							'src' => str_replace("//", "/", $uploadTmpDirectory . '/' . $handleFile . '-org.' . $ext)
						);
					}
				}
				if ($thumbFactor < 1 && $thumbFactor > 0) {
					$resized = resizeGDimage(
						($uploadTmpDirectory . $handleFile . '.' . $ext), 
						($uploadTmpDirectory . $handleFile . '-tmb.' . $ext), 
						round($thumbFactor, 4), null, null, true
					);
					if ($resized) {
						$tmbData = array(
							'name' => $filename . '.' . $ext,
							'src' => str_replace("//", "/", $uploadTmpDirectory . '/' . $handleFile . '-tmb.' . $ext)
						);
					}
				}
			} else if (function_exists('resizeGDimage') &&  $ext=="pdf" && $prvData) {
				error_log('resizeGDimage called for pdf thumb ' . $prvData['src'] . ' but not done');
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
				
				if ($prvData) {
					if (@ftp_put($ftp, $uploadFtpPrevPath, $prvData['src'], FTP_BINARY)) {
						error_log('prvFile copied by ftp to ' . $uploadFtpPrevPath);
						/*
						$createdfile[] = [
							'mediatype' => 'download',
							'mediafolder' => 'download',
							'filefolder' => trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))),
							'filename' => trim($filename.'.jpg'),
							'filetype' => 'jpg',
							'filekey' => md5(str_replace("//", "/", 
								trim($_REQUEST['targetfolder'])."/".trim($filename.'.jpg')
							)),
							'filedata' => serialize([]),
							'filesize' => null,
							'filedate' => time(),
							'thumb' => !empty($tmbData),
							'preview' => !empty($prvData), 
							'original' => !empty($orgData),
							'embed' => 0,
							'lastchange' => time(), 
						];
						*/
					}
				}
				if ($tmbData) {
					if (@ftp_put($ftp, $uploadFtpTmbPath, $tmbData['src'], FTP_BINARY)) {
						error_log('prvFile copied by ftp to ' . $uploadFtpTmbPath);
					}
				}
				if ($orgData) {
					if (@ftp_put($ftp, $uploadFtpOrgPath, $orgData['src'], FTP_BINARY)) {
						error_log('prvFile copied by ftp to ' . $uploadFtpPOrgPath);
					}
				}
				// finally copy the (processed) upload to folder
				if (@ftp_put($ftp, $uploadFtpPath, $fileData['src'], FTP_BINARY)) {
					$createdfile[] = [
						'mediatype' => trim($_REQUEST['mediafolder']),
						'mediafolder' => trim($_REQUEST['targetfolder']),
						'filefolder' => trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))),
						'filename' => trim($filename.'.'.$ext),
						'filetype' => trim($ext),
						'filekey' => md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext))),
						'filedata' => serialize([]),
						'filesize' => null,
						'filedate' => time(),
						'thumb' => !empty($tmbData),
						'preview' => !empty($prvData), 
						'original' => !empty($orgData),
						'embed' => 0,
						'lastchange' => time(), 
					];
				} else {
					$return = ['success' => false, 'params' => serialize($_REQUEST)];
				}
				ftp_close($ftp);

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

				if ($prvData) {
					if (copy($prvData['src'], $uploadPrevPath)) {
						error_log('prvFile copied to ' . $uploadPrevPath);
					}
				}
				if ($tmbData) {
					if (copy($tmbData['src'], $uploadTmbPath)) {
						error_log('tmbFile copied to ' . $uploadTmbPath);
					}
				}
				if ($orgData) {
					if (copy($orgData['src'], $uploadOrgPath)) {
						error_log('orgFile copied to ' . $uploadOrgPath);
					}
				}
				// finally copy the (processed) upload to folder
				if (copy($fileData['src'], $uploadPath)) {
					error_log('File copied to ' . $uploadPath);
					$createdfile[] = [
						'mediatype' => trim($_REQUEST['mediafolder']),
						'mediafolder' => trim($_REQUEST['targetfolder']),
						'filefolder' => trim(str_replace("//","/",str_replace("//","/",str_replace("/media/".$_REQUEST['mediafolder']."/","/",trim($_REQUEST['targetfolder']))))),
						'filename' => trim($filename.'.'.$ext),
						'filetype' => trim($ext),
						'filekey' => md5(str_replace("//", "/", trim($_REQUEST['targetfolder'])."/".trim($filename.'.'.$ext))),
						'filedata' => serialize([]),
						'filesize' => null,
						'filedate' => time(),
						'thumb' => !empty($tmbData),
						'preview' => !empty($prvData), 
						'original' => !empty($orgData),
						'embed' => 0,
						'lastchange' => time(), 
					];
				}
			}
			
			if (count($createdfile)>0) {
				// do the db inserts
				foreach ($createdfile as $file) {
					// check if file was just overwritten
					$e_sql = "SELECT `mid` FROM `wspmedia` WHERE `mediatype` = '".escapeSQL($file['mediatype'])."' AND `mediafolder` = '".escapeSQL($file['mediafolder'])."' AND `filefolder` = '".escapeSQL($file['filefolder'])."' AND `filename` = '".escapeSQL($file['filename'])."' AND `filetype` = '".escapeSQL($file['filetype'])."'";
					$e_res = doSQL($e_sql);
					// updating ?
					$sql = ($e_res['num']>0) ? "UPDATE `wspmedia` " : "INSERT INTO `wspmedia` ";
					// statement
					$sql.= " SET `mediatype` = '".escapeSQL($file['mediatype'])."', `mediafolder` = '".escapeSQL($file['mediafolder'])."', `filefolder` = '".escapeSQL($file['filefolder'])."', `filename` = '".escapeSQL($file['filename'])."', `filetype` = '".escapeSQL($file['filetype'])."', `filekey` = '".escapeSQL($file['filekey'])."', `filedata` = '".escapeSQL(serialize($file['filedata']))."', `filesize` = ".intval($file['filesize']).", `filedate` = " . time() . ", `thumb` = " . intval($file['thumb']) . ", `preview` = " . intval($file['preview']) . ", `original` = " . intval($file['original']) . ", `embed` = " . intval($file['embed']) . ", `lastchange` = ".time();
					// updating ?
					$sql.= ($e_res['num']>0) ? " WHERE `mid` = ".intval($e_res['set'][0]['mid']) : '';
					doSQL($sql);
				}
				return ['success' => true];
			} else {
				// no file could be copied
				return $return ?? ['success' => false];
			}
		} else {
			return array('success' => false, 'params' => serialize($_REQUEST), 'state' => 'tmp saving did not work');
		}

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
