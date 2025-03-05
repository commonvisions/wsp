<?php
/**
 * @description system administration
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2020-04-30
 */

/* switching off errors to prevent update failures in LIVE version */
/*
error_reporting(0);
ini_set('error_reporting', 0);
*/

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'system';
$_SESSION['wspvars']['pagedesc'] = array('far fa-cogs',returnIntLang('menu manage'),returnIntLang('menu manage system'));
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['addpagecss'] = array(
    'dropify.css',
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'dropify.js',
    );
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/siteinfo.inc.php");
// page specific includes -------------------- 

/* include_once "./data/include/lib5.xml.inc.php"; // 2017-02-21 - php < 5 not supported */
// define page specific vars -----------------
// calculate free and total space
$cf = 0; $freespace = disk_free_space(DOCUMENT_ROOT);
while ($freespace>1024): $freespace = $freespace/1024; $cf++; endwhile;
$ct = 0; $totalspace = disk_total_space(DOCUMENT_ROOT);
while ($totalspace>1024): $totalspace = $totalspace/1024; $ct++; endwhile;
// define page specific funcs ----------------

// unserializeBroken is also setup in funcs … this is fallback
if (!(function_exists('unserializeBroken'))) {
    function unserializeBroken($value, $arr = true) {
        if (is_array($value)) {
            return $value;
        }
        else if (trim($value)!='') {
            $check = @unserialize($value);
            if (is_array($check)) {
                return $check;
            }
            else {
                $tmpserialized = '';
                while (strlen($value)>0) {
                    $substring = substr($value, 0, 2);
                    if (strstr($substring, 'a:')) {
                        $posSemikolon = strpos($value, '{');
                        $substring2 = substr($value, 0, $posSemikolon+1);
                        $tmpserialized = $tmpserialized.$substring2;
                        $value = substr($value, $posSemikolon+1, strlen($value));
                    }
                    else if (strstr($substring, 'i:')) {
                        $posSemikolon = strpos($value, ';');
                        $substring2 = substr($value, 0, $posSemikolon+1);
                        $tmpserialized = $tmpserialized.$substring2;
                        $value = substr($value, $posSemikolon+1, strlen($value));
                    }
                    else if (strstr($substring, 's:')) {
                        $int = preg_match('/";[adis]:/', $value, $treffer, PREG_OFFSET_CAPTURE);
                        if($int == 1) {
                            $substring2 = substr($value, 0, $treffer[0][1]+2);
                            $a = strpos($substring2, ':"');
                            $substring3 = substr($substring2, $a+2, ($treffer[0][1]));
                            $substring3 = substr($substring3, 0, strlen($substring3)-2);
                            $strlaenge = strlen($substring3);
                            $tmpserialized = $tmpserialized."s:".$strlaenge.":".'"'.$substring3.'";';
                            $value = substr($value, $treffer[0][1]+2, strlen($value));
                        }
                        else {
                           preg_match('/";}/', $value, $treffer, PREG_OFFSET_CAPTURE);
                           $substring2 = substr($value, 0, $treffer[0][1]+2);
                           $a = strpos($substring2, ':"');
                           $substring3 = substr($substring2, $a+2, ($treffer[0][1]));
                           $substring3 = substr($substring3, 0, strlen($substring3)-2);
                           $strlaenge = strlen($substring3);
                           $tmpserialized = $tmpserialized."s:".$strlaenge.":".'"'.$substring3.'";';
                           $value = substr($value, $treffer[0][1]+2, strlen($value));
                        }
                    }
                    else if (strstr($substring, 'd:')) {
                        $posSemikolon = strpos($value, ';');
                        $substring2 = substr($value, 0, $posSemikolon+1);
                        $tmpserialized = $tmpserialized.$substring2;
                        $value = substr($value, $posSemikolon+1, strlen($value));
                    }
                    else if (strstr($substring, '}')) {
                        $tmpserialized = $tmpserialized."}";
                        $value = substr($value, 1, strlen($value));
                    }
                    else {
                        $tmpserialized = $tmpserialized.substr($value, 0, 1);
                        $value = substr($value, 1, strlen($value));
                    }
                }
                $return = @unserialize($tmpserialized);
                if (is_array($return)) {
                    return $return;
                }
                else if ($arr === true) {
                    return array();    
                }
            }
	   }
	}
}

