<?php
/**
 * Allgemeine Funktionen
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 6.11.1
 * fixed error with missing getWSPProperties
 * 
 * 2023-01-09
 * fixed exception on sql statement
 * fixed deprecation error on prepareTextField
 * 
 * 2025-01-19
 * added backtrace and error message to sql error message output
 * fixed call of utf8_decode
 * 
 */

// OLD MYSQL functions
if (!(function_exists('mysql_query'))) {
    define('MYSQL_ASSOC', true);
}
// replacing deprecated mysql_query()
if (!(function_exists('mysql_connect'))) {
    function mysql_connect($host, $user, $pass) { 
        if (isset($_SESSION['wspvars']['showdevmsg']) && $_SESSION['wspvars']['showdevmsg']===true) {
            addWSPMsg('errormsg', 'mysql_connect :'.var_export(debug_backtrace(), true)."<hr />");
        }
        return array('host' => $host, 'user' => $user, 'pass' => $pass);
    }
}
// replacing deprecated mysql_select_db()
if (!(function_exists('mysql_select_db'))) {
    function mysql_select_db($db, $connect) { 
        if (isset($_SESSION['wspvars']['showdevmsg']) && $_SESSION['wspvars']['showdevmsg']===true) {
            addWSPMsg('errormsg', 'mysql_select_db :'.var_export(debug_backtrace(), true)."<hr />");
        }
        $_SESSION['wspvars']['db'] = new mysqli($connect['host'],$connect['user'],$connect['pass'],$db);
        return $_SESSION['wspvars']['db'];
    }
}
// replacing deprecated mysql_insert_id()
if (!(function_exists('mysql_insert_id'))) {
    function mysql_insert_id() {
        if (isset($_SESSION['wspvars']['showdevmsg']) && $_SESSION['wspvars']['showdevmsg']===true) {
            addWSPMsg('errormsg', 'mysql_insert_id :'.var_export(debug_backtrace(), true)."<hr />");
        }
        return false;
    }
}
// replacing deprecated mysql_get_server_info()
if (!(function_exists('mysql_get_server_info'))) { 
    function mysql_get_server_info() {
        addWSPMsg('errormsg', 'mysql_get_server_info :'.var_export(debug_backtrace(), true)."<hr />");
        return mysqli_get_server_info($_SESSION['wspvars']['db']);
    }
}
// replacing deprecated mysql_get_client_info()
if (!(function_exists('mysql_num_rows')) && !(function_exists('mysql_get_client_info'))) { 
function mysql_get_client_info() {
    addWSPMsg('errormsg', 'mysql_num_rows :'.var_export(debug_backtrace(), true)."<hr />");
    return mysqli_get_client_info($_SESSION['wspvars']['db']);
}
}
// replacing deprecated mysql_query()
if (!(function_exists('mysql_query'))) { 
function mysql_query($sql) { 
    addWSPMsg('errormsg', 'mysql_query :'.var_export(debug_backtrace(), true)."<hr />");
	return doSQL($sql);
	}
}
// replacing deprecated mysql_fetch_row()
if (!(function_exists('mysql_fetch_row'))) { 
function mysql_fetch_row($resultset = array()) { 
    addWSPMsg('errormsg', 'mysql_fetch_row :'.var_export(debug_backtrace(), true)."<hr />");
	return false;
	}
}
// replacing deprecated mysql_num_rows()
if (!(function_exists('mysql_num_rows'))) { 
    function mysql_num_rows($queryarray = array('num'=>0)) { 
        addWSPMsg('errormsg', 'mysql_num_rows :'.var_export(debug_backtrace(), true)."<hr />");
        return $queryarray['num'];
	}
}
// replacing deprecated mysql_fetch_array()
if (!(function_exists('mysql_fetch_array'))) {
    function mysql_fetch_array($data, $datatype = NULL) {
        addWSPMsg('errormsg', 'mysql_fetch_array :'.var_export(debug_backtrace(), true)."<hr />");
        if (!(isset($_SESSION['mysql_fetch_array'][md5($data['sql'])]))) {
            $_SESSION['mysql_fetch_array'][md5($data['sql'])] = $data['set'];
        }
        if (isset($_SESSION['mysql_fetch_array'][md5($data['sql'])])) {
            if (count( $_SESSION['mysql_fetch_array'][md5($data['sql'])] )>0) {
                $subdata = array_shift($_SESSION['mysql_fetch_array'][md5($data['sql'])]);
                return $subdata;
            } else {
                unset($_SESSION['mysql_fetch_array'][md5($data['sql'])]);
                return false;
            }
        }
    }
}
// replacing deprecated mysql_result()
if (!(function_exists('mysql_result'))) {
    function mysql_result($resultset,$resultpos,$resultvar=false) {
        addWSPMsg('errormsg', 'mysql_result :'.var_export(debug_backtrace(), true)."<hr />");
        // setting up numeric keys for older statements
        $rnum = array();
        if (isset($resultset['set'][0])) {
            foreach ($resultset['set'][0] AS $rkey => $rvalue) {
                $rnum[] = $rkey;
            }
        }
        if ($resultvar===false && isset($rnum[0])) {
            return $resultset['set'][$resultpos][($rnum[0])];
        } else if (is_int($resultvar)) {
            return $resultset['set'][$resultpos][($rnum[intval($resultvar)])];
        } else if (isset($resultset['set'][$resultpos][$resultvar])) {
            return $resultset['set'][$resultpos][$resultvar];
        } else {
            return false;
        }
    }
}
// replacing deprecated mysql_real_escape_string()
if (!(function_exists('mysql_real_escape_string'))) {
    function mysql_real_escape_string($string) { 
        addWSPMsg('errormsg', 'mysql_real_escape_string :'.var_export(debug_backtrace(), true)."<hr />");
        return escapeSQL($string);
    }
}
// replacing deprecated mysql_db_name()
if (!(function_exists('mysql_db_name'))) {
    function mysql_db_name($result, $row, $field = NULL) { 
        addWSPMsg('errormsg', 'mysql_db_name :'.var_export(debug_backtrace(), true)."<hr />");
        return $result['set'][$row][$field];
    }
}
// end MYSQL functions
//
// NEW MYSQLi functions
// sql result function for mysqli
if (!(function_exists('mysqli_result'))) {
    function mysqli_result($res, $row, $field=0) { 
        $res->data_seek($row); 
        $datarow = $res->fetch_array(); 
        return $datarow[$field]; 
    }
}
// escape strings to sql
if (!(function_exists('escapeSQL'))) {
    function escapeSQL($string) {
        if (isset($_SESSION['wspvars']['db']) && $_SESSION['wspvars']['db']) {
            return mysqli_real_escape_string($_SESSION['wspvars']['db'], $string);
        } else {
            return $string;
        }
	}
}

function groupbyval( $groupby , $table ) {
	$groupbyval = doSQL("SELECT DISTINCT `".escapeSQL($groupby)."` FROM `".escapeSQL($table)."`");
	if ($groupbyval['num']>0) {
		$filter = array();
		foreach($groupbyval['set'] AS $k => $v) {
			$filter[] = $v[$groupby];
		}
		return ' `'.$groupby.'` IN (\''.implode('\',\'', $filter).'\') ';
	} else {
		return '';
	}
}

// doSQL returns array with resultset and complete information
if (!(function_exists('doSQL'))) {
    function doSQL($statement) {
		$set = array('res'=>false,'aff'=>0,'num'=>0,'set'=>array(),'sql'=>$statement,'inf'=>'','err'=>'','mysqli'=>false);
        if (isset($_SESSION['wspvars']['db']) && $_SESSION['wspvars']['db']) {			
			if (mysqli_get_server_info($_SESSION['wspvars']['db'])>=8) {
				// grep the group by part 
				$grp = '/(GROUP BY \`[a-zA-Z_\-0-9]*\`)/i';
				$grpfnd = preg_match($grp, $statement, $grpmatches, PREG_OFFSET_CAPTURE, 0);
				if ($grpfnd>0) {
					// get info about the WHERE statement
					$whr = '/(WHERE \`[a-zA-Z_\-0-9]*\`)/i';
					$whrfnd = preg_match($grp, $statement);
					// get info about FROM
					$frm = '/(FROM \`[a-zA-Z_\-0-9]*\` )(?!,)/i';
					$frmfnd = preg_match($frm, $statement, $frmmatches, PREG_OFFSET_CAPTURE, 0);
					// get the table
					preg_match('/`[a-zA-Z_\-0-9]*`/i', $frmmatches[0][0], $frmval, PREG_OFFSET_CAPTURE, 0);
					// get the groupfield
					preg_match('/`[a-zA-Z_\-0-9]*`/i', $grpmatches[0][0], $grpfld, PREG_OFFSET_CAPTURE, 0);
					$grpreplace = groupbyval( str_replace('`', '', $grpfld[0][0]) , str_replace('`', '', $frmval[0][0] ));
					if ($whrfnd>0) {
						$statement = str_replace($grpmatches[0][0], ' AND ('.$grpreplace.') ', $statement);
					} else {
						$statement = str_replace($grpmatches[0][0], ' WHERE ('.$grpreplace.') ', $statement);
					}
				}
			}
			try {
				$res = $_SESSION['wspvars']['db']->query($statement);
			}
			catch (Exception $e) {
				$set['err'] = $e;
				addWSPMsg('errormsg', '<p>' . nl2br($statement) . '</p>');
				addWSPMsg('errormsg', '<p>' . nl2br($e) . '</p>');
			}
		    if (isset($res) && $res===true) {
                $set['res'] = true;
                $set['aff'] = $_SESSION['wspvars']['db']->affected_rows;
            } else if (isset($res)) {
                $set['res'] = true;
                $set['aff'] = $_SESSION['wspvars']['db']->affected_rows;
                $set['num'] = $res->num_rows;
            } else if (!isset($set['err'])) {
                $set['err'] = $_SESSION['wspvars']['db']->error_list;
            }
            if ($set['res']) {
                $set['inf'] = $_SESSION['wspvars']['db']->insert_id;
            }
            if ($set['num'] && $set['num']>0) {
                for($n=0; $n<$set['num']; $n++) {
                    $set['set'][$n] = mysqli_fetch_assoc($res);
                }
                mysqli_free_result($res);
            }
            $set['mysqli'] = mysqli_get_server_info($_SESSION['wspvars']['db']);
        } else if (function_exists('mysql_get_client_info') && mysql_get_client_info()!==false) {
            $result = mysql_query($statement);
			if ($result) {
                $set['res'] = true;
                $set['num'] = (($result===false || $result===true)?0:mysql_num_rows($result)); 
                $set['aff'] = mysql_affected_rows();
                $set['inf'] = (mysql_insert_id()>0)?mysql_insert_id():'';
            }
            if ($set['num']>0) {
                for ($r=0; $r<$set['num']; $r++) {
                    $set['set'][$r] = mysql_fetch_assoc($result);
                }
            }
            if (mysql_error()) {
                $set['err'] = array (
                    'error' => mysql_error()
                );
            }
            $set['mysqli'] = false;
        }
        return $set;
	}
}
// doResultSQL returns ONE result with a given statement that SHOULD return ONE result 
if (!(function_exists('doResultSQL'))) {
function doResultSQL($statement) {
	$tmp = doSQL($statement);
	if ($tmp['res'] && is_array($tmp['set']) && count($tmp['set'])==1 && count($tmp['set'][0])==1) {
		$tmpkeys = array_keys($tmp['set'][0]);
		$tmpkey = $tmpkeys[0];
		return(setUTF8($tmp['set'][0][$tmpkey]));	
	} else {
		return false;
	}
	}
}
// getResultSQL returns an result ARRAY with a given statement that SHOULD return only ONE row 
if (!(function_exists('getResultSQL'))) {
    function getResultSQL($statement) {
        $tmp = doSQL($statement);
        if ($tmp['res'] && is_array($tmp['set']) && count($tmp['set'])>0 && count($tmp['set'][0])==1) {
            $keyname = array_keys($tmp['set'][0]);
            foreach ($tmp['set'] AS $tsk => $tsv) {
                $tmpval[$tsk] = $tsv[$keyname[0]];
            }
            return($tmpval);	
        } else {
            return false;
        }
	}
}
// getSetSQL returns an array of key-value-pairs of a given sql-data-stream if key AND value exist in stream
if (!(function_exists('getSetSQL'))) {
    function getSetSQL($dataarray,$varname,$varvalue) {
        $returnarray = array();
        if (isset($dataarray['set']) && count($dataarray['set'])>0) {
            foreach ($dataarray['set'] AS $dk => $dv) {
                if (isset($dv[$varname]) && isset($dv[$varvalue])) {
                    $returnarray[$dv[$varname]] = $dv[$varvalue];
                }
            }
            return ($returnarray);
        } else {
            return false;
        }
	}
}
// returnSingleResultSQL returnSingleResultSQL takes a value to search within a resultset and returns the set, 
// where the value is found as a value in set's key-value pair or returns the value of a given returnfield
if (!(function_exists('returnSingleResultSQL'))) {
    function returnSingleResultSQL($searchvalue, $searchfield, $resultset, $returnfield = '') {
        $resultrow = array_search($searchvalue, array_column($resultset, $searchfield));
        if (in_array($searchvalue, $resultset[$resultrow])) {
            if (trim($returnfield)!='') {
                if (isset($resultset[$resultrow][$returnfield])) {
                    return($resultset[$resultrow][$returnfield]);
                } else {
                    return false;
                }
            } else {
                return($resultset[$resultrow]);
            }
        } else {
            return false;
        }
	}
}
// getNumSQL returns num of result rows of given statement
if (!(function_exists('getNumSQL'))) {
    function getNumSQL($statement) { $tmp = doSQL($statement); if ($tmp['num']>0): return($tmp['num']); else: return 0; endif; }
}
// getAffSQL returns num of affected rows of given statement
if (!(function_exists('getAffSQL'))) {
    function getAffSQL($statement) { $tmp = doSQL($statement); if ($tmp['aff']>0): return(intval($tmp['aff'])); else: return 0; endif; }
}
// getInsSQL returns integer Value of last inserted row if it WAS inserted
if (!(function_exists('getInsSQL'))) {
    function getInsSQL($statement) { $tmp = doSQL($statement); if ($tmp['inf']>0): return($tmp['inf']); else: return 0; endif; }
}
// end MYSQLi related functions
//
// test CURL functionality
function _isCurl(){
    return function_exists('curl_version');
}
//
// start wsp stuff
//
// setup all wspvars to SESSION['wspvars'] AGAIN (already done in globalvars, but needful to grep ftp and db access
// bring all "older" defined wspvars to SESSION
if (isset($wspvars) && is_array($wspvars)) {
	foreach ($wspvars AS $wk => $wv) {
		$_SESSION['wspvars'][$wk] = $wv;
	}
}
//
define("gmlTable", 0);
define("gmlSelect", 1);
define("gmlContent", 3);
define("gmlSelectwo", 4);
define("gmlPublisher", 5);
define("gmlSelectwoID", 6);
define("gmlFieldset", 7);
define("gmlSortableList", 8);
define("gmlPreview", 9);
define("showSitemap", 10);
define("gmlMIDArray", 11);

// check for given params to vars, replace vars with param, etc
if (!(function_exists('checkParamVar'))) {
	function checkParamVar($var, $standard, $checkcookie = false, $checksession = true, $pref = '') {
		if (trim($pref)!="") {
			$param = $pref;
        } else {
			if ($checkcookie && isset($_COOKIE[$var])) {
				$param = $_COOKIE[$var];
			} else if ($checksession && isset($_SESSION[$var])) {
				$param = $_SESSION[$var];
			} else if (isset($_POST[$var])) {
				$param = $_POST[$var];
			} else if (isset($_GET[$var])) {
				$param = $_GET[$var];
			} else if (isset($_SESSION['wspvars']['hiddengetvars'][$var])) {
				$param = $_SESSION['wspvars']['hiddengetvars'][$var];
			} else {
				$param = $standard;
			}
        }
		return $param;
    }
}
// escape special chars from filename
if (!(function_exists('removeSpecialChar'))) {
    function removeSpecialChar($filename, $fileextension = '') {
        $lastdotpos = 0;
        if (trim($fileextension)=='') {
            $lastdotpos = strrpos($filename, ".");
            if($lastdotpos>0) {
                $fileextension = str_replace(".", "", substr($filename, $lastdotpos));
                $filename = substr($filename, 0, (-1*(intval(strlen($fileextension))+1)));
            }
        }
        $filereplacer = trim(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'filereplacer'"));
        if (trim($filereplacer)=="") {
            $filereplacer = "-";
        }
        $filename = str_replace(chr(228), 'ae', $filename);
        $filename = str_replace(chr(196), 'ae', $filename);
        $filename = str_replace(chr(246), 'oe', $filename);
        $filename = str_replace(chr(214), 'oe', $filename);
        $filename = str_replace(chr(252), 'ue', $filename);
        $filename = str_replace(chr(220), 'ue', $filename);
        $filename = str_replace(chr(223), 'ss', $filename);
        $filename = str_replace(' ', $filereplacer, $filename);
        $filename = str_replace('.', $filereplacer, $filename);
        $filename = str_replace('\\', '', $filename);
        $filename = str_replace('/', '', $filename);
        $filename = str_replace('(', 'kauf_', $filename);
        $filename = str_replace(')', 'kzu_', $filename);
        $allowed_in_file = "[^a-zA-Z0-9_]";
        $filename = preg_replace("/$allowed_in_file/", $filereplacer, $filename);
        $filename = str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, $filename))))));
        while (substr($filename,-1)==$filereplacer) {
            $filename = substr($filename,0,-1);
        }
        if (trim($filename)=='') {
            $filename = strtolower(md5(microtime()));
        }
        if ($lastdotpos>0) {
            return strtolower(urltext($filename).".".$fileextension);
        } else {
            return strtolower(urltext($filename));
        }
	}
}

if (!(function_exists("urltext"))) { 
    function urltext($txt) { $txt = strtolower(trim(setUTF8($txt))); $replaces = array( chr(192) => "a", chr(193) => "a", chr(194) => "a", chr(195) => "ae", chr(197) => "a", chr(196) => "ae", chr(228) => "ae", chr(198) => "ae", chr(214) => "oe", chr(220) => "ue", chr(223) => "ss", chr(224) => "a", chr(225) => "a", chr(226) => "a", chr(232) => "e", chr(233) => "e", chr(234) => "e", chr(236) => "i", chr(237) => "i", chr(238) => "i", chr(242) => "o", chr(243) => "o", chr(244) => "o", chr(246) => "oe", chr(249) => "u", chr(250) => "u", chr(251) => "u", chr(252) => "ue", "\"" => "", "'" => "", "," => "", " " => "-", "." => "", "?" => "", "!" => "", "*" => "", "#" => ""); foreach ($replaces AS $key => $value) { $txt = str_replace($key, $value, trim($txt)); } $txt = preg_replace('/[^a-z0-9\-_]/', "", $txt); $t = 0; while (strpos($txt, '--') || $t==20) { $txt = str_replace("--", "-", $txt); $t++; } return $txt; }
}

if (!(function_exists('datetotime'))) {
    // 2018-07-10, returns timestamp by given date()-based format string and given data string
    // e. g. datetotime("Y-m-d H:i:s", "2018-07-10 16:06:10") will return 1531238770 
    // strict param set true will return false even when a small error occurs, otherwise function returns 0 in worst case 
    function datetotime($formatstring, $datastring, $strict = false) {
        $strictbreak = false;
        $strictcheck = array(
            'A' => array('len',array(2)),
            'B' => array('intmax',999),
            'F' => array('string',array()),
            'G' => array('intrange',range(0,23)),
            'H' => array('len',array(2)),
            'L' => array('intrange',array(0,1)),
            'M' => array('string',array()),
            'N' => array('intrange',range(1,7)),
            'W' => array('intmax',54),
            'Y' => array('len',array(4)),
            'a' => array('len',array(2)),
            'c' => array('len',array(25)),
            'd' => array('len',array(2)),
            'g' => array('intrange',range(0,12)),
            'h' => array('len',array(1,2)),
            'i' => array('len',array(1,2)),
            'j' => array('len',array(1,2)),
            'm' => array('len',array(2)),
            'n' => array('len',array(1,2)),
            'o' => array('len',array(4)),
            'r' => array('len',array(31)),
            's' => array('len',array(2)),
            'w' => array('intrange',range(0,6)),
            'y' => array('len',array(1,2)),
            'z' => array('intmax',366),
            );
        for ($m=1; $m<=12; $m++) {
            $strictcheck['M'][1][$m] = date("M", mktime(5,5,5,$m,1,date('Y'))); 
            $strictcheck['F'][1][$m] = date("F", mktime(5,5,5,$m,1,date('Y'))); 
            }

        $gdf = preg_split('/\W+/', $formatstring); // GivenDateFormat
        // checking for spacing characters
        preg_match_all('/\W+/', $formatstring, $characters);
        $characters = $characters[0];
        // checking positions of spacing characters
        preg_match_all('/\W+/', $formatstring, $matches, PREG_OFFSET_CAPTURE);
        // setting up empty data array to store given date values
        $datapart = array();
        // run datastring and split it on each $character
        foreach ($characters AS $ck => $cv) {
            if ((strpos($datastring, $cv))!==false):
                $datapart[] = substr($datastring, 0, (strpos($datastring, $cv)));
                $datastring = substr($datastring, strpos($datastring, $cv)+strlen($cv));
            else:
                if ($strict) { return false; break; }
                $datapart[] = $datastring;
                $datastring = '';
            endif;
            }
        // get the last datapart as it was "forgotten" in foreach ;)
        $datapart[] = $datastring;
        // setup the final array
        $date = array();
        // run the gdf to setup the values to each date-format-string-keys
        foreach ($gdf AS $gdfk => $gdfv) {
            if (!(array_key_exists($gdfv, $date))) {
                $date[$gdfv] = $datapart[$gdfk];
            }
        }

        // checking for the strict option
        // if strict is not set, the system will try to prevent failures by converting some values (or ignore them)
        if ($strict) {
            if (count($gdf)!=count($datapart) && $strict) {
                $strictbreak = true;
            }
            if (count(array_diff_key($date, $strictcheck))>0) {
                // if some date-option was used, that is not allowed 
                $strictbreak = true;
            } else {
                // precheck with $strictcheck-setup
                foreach ($date AS $dk => $dv) {
                    if ($strictcheck[$dk][0]=='len') {
                        if (!(in_array(strlen($dv), $strictcheck[$dk][1]))) {
                            $strictbreak = true;
                        }
                    } else if ($strictcheck[$dk][0]=='string') {
                        if (!(in_array($dv, $strictcheck[$dk][1]))) {
                            $strictbreak = true;
                        }
                    } else if ($strictcheck[$dk][0]=='intmax') {
                        if (intval($dv)!=$dv || intval($dv)>$strictcheck[$dk][1]) {
                            $strictbreak = true;
                        }
                    } else if ($strictcheck[$dk][0]=='intrange') {
                        if (intval($dv)!=$dv || !(in_array(intval($dv), $strictcheck[$dk][1]))) {
                            $strictbreak = true;
                        }
                    } 
                }
            }
        }

        /*
        echo "gdf: ";
        print_r($gdf);
        echo "<hr />characters: ";
        print_r($characters);
        echo "<hr />datapart: ";
        print_r($datapart);
        echo "<hr />";
        echo "date (CALC): ";
        print_r($date);
        echo "<hr />";
        */

        // check the singular values in $date-array to get all required values for mktime()-function
        if (array_key_exists('r', $date)) {
            if (!(array_key_exists('Y', $date)) || intval($date['Y'])==0) $date['Y'] = date("Y", strtotime($date['r']));
            if (!(array_key_exists('m', $date)) || intval($date['m'])==0) $date['m'] = date("m", strtotime($date['r']));
            if (!(array_key_exists('d', $date)) || intval($date['d'])==0) $date['d'] = date("d", strtotime($date['r']));
            if (!(array_key_exists('H', $date)) || trim($date['H'])=='') $date['H'] = date("H", strtotime($date['r']));
            if (!(array_key_exists('i', $date)) || trim($date['i'])=='') $date['i'] = date("i", strtotime($date['r']));
            if (!(array_key_exists('s', $date)) || trim($date['s'])=='') $date['s'] = date("s", strtotime($date['r']));
        }
        if (array_key_exists('c', $date)) {
            if (!(array_key_exists('Y', $date)) || intval($date['Y'])==0) $date['Y'] = date("Y", strtotime($date['c']));
            if (!(array_key_exists('m', $date)) || intval($date['m'])==0) $date['m'] = date("m", strtotime($date['c']));
            if (!(array_key_exists('d', $date)) || intval($date['d'])==0) $date['d'] = date("d", strtotime($date['c']));
            if (!(array_key_exists('H', $date)) || trim($date['H'])=='') $date['H'] = date("H", strtotime($date['c']));
            if (!(array_key_exists('i', $date)) || trim($date['i'])=='') $date['i'] = date("i", strtotime($date['c']));
            if (!(array_key_exists('s', $date)) || trim($date['s'])=='') $date['s'] = date("s", strtotime($date['c']));
        }
        if (array_key_exists('j', $date) && (!(array_key_exists('d', $date)) || intval($date['d'])==0)) { $date['d'] = intval($date['j']); }
        if (array_key_exists('n', $date) && (!(array_key_exists('m', $date)) || intval($date['m'])==0)) { $date['m'] = intval($date['n']); }
        if (array_key_exists('M', $date) && (!(array_key_exists('m', $date)) || intval($date['m'])==0)) { $date['m'] = intval(array_search($date['M'], $strictcheck['M'][1])); }
        if (array_key_exists('F', $date) && (!(array_key_exists('m', $date)) || intval($date['m'])==0)) { $date['m'] = intval(array_search($date['F'], $strictcheck['F'][1])); }

        if (array_key_exists('y', $date) && (!(array_key_exists('Y', $date)) || intval($date['m'])==0)) { $date['Y'] = date('Y', mktime(12,0,0,1,1,intval($date['y']))); }

        // 2018-07-18 - SH - needs to be REchecked with week of year
        if (array_key_exists('o', $date) && (!(array_key_exists('Y', $date)) || intval($date['m'])==0)) {
            $date['Y'] = date('Y', mktime(12,0,0,1,1,intval($date['o'])));
        }

        if (array_key_exists('G', $date) && (!(array_key_exists('H', $date)) || trim($date['H'])=='')) { $date['H'] = intval($date['G']); }
        if (array_key_exists('h', $date) && (!(array_key_exists('H', $date)) || trim($date['H'])=='')) {
            if (array_key_exists('a', $date)) {
                if ($date['a']=='pm' && $date['h']<12) { $date['H'] = 12+intval($date['h']); } else if ($date['a']=='pm' && $date['h']==12) { $date['H'] = 24; }
            } else if (array_key_exists('A', $date)) {
                if (strtolower($date['A'])=='pm' && $date['h']<12) { $date['H'] = 12+intval($date['h']); } else if (strtolower($date['A'])=='pm' && $date['h']==12) { $date['H'] = 24; }
            } else {
                $date['H'] = intval($date['h']);
            }
        }
        if (array_key_exists('g', $date) && (!(array_key_exists('H', $date)) || trim($date['H'])=='')) {
            if (array_key_exists('a', $date)) {
                if ($date['a']=='pm' && $date['g']<12) { $date['H'] = 12+intval($date['g']); } else if ($date['a']=='pm' && $date['g']==12) { $date['H'] = 24; }
            } else if (array_key_exists('A', $date)) {
                if (strtolower($date['A'])=='pm' && $date['g']<12) { $date['H'] = 12+intval($date['g']); } else if (strtolower($date['A'])=='pm' && $date['g']==12) { $date['H'] = 24; }
            } else {
                $date['H'] = intval($date['g']);
            }
        }
        // some cryptic and or funny combinations to get the date
        // w » weekday (0=sonntag)
        // N » weekday (7=sonntag)
        // W » Woche des Jahres, 1 ist die erste Woche, die montag beginnt
        // L » schaltjahr oder nicht
        // o » Jahreszahl der Kalenderwoche (wie Y), außer wenn die ISO-Kalenderwoche (W) zum vorhergehenden oder nächsten Jahr gehört, wobei dann jenes Jahr verwendet wird
        // z - w/N - W - L - o

        // calculating day and month by given day of year (z)
        if (array_key_exists('z', $date) && (!(array_key_exists('d', $date)) || intval($date['d'])==0) && (!(array_key_exists('m', $date)) || intval($date['m'])==0)) {
            if (isset($date['Y']) && intval($date['Y'])>0) { $tmpstamp = mktime(12,0,0,1,1+intval($date['z']),intval($date['Y'])); } else { $tmpstamp = mktime(12,0,0,1,1+intval($date['z']),date('Y')); }
            $date['m'] = date("m", $tmpstamp);
            $date['d'] = date("d", $tmpstamp);
        }
        // calculating day and month by number of week and weekday
        if (array_key_exists('W', $date) && (array_key_exists('w', $date) || array_key_exists('N', $date)) && ((!(array_key_exists('d', $date)) || intval($date['d'])==0) && (!(array_key_exists('m', $date)) || intval($date['m'])==0))) {
            if (isset($date['Y']) && intval($date['Y'])>0) {
                $tmpstamp = mktime(12,0,0,1,1,intval($date['Y'])); 
            } else {
                $tmpstamp = mktime(12,0,0,1,1,date('Y'));
            }
            // rewriting sunday value for older 'w'-option
            if (array_key_exists('w', $date)) { if ($date['w']==0) { $date['N'] = 7; } else { $date['N'] = $date['w']; }}
            // calculating days to first monday of the year after 1st january 
            $dtfm = (8-(date('N', $tmpstamp))); if ($dtfm==7) { $dtfm = 0; }
            $calc = $date['N'] + ((intval($date['W'])-1)*7) + $dtfm;
            // calculating days of year after 1st january 
            if (isset($date['Y']) && intval($date['Y'])>0) {
                $calcstamp = mktime(12,0,0,1,$calc,intval($date['Y'])); 
            } else {
                $calcstamp = mktime(12,0,0,1,$calc,date('Y'));
            }
            $date['m'] = date("m", $calcstamp);
            $date['d'] = date("d", $calcstamp);
        }

        // calculating TIME with swatch time (beat)
        if (array_key_exists('B', $date) && (!(array_key_exists('H', $date)) || trim($date['H'])=='') && (!(array_key_exists('i', $date)) || trim($date['i'])=='') && (!(array_key_exists('s', $date)) || trim($date['s'])=='')) {
            if (isset($date['d']) && intval($date['d'])>0 && isset($date['m']) && intval($date['m'])>0 && isset($date['Y']) && intval($date['Y'])>0) {
                $tmpstamp = mktime(0,0,ceil((86400*intval($date['B']))/1000),intval($date['m']),intval($date['d']),intval($date['Y']));
            } else {
                $tmpstamp = mktime(0,0,ceil((86400*intval($date['B']))/1000),date('m'),date('d'),date('Y'));
            }
            if (date("I", $tmpstamp)==1) { $tmpstamp = $tmpstamp+3600; }
            $date['H'] = date("H", $tmpstamp);
            $date['i'] = date("i", $tmpstamp);
            $date['s'] = date("s", $tmpstamp);
        }

        // if some required values still missing -> set them up with actual data 
        if (!(array_key_exists('s', $date)) || trim($date['s'])=='') { $date['s'] = date('s'); $strictbreak = true; }
        if (!(array_key_exists('i', $date)) || trim($date['i'])=='') { $date['i'] = date('i'); $strictbreak = true; }
        if (!(array_key_exists('H', $date)) || trim($date['H'])=='') { $date['H'] = date('H'); $strictbreak = true; }
        if (!(array_key_exists('d', $date)) || intval($date['d'])==0) { $date['d'] = date('d'); $strictbreak = true; }
        if (!(array_key_exists('m', $date)) || intval($date['m'])==0) { $date['m'] = date('m'); $strictbreak = true; }
        if (!(array_key_exists('Y', $date)) || intval($date['Y'])==0) { $date['Y'] = date('Y'); $strictbreak = true; }

        if ($strict && $strictbreak) {
            return false;    
        } else {
            return(intval(mktime(intval($date['H']),intval($date['i']),intval($date['s']),intval($date['m']),intval($date['d']),intval($date['Y']))));
        }
    }
}

