<?php
/**
 * system administration
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2023-01-09
 * 6.11.4
 * fixed error with wspkey
 * 
 * 6.11.5
 * removed unnesseccary "update filesystem" fieldset
 * 
 * 2025-01-19
 * Fixed missing array key warnings
 * Fixed missing files warnings
 */

// switching off errors to prevent update failures
error_reporting(E_ALL ^ E_DEPRECATED ^ E_STRICT);
ini_set('display_errors', 1);

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// first includes ----------------------------
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
// checkParamVar -----------------------------

// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = 'system';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = false;
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// page specific includes --------------------

// define page specific vars -----------------

$c = 0; $freespace = diskfreespace($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']);
while ($freespace>1024):
	$freespace = $freespace/1024;
	$c++;
endwhile;

// define page specific funcs ----------------

if (!(function_exists('checkDatabase'))):
function checkDatabase($own,$dev,$dev_tablename,$own_tablename) {
	$temp = false;
    foreach ($own_tablename as $table) {
		if (in_array($table,$dev_tablename)){
			$x=0;
			$_SESSION['dbchanges'] = '';
            // anzahl der felder der dev-tabelle und der aktuellen tabelle ueberpruefen
			if (sizeof($dev[$table]['field']) < sizeof($own[$table]['field'])):
				$length = sizeof($own[$table]['field']);
			else:
				$length = sizeof($dev[$table]['field']);
			endif;
			
			for ($i=0; $i<$length; $i++){
				$same = true;
				// pruefen ob neue spalte in der dev-tabelle vorhanden ist
				if (isset($dev[$table]['field'][$i]) && !(in_array($dev[$table]['field'][$i], $own[$table]['field'])) && $dev[$table]['field'][$i]!=""):
					$temp[$table][$x]['action']="addnew";
					$temp[$table][$x]['field']=$dev[$table]['field'][$i];
					$x++;
				elseif (isset($own[$table]['field'][$i]) && in_array($own[$table]['field'][$i], $dev[$table]['field']) && $own[$table]['field'][$i]!=""):
					// position im dev array bestimmten
					$key = array_search($own[$table]['field'][$i],$dev[$table]['field']);
					// check for field type
					// some mysql-version don't want to change char fields with less than 4 units to varchar fields
					// so we use a little workaround to check even it is the same fieldlength, then char vs. varchar does
					// not effect
					if ($own[$table]['type'][$i]!=$dev[$table]['type'][$key] && (!("var".$own[$table]['type'][$i]==$dev[$table]['type'][$key]) || ($own[$table]['type'][$i]=="var".$dev[$table]['type'][$key]))){
						
						$_SESSION['dbchanges'] .= $own[$table]['type'][$i]." : ".$dev[$table]['type'][$key]."<br >";
						
						$temp[$table][$x]['field']=$dev[$table]['field'][$key];
						$temp[$table][$x]['type']="type";
						$temp[$table][$x]['action']="changed";
						$same=false;
					}
					// unterschiedliche 'Null'-Behandlung abfangen => NO == ""
					// dieses problem tritt trotz gleicher mysql-client-version auf
					// wenn der mysql-server 5.x ist, wird NO zurueckgeliefert, davor leer
					if ($own[$table]['null'][$i]=="NO"):
						$own[$table]['null'][$i] = "";
					endif;
					if ($dev[$table]['null'][$key]=="NO"):
						$dev[$table]['null'][$key] = "";
					endif;
					// test for NULL changes
					if ($own[$table]['null'][$i]!=$dev[$table]['null'][$key]):
						$temp[$table][$x]['field']=$dev[$table]['field'][$key];
						$temp[$table][$x]['null']="null";
						$temp[$table][$x]['action']="changed";
						$same=false;
					endif;
					// test for key changes
					if ($own[$table]['key'][$i]!=$dev[$table]['key'][$key]):
						$temp[$table][$x]['field']=$dev[$table]['field'][$key];
						$temp[$table][$x]['key']="key";
						$temp[$table][$x]['action']="changed";
						$same=false;
					endif;
					//
					// test for default value changes
					// second if-clause-part depends on problem, that some mysql-version cannot redeclare 
					// an integer field with default value to empty default, they set to '0'  
					// 
					if ($own[$table]['default'][$i]!=$dev[$table]['default'][$key] && !($own[$table]['default'][$i]==0 && strtolower(substr($own[$table]['type'][$i],0,3))=="int")):
						$temp[$table][$x]['field'] = $dev[$table]['field'][$key];
						$temp[$table][$x]['default'] = "default";
						$temp[$table][$x]['action'] = "changed";
						$same = false;
					endif;
					//
					// test for extra changes
					//
					if (isset($dev[$table]['extras'][$i]) && $own[$table]['extras'][$i]!=$dev[$table]['extras'][$i]):
						$temp[$table][$x]['field']=$dev[$table]['field'][$i];
						$temp[$table][$x]['extras']="extras";
						$temp[$table][$x]['action']="changed";
						$same=false;
					endif;
				endif;
				if (!in_array(($own[$table]['field'][$i] ?? null),$dev[$table]['field']) && $i<sizeof($own[$table]['field'])):
					//
					// feld geloescht
					// 
					$temp[$table][$x]['field']=$own[$table]['field'][$i];
					$temp[$table][$x]['action']="delete";
					$x++;
				endif;
				if($same===false){
					$x++;
				}

			}
		}
	}
	return $temp;
} // checkDatabase
endif;

if (!(function_exists('checkDatabaseNew'))):
function checkDatabaseNew($own = array(), $dev = array()) {
	$returnsql = array();
	foreach ($dev AS $dkey => $dvalue):
		if (array_key_exists($dkey, $own)):
			foreach ($dvalue['field'] AS $dfkey => $dfvalue):
				if (!(in_array($dfvalue, $own[$dkey]['field']))):
					// adding field
					$sql = "ALTER TABLE `".$dkey."` ADD `".$dfvalue."` ".$dev[$dkey]['type'][$dfkey];
					if (trim($dev[$dkey]['null'][$dfkey])=='NO'):
						$sql.= " NOT NULL";
					endif;
					if (trim($dev[$dkey]['default'][$dfkey])!=""):
						$sql.= " DEFAULT '".trim($dev[$dkey]['default'][$dfkey])."'"; //'
					endif;
					if ($dev[$dkey]['extras'][$dfkey]!=""):
						$sql.= " ".$dev[$dkey]['extras'][$dfkey];
					endif;
					$returnsql[] = $sql;
					unset($sql);
				else:
					// field exists => look for changes
					// 1. find key of same fieldname in own 
					$oftmpkey = array_keys($own[$dkey]['field'], $dfvalue);
					$ofkey = intval($oftmpkey[0]);
					$dosql = false;
					$sql = "ALTER TABLE `".$dkey."` CHANGE `".$dfvalue."` `".$dfvalue."` ".$dev[$dkey]['type'][$dfkey];
					if ($own[$dkey]['type'][$ofkey]!=$dev[$dkey]['type'][$dfkey]):
						$dosql = true;
					endif;
					if ($own[$dkey]['null'][$ofkey]!=$dev[$dkey]['null'][$dfkey] || $own[$dkey]['default'][$ofkey]!=$dev[$dkey]['default'][$dfkey] || $own[$dkey]['extras'][$ofkey]!=$dev[$dkey]['extras'][$dfkey]):
						$dosql = true;
						if (trim($dev[$dkey]['null'][$dfkey])=='YES'):
							$sql.= " NULL";
						elseif (trim($dev[$dkey]['null'][$dfkey])=='NO'):
							$sql.= " NOT NULL";
						endif;
						if (trim($dev[$dkey]['default'][$dfkey])!=""):
							$sql.= " DEFAULT '".trim($dev[$dkey]['default'][$dfkey])."'"; //'
						endif;
						if (trim($dev[$dkey]['extras'][$dfkey])!=""):
							$sql.= " ".trim($dev[$dkey]['extras'][$dfkey]);
						endif;
					endif;
					if ($dosql):
						$returnsql[] = $sql;
					endif;
					unset($sql);
					if ($own[$dkey]['key'][$ofkey]!=$dev[$dkey]['key'][$dfkey]):
						$sql = "ALTER TABLE `".$dkey."` DROP INDEX `".$dfvalue."`";
						$returnsql[] = $sql;
						if ($dev[$dkey]['key'][$dfkey]=='UNI'):
							$sql = "ALTER TABLE `".$dkey."` ADD UNIQUE (`".$dfvalue."`)";
							$returnsql[] = $sql;	
						endif;
						if ($dev[$dkey]['key'][$dfkey]=='PRI'):
							$sql = "ALTER TABLE `".$dkey."` ADD PRIMARY KEY (`".$dfvalue."`)";
							$returnsql[] = $sql;
						endif;
					endif;
					unset($sql);
				endif;
			endforeach;
		else:
			// build create table statement
			$sql = "CREATE TABLE IF NOT EXISTS `".trim($dkey)."` (";
			$fieldsql = array();
			$extrasql = array();
			$sqlstat = array();
			foreach ($dvalue['field'] AS $dfkey => $dfvalue):
				$fieldsqltmp = "`".$dfvalue."` ".$dvalue['type'][$dfkey];
				if (trim($dvalue['null'][$dfkey])=='NO'):
					$fieldsqltmp.= " NOT NULL";
				endif;
				if (trim($dvalue['extras'][$dfkey])!=''):
					$fieldsqltmp.= " ".trim($dvalue['extras'][$dfkey]);
				endif;
				$fieldsql[] = $fieldsqltmp;
				if ($dvalue['key'][$dfkey]=='PRI'):
					$extrasql[] = "PRIMARY KEY (`".trim($dfvalue)."`)";
				endif;
				if ($dvalue['key'][$dfkey]=='UNI'):
					$extrasql[] = "UNIQUE KEY `".trim($dfvalue)."` (`".trim($dfvalue)."`)";
				endif;
			endforeach;
			$sqlstat[] = implode(", ", $fieldsql);
			$sqlstat[] = implode(", ", $extrasql);
			$sql.= implode(", ", $sqlstat);
			$sql.= ") DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
			$returnsql[] = $sql;
			unset($sql);
		endif;
	endforeach;
	return $returnsql;
} // checkDatabaseNew
endif;

// Kopiert eine Verzeichnisstruktur mit allen Files und Subdirs an den angegebenen Platz
if (!(function_exists('copyTree'))) {
    function copyTree($src, $dest) {

        $ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
        if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }}
        if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }

        if ($ftp) {
            $dh = dir($src);
            while (false !== ($entry = $dh->read())) {
                if (($entry != '.') && ($entry != '..')) {
                    if (is_dir($src."/".$entry)) {
                        @ftp_mkdir($ftp, $dest."/".$entry);
                        copyTree($src."/".$entry, $dest."/".$entry);
                    }
                    elseif (is_file($src."/".$entry)) {
                        @ftp_put($ftp, $dest."/".$entry, $src."/".$entry, FTP_BINARY);
                        @unlink ($src."/".$entry);
                    }
                }
            }
            ftp_close($ftp);
        }
    }
}