if (!(function_exists('checkDatabase'))) {
	function checkDatabase($own,$dev,$dev_tablename,$own_tablename) {
		foreach ($own_tablename as $table) {
			if (in_array($table,$dev_tablename)){
				$x=0;
				// anzahl der felder der dev-tabelle und der aktuellen tabelle ueberpruefen
				if (sizeof($dev[$table]['field']) < sizeof($own[$table]['field'])):
					$length = sizeof($own[$table]['field']);
				else:
					$length = sizeof($dev[$table]['field']);
				endif;
				
				for ($i=0; $i<$length; $i++){
					$same = true;
					// pruefen ob neue spalte in der dev-tabelle vorhanden ist
					if (@(!in_array($dev[$table]['field'][$i],$own[$table]['field']) && $dev[$table]['field'][$i]!="")):
						$temp[$table][$x]['action']="addnew";
						$temp[$table][$x]['field']=$dev[$table]['field'][$i];
						$x++;
					elseif (in_array($own[$table]['field'][$i],$dev[$table]['field']) && $own[$table]['field'][$i]!=""):
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
						if ($own[$table]['extras'][$i]!=$dev[$table]['extras'][$i]):
							$temp[$table][$x]['field']=$dev[$table]['field'][$i];
							$temp[$table][$x]['extras']="extras";
							$temp[$table][$x]['action']="changed";
							$same=false;
						endif;
					endif;
					if (!in_array($own[$table]['field'][$i],$dev[$table]['field']) && $i<sizeof($own[$table]['field'])):
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
}

function checkDatabaseNew($own = array(), $dev = array()) {
	$returnsql = array();
//	echo "<pre>";
//	print_r($own);
//	echo "<hr />";
//	print_r($dev);
//	echo "</pre>";
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

// Datenbankstruktur updaten
function DatabaseUpdate($changes, $dbsource = '', $dbstructure = array()) {
	// grep actual database structure from update file
	if (trim($dbsource)!=""):
		$tempdev_fh = fopen(trim($dbsource), 'r');;
		$tempdev_xmlversion = '';
		while (!feof($tempdev_fh)):
			$tempdev_xmlversion .= fgets($tempdev_fh, 4096);
		endwhile;
		fclose($tempdev_fh);
		$tempdev_xml = xml_parser_create();
		xml_parse_into_struct($tempdev_xml, $tempdev_xmlversion, $tempdev_values, $index);
		$tempdevtable = array();
		
		foreach ($tempdev_values as $tags):
			if ($tags['tag']=='TABLENAME'):
				$tempdev_tablename[]=$tags['value'];
				foreach ($tempdev_values as $tags2):
					if($tags2['tag']=='TABLENAME') $tempdev_tablenametemp=$tags2['value'];
					if($tempdev_tablenametemp==$tags['value']):
						if($tags2['tag']=='FIELD') $tempdevtable[$tags['value']]['field'][]=$tags2['value'];
						if($tags2['tag']=='TYPE') $tempdevtable[$tags['value']]['type'][]=$tags2['value'];
						if($tags2['tag']=='NULL') $tempdevtable[$tags['value']]['null'][]=$tags2['value'];
						if($tags2['tag']=='KEY') $tempdevtable[$tags['value']]['key'][]=$tags2['value'];
						if($tags2['tag']=='DEFAULT') $tempdevtable[$tags['value']]['default'][]=$tags2['value'];
						if($tags2['tag']=='EXTRAS') $tempdevtable[$tags['value']]['extras'][]=$tags2['value'];
					endif;
				endforeach;
			endif;
		endforeach;
		
		$uptable = array();
		// switch between wsp-based tables and plugin-tables
		for($i=0;$i<sizeof($dbstructure);$i++):
			$sql = "DESCRIBE `".$dbstructure[$i]."`";
			$query = mysql_query($sql);
			$_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => $sql);
			$tablename[$i] = $dbstructure[$i];
			if ($query):
				while ($row = mysql_fetch_array($query)):
					$uptable[$tablename[$i]]['field'][] = $row['Field'];
					$uptable[$tablename[$i]]['type'][] = $row['Type'];
					$uptable[$tablename[$i]]['null'][] = $row['Null'];
					$uptable[$tablename[$i]]['key'][] = $row['Key'];
					$uptable[$tablename[$i]]['default'][] =$row['Default'];
					$uptable[$tablename[$i]]['extras'][] = $row['Extra'];
				endwhile;
			endif;
		endfor;
		
		// adds changes and deletes tables
		for($i=0;$i<sizeof($tablename);$i++):
			if($changes[$i]['tablename']==$tablename[$i] && $changes[$i]['tableaction']=="deletetable"):
				$sqlstrdelt="DROP TABLE `".$changes[$i]['tablename']."`";
				mysql_query($sqlstrdelt);
				$_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => $sqlstrdelt);
			else:
//				$xml.="<table>\r\n";
//				$xml.="<tablename>".$tablename[$i]."</tablename>\r\n";
				for($j=0;$j<sizeof($uptable[$tablename[$i]]['field']);$j++):
					if(sizeof($changes[$i])>0):
						$isin=false;
						for($k=0;$k<sizeof($changes[$i]);$k++):
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
										$sqlstrch[] = "ALTER TABLE `".$tablename[$i]."` DROP PRIMARY KEY, ADD PRIMARY KEY (".$changes[$i][$k]['field'].")";
									elseif($changes[$i][$k]['key']=="MUL"):
										$sqlstrch[] = "ALTER TABLE `".$tablename[$i]."` ADD INDEX (".$changes[$i][$k]['field'].")";
									elseif($changes[$i][$k]['key']=="UNI"):
										$sqlstrch[] = "ALTER TABLE `".$tablename[$i]."` ADD UNIQUE (`".$changes[$i][$k]['field']."`)";
									elseif($changes[$i][$k]['key']==""):
										$sqlstrch[] = "ALTER TABLE `".$tablename[$i]."` DROP INDEX (`".$changes[$i][$k]['field']."`)";
									endif;
									if ($changes[$i][$k]['default']!=""):
										$defaultch=" DEFAULT '".$changes[$i][$k]['default']."'";
									else:
										$defaultch = "";
									endif;
									$sqlstrch[] = "ALTER TABLE `".$tablename[$i]."` MODIFY `".$changes[$i][$k]['field']."` ".$changes[$i][$k]['type']." ".$defaultch." ".$nullstr." ".$changes[$i][$k]['extras'];
									foreach ($sqlstrch AS $statement):
										mysql_query($statement);
										$_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => $statement);
									endforeach;
									unset($sqlstrch);
								elseif ($changes[$i][$k]['action']=="delete"):
									$sqlstrdel="ALTER TABLE `".$tablename[$i]."` DROP COLUMN `".$changes[$i][$k]['field']."`";
									mysql_query($sqlstrdel);
									$_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => $sqlstrdel);
								endif;
							endif;
						endfor;
					endif;
				endfor;
				for($x=0;$x<sizeof($changes[$i]);$x++):
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
						mysql_query($sqlstradn);
						$_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => $sqlstradn);
					endif;
				endfor;
			endif;
		endfor;
		
		// adds new tables
		for ($i=0;$i<intval($changes['tablecount']);$i++):
			if ($changes[$i]['tableaction']=="addnewtable"):
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
				mysql_query($sqlstradnt);
				$_SESSION['wspvars']['showsql'][] = array('time' => time(), 'page' => $_SERVER['PHP_SELF'], 'sql' => $sqlstradnt);
			endif;
		endfor;
//		echo $funcsqlstat;
//		die();
	endif;
	} // DatabaseUpdate()