// getWSPProperties returns an array with wsp properties for multiple values or a string for ONE requested value
// input none or array with varnames that values should be returned
if (!(function_exists('getWSPProperties'))):
	function getWSPProperties($propselected = '') {
		if (!(is_array($propselected)) && trim($propselected)=='') {
			// get all properties
			$wspprop = doSQL("SELECT * FROM `wspproperties`");
			foreach ($wspprop['set'] AS $wpk => $wpv){
				$wspproperties[trim($wpv['varname'])] = $wpv['varvalue'];
			}
		} else if (!(is_array($propselected)) && trim($propselected)!='') {
			$wspproperties = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = '".escapeSQL( trim($propselected))."'");
		} else {
			if (count($propselected)==1) {
				$wspproperties = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($propselected[0]))."'");
			} 
			else {
				foreach ($propselected AS $pv) {
					$wspproperties[$pv] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = '".escapeSQL($pv)."'");
				}
			}
		}
		return $wspproperties;
	}
endif;

// reversepath zu einer gegebenen mid ermitteln => rueckgabe array mit allen mid's auf dem weg zur gegebenen mid
if (!(function_exists('returnReverseStructure'))):
	function returnReverseStructure($givenmid, $midpath = '') {
		if ($midpath==""):
			$midpath = array($givenmid);
		endif;
		$reverse_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($givenmid);
		$reverse_res = doResultSQL($reverse_sql);
		if ($reverse_res!==false):
			array_push($midpath, intval($reverse_res));
			returnReverseStructure(intval($reverse_res), $midpath);
		else:
			$GLOBALS['midpath'] = $midpath;
		endif;
		}
endif;

if (!(function_exists('returnMIDList'))):
	function returnMIDList($givenmid, $midpath = array()) {
		$reverse_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($givenmid);
		$reverse_res = doResultSQL($reverse_sql);
		if ($reverse_res!==false):
			$midpath[] = intval($givenmid);
			$midpath = returnMIDList(intval($reverse_res), $midpath);
		endif;
		return($midpath);
		}
endif;

// returns an array with all 'mid', that have a structured relation to given mid UPWARDS
if (!(function_exists('returnIDTree'))):
	function returnIDTree($mid) {
		$midtree = array();
		while ($mid>0):
			$midtree_sql = "SELECT `connected`, `level`, `mid` FROM `menu` WHERE `mid` = ".intval($mid)." AND `connected` > 0 AND `trash` != 1";
			$midtree_res = doSQL($midtree_sql);
			if ($midtree_res['num']>0):
				$midtree[intval($midtree_res['set'][0]['level'])] = intval($mid);
				$mid = intval($midtree_res['set'][0]['connected']);
			else:
				$midtree[1] = intval($mid);
				$mid = 0;
			endif;
		endwhile;
		return $midtree;
	} 
endif;

// returns an array with all 'mid', that have a structured relation to given mid DOWNWARDS
if (!(function_exists('returnIDRoot'))):
	function returnIDRoot($mid, $midlist = array()) {
		$connected_sql = "SELECT `mid` FROM `menu` WHERE `trash` != 1 AND `connected` = ".intval($mid)." ORDER BY `isindex` DESC, `position` ASC";
		$connected_res = doSQL($connected_sql);
		$midlist = array();
        if ($connected_res['num'] > 0):
			foreach($connected_res['set'] AS $crsk => $crsv):
            	$midlist[] = intval($crsv['mid']);
				$midlist = array_merge($midlist, returnIDRoot($crsv['mid'], $midlist));
			endforeach;
		endif;
		$midlist = array_unique($midlist);
		return $midlist;
	} //subMID();
endif;

// get template id for given mid up to main template
if (!(function_exists('getTemplateID'))) {
	function getTemplateID($mid) {
		$templateID = 0;
		$mid_sql = "SELECT `templates_id`, `connected` FROM `menu` WHERE `mid` = ".intval($mid);
		$mid_res = doSQL($mid_sql);
		if ($mid_res['num']>0) {
			$templateID = intval($mid_res['set'][0]['templates_id']);
			if ($templateID==0 && intval($mid_res['set'][0]['connected'])>0) {
				$templateID = getTemplateID(intval($mid_res['set'][0]['connected']));
			} else if ($templateID==0) {
				$sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'";
				$templateID = intval(doResultSQL($sql));
			}
		} else {
			$sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'";
			$templateID = intval(doResultSQL($sql));
		}
        return intval($templateID);
    }	// getTemplateID()
}

// get template id for given mid up to main template
if (!(function_exists('getTemplateVars'))) {
    function getTemplateVars($tid) {
        $tempinfo_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($tid);
		$tempinfo_res = doSQL($tempinfo_sql);
		$tempinfo = array();
		if ($tempinfo_res['num']>0) {
			$tempinfo['contentareas'] = array();
			$template_content = trim($tempinfo_res['set'][0]['template']);
			$c = '';
			while (str_replace("[%CONTENTVAR".$c."%]","[%CONTENT%]",$template_content)!=$template_content) {
				$c++;
				$tempinfo['contentareas'][] = $c;
			}
			while (str_replace("[%CONTENTVAR:".$c."%]","[%CONTENT%]",$template_content)!=$template_content) {
				$c++;
				$tempinfo['contentareas'][] = $c;
			}
			$templatevarsregexp = "!(\[%)([A-Z0-9]).*([A-Z0-9])(%\])!";
			preg_match_all($templatevarsregexp, $template_content, $arr, PREG_PATTERN_ORDER);
			$tempinfo['matches'] = $arr[0];
		}
        return $tempinfo;
    }
}

if (!(function_exists('getMIDusingTemplate'))) {
    function getMIDusingTemplate($tid = 0, $con = 0) {
        if ($tid==0 && $con==0) {
            $tid = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'"));
        }
        $sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($con)." AND `templates_id` = ".intval($tid);
        $res = doSQL($sql);
        if ($res['num']>0) { 
            foreach ($res['set'] AS $mid) {
                $tmid[] = $mid;
            }
        }
        if (count($tmid)>0) foreach ($tmid AS $mid) {
            $tmid = array_merge($tmid, getMIDusingTemplate(0, $mid));
        }
        return $tmid;
    }
}

// returns an non structured (but ordered) array of all connected menupoints to a given mid (just ONE level below)
if (!(function_exists('subpMenu'))) {
    function subpMenu($mid) {
        $returnsub = array();
        $connected_sql = "SELECT `mid` FROM `menu` WHERE trash = 0 AND `connected` = ".intval($mid)." ORDER BY `position` ASC, `visibility` ASC";
        $connected_res = doSQL($connected_sql);
        if ($connected_res['num']>0) {
            foreach ($connected_res['set'] AS $crk => $crv) {
                $returnsub[] = intval($crv['mid']);
                $subsub = subpMenu(intval($crv['mid']));
                if (is_array($subsub) && count($subsub)>0) {
                    $returnsub = array_merge($returnsub, $subsub);
                }
            }
        }
        return $returnsub;
	}
}
// subMID was replaced with subpMenu()
if (!(function_exists('subMID'))) {
	function subMID($mid) {
		addWSPMsg('errormsg', 'subMID should be replaced with subpMenu()');
        $GLOBALS['midlist'] = subpMenu($mid);
    }
}

if (!(function_exists('getChangeStat'))) {
    function getChangeStat($mid) {
        $changestat = 0;
        $changestat_sql = "SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($mid);
        $changestat_res = doResultSQL($changestat_sql);
        if ($changestat_res!==false) {
            $changestat = intval($changestat_res);
        }
        return $changestat;
	}
}

if (!(function_exists('changeMenuEntry'))) {
	function changeMenuEntry($mid, $subtomid, $oldname = '', $newname = '') {
		// get all information about existing facts
		$oldfilesql = "SELECT `connected`, `filename` FROM `menu` WHERE `mid` = ".intval($mid);
		$oldfileres = doSQL($oldfilesql);
		if ($oldfileres['num']==1) {
			// set oldname if not given 
			if (trim($oldname)=='') {
				$oldname = trim($oldfileres['set'][0]['filename']);
			}
			// given mid was found so we check for connected vs subtomid
			if ((intval($oldfileres['set'][0]['connected'])!=intval($subtomid)) || (trim($newname)!='' && trim($oldname)!=trim($newname))) {
				// if subtomid differs from connected, the menuentry was moved
				// if filenamas differ, the filename was changed 
				// for both cases, the existing structure must be republished
				// get the FULL structure from that point DOWNWARDS to update ALL paths
				$affmid = returnIDRoot(intval($mid));
				// include THIS mid to array
				array_unshift($affmid, intval($mid));
				$factset = array();
				foreach ($affmid AS $ak => $av) {
					$factsql = "SELECT `level`, `connected`, `filename`, `isindex` FROM `menu` WHERE `mid` = ".intval($av);
					$factres = doSQL($factsql);
					// add this point to set for later id comparement for connection request
					$factset[$av] = intval($factres['set'][0]['connected']);
					// the menupoint that has to be affected was found so we continue
					if ($factres['num']>0) {
						$updfactsql = "INSERT INTO `menu` SET ";
						$updfactsql.= "`level` = ".intval($factres['set'][0]['level']).", ";
						$updfactsql.= "`connected` = ".(isset($factset[$factres['set'][0]['connected']])?intval($factset[$factres['set'][0]['connected']]):0).", ";
						$updfactsql.= "`editable` = 2, ";
						$updfactsql.= "`position` = 0, ";
						$updfactsql.= "`visibility` = 0, ";
						$updfactsql.= "`description` = 'autofile-".date('Y-m-d-H-i-s')."-".escapeSQL($factres['set'][0]['filename'])."', ";
						$updfactsql.= "`filename` = '".escapeSQL($factres['set'][0]['filename'])."', ";
						$updfactsql.= "`forwarding_id` = ".intval($av).", ";
						$updfactsql.= "`contentchanged` = 4, ";
						$updfactsql.= "`changetime` = ".time().", ";
						$updfactsql.= "`isindex` = ".intval($factres['set'][0]['isindex']).", ";
						$updfactsql.= "`trash` = 1";
						$updfactres = doSQL($updfactsql);
						// setup the updated ID to $factset as VALUE
						$factset[$av] = intval($updfactres['inf']);
						// after creating menupoint, remove OLDER menupoints FROM publisher THAT are related to the changed one
						$delfactsql = "DELETE FROM `wspqueue` WHERE `done` = -1 AND `priority` = '".intval($av)."'";
						doSQL($delfactsql);
						// after creating menupoint, add THIS to publisher
						$pubfactsql = "INSERT INTO `wspqueue` SET `uid` = ".intval($_SESSION['wspvars']['userid']).", `set` = ".time().", action = 'publishitem', `param` = '".intval($updfactres['inf'])."', `timeout` = '".time()."', `done` = -1, `priority` = '".intval($av)."', `outputuid` = 0";
						doSQL($pubfactsql);
					}
				} 
				// check for changed structure
				if (intval($oldfileres['set'][0]['connected'])!=intval($subtomid)) {
					// get the level of connected
					$uplvl = doSQL("SELECT `level` FROM `menu` WHERE `mid` = ".intval($subtomid));
					$uplevel = ($uplvl['num']>0)?intval($uplvl['set'][0]['level']):0;
					$updatemidsql = "UPDATE `menu` SET `connected` = ".intval($subtomid).", `level` = ".($uplevel+1)." WHERE `mid` = ".intval($mid);
					doSQL($updatemidsql);
				}
				// check for changed filename
				if (trim($newname)!='' && trim($oldname)!=trim($newname)) {
					$updatemidsql = "UPDATE `menu` SET `filename` = '".escapeSQL(trim($newname))."' WHERE `mid` = ".intval($mid);
					doSQL($updatemidsql);
				}
				return true;
			}
			else {
				// nothing changed so nothing happens and we return 'false'
				return false;
			} 
		} else {
			// given mid wasn't found so we do nothing and return 'false'
			return false;
		}
	}
}

// uebergabe des menuepunktes, und WAS geaendert wurde
// rueckgabe des status, der zu setzen ist
if (!(function_exists('contentChangeStat'))) {
    function contentChangeStat($mid, $updated) {
        $nccres = 0; $ccres = 0; $minfo_num = 0; 
        if (trim($updated)=='content') {
            $updated = 2; // content
        } else if (trim($updated)=='structure') {
            $updated = 1; // structure
        } else if (trim($updated)=='complete') {
            $updated = 3; // structure+content
        } else if (intval($updated)>0) {
            $updated = intval($updated);
        }
        $minfo_sql = "SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($mid);
        $minfo_res = doResultSQL($minfo_sql);
        if ($minfo_res!==false) { 
            $ccres = intval($minfo_res);
        }
        if ($updated==1) {
            if ($ccres==0) { $nccres = 1; } else if ($ccres==1) { $nccres = 1; } else if ($ccres==2) { $nccres = 3; } else if ($ccres==3) { $nccres = 3; } else if ($ccres==4) { $nccres = 5; } else if ($ccres==5) { $nccres = 5; }
        } else if ($updated==2) {
            if ($ccres==0) { $nccres = 2; } else if ($ccres==1) { $nccres = 3; } else if ($ccres==2) { $nccres = 2; } else if ($ccres==3) { $nccres = 3; } else if ($ccres==4) { $nccres = 5; } else if ($ccres==5) { $nccres = 5; }
        } else if ($updated==3) {
            $nccres=5;
        }
        return $nccres;
	}
}

if (!(function_exists('isGUID'))):
function isGUID($guid) {
	if (preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $guid)):
  		return true;
	else:
  		return false;
	endif;
	}
endif;

if (!(function_exists('getMIDfromMenuvar'))):
function getMIDfromMenuvar($guid, $mid = NULL) {
	$allMIDs = array();
	// Level und Connected vom zu veröffentlichenden MP
	$mp_akt_sql = "SELECT `connected`,`level` FROM `menu` WHERE `mid` = ".intval($mid);
	$mp_akt_res = doSQL($mp_akt_sql);
	if ($mp_akt_res['num']>0):
        $mp_akt_lev = intval($mp_akt_res['set'][0]['level']);
        $mp_akt_con = intval($mp_akt_res['set'][0]['connected']);
	endif;
	
	$template_sql = "SELECT `id`,`code`,`startlevel` FROM `templates_menu` WHERE `guid` = '".escapeSQL($guid)."'";
	$template_res = doSQL($template_sql);
	if ($template_res['num']>0):
		$tid = intval($template_res['set'][0]['id']);
        $sl = intval($template_res['set'][0]['startlevel']);
        $code = trim($template_res['set'][0]['code']);
        // Testen, ob MENU.SHOW vorhanden ist 
        @preg_match_all("/MENU\.SHOW.*=.*;'/",$code, $mshow); //'
        if(is_array($mshow)):
            if(is_array($mshow[0]) && count($mshow[0])>0):
                foreach($mshow[0] AS $ms):
                    $mps = explode("=",$ms);
                    $mps_string = preg_replace("/;'/", "", trim($mps[1])); //'
                    $mps_string = preg_replace("/'/", "", trim($mps_string)); //'
                    $mps_ar = explode(";",$mps_string);
                    if(in_array($mid, $mps_ar)):
                        $allMIDs = array_merge($allMIDs,$mps_ar);
                    endif;
                endforeach;
            else:
                @preg_match_all("/LEVEL.*{/",$code, $mlevel);
                if(is_array($mlevel[0]) && count($mlevel[0])>0):
                    $level_anz = count($mlevel[0]);
                    if(($sl<=$mp_akt_lev) && (($sl+$level_anz)>$mp_akt_lev)):
                        $mt = returnIDTree($mid);
                        if($sl>1):
                            $tmp_tree = returnIDRootMaxLevel($mt[$sl-1],($sl+$level_anz));
                        else:
                            $tmp_tree = returnIDRootMaxLevel($mt[1],($sl+$level_anz));
                        endif;
                        foreach($tmp_tree AS $tmp_mid):
                            if(getTemplateID($tmp_mid)==getTemplateID($mid)):
                                $allMIDs[] = $tmp_mid;
                            endif;
                        endforeach;
                    endif;
                endif;
            endif;
		endif;
	endif;
	return $allMIDs;
	}
endif;

//if (!(function_exists('getTmplIDwithMenuvar'))):
//function getTmplIDwithMenuvar($guid, $mid = NULL) {
//	$allTmplIDs = array();
//
//	return $allTmplIDs;
//	}
//endif;


if (!(function_exists('getTmplAndMv'))):
function getTmplAndMv() {
	$allTmpls = array();
	$allTmpls['AllM'] = array();
	$allTmpls['TtoM'] = array();
	$allTmpls['MtoT'] = array();

	$template_sql = "SELECT `id`,`template` FROM `templates`";
	$template_res = doSQL($template_sql);
	if ($template_res['num']>0) {
        $allMVsTemp = array();
        foreach ($template_res['set'] AS $alltmplk => $alltmplv)
        for($alltmpl=0;$alltmpl<$template_num;$alltmpl++) {
            $template = trim($alltmplv["template"]);
            $template_id = intval($alltmplv["id"]);
            if($template!=""):
                @preg_match_all("/\[%MENUVAR:.*%\]/",$template, $mvars);
                if(is_array($mvars) && count($mvars[0])>0):
                    if(is_array($mvars[0])):
                        $alltypes = array_unique($mvars[0]);
                        $allTmpls['TtoM'][$template_id] = $alltypes;
                    else:
                        $alltypes = array();
                        $allTmpls['TtoM'][$template_id] = $alltypes;
                    endif;
                    $allMVsTemp = array_merge($allMVsTemp, $alltypes);
                    foreach($alltypes AS $mv):
                        $allTmpls['MtoT'][$mv][] = $template_id;
                    endforeach;
                endif;
            endif;
        }
        $allTmpls['AllM'] = array_unique($allMVsTemp);
    }
	return $allTmpls;
	}
endif;

// returns an array with all 'mid', that have a structured relation to given mid and a max level DOWNWARDS
if (!(function_exists('returnIDRootMaxLevel'))):
function returnIDRootMaxLevel($mid, $maxLevel, $midlist = array()) {
	$connected_sql = "SELECT `mid` FROM `menu` WHERE `trash` != 1 AND `connected` = ".intval($mid)." AND `level`<= " . intval($maxLevel) . " ORDER BY `isindex` DESC, `position` ASC";
	$connected_res = doSQL($connected_sql);
    $midlist = array();
    if ($connected_res['num'] > 0):
        foreach($connected_res['set'] AS $crsk => $crsv):
            $midlist[] = intval($crsv['mid']);
            $midlist = array_merge($midlist, returnIDRootMaxLevel($crsv['mid'], $maxLevel, $midlist));
        endforeach;
    endif;
    $midlist = array_unique($midlist);
    return $midlist;
	} //subMID();
endif;

if (!(function_exists('getFullXMPs'))):
function getFullMPs($tid) {
	$allMIDs = array();
	$fullX_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0";
	$fullX_res = doSQL($fullX_sql);
	if ($fullX_res['num']>0):
        foreach ($fullX_res AS $fxrk => $fxrv) {
            if(getTemplateID(intval($fxrv['mid']))==$tid):
                $allMIDs[] = intval($fxrv['mid']);
            endif;
        }
    endif;
	return $allMIDs;	
	}
endif;


if (!(function_exists('getEffectedMPs'))):
function getEffectedMPs($mid) {
	$effectedMPs = array();
	if($mid>0):
		$work_array = getTmplAndMv();
		$alltypes = $work_array['AllM'];
		foreach($alltypes AS $mv):
			$mtype_parts = explode(":",$mv);
			$mtype = substr($mtype_parts[1],0,strlen($mtype_parts[1])-2);
			switch ($mtype) {
				case "FULLLIST":
					foreach($work_array['MtoT'][$mv] AS $template_id):
						$effectedMPs = array_merge($effectedMPs,getFullMPs($template_id));
					endforeach;
					break;
				case "FULLSELECT":
					foreach($work_array['MtoT'][$mv] AS $template_id):
						$effectedMPs = array_merge($effectedMPs,getFullMPs($template_id));
					endforeach;
					break;
				case "HORIZONTALLIST":
					$mt = returnIDTree($mid);
					$lev = array_search($mid,$mt);
					if($lev>1):
						$effectedMPs = array_merge($effectedMPs,returnIDRoot($mt[$lev-1]));
					else:
						$effectedMPs = array_merge($effectedMPs,returnIDRoot($mt[1]));
					endif;
					
					break;
				case "HORIZONTALDIV":
					$mt = returnIDTree($mid);
					$lev = array_search($mid,$mt);
					if($lev>1):
						$effectedMPs = array_merge($effectedMPs,returnIDRoot($mt[$lev-1]));
					else:
						$effectedMPs = array_merge($effectedMPs,returnIDRoot($mt[1]));
					endif;
					break;
				case "HORIZONTALSELECT":
					break;
					$mt = returnIDTree($mid);
					$lev = array_search($mid,$mt);
					if($lev>1):
						$effectedMPs = array_merge($effectedMPs,returnIDRoot($mt[$lev-1]));
					else:
						$effectedMPs = array_merge($effectedMPs,returnIDRoot($mt[1]));
					endif;
				case "SUBLIST":
					$effectedMPs = array_merge($effectedMPs,returnIDRoot($mid));
					break;
				case "SUBDIV":
					$effectedMPs = array_merge($effectedMPs,returnIDRoot($mid));
					break;
				case "SUBSELECT":
					$effectedMPs = array_merge($effectedMPs,returnIDRoot($mid));
					break;
				case "LINKLAST":
					break;
				case "LINKNEXT":
					break;
				case "LINKUP":
					break;
				default:
					if(isGUID($mtype)):
						$effectedMPs = array_merge($effectedMPs,getMIDfromMenuvar($mtype, $mid));
					else:
					
					endif;
			}
		endforeach;
	else:
		echo "4";
	endif;
	return $effectedMPs;
	}
endif;

if (!(function_exists('getEffectedMPs_alt'))):
function getEffectedMPs_alt($mid) {
	addWSPMsg('errormsg', 'getEffectedMPs_alt is deprecated');
    return false;
	}
endif;