// update plugin
function plugUpdate($file, $plugin, $ftp) {
	$plugin_sql = "SELECT * FROM `wspplugins` WHERE `guid` = '".trim($plugin)."'";
	$plugin_res = doSQL($plugin_sql);
	$_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => $plugin_sql);
	$plugin_num = intval($plugin_res['num']);
	
	if ($plugin_num>0):
		require($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/plugins/".trim($plugin_res['set'][0]['pluginfolder'])."/data/include/globalvars.inc.php");
		if (trim($pluginvars[$plugin]['updateuri'])!=""):
			// read update file
            
            if (_isCurl()) {
                $defaults = array( 
                    CURLOPT_URL => trim($pluginvars[$plugin]['updateuri']."/updater.php?file=".$file), 
                    CURLOPT_HEADER => 0, 
                    CURLOPT_RETURNTRANSFER => TRUE, 
                    CURLOPT_TIMEOUT => 4 
                );
                $ch = curl_init();
                curl_setopt_array($ch, $defaults);    
                if( ! $fileupdate = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                curl_close($ch);
            }
    
            // write temporary file
				$fullfile = str_replace("//", "/", str_replace("//", "/", $file));
				$tmppfad = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".basename($fullfile);
				$tmpdat = fopen($tmppfad,'w');
				fwrite($tmpdat, $fileupdate);
				fclose($tmpdat);
				// file transfer action
				$filename = strrchr($fullfile, "/");
				$dirstruct = str_replace($filename, "/", $fullfile);
				$dirstruct = explode("/", $dirstruct);
				if (!(is_dir($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.implode("/", $dirstruct)))):
					ftp_mkdir($ftp, $_SESSION['wspvars']['ftpbasedir']."/".implode("/", $dirstruct));
				endif;
				if (!(is_dir($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.implode("/", $dirstruct)))):
					$_SESSION['wspvars']['errormsg'].= '<p>Kann Verzeichnis "'.implode("/", $dirstruct).'" nicht anlegen. (mkdir)</p>';
				endif;
				if (!(ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir']."/".$fullfile, $tmppfad, FTP_BINARY))):
					$_SESSION['wspvars']['errormsg'].= '<p>Kann erzeugte Datei "'.$fullfile.'" nicht hochladen. (put)</p>';
				endif;
				@unlink($tmppfad);

    endif;
	endif;
	}

// Datenbankstruktur updaten
function DatabaseUpdate($changes, $dbsource = '', $dbstructure = array()) {
    // grep actual database structure from update file
	if (trim($dbsource)!=""):

		// begin getting data from update server
        $tempdev_values = false;
        $tempdev_xmldata = '';
        $defaults = array( 
            CURLOPT_URL => trim($dbsource), 
            CURLOPT_HEADER => 0, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_TIMEOUT => 4 
        );
        $ch = curl_init();
        curl_setopt_array($ch, $defaults);    
        if( ! $tempdev_xmldata = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
        curl_close($ch);
        $tempdev_xml = xml_parser_create();
        xml_parse_into_struct($tempdev_xml, $tempdev_xmldata, $tempdev_values, $tempdev_index);
        // end getting data from update server
        
        $tempdevtable = array();
		
        foreach ($tempdev_values as $tags):
			if ($tags['tag']=='TABLENAME'):
				$tempdev_tablename[]=$tags['value'];
				foreach ($tempdev_values as $tags2):
					if($tags2['tag']=='TABLENAME') $tempdev_tablenametemp=$tags2['value'];
					if(isset($tempdev_tablenametemp) && $tempdev_tablenametemp==$tags['value']):
						if($tags2['tag']=='FIELD' && isset($tags2['value'])) $tempdevtable[$tags['value']]['field'][]=$tags2['value'];
						if($tags2['tag']=='TYPE' && isset($tags2['value'])) $tempdevtable[$tags['value']]['type'][]=$tags2['value'];
						if($tags2['tag']=='NULL' && isset($tags2['value'])) $tempdevtable[$tags['value']]['null'][]=$tags2['value'];
						if($tags2['tag']=='KEY' && isset($tags2['value'])) $tempdevtable[$tags['value']]['key'][]=$tags2['value'];
						if($tags2['tag']=='DEFAULT' && isset($tags2['value'])) $tempdevtable[$tags['value']]['default'][]=$tags2['value'];
						if($tags2['tag']=='EXTRAS' && isset($tags2['value'])) $tempdevtable[$tags['value']]['extras'][]=$tags2['value'];
					endif;
				endforeach;
			endif;
		endforeach;
		
		$uptable = array();
		// switch between wsp-based tables and plugin-tables
		for ($i=0;$i<sizeof($dbstructure);$i++) {
			$sql = "DESCRIBE `".$dbstructure[$i]."`";
			$res = doSQL($sql);
			$tablename[$i] = $dbstructure[$i];
			if ($res['res']) {
				foreach ($res['set'] AS $rsk => $rsv) {
					$uptable[$tablename[$i]]['field'][] = $rsv['Field'];
					$uptable[$tablename[$i]]['type'][] = $rsv['Type'];
					$uptable[$tablename[$i]]['null'][] = $rsv['Null'];
					$uptable[$tablename[$i]]['key'][] = $rsv['Key'];
					$uptable[$tablename[$i]]['default'][] = $rsv['Default'];
					$uptable[$tablename[$i]]['extras'][] = $rsv['Extra'];
                }
            }
		}
		
		// adds changes and deletes tables
		for($i=0;$i<sizeof($tablename);$i++):
			if(isset($changes[$i]) && isset($changes[$i]['tablename']) && isset($tablename[$i]) && $changes[$i]['tablename']==$tablename[$i] && $changes[$i]['tableaction']=="deletetable"):
				doSQL("DROP TABLE `".$changes[$i]['tablename']."`");
			else:
				for($j=0;$j<sizeof($uptable[$tablename[$i]]['field']);$j++):
					if(isset($changes[$i]) && sizeof($changes[$i])>0):
						$isin=false;
						for($k=0;$k<(count($changes[$i])-1);$k++):
							if($changes[$i][$k]['field']==$uptable[$tablename[$i]]['field'][$j]):
								$isin=true;
								if($changes[$i][$k]['action']=="changed" ):
									$sqlstrch = array();
									if ($changes[$i][$k]['null']=="YES"):
										$nullstr="NULL";
									else:
										$nullstr="NOT NULL";
									endif;
									if ($changes[$i][$k]['key']=="PRI"):
										$sqlstrch[] = "ALTER TABLE `".$tablename[$i]."` DROP PRIMARY KEY, ADD PRIMARY KEY (`".$changes[$i][$k]['field']."`)";
									elseif($changes[$i][$k]['key']=="MUL"):
										$sqlstrch[] = "ALTER TABLE `".$tablename[$i]."` ADD INDEX (`".$changes[$i][$k]['field']."`)";
									elseif($changes[$i][$k]['key']=="UNI"):
										$sqlstrch[] = "ALTER TABLE `".$tablename[$i]."` ADD UNIQUE (`".$changes[$i][$k]['field']."`)";
									elseif($changes[$i][$k]['key']==""):
										// $sqlstrch[] = " DROP INDEX (`".$changes[$i][$k]['field']."`) ON `".$tablename[$i]."`";
									endif;
									if ($changes[$i][$k]['default']!=""):
										$defaultch = " DEFAULT '".$changes[$i][$k]['default']."'";
									else:
										$defaultch = "";
									endif;
									if ($nullstr=="NOT NULL") {
										$sqlstrch[] = "UPDATE `".$tablename[$i]."` SET `".$changes[$i][$k]['field']."` = '".$changes[$i][$k]['default']."' WHERE `".$changes[$i][$k]['field']."` IS NULL";
									}
									$sqlstrch[] = "ALTER TABLE `".$tablename[$i]."` MODIFY `".$changes[$i][$k]['field']."` ".$changes[$i][$k]['type']." ".$defaultch." ".$nullstr." ".$changes[$i][$k]['extras'];
									foreach ($sqlstrch AS $statement):
										try {
											$res = @doSQL($statement);
											// $_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => var_export($res, true));
										} finally {
											addWSPMsg('errormsg', var_export($statement, true));
										}
									endforeach;
									unset($sqlstrch);
								elseif ($changes[$i][$k]['action']=="delete"):
									$sqlstrdel="ALTER TABLE `".$tablename[$i]."` DROP COLUMN `".$changes[$i][$k]['field']."`";
									$res = doSQL($sqlstrdel);
									$_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => var_export($res, true));
								endif;
							endif;
						endfor;
					endif;
				endfor;
                if (isset($changes[$i])) {
                    for($x=0;$x<(count($changes[$i])-1);$x++) {
                        if($changes[$i][$x]['action']=="addnew"):
                            if($changes[$i][$x]['null']=="YES"):
                                $nullstr="NULL";
                            else:
                                $nullstr="NOT NULL";
                            endif;
                            if($changes[$i][$x]['key']=="PRI"):
                                $keystr=", DROP PRIMARY KEY, ADD PRIMARY KEY (".$changes[$i][$x]['field'].")";
                            elseif($changes[$i][$x]['key']=="MUL"):
                                $keystr=", ADD INDEX (".$changes[$i][$x]['field'].")";
                            else:
                                $keystr="";
                            endif;
                            if($changes[$i][$x]['default']!=""):
                                $defaultan=" DEFAULT '".$changes[$i][$x]['default']."'";
                            else:
                                $defaultan="";
                            endif;
                            $sqlstradn="ALTER TABLE `".$tablename[$i]."` ADD `".$changes[$i][$x]['field']."` ".$changes[$i][$x]['type']." ".$defaultan." ".$nullstr." ".$changes[$i][$x]['extras']." ".$keystr;
                            $res = doSQL($sqlstradn);
                            $_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => var_export($res, true));
                        endif;
                    }
                }
			endif;
		endfor;
		
		// adds new tables
		for ($i=0;$i<intval($changes['tablecount']);$i++):
			if (isset($changes[$i]) && isset($changes[$i]['tableaction']) && $changes[$i]['tableaction']=="addnewtable"):
				$key=array_search($changes[$i]['tablename'],$tempdev_tablename);
				$sqlstradnt.="CREATE TABLE `".$tempdev_tablename[$key]."` (";
				for ($j=0;$j<sizeof($tempdevtable[$tempdev_tablename[$key]]['field']);$j++):
					// set field as key if needed
					if ($tempdevtable[$tempdev_tablename[$key]]['key'][$j]=="PRI"):
						$keystrnt.=", PRIMARY KEY (".$tempdevtable[$tempdev_tablename[$key]]['field'][$j].")";
					elseif ($tempdevtable[$tempdev_tablename[$key]]['key'][$j]=="MUL"):
						$keystrnt.=", INDEX (".$tempdevtable[$tempdev_tablename[$key]]['field'][$j].")";
					else:
						$keystrnt.="";
					endif;
					// set NULL value to field
					if ($tempdevtable[$tempdev_tablename[$key]]['null'][$j]=="YES"):
						$nullstr="NULL";
					else:
						$nullstr="NOT NULL";
					endif;
					// set default value to field
					if ($tempdevtable[$tempdev_tablename[$key]]['default'][$j]!=""):
						$defaultnt = " DEFAULT '".$tempdevtable[$tempdev_tablename[$key]]['default'][$j]."'";
					else:
						$defaultnt = "";
					endif;
					$sqlstradnt.= " `".$tempdevtable[$tempdev_tablename[$key]]['field'][$j]."` ".$tempdevtable[$tempdev_tablename[$key]]['type'][$j]." ".$nullstr." ".$defaultnt." ".$tempdevtable[$tempdev_tablename[$key]]['extras'][$j]."";
					
					if($j==(sizeof($tempdevtable[$tempdev_tablename[$key]]['field'])-1)):
						$sqlstradnt.= $keystrnt.")";
					else:
						$sqlstradnt.=", ";
					endif;
				endfor;
				$res = doSQL($sqlstradnt);
				$_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => var_export($res, true));
			endif;
		endfor;
//		echo $funcsqlstat;
//		die();
	endif;
}

// update wsp files
if (!(function_exists('wspUpdate'))) {
    function wspUpdate($data, $ftp, $run = 1) {
        
        if ($ftp) {
            
            $fileupdate = '';
            if (_isCurl()) {
                $defaults = array( 
                    CURLOPT_URL => trim($_SESSION['wspvars']['updateuri']."/updater.php?key=".md5($_SERVER['HTTP_HOST'])."&file=".str_replace("[wsp]", "wsp", $data)), 
                    CURLOPT_HEADER => 0, 
                    CURLOPT_RETURNTRANSFER => TRUE, 
                    CURLOPT_TIMEOUT => 4 
                );
                $ch = curl_init();
                curl_setopt_array($ch, $defaults);    
                if( ! $fileupdate = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                curl_close($ch);
            }
            
            if (trim($fileupdate)=='') {
                die('error reading file');
            }

            $tmppfad = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".basename(str_replace('[wsp]', $_SESSION['wspvars']['wspbasedir'], $data)))));
            $tmpdat = fopen($tmppfad,'w');
            fwrite($tmpdat, $fileupdate);
            fclose($tmpdat);
            
            $data = str_replace('[wsp]', $_SESSION['wspvars']['wspbasedir'], $data);
            $filename = strrchr($data, "/");
            $dirstruct = str_replace($filename, "/", $data);
            $dirstruct = explode("/", $dirstruct);
            
            // create new directory if needed
            if (!(is_dir($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.implode("/", $dirstruct)))):
                ftp_mkdir($ftp, $_SESSION['wspvars']['ftpbasedir']."/".implode("/", $dirstruct));
            endif;
            // output error if directory could not be created
            if (!(is_dir($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.implode("/", $dirstruct)))):
                $_SESSION['wspvars']['errormsg'].= '<p>Kann Verzeichnis "'.implode("/", $dirstruct).'" nicht anlegen. (mkdir)</p>';
                addWSPMsg('errormsg', returnIntLang('system could not create directory1', false)." ".implode("/", $dirstruct)."\" ".returnIntLang('system could not create directory2', false));
            endif;
            // create backup of old file
            if (!(ftp_rename($ftp, str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$data)), str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$data."-backup-".time()))))):
                addWSPMsg('errormsg', returnIntLang('system creating backup file failed1', false)." \"".str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$data))."\" ".returnIntLang('system creating backup file failed2', false));
            endif;
            // move new file to its destination
            if (!(ftp_put($ftp, str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$data)), $tmppfad, FTP_BINARY))):
                addWSPMsg('errormsg', returnIntLang('system could not upload file (put)1', false)." \"".str_replace($_SERVER['DOCUMENT_ROOT'], "", $tmppfad)."\" ".returnIntLang('system could not upload file (put)2', false)." \"".str_replace("//", "/", str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/".$data))."\" ".returnIntLang('system could not upload file (put)3', false));
                return false;
            else:
                @unlink($tmppfad);
                return true;
            endif;
        } 
        else {
            addWSPMsg('errormsg', returnIntLang('system could not connect', false));
            return false;
        }
    }
}