if (isset($_POST['doupdate'])) {
    $install = false;
    // create temporary file name for update file
    $sysfilename = 'update-'.time().'.zip';
    if ($_POST['doupdate']=='upload' && isset($_FILES['uploadsystem'])) {
        if ($_FILES['uploadsystem']['type']=='application/zip' && intval($_FILES['uploadsystem']['error'])==0) {
            $tmpdir = cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/');
            $sysfile = cleanPath($tmpdir.'/'.$sysfilename);
            if (createFolder('/'.WSP_DIR.'/tmp/')===false) {
                addWSPMsg('errormsg', returnIntLang('system update could not create tmp directory'));
                $install = false;
            }
            if (move_uploaded_file($_FILES['uploadsystem']['tmp_name'], $sysfile)) {
                $install = true;
            } else {
                addWSPMsg('errormsg', returnIntLang('system update failed while copying archive to tmp folder'));
            }
        } else {
            addWSPMsg('errormsg', returnIntLang('system update failed with wrong archive type'));
        }
    }
    // UPDATE VOM SERVER FERTIG MACHEN !!! 2019-10-21
    else if ($_POST['doupdate']=='server') {
        if (isCurl()) {
			
			// https://github.com/covistefan/wsp/archive/refs/heads/master.zip
			// http://update.wsp-server.info/versions/system/?ile=

            $defaults = array( 
                CURLOPT_URL => trim('https://'.WSP_UPDSRV.'/versions/system/?file='.trim($_POST['getversion'])),
                CURLOPT_HEADER => 0, 
                CURLOPT_RETURNTRANSFER => TRUE, 
                CURLOPT_TIMEOUT => 4 
            );
            $ch = curl_init();
            curl_setopt_array($ch, $defaults);    
            if( ! $getversion = curl_exec($ch)) { addWSPMsg('errormsg', trigger_error(curl_error($ch))); }
            curl_close($ch);
        }
        else {
            $fh = fopen('https://'.WSP_UPDSRV."/versions/system/?file=".trim($_POST['getversion']), 'r');
            if (intval($fh)!=0):
            while (!feof($fh)) {
                $getversion .= fgets($fh);
            }
            endif;
            fclose($fh);
        }
        // try to copy file to tmp user directory
        $tmpdir = cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/');
        if (createFolder('/'.WSP_DIR.'/tmp/')===false) {
            addWSPMsg('errormsg', returnIntLang('system update could not create tmp directory'));
            $install = false;
        }
		$sysfile = cleanPath($tmpdir.'/'.$sysfilename);
		$tmpdat = fopen($sysfile,'w');
		if (fwrite($tmpdat, $getversion)) {
			$install = true;
		} else {
			addWSPMsg('errormsg', returnIntLang('system update could not be retrieved from server'));
		}
		fclose ($tmpdat);        
    }
    // if file could be copied
    if ($install===true && is_file($sysfile) && filesize($sysfile)>2048) {
        // extract zip archive
        $zip = new ZipArchive;
        if ($zip->open($sysfile)===true) {        
            // get the name of the zip main folder
			$foldername = basename($zip->getNameIndex(0));
			// run archive for files
			for($i = 0; $i < $zip->numFiles; $i++) {
				$filename = $zip->getNameIndex($i);
				$fileinfo = pathinfo($filename);
				$fileinfo['dirname'] = substr($fileinfo['dirname'], strlen($foldername));
				// dont use hidden files
				if (substr($fileinfo['basename'],0,1)=='.') {
					// entry will be ignored
				} 
				else if ($fileinfo['basename']=='database.xml') {
					@copy("zip://".$sysfile."#".$filename, cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR.'/'.$_SESSION['wspvars']['usevar'].'/database.xml'));
				}
				// dont use double underscore stuff
				else if (substr($fileinfo['dirname'],0,2)=='__' || substr($fileinfo['basename'],0,2)=='__') {
					// entry will be ignored
				}
				// rename _wsp_ folder to WSP_DIR
				else if (substr($fileinfo['dirname'],0,5)=='_wsp_') {
					if ($fileinfo['basename']==$fileinfo['filename'] && !(isset($fileinfo['extension']))) {
						// it's a directory and the entry will be ignored
					} else {
						if (!(is_dir(cleanPath(DOCUMENT_ROOT.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']))))) {
							createFolder('/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/');
						}
						// try to make backup of file (but only once per day)
						if (!(is_file(cleanPath(DOCUMENT_ROOT.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/'.$fileinfo['basename'].'.backup-'.((defined('WSP_BETA') && WSP_BETA)?time():date('Ymd')))))) {
							var_export('backup for '.cleanPath(DOCUMENT_ROOT.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/'.$fileinfo['basename']));
							ftp_put($ftp, cleanPath(FTP_BASE.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/'.$fileinfo['basename'].'.backup-'.((defined('WSP_BETA') && WSP_BETA)?time():date('Ymd'))), cleanPath(DOCUMENT_ROOT.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/'.$fileinfo['basename']));
						}
						@copy("zip://".$sysfile."#".$filename, cleanPath(DOCUMENT_ROOT.'/'.str_replace('_wsp_', WSP_DIR, $fileinfo['dirname']).'/'.$fileinfo['basename']));
					}
				}
				else {
					if ($fileinfo['basename']==$fileinfo['filename'] && !(isset($fileinfo['extension']))) {
						// it's a directory and the entry will be ignored
					} else {
						if (!(is_dir(DOCUMENT_ROOT.'/'.$fileinfo['dirname']))) {
							createFolder('/'.$fileinfo['dirname'].'/');
						}
						@copy("zip://".$sysfile."#".$filename, cleanPath(DOCUMENT_ROOT.'/'.$fileinfo['dirname'].'/'.$fileinfo['basename']));
					}
				}
			}      
			$zip->close();
			addWSPMsg('resultmsg', returnIntLang('system update all files were copied'));
            // finaly remove file from tmp
			unlink($sysfile);
        } else {
            addWSPMsg('errormsg', returnIntLang('system update could not open zip file'));
        }
        deleteFile(WSP_DIR.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$_SESSION['wspvars']['usevar'].DIRECTORY_SEPARATOR.basename($sysfile));
        // run system UDPATER file
        if (is_file(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR.'sysupdate.php')) {
            addWSPMsg('errormsg', returnIntLang('system update ran system update'));
            deleteFile(WSP_DIR.DIRECTORY_SEPARATOR.'sysupdate.php');
        }
        if (is_file(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR.'sqlupdate.php')) {
            addWSPMsg('errormsg', returnIntLang('system update ran database update'));
            deleteFile(WSP_DIR.DIRECTORY_SEPARATOR.'sqlupdate.php');
        }
    }
}

// head der datei
require ("./data/include/header.inc.php");
require ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");

?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('system headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('system info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php
            
            showWSPMsg($_SESSION['wspvars']['shownotice']);

			if (defined('WSP_UPDSRV') && WSP_UPDSRV=='git') {
				echo date(returnIntLang('format date time'), $_SESSION['wspvars']['updatedate']);
				$updversion = time();
			} else if (defined('WSP_UPDSRV') && preg_match('#(\w{1,}\.\w{2,})#i', WSP_UPDSRV)>0) {
				// get update version from update server
				$updversion = false;
				if (isCurl()) {
					$defaults = array( 
						CURLOPT_URL => trim(WSP_UPDSRV."/download/version.php?key=".WSP_UPDKEY), 
						CURLOPT_HEADER => 0, 
						CURLOPT_RETURNTRANSFER => TRUE, 
						CURLOPT_TIMEOUT => 4 
					);
					$ch = curl_init();
					curl_setopt_array($ch, $defaults);    
					if( ! $updversion = curl_exec($ch)) { trigger_error(curl_error($ch)); } 
					curl_close($ch);
				} 
				else {
					$fh = fopen('https://'.WSP_UPDSRV."/download/version.php?key=".WSP_UPDKEY, 'r');
					if (intval($fh)!=0):
					while (!feof($fh)) {
						$updversion .= fgets($fh, 4096);
					}
					endif;
					fclose($fh);
				}
			}

            ?>
            <div class="row">
                <div class="col-md-6" <?php echo (version_compare($updversion, $_SESSION['wspvars']['localversion'])>0)?'':' style="display: none;" '; ?>>
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('system automatic update'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <form name="uploadsystem_zip" id="uploadsystem_zip" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="doupdate" value="server" />
                                <input type="hidden" name="getversion" value="full" />
                                <p><?php echo returnIntLang('system automatic update your version1')." ".$_SESSION['wspvars']['localversion']." ".returnIntLang('system automatic update your version2'); echo returnIntLang('system automatic update avaiable version1')." <strong>".$updversion."</strong> ".returnIntLang('system automatic update avaiable version2') ?></p>
                                <?php if ((defined('WSP_DEV') && WSP_DEV) || (defined('WSP_BETA') && WSP_BETA)) { ?>
                                    <p><input type="checkbox" name="getversion" value="nightly" checked="checked" /> <?php echo returnIntLang('system automatic update use nightly'); ?></p>
                                <?php } ?>
                                <p class="submitter"><input type="submit" class="btn btn-primary" value="<?php echo returnIntLang('str do update', false); ?>" /></p>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('system upload update'); ?></h3>
                            <p class="panel-subtitle"><?php echo returnIntLang('system upload update info'); ?></p>
                        </div>
                        <div class="panel-body">
                            <form name="uploadsystem_zip" id="uploadsystem_zip" method="post" enctype="multipart/form-data">
                                <p><input name="uploadsystem" type="file" id="dropify-uploadsystem" data-height="100" data-allowed-file-extensions="zip" /></p>
                                <input type="hidden" name="doupdate" value="upload" />
                                <p class="submitter" style="display: none;"><input type="submit" class="btn btn-primary" value="<?php echo returnIntLang('str do upload', false); ?>" /></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('system information widgets'); ?></h3>
                            <?php panelOpener(true, array(), false); ?>
                            <p class="panel-subtitle"><?php echo returnIntLang('system widget activation information'); ?></p>
                        </div>
                        <div class="panel-body">
                            <div class="row clear-4">
                                <?php 
                                
                                showWSPWidget('activepages', true);
                                showWSPWidget('activecontents', true);
                                showWSPWidget('freespace', true);
                                showWSPWidget('imagecount', true);
                                showWSPWidget('publishqueue', true);
                                showWSPWidget('documentcount', true);
                                showWSPWidget('zend', true);
                                showWSPWidget('phpversion', true);
                                showWSPWidget('gdlib', true);
                                showWSPWidget('uploadmax', true);
                                showWSPWidget('postmax', true);
                                showWSPWidget('mysqlserver', true);
                                showWSPWidget('mysqlclient', true);

                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            
                function updateWidget(widget, val) {
                    $.post("xajax/ajax.updatewidgetstat.php", { 'widget' : widget , 'val' : val})
                        .done (function(data) {
                    });
                }
                
            </script>
        </div>
    </div>
</div>

<script language="JavaScript" type="text/javascript">
<!--
    
$(document).ready(function() {
    
    $('.dropify').dropify();
    
    var drFolder = $('#dropify-uploadsystem').dropify({messages: { default: 'Upload ZIP' }});
    
    drFolder.on('dropify.afterReady', function(event, element) {
        $('#uploadsystem_zip .submitter').show();
        });
    drFolder.on('dropify.afterClear', function(event, element) {
        $('#uploadsystem_zip .submitter').hide();
        });
    
    });
    
// -->
</script>

<?php 

require ("./data/include/footer.inc.php");