// returns site structure < wsp 6.7
if (!(function_exists('getMenuStructure'))) {
	// call from sitestructure.php as admin: getMenuStructure(0, array, '', integer, 'structure', 'de')
	// call from contentstructure.php as admin: getMenuStructure(0, array, '', 0, 'contents')
	// call from contentstructure.php as user: getMenuStructure(0, Array, Array, 0, 'contents')
	function getMenuStructure($parent = 0, $aSelectIDs = array(), $op = '', $showmidpath = '', $outputtype = 'structure', $showlang = 'de') {
		/* get all menu information to parent connector */
		$gms_sql = "SELECT * FROM `menu` WHERE `connected` = ".intval($parent)." ORDER BY `position`";
		$gms_res = doSQL($gms_sql);
		if ($gms_res['num']>0) {
			$getList = '';
			foreach ($gms_res['set'] AS $gmsk => $gmsv) {
				// get informationen about submenupoints
				$gmsub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($gmsv["mid"])." ORDER BY `position`";
				$gmsub_res = doSQL($gmsub_sql);
				// building array with facts
				$mpfacts = array(
					'rest' => false, // rest|riction
					'sel' => false, /* sel|ected */
					'lvl' => 1, /* l|e|v|e|l */
					'forw' => false, /* forw|arding */
					'ext' => false, /* ext|ernlink */
					'int' => false, /* int|ernlink */
					'drag' => false, /* drag|able */
					'sub' => false, /* sub|structure */
                    'dyn' => false, /* dyn|amic */
                    'doc' => false, /* doc|ument link */
					'sd' => '', /* title information */
					'act' => false, /* act|ion allowed */
					'amd' => false, /* a|ction m|enupoint d|elete */
					'amc' => false, /* a|ction m|enupoint c|lone */
					'ams' => false, /* a|ction m|enupoint s|ubmenu */
					'amv' => false, /* a|ction m|enupoint v|isibility */
					'ama' => false, /* a|ction m|enupoint a|ddcontent */
					'con' => false /* con|tent editing allowed */
					);
				// get information about access restrictions
				if (is_array($op)) {
					// access restrictions exist
					if ((in_array(intval($gmsv["mid"]), $op))) {
						$mpfacts['rest'] = false;
                    } else {
						$mpfacts['rest'] = true;
                    }
                }
				// is menupoint in array of selected menupoints
				if (is_array($aSelectIDs)) {
					// case selection is defined
					if ((in_array(intval($gmsv["mid"]), $aSelectIDs))) {
						/* case THIS point is in selection */
						$mpfacts['sel'] = true;
                    }
                }
				// get menupoint level
				$mpfacts['lvl'] = intval($gmsv["level"]);
				// get type of menupoint to set right icon
				if (trim($gmsv["docintern"])!='') {
					$mpfacts['doc'] = true;
				}
                if (intval($gmsv["forwardmenu"])==1) {
					$mpfacts['forw'] = true;
				}
				if (trim($gmsv["offlink"])!="") {
					$mpfacts['ext'] = true;
				} else if (intval($gmsv["internlink_id"])>0) {
					$mpfacts['int'] = true;
				}
				// set information about subpoints
				if ($gmsub_num>0) {
					$mpfacts['sub'] = true;
				}
				$mpfacts['subhidden'] = " <input type=\"hidden\" name=\"sub_".intval($gmsv["mid"])."\" id=\"sub_".intval($gmsv["mid"])."\" value=\"";
				if ($gmsub_res['num']>0) {
					$midblock = array();
					foreach ($gmsub_res['set'] AS $gmsubk => $gmsubv) {
						$midblock[] = intval($gmsubv['mid']);
					}
					$mpfacts['subhidden'].= implode(",", $midblock);
				}
				$mpfacts['subhidden'].= "\" />";
				// III. name and template informations
				$mpfacts['sd'] = stripslashes($gmsv["description"]);
				if (trim($mpfacts['sd'])=="") {
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				}
				$sdarray = unserializeBroken($gmsv["langdescription"]);
				if (is_array($sdarray) && trim($sdarray[$showlang])!="") {
					$mpfacts['sd'] = trim(stripslashes($sdarray[$showlang]));
					if ($mpfacts['sd']==stripslashes($gmsv["description"]) && $showlang!='de') {
						$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [".returnIntLang('int', false)."]";
                    }
                } else if ($showlang!='de') {
					$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [".returnIntLang('de', false)."]";
                }
				if (trim($mpfacts['sd'])=="") {
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				}
				if ($_SESSION['wspvars']['rights']['sitestructure']==1) {
					/* get dragdrop information */
					$mpfacts['drag'] = true;
					$mpfacts['act'] = true;
					$mpfacts['amd'] = true;
					$mpfacts['amc'] = true;
					$mpfacts['ams'] = true;
					$mpfacts['amv'] = true;
					$mpfacts['ama'] = true;
                }
				if (array_key_exists('structuremidlist', $_SESSION) && is_array($_SESSION['structuremidlist'])) {
					if ($_SESSION['wspvars']['rights']['sitestructure']==7 && in_array(intval($gmsv["mid"]), $_SESSION['structuremidlist'])) {
						/* get dragdrop information */
						$mpfacts['drag'] = true;
						$mpfacts['act'] = true;
						$mpfacts['amd'] = true;
						if (intval($gmsv["mid"])==$_SESSION['structuremidlist'][0]) {
							$mpfacts['amc'] = false;
                        } else {
							$mpfacts['amc'] = true;
                        }
						$mpfacts['ams'] = true;
						$mpfacts['amv'] = true;
						$mpfacts['ama'] = true;
                    }
				}
				// IV. start output
                if ($mpfacts['act']) {
					$getList.= "<li id=\"li_".intval($gmsv["mid"])."\">\n";
					$getList.= "<table id=\"conttab_".intval($gmsv["mid"])."\" class=\"contenttable noborder\" style=\"margin-bottom: 1px;\">";
					// development output
					if (isset($_SESSION['wspvars']['devcontent']) && $_SESSION['wspvars']['devcontent']) {
						$getList.= "<tr class=\"tablehead\"><td colspan='20'>".str_replace("\"hidden", "\"_hidden", serialize($mpfacts))."</td></tr>\n";
					}
					/* realtime output */
					$getList.= "<tr>\n";
					if ($outputtype=="structure") {
						for ($lv=0; $lv<$mpfacts['lvl']-1; $lv++) {
							$getList.= "<td nowrap><span class=\"bubblemessage hidden\">".returnIntLang('bubble showsub', false)."</span></td>\n";
						}
						$getList.= "<td nowrap id=\"showspan_".intval($gmsv["mid"])."\" >";
						/* output link, if no restriction exists */
						if (!($mpfacts['rest']) && $mpfacts['sub']) {
							/* case submenupoints exist */ 
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"addShowSub(".intval($gmsv["mid"]).", ".intval($gmsv["level"]).");\">";
						} else if (!($mpfacts['rest']) && !($mpfacts['int']) && !($mpfacts['ext']) && !($mpfacts['forw'])) {
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"addShowNew(".intval($gmsv["mid"]).", ".intval($gmsv["level"]).");\">";
						}
						$getList.= "<span class=\"bubblemessage ";
						if ($mpfacts['sub']) {
							$getList.= " orange ";
                        }
						if ($mpfacts['rest'] || (($mpfacts['int'] || $mpfacts['ext'] || $mpfacts['forw']) && !($mpfacts['sub']))) {
							$getList.= " disabled ";
                        }
						$getList.= "\">";
						if ($mpfacts['sub']) {
							$getList.= returnIntLang('bubble showsub', false);
						} else if ($mpfacts['ext']) {
							$getList.= returnIntLang('bubble externlink', false);
						} else if ($mpfacts['int']) {
							$getList.= returnIntLang('bubble internlink', false);
						} else if ($mpfacts['forw']) {
							$getList.= returnIntLang('bubble forwarder', false);
						} else if ($mpfacts['doc']) {
                            $getList.= returnIntLang('bubble document', false);
                        } else if ($mpfacts['dyn']) {
                            $getList.= returnIntLang('bubble dynamic', false);
                        } else {
							$getList.= returnIntLang('bubble showsub', false);
                        }
						$getList.= "</span>";
						if (!($mpfacts['rest'])) {
							$getList.= "</a>";
						}
						$getList.= "</td>\n";
						if ($mpfacts['drag']) {
							if (intval($gmsv["mid"])!=intval($_SESSION['structuremidlist'][0])) {
								$getList.= "<td nowrap><table id=\"handletable_".intval($gmsv["mid"])."\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\"><tr><td><div class=\"handle\" id=\"handle_".intval($gmsv["mid"])."\" style=\"cursor: move; float: right;\" onmouseover=\"document.getElementById('li_".intval($gmsv["mid"])."').className = 'hoverclass';\" onmouseout=\"document.getElementById('li_".intval($gmsv["mid"])."').className = 'nohover'; searchStructure(document.getElementById('searchStructure').value, document.getElementById('highlightstructure').value);\"><span class=\"bubblemessage orange\">".returnIntLang('bubble move', false)."</span></div></td></tr></table></td>\n";
							}
							// description and edit-click-area
							$getList.= "<td nowrap width=\"100%\">";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a style=\"cursor: pointer;\" onclick=\"editMenupoint(".intval($gmsv["mid"]).")\">".$mpfacts['sd']."</a>";
							// development
							if (isset($_SESSION['wspvars']['devcontent']) && $_SESSION['wspvars']['devcontent']) {
								$getList.= " [mid".intval($gmsv["mid"])."]";
							}
							$templatedesc = "";
							if (intval($gmsv["templates_id"])==0) {
								$templatedesc.= "^ ";
							}
							$tmplid = getTemplateID(intval($gmsv["mid"]));
							$tplname_sql = "SELECT `name` FROM `templates` WHERE `id` = ".$tmplid;
							$tplname_res = doResultSQL($tplname_sql);
							if ($tplname_res!==false) {
								$templatedesc.= trim($tplname_res);
							} else {
								// else status main template
								$templatedesc.= "undefined";
							}
                            //
							$getList.= " ".helpText(returnIntLang('str menutypestat', false).': '.returnIntLang("structure menutypestat ".$status, false).'<br />'.returnIntLang("str filename", false).': '.trim($gmsv["filename"]).'.php<br />'.returnIntLang("str template", false).': '.$templatedesc.'<br />'.returnIntLang("structure lastchange", false).': '.date("Y-m-d H:i:s", intval($gmsv["changetime"])), false)." ";	$getList.= "</span>";
							$getList.= $mpfacts['subhidden']."</td>\n";
						} else if ($_SESSION['wspvars']['rights']['sitestructure']==3 || ($_SESSION['wspvars']['rights']['sitestructure']==4 && in_array(intval($gmsv["mid"]), $_SESSION['wspvars']['rights']['sitestructure_array']))) {
							// description and edit-click-area
							$getList.= "<td width=\"100%\" nowrap>";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a style=\"cursor: pointer;\" onclick=\"editMenupoint(".intval($gmsv["mid"]).");\">".$mpfacts['sd']."</a></span><span class=\"handle\"></span>";
							$getList.= $mpfacts['subhidden']."</td>\n";
						} else if ($_SESSION['wspvars']['rights']['sitestructure']==7 && in_array(intval($gmsv["mid"]), $_SESSION['structuremidlist'])) {
							// description and edit-click-area
							$getList.= "<td width=\"100%\" nowrap>";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a href=\"".$_SERVER['PHP_SELF']."?action=edit&mid=".intval($gmsv["mid"])."\">".$mpfacts['sd']."</a></span><span class=\"handle\"></span>";
							$getList.= $mpfacts['subhidden']."</td>\n";
						} else {
							// description
							$getList.= "<td width=\"100%\" nowrap>";
							$getList.= "<span style=\"float: left; margin-right: 4px;\">".$mpfacts['sd']."</span><span class=\"handle\" style=\"display: none;\"></span>";
							$getList.= $mpfacts['subhidden']."</td>\n";
						}
						// menupoint actions
						if ($mpfacts['act']) {
							$getList.= "<td nowrap>";
							/* delete menupoint */ 
							$getList.= " <a onclick=\"confirmDelete(".intval($gmsv["mid"]).",'".str_replace("\"", "‘", $mpfacts['sd'])."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a>\n";
							// duplicate menupoint
							$getList.= " <a onclick=\"confirmClone(".intval($gmsv["mid"]).");\"><span class=\"bubblemessage orange\">".returnIntLang('bubble clone', false)."</span></a>\n";
							// ad submenupoint to THIS menupoint
							$getList.= " <a onclick=\"document.getElementById('newmenuitem').focus(); document.getElementById('subpointfrom').value = '".intval($gmsv["mid"])."' ; menuTemp('".intval($gmsv["mid"])."');\"><span class=\"bubblemessage orange\">".returnIntLang('bubble addsubmenu', false)."</span></a>\n";
							// change visibility
							if (intval($gmsv["visibility"])==1) {
								$getList.= " <a id=\"acv_" . intval($gmsv["mid"]) . "\" onclick=\"return confirmVisibility(".intval($gmsv["mid"]).", 'hide');\"><span class=\"bubblemessage green\">".returnIntLang('bubble hide', false)."</span></a>\n";
							} else {
								$getList.= " <a id=\"acv_" . intval($gmsv["mid"]) . "\" onclick=\"return confirmVisibility(".intval($gmsv["mid"]).", 'show');\"><span class=\"bubblemessage red\">".returnIntLang('bubble show', false)."</span></a>\n";
							}
							$getList.= "</td>";
						}
					} else if ($outputtype=="contents") {
						$getList.= "<td nowrap>";
						if ($mpfacts['sub']) {
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"showSub(".intval($gmsv["mid"]).", ".intval($gmsv["level"]).");\"><span class=\"bubblemessage orange\">".returnIntLang('bubble showsub', false)."</span></a>\n";
						} else if ($mpfacts['int']) {
							$getList.= "<span class=\"bubblemessage disabled\">".returnIntLang('bubble forwarder', false)."</span>\n";
				        } else {
							$getList.= "<span class=\"bubblemessage orange disabled\">".returnIntLang('bubble showsub', false)."</span>\n";
						}
						if ($mpfacts['con'] && !($mpfacts['int'])) {
							$getList.= " <a style=\"cursor: pointer;\" onclick=\"addShowContents(".intval($gmsv["mid"]).", ".intval($gmsv["level"]).");\"><span class=\"bubblemessage blue\">".returnIntLang('bubble showcontent', false)."</span></a>";
                        } else {
							$getList.= " <span class=\"bubblemessage blue disabled\">".returnIntLang('bubble showcontent', false)."</span>";
                        }
						$getList.= "</td>\n";
						// output page name
						$getList.= "<td nowrap id=\"contentheadcell_".intval($gmsv["mid"])."\">";
						$getList.= "<span style=\"float: left;\" id=\"contenthead_".intval($gmsv["mid"])."\">".$mpfacts['sd'];
						// development
						if (isset($_SESSION['wspvars']['devcontent']) && $_SESSION['wspvars']['devcontent']) {
							$getList.= " [mid".intval($gmsv["mid"])."]";
						}
						$getList.= "</span>";
						$getList.= $mpfacts['subhidden']."</td>\n";
						// output count content areas and count contents
						// get content area count
						$tempinfo_sql = "SELECT `template` FROM `templates` WHERE `id` = ".getTemplateID(intval($gmsv["mid"]));
						$tempinfo_res = doResultSQL($tempinfo_sql);
						if ($tempinfo_res!==false) {
							$template_content = trim($tempinfo_res);
							unset($c);
							$contentareas = array();
							$c=0;
							while (str_replace("[%CONTENTVAR".$c."%]","[%CONTENT%]",$template_content)!=$template_content) {
								$c++;
								$contentareas[] = $c;
							}
						}
						// get content count
						$contents = 0;
						foreach ($contentareas AS $cavalue) {
							$ccount_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($gmsv["mid"])." AND `content_area` = ".intval($cavalue);
							$ccount_res = doSQL($ccount_sql);
							$contents = $contents + $ccount_res['num'];
						}
						if ($mpfacts['con'] && !($mpfacts['int'])) {
							$getList.= "<td width=\"100%\">[".intval($contents)." ";
							if ($contents!=1) {
								$getList.= returnIntLang('str contents', true)." ";
                            } else {
								$getList.= returnIntLang('str content', true)." ";
                            }
							$getList.= returnIntLang('str in', true);
							$getList.= " ".count($contentareas)." ";
							if (count($contentareas)!=1) {
								$getList.= returnIntLang('str contentareas', true); 
                            } else {
								$getList.= returnIntLang('str contentarea', true);
                            }
							$getList.= "]</td>\n";
						} else {
							$getList.= "<td width=\"100%\">&nbsp;</td>";
						}
						// split here with rights system if adding is allowed 
						if ($mpfacts['con'] && ($_SESSION['wspvars']['rights']['contents']!=3 && $_SESSION['wspvars']['rights']['contents']!=4) && !($mpfacts['int'])) {
							$getList.= "<td nowrap><a onclick=\"addContent(".intval($gmsv["mid"]).", 0);\"><span class=\"bubblemessage orange\">".returnIntLang('bubble addcontent', false)."</span></a></td>\n";
						}
					} else if ($outputtype=="preview") {
						// thumbstat 0 = anzeigen ; thumbstat 1 = verbergen
						$getList.= "<td nowrap width=\"100%\">";
						if ($_SESSION['wspvars']['rights']['publisher']==1 || ($_SESSION['wspvars']['rights']['publisher']==2 && in_array(intval($gmsv["mid"]), $_SESSION['wspvars']['rights']['publisher_array']))) {
							$getList.= "<span class=\"handle\"></span><span style=\"float: left;\"><a onClick=\"document.getElementById('previewid').value = '".intval($gmsv["mid"])."'; document.getElementById('previewform').submit(); return false;\" style=\"cursor: pointer;\">".$mpfacts['sd']."</a> [".trim($gmsv["filename"])."]</span>";
						} else {
							$getList.= "<span class=\"handle\"></span><span style=\"float: left;\">".$mpfacts['sd']." [".trim($gmsv["filename"])."]</span>";
						}
						$getList.= $mpfacts['subhidden']."</td>\n";
                    }
					$getList.= "</tr></table>\n";
					/* add content table area */
					if ($outputtype=="contents") {
						$getList.= "<span id=\"contenttable_".intval($gmsv["mid"])."\"></span>\n";
					}
					/* add dragable submenu list */
					$getList.= "<ul id=\"ul_".intval($gmsv["mid"])."\" class=\"dragable\">";
					returnReverseStructure($showmidpath);
					if (in_array(intval($gmsv["mid"]), $GLOBALS['midpath'])) {
						$menu = getMenuStructure(intval($gmsv["mid"]), $aSelectIDs, $op, $showmidpath, $outputtype, $showlang);
						$getList.= $menu[0];
					}
					$getList.= "</ul>\n</li>\n";
				}
            }
        }
        return array($getList);
    }
}