// update packages
if (!(function_exists('packageUpdate'))){
    function packageUpdate($package) {

        $ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
        if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }}
        if (isset($_SESSION['wspvars']['ftppasv'])) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }

        if ($ftp) {

            $defaults = array( 
                CURLOPT_URL => trim($_SESSION['wspvars']['updateuri'].'/updater.php?key='.md5($_SERVER['HTTP_HOST']).'&file=updater/media/packages/'.$package.'.wsp3.tgz'), 
                CURLOPT_HEADER => 0, 
                CURLOPT_RETURNTRANSFER => TRUE, 
                CURLOPT_TIMEOUT => 4 
            );
            $ch = curl_init();
            curl_setopt_array($ch, $defaults);    
            if( ! $fileupdate = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
            curl_close($ch);

            $tmppfad = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3.tgz";
            $tmpdat = fopen($tmppfad,'w');
            fwrite($tmpdat, $fileupdate);
            fclose($tmpdat);

            @mkdir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir");

            try {
                $phar = new PharData($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3.tgz");
                $phar->extractTo($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir"); // extract all files
            } catch (Exception $e) {
                exec("cd ".$_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir; tar xzf ".$_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3.tgz");
            }

            ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir']."/".$_SESSION['wspvars']['wspbasedir']."/packages/".$package.".wsp3.tgz", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3.tgz", FTP_BINARY);
            ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir']."/".$_SESSION['wspvars']['wspbasedir']."/packages/".$package.".wsp3.xml", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir/package.xml", FTP_BINARY);
            ftp_close($ftp);

            $d = dir("tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir");
            while (false !== ($entry = $d->read())) {
                if (!($entry=='.') && !($entry=='..') && !($entry=='package.xml')) {
                    copyTree($_SERVER['DOCUMENT_ROOT']. "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir/".$entry, $_SESSION['wspvars']['ftpbasedir']."/".$entry);
                }
            }
            $d->close();

            @unlink($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3");
            @unlink($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3.tgz");
            @rmdir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir");

        }
    }
}

// update mediafiles
if (!(function_exists('mediaUpdate'))){
    function mediaUpdate($package) {

        $ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
        if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; }}
        if (isset($_SESSION['wspvars']['ftppasv'])) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }

        if ($ftp!='') {

            $defaults = array( 
                CURLOPT_URL => trim($_SESSION['wspvars']['updateuri'].'/updater.php?key='.md5($_SERVER['HTTP_HOST']).'&file=updater/media/media/'.$package.'.wsp3'), 
                CURLOPT_HEADER => 0, 
                CURLOPT_RETURNTRANSFER => TRUE, 
                CURLOPT_TIMEOUT => 4 
            );
            $ch = curl_init();
            curl_setopt_array($ch, $defaults);    
            if( ! $fileupdate = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
            curl_close($ch);

            $tmppfad = $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3";
            $tmpdat = fopen($tmppfad,'w');
            fwrite($tmpdat, $fileupdate);
            fclose($tmpdat);

            @mkdir($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir");

            try {
                $phar = new PharData($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3");
                $phar->extractTo($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir"); // extract all files
            } catch (Exception $e) {
                exec("cd ".$_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir; tar xzf ".$_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3");
            }

            ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir']."/".$_SESSION['wspvars']['wspbasedir']."/packages/".$package.".wsp3", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3", FTP_BINARY);
            ftp_put($ftp, $_SESSION['wspvars']['ftpbasedir']."/".$_SESSION['wspvars']['wspbasedir']."/packages/".$package.".wsp3.xml", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir/package.xml", FTP_BINARY);
            ftp_close($ftp);

            $d = dir("tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir");
            while (false !== ($entry = $d->read())) {
                if (!($entry=='.') && !($entry=='..') && !($entry=='package.xml')) {
                    copyTree($_SERVER['DOCUMENT_ROOT']. "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir/".$entry, $_SESSION['wspvars']['ftpbasedir']."/".$entry);
                }
            }
            $d->close();

            @unlink($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3");
            @unlink($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package.".wsp3.tgz");
            @rmdir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$package."_pkgdir");
        }
    }
}

function GetCopyTree($startsysdir) {
	$dirread = dir($startsysdir);
	while (false !== ($entry = $dirread->read())):
		if (!($entry=='.') && !($entry=='..') && !($entry=='package.xml')):
			$GLOBALS['copytree'][] = str_replace("//","/",$startsysdir."/".$entry);
			if (is_dir($startsysdir."/".$entry)):
				GetCopyTree($startsysdir."/".$entry);
			endif;
		endif;
	endwhile;
	}

if (isset($_SESSION['dbchange']) && count($_SESSION['dbchange'])>0):
    foreach ($_SESSION['dbchange'] AS $pkey => $pvalue):
		foreach ($pvalue AS $sqlvalue):
            $res = doSQL($sqlvalue);
			if (!($res['res'])):
				addWSPMsg('errormsg', "Fehler beim Ausführen des SQL-Statements ".addslashes($sqlvalue));
			endif;
		endforeach;
	endforeach;
endif;
// empty array
$_SESSION['dbchange'] = array();

// head der datei

$op = checkParamVar('op', '');
$id = checkParamVar('id', '');

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");

flush();flush();flush();

?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('system headline'); ?></h1></fieldset>
	<fieldset id="updating" class="text" style="display: none;">
		<?php

		if(isset($_POST['wspupdate']) && $_POST['wspupdate']=="wspupdate"):
        
        // updating mediafiles
        if (isset($_POST['mediafile'])) {
            foreach ($_POST['mediafile'] as $media) {
                echo '<div id="updmedia_'.strtolower($media).'">'.$media.'</div>';
            }
	   				
            flush();flush();flush();
		  		
            for ($i=0; $i<=count($_POST['mediafile']); $i++) {
                mediaUpdate($_POST['mediafile'][$i]);
                flush();flush();flush();
            }
        }	
        
        if (isset($_POST['package'])) {
            for ($i=0; $i<=count($_POST['package']); $i++) {
                if (isset($_POST['package'][$i])) { 
                    packageUpdate($_POST['package'][$i]);
                    flush();flush();flush();
                }
            }
        }
				
        $updatpfad = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/updatedateien.php";
        $updat = file($updatpfad);
		        
        $deldatpfad = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/deletefiles.php")));
        $deldat = is_file($deldatpfad)?file($deldatpfad):array();
        
        flush();flush();flush();

        $ftp = false; $ftpt = 0;
        while ($ftp===false && $ftpt<3) {
            $ftp = doFTP();
            $ftpt++;
        }
        
        if ($ftp) {
            // remove unused files on the fly
            if (count($deldat)>0) {
                for($i=1;$i<=intval($deldat[0]);$i++) {
                    if (trim($deldat[$i])!='') {
                        ftp_delete($ftp, trim(str_replace("//", "/", str_replace("//", "/", str_replace('[wsp]', $_SESSION['wspvars']['wspbasedir'], $_SESSION['wspvars']['ftpbasedir']."/".$deldat[$i])))));
                    }
                }
            }
            
            // do wsp file updates
            $try = true;
            for ($i=1; $i<=$updat[0]; $i++) {
                if ($try===true) {
                    $try = wspUpdate(trim($updat[$i]), $ftp, $i);
                    echo '<script type="text/javascript" language="javascript">'."\n";
                    echo "document.getElementById('up_".$i."').style.display='none';\n";
                    if (($i+1)<$updat[0]): echo "document.getElementById('up_".($i+1)."').style.display='block'"; endif;
                    echo '</script>';
                    flush();flush();flush();
                }
            }
            doSQL("UPDATE `wspproperties` SET `varvalue` = '".time()."' WHERE `varname` = 'lastupdate'");
            $_SESSION['wspvars']['updatesystem'] = false;
            ftp_close($ftp);
        } else {
            addWSPMsg('errormsg', returnIntLang('system could not connect', false));
        }
                
        $updatabasepfad = $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/updatedatabase.php";
        $updatabase = file($updatabasepfad);
        $selectChanges = unserializeBroken(trim($updatabase[0]));
        DatabaseUpdate($selectChanges, $_SESSION['wspvars']['updatedatabase'].'/media/xml/database.xml', $_SESSION['wspvars']['tables']);
				
            elseif (isset($_POST['pluginupdaten'])): 
				
				if (is_array($_POST['updatefile'])):
					foreach ($_POST['updatefile'] AS $key => $value):
						echo "<div id=\"up_".$key."\">".$value."</div>";
					endforeach;
					flush();flush();flush();
					ob_flush(); ob_flush(); ob_flush();
					
					foreach ($_POST['updatefile'] AS $key => $value) {
                        plugUpdate(trim($value), trim($_POST['pluginupdaten']));
                        flush();flush();flush();
                        ob_flush(); ob_flush(); ob_flush();
                    }
				endif;
				
				if (isset($_SESSION['dbchange'][($pluginvars['guid'])]) && is_array($_SESSION['dbchange'][($pluginvars['guid'])]) && count($_SESSION['dbchange'][($pluginvars['guid'])])>0):
					foreach ($_SESSION['dbchange'][($pluginvars['guid'])] AS $sqlvalue):
						$res = doSQL($sqlvalue);
                        if (!($res['res'])):
							addWSPMsg('errormsg', "Fehler beim Ausführen des SQL-Statements ".addslashes($sqlvalue));
						endif;
					endforeach;
				endif;

				unset($pluginvars);
				
			endif; ?>
		</fieldset>
		<?php 
		
		flush();flush();flush();
			
            // begin getting data from update server
            $values = false;
            $xmldata = '';
            
            if (_isCurl()) {
                $defaults = array( 
                    CURLOPT_URL => $_SESSION['wspvars']['updatefiles'].'/versions.php?key='.md5($_SERVER['HTTP_HOST']).'&url='.$_SESSION['wspvars']['workspaceurl'].'&system='.$_SESSION['wspvars']['wspversion'], 
                    CURLOPT_HEADER => 0, 
                    CURLOPT_RETURNTRANSFER => TRUE, 
                    CURLOPT_TIMEOUT => 4 
                );
                $ch = curl_init();
                curl_setopt_array($ch, $defaults);
                if( ! $xmldata = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                curl_close($ch);
                $xml = xml_parser_create();
                xml_parse_into_struct($xml, $xmldata, $values, $index);
				// git based request
				$gitInfo = array( 
					CURLOPT_URL => "https://api.github.com/repos/commonvisions/wsp/releases/latest", 
					CURLOPT_HEADER => 0, 
					CURLOPT_RETURNTRANSFER => TRUE, 
					CURLOPT_TIMEOUT => 4, 
					CURLOPT_SSL_VERIFYPEER => FALSE, // Disable SSL verification if needed
					CURLOPT_HTTPHEADER => array(
						"User-Agent: WSP Updater"
					)
				);
				$ch = curl_init();
				curl_setopt_array($ch, $gitInfo);
				$gitResponse = curl_exec($ch);
				curl_close($ch);
				if (!empty($gitResponse)) {
					$gitData = json_decode($gitResponse, true);
				}
				$GIT = [
					'url' => $gitData['html_url'] ?? 'https://github.com/commonvisions/wsp/',
					'update' => date(returnIntLang('format date'), strtotime($gitData['published_at'] ?? date('Y-m-d H:i:s'))),
					'version' => $gitData['tag_name'],
				];
            }
            // end getting data from update server
    		
	        $devdata = array();		
            if (is_array($values)):
				$f = 0; foreach ($values as $file):
                    if (isset($file['tag']) && trim($file['tag'])=='FILE' && isset($file['type']) && trim($file['type'])=='open'):
                        $devdata[$f] = array();
                    endif;
                    if ($file['tag']=='FILENAME' && isset($file['value'])):
                        $devdata[$f]['filename'] = trim($file['value']);
                    elseif ($file['tag']=='DATE' && isset($file['value'])):
                        $devdata[$f]['date'] = trim($file['value']);
                    elseif ($file['tag']=='VERSION' && isset($file['value'])):
                        $devdata[$f]['version'] = trim($file['value']);
                    elseif ($file['tag']=='SIZE' && isset($file['value'])):
                        $devdata[$f]['size'] = intval($file['value']);
                    elseif ($file['tag']=='NOTE' && isset($file['value'])):
                        $devdata[$f]['note'] = (isset($file['value'])?trim($file['value']):'');
                    endif;
                    if (isset($file['tag']) && trim($file['tag'])=='FILE' && isset($file['type']) && trim($file['type'])=='close'):
                        $f++;
                    endif;
				endforeach;
			endif;
    
			$x= 0;
			$newversion = false;
			$newmodul = array();
            $delfiles = array();
			
			if (count($devdata)>0) {
				foreach ($_SESSION['wspvars']['files'] as $file) {
					$version = '';
					$lastchange = '';
                    $overridediv = false;
    
                    if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.str_replace('[wsp]', $_SESSION['wspvars']['wspbasedir'], $file))):
						
						$aFRows = file($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.str_replace('[wsp]', $_SESSION['wspvars']['wspbasedir'], $file));
						foreach ($aFRows as $value):
							$value = trim($value);
                            if (!(strpos($value, '@version')===false)):
								$version = trim(substr($value, strpos($value, '@version')+8));
							endif;
							if (!(strpos($value, '@lastchange')===false)):
								$lastchange = trim(substr($value, strpos($value, '@lastchange')+12));
							endif;
							if (!(strpos($value, '*/')===false)):
								break;
							endif;
                        endforeach;
                        if (isset($aFRows[(count($aFRows)-1)]) && strpos($aFRows[(count($aFRows)-1)], 'EOF')===false):
                            $overridediv = true;
                        endif;
					endif;
					
					foreach ($devdata AS $ddk => $ddv) {
                        if ($file==$ddv['filename']) {
                            $devpos = $ddk;
                            break;
                        }
                    }
                    
					if (isset($devdata[$devpos]['note']) && trim($devdata[$devpos]['note'])=='deprecated') {
                        if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.str_replace('[wsp]', $_SESSION['wspvars']['wspbasedir'], $file))) {
                            $delfiles[] = trim($file);
                        }
                    }
                    else {
                        $devversion = (isset($devdata[$devpos]['version']))?$devdata[$devpos]['version']:0;
                        $devdate = (isset($devdata[$devpos]['date']))?$devdata[$devpos]['date']:'';
                        if (compVersion($version, $devversion)==1) {
                            $newversion = true;
                            $diffversion = true;
                            $newmodulversionact[$x] = $version;
                            $newmodulversionnew[$x] = $devversion;
                            $newmodul[$x] = trim($file);
                            $x++;
                        }
                        else if (compVersion($devversion, $version)==1) {
                            $diffversion = true;
                        }
                        if ($overridediv && !($newversion)) {
                            $newversion = true;
                            $diffversion = true;
                            $newmodulversionact[$x] = $version.' [EOF]';
                            $newmodulversionnew[$x] = $devversion;
                            $newmodul[$x] = trim($file);
                            $x++;
                        }
					}
    
                }
		      
                if (count($delfiles)==0): $delfiles = false; endif;
    
				$upddbstruc['wsp'] = false;
				
                // begin getting data from update server
                $values = false;
                $xmldata = '';
                
                if (_isCurl()) {
                    $defaults = array( 
                        CURLOPT_URL => $_SESSION['wspvars']['updatedatabase'].'/media/xml/database.xml', 
                        CURLOPT_HEADER => 0, 
                        CURLOPT_RETURNTRANSFER => TRUE, 
                        CURLOPT_TIMEOUT => 4
                    );
                    $ch = curl_init();
                    curl_setopt_array($ch, $defaults);    
                    if( ! $xmldata = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                    curl_close($ch);
                    $xml = xml_parser_create();
                    xml_parse_into_struct($xml, $xmldata, $values, $index);
                }
                // end getting data from update server
    
				$devtable = array();
				
				if (is_array($values)) {
					foreach ($values as $tags) {
						if ($tags['tag']=='TABLENAME') {
							$dev_tablename[]=$tags['value'];
							$dev_tablenametemp='';
							foreach ($values as $tags2) {
								if ($tags2['tag']=='TABLENAME') {
									$dev_tablenametemp=$tags2['value'];
                                }
								if ($dev_tablenametemp==$tags['value']) {
									if($tags2['tag']=='FIELD'){
										$devtable[$tags['value']]['field'][]=$tags2['value'];
									}
									if($tags2['tag']=='TYPE'){
										$devtable[$tags['value']]['type'][]=$tags2['value'];
									}
									if($tags2['tag']=='NULL'){
										$devtable[$tags['value']]['null'][]=$tags2['value'];
									}
									if($tags2['tag']=='KEY') {
										if (array_key_exists('value', $tags2)) {
											$devtable[$tags['value']]['key'][]=$tags2['value'];
                                        }
										else {
											$devtable[$tags['value']]['key'][]='';
                                        }
                                    }
									if($tags2['tag']=='DEFAULT') {
										if (array_key_exists('value', $tags2)) {
											$devtable[$tags['value']]['default'][]=$tags2['value'];
                                        }
										else {
											$devtable[$tags['value']]['default'][]='';
                                        }
                                    }
									if($tags2['tag']=='EXTRAS') {
										if (array_key_exists('value', $tags2)) {
											$devtable[$tags['value']]['extras'][]=$tags2['value'];
                                        }
										else {
											$devtable[$tags['value']]['extras'][]='';
                                        }
                                    }
                                }
                            }
                        }
					}
				}
				
				$owntable = array();
				for($i=0;$i<sizeof($_SESSION['wspvars']['tables']);$i++) {
					$sql = "DESCRIBE `".$_SESSION['wspvars']['tables'][$i]."`";
					$res = doSQL($sql);
					if ($res['res']===true) {
						$own_tablename[$i] = $_SESSION['wspvars']['tables'][$i];
						foreach ($res['set'] AS $rsk => $rsv) {
                        	$owntable[$own_tablename[$i]]['field'][]= $rsv['Field'];
							$owntable[$own_tablename[$i]]['type'][]=$rsv['Type'];
							$owntable[$own_tablename[$i]]['null'][]=$rsv['Null'];
							$owntable[$own_tablename[$i]]['key'][]=$rsv['Key'];
							$owntable[$own_tablename[$i]]['default'][]=$rsv['Default'];
							$owntable[$own_tablename[$i]]['extras'][]=$rsv['Extra'];
						}
					}
				}
				
				flush();flush();flush();
				
				$changes = checkDatabase($owntable,$devtable,$dev_tablename,$own_tablename);
				$id = 0;
				$x= 0;
				$temarray = array();
				$devid = 0;
				$dbchanges = '';
				
				// search for changed tables
				foreach ($own_tablename as $table) {
					if (in_array($table, $dev_tablename)):
						
						// tabellenname von own ist in dev(wurde geaendert)
						
						if (isset($table) && is_array($changes) && array_key_exists($table, $changes) && sizeof($changes[$table])!=0):
							$upddbstruc['wsp'] = true;
							for($i=0;$i<sizeof($changes[$table]);$i++):
								$key= array_search($changes[$table][$i]['field'],$devtable[$table]['field']);
								$own_key= array_search($changes[$table][$i]['field'],$owntable[$table]['field']);
		
								if ($changes[$table][$i]['action']=="delete"):
									$temarray[$id][$i]['field']=$owntable[$table]['field'][$own_key];
									$temarray[$id][$i]['action']="delete";
								else:
									$temarray[$id][$i]['field']=$devtable[$table]['field'][$key];
									$temarray[$id][$i]['type']=$devtable[$table]['type'][$key];
									$temarray[$id][$i]['null']=$devtable[$table]['null'][$key];
									$temarray[$id][$i]['key']=$devtable[$table]['key'][$key];
									$temarray[$id][$i]['default']=$devtable[$table]['default'][$key];
									$temarray[$id][$i]['extras']=$devtable[$table]['extras'][$key];
								endif;
								
								$dbchanges .= $table." : ".$changes[$table][$i]['action']." field ".$devtable[$table]['field'][$key]."<br />";
								
								if ($changes[$table][$i]['action']=="changed"):
									// $newversion = true;
									$upddbstruc['wsp'] = true;
									$temarray[$id][$i]['action'] = "changed";
								endif;
								
								if ($changes[$table][$i]['action']=="addnew"):
									// $newversion = true;
									$upddbstruc['wsp'] = true;
									$temarray[$id][$i]['action'] = "addnew";
								endif;
								
								if ($changes[$table][$i]['action']=="delete"):
									// $newversion = true;
									$upddbstruc['wsp'] = true;
								endif;
							endfor;
							$temarray[$id]['changecount']=$i;
						endif;
					endif;		
					$id++;
					$devid = $id;
				}
				
				flush();flush();flush();
				
				// search for new tables
				foreach ($dev_tablename as $table) {
					if(!in_array($table,$own_tablename)) {
						// $newversion = true;
						$upddbstruc['wsp'] = true;
						$temarray[$devid]['tableaction'] = "addnewtable";
						$temarray[$devid]['tablename'] = $table;
						$devid++;
					}
                }
					
				$temarray['tablecount'] = $devid;
				$datbasepfad = str_replace('//', '/', str_replace('//', '/', $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/updatedatabase.php"));
				$datbasedat = @fopen($datbasepfad,'w');
				if (file_exists($datbasepfad)) {
					fwrite($datbasedat, serialize($temarray));
					fclose($datbasedat);
				} else {
					addWSPMsg('errormsg', returnIntLang('Could not create Database checkup file.'));
				}
				
				if (trim($_SESSION['dbchanges']) == trim($dbchanges)) {
					$_SESSION['dbchangerun'] = isset($_SESSION['dbchangerun'])?$_SESSION['dbchangerun']+1:1;
                }
				else {
					$_SESSION['dbchanges'] = $dbchanges;
					$_SESSION['dbchangerun'] = 0;
				}
				if ($_SESSION['dbchangerun']>3) {
					$upddbstruc['wsp'] = false;
				}
				$status = array('0' => 'inaktiv', '1' => 'aktiv');
            }
			else {
                $devfiles = array('error');
            }
    
			?>
		<fieldset class="text">
			<legend><?php echo returnIntLang('system sysinfo'); ?> <?php echo legendOpenerCloser('sysinfo'); ?></legend>
			<div id="sysinfo">
				<table class="tablelist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('system zend version'); ?></td>
						<td class="tablecell two"><?php echo zend_version(); ?></td>
						<td class="tablecell two"><?php echo returnIntLang('system php version'); ?></td>
						<td class="tablecell two"><?php echo phpversion(); ?></td>
					</tr>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('system mysql version'); ?></td>
						<td class="tablecell two"><?php echo (doSQL("SHOW GLOBAL VARIABLES LIKE '%version%'")['mysqli']); ?></td>
						<td class="tablecell two"><?php echo returnIntLang('system gdlib version'); ?></td>
						<td class="tablecell two"><?php $gdinfo = gd_info(); echo $gdinfo['GD Version']; ?></td>
					</tr>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('system wsp version'); ?></td>
						<td class="tablecell two"><?php echo $wspvars['wspversion']; ?></td>
						<td class="tablecell two"><?php echo returnIntLang('system free space'); ?></td>
						<td class="tablecell two"><?php
					
						$spacevals = array(
							1 => returnIntLang('system free space Byte'),
							2 => returnIntLang('system free space kB'),
							3 => returnIntLang('system free space MB'),
							4 => returnIntLang('system free space GB'),
							5 => returnIntLang('system free space TB')
							);
						
						echo ceil($freespace).' '.$spacevals[$c].'<br />';
						
						?></td>
					</tr>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('system structure entries all'); ?></td>
						<td class="tablecell two"><?php echo doSQL('SELECT `mid` FROM `menu`')['num']; ?></td>
						<td class="tablecell two"><?php echo returnIntLang('system structure entries active'); ?></td>
						<td class="tablecell two"><?php echo doSQL('SELECT `mid` FROM `menu` WHERE `trash` = 0')['num']; ?></td>
					</tr>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('system content entries all'); ?></td>
						<td class="tablecell two"><?php echo doSQL('SELECT `cid` FROM `content`')['num']; ?></td>
						<td class="tablecell two"><?php echo returnIntLang('system content entries active'); ?></td>
						<td class="tablecell two"><?php echo doSQL('SELECT `cid` FROM `content` WHERE `trash` = 0')['num']; ?></td>
					</tr>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('system post size'); ?></td>
						<td class="tablecell two"><?php echo ini_get('post_max_size'); ?></td>
						<td class="tablecell two"><?php echo returnIntLang('system upload size'); ?></td>
						<td class="tablecell two"><?php echo ini_get('upload_max_filesize'); ?></td>
					</tr>
				</table>
			</div>
		</fieldset>
		<?php if ($GIT ?? false) { ?>
		<fieldset class="text">
			<legend><?php echo returnIntLang('Information about GIT support', false); ?></legend>
			<p><?php echo sprintf(returnIntLang('Our self-hosted update service will expire in 2025. You can download the latest version from <strong><a href="%1$s" target="_blank">github.com [↗]</a></strong> or use the github.com repository as your git source. Latest release version is <strong>%2$s</strong> from <strong>%3$s</strong>.', false), $GIT['url'], $GIT['version'], $GIT['update']); ?></p>
		</fieldset>
		<?php } ?>
		<?php if (($devfiles[0] ?? '') == "error"): ?>
			<fieldset id="fieldset_wspkey" class="errormsg">
				<p>F&uuml;r die automatische Update-Funktion von WSP wird <strong>CURL</strong> benötigt.</p>
                <p>Updateserver: <?php echo str_replace("/wsp", "", $_SESSION['wspvars']['updatefiles']); ?></p>
				<p>WSP-Version: <?php echo $_SESSION['wspvars']['wspversion']; ?></p>
            </fieldset>
		<?php else:
			
			// find mediafile updates
			$media = array();
			// begin getting data from update server
            $values = false;
            
            if (_isCurl()) {
                $xmldata = '';
                $defaults = array( 
                    CURLOPT_URL => $_SESSION['wspvars']['updateuri'].'/versionsmedia.php', 
                    CURLOPT_HEADER => 0, 
                    CURLOPT_RETURNTRANSFER => TRUE, 
                    CURLOPT_TIMEOUT => 4
                );
                $ch = curl_init();
                curl_setopt_array($ch, $defaults);    
                if( ! $xmldata = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                curl_close($ch);
                $xml = xml_parser_create();
                xml_parse_into_struct($xml, $xmldata, $values, $index);
            }

            $mediapackage = array();
			$mediaversion = array();
			$mediadescription = array();
			$i = 0;
			foreach ($values as $file):
				if ($file['tag']=='PACKAGENAME'):
					$i++;
					$mediapackage[$i] = $file['value'];
				endif;
				if (($file['tag'] ?? '')=='VERSION'):
					$mediaversion[$i] = $file['value'];
				endif;
				if ($file['tag']=='DESCRIPTION'):
					$mediadescription[$i] = $file['value'];
				endif;
			endforeach;
			// read layout files			
			$cssdir = opendir ($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir']."/media/layout/");
			$cssdesc = array();
			$i = 0;
			while ($file=readdir($cssdir)):
				if (substr($file,0,1)!="." && $file!="screen.css.php" && $file!="screen.css" && is_file($file)):
					$readcss = $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir']."/media/layout/".$file;
					$fileinfo = file($readcss);
					for ($x=0; $x<=8; $x++):
						if (str_replace("@description", "@", ($fileinfo[$x] ?? 'x')) != ($fileinfo[$x] ?? 'x')):
							$cssdesc[$i] = trim(str_replace(" * @description ","",$fileinfo[$x]));
						endif;
						if (str_replace("@version", "@", ($fileinfo[$x] ?? 'x')) != ($fileinfo[$x] ?? 'x')):
							$cssversion[$i] = trim(str_replace(" * @version ","",$fileinfo[$x]));
						endif;
					endfor;
					$cssfile[$i] = $file;
				endif;
				$i++;
			endwhile;
			closedir ($cssdir);
			// set update field visible
			$mediaupdate = false;
			
            // begin getting data from update server
            $values = false;
            
            if (_isCurl()) {
                $xmldata = '';
                $defaults = array( 
                    CURLOPT_URL => $_SESSION['wspvars']['updateuri'].'/versionspackage.php', 
                    CURLOPT_HEADER => 0, 
                    CURLOPT_RETURNTRANSFER => TRUE, 
                    CURLOPT_TIMEOUT => 4
                );
                $ch = curl_init();
                curl_setopt_array($ch, $defaults);    
                if( ! $xmldata = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                curl_close($ch);
                $xml = xml_parser_create();
                xml_parse_into_struct($xml, $xmldata, $values, $index);
            }
            // end getting data from update server
            
			$updversions = array();
			$updpackages = array();
			$updpackagefiles = array();
			foreach ($values as $file):
				if ($file['tag']=='PACKAGENAME') {
					$updpackages[] = $file['value'];
				} elseif ($file['tag']=='VERSION') {
					$updversions[] = $file['value'];
				} elseif ($file['tag']=='FILE') {
					$updpackagefiles[] = $file['value'];
				}
			endforeach;
			// set update field visible
			$packageupdate = false;
			foreach ($updpackages as $key => $package):
				if (array_key_exists($package, $packages)):
					if ($updversions[$key]!=$packages[$package]['version']):
						$packageupdate = true;
					endif;
				else:
					$packageupdate = true;
				endif;
			endforeach;
			
			// end of package finder 

            // update section
			if ($newversion!==false || $upddbstruc['wsp']!==false || $packageupdate!==false): 
				$_SESSION['opentabs']['updatenewmod'] = 'display: block;';
				?>
			<form method="post" enctype="multipart/form-data" id="formmodupnew">
			<fieldset class="text" id="fieldset_updatenewmod">
				<legend><?php echo returnIntLang('system updatewsp'); ?> <?php echo legendOpenerCloser('updatenewmod'); ?></legend>
				<div id="updatenewmod">
					<?php if(in_array("".$_SESSION['wspvars']['wspbasedir']."/data/include/globalvars.inc.php",$newmodul)): 
						echo "<p>".returnIntLang('system globalvars update avaiable please install first')."</p>";
					endif; 
					if(!(in_array("".$_SESSION['wspvars']['wspbasedir']."/data/include/globalvars.inc.php",$newmodul))):
						if (sizeof($newmodul)>0):
							echo "<ul class=\"tablelist\">";
							?>
							<li class="tablecell head four"><?php echo returnIntLang('str filename'); ?></li>
							<li class="tablecell head two"><?php echo returnIntLang('system instversion'); ?></li>
							<li class="tablecell head two"><?php echo returnIntLang('system updversion'); ?></li>
							<?php
							for ($i=0;$i<sizeof($newmodul);$i++) {
								echo "<li class=\"tablecell four\">".$newmodul[$i]."</li>";
								echo "<li class=\"tablecell two\">".$newmodulversionact[$i]."</li>";
								echo "<li class=\"tablecell two\">".$newmodulversionnew[$i]."</li>";
							}
							echo "</ul>";
						endif;
					endif;

					$datverpfad = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/updatedateien.php"));
					$datverdat = @fopen($datverpfad,'w');

                    if ($datverdat) {
                        if (in_array("".$_SESSION['wspvars']['wspbasedir']."/data/include/globalvars.inc.php",$newmodul)):
                            fwrite($datverdat, "1\r\n");
                            fwrite($datverdat, $_SESSION['wspvars']['wspbasedir']."/data/include/globalvars.inc.php");
                        else:
                            fwrite($datverdat, sizeof($newmodul)."\r\n");
                            for($i=0;$i<sizeof($newmodul);$i++):
                                fwrite($datverdat, $newmodul[$i]."\r\n");
                            endfor;
                        endif;
                        fclose($datverdat);
                    } else {
						addWSPMsg('errormsg', returnIntLang('Could not create GlobalVars checkup file.'));
					}
                    
                    if (is_array($delfiles) && count($delfiles)>0):
                        $delverpfad = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/deletefiles.php")));
                        $delverdat = fopen($delverpfad,'w');
                        fwrite($delverdat, count($delfiles)."\r\n");
						for($i=0;$i<count($delfiles);$i++):
							fwrite($delverdat, $delfiles[$i]."\r\n");
						endfor;
                        fclose($delverdat);
					endif;
                    
					if ($packageupdate): echo "<p>".returnIntLang('system packageupdate avaiable', true)."</p>"; endif;

					foreach ($updpackages as $key => $package):
						if (array_key_exists($package, $packages)):
							if ($updversions[$key]!=$packages[$package]['version']):
								echo "<input type=\"hidden\" name=\"package[]\" value=\"".$package."\" />";
							endif;
						else:
							echo "<input type=\"hidden\" name=\"package[]\" value=\"".$package."\" />";
						endif;
					endforeach;
					
					?>
					<fieldset class="options innerfieldset">
						<p><input type="hidden" name="wspupdate" id="wspupdate" value="wspupdate" /><a href="#" onclick="document.getElementById('formmodupnew').submit(); return true;" class="greenfield"><?php echo returnIntLang('system updatewsp'); ?></a></p>
					</fieldset>
				</div>
			</fieldset>
			</form>
			<span id="dbdetails" style="display: none;">
			<fieldset class="text">
				<legend>DB-Update</legend>
				<?php echo $dbchanges; ?>
			</fieldset>
			</span>
			<?php else:
				echo '<fieldset class="text" id="fieldset_updatenewmod">';
				echo '<legend>'.returnIntLang('str websitepreview').' '.legendOpenerCloser('updatenewmod').'</legend>';
				echo '<div id="updatenewmod">';
				echo '<p>'.returnIntLang('system actualwsp').'</p>';
				echo '</div>';
				echo '</fieldset>';
				// if a false positive update was submitted we set this information to false
				$_SESSION['wspvars']['updatesystem'] = false;
				// update database for last check
				$lastupdate_sql = "UPDATE `wspproperties` SET `varvalue` = '".time()."' WHERE `varname` = 'lastupdate'";
				$lastupdate_res = doSQL($lastupdate_sql);
				echo '<script> $(\'#m_10 span\').hide(); $(\'#m_10_3 span\').hide(); </script>';
			endif; ?>
    
		<?php if (isset($plugin_num) && $plugin_num>0):
			for ($pres=0; $pres<$plugin_num; $pres++):
				if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$wspvars['wspbasedir']."/plugins/".trim($plugin_res['set'][$pres]['pluginfolder'])."/data/include/globalvars.inc.php")):
					?>
					<fieldset class="text" id="fieldset_plugin_<?php echo trim($plugin_res['set'][$pres]['guid']); ?>">
						<legend><?php echo returnIntLang('system updateplugin1'); ?> <?php echo trim($plugin_res['set'][$pres]['pluginname']); ?> <?php echo returnIntLang('system updateplugin2'); ?> <?php echo legendOpenerCloser('plugin_'.trim($plugin_res['set'][$pres]['guid'])); ?></legend>
						<div id="plugin_<?php echo trim($plugin_res['set'][$pres]['guid']); ?>">
							<?php
							
							unset($pluginvars);
                            
							require($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$wspvars['wspbasedir']."/plugins/".trim($plugin_res['set'][$pres]['pluginfolder'])."/data/include/globalvars.inc.php");
							if (trim($plugin_res['set'][$pres]['guid'])!=$pluginvars['guid']):
								echo "Die Versionen stimmen nicht &uuml;berein. Bitte installieren Sie das aktuelle Plugin, bevor Sie den automatischen Updateservice nutzen.";
							else:
								
                                // begin getting data from update server
                                $values = false;
                                $xmldata = '';
                                $defaults = array( 
                                    CURLOPT_URL => $pluginvars[$pluginvars['guid']]['updatefiles'].'/versions.php?key='.md5($_SERVER['HTTP_HOST']).'&url='.$_SESSION['wspvars']['workspaceurl'].'&system='.$_SESSION['wspvars']['wspversion'], 
                                    CURLOPT_HEADER => 0, 
                                    CURLOPT_RETURNTRANSFER => TRUE, 
                                    CURLOPT_TIMEOUT => 4 
                                );
                                $ch = curl_init();
                                curl_setopt_array($ch, $defaults);    
                                if( ! $xmldata = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                                curl_close($ch);
                                $xml = xml_parser_create();
                                xml_parse_into_struct($xml, $xmldata, $values, $index);
                                // end getting data from update server
                            
								$devversions = array();
								$devdates = array();
								$devfiles = array();
								$devdata = array();
								$d = 0;
								
								foreach ($values as $file):
									if ($file['tag']=='FILENAME' && array_key_exists('value', $file)):
										$devfiles[] = $file['value'];
										$devdata[$d]['filename'] = $file['value'];
									elseif ($file['tag']=='DATE' && array_key_exists('value', $file)):
										$devdates[] = $file['value'];
										$devdata[$d]['date'] = $file['value'];
									elseif ($file['tag']=='VERSION' && array_key_exists('value', $file)):
										$devversions[] = $file['value'];
										$devdata[$d]['version'] = $file['value'];
									elseif ($file['tag']=='DESC' && array_key_exists('value', $file)):
										$devdescs[] = $file['value'];
										$devdata[$d]['desc'] = $file['value'];
									endif;
									$d++;
								endforeach;
								
								$id = 0;
								$x= 0;
								$newversion = false;
								$newmodul = array();
								
								if ($devfiles[0]!="error"):
									foreach ($pluginvars[$pluginvars['guid']]['files'] as $file):
										
										$version = '';
										$lastchange = '';
										$aFRows = '';
										
										if (strstr($file, '[plugin]')):
											if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/plugins/'.str_replace('[plugin]', $pluginvars[$pluginvars['guid']]['plugindir'], $file))):
												$aFRows = file($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/plugins/'.str_replace('[plugin]', $pluginvars[$pluginvars['guid']]['plugindir'], $file));
											endif;
										elseif(strstr($file, '[wsp]')):
											if (is_file($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.str_replace('[wsp]', $_SESSION['wspvars']['wspbasedir'], $file))):
												$aFRows = file($_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.str_replace('[wsp]', $_SESSION['wspvars']['wspbasedir'], $file));
											endif;
										else:
											if (is_file(str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$file))):
												$aFRows = file(str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$file));
											endif;
										endif;
										
										if (is_array($aFRows)):
											foreach ($aFRows as $value):
												$value = trim($value);
												if (!(strpos($value, '@version')===false)):
													$version = trim(substr($value, strpos($value, '@version')+8));
												endif;
												if (!(strpos($value, '@lastchange')===false)):
													$lastchange = trim(substr($value, strpos($value, '@lastchange')+12));
												endif;
												if (!(strpos($value, '*/')===false)):
													break;
												endif;
											endforeach;
										endif;
										
										$devpos = intval(array_search($file, $devfiles));
										if ($devpos===false):
											$devversion = '';
											$devdate = '';
										else:
											if (array_key_exists($devpos, $devversions)): $devversion = $devversions[$devpos]; else: $devversion = 1; endif;
											if (array_key_exists($devpos, $devdates)): $devdate = $devdates[$devpos]; else: $devdate = date('Y-m-d', (time()-86400)); endif;
											if (compVersion($version, $devversion)):
												$newversion = true;
												$diffversion = true;
												$newmodulversionact[$x] = $version;
												$newmodulversionnew[$x] = $devversion;
												$newmoduldateact[$x] = "-";
												$newmoduldatenew[$x] = $devdate;
												$newmodul[$x] = str_replace("[plugin]", $pluginvars[$pluginvars['guid']]['plugindir'], $file);
												$newmodulpath[$x] = str_replace("//", "/", str_replace("//", "/", str_replace("[plugin]", "/".$_SESSION['wspvars']['wspbasedir']."/plugins/".$pluginvars[($pluginvars['guid'])]['plugindir']."/", $file)));
												$newmoduldesc[$x] = $newmodul[$x];
												if (isset($devdesc) && trim($devdesc)!=""):
													$newmoduldesc[$x] = $devdesc;
												endif;
												$x++;
											elseif (compVersion($devversion, $version)):
												$diffversion = true;
											endif;
											array_splice($devversions, $devpos, 1);
											array_splice($devdates, $devpos, 1);
											array_splice($devfiles, $devpos, 1);
										endif;
										$id++;
									endforeach;
									
									if (count($devfiles)>0):
										foreach ($devfiles as $devpos => $file):
											$devversion = $devversions[$devpos];
											$devdate = $devdates[$devpos];
											$id++;
										endforeach;
									endif;
									
									// unset all wsp-table-based vars
									$own_tablename = array();
									$dev_tablename = array();
									
									$upddbstruc[trim($pluginvars['guid'])] = false;
                                    // begin getting data from update server
                                    $values = false;
                                    $xmldata = '';
                                    $defaults = array( 
                                        CURLOPT_URL => $pluginvars[$pluginvars['guid']]['updatefiles'].'/xml/database.xml', 
                                        CURLOPT_HEADER => 0, 
                                        CURLOPT_RETURNTRANSFER => TRUE, 
                                        CURLOPT_TIMEOUT => 4 
                                    );
                                    $ch = curl_init();
                                    curl_setopt_array($ch, $defaults);    
                                    if( ! $xmldata = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
                                    curl_close($ch);
                                    $xml = xml_parser_create();
                                    xml_parse_into_struct($xml, $xmldata, $values, $index);
                                    // end getting data from update server
                            
									$devtable = array();
									foreach ($values as $tags):
										if ($tags['tag']=='TABLENAME'):
											$dev_tablename[] = $tags['value'];
											foreach ($values as $tags2):
												if ($tags2['tag']=='TABLENAME'):
													$dev_tablenametemp=$tags2['value'];
												endif;
												if ($dev_tablenametemp==$tags['value']):
													if($tags2['tag']=='FIELD'){
														$devtable[$tags['value']]['field'][]=$tags2['value'];
													}
													if($tags2['tag']=='TYPE'){
														$devtable[$tags['value']]['type'][]=$tags2['value'];
													}
													if($tags2['tag']=='NULL'):
														$devtable[$tags['value']]['null'][]=$tags2['value'];
													endif;
													if($tags2['tag']=='KEY'):
														if (array_key_exists('value', $tags2)):
															$devtable[$tags['value']]['key'][]=$tags2['value'];
														else:
															$devtable[$tags['value']]['key'][]='';
														endif;
													endif;
													if($tags2['tag']=='DEFAULT'):
														if (array_key_exists('value', $tags2)):
															$devtable[$tags['value']]['default'][]=$tags2['value'];
														else:
															$devtable[$tags['value']]['default'][]='';
														endif;
													endif;
													if($tags2['tag']=='EXTRAS'):
														if (array_key_exists('value', $tags2)):
															$devtable[$tags['value']]['extras'][]=$tags2['value'];
														else:
															$devtable[$tags['value']]['extras'][]='';
														endif;
													endif;
												endif;
											endforeach;
										endif;
									endforeach;
									
									$owntable = array();
									for($i=0;$i<sizeof($pluginvars[$pluginvars['guid']]['tables']);$i++) {
										$sql = "DESCRIBE `".$pluginvars[$pluginvars['guid']]['tables'][$i]."`";
										$res = doSQL($sql);
										if ($res['res']) {
											$own_tablename[$i]=$pluginvars[$pluginvars['guid']]['tables'][$i];
											foreach ($res['set'] AS $rsk => $rsv) {
												$owntable[$own_tablename[$i]]['field'][] = trim($rsv['Field']);
												$owntable[$own_tablename[$i]]['type'][] = trim($rsv['Type']);
												$owntable[$own_tablename[$i]]['null'][] = trim($rsv['Null']);
												$owntable[$own_tablename[$i]]['key'][] = trim($rsv['Key']);
												$owntable[$own_tablename[$i]]['default'][] = trim($rsv['Default']);
												$owntable[$own_tablename[$i]]['extras'][] = trim($rsv['Extra']);
                                            }
                                        }
                                    }
									
									flush();flush();flush();
									ob_flush(); ob_flush(); ob_flush();

									$changes = checkDatabaseNew($owntable, $devtable);
									
									if (isset($_SESSION['wspvars']['devstat']) && $_SESSION['wspvars']['devstat']):
										echo "<fieldset><legend>plugin db changes</legend><pre>";
										var_export($changes);
										echo "</pre></fieldset>";
									endif;
									
									if (is_array($changes) && count($changes)>0):
										$_SESSION['dbchange'][($pluginvars['guid'])] = $changes;
									else:
										unset($_SESSION['dbchange'][($pluginvars['guid'])]);
									endif;
									
									flush();flush();flush();
									ob_flush(); ob_flush(); ob_flush();
								else:
									unset($_SESSION['dbchange'][($pluginvars['guid'])]);
								endif;
							endif;
							
							if ($newversion || isset($_SESSION['dbchange'][($pluginvars['guid'])])):
								// show update files and/or update button only if updates found
								?>
								<form method="post" enctype="multipart/form-data" name="form<?php echo $pluginvars['guid']; ?>update" id="form<?php echo $pluginvars['guid']; ?>update">
								<table class="tablelist">
									<?php
									if (sizeof($newmodul)>0):
										echo "<tr>";
										echo "<td class=\"tablecell head four\">".returnIntLang('str filename', true)."</td>";
										echo "<td class=\"tablecell head two\">".returnIntLang('system instversion', true)."</td>";
										echo "<td class=\"tablecell head two\">".returnIntLang('system updversion', true)."</td>";
										echo "</tr>";
										for($i=0;$i<sizeof($newmodul);$i++):
											echo "<tr>";
											echo "<td class=\"tablecell four\"><input type=\"checkbox\" name=\"updatefile[]\" id=\"pluginfile_".$pluginvars['guid']."_".$i."\" value=\"".$newmodulpath[$i]."\" checked=\"checked\" /><input type=\"hidden\" name=\"updatefile[]\" value=\"".$newmodulpath[$i]."\" />".$newmoduldesc[$i]."</td>";
											echo "<td class=\"tablecell one\">".$newmodulversionact[$i]."</td>";
											echo "<td class=\"tablecell one\">".$newmoduldateact[$i]."</td>";
											echo "<td class=\"tablecell one\">";
											if ($newmodulversionnew[$i]=='NEW'):
												echo "<span class=\"bubblemessage green\">".returnIntLang('str new', false)."</span>";
											else:
												echo $newmodulversionnew[$i];
											endif;
											echo "</td>";
											echo "<td class=\"tablecell one\">".$newmoduldatenew[$i]."</td>";
										endfor;
									endif;
									
									?>
								</table>
								<fieldset class="options innerfieldset"><p><input type="hidden" name="pluginupdaten" id="plugin_<?php echo $pluginvars['guid']; ?>_updaten" value="<?php echo $pluginvars['guid']; ?>" /><a onclick="document.getElementById('form<?php echo $pluginvars['guid']; ?>update').submit(); return true;" class="greenfield" style="cursor: pointer;"><?php echo returnIntLang('system update plugin'); ?></a></p></fieldset>
								</form>
								<script language="JavaScript1.2" type="text/javascript">
								<!--
								
								document.getElementById('plugin_<?php echo trim($plugin_res['set'][$pres]['guid']); ?>').style.display = 'block';
										
								// -->
								</script>
							<?php else: ?>
								<p><?php echo returnIntLang('system plugin up to date'); ?></p>
							<?php endif; ?>
						</div>
					</fieldset>
					<?php
				endif;
			endfor;
		endif;
	endif;
?>
</div>
<?php require ("./data/include/footer.inc.php"); ?>
<!-- EOF -->