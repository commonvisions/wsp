<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 */

session_start();
// define error reporting
error_reporting(E_ALL);
ini_set("display_errors", 1);
// define timezone
date_default_timezone_set(@date_default_timezone_get());
// define root dir
// - strato problem
$buildsysfile = str_replace("/", "/", str_replace("/", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SERVER['SCRIPT_NAME']));
if ($buildsysfile!=$_SERVER['SCRIPT_FILENAME']) { define('DOCUMENT_ROOT', str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".str_replace($_SERVER['SCRIPT_NAME'], "", str_replace($_SERVER['DOCUMENT_ROOT'], "", $_SERVER['SCRIPT_FILENAME'])))); } else { define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']); }
// get wsp install directory
$_SESSION['tmpwspbasedir'] = basename(__DIR__);
$_SESSION['installserver'] = 'update.wsp-server.info';
$_SESSION['locallanguage'] = array();
$_SESSION['msg'] = array();
$_SESSION['wspvars']['locallang'] = 'de';
// get language information
if (isset($_REQUEST['setlang'])) { $_SESSION['wspvars']['locallang'] = trim($_REQUEST['setlang']); }
if (isset($_POST['wsplang'])) { $_SESSION['wspvars']['locallang'] = trim($_REQUEST['wsplang']); }
// setup empty data array to HAVE some data beeing displayed in fields 
// even there is no saved or posted data 
$data = array('DB_HOST' => 'localhost', 'DB_NAME' => '', 'DB_USER' => '', 'DB_PASS' => '', 'DB_PREFIX' => '', 'FTP_HOST' => 'localhost',  'FTP_BASE' => '/', 'FTP_USER' => '', 'FTP_PASS' => '', 'FTP_PORT' => 21, 'FTP_SSL' => false, 'SMTP_HOST' => '', 'SMTP_USER' => '', 'SMTP_PASS' => '', 'SMTP_PORT' => '', 'SMTP_SSL' => true, 'ROOTPHRASE' => '', 'WSP_LANG' => '', 'WSP_DIR' => $_SESSION['tmpwspbasedir'], 'ADMINUSER' => '', 'ADMINPASS' => '', 'ADMINMAIL' => '', 'REPEATMAIL' => '');
// get the functions
if (is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/funcs.inc.php')) {
    include_once(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/funcs.inc.php');
    $data['ADMINPASS'] = generate_password();
} 
else {
    die ('<pre>required functions not found. install progress stopped.</pre>');
}
// get ALL language files
if (is_dir(str_replace("//", "/", DOCUMENT_ROOT."/".$_SESSION['tmpwspbasedir']."/data/lang/"))) {
	$d = @dir(str_replace("//", "/", DOCUMENT_ROOT."/".$_SESSION['tmpwspbasedir']."/data/lang/"));
	while (false !== ($entry = $d->read())) {
		if ((substr($entry, 0, 1)!='.') && (is_file(str_replace("//", "/", DOCUMENT_ROOT."/".$_SESSION['tmpwspbasedir']."/data/lang/".$entry))) && substr($entry, -3)=='php') {
			$_SESSION['locallanguage'][] = $entry;
		}
	}
	$d->close();
}
// set language and get required language file
if (is_array($_SESSION['locallanguage'])) {
	foreach ($_SESSION['locallanguage'] AS $langkey => $langvalue) {
		include(str_replace("//", "/", DOCUMENT_ROOT."/".$_SESSION['tmpwspbasedir']."/data/lang/".$langvalue));
        $_SESSION['locallanguage'][array_key_first($lang ?? []) ?? 'de'] = $langvalue;
        unset($lang);
    }
    foreach ($_SESSION['locallanguage'] AS $langkey => $langvalue) {
        if ($langkey==intval($langkey)) {
			unset($_SESSION['locallanguage'][intval($langkey)]);
		}
	}
    include(str_replace("//", "/", DOCUMENT_ROOT."/".$_SESSION['tmpwspbasedir']."/data/lang/".$_SESSION['locallanguage'][$_SESSION['wspvars']['locallang']]));
}
// check for install requirements
$install = false;
// check for uploaded zip
if (is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/wsp-install.zip')) { $uselocal = true; }
// create update key
$updkey = cryptRootPhrase($_SERVER['SERVER_NAME'],'e',$_SERVER['SERVER_ADDR']);
if (isset($_POST['doinstall']) && trim($_POST['doinstall'])==$updkey) {
    $install = true;
} else {
    // updating from wsp < 7
    if (is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/dbaccess.inc.php') && is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/ftpaccess.inc.php')) {
        include_once(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/dbaccess.inc.php');
        if (defined('DB_HOST')) $data['DB_HOST'] = DB_HOST;
        if (defined('DB_NAME')) $data['DB_NAME'] = DB_NAME;
        if (defined('DB_USER')) $data['DB_USER'] = DB_USER;
        if (defined('DB_PASS')) $data['DB_PASS'] = DB_PASS;
        include_once(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/ftpaccess.inc.php');
        if (isset($wspvars['ftphost'])) $data['FTP_HOST'] = $wspvars['ftphost'];
        if (isset($wspvars['ftpuser'])) $data['FTP_USER'] = $wspvars['ftpuser'];
        if (isset($wspvars['ftpbasedir'])) $data['FTP_BASE'] = cleanPath('/'.$wspvars['ftpbasedir'].'/');
        if (isset($wspvars['ftppass'])) $data['FTP_PASS'] = $wspvars['ftppass'];
        $_SESSION['msg'][] = returnIntLang('install update from wsp < 7');
        $install = true;
    }
    // reinstall using wspconf.inc.php
    else if (is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/wspconf.inc.php')) {
        include_once(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/wspconf.inc.php');
        if (defined('DB_HOST')) $data['DB_HOST'] = DB_HOST;
        if (defined('DB_NAME')) $data['DB_NAME'] = DB_NAME;
        if (defined('DB_USER')) $data['DB_USER'] = DB_USER;
        if (defined('DB_PASS')) $data['DB_PASS'] = DB_PASS;
        if (defined('DB_PREFIX')) $data['DB_PREFIX'] = DB_PREFIX;
        if (defined('FTP_HOST')) $data['FTP_HOST'] = FTP_HOST;
        if (defined('FTP_BASE')) $data['FTP_BASE'] = cleanPath('/'.FTP_BASE.'/');
        if (defined('FTP_USER')) $data['FTP_USER'] = FTP_USER;
        if (defined('FTP_PASS')) $data['FTP_PASS'] = FTP_PASS;
        if (defined('FTP_PORT')) $data['FTP_PORT'] = FTP_PORT;
        if (defined('FTP_SSL')) $data['FTP_SSL'] = FTP_SSL;
        if (defined('SMTP_HOST')) $data['SMTP_HOST'] = SMTP_HOST;
        if (defined('SMTP_USER')) $data['SMTP_USER'] = SMTP_USER;
        if (defined('SMTP_PASS')) $data['SMTP_PASS'] = SMTP_PASS;
        if (defined('SMTP_PORT')) $data['SMTP_PORT'] = SMTP_PORT;
        if (defined('SMTP_SSL')) $data['SMTP_SSL'] = SMTP_SSL;
        if (defined('BASEMAIL')) $data['ADMINMAIL'] = BASEMAIL;
        if (defined('BASEMAIL')) $data['REPEATMAIL'] = BASEMAIL;
        if (defined('BASEMAIL')) $data['ADMINUSER'] = trim(explode('@',BASEMAIL)[0]);
        $_SESSION['msg'][] = returnIntLang('install using wsp configuration file');
        $install = true;
    }
    // OR blank installation
    else {
        $install = true;
    }
}

if ($install!==true) {
    die ('<pre>no installation required</pre>');
} 
else {
    // check for REALLY required values
    $errorgroup = array(
        1 => array('ADMINUSER', 'ADMINPASS', 'ADMINMAIL','REPEATMAIL'),
        2 => array('DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'),
        3 => array('FTP_HOST', 'FTP_BASE', 'FTP_USER', 'FTP_PASS', 'FTP_PORT', 'FTP_SSL'),
    );
    $errorclass = range(1,6);
    foreach($errorclass AS $ek => $ev) {
        $errorclass[$ek] = 0;
    }
    // remove FTP from errorgroups if ftp_ignore is checked
    if (isset($_POST['ftp_ignore'])) { unset($errorgroup[3]); }
    // run the errorgroups to detect missing params
    foreach ($errorgroup AS $egk => $egv) { 
        foreach ($egv AS $ck => $cv) { 
            if (isset($_POST[strtolower($cv)])) {
                if (trim($_POST[strtolower($cv)])=='') {
                    $data[$cv] = '';
                    $errorclass[$egk] = $errorclass[$egk]+(count($errorgroup)*count($egv));
                } else {
                    $data[$cv] = filter_var(trim($_POST[strtolower($cv)]), FILTER_SANITIZE_STRING);
                    $errorclass[$egk]--;
                }
            }
        }
    }
    // recheck FTP_BASE for leading and closing slash
    $data['FTP_BASE'] = cleanPath('/'.$data['FTP_BASE'].'/');
    // check some other POST-values
    $datagroup = array('SMTP_HOST','SMTP_NAME','SMTP_USER','SMTP_PASS','SMTP_PORT','SMTP_SSL');
    foreach ($datagroup AS $dk => $dv) { 
        if (isset($_POST[strtolower($dv)])) { 
            $data[$dv] = filter_var(trim($_POST[strtolower($dv)]), FILTER_SANITIZE_STRING); 
        }
    }
    // DO INSTALL
    if (array_sum($errorclass)<0) {
        // usnet older msgs to do output for install messages only 
        $_SESSION['msg'] = array();
        // run install routine
        $errorclass[4] = -1;
        $errorclass[5] = -1;
        // check ftp-connection - if not ignored
        if (!(isset($_POST['ftp_ignore']))) { 
            $_SESSION['ftp_ssl'] = (($data['FTP_SSL']==1)?true:false);
            $_SESSION['ftp_host'] = $data['FTP_HOST'];
            $_SESSION['ftp_port'] = $data['FTP_PORT'];
            $_SESSION['ftp_user'] = $data['FTP_USER'];
            $_SESSION['ftp_pass'] = $data['FTP_PASS'];
            $_SESSION['ftp_base'] = $data['FTP_BASE'];
            $_SESSION['ftp_pasv'] = false;
            $ftpstat = @doFTP();
            $webstat = false;
        } else {
            $ftpstat = false;
            $webstat = true;
        }
        if ($ftpstat || $webstat) {
            if ($ftpstat) {
                // check the login directory with ftp credentials
                $ftpldlist = @ftp_nlist($ftpstat,'/');
                if (is_array($ftpldlist) && count($ftpldlist)>0) {
                    // we got a ftp structure
                    $install = false;
                    foreach ($ftpldlist AS $flk => $flv) {
                        if (cleanPath('/'.$flv.'/')==cleanPath('/'.$data['FTP_BASE'].'/')) {
                            // the given directory was found
                            // try to change to this folder
                            if (ftp_chdir($ftpstat, cleanPath('/'.$data['FTP_BASE'].'/'))) {
                                // get the basedir structure 
                                $ftpbdlist = @ftp_nlist($ftpstat,'*');
                                foreach ($ftpbdlist AS $fbk => $fbv) {
                                    // this directory should have a folder with wsp_basename
                                    if (cleanPath('/'.$fbv.'/')==cleanPath('/'.$_SESSION['tmpwspbasedir'].'/')) {
                                        // we got a match
                                        // ftp was successful
                                        // we use it a bit later, so it will stay open
                                        $_SESSION['msg'][] = returnIntLang('install could connect with ftp data');
                                        $install = true;
                                    }
                                }
                            } 
                            else {
                                $_SESSION['msg'][] = returnIntLang('install could not change to correct ftp base folder');
                                $install = false;
                                @ftp_close($ftpstat);
                            }
                        }
                    }
                }
                else {
                    $_SESSION['msg'][] = returnIntLang('install some error occured while reading ftp login directory');
                    $install = false;
                    @ftp_close($ftpstat);
                }
            }
            else {
                // check the login directory with webserver credentials
                $tmpfile = fopen(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/tmp/install.log', "a");
                fwrite($tmpfile, "install ".DOCUMENT_ROOT);
                fclose($tmpfile);
                $install = (is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/tmp/install.log')) ? true : false ;
            }
        } 
        else {
            $_SESSION['msg'][] = returnIntLang('install could not connect to ftp');
            $install = false;
        }
        // check db-connection (only usefull if ftp works)
        if ($install===true) {
            $dbcon = @mysqli_connect($data['DB_HOST'], $data['DB_USER'], $data['DB_PASS'], $data['DB_NAME']);
            if (!($dbcon)) {
                $_SESSION['msg'][] = returnIntLang('install could not connect to database');
                $install = false;
            } 
            // else database connection established
            // we use it a bit later, so it will stay open 
        }
        // check smtp-connection » this is not required for install
        /*
        if (trim($data['SMTP_HOST'])!='' && intval($data['SMTP_PORT'])>0 && trim($data['SMTP_USER'])!='' && trim($data['SMTP_PASS'])!='') {
            
            $_SESSION['smtp_host'] = $data['SMTP_HOST'];
            $_SESSION['smtp_port'] = intval($data['SMTP_PORT']);
            $_SESSION['smtp_user'] = $data['SMTP_USER'];
            $_SESSION['smtp_pass'] = $data['SMTP_PASS'];
            $_SESSION['smtp_ssl'] = (($data['SMTP_SSL']==1)?true:false);
            
            $f = fsockopen($data['SMTP_HOST'], intval($data['SMTP_PORT'])) ;
            if ($f !== false) {
                $res = fread($f, 1024) ;
                if (strlen($res) > 0 && strpos($res, '220') === 0) {
                    $_SESSION['msg'][] = returnIntLang('install could connect to smtp-server');
                }
                else {
                    $_SESSION['msg'][] = returnIntLang('install could not connect to smtp-server');
                }
            }
            fclose($f) ;
        }
        */
        // do the REAL installation finally if install is STILL true
        if ($install===true) {
            // setup output and feedback vars
            $error = false;
            $didinstall = false;
            if (isset($_POST['serversystem']) && trim($_POST['serversystem'])=='git') {
                // wsp updates using git
                // git brings database.xml to _wsp_/tmp/
                $error = false;
            } else {
                // setup key
                // setup final location and name of installer zip 
                $sysfile = cleanPath(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/tmp/wspsystem.zip');
                // create temporary directory (it will be used in wsp later, too)
                createDirFTP('/'.$_SESSION['tmpwspbasedir'].'/tmp/');
                // getting installer file from server, upload or local file
                // getting zip from server
                if (isset($_POST['serversystem']) && trim($_POST['serversystem'])!='') {
                    // get update file from update server
                    $updsystem = trim($_POST['serversystem']);
                    if (isCurl()) {
                        $defaults = array( 
                            CURLOPT_URL => trim($_SESSION['installserver'].'/versions/system/'),
                            CURLOPT_HEADER => 0, 
                            CURLOPT_POST => 0,
                            CURLOPT_POSTFIELDS => array(
                                'file' => $updsystem,
                                'key' => $updkey,
                            ),
                            CURLOPT_RETURNTRANSFER => TRUE, 
                            CURLOPT_TIMEOUT => 4 
                        );
                        $ch = curl_init();
                        curl_setopt_array($ch, $defaults);    
                        if( ! $getversion = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                        curl_close($ch);
                    } 
                    else {
                        $fh = fopen('https://'.$_SESSION['installserver']."/versions/system/?file=".$updsystem."&key=".$updkey, 'r');
                        if (intval($fh)!=0):
                        while (!feof($fh)) {
                            $getversion .= fgets($fh);
                        }
                        endif;
                        fclose($fh);
                    }
                    $tmpsys = fopen($sysfile, "w");
                    if (fwrite($tmpsys, $getversion)) {
                        $error = false;
                    } else {
                        $_SESSION['msg'][] = returnIntLang('install could not write server based update file');
                        $error = true;
                    }
                } 
                // else if upload
                else if (isset($_FILES['uploadsystem'])) {
                    if ($_FILES['uploadsystem']['error']==0) {
                        if ($_FILES['uploadsystem']['type']=='application/zip') {
                            $didcopy = @ftp_put($ftpstat, $data['FTP_BASE'].'/'.trim($_SESSION['tmpwspbasedir']).'/tmp/wspsystem.zip', $_FILES['uploadsystem']['tmp_name'], FTP_BINARY);
                            if (!$didcopy) {
                                $_SESSION['msg'][] = returnIntLang('install got no usable installer file');
                                $error = true;
                            }
                        } else {
                            $_SESSION['msg'][] = returnIntLang('install uploaded file was no zip');
                            $error = true;
                        }
                    } else {
                        $_SESSION['msg'][] = returnIntLang('install could not upload installer file');
                        $error = true;
                    }
                }
                // else if is local
                // check for existing zip-file
                else if (is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/wsp-install.zip')) {
                    $didcopy = @ftp_put($ftpstat, $data['FTP_BASE'].'/'.trim($_SESSION['tmpwspbasedir']).'/tmp/wspsystem.zip', DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/wsp-install.zip', FTP_BINARY);
                    if (!$didcopy) {
                        $_SESSION['msg'][] = returnIntLang('install got no usable installer file');
                        $error = true;
                    }
                }
                else {
                    $_SESSION['msg'][] = returnIntLang('install got no usable installer file');
                    $error = true;
                }
                // unzip database.xml (if it is in zip)
                if (is_file($sysfile) && $error===false) {
                    $zip = new ZipArchive;
                    if ($zip->open($sysfile)===true) {        
                        // run archive for files
                        for($i = 0; $i < $zip->numFiles; $i++) {
                            $filename = $zip->getNameIndex($i);
                            $fileinfo = pathinfo($filename);
                            if ($filename=='database.xml') {
                                copy("zip://".$sysfile."#".$filename, cleanPath(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/tmp/database.xml'));
                            }
                        }
                    }
                } else {
                    $_SESSION['msg'][] = returnIntLang('install could not access installer file');
                    $error = true;
                }
            }
            // prepare tablename prefix
            if (isset($data['DB_PREFIX']) && trim($data['DB_PREFIX'])!='') {
                $prefix = trim($data['DB_PREFIX'])."_";
            } else {
                $prefix = '';
            }
            // updating or installing database
            if (is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/tmp/database.xml') && $error===false) {
                $dbfile = file(cleanPath(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/tmp/database.xml'));
                if (isset($dbfile) && is_array($dbfile)) {
                    foreach ($dbfile AS $fk => $fv) { $dbfile[$fk] = trim($fv); }
                    $dbxml = implode("", $dbfile);
                } else {
                    // fallback » fake xml data with no db updates required
                    $dbxml = '<database></database>';
                }
                // get the structure of tables that should be installed
                $updtable = createDBArrFromDBXML($dbxml);
                // setup db con to use with functions
                $_SESSION['wspvars']['db'] = $dbcon;
                // setup counter for databases to be installed
                $dbinst = 0;
                // run all update tables
                foreach ($updtable AS $uk => $uv) {
                    $dbinstall = installUpdateDBTable($_POST['db_name'], $prefix.$uk, $uv);
                    if ($dbinstall['status']===true) {
                        $dbinst++;
                    } else if ($dbinstall['return']!='') {
                        $_SESSION['msg'][] = $dbinstall['return'];
                        $error = true;
                    }
                }
                if ($dbinst==count($updtable)) {
                    $_SESSION['msg'][] = returnIntLang('install installed or updated all tables');
                    unlink(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/tmp/database.xml');
                }
            } else {
                // init the db connection to create user 
                $_SESSION['wspvars']['db'] = $dbcon;
            }
            // get the user table
            if ($error===false) {
                $resttable = "SELECT `rid` FROM `".$_POST['db_name']."`.`".trim($prefix.'restrictions')."` WHERE `user` = '".escapeSQL(trim($_POST['adminuser']))."'";
                $restcheck = doSQL($resttable);
                // create admin user
                if ($restcheck['res']===true && $restcheck['num']==0 && $error===false) {
                    $admin_sql = "INSERT INTO `".$_POST['db_name']."`.`".trim($prefix.'restrictions')."` SET `usertype` = 1, `user` = '".escapeSQL(trim($_POST['adminuser']))."', `pass` = '".md5(trim($_POST['adminpass']))."', `realname` = '".escapeSQL(trim($_POST['adminuser']))."', `realmail` = '".escapeSQL(trim($_POST['adminmail']))."'";
                    $admin_res = doSQL($admin_sql);
                    if ($admin_res['aff']==1) {
                        $_SESSION['msg'][] = returnIntLang('install created admin acccount');
                    }
                } else {
                    if ($restcheck['res']===false) {
                        $_SESSION['msg'][] = returnIntLang('install table restrictions is missing');
                        $error = true;
                    } else {
                        if ($_POST['overwriteconf']==0) {
                            $_SESSION['msg'][] = returnIntLang('install could not create admin user');
                        } else {
                            $_SESSION['msg'][] = returnIntLang('install found existing admin user and did not replace it');
                        }
                    }
                }
            }
            // unpack zip file from temporary directory
            // only happens if sysfile is avaiable -> it's not while using git
            if ($error===false && isset($sysfile)) {
                $zip = new ZipArchive;
                if ($zip->open($sysfile)===true) {        
                    // run archive for files
                    for($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        $fileinfo = pathinfo($filename);
                        // dont use hidden files
                        if (substr($fileinfo['basename'],0,1)=='.') {
                            // entry will be ignored
                        } 
                        else if ($fileinfo['basename']=='database.xml') {
                            // database.xml entry will be ignored in generic unzipping
                        } 
                        // dont use double underscore stuff
                        else if (substr($fileinfo['dirname'],0,2)=='__' || substr($fileinfo['basename'],0,2)=='__') {
                            // entry will be ignored
                        }
                        // rename _wsp_ folder to $_SESSION['tmpwspbasedir']
                        else if (substr($fileinfo['dirname'],0,5)=='_wsp_') {
                            if ($fileinfo['basename']==$fileinfo['filename'] && !(isset($fileinfo['extension']))) {
                                // it's a directory and the entry will be ignored
                            } else {
                                if (!(is_dir(cleanPath(DOCUMENT_ROOT.'/'.str_replace('_wsp_', $_SESSION['tmpwspbasedir'], $fileinfo['dirname']))))) {
                                    $createdir = createDirFTP('/'.str_replace('_wsp_', $_SESSION['tmpwspbasedir'], $fileinfo['dirname']).'/');
                                }
                                @copy("zip://".$sysfile."#".$filename, cleanPath(DOCUMENT_ROOT.'/'.str_replace('_wsp_', $_SESSION['tmpwspbasedir'], $fileinfo['dirname']).'/'.$fileinfo['basename']));
                            }
                        }
                        else {
                            if ($fileinfo['basename']==$fileinfo['filename'] && !(isset($fileinfo['extension']))) {
                                // it's a directory and the entry will be ignored
                            } else {
                                if (!(is_dir(DOCUMENT_ROOT.'/'.$fileinfo['dirname']))) {
                                    createDirFTP('/'.$fileinfo['dirname'].'/');
                                }
                                @copy("zip://".$sysfile."#".$filename, cleanPath(DOCUMENT_ROOT.'/'.$fileinfo['dirname'].'/'.$fileinfo['basename']));
                            }
                        }
                    }      
                    $zip->close();
                    $_SESSION['msg'][] = returnIntLang('install copied all install files');
                    // finaly remove file from tmp
                    unlink($sysfile);
                } else {
                    $_SESSION['msg'][] = returnIntLang('install could not open zip file');
                    $error = true;
                }
            }
            
            // create conf file if isset overwriteconf or no conf file exists
            if ($error===false) {
                if (!(is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/wspconf.inc.php')) || $_POST['overwriteconf']==1) {
                    $confdata = "<?php\n";
                    $confdata.= "/**\n";
                    $confdata.= " * WSP conf file (system written)\n";
                    $confdata.= " * @since 7.0\n";
                    $confdata.= " * @created ".date('Y-m-d H:i:s')."\n";
                    $confdata.= " * @version 7.".time()."\n";
                    $confdata.= " */\n\n";
                    $confdata.= "define('DB_HOST','".trim($data['DB_HOST'])."');\n";
                    $confdata.= "define('DB_NAME','".trim($data['DB_NAME'])."');\n";
                    $confdata.= "define('DB_USER','".trim($data['DB_USER'])."');\n";
                    $confdata.= "define('DB_PASS','".trim($data['DB_PASS'])."');\n";
                    $confdata.= "define('DB_PREFIX','".trim($data['DB_PREFIX'])."'); // optional\n\n";
                    if ($ftpstat) {
                        $confdata.= "define('FTP_HOST','".trim($data['FTP_HOST'])."');\n";
                        $confdata.= "define('FTP_BASE','".trim($data['FTP_BASE'])."');\n";
                        $confdata.= "define('FTP_USER','".$_POST['ftp_user']."');\n";
                        $confdata.= "define('FTP_PASS','".$_POST['ftp_pass']."');\n";
                        $confdata.= "define('FTP_PORT','".$_POST['ftp_port']."'); // optional\n";
                        $confdata.= "define('FTP_SSL',".((intval($_POST['ftp_ssl'])==1)?'true':'false')."); // optional\n\n";
                    } else {
                        $confdata.= "define('FTP_IGNORE',true);\n\n";
                    }
                    if (trim($data['SMTP_HOST'])!='' && intval($data['SMTP_PORT'])>0 && trim($data['SMTP_USER'])!='' && trim($data['SMTP_PASS'])!='') {
                        $confdata.= "define('SMTP_HOST','".trim($data['SMTP_HOST'])."');\n";
                        $confdata.= "define('SMTP_USER','".trim($data['SMTP_USER'])."');\n";
                        $confdata.= "define('SMTP_PASS','".trim($data['SMTP_PASS'])."'); \n";
                        $confdata.= "define('SMTP_PORT','".intval($data['SMTP_PORT'])."'); // optional\n";
                        $confdata.= "define('SMTP_SSL',".((intval($data['SMTP_SSL'])==1)?'true':'false')."); // optional\n\n";
                    }
                    $confdata.= "define('ROOTPHRASE','".cryptRootPhrase($data['ADMINPASS'],'e',$data['ADMINPASS'])."');\n\n";
                    $confdata.= "define('WSP_LANG','".trim($_POST['wsplang'])."');\n";
                    $confdata.= "define('WSP_DIR','".trim($_SESSION['tmpwspbasedir'])."');\n";
                    $confdata.= "define('WSP_SPACE',0); // optional\n\n";
                    $confdata.= "define('WSP_UPDKEY','".$updkey."' );\n";
                    $confdata.= "define('WSP_UPDSRV','".$_SESSION['installserver']."'); // update-server location\n";
                    $confdata.= "\n";
                    $confdata.= "define('BASEMAIL','".trim($_POST['adminmail'])."');\n\n";
                    $confdata.= "?>";
                    // create file content
                    $conffile = cleanPath(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/tmp/wspconf.inc.php');
                    $fh = fopen($conffile, "w+");
                    fwrite($fh, $confdata);
                    fclose($fh);
                    // copy file to structure
                    if ($ftpstat) {
                        $didcopy = @ftp_put($ftpstat, $data['FTP_BASE'].'/'.trim($_SESSION['tmpwspbasedir']).'/data/include/wspconf.inc.php', $conffile, FTP_BINARY);
                    } else {
                        $didcopy = rename(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/tmp/wspconf.inc.php', DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/wspconf.inc.php');
                    }
                    @unlink($conffile);
                    if ($didcopy) {
                        $_SESSION['msg'][] = returnIntLang('install configuration file written');
                        $error = false;
                    } else {
                        $_SESSION['msg'][] = returnIntLang('install could not write configuration file');
                        $error = true;
                    }
                }
            }
            
            if (!(isset($_POST['ftp_ignore']))) { 
                // closing the ftp connection opened in ftp testing    
                @ftp_close($ftpstat); 
                // remove zip file from tmp
                @unlink($sysfile);
            }
            // closing the database connection opened in db testing
            @mysqli_close($dbcon);
            
            if ($error===false) {
                $_SESSION['msg'][] = returnIntLang('install installation succesful');
                $didinstall = true;
            }
        }
        
        // ? unset all vars used in installation process
        
    }
    
?>    

<!doctype html>
<html lang="de">
	<head>
		<title>WSP Installation Wizard</title>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
		<!-- VENDOR CSS -->
		<link rel="stylesheet" href="./media/layout/bootstrap.css">
		<link rel="stylesheet" href="./media/layout/font-awesome-5-7-2.css">
        <link rel="stylesheet" href="./media/layout/dropify.css">
        <!-- MAIN CSS -->
		<link rel="stylesheet" href="./media/layout/wsp7.css">
        <!-- JS -->
        <script src="./data/script/jquery/js/jquery-3.3.1.js"></script>
	</head>
	<body>
		<!-- WRAPPER -->
		<div id="wizard-wrapper">
			<!-- MAIN -->
			<div class="main">
                <!-- MAIN CONTENT -->
                <div class="container-fluid">
                    <?php if(isset($didinstall) && $didinstall===true) { ?>
                    <div id="client-wizard" class="wizard">
                        <div class="step-content">
                            <div class="row">
                                <div class="col-md-12">
                                    <h2 class='wizarddesc'><?php echo returnIntLang('install wsp'); ?></h2>
                                    <?php 
                                    
                                    if (isset($_SESSION['msg']) && is_array($_SESSION['msg']) && count($_SESSION['msg'])>0) {
                                        foreach ($_SESSION['msg'] AS $msgk => $msgv) {
                                            echo "<p class='wizarddesc wizardmsg'>".$msgv."</p>";
                                        }
                                    }
                                    
                                    ?>
                                    <p><a href="./login.php" class="btn btn-success"><?php echo returnIntLang('btn goto login', false); ?></a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } else { ?>
                    <div id="client-wizard" class="wizard">
                        <div class="steps-container">
                            <ul class="steps">
                                <li data-step="1" id="step-1" class="active">
                                    <i class="fas fa-clipboard-check"></i> <?php echo returnIntLang('install steps system'); ?>
                                    <span class="chevron"></span>
                                </li>
                                <li data-step="2" id="step-2" class="<?php echo (($errorclass[1]<0)?'success':(($errorclass[1]>0)?'error':'')); ?>" onclick="$('#client-wizard').wizard('selectedItem', { step: 2 });">
                                    <i class="fas fa-user-secret"></i> <?php echo returnIntLang('install steps admin'); ?>
                                    <span class="chevron"></span>
                                </li>
                                <li data-step="3" id="step-3" class="<?php echo (($errorclass[2]<0)?'success':(($errorclass[2]>0)?'error':'')); ?>" <?php if (intval($errorclass[2])<0) { ?>onclick="$('#client-wizard').wizard('selectedItem', { step: 3 });"<?php } ?>>
                                    <i class="fas fa-database"></i> <?php echo returnIntLang('install steps database'); ?>
                                    <span class="chevron"></span>
                                </li>
                                <li data-step="4" id="step-4" class="<?php echo (($errorclass[3]<0)?'success':(($errorclass[3]>0)?'error':'')); ?>" <?php if (intval($errorclass[3])<0) { ?>onclick="$('#client-wizard').wizard('selectedItem', { step: 4 });"<?php } ?>>
                                    <i class="fas fa-server"></i> <?php echo returnIntLang('install steps ftp'); ?>
                                    <span class="chevron"></span>
                                    </li>
                                <li data-step="5" id="step-5" class="<?php echo (($errorclass[4]<0)?'success':(($errorclass[4]>0)?'error':'')); ?>" <?php if (intval($errorclass[4])<0) { ?>onclick="$('#client-wizard').wizard('selectedItem', { step: 5 });"<?php } ?>>
                                    <i class="fas fa-envelope"></i> <?php echo returnIntLang('install steps smtp'); ?>
                                    <span class="chevron"></span>
                                </li>
                                <li data-step="6" id="step-6" class="last <?php echo (($errorclass[5]<0)?'success':(($errorclass[5]>0)?'error':'')); ?>" <?php if (array_sum($errorclass)<0) { ?>onclick="$('#client-wizard').wizard('selectedItem', { step: 6 });"<?php } ?>>
                                    <i class="fas fa-download" <?php if (intval($errorclass[5])==6) { ?>onclick="$('#client-wizard').wizard('selectedItem', { step: 5 });"<?php } ?>></i> <?php echo returnIntLang('install steps install'); ?>
                                </li>
                            </ul>
                        </div>
                        <div class="step-content">
                            <form id="wizardlang" name="wizardlang" method="post" enctype="multipart/form-data">
                                <div class="step-pane active form-section-1" data-step="1">
                                    <h2 class='wizarddesc'><?php echo returnIntLang('install wsp'); ?>
                                        <div style="float: right">
                                            <select name="setlang" class="form-control" onchange="$('#wizardlang').submit();">
                                                <?php 
    
                                                $wsplang = (isset($_SESSION['locallanguage'][0]))?$_SESSION['locallanguage'][0]:'de';
                                                foreach($_SESSION['locallanguage'] AS $llk => $llv) {
                                                    echo "<option value='".$llk."' ";
                                                    if (isset($_SESSION['wspvars']['locallang']) && $_SESSION['wspvars']['locallang']==$llk) {
                                                        echo ' selected="selected" ';
                                                        $wsplang = $llk;
                                                    }
                                                    echo ">".$_SESSION['wspvars']['locallanguages'][$llk]."</option>";
                                                }

                                                ?>
                                            </select>
                                        </div>
                                    </h2>
                                    <?php
                                    
                                    if (isset($_SESSION['msg']) && is_array($_SESSION['msg']) && count($_SESSION['msg'])>0) {
                                        foreach ($_SESSION['msg'] AS $msgk => $msgv) {
                                            echo "<p class='wizarddesc wizardmsg'>".$msgv."</p>";
                                        }
                                    }
                                  
                                    ?>
                                    <p class='wizarddesc'><?php echo returnIntLang('install steps system desc'); ?></p>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="widget widget-metric_6">
                                                <span class="icon-wrapper custom-bg-<?php echo ((floatval(phpversion())<7.1)?'red':'green'); ?>"><i class="fas fa-code"></i></span>
                                                <div class="right">
                                                    <span class="value"><?php echo returnIntLang('install steps system phpmin'); ?></span>
                                                    <span class="title"><?php echo returnIntLang('install steps system phpversion'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="widget widget-metric_6">
                                                <span class="icon-wrapper custom-bg-<?php echo ((intval(mysqli_get_client_version())>=50000)?'green':'red'); ?>"><i class="fas fa-database"></i></span>
                                                <div class="right">
                                                    <span class="value"><?php echo returnIntLang('install steps system mysqlmin'); ?></span>
                                                    <span class="title"><?php echo returnIntLang('install steps system mysqlversion'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="widget widget-metric_6">
                                                <span class="icon-wrapper custom-bg-<?php echo (isset($_COOKIE['PHPSESSID'])?'green':'red'); ?>"><i class="fas fa-cookie"></i></span>
                                                <div class="right">
                                                    <span class="value"><?php echo returnIntLang('install steps system cookies'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="widget widget-metric_6">
                                                <span id="jstest" class="icon-wrapper custom-bg-red"><i class="fas fa-file-code"></i></span>
                                                <div class="right">
                                                    <span class="value"><?php echo returnIntLang('install steps system js'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <?php if (isCurl()) { ?>
                                        <div class="col-md-3">
                                            <div class="widget widget-metric_6">
                                                <span class="icon-wrapper custom-bg-green"><i class="fas fa-download"></i></span>
                                                <div class="right">
                                                    <span class="value"><?php echo returnIntLang('install steps system curl'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php } else { ?>
                                        <div class="col-md-3">
                                            <div class="widget widget-metric_6">
                                                <span class="icon-wrapper custom-bg-red"><i class="fas fa-download"></i></span>
                                                <div class="right">
                                                    <span class="value"><?php echo returnIntLang('install steps system curl'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="widget widget-metric_6">
                                                <span class="icon-wrapper custom-bg-yellow"><i class="fas fa-file-import"></i></span>
                                                <div class="right">
                                                    <span class="value"><?php echo returnIntLang('install steps system fopen'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php } ?>
                                        <div class="col-md-3">
                                            <div class="widget widget-metric_6">
                                                <span class="icon-wrapper custom-bg-<?php echo ((extension_loaded('zip'))?'green':'red'); ?>"><i class="fas fa-file-archive"></i></span>
                                                <div class="right">
                                                    <span class="value"><?php echo returnIntLang('install steps system unzip'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <form id="clientwizard" name="client-wizard" method="post" enctype="multipart/form-data">
                                <div class="step-pane form-section-2" data-step="2">
                                    <h2 class="wizarddesc"><?php echo returnIntLang('install steps admin'); ?></h2>
                                    <p class='wizarddesc'><?php echo returnIntLang('install steps admin desc'); ?></p>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps admin username'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="adminuser" name="adminuser" required class="form-control validate" value="<?php echo $data['ADMINUSER']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install admin username required', false); ?>" /></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps admin userpass'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="adminpass" name="adminpass" required class="form-control validate" value="<?php echo $data['ADMINPASS']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install admin userpass required', false); ?>" /></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps admin usermail'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p><input type="text" id="adminmail" name="adminmail" required class="form-control validate" value="<?php echo $data['ADMINMAIL']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install admin usermail required', false); ?>" /></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p><input type="text" id="repeatmail" name="repeatmail" required class="form-control validata" value="<?php echo $data['REPEATMAIL']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install admin repeatmail required', false); ?>" /></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="step-pane form-section-3" data-step="3">
                                    <h2 class="wizarddesc"><?php echo returnIntLang('install steps database'); ?></h2>
                                    <p class='wizarddesc'><?php echo returnIntLang('install steps database desc'); ?></p>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps database host'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="db_host" name="db_host" required class="form-control validate" value="<?php echo $data['DB_HOST']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install database host required', false); ?>" /></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps database name'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="db_name" name="db_name" required class="form-control validate" value="<?php echo $data['DB_NAME']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install database name required', false); ?>" /></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps database user'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="db_user" name="db_user" required class="form-control validate" value="<?php echo $data['DB_USER']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install database user required', false); ?>" /></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps database pass'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="db_pass" name="db_pass" required class="form-control validate" value="<?php echo $data['DB_PASS']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install database pass required', false); ?>" /></p>
                                        </div>
                                    </div>
                                    <!-- <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps database prefix'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="db_prefix" name="db_prefix" required class="form-control" value="<?php echo $data['DB_PREFIX']; ?>" /></p>
                                        </div>
                                    </div> -->
                                </div>
                                <div class="step-pane form-section-4" data-step="4">
                                    <h2 class="wizarddesc"><?php echo returnIntLang('install steps ftp'); ?></h2>
                                    <p class='wizarddesc'><?php echo returnIntLang('install steps ftp desc'); ?></p>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps ftp ignore'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                        <input type="checkbox" id="ftp_ignore" name="ftp_ignore" value="1" onchange="ignoreFTP(this.checked)" />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps ftp host'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="ftp_host" name="ftp_host" required class="form-control validate" value="<?php echo $data['FTP_HOST']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install ftp host required', false); ?>"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps ftp user'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="ftp_user" name="ftp_user" required class="form-control validate" value="<?php echo $data['FTP_USER']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install ftp user required', false); ?>"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps ftp pass'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="ftp_pass" name="ftp_pass" required class="form-control validate" value="<?php echo $data['FTP_PASS']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install ftp pass required', false); ?>"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps ftp base'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="text" id="ftp_base" name="ftp_base" required class="form-control validate" value="<?php echo $data['FTP_BASE']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install ftp base required', false); ?>"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <p><?php echo returnIntLang('install steps ftp base desc'); ?></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps ftp port'); ?></p>
                                        </div>
                                        <div class="col-md-8">
                                            <p><input type="number" id="ftp_port" name="ftp_port" required class="form-control validate" value="<?php echo $data['FTP_PORT']; ?>" data-parsley-group="validate" data-parsley-required-message="<?php echo returnIntLang('install ftp port required', false); ?>"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps ftp ssl'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="hidden" name="ftp_ssl" value="0" />
                                            <input type="checkbox" id="ftp_ssl" name="ftp_ssl" value="1" <?php echo ((intval($data['FTP_SSL'])!=0)?' checked="checked" ':''); ?> />
                                        </div>
                                    </div>
                                </div>
                                <div class="step-pane form-section-5" data-step="5">
                                    <h2 class="wizarddesc"><?php echo returnIntLang('install steps smtp'); ?></h2>
                                    <p class='wizarddesc'><?php echo returnIntLang('install steps smtp desc'); ?></p>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps smtp ignore'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                        <input type="checkbox" id="smtp_ignore" name="smtp_ignore" value="1" onchange="ignoreSMTP(this.checked)" />&nbsp;
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps smtp host'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p><input type="text" id="smtp_host" name="smtp_host" class="form-control" value="<?php echo $data['SMTP_HOST']; ?>"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps smtp user'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p><input type="text" id="smtp_user" name="smtp_user" class="form-control" value="<?php echo $data['SMTP_USER']; ?>"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps smtp pass'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p><input type="text" id="smtp_pass" name="smtp_pass" class="form-control" value="<?php echo $data['SMTP_PASS']; ?>"></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps smtp port'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <p><input type="number" id="smtp_port" name="smtp_port" class="form-control" value="<?php echo $data['SMTP_PORT']; ?>" /></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><?php echo returnIntLang('install steps smtp ssl'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="hidden" name="smtp_ssl" value="0" />
                                            <input type="checkbox" id="smtp_ssl" name="smtp_ssl" value="1" <?php echo ((intval($data['SMTP_SSL'])!=0)?' checked="checked" ':''); ?> />
                                        </div>
                                    </div>
                                </div>
                                <div class="step-pane form-section" data-step="6">
                                    <h2 class="wizarddesc"><?php echo returnIntLang('install steps install'); ?></h2>
                                    <p class='wizarddesc'><?php echo returnIntLang('install steps do install'); ?></p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><select name="serversystem" id="serversystem" class="form-control" onchange="showInstall('server')">
                                                <option value=""><?php echo returnIntLang('install steps server choose server'); ?></option>
                                                <?php if (is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/.gitignore')) { ?><option value="git"><?php echo returnIntLang('install steps server install git'); ?></option><?php } ?>
                                                <option value="full"><?php echo returnIntLang('install steps server install full'); ?></option>
                                                <!-- <option value="last"><?php echo returnIntLang('install steps server install last'); ?></option> -->
                                                <option value="nightly"><?php echo returnIntLang('install steps server install nightly'); ?></option>
                                            </select></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><input name="uploadsystem" type="file" id="dropify-uploadsystem" data-height="100" data-allowed-file-extensions="zip" /></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <?php if (isset($uselocal) && $uselocal===true) { ?>
                                        <div class="col-md-12"><input type="hidden" name="localsystem" value="0" /><input type="checkbox" id="use-localsystem" name="localsystem" value="1" /> <?php echo returnIntLang('install use local zip'); ?></div>
                                        <?php } ?>
                                        <div class="col-md-12"><input type="hidden" name="overwriteconf" value="<?php if (!(is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/wspconf.inc.php'))) { echo 1; } ?>" /><input type="checkbox" name="overwriteconf" value="1" <?php if (!(is_file(DOCUMENT_ROOT.'/'.$_SESSION['tmpwspbasedir'].'/data/include/wspconf.inc.php'))) { echo ' disabled="disabled" checked="checked" '; } ?> /> <?php echo returnIntLang('install overwrite existing config'); ?></div>
                                    </div>
                                </div>
                                <input type="hidden" name="wsplang" value="<?php echo $_SESSION['wspvars']['locallang']; ?>" />
                                <input type="hidden" name="doinstall" value="<?php echo $updkey; ?>" />
                            </form>
                        </div>
                        <?php 
                        
                        if (floatval(phpversion())<7.1) {}
                        else if (intval(mysqli_get_client_version())<50000) {}
                        else if (!(isset($_COOKIE['PHPSESSID']))) {}
                        else if (!(extension_loaded('zip'))) {}
                        // if none of the errors occurs » show button-area in code but display with js
                        else {
                        
                        ?>
                        <div class="actions" style="display: none;">
                            <button type="button" class="btn btn-default btn-prev"><i class="fa fa-arrow-left"></i> <?php echo returnIntLang('btn back', false); ?></button>
                            <button type="button" class="btn btn-primary btn-next"><?php echo returnIntLang('btn next', false); ?> <i class="fa fa-arrow-right"></i></button>
                        </div>
                        <?php
                        
                        }
                        
                        ?>
                    </div>
                    <?php } ?>
                </div>
				<!-- END MAIN CONTENT -->
			</div>
			<!-- END MAIN -->
			<div class="clearfix"></div>
			<footer>
				<div class="container-fluid">
					<p class="copyright">© 2021 COVI.DE</p>
				</div>
			</footer>
		</div>
		
        <!-- Javascript -->
		<script src="./data/script/bootstrap/bootstrap.min.js"></script>
        <script src="./data/script/dropify.min.js"></script>
		<script src="./data/script/wizard.min.js"></script>
        <script src="./data/script/parsley.min.js"></script>
        <script src="./data/script/wspbase.min.js"></script>
		<script>
            
            function ignoreFTP(val) {
                if (val) {
                    $('#ftp_host').removeClass('validate').attr('required', false).attr('data-parsley-group', false).attr('disabled', 'disabled');
                    $('#ftp_user').removeClass('validate').attr('required', false).attr('data-parsley-group', false).attr('disabled', 'disabled');
                    $('#ftp_pass').removeClass('validate').attr('required', false).attr('data-parsley-group', false).attr('disabled', 'disabled');
                    $('#ftp_base').removeClass('validate').attr('required', false).attr('data-parsley-group', false).attr('disabled', 'disabled');
                    $('#ftp_port').removeClass('validate').attr('required', false).attr('data-parsley-group', false).attr('disabled', 'disabled');
                    $('#ftp_ssl').attr('disabled', 'disabled');
                } else {
                    $('#ftp_host').addClass('validate').attr('required', true).attr('data-parsley-group', 'validate').attr('disabled', false);
                    $('#ftp_user').addClass('validate').attr('required', true).attr('data-parsley-group', 'validate').attr('disabled', false);
                    $('#ftp_pass').addClass('validate').attr('required', true).attr('data-parsley-group', 'validate').attr('disabled', false);
                    $('#ftp_base').addClass('validate').attr('required', true).attr('data-parsley-group', 'validate').attr('disabled', false);
                    $('#ftp_port').addClass('validate').attr('required', true).attr('data-parsley-group', 'validate').attr('disabled', false);
                    $('#ftp_ssl').attr('disabled', false);
                }
            }

            function ignoreSMTP(val) {
                if (val) {
                    $('#smtp_host').removeClass('validate').attr('required', false).attr('data-parsley-group', false).attr('disabled', 'disabled');
                    $('#smtp_user').removeClass('validate').attr('required', false).attr('data-parsley-group', false).attr('disabled', 'disabled');
                    $('#smtp_pass').removeClass('validate').attr('required', false).attr('data-parsley-group', false).attr('disabled', 'disabled');
                    $('#smtp_port').removeClass('validate').attr('required', false).attr('data-parsley-group', false).attr('disabled', 'disabled');
                    $('#smtp_ssl').attr('disabled', 'disabled');
                } else {
                    $('#smtp_host').addClass('validate').attr('required', true).attr('data-parsley-group', 'validate').attr('disabled', false);
                    $('#smtp_user').addClass('validate').attr('required', true).attr('data-parsley-group', 'validate').attr('disabled', false);
                    $('#smtp_pass').addClass('validate').attr('required', true).attr('data-parsley-group', 'validate').attr('disabled', false);
                    $('#smtp_port').addClass('validate').attr('required', true).attr('data-parsley-group', 'validate').attr('disabled', false);
                    $('#smtp_ssl').attr('disabled', false);
                }
            }

            function showInstall(givenVal) {
                if (givenVal=='upload') {
                    $('.wizard').find('.btn-next').find('i').removeClass('fa-placeholder').removeClass('fa-server').removeClass('fa-download').addClass('fa-upload');
                    $('.wizard').find('.btn-next').removeClass('btn-default').addClass('btn-success').attr('disabled', false);
                } else if (givenVal=='server') {
                    if ($('#serversystem').val()!='') {
                        $('.wizard').find('.btn-next').find('i').removeClass('fa-placeholder').removeClass('fa-server').removeClass('fa-upload').addClass('fa-download');
                        $('.wizard').find('.btn-next').removeClass('btn-default').addClass('btn-success').attr('disabled', false);
                    }
                    else {
                        hideInstall();
                    }
                } else if (givenVal=='local') {
                    $('.wizard').find('.btn-next').find('i').removeClass('fa-placeholder').removeClass('fa-upload').removeClass('fa-download').addClass('fa-server');
                    $('.wizard').find('.btn-next').removeClass('btn-default').addClass('btn-success').attr('disabled', false);
                } else {
                    hideInstall();
                }
            }
            
            function hideInstall() {
                $('#serversystem').val('');
                $('.wizard').find('.btn-next').find('i').removeClass('fa-upload').removeClass('fa-download').addClass('fa-check');
                $('.wizard').find('.btn-next').addClass('btn-default').removeClass('btn-success').attr('disabled', 'disabled');
            }
            
        $(document).ready(function() {

            $('#jstest').removeClass('custom-bg-red').addClass('custom-bg-green');
            $('.wizard .actions').show();
            
            var drFolder = $('#dropify-uploadsystem').dropify({messages: { default: 'Upload ZIP' }});
            drFolder.on('dropify.afterReady', function(event, element) {
                hideInstall();
                showInstall('upload');
            });
            drFolder.on('dropify.afterClear', function(event, element) {
                hideInstall();
            });
            
            $('#use-localsystem').on('change', function(e) {
                if ($('#use-localsystem').prop('checked')) {
                    hideInstall();
                    showInstall('local');
                } else {
                    hideInstall();
                }
            });
            
			var wiZard = $('#client-wizard').wizard();
            var wizLen = $('#client-wizard').find('ul.steps').find('li').length;
            
            wiZard.on('actionclicked.fu.wizard', function(e, data) {
                
                console.info(e);
                console.info(data);
                console.info(wizLen);
                
                //validation
                if ($('.form-section-' + data.step).length && data.direction=='next') {
                    var stopForm = false;
                    $('.form-section-' + data.step + ' .validate').each(function() {
                        var myfield = $(this).parsley();
                        myfield.validate();
                        if (!(myfield.isValid())) {
                            stopForm = true;
                            $('ul.steps li#step-' + data.step).addClass('error');
                        }
                    });
                    if (stopForm) { 
                        return false;
                    } else {
                        $('ul.steps li#step-' + data.step).removeClass('error');
                    }
                }
            }).on('finished.fu.wizard', function() {
                $('#clientwizard').submit();
            }).on('changed.fu.wizard', function(e, data) {
                $btnLast = $('.wizard').find('.btn-prev');
                //last step button
                $btnNext = $('.wizard').find('.btn-next');
                if (data.step === wizLen) {
                    $btnNext.text('<?php echo returnIntLang('btn doinstall', false); ?> ')
                        .append('<i class="fa fa-placeholder"></i>')
                        .removeClass('btn-primary')
                        .addClass('btn-default')
                        .attr('disabled', 'disabled');
                } else {
                    $btnNext.text('<?php echo returnIntLang('btn next', false); ?> ')
                        .append('<i class="fa fa-arrow-right"></i>')
                        .removeClass('btn-default')
                        .removeClass('btn-success')
                        .addClass('btn-primary')
                        .attr('disabled', false);
                }
            });
        });
            
		</script>
	</body>
</html>

<?php }

// EOF