// returns site structure with new jquery options and attributes
if (!(function_exists('getjMenuStructure'))) {
	/* call from sitestructure.php as admin: getjMenuStructure(0, array, '', array, 'structure', 'de'); */ 
	/* call from contentstructure.php as admin: getjMenuStructure(0, array, '', 0, 'contents') */
	/* call from contentstructure.php as user: getjMenuStructure(0, Array, Array, 0, 'contents') */
	/* call from publisher.php as admin: getjMenuStructure(0, array, '', array, 'publisher', lang) */
	function getjMenuStructure($parent = 0, $aSelectIDs = array(), $op = '', $showmidpath = array(), $outputtype = 'structure', $showlang = 'de') {
		// define empty output var
		$getList = '';
		/* get all menu information to parent connector */
		$gms_sql = "SELECT * FROM `menu` WHERE `trash` = 0 AND `connected` = ".intval($parent)." ORDER BY `position`";
		$gms_res = doSQL($gms_sql);
		// get count publishing required information from menu
		$pub_sql = "SELECT * FROM `menu` WHERE `trash` = 0 AND `visibility` = 1 AND `editable` = 1 AND `contentchanged` != 0";
		$pub_res = doSQL($pub_sql);
		// get template information to display in menuedit		
		$tplopt_sql = "SELECT `id`, `name` FROM `templates`";
		$tplopt_res = doSQL($tplopt_sql);
		
		// run loop
		if ($gms_res['num']>0) {
			foreach ($gms_res['set'] AS $gmsk => $gmsv) {
				/* get informationen about submenupoints */
				$gmsub_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0 AND `connected` = ".intval($gmsv["mid"])." ORDER BY `position`";
				$gmsub_res = doSQL($gmsub_sql);
                // building array with facts
				$mpfacts = array(
					'rest' => false, /* rest|riction */
					'sel' => false, /* sel|ected */
					'lvl' => 1, /* l|e|v|e|l */
					'edit' => true, /* edit|able */
					'forw' => false, /* forw|arding */
					'ext' => false, /* ext|ernlink */
					'int' => false, /* int|ernlink */
					'drag' => false, /* drag|able */
					'sub' => false, /* sub|structure */
                    'doc' => false, /* doc|ument link */
                    'dyn' => false, /* dyn|amic */
					'sd' => '', /* title information */
					'act' => false, /* act|ion allowed */
					'amd' => false, /* a|ction m|enupoint d|elete */
					'amc' => false, /* a|ction m|enupoint c|lone */
					'ams' => false, /* a|ction m|enupoint s|ubmenu */
					'amv' => false, /* a|ction m|enupoint v|isibility */
					'ama' => false, /* a|ction m|enupoint a|ddcontent */
					'con' => false, /* con|tent editing allowed */
					'que' => false, /* in |que|ue */
					);
				/* get information about access restrictions */
				if (is_array($op)) {
					/* access restrictions exist */
					if ((in_array(intval($gmsv["mid"]), $op))):
						$mpfacts['rest'] = false;
					else:
						$mpfacts['rest'] = true;
					endif;
				}
				/* is menupoint in array of selected menupoints */
				if (is_array($aSelectIDs)) {
					/* case selection is defined */
					if ((in_array(intval($gmsv["mid"]), $aSelectIDs))) {
						/* case THIS point is in selection */
						$mpfacts['sel'] = true;
					}
				}
				/* get menupoint level */
				$mpfacts['lvl'] = intval($gmsv["level"]);
				/* get type of menupoint to set right icon */
				if (!empty($gmsv["docintern"])) { $mpfacts['doc'] = true; $mpfacts['ext'] = true; }
                if (intval($gmsv["forwardmenu"])==1) { $mpfacts['forw'] = true; }
				if (!empty($gmsv["offlink"])) { $mpfacts['ext'] = true; }
				else if (intval($gmsv["internlink_id"])>0) { $mpfacts['int'] = true; }
				// get editable stat
				if (intval($gmsv["editable"])==0 || intval($gmsv["editable"])==2) { $mpfacts['edit'] = false; }
                if (intval($gmsv["editable"])==9) { $mpfacts['dyn'] = true; }
                // set information about subpoints
				$mpfacts['subhidden'] = " <input type=\"_hidden\" name=\"sub_".intval($gmsv["mid"])."\" id=\"sub_".intval($gmsv["mid"])."\" value=\"";
				if ($gmsub_res['num']>0) {
                    $mpfacts['sub'] = true;
					$midblock = array();
					foreach ($gmsub_res['set'] AS $gmssk => $gmssv) {
						$midblock[] = intval($gmssv['mid']);
                    }
					$mpfacts['subhidden'].= implode(",", $midblock);
				}
				$mpfacts['subhidden'].= "\" />";
				// II. get content informations
				if ($_SESSION['wspvars']['rights']['contents']==1) {
					$mpfacts['con'] = true;
					$mpfacts['act'] = true;
                } else {
					if (is_array($op)) {
						if (in_array(intval($gmsv["mid"]), $op)) {
							$mpfacts['act'] = true;
						}
                    } else {
						$mpfacts['act'] = true;
						$mpfacts['con'] = true;
                    }
					if (is_array($aSelectIDs)) {
						if (in_array(intval($gmsv["mid"]), $aSelectIDs)) {
							$mpfacts['con'] = true; 
                        }
                    }
                }
				// 3. namen und templateinformationen
				$mpfacts['sd'] = stripslashes(trim($gmsv["description"]));
				if (trim($mpfacts['sd'])==""):
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				endif;
				$sdarray = unserializeBroken($gmsv["langdescription"]);
				$dlarray = unserializeBroken($gmsv["denylang"]);
				if (is_array($sdarray) && array_key_exists($showlang, $sdarray) && trim($sdarray[$showlang])!="" && count($_SESSION['wspvars']['lang'])>1) {
					$mpfacts['sd'] = trim(stripslashes($sdarray[$showlang]));
					if ($mpfacts['sd'] == stripslashes(trim($gmsv["description"])) && $showlang!='de') {
						$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [int]";
					}
				} else if ($showlang!='de') {
					$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [".$_SESSION['wspvars']['wspbaselang']."]";
				}
				if (trim($mpfacts['sd'])=="") {
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				}
				if ($_SESSION['wspvars']['rights']['sitestructure']==1) {
					/* get dragdrop information */
					$mpfacts['drag'] = true;
					$mpfacts['act'] = true;
					$mpfacts['amd'] = true;
					$mpfacts['amc'] = true;
					$mpfacts['ams'] = true;
					$mpfacts['amv'] = true;
					$mpfacts['ama'] = true;
				}
				if (isset($_SESSION['structuremidlist']) && is_array($_SESSION['structuremidlist'])) {
					if ($_SESSION['wspvars']['rights']['sitestructure']==7 && in_array(intval($gmsv["mid"]), $_SESSION['structuremidlist'])) {
						/* get dragdrop information */
						$mpfacts['drag'] = true;
						$mpfacts['act'] = true;
						$mpfacts['amd'] = true;
						if (intval($gmsv["mid"])==$_SESSION['structuremidlist'][0]) {
							$mpfacts['amc'] = false;
                        } else {
							$mpfacts['amc'] = true;
                        }
						$mpfacts['ams'] = true;
						$mpfacts['amv'] = true;
						$mpfacts['ama'] = true;
                    }
                }
				// get information of files stored in queue
				$q_sql = "SELECT `id` FROM `wspqueue` WHERE `param` = ".intval($gmsv["mid"])." AND `done` = 0";
				$q_res = doSQL($q_sql);
				if ($q_res['num']>0) { $mpfacts['que'] = true; }
				// start output
				if ($mpfacts['act']) {
					$getList.= "<li id=\"li_".intval($gmsv["mid"])."\" class=\"moveable\">\n";
					$getList.= "<ul id=\"conttab_".intval($gmsv["mid"])."\" class=\"tablelist ";
					if ($outputtype=="structure") {
						$getList.= " structure ";
						if (intval($gmsv["visibility"])==0) {
							$getList.= " hiddenstructure ";
                        } else {
							if (is_array($dlarray) && in_array($showlang, $dlarray)) {
								$getList.= " hiddenstructure ";
                            } else {
								$getList.= " shownstructure ";
                            }
                        }
					}
					$getList.= " level-".$mpfacts['lvl']." ";
					$getList.= "\">";
					
					if ($outputtype=="structure") {
                        // avaiable options
						$getList.= "<li class=\"tablecell two\">";
						// show sublist
						if (!($mpfacts['ext'])) {
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"showSub(".intval($gmsv["mid"]).", ".intval($gmsv["level"]).");\">";
                        }
						$getList.= "<span id=\"showspan_".intval($gmsv["mid"])."\" class=\"bubblemessage ";
						if ($mpfacts['sub']) {
							$getList.= " orange ";
						}
						if ($mpfacts['ext']) {
							$getList.= " disabled ";
						}
						$getList.= "\" ";
						if ($mpfacts['sub']) {
							$getList.= " title='".returnIntLang('bubble showsub icondesc', false)."' ";
						} else if ($mpfacts['doc']) {
							$getList.= " title='".returnIntLang('bubble document icondesc', false)."' ";
						} else if ($mpfacts['ext']) {
							$getList.= " title='".returnIntLang('bubble externlink icondesc', false)."' ";
						} else if ($mpfacts['int']) {
							$getList.= " title='".returnIntLang('bubble internlink icondesc', false)."' ";
						} else if ($mpfacts['forw']) {
							$getList.= " title='".returnIntLang('bubble forwarder icondesc', false)."' ";
						} else {
							$getList.= " title='".returnIntLang('bubble showsub nosub icondesc', false)."' ";
                        }
						$getList.= ">";
						if ($mpfacts['sub'] && !($mpfacts['dyn'])) {
							$getList.= returnIntLang('bubble showsub', false);
                            $status = 'structure';
						} else if ($mpfacts['doc']) {
							$getList.= returnIntLang('bubble document', false);
                            $status = 'document';
                            $mpfacts['ams'] = false;
						} else if ($mpfacts['ext']) {
							$getList.= returnIntLang('bubble externlink', false);
                            $status = 'extern';
						} else if ($mpfacts['int']) {
							$getList.= returnIntLang('bubble internlink', false);
                            $status = 'forwarding';
						} else if ($mpfacts['forw']) {
							$getList.= returnIntLang('bubble forwarder', false);
                            $status = 'forwarding';
						} else if ($mpfacts['dyn']) {
                            $getList.= returnIntLang('bubble dynamic', false);
                            $status = 'dynamic';
                        } else {
							$getList.= returnIntLang('bubble showsub', false);
                            $status = 'structure';
                        }
						$getList.= "</span>";
						if (!($mpfacts['ext'])) {
							$getList.= "</a>";
						}
						
						if ($mpfacts['drag']) {
							// dragable
							if (!(key_exists(0, $_SESSION['structuremidlist'])) || intval($gmsv["mid"])!=intval($_SESSION['structuremidlist'][0])) {
								if (array_key_exists('useiconfont', $_SESSION['wspvars']) && $_SESSION['wspvars']['useiconfont']==1) {
									$getList.= " <span class=\"icon orange handle\" id=\"handle_".intval($gmsv["mid"])."\">".returnIntLang('icon move', false)."</span> ";
                                } else {
									$getList.= " <span class=\"bubblemessage orange handle\" id=\"handle_".intval($gmsv["mid"])."\" title='".returnIntLang('bubble move structure icondesc', false)."'>".returnIntLang('bubble move', false)."</span> ";
                                }
                            }
							// edit icon
                            $getList.= " <a style=\"cursor: pointer;\" onclick=\"editMenupoint(".intval($gmsv["mid"]).")\" title='".returnIntLang('bubble edit structure icondesc', false)."'><span class=\"bubblemessage orange\">".returnIntLang('bubble edit', false)."</span></a>";
							// menupoint actions
							if ($mpfacts['act']) {
								// delete menupoint
								$getList.= " <a onclick=\"confirmDelete(".intval($gmsv["mid"]).",'".str_replace("\"", "’", $mpfacts['sd'])."');\">";
								$getList.= "<span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span>";
								$getList.= "</a>\n";
								// duplicate menupoint
								$getList.= " <a onclick=\"confirmClone(".intval($gmsv["mid"]).",'".$mpfacts['sd']."');\">";
								$getList.= "<span class=\"bubblemessage orange\">".returnIntLang('bubble clone', false)."</span>";
								$getList.= "</a>\n";
								if (!($mpfacts['dyn']) && $mpfacts['ams']):
                                    // ad submenupoint to THIS menupoint
                                    $getList.= " <a onclick=\"addCreateSub(".intval($gmsv["mid"]).")\">";
                                    if (array_key_exists('useiconfont', $_SESSION['wspvars']) && $_SESSION['wspvars']['useiconfont']==1):
                                        $getList.= "<span class=\"icon orange\">".returnIntLang('icon addsubmenu', false)."</span>";
                                    else:
                                        $getList.= "<span class=\"bubblemessage orange\">".returnIntLang('bubble addsubmenu', false)."</span>";
                                    endif;
                                    $getList.= "</a>\n";
                                else:
                                    $getList.= "<span class=\"bubblemessage orange disabled\">".returnIntLang('bubble addsubmenu', false)."</span>";
                                endif;
								// change visibility
								if (intval($gmsv["visibility"])==0):
									$getList.= " <a id=\"acv_" .intval($gmsv["mid"])."\" onclick=\"return confirmVisibility(".intval($gmsv["mid"]).");\">";
									$getList.= "<span class=\"bubblemessage red\">".returnIntLang('bubble show', false)."</span>";
									$getList.= "</a>\n";
								else:
									if (is_array($dlarray) && in_array($showlang, $dlarray)):
										$getList.= " <a id=\"acv_".intval($gmsv["mid"])."\" onclick=\"return confirmVisibility(".intval($gmsv["mid"]).");\">";
										$getList.= "<span class=\"bubblemessage red\">".returnIntLang('bubble show', false)."</span>";
										$getList.= "</a>\n";
									else:
										$getList.= " <a id=\"acv_".intval($gmsv["mid"])."\" onclick=\"confirmVisibility(".intval($gmsv["mid"]).");\">";
										$getList.= "<span class=\"bubblemessage green\">".returnIntLang('bubble hide', false)."</span>";
										$getList.= "</a>\n";
									endif;
								endif;
							}
							$getList.= "</li>";
							// end of options
							// description and edit-click-area
							$getList.= "<li class=\"tablecell three\">";
							$getList.= "<span id=\"\" class=\"levelclass\"></span>";
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"editMenupoint(".intval($gmsv["mid"]).")\">".$mpfacts['sd']."</a>";
							// development
							if (isset($_SESSION['wspvars']['devcontent']) && $_SESSION['wspvars']['devcontent']):
								$getList.= " [mid".intval($gmsv["mid"])."]";
							endif;
							// gathering information about the menupoint for the helpbox
							$templatedesc = "";
							if (intval($gmsv["templates_id"])==0):
								$templatedesc.= "^ ";
							endif;
							$tmplid = getTemplateID(intval($gmsv["mid"]));
							$tplname_sql = "SELECT `name` FROM `templates` WHERE `id` = ".$tmplid;
							$tplname_res = doResultSQL($tplname_sql);
							if (trim($tplname_res)!=''):
								$templatedesc.= $tplname_res;
							else:
								// else status main template
								$templatedesc.= returnIntLang('str undefined', false);
							endif;
							
                            if (intval($gmsv["changetime"])>10000): $changedate = date("Y-m-d H:i:s", intval($gmsv["changetime"])); else: $changedate = returnIntLang('structure lastchange not set', false); endif;
							
							// infobox
							$getList.= " ".helpText(
								returnIntLang('str menutypestat', false).': '.
								returnIntLang("structure menutypestat ".$status, false).'<br />'.
								returnIntLang("str filename", false).': '.trim($gmsv["filename"]).'<br />'.
								returnIntLang("str template", false).': '.$templatedesc.'<br />'.
								returnIntLang("structure lastchange", false).': '.$changedate.'<br />'.
								returnIntLang("structure mid", false).': '.intval($gmsv["mid"])
                                , false)." ";
							
							// lockpage vs. bindcontentview
							if ((isset($_SESSION['wspvars']['bindcontentview']) && intval($_SESSION['wspvars']['bindcontentview'])==1) && intval($gmsv["lockpage"])==1) {
                                $getList.= " <span class=\"bubblemessage\">".returnIntLang('structure bubble contentlock2', false)."</span> ";
                            } else if (intval($gmsv["lockpage"])==1) {
                                $getList.= " <span class=\"bubblemessage\">".returnIntLang('structure bubble contentlock1', false)."</span> ";
                            }
							// is index file
							if (intval($gmsv["isindex"])==1) { 
                                $getList.= " <span class=\"bubblemessage green\">".returnIntLang('structure bubble indexpage', false)."</span> ";
                            }
							// has user limit
							if (intval($gmsv["login"])==1) { 
                                $getList.= " <span class=\"bubblemessage blue\">".returnIntLang('structure bubble locked', false)."</span> "; 
                            }
							// has images
							if (!empty($gmsv["imageon"]) || !empty($gmsv["imageoff"]) || !empty($gmsv["imageakt"]) || !empty($gmsv["imageclick"])) { 
                                $getList.= " <span class=\"bubblemessage blue\">".returnIntLang('structure bubble imginfo', false)."</span> ";
                            }
							// has time limit
							if (!empty($gmsv["showtime"]) || intval($gmsv["weekday"])>0) { 
                                $getList.= " <span class=\"bubblemessage blue\">".returnIntLang('structure bubble time', false)."</span> "; 
                            }
							
							$getList.= "</li>";
	
						} else if ($_SESSION['wspvars']['rights']['sitestructure']==3 || ($_SESSION['wspvars']['rights']['sitestructure']==4 && in_array(intval($gmsv["mid"]), $_SESSION['wspvars']['rights']['sitestructure_array']))) {
							// description and edit-click-area
							$getList.= "<li class=\"tablecell three\">";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a style=\"cursor: pointer;\" onclick=\"editMenupoint(".intval($gmsv["mid"]).");\">".$mpfacts['sd']."</a></span><span class=\"handle\"></span>";
							$getList.= "</li>\n";
						} else if ($_SESSION['wspvars']['rights']['sitestructure']==7 && in_array(intval($gmsv["mid"]), $_SESSION['structuremidlist'])) {
							// description and edit-click-area
							$getList.= "<li class=\"tablecell three\">";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a href=\"".$_SERVER['PHP_SELF']."?action=edit&mid=".intval($gmsv["mid"])."\">".$mpfacts['sd']."</a></span><span class=\"handle\"></span>";
							$getList.= "</li>\n";
						} else {
							// description
							$getList.= "<li class=\"tablecell three\">";
							$getList.= "<span style=\"float: left; margin-right: 4px;\">".$mpfacts['sd']."</span><span class=\"handle\" style=\"display: none;\"></span>";
							$getList.= "</li>\n";
                        }

						if ($tplopt_res['num']>0) {
							// preview button
							$getList.= "<li class=\"tablecell one alignright\">";
							foreach ($tplopt_res['set'] AS $tplok => $tplov) {
								$getList.= "<div class='chstpl mid".intval($gmsv["mid"])." tpl".intval($tplov['id'])."' ";
								if (intval($tmplid)!=intval($tplov['id'])): $getList.= " style='display: none;' "; endif;
								$getList.= "><a href=\"showpreview.php?previewid=".intval($gmsv["mid"])."&previewlang=".$_SESSION['wspvars']['workspacelang']."&previewtpl=".intval($tplov['id'])."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('structure bubble preview', false)."</span></a>&nbsp;</div>";
							}			
							$getList.= "</li>";
							// template chooser
							$getList.= "<li class=\"tablecell two\">";
							$getList.= "<select id='usepreviewtemplate-".intval($gmsv["mid"])."' class='one full' onchange='showPreviewTemplate(".intval($gmsv["mid"]).", this.value)'>";
							$getList.= "<option value='".$tmplid."'>".returnIntLang('structure choose preview template')."</option>";
							foreach ($tplopt_res['set'] AS $tplok => $tplov) {
								$getList.= "<option value='".intval($tplov['id'])."' ";
								if (intval($tmplid)==intval($tplov['id'])): $getList.= " selected='selected' "; endif;
								$getList.= ">".setUTF8(trim($tplov['name']))."</option>";	
                            }
							$getList.= "</select></li>";
                            }
						else {
							$getList.= "<li class=\"tablecell three alignright\"><a href=\"showpreview.php?previewid=".intval($gmsv["mid"])."&previewlang=".$_SESSION['wspvars']['workspacelang']."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('publisher bubble preview', false)."</span></a>&nbsp;</li>";
						}
					} else if ($outputtype=="contents") {
						if (!($mpfacts['ext'] && !($mpfacts['sub'])) && !($mpfacts['forw'] && !($mpfacts['sub'])) && !($mpfacts['int'] && !($mpfacts['sub']))) {
                            // show only lines not forwarding and NO subs
                            // start output
							$getList.= "<li class=\"tablecell two\">";
							// show sublist
							if ($mpfacts['sub']) {
								$getList.= "<a style=\"cursor: pointer;\" onclick=\"showSub(".intval($gmsv["mid"]).", ".intval($gmsv["level"]).");\">";
							}
							$getList.= "<span id=\"showspan_".intval($gmsv["mid"])."\" class=\"bubblemessage ";
							if ($mpfacts['sub']) {
								$getList.= " orange ";
							}
							if (!($mpfacts['sub'])) {
								$getList.= " disabled ";
							}
							$getList.= "\">";
							if ($mpfacts['dyn']) {
								$getList.= returnIntLang('bubble dynamic', false);
							} else if ($mpfacts['sub']) {
								$getList.= returnIntLang('bubble showsub', false);
							} else if ($mpfacts['int']) {
								$getList.= returnIntLang('bubble internlink', false);
							} else {
								$getList.= returnIntLang('bubble showsub', false);
							}
							$getList.= "</span>";
							if ($mpfacts['sub']) {
								$getList.= "</a>";
							}
							// output count content areas and count contents
							// get content area count
							$realtemp = getTemplateID(intval($gmsv["mid"]));
							$templatevars = getTemplateVars($realtemp);
							// get content count
							$contents = 0;
							foreach ($templatevars['contentareas'] AS $cavalue) {
								$ccount_sql = "SELECT `cid` FROM `content` WHERE `trash` = 0 AND `mid` = ".intval($gmsv["mid"])." AND `content_area` = ".intval($cavalue);
								$ccount_sql.= " AND (`content_lang` = '".escapeSQL(trim($_SESSION['wspvars']['workspacelang']))."' OR `content_lang` = '')";
								$ccount_res = doSQL($ccount_sql);
								$contents = $contents + $ccount_res['num'];
							}
							// if contentdisplay is allowed
							if (count($templatevars['contentareas'])>0 && $mpfacts['edit']) {
								if ($mpfacts['con'] && !($mpfacts['int'])) {
									$getList.= " <a style=\"cursor: pointer;\" onclick=\"showContent(".intval($gmsv["mid"]).", ".intval($gmsv["level"]).");\"><span class=\"bubblemessage blue\">".returnIntLang('bubble showcontent', false)."</span></a>";
								} else {
									$getList.= " <span class=\"bubblemessage blue disabled\">".returnIntLang('bubble showcontent', false)."</span>";
								}
							} else {
								$getList.= " <span class=\"bubblemessage hidden\">".returnIntLang('bubble showcontent', false)."</span>";
							}
							// if adding is allowed 
							if (count($templatevars['contentareas'])>0 && $mpfacts['edit']) {
								if ($mpfacts['con'] && ($_SESSION['wspvars']['rights']['contents']!=3 && $_SESSION['wspvars']['rights']['contents']!=4) && !($mpfacts['int']) && $gmsv['editable']!=7) {
									$getList.= " <a onclick=\"addContent(".intval($gmsv["mid"]).", 1);\"><span class=\"bubblemessage orange\">".returnIntLang('bubble addcontent', false)."</span></a>\n";
                                } else {
									/* these contents should come from dynamic -> so ADDING is not allowed (but editing) */
									$getList.= " <span class=\"bubblemessage orange disabled\">".returnIntLang('bubble addcontent', false)."</span>";
								}
							} else {
								$getList.= " <span class=\"bubblemessage hidden\">".returnIntLang('bubble addcontent', false)."</span>";
							}
							// preview content
							$getList.= " <a href=\"showpreview.php?previewid=".intval($gmsv["mid"])."&previewlang=".$_SESSION['wspvars']['workspacelang']."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('publisher bubble preview', false)."</span></a>";
							$getList.= "</li>";
						
							// output page name
							$getList.= "<li class=\"tablecell four\" id=\"contenthead_".intval($gmsv["mid"])."\">";
							$getList.= $mpfacts['sd'];
							// development
							if (isset($_SESSION['wspvars']['devcontent']) && $_SESSION['wspvars']['devcontent']) {
								$getList.= " [mid".intval($gmsv["mid"])."]";
							}
							$getList.= "</li>\n";
							if ($mpfacts['con'] && !($mpfacts['int']) && $mpfacts['edit']) {
								$getList.= "<li class=\"tablecell two\">";
								$getList.= "[".intval($contents)." ";
								if ($contents!=1):
									$getList.= returnIntLang('str contents', true)." ";
								else:
									$getList.= returnIntLang('str content', true)." ";
								endif;
								$getList.= returnIntLang('str in', true);
								$getList.= " ".count($templatevars['contentareas'])." ";
								if (count($templatevars['contentareas'])!=1):
									$getList.= returnIntLang('str contentareas', true); 
								else:
									$getList.= returnIntLang('str contentarea', true);
								endif;
								$getList.= "]</li>\n";
							} else {
								$getList.= "<li class=\"tablecell two\">&nbsp;</li>";
							}
                        } // end show only lines not forwarding and NO subs
                    }
					$getList.= "</ul>";
					
					if ($outputtype=="contents") {
						$getList.= "<ul id=\"ulc_".intval($gmsv["mid"])."\" class=\"tablelist sub sortable\" style=\"margin: 0px; width: 100%; padding: 0px; display: none;\"></ul>";
						$getList.= "<ul id=\"ul_".intval($gmsv["mid"])."\" class=\"tablelist sub sortable\" style=\"";
						if (is_array($showmidpath) && in_array(intval($gmsv["mid"]), $showmidpath)):
							$getList.= "display: block;";
						else:
							$getList.= "display: none;";
						endif;
						$getList.= "margin: 0px; width: 100%; padding: 0px; \" >";
					} else {
						$getList.= "<ul id=\"ul_".intval($gmsv["mid"])."\" class=\"tablelist sub sortable\" style=\"";
						if (is_array($showmidpath) && in_array(intval($gmsv["mid"]), $showmidpath)):
							$getList.= "display: block;";
						else:
							$getList.= "display: none;";
						endif;
						$getList.= "margin: 0px; width: 100%; padding: 0px; \" >";
					}
					if (is_array($showmidpath) && in_array(intval($gmsv["mid"]), $showmidpath)) {
						$getList.= getjMenuStructure(intval($gmsv["mid"]), $aSelectIDs, $op, $showmidpath, $outputtype, $showlang);
					}
					
					$getList.= "</ul>\n";
					$getList.= "</li>\n";
                }
            }
			if ($parent!=0 && $outputtype!="publisher") {
				$getList.= "<li class=\"structurelistspacer\"></li>";
			}
		}
		
		return $getList;
    } // getjMenuStructure
}
// returns site structure for publisher with new jquery options and attributes
if (!(function_exists('getPublisherStructure'))) {
	/* call from publisher.php as admin: getPublisherStructure(0, array, array, lang, selector, search) */
	function getPublisherStructure($parent = 0, $aSelectIDs = array(), $showmidpath = array(), $publishlang = 'de', $select = 'all', $search = '') {
		// define empty output var
		$getList = '';
		// get all menu information to parent connector
		$gms_sql = "SELECT * FROM `menu` WHERE `trash` = 0 AND `connected` = ".intval($parent)." ORDER BY `position`";
		$gms_res = doSQL($gms_sql);
		if ($gms_res['num']>0) {
			foreach ($gms_res['set'] AS $gmsk => $gmsv) {
				// get informationen about submenupoints
				$gmsub_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0 AND `connected` = ".intval($gmsv["mid"])." ORDER BY `position`";
				$gmsub_res = doSQL($gmsub_sql);
				// building array with facts
				$mpfacts = array(
					'edit' => true, /* edit|able */
					'forw' => false, /* forw|arding */
					'ext' => false, /* ext|ernlink */
					'sub' => false, /* sub|structure */
					'sd' => '', /* title information */
					'con' => false, /* con|tent editing allowed */
					'que' => false, /* in |que|ue */
                    'dyn' => false /* dyn|amic contents */
					);
				// get editable stat
				if (intval($gmsv["editable"])==0 || intval($gmsv["editable"])==2) { $mpfacts['edit'] = false; }
                // get some dynamic stat
                if (intval($gmsv["editable"])==9) { $mpfacts['dyn'] = true; }
				// get type of menupoint to set right icon
				if (intval($gmsv["forwardmenu"])==1) {
					$mpfacts['forw'] = true;
				}
				if (trim($gmsv["offlink"])!="") {
					$mpfacts['ext'] = true;
				}
				// set information about subpoints
				if ($gmsub_res['num']>0) {
					$mpfacts['sub'] = true;
				}
				// 2. contentinformationen sammeln
				// not required for publisher
				// 3. namen und templateinformationen
				$mpfacts['sd'] = stripslashes(trim($gmsv["description"]));
				if (trim($mpfacts['sd'])=="") {
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				}
				$sdarray = unserializeBroken($gmsv["langdescription"]);
				if (is_array($sdarray) && array_key_exists($publishlang, $sdarray) && trim($sdarray[$publishlang])!="" && count($_SESSION['wspvars']['lang'])>1) {
					$mpfacts['sd'] = trim(stripslashes($sdarray[$publishlang]));
					if ($mpfacts['sd']==stripslashes(trim($gmsv["description"])) && $publishlang!='de') {
						$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [int]";
					}
				} else if ($publishlang!='de') {
					$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [".$_SESSION['wspvars']['wspbaselang']."]";
				}
				if (trim($mpfacts['sd'])=="") {
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				}
                // 4. queue information
				$q_sql = "SELECT `id` FROM `wspqueue` WHERE `param` = ".intval($gmsv["mid"])." AND `done` = 0";
				$q_res = doSQL($q_sql);
				if ($q_res['num']>0): $mpfacts['que'] = true; endif;
				// start output
				// show only menu with subpoints, if no defined extern forwarding or editable
				if ((!($mpfacts['edit']) && !($mpfacts['sub'])) || ($mpfacts['ext'] && !($mpfacts['sub'])) || ($mpfacts['forw'] && !($mpfacts['sub']))) {
					// not editable & no subs
					// extern link & no subs
					// forwarding & no subs
                } else {
					// show only menupoints with selected publish attribute
					// case: all
					// case: only publish required
					// case: 
					$selected = true;
					if ($select=='publishrequired' && intval($gmsv["contentchanged"])==0):
						$selected = false;	
					endif;
					if ($select=='publishcontent' && (intval($gmsv["contentchanged"])!=2 && intval($gmsv["contentchanged"])!=3 && intval($gmsv["contentchanged"])!=5)):
						$selected = false;
					endif;
					if ($select=='publishstructure' && (intval($gmsv["contentchanged"])!=1)):
						$selected = false;
					endif;
					
					if ($selected && trim($search)!='') {
						$selected = false;
						if (stristr(trim($gmsv["description"]), trim($search))) {
							$selected = true;
                        } else if (stristr(trim(returnPath(intval($gmsv["mid"]), 2)), trim($search))) {
							$selected = true;
                        }
					}
					
					if ($selected) {
						$getList.= "<tr>";
						$getList.= "<td class=\"tablecell two ";
						if ($mpfacts['edit']) {
							$getList.= " itempublish ";
							// adding information publishing required
							if (intval($gmsv["contentchanged"])>0): $getList.= " publishrequired "; endif;
							if (intval($gmsv["contentchanged"])==1 || intval($gmsv["contentchanged"])>2): $getList.= " publishstructure "; endif;
							if (intval($gmsv["contentchanged"])==2 || intval($gmsv["contentchanged"])==3 || intval($gmsv["contentchanged"])==5): $getList.= " publishcontent "; endif;
							// adding information in queue
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
							$getList.= " item".intval($gmsv["mid"])."\" ";
							if ($_SESSION['wspvars']['rights']['publisher']<100) {
								$getList.= "onclick=\"selectPublish('item".intval($gmsv["mid"])."','item'); return true;\"";
                            }
							$getList.= "><span class=\"levelclass\"></span>";
						} else {
							$getList.= " locked ";
							$getList.= "\"><span class=\"levelclass\"></span>";
                        }
						$getList.= $mpfacts['sd'];
						if (intval($gmsv["isindex"])==1): $getList.= " <span class=\"bubblemessage green\">ROOT</span>"; endif;
                        if ($mpfacts["dyn"]): $getList.= " <span class=\"bubblemessage orange\">DYNAMIC ▾</span>"; endif;
						if ($mpfacts['edit']) {
							if (intval($gmsv["contentchanged"])==1) {
								$getList.= " <span class=\"bubblemessage\">MENU</span>";
							} else if (intval($gmsv["contentchanged"])==2) {
								$getList.= " <span class=\"bubblemessage\">CNT</span>";
							} else if (intval($gmsv["contentchanged"])==3) {
								$getList.= " <span class=\"bubblemessage\">MENU+CNT</span>";
							} else if (intval($gmsv["contentchanged"])==4) {
								$getList.= " <span class=\"bubblemessage\">STCR</span>";
							} else if (intval($gmsv["contentchanged"])==5) {
								$getList.= " <span class=\"bubblemessage\">STCR+CNT</span>";
                            }
						} else {
							$getList.= " <span class=\"bubblemessage red\">LOCKED</span>";
						}
						// output some more facts ?!?!!?
						$getList.= "</td>\n";
						if ($mpfacts['edit']) {
							$getList.= "<td class=\"tablecell four itempublish ";
							if (intval($gmsv["contentchanged"])>0): $getList.= " publishrequired "; endif;
							if (intval($gmsv["contentchanged"])==1 || intval($gmsv["contentchanged"])>2): $getList.= " publishstructure "; endif;
							if (intval($gmsv["contentchanged"])==2 || intval($gmsv["contentchanged"])==3 || intval($gmsv["contentchanged"])==5): $getList.= " publishcontent "; endif;
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
							$getList.= " item".intval($gmsv["mid"])."\" ";
							if ($_SESSION['wspvars']['rights']['publisher']<100) {
								$getList.= "onclick=\"selectPublish('item".intval($gmsv["mid"])."','item'); return true;\"";
							}
							$getList.= ">";
						} else {
							$getList.= "<td class=\"tablecell four locked\">";
						}
						// display filenames
						$getName = "";
						if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1) {
							if (intval($gmsv["isindex"])==1 && intval($gmsv["connected"])==0) {
								$getName.= "/";
							} else if (intval($gmsv["isindex"])==1) {
								if (intval($gmsv["level"])==1) {
									$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(intval($gmsv["mid"]), 1)."/"));
								} else {
									$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(intval($gmsv["mid"]), 1)."/"));
								} 
							} else {
								$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(intval($gmsv["mid"]), 1)."/"));
							}
						} else {
							if (intval($gmsv["isindex"])==1 && intval($gmsv["level"])==1) {
								$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(intval($gmsv["mid"]), 0)."/index.php"));
							} else {
								$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(intval($gmsv["mid"]), 2)));
							}
						}
						// shorten toooooo long names ...
						if (strlen(trim($getName))>60) {
							$getNameEx = explode("/", $getName);
							if (is_array($getNameEx) && count($getNameEx)>1) {
								foreach($getNameEx AS $gNk => $gNv) {
									if (strlen($gNv)>10 && $gNk<(count($getNameEx)-2)) {
										$getNameEx[$gNk] = substr($gNv,0,8)."...";
									}
								}
								$getName = implode("/", $getNameEx);
							}
						}
						$getList.= $getName;					
						$getList.= "</td>";
						// show date & time of last succesfull publishing action
						$getList.= "<td class=\"tablecell one ";
						if ($mpfacts['edit']) {
							$getList.= " itempublish ";
							if (intval($gmsv["contentchanged"])>0): $getList.= " publishrequired "; endif;
							if (intval($gmsv["contentchanged"])==1 || intval($gmsv["contentchanged"])>2): $getList.= " publishstructure "; endif;
							if (intval($gmsv["contentchanged"])==2 || intval($gmsv["contentchanged"])==3 || intval($gmsv["contentchanged"])==5): $getList.= " publishcontent "; endif;
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
                        } else {
							$getList.= " locked ";
                        }
						$getList.= " item".intval($gmsv["mid"])."\" ";
						if ($_SESSION['wspvars']['rights']['publisher']<100) {
							$getList.= " onclick=\"selectPublish('item".intval($gmsv["mid"])."','item'); return true;\" ";
						}
						$getList.= ">";
						$lp_sql = "SELECT `done` FROM `wspqueue` WHERE `param` = ".intval($gmsv["mid"])." AND `done` != 0 ORDER BY `done` DESC LIMIT 0,1";
						$lp_res = doSQL($lp_sql);
						if ($lp_res['num']>0) { 
							if (date('Y-m-d', $lp_res['set'][0]['done'])==date('Y-m-d')):
								$getList.= date(returnIntLang('format time', false), $lp_res['set'][0]['done']);
							else:
								$getList.= date(returnIntLang('format date', false), $lp_res['set'][0]['done']);
							endif;
						} else {
							$getList.= "-";	
						}
						$getList.= "</td>";
						$getList.= "<td class=\"tablecell one ";
						if ($mpfacts['edit']) {
							$getList.= " itempublish ";
							if (intval($gmsv["contentchanged"])>0): $getList.= " publishrequired "; endif;
							if (intval($gmsv["contentchanged"])==1 || intval($gmsv["contentchanged"])>2): $getList.= " publishstructure "; endif;
							if (intval($gmsv["contentchanged"])==2 || intval($gmsv["contentchanged"])==3 || intval($gmsv["contentchanged"])==5): $getList.= " publishcontent "; endif;
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
                        } else {
							$getList.= " locked ";
                        }
						$getList.= " item".intval($gmsv["mid"])."\" >";
						if ($mpfacts['edit'] && $_SESSION['wspvars']['rights']['publisher']<100) {
							$getList.= "<input type=\"checkbox\" class=\"itempublishbox";
							if (intval($gmsv["contentchanged"])>0): $getList.= " publishrequired "; endif;
							if (intval($gmsv["contentchanged"])==1 || intval($gmsv["contentchanged"])>2): $getList.= " publishstructure "; endif;
							if (intval($gmsv["contentchanged"])==2 || intval($gmsv["contentchanged"])==3 || intval($gmsv["contentchanged"])==5): $getList.= " publishcontent "; endif;
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
							$getList.= "\" ";
							if ($mpfacts['que']): $getList.= " disabled=\"disabled\" readonly=\"readonly\" "; endif;
							$getList.= " name=\"publishitem[]\" value=\"".intval($gmsv["mid"])."\" ";
							$getList.= " id=\"checkitem".intval($gmsv["mid"])."\" ";
							$getList.= " onchange=\"selectPublish('item".intval($gmsv["mid"])."','item'); return true;\" />";
							// output checkbox ..
							$getList.= " <a href=\"showpreview.php?previewid=".intval($gmsv["mid"])."&previewlang=".$publishlang."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('publisher bubble preview', false)."</span></a> &nbsp;";
						} else if ($mpfacts['edit'] && $_SESSION['wspvars']['rights']['publisher']>100) {
							$getList.= " <a href=\"showpreview.php?previewid=".intval($gmsv["mid"])."&previewlang=".$publishlang."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('publisher bubble preview', false)."</span></a> &nbsp;";
						}
						$getList.= "</td>\n";
						$getList.= "</tr>\n";
                    }
					$getList.= getPublisherStructure($gmsv["mid"], $aSelectIDs, $showmidpath, $publishlang, $select, $search);
				}
            }
        }
		return $getList;
    } // getPublisherStructure
}

// delete menupoint with existing submenu
function deleteMenuItems($mid, $ftp) {
	// dateien des zu loeschenden menuepunktes loeschen
	$sql = "SELECT `mid`,`filename` FROM `menu` WHERE `connected` = ".intval($mid);
	$res = doSQL($sql);
	if ($res['num']>0):
		foreach ($res['set'] AS $rsk => $rsv) {
			deleteMenuItems($rsv['mid'], $ftp);
        }
	endif;
	ftpDeleteFile(returnPath(intval($mid), $_SESSION['wspvars']['ftpbasedir'], 2));
	if ($res['num']>0) ftpDeleteDir(returnPath(intval($mid), $_SESSION['wspvars']['ftpbasedir'], 1));
	$GLOBALS['errormsg'] = "";
	$GLOBALS['deleteMenuItems'][intval($mid)] = intval($mid);
	}

if (!(function_exists('getSiteStructure'))): function getSiteStructure($parent, $spaces, $modi, $aSelectIDs = array(), $op = '', $showmidpath = '') { return "function deprecated"; } endif;

if (!(function_exists('getMenuLevel'))):
	function getMenuLevel($parent, $spaces, $modi, $aSelectIDs = array(), $op = '', $gmlVisible = 1, $allowselfselect = true) {
		//
		// 2012-05-09 added "gmlVisible" to limit output between visible and hidden menupoints
		// 
		// $gmlVisible 1 => show all
		// $gmlVisible 0 => show only hidden
		// $gmlVisible 2 => show only visible
		//
		// modi
		// 0 => gmlTable
		// 1 => gmlSelect
		// 2 => 
		// 3 =>	gmlContent
		// 4 => show as <option>-tag with value mid
		// 5 => gmlPublisher
		// 6 => gmlSelectwoID  => show as <option>-tag with value mid, but submenu hidden and no selected value
		// 7 => gmlFieldset
		// 8 => gmlSortableList
		// 9 => gmlPreview
		// 10 => showSitemap
		// 11 => gmlMIDArray
		//
		$menulevel_sql = "SELECT * FROM `menu` WHERE trash = 0 AND `connected` = '".intval($parent)."' ORDER BY `position`";
		$menulevel_res = doSQL($menulevel_sql);
		
		if ($menulevel_res['num']>0) {
			$spacer = ""; for ($i=0; $i<$spaces; $i++): $spacer .= "&nbsp;"; endfor;
			$i = 1;
            foreach ($menulevel_res['set'] AS $mlrk => $mlrv) {
				$menuItem = "";
				$getsubs = true;
				
				$menudescription = $mlrv['description'];
				$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
				if (intval(count($worklang['languages']['shortcut']))>1) {
					$langdescription = unserializeBroken($mlrv['langdescription']);
					if (isset($_SESSION['wspvars']['workspacelang']) && isset($_SESSION['wspvars']['workspacelang'][$langdescription]) && trim($langdescription[$_SESSION['wspvars']['workspacelang']])!='') {
						$menudescription = trim($langdescription[$_SESSION['wspvars']['workspacelang']]);
					}
                    else {
						$menudescription = $mlrv['description']." [".$_SESSION['wspvars']['lang'][0][0]."]";
					}
				}
				
				if ($modi == gmlTable) {
	
					$sub_sql = "SELECT `mid` FROM `menu` WHERE trash != 1 AND `connected` = ".intval($mlrv['mid']);
					$sub_res = doSQL($sql);
					if ($sub_res['num']>0) {
                        foreach ($sub_res['set'] AS $subrk => $subrv) {
                            // GLOBALS should be changed !!!!
                            $GLOBALS['system']['menustructure'][intval($mlrv['mid'])][intval($subrv['mid'])] = $subrk;
                        }
                    }
	
					// hier muessen irgendwie die unterpunkte festgestellt werden,
					// damit diese durchnummeriert und per id angesprochen werden koennen
	
					if ($GLOBALS['system']['menustructure']['connected'][($mlrv['mid'])]==$mlrv['connected']):
						$qmid = $mlrv['connected'];
						$qres = $mlrv['mid'];
						$menuItem .= "<div id=\"sub_".$mlrv['connected']."_".$GLOBALS['system']['menustructure'][$qmid][$qres]."\" style=\"clear: both; width: 98%; height: 20px; display: none;\" onMouseOver=\"this.style.background = '#EEEEEE';\" onMouseOut=\"this.style.background = '#E2E7EF';\">";
					else:
						$menuItem .= "<div id=\"mid_".$mlrv['mid']."\" style=\"clear: both; width: 98%; height: 20px;\" onMouseOver=\"this.style.background = '#EEEEEE';\" onMouseOut=\"this.style.background = '#E2E7EF';\">";
					endif;
	
					$editable = (($op == 'no') || (($op == 'some') && (in_array($mlrv['mid'], $aSelectIDs))));
	
					$menuItem .= "<div style=\"float: left;\">";
	
					$menuItem .= str_replace("&nbsp;&nbsp;&nbsp;","<img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/no.gif\" width=\"12\" height=\"12\" border=\"0\" style=\"float: left; margin-right: 2px;\" />",$spacer);
	
					if ($editable) {
						$menuItem .= "<a href=\"menueditdetails.php?usevar=$usevar&op=editdetails&mid=".$mlrv['mid']."\" title=\"Men&uuml;punkt bearbeiten\" onmouseover=\"status='Men&uuml;punkt bearbeiten'; return true;\" onmouseout=\"status=''; return true;\" style=\"float: left; margin-right: 5px;\">";
					}	// if
					if ($mlrv['visibility'] != "yes") {
						$menuItem .= "<span style=\"text-decoration: line-through;\">";
					}	// if
					$menuItem .= $menudescription;
					if ($mlrv['visibility'] != "yes") {
						$menuItem .= "</span>";
					}	// if
					if ($editable) {
						$menuItem .= "</a>";
					}	// if
					if ($menulevel_num >$i && $editable) {
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=".$_SESSION['wspvars']['usevar']."&mid=".$mlrv['mid']."&op=posdn\" title=\"Men&uuml;punkt eine Position nach unten verschieben\" onmouseover=\"window.status='Men&uuml;punkt eine Position nach unten verschieben'; return true;\" onmouseout=\"window.status=''; return true;\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/btn_down.gif\" alt=\"&#x2193;\" width=\"10\" height=\"10\" border=\"1\" style=\"float: left; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a>";
                        }
					else {
						$menuItem .= "<img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/btn_down.gif\" alt=\"&#x2193;\" width=\"10\" height=\"10\" border=\"1\" style=\"float: left; margin-right: 2px; opacity: 0.2; filter: alpha(opacity: 20); font-size: 9px; text-align: center; line-height: 8px;\" />";
					}
					if (($i > 1) && ($editable)) {
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=$usevar&mid=".$mlrv['mid']."&op=posup\" title=\"Men&uuml;punkt eine Position nach oben verschieben\" onmouseover=\"window.status='Men&uuml;punkt eine Position nach oben verschieben'; return true;\" onmouseout=\"window.status=''; return true;\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/btn_up.gif\" alt=\"&#x2191;\" width=\"10\" height=\"10\" border=\"1\" style=\"float: left; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a>";
					}
					else {
						$menuItem .= "<img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/btn_up.gif\" alt=\"&#x2191;\" width=\"10\" height=\"10\" border=\"1\" style=\"float: left; margin-right: 2px; opacity: 0.2; filter: alpha(opacity: 20); font-size: 9px; text-align: center; line-height: 8px;\" />";
					}	// if
					if ($sub_res['num']>0) {
						$menuItem .= "<span id=\"".$mlrv['mid']."_close\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
						$menuItem .= "document.getElementById('".$mlrv['mid']."_open').style.display = 'block';";
						$menuItem .= " document.getElementById('".$mlrv['mid']."_close').style.display = 'none';";
	
						foreach ($sub_res['set'] AS $srsk => $srsv) {
							$menuItem .= " document.getElementById('sub_".$mlrv['mid']."_".$srsk."').style.display = 'block';";
						}
	
						$menuItem .= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/expand.gif\" alt=\"+\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"float: left; margin-top: 0px; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";
						$menuItem .= "<span id=\"".$mlrv['mid']."_open\" style=\"display: none;\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
						$menuItem .= "document.getElementById('".$mlrv['mid']."_open').style.display = 'none';";
						$menuItem .= " document.getElementById('".$mlrv['mid']."_close').style.display = 'block';";
	
						foreach ($sub_res['set'] AS $srsk => $srsv) {
							$menuItem .= " document.getElementById('sub_".$mlrv['mid']."_".$srsk."').style.display = 'none';";
                        }
	
						$menuItem .= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/collapse.gif\" alt=\"-\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"float: left; margin-top: 0px; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";
		
						foreach ($sub_res['set'] AS $srsk => $srsv) {
							$fillres = $srsv['mid'];
							$GLOBALS['system']['menustructure']['connected'][$fillres] = $mlrv['mid'];
                        }
		
                    }
	
					$menuItem .= "</div>";
	
					if ($editable) {
						$availfuncs = 5;
						$menuItem .= "<div style=\"float: right; width: ".(($availfuncs*20)+2)."px;\">";
						// hinzufügen
						?>
						<script language="javascript" type="text/javascript">
							var SelectBox = document.getElementById('subpointfrom');
							SelectBox.selectOptionByValue = function( value )
							{
							 for(var i = 0; i < this.options.length; i++)
							 {
							  if( this.options[i].value == value )
							   this.options[i].selected = true;
							  else
							   this.options[i].selected = false;
							 }
							}
						</script>
						<?php
						
						// loeschen
						//
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=$usevar&mid=".$mlrv['mid']."&op=delete\" onclick=\"return confirmDelete();\" title=\"Men&uuml;punkt mit allen Untermen&uuml;punkten und Content-Elementen l&ouml;schen\" onmouseover=\"status='Men&uuml;punkt mit allen Untermen&uuml;punkten und Content-Elementen l&ouml;schen'; return true;\" onmouseout=\"status=''; return true;\" class=\"red\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/delete.gif\" alt=\"X\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #C0000D; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						
						// klonen
						//
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=".$_SESSION['wspvars']['usevar']."&mid=".$mlrv['mid']."&op=cloneit\" title=\"Men&uuml;punkt klonen\" onmouseover=\"status='Men&uuml;punkt klonen'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/clone.gif\" alt=\"x2\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						
						// verschieben
						//
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=".$_SESSION['wspvars']['usevar']."&mid=".$mlrv['mid']."&op=repos\" title=\"Men&uuml;punkt beliebig verschieben\" onmouseover=\"status='Men&uuml;punkt beliebig verschieben'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/resort.gif\" alt=\" &#x2195;\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						
						// einfuegen
						//
						$menuItem .= "<a href=\"#\" onclick=\"document.getElementById('newmenuitem').focus();SelectBox.selectOptionByValue( ".$mlrv['mid']." );\" title=\"Submen&uuml;punkt zu DIESEM Men&uuml;punkt hinzuf&uuml;gen\" onmouseover=\"status='Submen&uuml;punkt zu DIESEM Men&uuml;punkt hinzuf&uuml;gen'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/createsub.gif\" alt=\"=\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						
						// sichtbarkeit
						//
						if ($mlrv['visibility']==1):
							$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=$usevar&mid=".$mlrv['mid']."&op=hide\" onclick=\"return confirmHide();\" title=\"Men&uuml;punkt verstecken\" onmouseover=\"status='Men&uuml;punkt verstecken'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/hide.gif\" alt=\"-\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						else:
							$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=".$usevar."&mid=".$mlrv['mid']."&op=show\" onclick=\"return confirmShow();\" title=\"Men&uuml;punkt anzeigen\" onmouseover=\"status='Men&uuml;punkt anzeigen'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/view.gif\" alt=\"O\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						endif;
	
						$menuItem .= "</div>";
					}	// if
	
					$menuItem .= "</div>\n";
				}
                else if ($modi == gmlSortableList) {
					
					if ($i == 1):
						$menuItem = "\n<ul id=\"ulholder".$parent."\" style=\"list-style-type: square;\">\n";
					endif;
					
                    $sub_sql = "SELECT `mid` FROM `menu` WHERE trash != 1 AND `connected` = ".intval($mlrv['mid']);
					$sub_res = doSQL($sql);
					if ($sub_res['num']>0) {
                        foreach ($sub_res['set'] AS $subrk => $subrv) {
                            // GLOBALS should be changed !!!!
                            $GLOBALS['system']['menustructure'][intval($mlrv['mid'])][intval($subrv['mid'])] = $subrk;
                        }
                    }
	
					// hier muessen irgendwie die unterpunkte festgestellt werden,
					// damit diese durchnummeriert und per id angesprochen werden koennen
	
					if ($GLOBALS['system']['menustructure']['connected'][($mlrv['mid'])]==$mlrv['connected']):
						$qmid = $mlrv['connected'];
						$qres = $mlrv['mid'];
						$menuItem .= "\n<li id=\"sub_".$mlrv['connected']."_".$GLOBALS['system']['menustructure'][$qmid][$qres]."\" style=\"clear: both; width: 98%; height: 20px; display: none;\" onMouseOver=\"this.style.background = '#EEEEEE';\" onMouseOut=\"this.style.background = '#E2E7EF';\">";
					else:
						$menuItem .= "\n<li id=\"mid_".$mlrv['mid']."\" style=\"clear: both; width: 98%; height: 20px;\" onMouseOver=\"this.style.background = '#EEEEEE';\" onMouseOut=\"this.style.background = '#E2E7EF';\">";
					endif;
	
					$editable = (($op == 'no') || (($op == 'some') && (in_array($mlrv['mid'], $aSelectIDs))));
	
					$menuItem .= "\n<div style=\"float: left;\">";
	
					$menuItem .= str_replace("&nbsp;&nbsp;&nbsp;","<img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/no.gif\" width=\"12\" height=\"12\" border=\"0\" style=\"float: left; margin-right: 2px;\" />",$spacer);
	
					if ($editable):
						$menuItem .= "<a href=\"menueditdetails.php?usevar=$usevar&op=editdetails&mid=".$mlrv['mid']."\" title=\"Men&uuml;punkt bearbeiten\" onmouseover=\"status='Men&uuml;punkt bearbeiten'; return true;\" onmouseout=\"status=''; return true;\" style=\"float: left; margin-right: 5px;\">";
					endif;
					if ($mlrv['visibility'] != "yes"):
						$menuItem .= "<span style=\"text-decoration: line-through;\">";
					endif;
					
					$menuItem .= $menudescription;
					if ($mlrv['visibility'] != "yes"):
						$menuItem .= "</span>";
					endif;
					if ($editable):
						$menuItem .= "</a>";
					endif;
	
					if ($sub_res['num']>0) {
                        $menuItem .= "<span id=\"".$mlrv['mid']."_close\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
                        $menuItem .= "document.getElementById('".$mlrv['mid']."_open').style.display = 'block';";
                        $menuItem .= " document.getElementById('".$mlrv['mid']."_close').style.display = 'none';";
                        foreach ($sub_res['set'] AS $subrk => $subrv) {
                           $menuItem .= " document.getElementById('sub_".$mlrv['mid']."_".$subrk."').style.display = 'block';";
                        }
                        $menuItem .= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/expand.gif\" alt=\"+\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"float: left; margin-top: 0px; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";
                        $menuItem .= "<span id=\"".$mlrv['mid']."_open\" style=\"display: none;\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
                        $menuItem .= "document.getElementById('".$mlrv['mid']."_open').style.display = 'none';";
                        $menuItem .= " document.getElementById('".$mlrv['mid']."_close').style.display = 'block';";
                        foreach ($sub_res['set'] AS $subrk => $subrv) {
                           $menuItem .= " document.getElementById('sub_".$mlrv['mid']."_".$subrk."').style.display = 'none';";
                        }
                        $menuItem .= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/collapse.gif\" alt=\"-\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"float: left; margin-top: 0px; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";

                        foreach ($sub_res['set'] AS $subrk => $subrv) {
                            $GLOBALS['system']['menustructure']['connected'][intval($subrv['mid'])] = intval($mlrv['mid']);
                        }
                    }
                    // close items
					$menuItem .= "</div>";
					$menuItem .= "</li>\n";
				}
				else if ($modi == gmlSelect) {
					if (is_array($op)):
						if (count($op)>0):
							if (in_array(intval($mlrv['mid']), $op)):
								if (($gmlVisible==0 && $mlrv['visibility']==0) || $gmlVisible==1 || ($gmlVisible==2 && $mlrv['visibility']==1)):
									$menuItem = "<option value=\"".$mlrv['mid']."\"";
									if (!(array_search($mlrv['mid'], $aSelectIDs) === false)) {
										$menuItem .= " selected=\"selected\"";
									}	// if
									$menuItem .= ">".$spacer.$menudescription."</option>";
								endif;			
							endif;
						else:
							if (($gmlVisible==0 && $mlrv['visibility']==0) || $gmlVisible==1 || ($gmlVisible==2 && $mlrv['visibility']==1)):
								$menuItem = "<option value=\"".$mlrv['mid']."\"";
								if (!(array_search($mlrv['mid'], $aSelectIDs) === false)) {
									$menuItem .= " selected=\"selected\"";
								}	// if
								$menuItem .= ">".$spacer.$menudescription."</option>";
							endif;
						endif;
					else:
						if (($gmlVisible==0 && $mlrv['visibility']==0) || $gmlVisible==1 || ($gmlVisible==2 && $mlrv['visibility']==1)):
							if (!($allowselfselect) && $mlrv['mid']==intval($_SESSION['wspvars']['editmenuid'])):
								// find upper
								$topm_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($mlrv['mid']);
								$topm_res = doResultSQL($topm_sql);
								$topmid = intval($topm_res);
								$menuItem = "<option value=\"".$topmid."\" disabled=\"disabled\">".$spacer.$menudescription." - ".returnIntLang('structure edit property can not be set to itself', false)."</option>";
								$getsubs = false;
							else:
								$menuItem = "<option value=\"".$mlrv['mid']."\"";
								if (!(array_search($mlrv['mid'], $aSelectIDs) === false)) {
									$menuItem .= " selected=\"selected\"";
								}	// if
								$menuItem .= ">".$spacer.$menudescription."</option>";
							endif;
						endif;
					endif;
				}
				else if ($modi == 2) {
					if ($i == 1) {
						$menuItem = "<ul id=\"repossub".$parent."\">\n";
					}
					$menuItem .= "<li id=\"repos".$mlrv['mid']."\" style=\"list-style-type:none;\"><a href=\"#\" onclick=\"makeRepos(".$mlrv['mid']."); return false;\">".$menudescription."</a></li>\n";
				}
				else if ($modi == gmlFieldset) {
					$menuItem = "<fieldset>";
					$menuItem.= "<legend>".$menudescription."</legend>";
	
				}
				else if ($modi == gmlContent) {
                    $menuItem .= "<pre>function 'getMenuLevel' is deprecated since wsp 6.0 and must be replaced with getjMenuLevel</pre>";
				} 
				else if ($modi == 4) {
					if ($spaces=="-1"):
					$spacer = "";
					endif;
					$menuItem = "<option value=\"".$mlrv['mid']."\"";
					if(is_array($aSelectIDs)):
						if (is_int(array_search(intval($mlrv['mid']), $aSelectIDs))) {
							$menuItem .= " selected=\"selected\"";
						}	// if
					endif;
					$menuItem .= ">".$spacer.$menudescription."</option>\n";
				}
				else if ($modi == 12) {
					if ($spaces=="-1"):
					$spacer = "";
					endif;
					$menuItem = "<option value=\"".$mlrv['mid']."\"";
					if(is_array($aSelectIDs)):
						if (is_int(array_search(intval($mlrv['mid']), $aSelectIDs))) {
							$menuItem .= " selected=\"selected\"";
						}	// if
					endif;
					$menuItem .= ">".$spacer.$menudescription."</option>";
				}
				else if ($modi == gmlSelectwoID) {
					if (!in_array($mlrv['mid'],$aSelectIDs)):
					$menuItem = "<option value=\"".$mlrv['mid']."\"";
					if (is_int(array_search(intval($mlrv['mid']), $aSelectIDs))):
					$menuItem .= " selected=\"selected\"";
					endif;
					$menuItem .= ">".$spacer.$menudescription."</option>";
					else:
					$getsubs = false;
					endif;
				}
				else if ($modi == gmlPublisher) {
					$editable = (($op == 'no') || (($op == 'some') && (in_array($mlrv['mid'], $aSelectIDs))));
					if($mlrv['editable']==0):
						$blocked= false;
					else:
						$blocked= true;
					endif;
					$GLOBALS['linecount']++;
					$menuItem .= "<li class=\"";
					if ($GLOBALS['linecount']/2!=ceil($GLOBALS['linecount']/2)):
						$menuItem .= "firstcol";
					else:
						$menuItem .= "secondcol";
					endif;
					if ($mlrv['contentchanged']==1) {
						$menuItem .= " publishcontent";
                    } else if ($mlrv['contentchanged']==2) {
						$menuItem .= " publishmenu";
                    }
					$menuItem .= "\" style=\"position: relative;";
					if ($editable && $blocked):
						$menuItem .= " cursor: pointer;\" id=\"m".$mlrv['mid']."\"";
					else:
						$menuItem .= " color: #cccccc;\"";
					endif;
					$menuItem .= "><span id=\"m".$mlrv['mid']."text\" style=\"width: 99%; float: left;\"";
					if ($editable && $blocked):
						$menuItem .= " onclick=\"selectItem('m".$mlrv['mid']."'); return true;\"";
					endif;
					$menuItem .= ">";
//					$menuItem .= "><span id=\"m".$mlrv['mid']."text\" style=\"width: 99%; float: left;\">";
					$menuItem .= $spacer.$menudescription."</span>";
					if (intval($mlrv['contentchanged'])>0):
						$menuItem .= "";
					endif;
					$menuItem .= "&nbsp;</li>";
					}
				else if ($modi == gmlPreview) {
					$editable = (($op == 'no') || (($op == 'some') && (in_array($mlrv['mid'], $aSelectIDs))));
					$menuItem .= "<div class=\""; 
					if ($mlrv['contentchanged']==1):
						$menuItem .= "publishrequired";
					else:
						$menuItem .= "nopublish";
					endif;
					$menuItem .= "\" style=\"position: relative;";
					if ($editable):
						$menuItem .= " cursor: pointer;\" id=\"m".$mlrv['mid']."\"><a href=\"javascript:;\" onClick=\"document.getElementById('previewid').value = '".$mlrv['mid']."'; document.getElementById('previewform').submit(); return false;\" target=\"_blank\">";
					else:
						$menuItem .= " color: #cccccc;\">";
					endif;
					$menuItem .= $spacer.$menudescription;
					if ($editable):
						$menuItem .= "</a>";
					endif;
					$menuItem .= "</div>";
				}	// if
				
				if ($op!='xajax'):
					echo $menuItem;
				else:
					$GLOBALS['getMenuLevel']['finalmenu'].= $menuItem;
				endif;
				
				if ($modi == gmlContent):
					echo getContents($mlrv['mid'], (intval($spaces)+3), 0, null, $editable, $contentareas);
					echo "</div>";
				endif;
				
				if ($spaces=="-1") {
					getMenuLevel($mlrv['mid'], intval($spaces), $modi, $aSelectIDs, $op, $gmlVisible, $allowselfselect);
                } else {
					echo "";
					if (!isset($getsubs)) {
						getMenuLevel($mlrv['mid'], (intval($spaces)+3), $modi, $aSelectIDs, $op, $gmlVisible, $allowselfselect);
						if ($modi == gmlContent):
							echo "</div>\n";
						endif;
					} else if ($getsubs) {
						getMenuLevel($mlrv['mid'], (intval($spaces)+3), $modi, $aSelectIDs, $op, $gmlVisible, $allowselfselect);
					}
				}
				$i++;
                // unset values to free space
                unset($menulevel_res['set'][$mlrk]);
            }
			if ($modi == 2 || $modi == gmlSortableList) { if ($i > 1) { echo "</ul>\n"; }}
		}	// if
	}	// getMenuLevel()
endif;

/**
* alle Contents mit moeglichen aktionen zum gegebenen Menuepunkt ermitteln und ausgeben
*/
if (!(function_exists('getContents'))):
	function getContents($mid, $spaces, $modi, $cid = 0, $editable = 0, $contentareas = array()) {
		//	modi:
		//	0 = dragable list
		//	1 = ??
		//	2 = darstellung contentedit => nur ein (!!!) contentbereich wird ausgegeben 
		//	3 = like 0, without usage/display info and edit link
		//  4 = ??
		//	5 = copy and paste view for content move page
		//	6 = ??
		$contentItem = "";
		$contentDesc = "";
		if (intval($contentareas[0])>0):
			// description missing			
			if (intval($modi)==6):
				$contents_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($mid)." && `content_area` = 1 && `content_lang` = '".$_SESSION["wspvars"]["workspacelang"]."' && `connected` = 0 ORDER BY `position`";
				$contents_res = doSQL($contents_sql);
				$contentItem.= "<table class=\"contenttable\"><tr class=\"tablehead\">";
				$contentItem.= "<td width=\"100%\">".returnIntLang('str contentarea', true)." 1 - ".$contents_num." Element";
				if ($contents_res['num']!=1):
					$contentItem.= "e";
				endif;
				$contentItem.= "</td>";
				$contentItem.= "</tr></table>";
				$contentItem.= "<ul id=\"targetlist\" class=\"fieldlist dragable\" style=\"padding: 0px;\">";
			endif;
			
			if (is_array($contentareas)):
				// run through content areas
				foreach ($contentareas AS $areakey => $areavalue) {
					if (intval($cid)>0 && $modi==2):
						// select ONE content from selected content area
						$contents_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($mid)." AND `content_area` = ".intval($areavalue)." AND `content_lang` = '".$_SESSION['wspvars']['workspacelang']."' AND `cid` = ".intval($cid)." ORDER BY `position`";
					else:
						// select ALL contents from selected content area
						$contents_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($mid)." AND `content_area` = ".intval($areavalue)." AND `content_lang` = '".$_SESSION['wspvars']['workspacelang']."' AND `connected` = 0 ORDER BY `position`";
					endif;
					$contents_res = doSQL($contents_sql);
					
					$menuinfo_sql = "SELECT `description`, `internlink_id`, `offlink`, `forwardmenu` FROM `menu` WHERE `trash` != 1 `mid` = ".intval($mid);
					$menuinfo_res = doSQL($menuinfo_sql);
					if ($menuinfo_res['num']>0):
						$menudescription = trim($menuinfo_res['set'][0]["description"]);
					endif;
					
					if (intval($modi)==0 && intval($menuinfo_res['set'][0]["forwardmenu"])==1 && (intval($menuinfo_res['set'][0]["internlink_id"])>0 || trim($menuinfo_res['set'][0]["offlink"])!="")):
						$contentItem .= "";
					else:
						// build table header with counting content elements
						if (intval($modi)==0 || intval($modi)==3 || intval($modi)==4 || intval($modi)==5) {
							$contentItem .= "<table class=\"contenttable\"><tr class=\"tablehead\">";
							$contentItem .= "<td width=\"100%\">".returnIntLang('str contentarea', true)." ".$areavalue;
							$contentItem .= " - ".$contents_res['num']." ";
							if ($contents_res['num']!=1):
								$contentItem .= returnIntLang('str contents', true);
							else:
								$contentItem .= returnIntLang('str content', true);
							endif;
							$contentItem .= "</td>";
							if (intval($modi)==0 && $editable!="show" && ($_SESSION['wspvars']['rights']['contents']==1 || $_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==7)) {
								$contentItem .= "<td nowrap><a onclick=\"addContent(".$mid.", ".$areavalue.");\"><span class=\"bubblemessage orange\">".setUTF8(returnIntLang('bubble addcontent', false))."</span></a></td>";
							}
							$contentItem .= "</tr></table>";
						} else if (intval($modi)==6 && $areavalue>1) {
							$contentItem .= "<li><span class=\"handle\"></span><table class=\"contenttable\" style=\"margin-bottom: 1px;\"><tr>";
							$contentItem .= "<td width=\"100%\">".returnIntLang('str contentarea', true)." ".$areavalue;
							$contentItem .= " - ".$contents_res['num']." ";
							if ($contents_res['num']!=1):
								$contentItem .= returnIntLang('str contents', true);
							else:
								$contentItem .= returnIntLang('str content', true);
							endif;
							$contentItem .= "<input type=\"hidden\" name=\"contentarea".$areavalue."\" value=\"content".$areavalue."\"></td>";
							if (intval($modi)==0 && $editable!="show" && ($_SESSION['wspvars']['rights']['contents']==1 || $_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==7)):
								$contentItem .= "<td nowrap><a href=\"#sid\" onclick=\"showAddContentBlock(".$mid.",'".$areavalue."','".$menudescription."');\" class=\"green\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/addcontent_x.gif\" alt=\"=\" border=\"0\" style=\"float: left; font-size: 9px; text-align: center; line-height: 16px;\"></a></td>";
							endif;
							$contentItem .= "</tr></table></li>";
						}
						// begin display contents
						if ($contents_res['num']>0) {
							if (intval($modi)==0 || intval($modi)==3 || intval($modi)==4) {
								$contentItem.= "<ul id=\"ul_".$mid."_".$areavalue."\" class=\"fieldlist dragable\" style=\"padding: 0px;\">";
							} else if (intval($modi)==5) {
								$contentItem.= "<ul class=\"fieldlist dragable\" style=\"padding: 0px;\">";
                            }
        
							// run through every content element
							foreach ($contents_res['set'] AS $cresk => $cresv) {
								// find interpreter file
								if (intval($cresv['globalcontent_id'])==0):
									$interpreter_sql = "SELECT `parsefile`, `name` FROM `interpreter` WHERE `guid` = '".trim($cresv['interpreter_guid'])."'";
								else:
									$interpreter_sql = "SELECT i.`parsefile`, i.`name` FROM `interpreter` AS i, `globalcontent` AS g WHERE i.`guid` = g.`interpreter_guid` AND g.`id` = ".intval($cresv['globalcontent_id']);
								endif;
								$interpreter_res = doSQL($interpreter_sql);
								// call parser, if interpreter was found
								if ($interpreter_res['num']>0):
									// select parsefile name
									$parsefile = trim($interpreter_res['set'][0]['parsefile']);
									if (trim($parsefile)!=""): // begin parser file usage
										$checkwsptype = unserializeBroken($cresv['valuefields']);
										// check if data from database is an array (changed in wsp3.3)
										if (is_array($checkwsptype)):
											$fieldvalue = unserializeBroken($cresv['valuefields']);
											$fieldvaluestyle = 'array';
										else:
											$fieldvalue = explode('<#>', trim($cresv['valuefields']));
											$fieldvaluestyle = 'string';
										endif;
										// load parser file and interpreter class
										$interpreter_load = false;
										if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$parsefile)):
											include_once ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php");
											include ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$parsefile);
											$clsInterpreter = new $interpreterClass;
											$interpreter_load = true;
										endif;
										// design cols			
										if ($cres/2==ceil($cres/2)):
											$colclass = "firstcol";
										else:
											$colclass = "secondcol";
										endif;
										if (intval($cresv['cid'])==intval($_SESSION['opencontent'])):
											$colclass.= " highlight";
										endif;

										// description missing

										if (intval($modi)==0 || intval($modi)>2) {
											$contentItem .= "<li id=\"mid_".$mid."-cid_".intval($cresv['cid'])."\"><table class=\"smartcontenttable\"><tr class=\"".$colclass."\">";
											// move
											if ($editable!="convert" && intval($modi)!=5 && intval($modi)!=6):
												$contentItem .= "<td>";
												if (($_SESSION['wspvars']['rights']['contents']==1 || $_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==7) && $contents_res['num']>1):
													$contentItem .= "<span class=\"handle\"><span class=\"bubblemessage orange\">".returnIntLang('bubble move', false)."</span></span>";
												else:
													$contentItem .= "<span class=\"bubblemessage hidden\">".returnIntLang('bubble move', false)."</span><span class=\"handle\" style=\"display: none;\"></span>";
												endif;
												$contentItem .= "</td>";
											endif;
										}
										
										// display content information
										
										$contentDesc = "";
										
										if (intval($modi)==0 || intval($modi)>2):
											$contentItem .= "<td width=\"100%\">";
										endif;
										if (intval($cresv['globalcontent_id'])==0):
											if ($interpreter_load):
												if ($editable=="convert") {
													if (intval($modi)==0) {
														$contentItem .= "<a href=\"contentconvert.php?op=convert&cid=".intval($cresv['cid'])."\" title=\"Content-Element konvertieren\" onmouseover=\"window.status='Content-Element konvertieren'; return true;\" onmouseout=\"window.status=''; return true;\">";
													}
												} else if ($editable) {
													if (intval($modi)==0) $contentItem .= "<a onclick=\"document.getElementById('editcontentid').value = '".intval($cresv['cid'])."'; document.getElementById('editcontents').submit();\" style=\"cursor: pointer;\">";
												}
												$contentItem .= trim($interpreter_res['set'][0]['name']);
												$contentDesc .= trim($interpreter_res['set'][0]['name']);
												if ($editable=="convert"):
													if (intval($modi)==0 || intval($modi)==3 || intval($modi)==4):
														$contentItem .= " konvertieren";
													endif;
												else:
													$tmpContentItem = " - ".$clsInterpreter->getView($fieldvalue, intval($cresv['mid']), intval($cresv['cid']));
                                                    $clsInterpreter->closeInterpreterDB();
													if (strlen($tmpContentItem)>200):
														$contentItem .= substr($tmpContentItem,0,15)." ... ".substr($tmpContentItem,-15);
														$contentDesc .= substr($tmpContentItem,0,15)." ... ".substr($tmpContentItem,-15);
													else:
														$contentItem .= $tmpContentItem;
														$contentDesc .= $tmpContentItem;
													endif;
												endif;
												if ($editable):
													if (intval($modi)==0 || intval($modi)==3 || intval($modi)==4):
														$contentItem .= "</a>";
													endif;
												endif;
                                            else:
												if (intval($modi)==0 || intval($modi)==3 || intval($modi)==4):
													$contentItem .= "<em>".returnIntLang('interpreter errormsg', true)."</em>";
												endif;
											endif;
										else:
											$globalcontent_sql = "SELECT `valuefield` FROM `globalcontent` WHERE `id`=".intval($cresv['globalcontent_id']);
											$globalcontent_res = doResultSQL($globalcontent_sql);
											
											$checknewstyle = "([as])\:([1-9])\:*";
											$checkvar = trim($globalcontent_res);
										    
                                            if ($checkvar!='') {
                                                if (eregi($checknewstyle, $checkvar)):
                                                    $fieldvalue = unserializeBroken($checkvar);
                                                    $fieldvaluestyle = "array";
                                                else:
                                                    $fieldvalue = explode('<#>', $checkvar);
                                                    $fieldvaluestyle = "string";
                                                endif;
                                            }
											
											//
											// display data
											//
											if ($editable=="convert") {
												if (intval($modi)==0 || intval($modi)==3 || intval($modi)==4) {
													$contentItem .= "&convert&";
                                                }
                                            } else if ($editable) {
												if (intval($modi)==0) {
													$contentItem .= "<a onclick=\"document.getElementById('editcontentid').value = '".intval($cresv['cid'])."'; document.getElementById('editcontents').submit();\" style=\"cursor: pointer;\">";
												}
                                            }
											$contentItem .= trim($interpreter_res['set'][0]['name']);
											$contentDesc .= trim($interpreter_res['set'][0]['name']);
											if ($editable!="convert"):
												$contentItem .= " - ".$clsInterpreter->getView($fieldvalue, intval($cresv['mid']), intval($cresv['cid']));
                                                $clsInterpreter->closeInterpreterDB();
											endif;
											if ($editable):
												if (intval($modi)==0):
													$contentItem .= "</a>";
												endif;
											endif;
											if (intval($modi)==0):
												$contentItem .= " <strong>[GlobalContent]</strong>";
											else:
												$contentItem .= " [GlobalContent]";
												$contentDesc .= " [GlobalContent]";
											endif;
										endif;
										
										if (intval($modi)==6):
											$contentItem .= "<input type=\"hidden\" name=\"".intval($cresv['cid'])."\" value=\"hold\">";
										endif;
										
										doSQL("UPDATE `content` SET `position` = '".($cresk+1)."' WHERE `cid` = ".intval($cresv['cid']));
										
										if (intval($modi)==0 || intval($modi)>2) $contentItem .= "</td>";
										// display content
										if (intval($modi)==0 || intval($modi)>2) {
											$contentItem .= "<td nowrap>";
											if (intval($modi)==0 && $editable!="convert") {
												if (trim($cresv['visibility']) == "no" || intval($cresv['visibility']) == 0) {
													$contentItem .= "<a onclick=\"showContent('".$mid."', '".intval($cresv['cid'])."');\"><span class=\"bubblemessage green\">".returnIntLang('bubble show', false)."</span></a>";
                                                } else {
													if (trim($cresv['showtime'])!="" && trim($cresv['showtime'])!="0") {
														$contentItem .= "<span class=\"bubblemessage\">".returnIntLang('bubble time', false)."</span>";
													} else {
														$contentItem .= "<a onclick=\"hideContent('".$mid."', '".intval($cresv['cid'])."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble hide', false)."</span></a>";
                                                    }
                                                }
                                            } else if (intval($modi)==3) {
												$contentItem .= "<input type=\"checkbox\" />";
                                            } else if (intval($modi)==5) {
												$contentItem .= "<a href=\"#\" onclick=\"copyItem('mid_".$mid."-ca_".$areavalue."-pos_".$cresk."', 'copy', ".intval($cresv['cid']).", ".intval($mid).", '".addslashes($contentDesc)."')\"><span class=\"bubblemessage orange\">".returnIntLang('bubble contentcopy', false)."</span></a> <a href=\"#\" onclick=\"moveItem('mid_".$mid."-ca_".$areavalue."-pos_".$cresk."', ".intval($cresv['cid']).", ".intval($mid).", '".addslashes($contentDesc)."')\"><span class=\"bubblemessage red\">".returnIntLang('bubble contentmove', false)."</span></a>";
                                            } else if (intval($modi)==6) {
												$contentItem .= "<span class=\"handle\"></span>";
                                            }
											if (intval($modi)==0 && $editable!="show" && $editable!="convert" && ($_SESSION['wspvars']['rights']['contents']==1 || $_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==7)):
												// delete item
												$contentItem .= "&nbsp;<a onclick=\"deleteContent('".intval($mid)."', '".intval($cresv['cid'])."', '".$contentDesc."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a>";
											endif;
											$contentItem .= "</td></tr></table></li>\n";
                                        }

									endif; // end parser file usage
								else:
									if (intval($modi)==0 || intval($modi)>2):
										$contentItem .= "<li><table border=\"0\" cellspacing=\"0\" cellpadding=\"3\" width=\"100%\"><tr><td>";
										if ($editable=="convert"):
											$contentItem .= "<a href=\"contentconvert.php?op=convert&cid=".intval($cresv['cid'])."\" title=\"Content-Element konvertieren\" onmouseover=\"window.status='Content-Element konvertieren'; return true;\" onmouseout=\"window.status=''; return true;\">Unbekanntes Content-Element konvertieren</a>";
										else:
											$contentItem .= "<em>Das hier platzierte Contentelement verwendet einen Interpreter, der nicht installiert ist.</em><br />";
											if (intval($modi)==0 && $editable!="show" && ($_SESSION['wspvars']['rights']['contents']==1 || $_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==7)):
												$contentItem .= "</td><td><a href=\"".$_SERVER['PHP_SELF']."?usevar=".$_SESSION['wspvars']['usevar']."&cid=".intval($cresv['cid'])."&op=delete\" onclick=\"if (confirm(unescape('Dieses Content-Element l%F6schen?'))) {return true;} else {return false;}\" title=\"Content-Element l&ouml;schen\" onmouseover=\"status='Content-Element l&ouml;schen'; return true;\" onmouseout=\"status=''; return true;\" class=\"red\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/delete_x.png\" alt=\"X\" border=\"0\" style=\"margin: 1px; font-size: 9px; text-align: center;\"></a>";
											endif;
										endif;
										$contentItem .= "</td></tr></table></li>\n";
									else:
										$contentItem .= "Unbekannter Interpreter";
									endif;
								endif;
                            }
							if (intval($modi)==0 || intval($modi)==3 || intval($modi)==4 || intval($modi)==5) {
								$contentItem.= "</ul>\n\n";
							}
						} else if (intval($modi)==4) {
							$contentItem.= "<ul id=\"ul_".$mid."_".$areavalue."\" class=\"fieldlist dragable\" style=\"padding: 0px;\">";
							$contentItem .= "<li>drag item to ul_".$mid."_".$areavalue."</li>";
							$contentItem.= "</ul>\n\n";
						} else {
							$contentItem.= "<table class=\"contenttable\"><tr class=\"firstcol\">";
							$contentItem.= "<td width=\"100%\">there are no contents assigned to this content area yet. please use the upper right icon to add some contents to this content area.</td>";
							$contentItem.= "</tr></table>";
						}
					endif;
				}
			else:
				$contentItem.= "<table class=\"contenttable\"><tr class=\"tablehead\">";
				$contentItem.= "<td width=\"100%\">the content structure was not readable</td>";
				$contentItem.= "</tr></table>";
			endif;
			
			if (intval($modi)==6):
				$contentItem .= "<li style=\"height: 1px; line-height: 1px; font-size: 1px; visibility: hidden;\">emptyrow</li></ul>";
			endif;
		else:
			$contentItem.= "<table class=\"contenttable\"><tr class=\"tablehead\">";
			$contentItem.= "<td width=\"100%\">an error occured during opening these contents</td>";
			$contentItem.= "</tr></table>";
		endif;
		
		return $contentItem;
		}	// getContents
endif;

/**
 * resize image - usage should be replaced with ()
 */
if (!(function_exists('imgResize'))):
	function imgResize($src, $dest, $width, $height) {
		$imgsize = getimagesize($src);
	
		if (($imgsize[0] == 0) || ($imgsize[1] == 0)) {
			$tnwidth  = $width;
			$tnheight = $height;
		}
		else if (($imgsize[0]/$imgsize[1]) > ($width/$height)) {
			$tnwidth  = $width;
			$tnheight = round($width*$imgsize[1]/$imgsize[0]);
		}
		else if (($imgsize[0]/$imgsize[1]) < ($width/$height)) {
			$tnwidth  = round($height*$imgsize[0]/$imgsize[1]);
			$tnheight = $height;
		}
		else {
			$tnwidth  = $width;
			$tnheight = $height;
		}	// if
	
		exec("/usr/bin/convert $src -resize ".$tnwidth."x$tnheight $dest");
	}	// imgResize()
endif;

/**
 * verzeichnisstruktur kopieren, z.b. bei userlogin
 */
if (!(function_exists('copySkel'))):
	function copySkel($skel, $dest) {
		$skelhdl = @opendir($skel);
		if ($skelhdl):
            while ($dirname = readdir($skelhdl)) {
                if (($dirname != ".") && ($dirname != "..")) {
                    if (is_dir($skel."/".$dirname)) {
                        @$mkdir = mkdir($dest."/".$dirname);
                        @chmod($dest."/".$dirname, 0777);
                        if ($mkdir === false) {
                            header("Location: index.php");
                            die();
                        }	// if
                        copySkel($skel."/".$dirname, $dest."/".$dirname);
                    }	// if
                }	// if
            }	// while
        endif;
	}	// copySkel()
endif;

/**
 * ein komplettes Verzeichnis incl. aller Untervzeichnisse und enthaltener Dateien lï¿½schen
 * deaktivierung durch s.haendler am 6.2.2008 wegen des fehlers vom 5.2.2008
 */
if (!(function_exists('delTree'))):
	function delTree($dir) {
		$nondel = array(
			$_SERVER['DOCUMENT_ROOT'],
			$_SERVER['DOCUMENT_ROOT'].'/data',
			$_SERVER['DOCUMENT_ROOT'].'/media',
			$_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasedir']
			);
		if (!(in_array($dir, $nondel))):
			@$handle = opendir($dir);
			while (@$entry = readdir($handle)):
				if (($entry!=".") && ($entry!="..")):
					if (is_dir($dir."/".$entry)):
						delTree($dir."/".$entry);
					else:
						@unlink($dir."/".$entry);
					endif;
				endif;
			endwhile;
			@closedir($handle);
			@rmdir($dir);
		endif;
	}	// delTree()
endif;

// $path = full path from actual tmp directory with leading slash
if (!(function_exists('createDir'))):
function createDir($path) {
	if (substr($path, 0, 1)=="/"):
		// $_SESSION['wspvars']['usevar'];
		$path = substr($path, 1);
	endif;
	$pathstructure = explode("/", $path);
	$fullpath = $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/";
	foreach ($pathstructure AS $pathvalue):
		if (trim($pathvalue)!=""):
			$fullpath = $fullpath.$pathvalue."/";
			if (!is_dir($fullpath)):
				mkdir($fullpath);
			endif;
		endif;
	endforeach;
	}
endif;

// $path = full ftp path
if (!(function_exists('createDirFTP'))):
	function createDirFTP($path) {
		$path = str_replace("//", "/", str_replace($_SESSION['wspvars']['ftpbasedir'], "/", $path));
		if (substr($path, 0, 1)=="/"):
			// $_SESSION['wspvars']['usevar'];
			$path = substr($path, 1);
		endif;
		$pathstructure = explode("/", $path);
		$fullpath = str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/");
		$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
		$login = ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
		foreach ($pathstructure AS $pathvalue):
			if (trim($pathvalue)!=""):
				$fullpath = $fullpath.$pathvalue."/";
				@ftp_mkdir($ftp, $fullpath);
			endif;
		endforeach;
		ftp_close($ftp);
		}
endif;

// return path to given mid
if (!(function_exists('returnPath'))) {
	function returnPath($mid, $depth = 0, $basepath = '', $baselang = 'de') {
		// depth 0 => rueckgabe des pfades bis hin zum hoeheren verzeichnis
		// depth 1 => rueckgabe des pfades bis hin zum verzeichnis
		// depth 2 => rueckgabe des pfades bis hin zur datei
		$path_sql = "SELECT `connected`, `filename`, `isindex`, `level` FROM `menu` WHERE `mid` = ".intval($mid);
		$path_res = doSQL($path_sql);
		if ($path_res['num']>0) {
			$parent = intval($path_res['set'][0]["connected"]);
			$fullpath = array();
			$fullfile = array();
			$p = 0;
			while (true) {
				$fullpath[$p] = trim($path_res['set'][0]["filename"]);
				if (intval($path_res['set'][0]['isindex'])==1 && intval($path_res['set'][0]['level'])>1) {
					$fullfile[$p] = 'index';
                } else if (intval($path_res['set'][0]['isindex'])==1 && intval($path_res['set'][0]['connected'])==0) {
					$fullfile[$p] = 'index';
                } else {
					$fullfile[$p] = trim($path_res['set'][0]["filename"]);
                }
				if (intval($parent)==0): break; endif;
				$path_sql = "SELECT `connected`, `filename`, `isindex`, `level` FROM `menu` WHERE `mid` = ".intval($parent);
				$path_res = doSQL($path_sql);
				if ($path_res['num']>0): $parent = intval($path_res['set'][0]['connected']); else: $parent = 0; break; endif;
				$p++;
            }
			$fullpath = array_reverse($fullpath);
			$givebackpath = '';
			if ($depth==0) {
				$throwdir = array_pop($fullpath);
				$givebackpath = str_replace("//", "/", str_replace("//", "/", $basepath."/".implode("/", $fullpath)."/"));
            } else if ($depth==1) {
				$givebackpath = str_replace("//", "/", str_replace("//", "/", $basepath."/".implode("/", $fullpath)."/"));
            } else if ($depth==2) {
				$throwdir = array_pop($fullpath);
				$givebackpath = str_replace("//", "/", str_replace("//", "/", $basepath."/".implode("/", $fullpath)."/".array_shift($fullfile).".php"));
            } else {
				$givebackpath = str_replace("//", "/", str_replace("//", "/", $basepath."/"));
            }
        } else {
			$givebackpath = str_replace("//", "/", str_replace("//", "/", $basepath."/"));
        }
		// setting up language information
		if ($baselang!='de') {
			$givebackpath = str_replace("//", "/", str_replace("//", "/", "/".$baselang."/".$givebackpath));
		}
		return str_replace("//", "/", str_replace("//", "/", $givebackpath));
	}	// returnPath()
}

if (!(function_exists('returnStructureArray'))):
// creates an structured array with all mids relating to given startmid (e.g. 0 => full tree)
function returnStructureArray($startmid=0,$depth=999) {
	$mnu = array();
	$str_sql = "SELECT `mid`, `connected`, `level` FROM `menu` WHERE `connected` = ".intval($startmid)." AND `trash` = 0 AND `visibility` = 1 ORDER BY `position` ASC";
	$str_res = doQL($str_sql);
	if ($str_res['num']>0):
		foreach ($str_res['set'] AS $smresk => $smresv):
			$mnu[intval($smresv['mid'])] = '';
		endforeach;
		if ($depth>1):
			foreach ($str_res['set'] AS $smresk => $smresv):
				$sub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($smresv['mid'])." AND `trash` = 0 AND `visibility` = 1 ORDER BY `position` ASC";
				$sub_res = doSQL($sub_sql);
				if ($sub_res['num']>0):
					$mnu[intval($smresv['mid'])] = returnStructureArray(intval($smresv['mid']),($depth-1));
				endif;
			endforeach;
		endif;
	endif;
	return $mnu;
	}
endif; // returnStructureArray();

/* xml-reader funktionen */
if (!(function_exists('startElement'))) {
	function startElement($parser, $name, $attrs) {
		global $insideitem, $tag, $title, $description, $link, $updated, $countitem;
		if ($insideitem) {
            $tag = $name;
        } else if ($name == "ENTRY") {
            $insideitem = true;
            $countitem = $countitem + 1;
        }
	}	// startElement()
}
if (!(function_exists('endElement'))) {
	function endElement($parser, $name) {
		global $insideitem, $tag, $title, $description, $link, $updated, $countitem, $topvalue, $xmlstyle;
		if ($name == "ENTRY" && $countitem<=$topvalue) {
            printf("<p><a href='http://%s' target=\"_blank\">%s</a></p>",trim($link),trim($title));
            printf("<p><em>%s</em></p>",substr(trim($updated),0,10));
            printf("<p>%s</p>", trim($description));
			$title = "";
			$description = "";
			$desc = "";
			$link = "";
			$updated = "";
			$insideitem = false;
		}
    }	// endElement()
}
if (!(function_exists('characterData'))) {
	function characterData($parser, $data) {
		global $insideitem, $tag, $title, $description, $link, $updated, $countitem;
		if ($insideitem) {
            switch ($tag) {
                case "TITLE":
                    $title .= $data;
                    break;
                case "SUMMARY":
                    $description .= $data;
                    break;
                case "ID":
                    $link .= $data;
                    break;
                case "UPDATED":
                    $updated .= $data;
                    break;
            }
		}
	}	// characterData()
}
// ende xml-reader funktionen

if (!(function_exists('feedReader'))) {
	function feedReader($rssfeed, $encode = "auto", $count = 10, $mode = 0, $dlength = 0) {
		/*
		mode 0 = linked item title
		mode 1 = adding linked channel title
		mode 2 = linked item title + description (optional dlength > 0 shortens description) 
		mode 3 = 2 + 1
		mode 4 = linked item title + publishing date + description (optional dlength > 0 shortens description)
		mode 5 = 4 + 1
		*/
		/* read file */
		$data = @file($rssfeed);
		$data = implode ("", $data);
		if (strpos($data,"</item>")>0) {
			/* regular rss feed */
			preg_match_all("/<item.*>(.+)<\/item>/Uism", $data, $items);
			$atom = 0;
		} else if (strpos($data,"</entry>")>0) {
			/* atom feed */
			preg_match_all("/<entry.*>(.+)<\/entry>/Uism", $data, $items);
			$atom = 1;
		}
		/* encoding */
		if($encode == "auto") {
			preg_match("/<?xml.*encoding=\"(.+)\".*?>/Uism", $data, $encodingarray);
			$encoding = $encodingarray[1];
		} else {
			$encoding = $encode;
		}
		echo "<div class=\"feedreader_area\">\n";
		/* linked channel title */
		if ($mode==1 || $mode==3 || $mode==5) {
			if(strpos($data,"</item>")>0) {
				$data = preg_replace("/<item.*>(.+)<\/item>/Uism", '', $data);
			} else {
				$data = preg_replace("/<entry.*>(.+)<\/entry>/Uism", '', $data);
			}
			preg_match("/<title.*>(.+)<\/title>/Uism", $data, $channeltitle);
			if ($atom==0) {
				preg_match("/<link>(.+)<\/link>/Uism", $data, $channellink);
			} else if ($atom==1) {
				preg_match("/<link.*alternate.*text\/html.*href=[\"\'](.+)[\"\'].*\/>/Uism", $data, $channellink);
			}
			$channeltitle = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $channeltitle);
			$channellink = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $channellink);
			echo "<h1 class=\"feedreader_channel\"><a href=\"".$channellink[1]."\" title=\"";
			if ($encode != "no") {
				echo htmlentities($channeltitle[1],ENT_QUOTES,$encoding);
			} else {
				echo $channeltitle[1];
			}
			echo "\">";
			if ($encode!="no") {
				echo htmlentities($channeltitle[1],ENT_QUOTES,$encoding);
			} else {
				echo $channeltitle[1];
            }
			echo "</a></h1>\n";
        }
		/* items */
		// Titel, Link und Beschreibung der Items
		foreach ($items[1] as $item) {
			preg_match("/<title.*>(.+)<\/title>/Uism", $item, $title);
			if ($atom==0) {
				preg_match("/<link>(.+)<\/link>/Uism", $item, $link);
				preg_match("/<description>(.*)<\/description>/Uism", $item, $description);
				preg_match("/<pubDate>(.*)<\/pubDate>/Uism", $item, $published);
			} else if ($atom==1) {
				preg_match("/<link.*alternate.*text\/html.*href=[\"\'](.+)[\"\'].*\/>/Uism", $item, $link);
				preg_match("/<summary.*>(.*)<\/summary>/Uism", $item, $description);
				preg_match("/<updated>(.*)<\/updated>/Uism", $item, $published);
            }
			
			$title = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $title);
			$published = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $published);
			$description = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $description);
			$link = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $link);
			
			echo "<p class=\"link\">\n";
			echo "<a href=\"".$link[1]."\" title=\"";
			if($encode != "no")
			{echo htmlentities($title[1],ENT_QUOTES,$encoding);}
			else
			{echo $title[1];}
			echo "\">";
			if($encode != "no")
			{echo htmlentities($title[1],ENT_QUOTES,$encoding)."</a>\n";}
			else
			{echo $title[1]."</a>\n";}
			echo "</p>\n";
			
			if($mode==4 && ($published[1]!="" && $published[1]!=" ")) {
				echo "<p class=\"feedreader_date\">\n";
				if($encode != "no") {
                    echo htmlentities($published[1],ENT_QUOTES,$encoding)."\n";
                } else {
                    echo $published[1];
                }
				echo "</p>\n";
			}
			/* description */
			if($mode>1 && ($description[1]!="" && $description[1]!=" ")):
				echo "<p class=\"description\">\n";
				if($encode!="no"):
					echo htmlentities($description[1],ENT_QUOTES,$encoding)."\n";
				else:
					echo $description[1];
				endif;
				echo "</p>\n";
			endif;
			if ($count-- <= 1) break;
        }
		echo "</div>\n\n";
		} // feedReader
		/* base script developed by: Sebastian Gollus, http://www.web-spirit.de */
}

if (!(function_exists('showOpenerCloser'))) {
	function showOpenerCloser($idtag,$status) {
		$soc = "<a onclick=\"document.getElementById('".$idtag."').style.display = 'block'; document.getElementById('".$idtag."_closer').style.display = 'inline'; document.getElementById('".$idtag."_opener').style.display = 'none'; setOpenTab('".$idtag."_opener','open');\"><span id=\"".$idtag."_opener\" class=\"bubblemessage\"";
		if ($status=="open") {
            $soc.= " style=\"display: none;\"";
		}
		$soc.= ">".returnIntLang('fset open', false)."</span></a> <a onclick=\"document.getElementById('".$idtag."').style.display = 'none'; document.getElementById('".$idtag."_closer').style.display = 'none'; document.getElementById('".$idtag."_opener').style.display = 'inline'; setOpenTab('".$idtag."_opener','closed');\"><span id=\"".$idtag."_closer\" class=\"bubblemessage\"";
		if ($status!="open") {
            $soc.= " style=\"display: none;\"";
		}
		$soc.= ">".returnIntLang('fset close', false)."</span></a>";
		if ($status!="open") {
            $soc.= "<style type=\"text/css\"><!-- #".$idtag." { display: none; } --></style>";
		}
		return $soc;
	} // showOpenerCloser()
}
if (!(function_exists('legendOpenerCloser'))) {
	function legendOpenerCloser($idtag, $stat = 1) {
		if (array_key_exists($idtag, $_SESSION['opentabs'])) {
			$status = $_SESSION['opentabs'][$idtag];
        } else {
			$status = 'display: none;';
        }
		if ($stat==0) { 
            $status = 'display: none;';
        }
		if ($status=='display: none;' || trim($status)=='') {
			$_SESSION['opentabs'][$idtag] = 'display: none;';
			$setstyle = 'none';
		} else {
			$_SESSION['opentabs'][$idtag] = 'display: block;';
			$setstyle = 'block';
		}
		$soc = "<span class=\"opencloseButton bubblemessage\" rel=\"".$idtag."\">↕</span>\n";
		$soc.= "<script language=\"JavaScript\" type=\"text/javascript\">\n";
		$soc.= "\$(document).ready(function() {\$('#".$idtag."').css('display', '".$setstyle."')});\n";
		$soc.= "</script>";
		return $soc;
	} // legendOpenerCloser()
}

if (!(function_exists('lengthQuality'))) {
	function lengthQuality($string,$best,$max) {
        if (strlen($string)<=ceil($best/4)) {
            $qualstat = 1;
        } else if (strlen($string)<=ceil($best/4*3)) {
            $qualstat = 2;
		} else if (strlen($string)<=$best) {
            $qualstat = 3;
		} else if (strlen($string)<=($best+($max-$best)/3)) {
            $qualstat = 4;
		} else if (strlen($string)<=($best+($max-$best)/3*2)) {
            $qualstat = 5;
		} else {
		  $qualstat = 6;
        }
		return $qualstat;
	} // lengthQuality()
}

// convert doubleslashes in strings to ascii-expression to use with inputfields
if (!(function_exists('prepareTextField'))):
	function prepareTextField($givenstring) {
		if (!empty($givenstring)) {
			$string = str_replace("\"","&#34;",$givenstring);
		} else {
			$string = '';
		}
		return $string;
		}
endif;

// check given raw strings for utf and converts to, if needed
if (!(function_exists("setUTF8"))) { 
	function setUTF8($givenstring) { 
		$stringtype = mb_detect_encoding($givenstring);
		if (trim($stringtype) && mb_check_encoding($givenstring, $stringtype)) {
			return $stringtype === 'UTF-8' ? $givenstring : mb_convert_encoding($givenstring, 'UTF-8');
		}
		return mb_convert_encoding($givenstring, 'UTF-8');
	}
}

if (!(function_exists('mediaDirList'))):
	function mediaDirList($path, $basefolder = '') {
		$showdir = dir(str_replace("//","/",str_replace("//","/",$_SERVER['DOCUMENT_ROOT'].$path)));
		if ($showdir):
			while (false !== ($folder = $showdir->read())):
				if (substr($folder, 0, 1) != '.'):
					if (is_dir($_SERVER['DOCUMENT_ROOT'].$path."/".$folder) && $folder!="thumbs"):
						$GLOBALS['directory'][] = str_replace("//","/",str_replace("//","/",$path."/".$folder));
						mediaDirList(str_replace("//","/", str_replace("//","/",$path."/".$folder)), $folder);
					endif;
				endif;
			endwhile;
		endif;
		$showdir->close();
		}
endif;

/**
 * ermittelt alle Download-Dateien und gibt sie fuer ein select aufbereitet zurueck
 *
 * @param string $path Unterverzeichnis, das aufgelistet werden soll
 * @return $mediafiles
 */
if (!(function_exists('getDownloadFiles'))) {
	function getDownloadFiles($path='/', $selected=array(), $toppath = '', $trimname = 40, $buildforjs = true) {
		// array $selected abfangen 
		if (!(is_array($selected))) {
			$selected = array($selected);
        }
		$mediafiles = '';
		$files = array();
        // get hidemedia option
		$hide_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddendownload'";
		$hide_res = doResultSQL($hide_sql);
		// define hidemedia sql statement
		$hiddenmedia = array();
		if ($hide_res!==false && trim($hide_res)!=''): 
			$hiddenmedia = explode(",", trim($hide_res));
            foreach ($hiddenmedia AS $hmk => $hmv) {
                $hiddenmedia[$hmk] = trim($hmv);
            }
		endif;
        $dir = array();
		if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/download".$path)) {
			$d = dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/download".$path);
			while (false !== ($entry = $d->read())) {
				if (substr($entry, 0, 1)!='.') {
					if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/download".$path.$entry)) {
						$files[] = $path.$entry;
					} else if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/download".$path.$entry) && !(in_array(trim($entry),$hiddenmedia))) {
						$dir[] = $path.$entry;
					}
                }
            }
			$d->close();
			sort($files);
			sort($dir);
			foreach($files AS $value) {
				$mediafiles .= "<option value='".trim($value)."'";
				if (in_array($value, $selected)):
					$mediafiles .= " selected='selected'";
				endif;
				$mediafiles .= ">";
				$mediadesc = '';
				$desc_sql = "SELECT `filedesc` FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//", "/", str_replace("//", "/", $value))."%'";
				$desc_res = doResultSQL($desc_sql);
				if ($desc_res!==false && trim($desc_res)!='') {
					$mediadesc = setUTF8(trim($desc_res));
                }
				if (trim($toppath)!="" && $toppath!="/") {
					$value = str_replace("//", "/", str_replace("//", "/", str_replace($toppath, "", $value)));
				}
				if (trim($mediadesc)!="") {
					$mediafiles .= $mediadesc;
//				} else if (strlen($value)>$trimname) {
//					$mediafiles .= substr($value,0,5)."...".substr($value,-($trimname-5));
				} else {
					$mediafiles .= $value;
				}
				$mediafiles .= "</option>"; 
                if (!($buildforjs)) { $mediafiles .= "\n"; }
            }   
			foreach($dir AS $value) {
				$mediafiles .= "<optgroup label=\"".substr($value,1)."\">"; if (!($buildforjs)) { $mediafiles .= "\n"; }
				$mediafiles .= getDownloadFiles($value.'/', $selected, $toppath, $trimname, $buildforjs);
				$mediafiles .= "</optgroup>"; if (!($buildforjs)) { $mediafiles .= "\n"; }
			}
		}
		return $mediafiles;
    }	// getDownloadFiles()
}

if (!(function_exists('getImageDesc'))) {
    function getImageDesc($imgpath) {
        
    }
} // getImageDesc

/**
 * ermittelt alle Bild-Dateien und gibt sie fuer ein select aufbereitet zurueck
 * deprecated from 2015-03-17
 *
 * @param string $path Unterverzeichnis, das aufgelistet werden soll
 * @return $mediafiles
 */
if (!(function_exists('getImageFiles'))):
	function getImageFiles($path = '/', $selected = [], $toppath = '', $trimname = 40, $buildforjs = true) {
		//
		// array $selected abfangen 
		//
		if (!(is_array($selected))): $selected = [$selected]; endif;
		// set empty return string
		$mediafiles = '';
		// get hidemedia option
		$hide_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddenmedia'";
		$hide_res = doResultSQL($hide_sql);
		// define hidemedia sql statement
		$hidemedia = "";
		if ($hide_res!==false && trim($hide_res)!=''): 
			$hiddenmedia = explode(",", trim($hide_res));
			$hideoption = array(" `filefolder` NOT LIKE '/thumbs/%' ");
			foreach ($hiddenmedia AS $k => $v):
				$hideoption[] = " `filefolder` NOT LIKE '/".$v."/%' ";
			endforeach;
			$hidemedia = " AND (".implode(" AND ", $hideoption).") ";	
		endif;
		// get last 10 uploads
		$l_sql = "SELECT * FROM `wspmedia` WHERE `mediatype` = 'images' AND `filefolder` LIKE '".$path."%' ".$hidemedia." ORDER BY `filedate` DESC, `filefolder` ASC, `filename` ASC LIMIT 0,10";
		$l_res = doSQL($l_sql);

        if ($l_res['num']>0):
			$mediafiles.= '<optgroup label="last uploaded">';
			if (!($buildforjs)): $mediafiles .= "\n"; endif;
			$mediafiles .= "<option value=\".\" >last uploaded</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			foreach ($l_res['set'] AS $lrsk => $lrsv) {
				$value = str_replace("//", "/", str_replace("//", "/", trim("/".trim($lrsv['filefolder'])."/".trim($lrsv['filename']))));
				$mediafiles .= "<option value=\"".$value."\" >";
				$mediadesc = '';
				$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".$value."%'";
				$desc_res = doResultSQL($desc_sql);
				if ($desc_res!==false && trim($desc_res)!='') {
					$mediadesc = setUTF8(trim($desc_res));
                }
				if (trim($toppath)!="" && $toppath!="/"):
					$value = str_replace($toppath, "", $value);
				endif;
				if (trim($mediadesc)!="") {
					$mediafiles .= $mediadesc;
				} else if (strlen($value)>$trimname) {
					$mediafiles .= substr($value,0,5)."...".substr($value,-($trimname-5));
				} else {
					$mediafiles .= $value;
				}
				$mediafiles .= "</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
            }
			$mediafiles.= '</optgroup>';
			if (!($buildforjs)): $mediafiles .= "\n"; endif;
		endif;
		
		$i_sql = "SELECT * FROM `wspmedia` WHERE `mediatype` = 'images' AND `filefolder` LIKE '".$path."%' ".$hidemedia." ORDER BY `filefolder`, `filename`";
		$i_res = doSQL($i_sql);
		
		if ($i_res['num']>0):
			$mediafiles.= "<optgroup label=\"".str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", ("/".$path."/")))))."\">";
			if (!($buildforjs)): $mediafiles .= "\n"; endif;
            $filefolder = '';
			foreach ($i_res['set'] AS $irsk => $irsv) {
				if ($r>0 && (trim($irsv['filefolder'])!=$filefolder)):
					$mediafiles.= '</optgroup>';
					if (!($buildforjs)): $mediafiles .= "\n"; endif;
					$mediafiles.= "<optgroup label=\"".str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", ("/".trim($irsv['filefolder'])."/")))))."\">";
					if (!($buildforjs)): $mediafiles .= "\n"; endif;
				endif;
                $filefolder = trim($irsv['filefolder']);
				$value = str_replace("//", "/", str_replace("//", "/", trim("/".trim($irsv['filefolder'])."/".trim($irsv['filename']))));
				$mediafiles .= "<option value=\"".$value."\" ";
				if (in_array($value, $selected)):
					$mediafiles .= " selected=\"selected\"";
				endif;
				$mediafiles .= ">";
				$mediadesc = '';
				$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".$value."%'";
				$desc_res = doResultSQL($desc_sql);
				if ($desc_res!==false && trim($desc_res)!='') {
					$mediadesc = setUTF8(trim($desc_res));
                }
				if (trim($toppath)!="" && $toppath!="/"):
					$value = str_replace($toppath, "", $value);
				endif;
				if (trim($mediadesc)!="") {
					$mediafiles .= $mediadesc;
				} else if (strlen($value)>$trimname) {
					$mediafiles .= substr($value,0,5)."...".substr($value,-($trimname-5));
				} else {
					$mediafiles .= $value;
				}
				$mediafiles .= "</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
            }
			$mediafiles.= '</optgroup>';
			if (!($buildforjs)): $mediafiles .= "\n"; endif;
		endif;
		
		return $mediafiles;
		}	// getImageFiles()
endif;

if (!(function_exists('mediaLoc'))):
// returns type of mediadb for different wsp versions
function mediaLoc() {
    $return = false;
    $mdb = doSQL("SELECT `mid` FROM `wspmedia`");
    if (intval($mdb['num'])>0) {
        $mtdb = doSQL("SELECT `mid` FROM `wspmedia` WHERE (`mediatype` = '' || `mediatype` = NULL) AND (`mediafolder` = '' || `mediafolder` = NULL)");
        if (intval($mtdb['num'])>0) {
            // new system without `mediafolder`
            $return = ' `filepath` ';
        } else {
            $return = ' CONCAT ( `mediafolder`,`filename` ) ';
        }
    }
    return $return;
}
endif;

/**
 * ermittelt alle Bild-Dateien und gibt sie fuer ein select aufbereitet zurueck
 * new since 2015-03-17
 * @return $mediafiles
 */

if (!(function_exists('imageSelect'))) {
	// path => startpfad der suche 
	// toppath => point in path, from which data will be returned as value (e.g. path = / , toppath = /media/images => return will start below /images)
	// hidepath => hide path in selection (show only filenames)
	// selected => array ausgewählter Dateien
	function imageSelect($path = '/', $toppath = '', $hidepath = false, $selected = [], $trimname = 60, $buildforjs = true) {
		if (isset($_SESSION['wspvars']['stripfilenames']) && intval($_SESSION['wspvars']['stripfilenames'])>intval($trimname)):
			$trimname = intval($_SESSION['wspvars']['stripfilenames']);
		endif;
		// get hidemedia option
		$hide_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddenmedia'";
		$hide_res = doResultSQL($hide_sql);
		// define hidemedia sql statement
		$hidemedia = "";
		$hiddenmedia = [];
		if ($hide_res!==false && trim($hide_res)!=''): 
			$hiddenmedia = explode(",", trim($hide_res));
			$hideoption = array(" `filefolder` NOT LIKE '/thumbs/%' ");
			foreach ($hiddenmedia AS $k => $v):
				$hideoption[] = " `filefolder` NOT LIKE '/".$v."/%' ";
			endforeach;
			$hidemedia = " AND (".implode(" AND ", $hideoption).") ";	
		endif;
		// prepare selected as array
		if (!(is_array($selected))): $selected = array($selected); endif;
		// prepare toppath as path
		$toppath = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".$toppath."/"))));
		// unset mediafiles
		$mediafiles = '';
		// get last 10 uploads
		$l_sql = "SELECT * FROM `wspmedia` WHERE `mediatype` = 'images' AND `filefolder` LIKE '".$path."%' ".$hidemedia." ORDER BY `filedate` DESC, `filefolder` ASC, `filename` ASC LIMIT 0,10";
		$l_res = doSQL($l_sql);
		if ($l_res['num']>0) {
			$mediafiles.= '<optgroup label="last uploaded">';
			$mediafiles .= "<option value=\"#\" >last uploaded</option>"; if (!($buildforjs)) { $mediafiles .= "\n"; }
			if (!($buildforjs)) { $mediafiles .= "\n"; }
			foreach ($l_res['set'] AS $lresk => $lresv) {
				$value = str_replace("//", "/", str_replace("//", "/", trim("/".trim($lresv['filefolder'])."/".trim($lresv['filename']))));
				if (trim($toppath)!="" && $toppath!="/") {
					$value = str_replace($toppath, "", $value);
				}
                $mediafiles .= "<option value=\"".$value."\" >";
				$mediadesc = '';
				$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".escapeSQL($value)."%'";
				$desc_res = doResultSQL($desc_sql);
				if ($desc_res!==false && trim($desc_res)!='') {
					$mediadesc = setUTF8(trim($desc_res));
                }
				if (trim($mediadesc)!="") {
					$mediafiles .= $mediadesc;
				} else if (strlen($value)>$trimname) {
					$mediafiles .= substr($value,0,5)."...".substr($value,-($trimname-5));
				} else {
					$mediafiles .= $value;
				}
				$mediafiles .= "</option>"; if (!($buildforjs)) { $mediafiles .= "\n"; }
			}
			$mediafiles .= "<option value=\"#\" >--------</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			$mediafiles.= '</optgroup>';
			if (!($buildforjs)): $mediafiles .= "\n"; endif;
        }
		
		// setup empty arrays
		$files = array();
		$dir = array();
		if (is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/".$path)))):
			$d = dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/".$path)));
			while (false !== ($entry = $d->read())) {
				// get only folders with images in
				if (substr($entry, 0, 1)!='.' && (stristr($path.$entry, 'images') || stristr($path.$entry, 'screen')) && !(in_array($entry, ($hiddenmedia ?? [])))) {
					if (is_file(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media/'.$path."/".$entry)))) {
						$files[] = str_replace("//", "/", str_replace("//", "/", "/media/".$path."/".$entry));
					} else if (is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media/'.$path."/".$entry)))) {
						$dir[] = str_replace("//", "/", str_replace("//", "/", "/".$path."/".$entry."/"));
					}
				}
			}
			$d->close();
			sort($files);
			sort($dir);
			$mediafiles .= "<optgroup label=\"".str_replace("//", "/", str_replace("//", "/", "/media/".$path))."\">"; if (!($buildforjs)) { $mediafiles .= "\n"; }
			foreach($files AS $value) {
				$returnvalue = $value;
                if (trim($toppath)!='/' && strpos($value, $toppath)===0):
					$returnvalue = str_replace("//", "/", str_replace("//", "/", "/".substr($value, strlen(trim($toppath)))));
				endif;
				$showvalue = $value;
				if ($hidepath):
					$showvalue = substr($value, (strrpos($value, "/")+1));
				endif;
				$mediafiles .= "<option value=\"".$returnvalue."\"";
				if (in_array($returnvalue, $selected)):
					$mediafiles .= " selected=\"selected\"";
				endif;
				$mediafiles .= ">";
				$mediadesc = '';
				$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//", "/", str_replace("//", "/", $value))."%'";
				$desc_res = doResultSQL($desc_sql);
				if ($desc_res!==false && trim($desc_res)!='') {
					$mediadesc = setUTF8(trim($desc_res));
                }
				if (trim($mediadesc)!="") {
					$mediafiles .= $mediadesc;
				} else if (strlen($showvalue)>$trimname) {
					$mediafiles .= substr($showvalue,0,5)."...".substr($showvalue,-($trimname-5));
                } else {
					$mediafiles .= $showvalue;
                }
				$mediafiles .= "</option>"; if (!($buildforjs)) { $mediafiles .= "\n"; }
            }
			$mediafiles .= "</optgroup>"; if (!($buildforjs)) { $mediafiles .= "\n"; }
			foreach($dir AS $value) {
				if (trim(imageSelect($value, $toppath, $hidepath, $selected, $trimname, $buildforjs))!='') {
					$mediafiles .= imageSelect($value, $toppath, $hidepath, $selected, $trimname, $buildforjs);
				}
			}
		endif;
		return $mediafiles;
		}	// imageSelect()
}

if (!(function_exists('documentSelect'))) {
	// path => startpfad der suche 
	// toppath => point in path, from which data will be returned as value (e.g. path = / , toppath = /images => return will start below /images)
	// hidepath => hide path in selection (show only filenames)
	// selected => array ausgewählter Dateien
	function documentSelect($path = '/', $toppath = '', $hidepath = false, $selected = [], $trimname = 60, $buildforjs = true) {
		if (isset($_SESSION['wspvars']['stripfilenames']) && intval($_SESSION['wspvars']['stripfilenames'])>intval($trimname)) {
			$trimname = intval($_SESSION['wspvars']['stripfilenames']);
		}
		// get hidemedia option
		$hide_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddendownload'";
		$hide_res = doResultSQL($hide_sql);
		// define hidemedia sql statement
		$hidemedia = "";
        $hiddenmedia = array();
		// always remove thumbs and preview from download
		$hideoption = array(" ".mediaLoc()." NOT LIKE '/media/download/thumbs/%' ", " ".mediaLoc()." NOT LIKE '/media/download/preview/%' ");
		if ($hide_res!==false && trim($hide_res)!='') { 
			$hiddenmedia = explode(",", trim($hide_res));
            foreach ($hiddenmedia AS $k => $v) {
				$hiddenmedia[$k] = trim($v);
                $hideoption[] = " ".mediaLoc()." NOT LIKE '".str_replace("//", "/", str_replace("//", "/", "/media/download/".trim($v)."/"))."%' ";
			}
		}
		$hidemedia = " AND (".implode(" AND ", $hideoption).") ";
		// prepare selected as array
		if (!(is_array($selected))) { 
			$selected = explode(",", $selected); 
		}
		// prepare toppath as path
		$toppath = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".$toppath."/"))));
		// unset mediafiles
		$mediafiles = '';
		/*
        // get last 10 uploads
        if ($path=='/') {
            $l_sql = "SELECT ".mediaLoc()." AS 'filepath' FROM `wspmedia` WHERE ".mediaLoc()." LIKE '".str_replace("//", "/", str_replace("//", "/", "/media/download/".$path."/_"))."%' ".$hidemedia." AND `filesize` > 0 ORDER BY `filedate` DESC, ".mediaLoc()." ASC, `filename` ASC LIMIT 0,10";
            $l_res = doSQL($l_sql);
            if ($l_res['num']>0) {
                $mediafiles.= '<optgroup label="'.returnIntLang('dropdown label last uploaded', false).'">';
                if (!($buildforjs)) { $mediafiles .= "\n"; }
                foreach ($l_res['set'] AS $lresk => $lresv) {
                    $value = str_replace("//", "/", str_replace("//", "/", trim("/".trim($lresv['filepath']))));
                    $showvalue = $value;
                    if (trim($toppath)!="" && $toppath!="/") {
                        $value = str_replace("//", "/", str_replace("//", "/", str_replace($toppath, "/", $value)));
				    }
                    $mediafiles .= "<option value=\"".$value."\" >";
                    $mediadesc = '';
                    $desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '".escapeSQL($showvalue)."'";
                    $desc_res = doResultSQL($desc_sql);
                    if ($desc_res!==false && trim($desc_res)!='') {
                        $mediadesc = setUTF8(trim($desc_res));
                    }
                    if ($hidepath) {
                        $showvalue = basename($showvalue);
                    }
                    if (trim($mediadesc)!="") {
                        $mediafiles.= $mediadesc;
                    } else if (strlen($showvalue)>$trimname) {
                        $mediafiles.= substr($showvalue,0,5)."...".substr($showvalue,-($trimname-5));
                    } else {
                        $mediafiles.= $showvalue;
                    }
                    $mediafiles.= "</option>"; if (!($buildforjs)) { $mediafiles .= "\n"; }
                }
                $mediafiles.= '</optgroup>';
                if (!($buildforjs)): $mediafiles .= "\n"; endif;
            }
        }
		*/

		$l_sql = "SELECT ".mediaLoc()." AS 'filepath' FROM `wspmedia` WHERE ".mediaLoc()." LIKE '".str_replace("//", "/", str_replace("//", "/", "/media/download/".$path."/"))."%' ".$hidemedia." AND `filesize` > 0 ORDER BY ".mediaLoc()." ASC, `filename` ASC";
		$l_res = doSQL($l_sql);
		
		// setup empty arrays
		$files = array();
		// $dir = array();

		if ($l_res['num']>0) {
			foreach ($l_res['set'] AS $fileName) {
				$files[] = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $fileName)));
			}
		}

		if (count($files)>0) {
			$dirname = '';
			$optgroup = false;
			foreach($files AS $value) {
				$returnvalue = trim($value['filepath']);
				$showvalue = $value['filepath'];
				if (trim($toppath)!='/' && strpos($value, $toppath)===0):
					$returnvalue = str_replace("//", "/", str_replace("//", "/", "/".substr($value, strlen(trim($toppath)))));
				endif;
				if ($hidepath):
					$showvalue = basename($value['filepath']);
					if ($dirname!=dirname($value['filepath'])) {
						$dirname = dirname($value['filepath']);
						if ($optgroup) { $mediafiles .= '</optgroup>'; if (!($buildforjs)) { $mediafiles .= "\n"; }  }
						$mediafiles .= '<optgroup label="' . $dirname . '">'; if (!($buildforjs)) { $mediafiles .= "\n"; }
						$optgroup = true;
					}
				endif;
				$mediafiles .= "<option value=\"".$returnvalue."\" ";
				if (in_array($returnvalue, $selected)):
					$mediafiles .= " selected=\"selected\" ";
				endif;
				$mediafiles .= ">";
				$mediadesc = '';
				$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//", "/", str_replace("//", "/", $value['filepath']))."%'";
				$desc_res = doResultSQL($desc_sql);
				if ($desc_res!==false && trim($desc_res)!='') {
					$mediadesc = setUTF8(trim($desc_res));
				}
				if (trim($mediadesc)!="") {
					$mediafiles .= $mediadesc;
				} else if (strlen($showvalue)>$trimname) {
					$mediafiles .= substr($showvalue,0,5)."...".substr($showvalue,-($trimname-5));
				} else {
					$mediafiles .= $showvalue;
				}
				$mediafiles .= "</option>"; if (!($buildforjs)) { $mediafiles .= "\n"; }
			}
			if ($optgroup) { $mediafiles .= '</optgroup>'; if (!($buildforjs)) { $mediafiles .= "\n"; }  }
		}

		return $mediafiles;
    }
}


/**
 * ermittelt alle Bild-Dateien und gibt sie fuer ein select aufbereitet zurueck
 *
 * @param string $path Unterverzeichnis, das aufgelistet werden soll
 * @return $mediafiles
 */
if (!(function_exists('getFlashFiles'))):
	function getFlashFiles($path='/', $selected = [], $toppath = '', $trimname = 40, $buildforjs = true) {
		//
		// array $selected abfangen 
		//
		if (!(is_array($selected))):
			$selected = array($selected);
		endif;
		$mediafiles = '';
		$files = array();
		$dir = array();
		if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/flash".$path)):
			$d = dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/flash".$path);
			while (false !== ($entry = $d->read())) {
				if (substr($entry, 0, 1)!='.') {
					if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media/flash'.$path.$entry)) {
						if (substr($entry,-8)!="_pvw.jpg" && substr($entry,-8)!="_pvw.png") {
							$files[] = $path.$entry;
                        }
                    } else if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media/flash'.$path.$entry) && str_replace("/","",trim($entry))!="thumbs") {
                        $dir[] = $path.$entry;
                    }
                }
            }
            $d->close();
			sort($files);
			sort($dir);
			foreach($files AS $value) {
				$mediafiles .= "<option value=\"".$value."\"";
				if (in_array($value, $selected)) {
					$mediafiles .= " selected=\"selected\"";
				}
				$mediafiles .= ">";
				$mediadesc = '';
				$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//", "/", str_replace("//", "/", $value))."%'";
				$desc_res = doResultSQL($desc_sql);
				if ($desc_res!==false && trim($desc_res)!='') {
					$mediadesc = setUTF8(trim($desc_res));
                }
				if (trim($toppath)!="" && $toppath!="/") {
					$value = str_replace($toppath, "", $value);
				}
				if (trim($mediadesc)!="") {
					$mediafiles .= $mediadesc;
				} else if (strlen($value)>$trimname) {
					$mediafiles .= substr($value,0,5)."...".substr($value,-($trimname-5));
				} else {
					$mediafiles .= $value;
                }
				$mediafiles .= "</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			}
			foreach($dir AS $value):
				$mediafiles .= "<optgroup label=\"".substr($value,1)."\">"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
				$mediafiles .= getFlashFiles($value.'/', $selected, $toppath, $trimname, $buildforjs);
				$mediafiles .= "</optgroup>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			endforeach;
		endif;
		return $mediafiles;
		}	// getFlashFiles()
endif;

/**
 * ermittelt alle Media-Dateien und gibt sie fuer ein select aufbereitet zurueck
 *
 * @param string $path Unterverzeichnis, das aufgelistet werden soll
 * @return $mediafiles
 */
if (!(function_exists('getMediaDownload'))):
	function getMediaDownload($path = '/', $selected = [], $toppath = '', $trimname = 40, $buildforjs = true) {
		//
		// array $selected abfangen 
		//
		if (!(is_array($selected))):
			$selected = array($selected);
		endif;
		$mediafiles = '';
		$files = array();
		$dir = array();
		if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media".$path)):
			$d = dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media".$path);
			while (false !== ($entry = $d->read())) {
				if (substr($entry, 0, 1)!='.') {
					if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media'.$path.$entry)) {
						$files[] = $path.$entry;
                    } else if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media'.$path.$entry) && str_replace("/","",trim($entry))!="thumbs" && str_replace("/","",trim($entry))!="flash" && str_replace("/","",trim($entry))!="screen") {
						$dir[] = $path.$entry;
                    }
                }
            }
			$d->close();
			sort($files);
			sort($dir);
			foreach($files AS $value):
				$mediafiles .= "<option value=\"".$value."\"";
				if (in_array($value, $selected)):
					$mediafiles .= " selected=\"selected\"";
				endif;
				$mediafiles .= ">";
				$mediadesc = '';
				$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//", "/", str_replace("//", "/", $value))."%'";
				$desc_res = doResultSQL($desc_sql);
				if ($desc_res!==false && trim($desc_res)!='') {
					$mediadesc = setUTF8(trim($desc_res));
                }
				if (trim($toppath)!="" && $toppath!="/") {
					$value = str_replace($toppath, "", $value);
                }
				if (trim($mediadesc)!="") {
					$mediafiles .= $mediadesc;
                } else if (strlen($value)>$trimname) {
					$mediafiles .= substr($value,0,5)."...".substr($value,-($trimname-5));
                } else {
					$mediafiles .= $value;
                }
				$mediafiles .= "</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			endforeach;
			foreach($dir AS $value):
				$mediafiles .= "<optgroup label=\"".substr($value,1)."\">"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
				$mediafiles .= getMediaDownload($value.'/', $selected, $toppath, $trimname, $buildforjs);
				$mediafiles .= "</optgroup>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			endforeach;
		endif;
		return $mediafiles;
		}	// getMediaDownload()
endif;

/**
* MySQL-Fehler ausgeben
*/
if (!(function_exists('writeMySQLError'))):
	function writeMySQLError($sql = "") { addWSPMsg('errormsg', 'writeMySQLError is deprecated'); }	// writeMySQLError()
endif;

// output messages 
if (!(function_exists('addWSPMsg'))):
	function addWSPMsg($msgtarget, $msg = '') {
		if (isset($msgtarget) && $msgtarget!='' && array_key_exists($msgtarget, $_SESSION['wspvars'])):
			$_SESSION['wspvars'][$msgtarget].= $msg;
		else:
			$_SESSION['wspvars'][$msgtarget] = $msg;	
		endif;
		}
endif;

// copyImage()-function to copy one single image from modules, etc.
if (!(function_exists('copyImage'))):
	function copyImage($tmpdata = '', $tmpfilename = '') {
		if (trim($tmpdata)!='' && $tmpfilename!=''):
			// try ftp-login
			$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
			if ($ftp):
				$login = ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
				if ($login):
					$finalfilename = removeSpecialChar($tmpfilename);
					if (ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir']."/media/images/".$finalfilename), $tmpdata, FTP_BINARY)):
						return array('copy'=>true,'filename'=>"/".$finalfilename);
					else:
						return array('copy'=>false,'filename'=>'');
					endif;
					ftp_close($ftp);
				else:
					addWSPMsg('errormsg', 'could not login to ftp-server');
					return array('copy'=>false,'filename'=>'');
				endif;
			else:
				addWSPMsg('errormsg', 'could not connect to ftp-server');
				return array('copy'=>false,'filename'=>'');
			endif;
		else:
			return array('copy'=>true,'filename'=>'');
		endif;
		}
endif;

//
// new thumbnail function with gdlib
//
if (!(function_exists('resizeGDimage'))) {
	function resizeGDimage($orig, $dest, $factor=0, $width=0, $height=0, $format=1) {
        $imgsize = @getimagesize($orig);
        /*
        $imgsize[0] = orig width
        $imgsize[1] = orig height
        $imgsize['mime'] = type
        */
        $error = false;
        if ($imgsize['mime']=="image/jpeg") {
            $img = imagecreatefromjpeg($orig);
        } else if ($imgsize['mime']=="image/gif") {
            $img = @imagecreatefromgif($orig);
        } else if ($imgsize['mime']=="image/png") {
            $img = @imagecreatefrompng($orig);
        } else {
            $error = true;
            $_SESSION['wspvars']['errormsg'].= "<p>Beim &uuml;bergebenen Bildmaterial handelte es sich nicht um ein unterst&uuml;tztes Dateiformat.</p>";
        }
        if (intval($imgsize[0])==0 || intval($imgsize[1])==0):
            $error = true;
            $_SESSION['wspvars']['errormsg'].= "<p>Fehler bei der Bildkonvertierung [Bildgr&ouml;&szlig;e]</p>";
        endif;
        if (intval($factor)>0) {
            // faktorierte skalierung
            $newwidth = ceil((intval($factor)/100)*intval($imgsize[0]));
            $newheight = ceil((intval($factor)/100)*intval($imgsize[1]));
        } else if ((intval($width)>0 || intval($height)>0) && $format==1) {
            // breite und/oder hoehe gegeben und format bleibt erhalten
            if (intval($width)>0 && intval($height)==0) {
                // breite gegeben
                $newwidth = intval($width);
                $scale = $newwidth/intval($imgsize[0]);
                $newheight = ceil($scale*intval($imgsize[1]));
            } else if (intval($width)==0 && intval($height)>0) {
                // hoehe gegeben
                $newheight = intval($height);
                $scale = $newheight/intval($imgsize[1]);
                $newwidth = ceil($scale*intval($imgsize[0]));
            } else if (intval($width)>0 && intval($height)>0) {
                $newwidth = intval($width);
                $scale = $newwidth/intval($imgsize[0]);
                $newheight = ceil($scale*intval($imgsize[1]));
                if ($newheight>$height) {
                    $newheight = intval($height);
                    $scale = $newheight/intval($imgsize[1]);
                    $newwidth = ceil($scale*intval($imgsize[0]));
                }
            } else {
                $error = true;
                $_SESSION['wspvars']['errormsg'].= "<p>Fehler bei der Bildkonvertierung [Bildgr&ouml;&szlig;e]</p>";
            }
        } else if (intval($width)>0 && intval($height)>0) {
            // breite und hoehe gegeben
            $newwidth = intval($width);
            $newheight = intval($height);
        } else {
            $error = true;
            addWSPMsg('errormsg', "Fehler bei der Bildkonvertierung [Skalierung]");
        }
        if (!$error && $img) {
            if ($imgsize['mime']=="image/gif") {
                $new = imagecreate($newwidth, $newheight);
                imagecopyresized($new, $img, 0, 0, 0, 0, $newwidth, $newheight, $imgsize[0], $imgsize[1]);
                imagegif($new, $dest);
            } else if ($imgsize['mime']=="image/png") {
                // creating jpg-type cause error with transparent pngs
                $colortype = imagecolorstotal($img);
                $new = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresized($new, $img, 0, 0, 0, 0, $newwidth, $newheight, $imgsize[0], $imgsize[1]);
                imagejpeg($new, $dest, 90);
            } else {
                $new = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($new, $img, 0, 0, 0, 0, $newwidth, $newheight, $imgsize[0], $imgsize[1]);
                imagejpeg($new, $dest, 90);
            }
        }
    }
}

// check serialized arrays for broken contents and repair them
// thx to martin dordel for developing this function
if (!(function_exists('unserializeBroken'))) {
    function unserializeBroken($value, $arr = true) {
        if (is_array($value)) {
            return $value;
        } else if (!empty($value)) {
            $check = @unserialize(trim($value));
            if (is_array($check)) {
                return $check;
            } else {
                $tmpserialized = '';
                while (strlen($value)>0) {
                    $substring = substr($value, 0, 2);
                    if (strstr($substring, 'a:')) {
                        $posSemikolon = strpos($value, '{');
                        $substring2 = substr($value, 0, $posSemikolon+1);
                        $tmpserialized = $tmpserialized.$substring2;
                        $value = substr($value, $posSemikolon+1, strlen($value));
                    } else if (strstr($substring, 'i:')) {
                        $posSemikolon = strpos($value, ';');
                        $substring2 = substr($value, 0, $posSemikolon+1);
                        $tmpserialized = $tmpserialized.$substring2;
                        $value = substr($value, $posSemikolon+1, strlen($value));
                    } else if (strstr($substring, 's:')) {
                        $int = preg_match('/";[adis]:/', $value, $treffer, PREG_OFFSET_CAPTURE);
                        if ($int == 1) {
                            $substring2 = substr($value, 0, $treffer[0][1]+2);
                            $a = strpos($substring2, ':"');
                            $substring3 = substr($substring2, $a+2, ($treffer[0][1]));
                            $substring3 = substr($substring3, 0, strlen($substring3)-2);
                            $strlaenge = strlen($substring3);
                            $tmpserialized = $tmpserialized."s:".$strlaenge.":".'"'.$substring3.'";';
                            $value = substr($value, $treffer[0][1]+2, strlen($value));
                        } else {
                            preg_match('/";}/', $value, $treffer, PREG_OFFSET_CAPTURE);
                            $substring2 = substr($value, 0, $treffer[0][1]+2);
                            $a = strpos($substring2, ':"');
                            $substring3 = substr($substring2, $a+2, ($treffer[0][1]));
                            $substring3 = substr($substring3, 0, strlen($substring3)-2);
                            $strlaenge = strlen($substring3);
                            $tmpserialized = $tmpserialized."s:".$strlaenge.":".'"'.$substring3.'";';
                            $value = substr($value, $treffer[0][1]+2, strlen($value));
                        }
                    } else if (strstr($substring, 'd:')) {
                        $posSemikolon = strpos($value, ';');
                        $substring2 = substr($value, 0, $posSemikolon+1);
                        $tmpserialized = $tmpserialized.$substring2;
                        $value = substr($value, $posSemikolon+1, strlen($value));
                    } else if (strstr($substring, '}')) {
                        $tmpserialized = $tmpserialized."}";
                        $value = substr($value, 1, strlen($value));
                    } else {
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

if (!(function_exists('checkandsendMail'))):
	function checkandsendMail($value = array()) {
		$status = false;
		if(count($value)>0):
			if($_SESSION['wspvars']['mailclass']==1):
				require_once ($_SERVER['DOCUMENT_ROOT']."/wsp/data/include/phpmailer/class.phpmailer.php");
				$mail = new phpmailer();
		
				if (file_exists($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/rootphrase.inc.php")):
					include ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/rootphrase.inc.php");
					require_once ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/xtea/xtea.class.php");
					require_once ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php");
					// initiate xtea class
					$xtea = new XTEA($wspvars['rootphrase']);
					$smtpcon_sql = "SELECT * FROM `wspaccess` WHERE `type` = 'smtp' AND `definition` = 1";
					$smtpcon_res = doSQL($smtpcon_sql);
					if ($smtpcon_res['num']>0):
						$mail->IsSMTP(); // per SMTP verschicken
						$mail->Host = trim($smtpcon_res['set'][0]["host"]); // SMTP-Server
						$mail->SMTPAuth = true; // SMTP mit Authentifizierung benutzen
						$mail->Username = trim($smtpcon_res['set'][0]["username"]);  // SMTP-Benutzername
						$status = $xtea->Decrypt(trim($smtpcon_res['set'][0]["passphrase"]));
						$mail->Password = trim($xtea->Decrypt(trim($smtpcon_res['set'][0]["passphrase"]))); // SMTP-Passwort
					endif;
				endif;
				if($value['useHTML']==1):
					$mail->IsHTML(true);
					$mail->Body = $value['mailHTML'];
					$mail->AltBody = $value['mailTXT'];
				else:
					$mail->Body = $value['mailTXT'];
					$mail->AltBody = "";
				endif;
//				if($value['mailReturnPath']!=""):
//					$mail->Sender = $value['mailReturnPath'];
//				endif;
				$mail->From     = trim($value['mailFrom'][0]);
				$mail->FromName = setUTF8(trim($value['mailFrom'][1]));
				for($to=0;$to<count($value['mailTo']);$to++):
					$mail->AddAddress(trim($value['mailTo'][$to][0]),trim($value['mailTo'][$to][1]));
				endfor;
				if(count($value['mailCC'][0])>0):
					for($cc=0;$cc<count($value['mailCC']);$cc++):
						$mail->AddCC(trim($value['mailCC'][$cc][0]),trim($value['mailCC'][$cc][1]));
					endfor;
				endif;
				if(count($value['mailBCC'][0])):
					for($bcc=0;$bcc<count($value['mailBCC']);$bcc++):
						$mail->AddBCC(trim($value['mailBCC'][$bcc][0]),trim($value['mailBCC'][$bcc][1]));
					endfor;
				endif;
				$mail->AddReplyTo(trim($value['mailReply'][0]),trim($value['mailReply'][1]));			
				$mail->WordWrap = 50;                              // Zeilenumbruch einstellen
				// $mail->AddAttachment("/var/tmp/file.tar.gz");      // Attachment
				// $mail->AddAttachment("/tmp/image.jpg", "new.jpg");
				$mail->Subject  = setUTF8($value['mailSubject']);
				if(!$mail->Send()):
					$status = true;
	//			    echo "Die Nachricht konnte nicht versandt werden <p>";
	//			    echo "Mailer Error: " . $mail->ErrorInfo;
	//			    exit;
				endif;
			else:
				$message[0] = trim($value['mailTo'][0][1]). " <" . trim($value['mailTo'][0][0]).">";
				$message[1] = $value['mailSubject'];
				$message[2] = $value['mailTXT'];
				$message[3] = "";
				if($value['mailReturnPath']!=""):
					$message[3].= "Return-Path: <" . trim($value['mailReturnPath']) .">\n";
				endif;
				$message[3].= "X-Mailer: WSP Mailer\n";
				$message[3].= "MIME-Version: 1.0\n";
				$message[3].= "From: " . trim($value['mailFrom'][1]) . " <" . trim($value['mailFrom'][0]) .">\n";
				$message[3].= "Reply-To: " . trim($value['mailReply'][0]) . " <" . trim($value['mailReply'][0]) .">\n";
				$message[3].= "Content-Transfer-Encoding: quoted-printable\n";
				$message[3].= "Content-Type: text/plain; charset=ISO-8859-1\n\n";
				if(mail ($message[0],$message[1],$message[2],$message[3])):
					$status = true;
				endif;
			endif;
		endif;		
		return $status;
	}
endif;

if (!(function_exists('createfilename'))):
	function createfilename($newmenuitem = "index", $subfromitem = 0) {
		$newmenuitem = strtolower(removeSpecialChar($newmenuitem)); // convert filename
		$usedname_sql = "SELECT * FROM `menu` WHERE `filename`= '".escapeSQL($newmenuitem)."' AND `connected` = ".intval($subfromitem);
		$usedname_res = doSQL($usedname_sql);
		if ($usedname_res['num']>0):
            $nameok= false;
            while(!$nameok):
                $newmenuitem.= time();
                $usedname_sql = "SELECT * FROM `menu` WHERE `filename`= '".escapeSQL($newmenuitem)."' AND  `connected` = ".intval($subfromitem);
                $usedname_res = doSQL($usedname_sql);
                if ($usedname_res['num']>0):
                    $nameok = false;
                else:
                    $nameok = true;
                endif;
            endwhile;
		endif;
		return $newmenuitem;
	}
endif;

if (!function_exists('returnIntLang')):
	function returnIntLang($internationalize, $textoutput = true) {
		if (!(isset($_SESSION['wspvars']['locallang'])) || $_SESSION['wspvars']['locallang'] == ''):
			$_SESSION['wspvars']['locallang'] = 'de';
		endif;
		if (is_array($GLOBALS['lang'][($_SESSION['wspvars']['locallang'])])):
			if (array_key_exists($internationalize, $GLOBALS['lang'][($_SESSION['wspvars']['locallang'])])):
				return setUTF8($GLOBALS['lang'][($_SESSION['wspvars']['locallang'])][$internationalize]);
			else:
				if ($textoutput):
					return "<em style=\"color: red;\">".$internationalize." [".$_SESSION['wspvars']['locallang']."]</em>";
				else:
					return setUTF8($internationalize." [".$_SESSION['wspvars']['locallang']."]");
				endif;
			endif;
		else:
			if ($textoutput):
				return "<em style=\"color: red;\">[".$_SESSION['wspvars']['locallang']."] not installed</em>";
			else:
				return "[".$_SESSION['wspvars']['locallang']."] not installed";
			endif;
		endif;
		}
endif;

if (!(function_exists("checkTree"))):
	function checkTree($basedir, $src, $includefiles = false){
		$aDirFile = array();
		$dh = dir(str_replace("//", "/", str_replace("//", "/", $basedir.$src)));
		while (false !== ($entry = $dh->read())):
			if (($entry != '.') && ($entry != '..')) {
                if (is_dir(str_replace("//", "/", str_replace("//", "/", $basedir."/".$src."/".$entry)))) {
					$sDirFile = checkTree($basedir, $src."/".$entry, $includefiles);
                    if (count($sDirFile)>0) {
                        $aDirFile = array_merge($aDirFile, $sDirFile);
                    } else {
                        $aDirFile[] = $src."/".$entry."/";
                    }
				}
                else if (is_file($basedir."/".$src."/".$entry) && $includefiles){
					$aDirFile[] = $src."/".$entry;
				}
                else if (is_file($basedir."/".$src."/".$entry)) {
					if (trim($src)!='') { $aDirFile[] = $src."/"; }
				}
			}
		endwhile;
		$aTmp = array_filter(array_unique($aDirFile));
		$aRet = array();
		foreach ($aTmp AS $ak => $av): if (trim($av)!=""): $aRet[] = $av; endif; endforeach;
		return $aRet;
		}
endif;

// interpreter funcs

// returns path from Interpreter to given mid
if (!(function_exists('returnInterpreterPath'))):
	function returnInterpreterPath($mid, $baselang = 'de') {
		// just check, if mid is set in database
		$mid_sql = "SELECT `offlink`, `externtarget`, `filename` FROM `menu` WHERE `mid` = ".intval($mid);
		$mid_res = doSQL($mid_sql);
		$offlink = '';
		if ($mid_res['num']>0):
			if (!empty($mid_res['set'][0]['offlink'])):
				$offlink = trim($mid_res['set'][0]['offlink']);
			endif;
		endif;
		if ($offlink!='') {
			$givebackpath = $offlink;
        } else if (isset($_SESSION['preview']) && intval($_SESSION['preview'])==1) {
			$givebackpath = '?previewid='.intval($mid).'&previewlang='.trim($_SESSION['previewlang']);
		} else {
			$pd_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'parsedirectories'";
			$pd_res = doResultSQL($pd_sql);
			if ($pd_res!==false): $parsedir = intval($pd_res); else: $parsedir = 1; endif;
			if ($mid_res['num']>0) {
				if ($parsedir==1) {
					$givebackpath = returnPath($mid, 1, '', $baselang);
                } else {
					$givebackpath = returnPath($mid, 2, '', $baselang);
                }
            } else {
				$givebackpath = "/";
            }
		}
		return $givebackpath;
	}	// returnInterpreterPath()
endif;

// returns path from Interpreter to given mid
if (!(function_exists('returnLinkedText'))):
	function returnLinkedText($text) {
		//	linktypen:
		//	wsp<6
		//	intern:#[0-9]*; => link auf mid	//	option sprache	//	hreflang="xy" « sprachvariable xy
		//	intern:#[0-9]*;[a-z][a-z] => link auf mid mit sprache
		//	wsp>=6
		//	[%PAGE123%] => link to mid 123
		//	[%DOC:/download/doc1.pdf%] => link auf /media/download/doc1.pdf
		//
		// 	email replacement [_a-zA-Z0-9-\.]*@[_a-z0-9-]*(.[a-z]{2,6})
		
		//	wsp<6 find intern links with language definition
		preg_match_all('/intern:#[0-9]*;[a-z][a-z]/', $text, $pattern);
		if (is_array($pattern[0])):
			foreach ($pattern[0] as $key => $value):
				$linkID = substr($value, strpos($value, '#')+1);
				$linkIDarray = explode(";", $linkID);
				$langTarget = "";
				if ($linkIDarray[1]!="" && $linkIDarray[1]!="de"):
					$langTarget = $linkIDarray[1];
				endif;
				$link = returnInterpreterPath(intval($linkIDarray[0]), $langTarget);
				$text = str_replace("intern:#".$linkIDarray[0], $link, $text);
				$text = str_replace($link.";", $link, $text);
			endforeach;
		endif;
		// find intern links without language definition
		preg_match_all('/intern:#[0-9]*/', $text, $pattern);
		if (is_array($pattern[0])):
			foreach ($pattern[0] as $key => $value):
				$linkID = substr($value, strpos($value, '#')+1);
//				$sql = "SELECT `filename` FROM `menu` WHERE `mid` = ".intval($linkID);
//				$res = doSQL($sql);
				$link = returnInterpreterPath(intval($linkID));
				$link = str_replace(";", "", $link);
				$text = str_replace("intern:#".$linkID, $link, $text);
				$text = str_replace($link.";", $link, $text);
			endforeach;
		endif;
		// email replacement with ascii - only php5 upwards
		if (phpversion()>5):
			$findmail = "/[_a-zA-Z0-9-\.]*@[_a-z0-9-]*(.[a-z]{2,8})/";
			preg_match_all($findmail , $text, $output);
			if (is_array($output[0])):
				$matches = array_unique($output[0]);
				foreach ($matches AS $emailvalue):
					$replacevalue = '';
					foreach (str_split("mailto:".$emailvalue) as $obj):
						$replacevalue .= '&#' . ord($obj) . ';';
					endforeach;
					$text = str_replace("mailto:".$emailvalue, $replacevalue, $text);
					$replacevalue = '';
					foreach (str_split($emailvalue) as $obj):
						if (isset($_SESSION['wspvars']['publisherdata']['maskmail']) && trim($_SESSION['wspvars']['publisherdata']['maskmail'])!=''):
							if ($obj=='@'):
								foreach (str_split($_SESSION['wspvars']['publisherdata']['maskmail']) as $mask):
									$replacevalue .= '&#' . ord($mask) . ';';
								endforeach;
							else:
								$replacevalue .= '&#' . ord($obj) . ';';
							endif;
						else:
							$replacevalue .= '&#' . ord($obj) . ';';
						endif;
					endforeach;
					$text = str_replace($emailvalue, $replacevalue, $text);
				endforeach;
			endif;
		endif;
		// wsp6:
		// replace links to MID
		$pattern = array();
		preg_match_all('/\[\%PAGE:[0-9]*\%\]/', $text, $pattern);
		if (is_array($pattern[0])):
			foreach ($pattern[0] as $key => $value):
				// get linked file
				$linkID = intval(str_replace("%]", "", str_replace("[%PAGE:", "", $value)));
				// get language 
				$langTarget = "";
				$link = returnInterpreterPath($linkID, $langTarget);
				$text = str_replace("[%PAGE:".$linkID."%]", $link, $text);
			endforeach;
		endif;
        // replace links to FILENAMES
		$pattern = array();
		preg_match_all('/\[\%LINK:[-_\w]*\%\]/', $text, $pattern);
		if (is_array($pattern[0])):
            foreach ($pattern[0] as $key => $value):
                // get linked file
				$fileName = strtolower(trim(str_replace("%]", "", str_replace("[%LINK:", "", $value))));
                $linkID_sql = "SELECT `mid` FROM `menu` WHERE `filename` = '".escapeSQL($fileName)."'";
                $linkID_res = doResultSQL($linkID_sql);
                if ($linkID_res===false) {
                    $linkID_sql = "SELECT `mid` FROM `menu` WHERE `linktoshortcut` = '".escapeSQL($fileName)."'";
                    $linkID_res = doResultSQL($linkID_sql);
                } 
                $langTarget = "";
                $link = returnInterpreterPath(intval($linkID_res), $langTarget);
                $text = str_replace($value, $link, $text);
			endforeach;
		endif;
        // replace document links
		$pattern = array();
		preg_match_all('/\[(%DOC:)(\S*)%\]/', $text, $pattern);
		if (is_array($pattern[0])):
			foreach ($pattern[0] as $key => $value):
				$doclink = str_replace('[%DOC:', '/media/', $value);
				$doclink = str_replace('%]', '', $doclink);
				$doclink = str_replace('//', '/', $doclink);
				$text = str_replace($value, $doclink, $text);
			endforeach;
		endif;
        // replace links to SHORTCUTS
        $pattern = array();
		preg_match_all('/\[\%[^:][-_\w]*\%\]/', $text, $pattern);
		if (is_array($pattern[0])):
			foreach ($pattern[0] as $key => $value):
				// get linked file
				$fileName = strtolower(trim(str_replace("%]", "", str_replace("[%", "", $value))));
                $linkID_sql = "SELECT `mid` FROM `menu` WHERE `linktoshortcut` = '".escapeSQL($fileName)."'";
                $linkID_res = doResultSQL($linkID_sql);
                $langTarget = "";
                $link = returnInterpreterPath(intval($linkID_res), $langTarget);
                $text = str_replace($value, $link, $text);
			endforeach;
		endif;
		return $text;
	}	// returnLinkedText()
endif;

// compare versions
if (!(function_exists('compVersion'))):
	function compVersion($old, $new) {
		if (trim($old)!=trim($new)):
			if (trim($new)=='NEW' && trim($old)==''): $new = 1; endif;
			$old = explode('.', trim($old));
			$new = explode('.', trim($new));
			$newer = -1;
			$vstep = 0;
			if (count($old)<=count($new)): 
				$vstep=count($old);
			else:
				$vstep=count($new);
			endif;
			for ($s=0; $s<=$vstep; $s++) {
				if (!(isset($old[$s]))) { 
                    $old[$s] = 0;
                }
				if (!(isset($new[$s]))) { 
                    $new[$s] = 0;
                }
				if (intval($old[$s])<intval($new[$s])) {
					$newer = 1;
					break;
				} else if (intval($old[$s])>intval($new[$s])) {
					break;
				}
			}
			return $newer;
		else:
			return 0;
		endif;
	}	//  compVersion()
endif;

if (!(function_exists('returnUserData'))):
	function returnUserData($values, $uid) {
		$usertypes = array(0 => returnIntLang('usertypes locked', false), 1 => returnIntLang('usertypes admin', false), 2 => returnIntLang('usertypes user', false));
		// values options usertype, user, realname, realmail, shortcut
		if (intval($uid)>0):
			$u_sql = "SELECT `usertype`, `user`, `realname`, `realmail` FROM `restrictions` WHERE `rid` = ".intval($uid);
			$u_res = doSQL($u_sql);
			if ($u_res['num']>0):
				// replace with vars to prevent later double replacing 
				$values = str_replace('shortcut', '[%shortcut%]', $values);
				$values = str_replace('realname', '[%realname%]', $values);
				$values = str_replace('realmail', '[%realmail%]', $values);
				$values = str_replace('usertype', '[%usertype%]', $values);
				$values = str_replace('user', '[%user%]', $values);
				// replace with values
				$values = str_replace('[%user%]', trim($u_res['set'][0]['user']), $values);
				$values = str_replace('[%usertype%]', $usertypes[intval($u_res['set'][0]['usertype'])], $values);
				$values = str_replace('[%realmail%]', '<a href="mailto:'.trim($u_res['set'][0]['realmail']).'">'.trim($u_res['set'][0]['realmail']).'</a>', $values);
				$values = str_replace('[%realname%]', trim($u_res['set'][0]['realname']), $values);
				$shortcut = explode(" ", trim($u_res['set'][0]['realname']));
				if (is_array($shortcut) && count($shortcut)>1):
					$sc = '';
					foreach($shortcut AS $sk => $sv):
						$sc .= substr($sv,0,1);	
					endforeach;
					$shortcut = strtoupper(substr(trim($sc),0,2));
				else:
					$shortcut = strtoupper(substr(trim($u_res['set'][0]['realname']),0,2));
				endif;
				$values = str_replace('[%shortcut%]', $shortcut, $values);
				return $values;
			endif;
		endif;
		}	// returnUserData()
endif;

// 2016-11-18

if (!(function_exists('overlayWYSIWYG'))):
function overlayWYSIWYG($fieldname = array(), $wysiwygcontent = '') {
	$pagecode = '';
	$pagecode.= "<div id='sOWC".implode("_", $fieldname)."' style='height: 1em; width: 95%; border: 1px solid black; padding: 3px; overflow: hidden;'>".strip_tags($wysiwygcontent)."</div>";
	$pagecode.= "<div id='fOWC".implode("_", $fieldname)."' style='display: none;'>";
	$pagecode.= "<textarea name='field[".implode("][", $fieldname)."]' id='tOWC".implode("_", $fieldname)."' style='width: 95%; height: 300px;'>".$wysiwygcontent."</textarea></div>\n";
	$pagecode.= "<script language='javascript' type='text/javascript'>\n";
	$pagecode.= "<!--\n";
	$pagecode.= "\n";
	$pagecode.= "\$('#sOWC".implode('_', $fieldname)."').click(function() {\n";
	$pagecode.= "\$.fancybox({\n";
	$pagecode.= "'type': 'inline',\n";
	$pagecode.= "'href': '#fOWC".implode("_", $fieldname)."',\n";
	$pagecode.= "'beforeShow': function() {\n";
	$pagecode.= "tinymce.execCommand('mceAddEditor', false, 'tOWC";
	$pagecode.= implode('_', $fieldname);
	$pagecode.= "');\n"; //'
	$pagecode.= "},\n";
	$pagecode.= "'afterClose': function() {\n";
	$pagecode.= "tinymce.execCommand('mceRemoveEditor', false, 'tOWC".implode('_', $fieldname)."');\n";
	$pagecode.= "cleanText = \$('#tOWC".implode('_', $fieldname)."').text().replace(/<\/?[^>]+(>|$)/g, '');\n";
	$pagecode.= "\$('#sOWC".implode('_', $fieldname)."').text(cleanText);\n";
	$pagecode.= "}\n";
	$pagecode.= "});\n";
	$pagecode.= "});\n";
	$pagecode.= "\n";
	$pagecode.= "//-->\n";
	$pagecode.= "</script>\n";

    echo $pagecode;
	}
endif;

if (!(function_exists('createModLink'))):
function createModLink($modguid, $text) {
    $return = returnIntLang('modlink not found');
    if (trim($modguid)!='') {
        $mod_sql = "SELECT `id`, `title` FROM `wspmenu` WHERE `guid` = '".escapeSQL(trim($modguid))."'";
        $mod_res = doSQL($mod_sql);
        if ($mod_res['num']>0) {
            $return = "<a href='modgoto.php?modid=".$mod_res['set'][0]['id']."'>".((trim($text)!='')?trim($text):trim($mod_res['set'][0]['title']))."</a>";
        } 
        else { if (trim($text)!='') { $return = trim($text); }}
    }
    else {
        if (trim($text)!='') {
            $return = trim($text);
        } 
    }
    echo $return;
}
endif;

// module funcs

if (!(function_exists('openModDB'))) {
    function openModDB($modHost, $modUser, $modPass, $modDB) {
        if (isset($_SESSION['wspvars']['db'])) {
            if (mysqli_ping($_SESSION['wspvars']['db'])) {
                // close existing db-connection
                mysqli_close($_SESSION['wspvars']['db']);
            }
        }
        // create a new connection to given param
        $_SESSION['wspvars']['db'] = new mysqli($modHost, $modUser, $modPass, $modDB);
    }
}

if (!(function_exists('closeModDB'))) {
    function closeModDB() {
        if (isset($_SESSION['wspvars']['db'])) {
            if (mysqli_ping($_SESSION['wspvars']['db'])) {
                // close existing db-connection
                mysqli_close($_SESSION['wspvars']['db']);
            }
        }
        // create a new connection to given param
        $_SESSION['wspvars']['db'] = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
}

// EOF ?>