<?php
/**
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
 * 2025-02-14
 * bugfixes
 * 
 * 2025-02-19
 * removed "group replacement" in exchange to well formed sql statements 
 * 
 * 2025-03-05
 * WSP7
 */

// mysqli based functions 
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

// do sql statement
// returns array with resultset and complete information
if (!(function_exists('doSQL'))) {
function doSQL( $statement = '' ) {
	$set = array('res'=>false,'aff'=>0,'num'=>0,'set'=>array(),'sql'=>$statement,'inf'=>'','err'=>'');
	$groupby = false;
    if ($_SESSION['wspvars']['db']) {
        if (isset($_SESSION['wspvars']['sqlmode']) && is_array($_SESSION['wspvars']['sqlmode']) && in_array('ONLY_FULL_GROUP_BY', $_SESSION['wspvars']['sqlmode'])) {
            // first we try to run the statement without replacement
            $res = $_SESSION['wspvars']['db']->query($statement);
            // false res means there is the option the request could not be handled while using GROUP BY in statement
            if ($res===false) {
                // grep the group by part WITH following ORDER BY 
                $grp = '/group[ ]{1,}by[ ]{1,}\({0,1}[`(),a-zA-Z_\-0-9\. ]*\){0,1}(?= order)/i';
                $grpfnd = preg_match($grp, $statement, $grpmatches, PREG_OFFSET_CAPTURE, 0);
                if (intval($grpfnd)==0) {
                    // grep the group by part WITHOUT following ORDER BY 
                    $grp = '/group[ ]{1,}by[ ]{1,}\({0,1}[`(),\.a-zA-Z_\-0-9 ]*\){0,1}/i';
                    $grpfnd = preg_match($grp, $statement, $grpmatches, PREG_OFFSET_CAPTURE, 0);
                }
                if ($grpfnd>0) {
                    if (defined('WSP_DEV') && WSP_DEV) {
                        addWSPMsg('errormsg', 'Statement "'.$statement.'" automatically converted with GROUPBY');
                    }
                    // some group statement was found so we replace the group by part with empty string
                    // and do grouping later with result ;)
                    $statement = str_replace($grpmatches[0][0], '', $statement);
                    $groupby = getGROUPBY($grpmatches[0][0]);
                }
            }
        }
        // second try of statement ONLY if the first try in ONLY_FULL_GROUP_BY did not work
        if (!isset($res) || $res===false) {
            $res = $_SESSION['wspvars']['db']->query($statement);
        }
		if ($res===true) {
			$set['res'] = true;
			$set['aff'] = $_SESSION['wspvars']['db']->affected_rows;
		} else if ($res) {
			$set['res'] = true;
			$set['aff'] = $_SESSION['wspvars']['db']->affected_rows;
			$set['num'] = $res->num_rows;
		} else {
			$set['err'] = $_SESSION['wspvars']['db']->error_list;
        }
		if ($set['res']) {
			$set['inf'] = $_SESSION['wspvars']['db']->insert_id;
        }
		if ($set['num'] && $set['num']>0) {
			for($n=0; $n<$set['num']; $n++) {
				$set['set'][$n] = mysqli_fetch_assoc($res);
            }
            // handle the late groupby
            if ($groupby!==false) {
                $tmpgrp = array();
                $grpval = array();
                foreach ($groupby AS $gbk => $gbv) {
                    if (array_key_exists($gbv, $set['set'][array_key_first($set['set'])])) {
                        $grpval[] = $gbv;
                    }
                }
                if (count($grpval)>0) {
                    rsort($set['set']);
                    foreach ($set['set'] AS $sk => $sv) {
                        $key = array();
                        foreach($grpval AS $gvk => $gvv) {
                            $key[] = $sv[$gvv];
                        }
                        $tmpgrp[implode(':', $key)] = $sk;
                    }
                    rsort($set['set']);
                    foreach ($set['set'] AS $sk => $sv) {
                        if (!(in_array($sk, $tmpgrp))) {
                            unset($set['set'][$sk]);
                        }
                    }
                    $set['set'] = array_values($set['set']);
                    $set['num'] = (intval($set['num'])>0) ? count($set['set']) : $set['num'];
                    $set['aff'] = (intval($set['aff'])>0) ? count($set['set']) : $set['aff'];
                    unset($tmpgrp);
                    unset($grpval);
                }
            }
			mysqli_free_result($res);
		}
	}
	return $set;
	}
}

// helping function to use group by statements
if (!(function_exists('getGROUPBY'))) {
    function getGROUPBY( $groupbystring ) {
        $grpfnd = preg_match_all('/`[a-zA-Z0-9_]*`/i', $groupbystring, $grpfld, PREG_OFFSET_CAPTURE, 0);
        if ($grpfnd>0) {
            $grpflds = array();
            foreach ($grpfld[0] AS $gfk => $gfv) {
                $grpfldtmp = trim(str_replace('`', '', trim($gfv[0])));
                if (!(strstr($grpfldtmp, ' '))) {
                    $grpflds[] = $grpfldtmp;
                }
            }
            return $grpflds;
        }
    }
}

// returns ONE result with a given statement that SHOULD return ONE result 
if (!(function_exists('doResultSQL'))):
function doResultSQL($statement) {
	$tmp = doSQL($statement);
	if ($tmp['res'] && is_array($tmp['set']) && count($tmp['set'])==1 && count($tmp['set'][0])==1):
		$tmpkeys = array_keys($tmp['set'][0]);
		$tmpkey = $tmpkeys[0];
		return(setUTF8($tmp['set'][0][$tmpkey]));	
	else:
		return false;
	endif;
	}
endif;

if (!(function_exists('getNumSQL'))) {
    function getNumSQL($statement) { $tmp = doSQL($statement); return ($tmp['num']>0) ? intval($tmp['num']) : 0; }
}
// does statement and returns affected rows 
if (!(function_exists('getAffSQL'))) {
    function getAffSQL($statement) { $tmp = doSQL($statement); return ($tmp['aff']>0) ? intval($tmp['aff']) : 0; }
}

if (!(function_exists('getInsSQL'))) {
    function getInsSQL($statement) { $tmp = doSQL($statement); return ($tmp['inf']>0) ? intval($tmp['inf']) : 0; }
}

// returns an result ARRAY with a given statement that SHOULD return only ONE row 
if (!(function_exists('getResultSQL'))):
function getResultSQL($statement) {
	$tmp = doSQL($statement);
    if ($tmp['res'] && is_array($tmp['set']) && count($tmp['set'])>0 && count($tmp['set'][0])==1):
		$keyname = array_keys($tmp['set'][0]);
        foreach ($tmp['set'] AS $tsk => $tsv):
            $tmpval[$tsk] = $tsv[$keyname[0]];
        endforeach;
		return($tmpval);	
	else:
		return false;
	endif;
	}
endif;

// getSetSQL returns an array of key-value-pairs of a given sql-data-stream if key AND value exist in stream
if (!(function_exists('getSetSQL'))):
function getSetSQL($dataarray,$varname,$varvalue) {
	$returnarray = array();
	if (isset($dataarray['set']) && count($dataarray['set'])>0):
		foreach ($dataarray['set'] AS $dk => $dv):
			if (isset($dv[$varname]) && isset($dv[$varvalue])):
				$returnarray[$dv[$varname]] = $dv[$varvalue];
			endif;
		endforeach;
		return ($returnarray);
	else:
		return false;
	endif;
	}
endif;

// returnSingleResultSQL takes a value to search within a resultset and returns the set, 
// where the value is found as a value in set's key-value pair or returns the value of a
// given returnfield
if (!(function_exists('returnSingleResultSQL'))):
function returnSingleResultSQL($searchvalue, $searchfield, $resultset, $returnfield = '') {
	$resultrow = array_search($searchvalue, array_column($resultset, $searchfield));
	if (in_array($searchvalue, $resultset[$resultrow])):
		if (trim($returnfield)!=''):
			if (isset($resultset[$resultrow][$returnfield])):
				return($resultset[$resultrow][$returnfield]);
			else:
				return false;
			endif;
		else:
			return($resultset[$resultrow]);
		endif;
	else:
		return false;
	endif;
	}
endif;

// readable mysqli server info
if (!(function_exists('mysqli_wsp_server_version'))) {
    function mysqli_wsp_server_version() {
        if (isset($_SESSION['wspvars']['db']) && $_SESSION['wspvars']['db']) {
            $data = mysqli_get_server_version($_SESSION['wspvars']['db']);
            $main = floor($data/10000);
            $minor = intval($data-(floor($data/10000)*10000))/100;
            return $main.".".$minor;
        }
        else {
            return "-";
        }
    }
}
// readable mysqli client info
if (!(function_exists('mysqli_wsp_client_version'))) {
    function mysqli_wsp_client_version() {
        if (isset($_SESSION['wspvars']['db']) && $_SESSION['wspvars']['db']) {
            $data = mysqli_get_client_version();
            $main = floor($data/10000);
            $minor = intval($data-(floor($data/10000)*10000))/100;
            return $main.".".$minor;
        }
        else {
            return "-";
        }
    }
}

// getWSPProperties returns an array with wsp properties for multiple values or a string for ONE requested value
// input none or array with varnames that values should be returned
if (!(function_exists('getWSPProperties'))) {
    function getWSPProperties($propselected = '') {
        $wspproperties = array();
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

// test CURL functionality
if (!(function_exists('isCurl'))) {
    function isCurl(){
        return function_exists('curl_version');
    }
}

// get <7.3 compatibility
if (!(function_exists('array_key_first'))) {
    function array_key_first($arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}

// check for given params to vars, replace vars with param, etc
if (!(function_exists('checkParamVar'))):
function checkParamVar($var, $standard = false, $checkcookie = false, $checksession = true, $pref = '') {
    $param = false;
    if (isset($standard) && $standard=='COOKIE' && isset($_COOKIE[$var])):
        $param = $_COOKIE[$var];
    elseif (isset($standard) && $standard=='SESSION' && isset($_SESSION[$var])):
        $param = $_SESSION[$var];
    elseif (isset($standard) && $standard=='WSPSESSION' && isset($_SESSION['wspvars'][$var])):
        $param = $_SESSION['wspvars'][$var];
    elseif (isset($standard) && $standard=='POST' && isset($_POST[$var])):
        $param = $_POST[$var];
    elseif (isset($standard) && $standard=='GET' && isset($_GET[$var])):
        $param = $_GET[$var];
    endif;
    if ($param===false):
        if ($checkcookie && isset($_COOKIE[$var])):
            $param = $_COOKIE[$var];
        elseif ($checksession && isset($_SESSION[$var])):
            $param = $_SESSION[$var];
        elseif ($checksession && isset($_SESSION['wspvars'][$var])):
            $param = $_SESSION['wspvars'][$var];
        elseif (isset($_POST[$var])):
            $param = $_POST[$var];
        elseif (isset($_GET[$var])):
            $param = $_GET[$var];
        elseif (isset($_SESSION['wspvars']['hiddengetvars'][$var])):
            $param = $_SESSION['wspvars']['hiddengetvars'][$var];
        elseif (isset($GLOBALS[$var])):
            $param = $GLOBALS[$var];
        endif;
    endif;
    if (($param===false || (!(is_array($param)) && trim($param)=='')) && (is_array($pref) || trim($pref)!="")):
        $param = $pref;
    endif;
    return $param;
    }	// checkParamVar()
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

if (!function_exists('generate_password')) {
	function generate_password(){
		$pool = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$pool.= "abcdefghijklmnopqrstuvwxyz";
		$pool.= "1234567890";
        $pool.= "-.+&()!";
		$password = '';
		for ($i = 0; $i < 10; $i++) {
			$password .= $pool[rand(0, strlen($pool)-1)];
		}
		return $password;
	}
}

if (!(function_exists('cryptRootPhrase'))) {
    function cryptRootPhrase( $string, $action = 'e', $secret = '') {
        // you may change these values to your own
        if (defined('ROOTPHRASE')) {
            $secret_key = ROOTPHRASE.'key';
            $secret_iv = ROOTPHRASE.'iv';
        }
        else if (defined('DB_PASS')) {
            $secret_key = DB_PASS.'key';
            $secret_iv = DB_PASS.'iv';
        }
        else if (trim($secret)!='') {
            $secret_key = trim($secret).'key';
            $secret_iv = trim($secret).'iv';
        } else {
            $secret = md5(date('Y-m-d H:i:S'));
            $secret_key = trim($secret).'key';
            $secret_iv = trim($secret).'iv';
        }

        $output = false;
        $encrypt_method = "AES-256-CBC";
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

        if( $action == 'e' ) {
            $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
        }
        else if( $action == 'd' ){
            $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        }

        return $output;
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
        if (trim($filereplacer)=="") { $filereplacer = "-"; }
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
        $filename = str_replace('(', $filereplacer, $filename);
        $filename = str_replace(')', $filereplacer, $filename);
        $allowed_in_file = "[^a-zA-Z0-9_]";
        $filename = preg_replace("/$allowed_in_file/", $filereplacer, $filename);
        $filename = str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, str_replace($filereplacer.$filereplacer, $filereplacer, $filename))))));
        if($lastdotpos>0) {
            return strtolower(urltext($filename).".".$fileextension);
        } else {
            return strtolower(urltext($filename));
        }
	}	// removeSpecialChar()
}

// return any string url-ready
if (!(function_exists("urltext"))) { 
    function urltext($txt) { $txt = strtolower(trim(utf8_decode($txt))); $replaces = array( chr(192) => "a", chr(193) => "a", chr(194) => "a", chr(195) => "ae", chr(197) => "a", chr(196) => "ae", chr(228) => "ae", chr(198) => "ae", chr(214) => "oe", chr(220) => "ue", chr(223) => "ss", chr(224) => "a", chr(225) => "a", chr(226) => "a", chr(232) => "e", chr(233) => "e", chr(234) => "e", chr(236) => "i", chr(237) => "i", chr(238) => "i", chr(242) => "o", chr(243) => "o", chr(244) => "o", chr(246) => "oe", chr(249) => "u", chr(250) => "u", chr(251) => "u", chr(252) => "ue", "\"" => "", "'" => "", "," => "", " " => "-", "/" => "-", "." => "-", "_" => "-", "?" => "", "!" => "", "*" => "", "#" => ""); foreach ($replaces AS $key => $value): $txt = str_replace($key, $value, trim($txt)); endforeach; $txt = preg_replace('/[^a-z0-9\-_]/', "", $txt); $t = 0; while (strpos($txt, '--') || $t==20): $txt = str_replace("--", "-", $txt); $t++; endwhile; return $txt; }
}

// returns a clean path without double slashes etc
if (!(function_exists('cleanPath'))) {
    function cleanPath($pathstring) {
        while (substr($pathstring, 0, 1)=='.') { $pathstring = substr($pathstring, 1); }
        // replaces all '..' with '.'
        while (preg_match("/\.\./", $pathstring)) { $pathstring = preg_replace("/\.\./", ".", $pathstring); }
        // replaces all './' with '/'
        while (preg_match("/\.\//", $pathstring)) { $pathstring = preg_replace("/\.\//", "/", $pathstring); }
        // replaces all '//' with '/'
        while (preg_match("/\/\//", $pathstring)) { $pathstring = preg_replace("/\/\//", "/", $pathstring); }
        return trim($pathstring);
    }
}

// replaces only first existment of needle in string if it was found at the beginning of the string
if (!(function_exists("strl_replace"))) { 
    function strl_replace($needle, $replacement, $string) {
        $strl = strpos($string, $needle);
        if ($strl!==false && intval($strl)==0) {
            $needle = '/('.preg_quote($needle, '/').')/';
            return preg_replace($needle, $replacement, $string, 1);
        } else {
            return $string;
        }
    }
}

if (!(function_exists("strr_replace"))) { 
    function strr_replace($needle, $replacement, $string) {
        $strr = strrpos($string, $needle);
        $strl = strlen($string);
        $strn = strlen($needle);
        if ($strr!==false && (intval($strr)+intval($strn))==intval($strl)) {
            return substr($string, 0, $strr).$replacement;
        } else {
            return $string;
        }
    }
}

if (!(function_exists("compareVersion"))): 
function compareVersion($given,$check) {
    die ('deprecated compareVersion');
    $v = 0; // v>0 » newer version avaiable, v<0 » newer version given
    $givenv = explode(".", $given);
    $checkv = explode(".", $check);
    if (count($givenv)>count($checkv)):
        foreach ($givenv AS $vk => $vv):
            if (isset($checkv[$vk])):
                if (intval($checkv[$vk])>intval($givenv[$vk])):
                    $v = 1;
                    break;
                elseif (intval($checkv[$vk])<intval($givenv[$vk])):
                    $v = -1;
                    break;
                endif;
            else:
                $v = 1;
                break;
            endif;
        endforeach;
    else:
        foreach ($checkv AS $vk => $vv):
            if (isset($givenv[$vk])):
                if (intval($checkv[$vk])>intval($givenv[$vk])):
                    $v = 1;
                    break;
                elseif (intval($checkv[$vk])<intval($givenv[$vk])):
                    $v = -1;
                    break;
                endif;
            else:
                $v = 1;
                break;
            endif;
        endforeach;
    endif;
    if ($v==0 && (trim($given)!=trim($check))): $v = 1; endif;
    return $v;
    }
endif;

// mid-related functions
// 
// returns an array with all 'mid', that have a relation to given mid DOWNWARDS - WITHOUT any structure information 
if (!(function_exists('returnIDRoot'))) {
	function returnIDRoot($mid, $midlist = array(), $all = false, $order = true) {
		$connected_sql = "SELECT `mid` FROM `menu` WHERE `trash` = ".intval($all)." AND `connected` = ".intval($mid).(($order===true)?" ORDER BY `isindex` DESC, `position` ASC":"");
		$connected_res = doSQL($connected_sql);
		if ($connected_res['num']>0) {
            foreach ($connected_res['set'] AS $crsk => $crsv) {
                $midlist[] = intval($crsv['mid']);
                $midlist = array_merge($midlist, returnIDRoot(intval($crsv['mid']), $midlist, $all, $order));
            }
		}
		return array_values(array_unique($midlist));
    }
}

// returns an array with all 'mid', that have a structured relation to given mid UPWARDS, eg. a list if mid up to root level - WITHOUT any structure information 
if (!(function_exists('returnIDTree'))) {
	function returnIDTree($mid, $midpath = array(), $all = false, $order = true) {
		$reverse_sql = "SELECT `connected` FROM `menu` WHERE `trash` = ".intval($all)." AND `mid` = ".intval($mid).(($order===true)?" ORDER BY `isindex` DESC, `position` ASC":"");
		$reverse_res = doSQL($reverse_sql);
		if ($reverse_res['num']>0) {
			$midpath[] = intval(intval($reverse_res['set'][0]['connected']));
			$midpath = returnIDTree(intval($reverse_res['set'][0]['connected']), $midpath, $all, $order);
        }
        $midpath = array_values(array_unique($midpath));
		return $midpath;
	}
}

// structure and content related functions

// setStructure
// sets new ordered structure by given array
// 1. called from ajax.setstructure.php
if (!(function_exists('setStructure'))) {
    function setStructure($sArray, $sParent = 0, $sLevel = 1) {
        if (is_array($sArray) && count($sArray)>0) {
            foreach ($sArray AS $sAk => $sAv) {
                $sql = "UPDATE `menu` SET `connected` = ".intval($sParent).", `level` = ".intval($sLevel).", `position` = ".intval($sAk)." WHERE `mid` = ".intval($sAv['id']);
                $res = doSQL($sql);
                if ($res['aff']==1) {
                    // has to be developed: get all affected pages and set contentchange
                    // use: setContentChangeStat(intval($sAv['id']), 'structure')
                    // status 2019-03-14: update ALL pages contentchange
                    doSQL("UPDATE `menu` SET `contentchange` = 1 WHERE `trash` = 0");
                }
                if (isset($sAv['children']) && is_array($sAv['children'])) {
                    setStructure ($sAv['children'], $sAv['id'], $sLevel+1);
                }
            }
        }
    }
}

// returnStructureItem
// returns list/option of structure
//    $datatable » menu-table to be selected as string
//    $mid » base mid as integer (for full structure use 0)
//    $showsub » show sub structure as boolean 
//    $maxlevel » max levels to show as integer (for no limitation use 9999)
//    $openpath » array of open path to a selected mid
//    $datatype » list, option 
//    $param » if some special cases shall appear
//     
if (!(function_exists('returnStructureItem'))):
function returnStructureItem($datatable = 'menu', $mid = 0, $showsub = false, $maxlevel = 9999, $openpath = array(), $datatype = 'list', $param = false) {
    $item = '';
    $visopt = '';
    if (isset($param) && is_array($param) && isset($param['visible']) && intval($param['visible'])>0) {
        if (intval($param['visible'])==1) {
            $visopt = ' AND `visibility` != 0 ';
        }
        else if (intval($param['visible'])==2) {
            $visopt = ' AND !(`visibility` = 0 AND (`internlink_id` != 0 OR `forwarding_id` != 0))';
        }
    }
    if ($mid==0):
        $middata_sql = "SELECT * FROM `".$datatable."` WHERE `trash` = 0 AND `connected` = ".intval($mid)." ".$visopt." ORDER BY `position` ASC";
    else:
        $middata_sql = "SELECT * FROM `".$datatable."` WHERE `trash` = 0 AND `mid` = ".intval($mid)." ".$visopt." ORDER BY `position` ASC";
    endif;
    $middata_res = doSQL($middata_sql);
    if ($middata_res['num']>0):
        foreach ($middata_res['set'] AS $mrsk => $mrsv):
            $subdata_res = doSQL("SELECT `mid` FROM `".$datatable."` WHERE `level` <= ".$maxlevel." AND `trash` != 1 AND `connected` = ".$mrsv['mid']." ORDER BY `position` ASC");
            if ($datatype=='list') {
                $item.= '<li class="dd-item custom-item ';
                if(isset($_SESSION['wspvars']['structurefilter']) && trim($_SESSION['wspvars']['structurefilter'])!='') {
                    if (stripos($mrsv['description'], trim($_SESSION['wspvars']['structurefilter']))!==false) {
                        $item.= ' search-item ';
                        // open up to root level but NOT THIS mid ;)
                        $opentree = returnIDTree($mrsv['mid']);
                        foreach ($opentree AS $otv) {
                            $_SESSION['wspvars']['opentree'][] = intval($otv);
                        }
                    }
                }
                $item.= '" id="structure-'.$mrsv['mid'].'" data-id="'.$mrsv['mid'].'" ';
                if ($_SESSION['wspvars']['ssh']==1 && $mrsv['visibility']==0):
                    $item.= ' style="display: none;" ';
                endif;
                $item.= '>';
                $item.= '<div class="dd-handle custom-handle"><i class="fas fa-arrows-alt"></i></div>';
                $item.= '<div class="dd-action custom-action"><div class="dropdown">';
                $item.= '<a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false">';
                // show item by type of menulink 
                if ($mrsv['editable']==9):
                    $item.= '<i class="fas fa-database'.(($mrsv['visibility']==0)?' fa-disabled':'').(($mrsv['isindex']==1)?' text-success':'').'"></i> ';
                elseif (trim($mrsv['filetarget'])!=''):
                    $item.= '<i class="fas fa-hashtag'.(($mrsv['visibility']==0)?' fa-disabled':'').(($mrsv['isindex']==1)?' text-success':'').'"></i> ';
                elseif (trim($mrsv['offlink'])!=''):
                    $item.= '<i class="fas fa-sign-out-alt'.(($mrsv['visibility']==0)?' fa-disabled':'').(($mrsv['isindex']==1)?' text-success':'').'"></i> ';
                elseif (trim($mrsv['docintern'])!=''):
                    $item.= '<i class="fas fa-file'.(($mrsv['visibility']==0)?' fa-disabled':'').(($mrsv['isindex']==1)?' text-success':'').'"></i> ';
                elseif ($mrsv['internlink_id']>0):
                    $item.= '<i class="fas fa-sign-in-alt'.(($mrsv['visibility']==0)?' fa-disabled':'').(($mrsv['isindex']==1)?' text-success':'').'"></i> ';
                else:
                    $item.= '<i class="fas fa-bookmark'.(($mrsv['visibility']==0)?' fa-disabled':'').(($mrsv['isindex']==1)?' text-success':'').'"></i> ';
                endif;
                $item.= '</a>';
                $item.= '<ul class="dropdown-menu dropdown-menu-left">';
                $item.= '<li><a onclick="doEdit(\''.$mrsv['mid'].'\')"><i class="fas fa-pen-square"></i>'.returnIntLang('str edit').'</a></li>';
                $item.= '<li><a onclick="doClone(\''.$mrsv['mid'].'\')"><i class="fas fa-clone"></i>'.returnIntLang('str clone').'</a></li>';
                $item.= '<li><a onclick="doSub(\''.$mrsv['mid'].'\')"><i class="fas fa-plus-square"></i>'.returnIntLang('str addsub').'</a></li>';
                $item.= '<li class="showhide" '.((intval($mrsv['visibility'])!=0)?' style="display: none;" ':'').'><a onclick="doShowHide('.intval($mrsv['mid']).',1)"><i class="fas fa-bookmark"></i>'.returnIntLang('str show').'</a></li>';
                $item.= '<li class="hideshow" '.((intval($mrsv['visibility'])==0)?' style="display: none;" ':'').'><a onclick="doShowHide('.$mrsv['mid'].',0)"><i class="far fa-bookmark"></i>'.returnIntLang('str hide').'</a></li>';
                $item.= '<li><a href="./showpreview.php?previewid='.$mrsv['mid'].'&previewlang=de" target="_blank"><i class="fas fa-eye"></i>'.returnIntLang('str preview').'</a></li>';
                $item.= '<li><a onclick="doDelete(\''.$mrsv['mid'].'\')"><i class="fa fa-trash"></i>'.returnIntLang('str delete').'</a></li>';
                $item.= '</ul>';
                $item.= '</div></div>';
                $item.= '<div class="dd-action custom-marker"><input type="checkbox" id="multiedit_'.$mrsv['mid'].'" name="multiedit['.$mrsv['mid'].']" value="1" /></div>';
                $item.= '<div class="dd-data custom-content">';
                // select data to be shown
                $item.= '<a onclick="doEdit(\''.$mrsv['mid'].'\')" style="cursor: pointer;">';
            }
            else if ($datatype=='option') {
                if (isset($param) && is_array($param) && isset($param['disable']) && is_array($param['disable']) && count($param['disable'])>0) {
                    // remove any disabled mid from list and replace entry
                    if (in_array(intval($mrsv['mid']), $param['disable'])) {
                        $item.= "<option value='-1'";
                        if (in_array($mrsv['mid'], $openpath)) {
                            $item.= " selected='selected'"; 
                        }
                        $item.= " class='level".$mrsv['level']."' disabled='disabled' ";
                        $item.= ">";
                        for ($l=1; $l<$mrsv['level']; $l++) {
                            $item.= "&nbsp;&nbsp;";
                        }
                    } else {
                        $item.= "<option value='".intval($mrsv['mid'])."'";
                        if (in_array($mrsv['mid'], $openpath)) {
                            $item.= " selected='selected'"; 
                        }
                        $item.= " class='level".$mrsv['level']."' ";
                        $item.= ">";
                        for ($l=1; $l<$mrsv['level']; $l++) {
                            $item.= "&nbsp;&nbsp;";
                        }
                    }
                }
                else {
                    $item.= "<option value='".$mrsv['mid']."'";
                    if (in_array($mrsv['mid'], $openpath)) {
                        $item.= " selected='selected'"; 
                    }
                    $item.= " class='level".$mrsv['level']."' ";
                    $item.= ">";
                    for ($l=1; $l<$mrsv['level']; $l++) {
                        $item.= "&nbsp;&nbsp;";
                    }
                }
            }
            
            if ($_SESSION['wspvars']['sdm']==4) {
                // case 4 is called from headerprefs and shows the full path
                $item.= fileNamePath(intval($mrsv['mid']),0,1);
            }
            elseif ($_SESSION['wspvars']['sdm']==3) {
                if ($datatype=='option') {
                    $item.= $mrsv['description'].' - # '.$mrsv['mid'];
                }
                else {
                    $item.= $mrsv['description'].' &nbsp; <i class="fa fa-code"></i> '.fileNamePath($mrsv['mid']).' &nbsp; <i class="fa fa-hashtag"></i> '.$mrsv['mid'];
                }
            }
            elseif ($_SESSION['wspvars']['sdm']==2) {
                if ($datatype=='option') {
                    $item.= '# '.$mrsv['mid'];
                }
                else {
                    $item.= '<i class="fa fa-hashtag"></i> '.$mrsv['mid'];
                }
            }
            elseif ($_SESSION['wspvars']['sdm']==1) {
                if ($datatype=='option') {
                    $item.= '&lt;/&gt; '.fileNamePath(intval($mrsv['mid']));
                }
                else {
                    $item.= '<i class="fa fa-code"></i> '.fileNamePath(intval($mrsv['mid']));
                }
            }
            else {
                $item.= $mrsv['description'];
            }
                    
        
            if ($datatype=='option') {
                $item.= " <sup><i class='fas fa-certificate'></i></sup>";
            }
            else if (trim($mrsv['showtime'])!='' || intval($mrsv['weekday'])>0) {
                $item.= " <sup><i class='fas fa-clock'></i></sup>";
            }
                
            if (defined('WSP_DEV') && WSP_DEV===true) {
                if(isset($_SESSION['wspvars']['structurefilter']) && trim($_SESSION['wspvars']['structurefilter'])!='') {
                    $item.= " : ".trim($_SESSION['wspvars']['structurefilter']);
                    $item.= " : ".var_export(stripos($mrsv['description'], trim($_SESSION['wspvars']['structurefilter'])), true);
                }
            }
    
            if ($datatype=='list'):
                $item.= '</a>';
                $item.= '</div>';
            elseif ($datatype=='option'):
                $item.= "</option>\n";
            endif;

            if ($showsub && $subdata_res['num']>0) {
                if ($datatype=='list'):
                    $item.= '<ol class="dd-list">';
                endif;
                // calling returnStructureItem to get next level
                foreach ($subdata_res['set'] AS $sk => $sv) {
                    if ($param && is_array($param) && isset($param['disable']) && is_array($param['disable']) && in_array(intval($mrsv['mid']), $param['disable'])) {
                        $item.= returnStructureItem($datatable, $sv['mid'], $showsub, $maxlevel, $openpath, $datatype, array('disable' => array($mrsv['mid'], $sv['mid'])));
                    }
                    else {
                        $item.= returnStructureItem($datatable, $sv['mid'], $showsub, $maxlevel, $openpath, $datatype, $param);
                    }
                }
                if ($datatype=='list'):
                    $item.= '</ol>';
                endif;
            }
            else if ($showsub && $subdata_res['num']==0 && $datatype=='list') {
                // create empty list to enable drag & drop
//                $item.= '<ol class="dd-list"></ol>';
            }
            if ($datatype=='list'):
                $item.= "<hr class='clearbreak'>\n";
                $item.= "</li>\n";
            endif;
        endforeach;
    endif;
    return $item;
    }
endif;

//  returnContentStructureItem
//  $datatable » menu-table to be selected as string
//  $mid » base mid as integer (for full structure use 0)
//  $showsub » show sub structure as boolean 
//  $maxlevel » max levels to show as integer (for no limitation use 9999)
//  $openpath » array of open path to a selected mid
//  $posmid » array of mid that can be shown
//  $lvl »
if (!(function_exists('returnContentStructureItem'))):
function returnContentStructureItem($datatable = 'menu', $mid = 0, $showsub = false, $maxlevel = 9999, $openpath = array(), $posmid = array(), $lvl = 0) {
    $item = '';
    $middata_sql = "SELECT * FROM `".$datatable."` WHERE `trash` != 1 AND `mid` = ".intval($mid);
    $middata_res = doSQL($middata_sql);
    if (is_array($posmid) && count($posmid)>0 && !(in_array(intval($mid), $posmid))) {
        $middata_res['num'] = 0;
    }
    if ($middata_res['num']>0) {
        $subdata_res = doSQL("SELECT `mid` FROM `".$datatable."` WHERE `level` <= ".$maxlevel." AND `trash` != 1 AND `connected` = ".intval($middata_res['set'][0]['mid'])." ORDER BY `position` ASC ");
        $item.= '<div class="panel panel-group ';
        if ($lvl==0): $item.= 'panel-toplevel'; else: $item.= 'panel-level-'.$lvl; endif;
        if(isset($_SESSION['wspvars']['contentfilter']) && trim($_SESSION['wspvars']['contentfilter'])!='') {
            if (in_array(intval($middata_res['set'][0]['mid']), $_SESSION['wspvars']['contentfiltermid'])) {
                $item.= ' search-item ';
                // open up to root level but NOT THIS mid ;)
                $opentree = returnIDTree(intval($middata_res['set'][0]['mid']));
                foreach ($opentree AS $otv) {
                    $_SESSION['wspvars']['opentree'][] = intval($otv);
                }
            }
        }
        $item.= '" data-level="'.$lvl.'">';
        $item.= '<div class="panel-heading ';
        if (isset($_SESSION['wspvars']['contentfiltermid']) && in_array(intval($middata_res['set'][0]['mid']), $_SESSION['wspvars']['contentfiltermid'])): $item.= " panel-filterpath "; endif;
        if ($middata_res['set'][0]['editable'] && isset($_SESSION['wspvars']['contentfilterid']) && in_array(intval($middata_res['set'][0]['mid']), $_SESSION['wspvars']['contentfilterid'])): $item.= " panel-filterresult "; endif;
        $item.= '">';
        $item.= '<h3 class="panel-title">';
        if ($subdata_res['num']>0):
            $item.= '<a class="toggle-structure btn-link" mid="'.$middata_res['set'][0]['mid'].'"><i class="fas fa-plus-square"></i></a>&nbsp; ';
        else:
            $item.= '<i class="fas fa-plus-square disabled"></i>&nbsp; ';
        endif;
        if ($middata_res['set'][0]['editable']):
            // 
        else:
            // 
        endif;
        if ($middata_res['set'][0]['editable']==1 || $middata_res['set'][0]['editable']==9) {
            $item.=' <a class="toggle-content" mid="'.$middata_res['set'][0]['mid'].'">';
        }
        if (isset($_SESSION['wspvars']['sdm']) && $_SESSION['wspvars']['sdm']==3) {
            $item.= $middata_res['set'][0]['description'].' &nbsp; <i class="fa fa-code"></i> '.fileNamePath($middata_res['set'][0]['mid']).' &nbsp; <i class="fa fa-hashtag"></i> '.$middata_res['set'][0]['mid'];
        }
        else if (isset($_SESSION['wspvars']['sdm']) && $_SESSION['wspvars']['sdm']==2) {
            $item.= '<i class="fa fa-hashtag"></i> '.$middata_res['set'][0]['mid'];
        }
        else if (isset($_SESSION['wspvars']['sdm']) && $_SESSION['wspvars']['sdm']==1) {
            $item.= '<i class="fa fa-code"></i> '.fileNamePath(intval($middata_res['set'][0]['mid']));
        }
        else {
            $item.= $middata_res['set'][0]['description'];
            $_SESSION['wspvars']['sdm'] = 0;
        }
        if ($middata_res['set'][0]['editable']==1 || $middata_res['set'][0]['editable']==9) {
            $item.= "</a> ";
        }
        $realtemp = getTemplateID(intval($middata_res['set'][0]['mid']));
        $templatevars = getTemplateVars($realtemp);
        if (isset($templatevars['contentareas']) && is_array($templatevars['contentareas']) && count($templatevars['contentareas'])>0) {
            // editable 1 == REALLY editable + editable 9 == setup dynamic contents
            if ($middata_res['set'][0]['editable']==1 || $middata_res['set'][0]['editable']==9) {
                $item.= ' <a class="toggle-content btn-link" mid="'.$middata_res['set'][0]['mid'].'"><span class="label inline-label label-default">';
                $item.= '<span class="longdesc">'.getTemplateName($realtemp).' - </span>';
                $item.= count($templatevars['contentareas']).'<span class="longdesc"> ';
                $item.= (count($templatevars['contentareas'])!=1) ? returnIntLang('str contentareas') : returnIntLang('str contentarea');
                $item.= '</span></span></a> ';
            }
            // just show number of contentareas for dynamic pages
            else if ($middata_res['set'][0]['editable']==7) {
                $item.= ' <span class="label inline-label label-default">'.count($templatevars['contentareas']).'<span class="longdesc"> ';
                $item.= (count($templatevars['contentareas'])!=1) ? returnIntLang('str contentareas') : returnIntLang('str contentarea');
                $item.= ' '.getTemplateName($realtemp);
                $item.= '</span></span> ';
            }
        } else {
            $templatevars['contentareas'] = array(0);
        }
        $contentres = getResultSQL("SELECT `cid` FROM `content` WHERE `mid` = ".intval($middata_res['set'][0]['mid'])." AND `trash` = 0 AND (`content_lang` = '".escapeSQL(trim($_SESSION['wspvars']['workspacelang']))."' OR `content_lang` = '') AND `content_area` IN ('".implode("','", $templatevars['contentareas'])."')");
        if ($contentres!==false) {
            // editable 1 == REALLY editable + editable 9 == setup dynamic contents
            if ($middata_res['set'][0]['editable']==1 || $middata_res['set'][0]['editable']==9) {
                $item.= ' <a class="toggle-content btn-link" mid="'.$middata_res['set'][0]['mid'].'"><span class="label inline-label label-primary">'.count($contentres).'<span class="longdesc"> '.((count($contentres)!=1) ? returnIntLang('str contents') : returnIntLang('str content')).'</span></span></a> ';
            }
            else if ($middata_res['set'][0]['editable']==7) {
                $item.= ' <span class="label inline-label label-primary">'.count($contentres).'<span class="longdesc"> '.((count($contentres)!=1) ? returnIntLang('str dynamic contents') : returnIntLang('str dynamic content')).'</span></span> ';
            }
        }
        $item.= '</h3>';
        
        $item.= '<div class="right">';        

        if ($middata_res['set'][0]['editable']==1) {
            $item.= ' &nbsp;<a onclick="createContent('.intval($middata_res['set'][0]['mid']).',0);" title="'.prepareTextField(returnIntLang('contents add content to page', false)).'" style="cursor: pointer;"><i class="fa fa-plus-square"></i></a> ';

            // check if page is already in wspqueue
            $cpub_sql = intval(getNumSQL("SELECT `id` FROM `wspqueue` WHERE (`action` = 'publishcontent' OR `action` = 'publishitem' OR `action` = 'publishstructure') AND `param` = ".intval($middata_res['set'][0]['mid'])." AND `done` = 0"));
            if ($cpub_sql>0) {
                $item.= ' &nbsp;<a><i class="fas fa-spinner fa-spin"></i></a> ';    
            } else {
                $item.= ' &nbsp;<a id="toggledirectpublish-'.intval($middata_res['set'][0]['mid']).'" onclick="setupPublisher('.intval($middata_res['set'][0]['mid']).');" title="'.prepareTextField(returnIntLang('contents add page to publisher', false)).'" style="cursor: pointer;"><i class="fas fa-globe"></i></a> ';    
            }
        }
        
        $item.= ' &nbsp;<a href="./showpreview.php?previewid='.intval($middata_res['set'][0]['mid']).'&previewlang='.trim($_SESSION['wspvars']['workspacelang']).'" target="_blank" title="'.prepareTextField(returnIntLang('str preview', false)).'"><i class="fas fa-eye"></i></a>';
        
        $item.= '</div>';
        $item.= '</div>';
        $item.= '<div class="panel-body" id="carea-'.intval($middata_res['set'][0]['mid']).'" style="display: none;">';
        $item.= '<em>this area will show contents on call</em>';
        $item.= '</div>';
        $item.= '</div>';
        if ($subdata_res['num']>0 && $showsub) {
            // calling returnContentStructureItem to get next level
            $item.= '<div class="sub" ';
            if (!(is_array($openpath)) || (is_array($openpath) && !(in_array(intval($mid), $openpath)))) {
                $item.= ' style="display: none;" ';
            }
            $item.= '>';
            $lvl++;
            foreach ($subdata_res['set'] AS $sk => $sv) {
                $item.= returnContentStructureItem($datatable, $sv['mid'], $showsub, $maxlevel, $openpath, $posmid, $lvl);
            }
            $item.= '</div>';
        }
    }
    return $item;
    }
endif;

// set deprecated 2019-08-20
if (!(function_exists('getContents'))) {
    function getContents($mid, $spaces, $modi, $cid = 0, $editable = 0, $contentareas = array()) {
        return "<em>getContents() is deprecated</em>";
    }	// getContents
}

if (!(function_exists('returnContents'))) {
	function returnContents($mid = 0, $cid = 0, $careas = array(), $language = '', $mode = 0) {
        // mid: required » the menupoint we want to read contents from
        // cid: optional » the content selected
        // careas: optional » a numeric array with all content areas, that shall be shown in list of existing order
        //         working example will be set her later
        // language: optional » if not set, workspace language will be used 
        // mode:
        // 0 = generic list as used in structure.php
        // 1 = generic list as used in structure.php without action dropdown and adding contents option, but draggable
        
        $templatevars = array();
        if (intval($mid)>0) {
            // get the template information
            $realtemp = getTemplateID(intval($mid));
            $templatevars = getTemplateVars($realtemp);
            // get the description of content areas as an option
            $contentvardesc = unserializeBroken(getWSPProperties(array('contentvardesc')));
        }
        // if some content areas exist
        if (count($templatevars)>0) {
            if ((!(is_int($careas)) && !(is_array($careas))) || (is_array($careas) && count($careas)==0)) {
                $careas = range(0, intval(count($contentvardesc)-1));
            } else if (is_int($careas)) {
                $careas = array($careas);
            }
        
            $output = '';
            $output.= "<div class='dd'><ul class='dd-list content-sortable' mid='".intval($mid)."'>";
            foreach ($templatevars['contentareas'] AS $tvck => $tvcv) {
                if (in_array($tvck, $careas)) {
                    $output.= "<li class='dd-item custom-item dd-area ";
                    if ($tvck==0): $output.= " dd-disabled "; endif;
                    $output.= "' id='area'><div class='custom-content' style='padding-left: 15px;'>";
                    if (isset($contentvardesc[$tvck]) && trim($contentvardesc[$tvck])!='') {
                        $output.= trim($contentvardesc[$tvck]);
                    }
                    else {
                        $output.= returnIntLang('str contentarea')." ".($tvck+1);
                    }
                    if ($mode==0) {
                        $output.= "<div class='right' style='padding-right: 15px;'>";
                        $output.= "<a onclick=\"createContent(".intval($mid).",".(intval($tvck)+1).");\"><i class='fa fa-plus-square'></i> ".returnIntLang('contents add content')."</a>";
                        $output.= "</div>";
                    }
                    $output.= "</div>";
                    $output.= "</li>\n";
                    // select contents related to mid
                    $consel_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($mid)." AND `content_area` = ".(intval($tvck)+1)." AND `trash` = 0 AND (`content_lang` = '".escapeSQL(trim($_SESSION['wspvars']['workspacelang']))."' OR `content_lang` = '') ORDER BY `position`";
                    $consel_res = doSQL($consel_sql);
                    if ($consel_res['num']>0) {
                        // run all contentareas with their contents
                        foreach ($consel_res['set'] AS $csk => $csv) {
                            // setup content information array
                            $output.= returnContentItem($csv, $mode);
                        }
                    }
                }
            }
            $output.= '</ul></div>';
        } 
        else {
            $output = returnIntLang('contents no content area found');
        }
        
        return $output;
    }
}

// inserts content from dataset (based on table `content` structure to a special mid)
if (!(function_exists('insertContents'))) {
    function insertContents($dataset = array(), $mid = 0, $carea = 0, $pos = 0, $language = '') {
        if (count($dataset)>0 && intval($mid)>0) {
            $sql = "INSERT INTO `content` SET ";
            $sql.= "`mid` = ".intval($mid).", ";
            $sql.= "`uid` = ".intval($_SESSION['wspvars']['userid']).", ";
            $sql.= ((isset($dataset['interpreter_guid']))?"`interpreter_guid` = '".$dataset['interpreter_guid']."', ":'');
            $sql.= ((isset($dataset['globalcontent_id']))?"`globalcontent_id` = ".intval($dataset['globalcontent_id']).", ":'');
            $sql.= ((isset($dataset['connected']))?"`connected` = ".intval($dataset['connected']).", ":'');
            $sql.= "`content_area` = ".intval($carea).", ";
            $sql.= "`content_lang` = '".((trim($language)!='')?trim($language):((isset($dataset['content_lang']))?$dataset['content_lang']:WSP_LANG))."', ";
            $sql.= "`position` = ".intval($pos).", ";
            $sql.= ((isset($dataset['visibility']))?"`visibility` = ".intval($dataset['visibility']).", ":"`visibility` = 1, ");
            $sql.= ((isset($dataset['showday']))?"`showday` = ".intval($dataset['showday']).", ":"`showday` = 0, ");
            $sql.= ((isset($dataset['showtime']))?"`showtime` = '".(trim($dataset['showtime']))."', ":"`showtime` = '', ");
            $sql.= ((isset($dataset['sid']))?"`sid` = ".intval($dataset['sid']).", ":'');
            $sql.= ((isset($dataset['description']) && trim($dataset['description'])!='')?"`description` = '".$dataset['description']."', ":'');
            if (isset($dataset['valuefields'])) {
                $sql.= "`valuefields` = '".escapeSQL(serialize(unserializeBroken($dataset['valuefields'])))."', ";
            } else {
                $sql.= "`valuefields` = '', ";
            }
            $sql.= ((isset($dataset['container']))?"`container` = ".intval($dataset['container']).", ":'');
            $sql.= ((isset($dataset['containerclass']) && trim($dataset['containerclass'])!='')?"`containerclass` = '".($dataset['containerclass'])."', ":'');
            $sql.= ((isset($dataset['containeranchor']) && trim($dataset['containeranchor'])!='')?"`containeranchor` = '".($dataset['containeranchor'])."', ":'');
            $sql.= ((isset($dataset['containerview']))?"`containerview` = ".intval($dataset['containerview']).", ":'');
            $sql.= ((isset($dataset['displayclass']))?"`displayclass` = ".intval($dataset['displayclass']).", ":'');
            $sql.= ((isset($dataset['login']))?"`login` = ".intval($dataset['login']).", ":'');
            $sql.= ((isset($dataset['logincontrol']))?"`logincontrol` = '".($dataset['logincontrol'])."', ":'');
            $sql.= "`lastchange` = ".time(); // last element in set
            $res = doSQL($sql);
            if ($res['inf']>0) {
                return (intval($res['inf']));
            }
            else {
                if (defined('WSP_DEV') && WSP_DEV) {
                    addWSPMsg('errormsg', var_export($res, true));
                }
                return false;
            }
        }
    }
}

// insert SINGLE content element
function insertContent($mid, $op = 'add', $lang = '', $carea = 0, $posvor = 0, $sid = false, $gcid = false) {
    // detect or set content position
    $newpos = intval($posvor);
	if ($newpos>0) {
		$exc_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid)." AND `content_area` = ".intval($carea)." AND `position` >= ".$newpos." ORDER BY `position`";
		$exc_res = doSQL($exc_sql);
		if ($exc_res['num']>0) {
			for ($ecres=0; $ecres<$exc_res['num']; $ecres++) {
                doSQL("UPDATE `content` SET `position` = ".($newpos+$ecres+1)." WHERE `cid` = ".intval($exc_res['set'][$ecres]['cid']));
			}
		}
    }
	else {
		$pc_sql = "SELECT MAX(`position`) AS `maxpos` FROM `content` WHERE `mid` = ".intval($mid)." AND `content_area` = ".intval($carea);
		$pc_res = doSQL($pc_sql);
		if ($pc_res['num']>0) { $newpos = intval($pc_res['set'][0]['maxpos'])+1; } else { $newpos = 1; }
	}
    // set $interpreterguid to given interpreter, but maybe overwrite it with global content interpreter
	$interpreterguid = trim($sid); 
    $globalcontentid = NULL;
    // check for globalcontent
    if (intval($gcid)>0) {
        // if global content was choosen, check what global content was choosen and insert THIS into content table
		$gc_sql = "SELECT `id`, `interpreter_guid` FROM `content_global` WHERE `id` = ".intval($gcid)." LIMIT 0,1";
		$gc_res = doSQL($gc_sql);
		if ($gc_res['num']>0) {
            $interpreterguid = trim($gc_res['set'][0]['interpreter_guid']); 
            $globalcontentid = intval($gc_res['set'][0]['id']); 
        }
    }
    // CREATE the new content entry
    $nc_sql = "INSERT INTO `content` SET 
		`mid` = ".intval($mid).",
        `uid` = ".intval($_SESSION['wspvars']['userid']).",
		`globalcontent_id` = ".intval($globalcontentid).",
		`connected` = 0,
		`content_area` = ".intval($carea).",
		`content_lang` = '".escapeSQL(trim($lang))."',
		`position` = ".$newpos.",
		`visibility` = 1,
		`showday` = 0,
		`showtime` = '',
		`sid` = 0,
		`valuefields` = '',
		`lastchange` = ".time().",
		`interpreter_guid` = '".escapeSQL($interpreterguid)."'";
    $nc_res = doSQL($nc_sql);
    if ($nc_res['inf']>0) {
        // updating menu for changed content
		$minfo_sql = "SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($mid);
		$minfo_res = doSQL($minfo_sql);
		$ccres = 0; if ($minfo_res['num']>0): $ccres = intval($minfo_res['set'][0]['contentchanged']); endif;
		$nccres = 0; if ($ccres==0): $nccres = 2;
		elseif ($ccres==1): $nccres = 3;
		elseif ($ccres==2): $nccres = 2;
		elseif ($ccres==3): $nccres = 3;
		elseif ($ccres==4): $nccres = 5;
		elseif ($ccres==5): $nccres = 5;
		endif;
		$minfo_sql = "UPDATE `menu` SET `contentchanged` = ".intval($nccres)." WHERE `mid` = ".intval($mid);
		doSQL($minfo_sql);
        // return ID of inserted content
        return $nc_res['inf'];
    }
    else {
        if (defined('WSP_DEV') && WSP_DEV) {
            addWSPMsg('errormsg', var_export($nc_res, true));
        }
        return false;
    }
}

if (!(function_exists('returnContentItem'))) {
    function returnContentItem ($contentdata = array(), $mode = 0) {
        // $contentdata: an array with dataset from table `content`
        // mode:
        // 0 = item as used in structure.php
        // 1 = item as used in contentedit.php without action dropdown and adding contents option, but dragable
        $contentinfo = array();
        $jsinfo = array();
        // 
        $selectable = true;
        $getViewData = '';
        // get information about interpreter
        $interpreter = doSQL("SELECT * FROM `interpreter` WHERE `guid` = '".escapeSQL($contentdata['interpreter_guid'])."'");
        $intername = returnIntLang('unknown interpreter', false);
        // get content description from interpreter values
        $contentvalue = array();
        if (intval($contentdata['globalcontent_id'])>0):
            $globalcontent = doResultSQL("SELECT `valuefields` FROM `content_global` WHERE `id` = ".intval($contentdata['globalcontent_id']));
            if (trim($globalcontent)!=''):
                $contentinfo[] = '<i class="fa fa-globe"></i>';
                $jsinfo[] = returnIntLang('global content', false);
                $contentvalue = unserializeBroken($globalcontent);
            endif;
        else:
            $contentvalue = unserializeBroken($contentdata['valuefields']);
        endif;
        // interpreter name
        if ($interpreter['num']>0) {
            $intername = $interpreter['set'][0]['name'];
            if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".$interpreter['set'][0]['parsefile'])) {
                // supress errors to prevent warnings about false methods
                $er = error_reporting();
                error_reporting(0);
                include DOCUMENT_ROOT."/".WSP_DIR."/data/interpreter/".$interpreter['set'][0]['parsefile'];
                // compare interpreter class against clsinterpreter
                // get all informations about clsInterpreter
                $ci = array();
                $rf = new ReflectionClass('clsInterpreter');
                //run through all methods.
                foreach ($rf->getMethods() as $method) {
                    $ci[$method->name] = array();
                    //run through all parameters of the method.
                    foreach ($method->getParameters() as $parameter) {
                        $ci[$method->name][$parameter->getName()] = $parameter->getType();
                    }
                }
                unset($rf);
                // run through interpreter class and compare against clsInterpreter methods
                // define $ic as true and set to false if methods don't match  
                $ic = true;
                $me = array();
                $rf = new ReflectionClass($interpreterClass);
                //run through all methods
                foreach ($rf->getMethods() as $method) {
                    $me[$method->name] = array();
                    //run through all parameters of the method.
                    foreach ($method->getParameters() as $parameter) {
                        $me[$method->name][$parameter->getName()] = $parameter->getType();
                    }
                    // do compare
                    if (isset($ci[$method->name])) {
                        if (count(array_diff_key($ci[$method->name], $me[$method->name]))>0 || count(array_diff_key($me[$method->name], $ci[$method->name]))>0) {
                            $ic = false;
                        }
                    }
                }
                unset($rf);unset($ci);unset($me);
                // reSet error_reporting
                error_reporting($er);
                // show only if $ic is true
                if ($ic) {
                    $clsInterpreter = new $interpreterClass;
                    if (method_exists($interpreterClass,'getView')) {
                        $getViewData = $clsInterpreter->getView($contentvalue, $contentdata['mid'], $contentdata['cid']);
                    }
                } else {
                    $selectable = false;
                    $getViewData = returnIntLang('contents interpreter has errors and should not be used');
                }
            }
            else {
                $selectable = false;
            }
        } else if ($contentdata['interpreter_guid']=='genericwysiwyg') {
            $intername = returnIntLang('interpreter genericwysiwyg', false);
        } else if ($contentdata['interpreter_guid']=='modularcontent') {
            $intername = returnIntLang('interpreter modularcontent', false);
        }
        if (isset($intername)) {
            $contentinfo[] = ($selectable)?'<a onclick="doEdit(\''.$contentdata['cid'].'\')" style="cursor: pointer;">'.$intername."</a>":$intername; 
            $jsinfo[] = $intername;
        }
        
        // try to get parser getView
        if (trim($getViewData)!='') {
            $contentinfo[] = trim($getViewData);
        }
        
        if (isset($contentdata['description']) && trim($contentdata['description'])!='') {
            $contentinfo[] = ($selectable)?'<a onclick="doEdit(\''.$contentdata['cid'].'\')" style="cursor: pointer;">'.$contentdata['description']."</a>":$contentdata['description'];
        }
        else if (isset($contentvalue['description']) && trim($contentvalue['description'])!='') {
            $contentinfo[] = ($selectable)?'<a onclick="doEdit(\''.$contentdata['cid'].'\')" style="cursor: pointer;">'.$contentvalue['description']."</a>":$contentvalue['description'];
        }
        else if (isset($contentvalue['desc']) && trim($contentvalue['desc'])!='') {
            $contentinfo[] = ($selectable)?'<a onclick="doEdit(\''.$contentdata['cid'].'\')" style="cursor: pointer;">'.$contentvalue['desc']."</a>":$contentvalue['desc']; 
        }
        
        $item = "<li class='dd-item custom-item ";
        if (isset($_SESSION['wspvars']['contentfiltercid']) && is_array($_SESSION['wspvars']['contentfiltercid']) && in_array($contentdata['cid'], $_SESSION['wspvars']['contentfiltercid'])): $item.= " dd-filterresult "; endif;
        $item.= "' id='".$contentdata['cid']."'>";
        $item.= "<div class='dd-handle custom-handle'><i class='fas fa-arrows-alt'></i></div>";
        if ($mode==0) {
            
            $viewstaticon = 'fa-bookmark';
            if (intval($contentdata['showday'])>0) { $viewstaticon = 'fa-calendar'; }
            if (trim($contentdata['showtime'])!='') { $viewstaticon = 'fa-clock'; }
            if ($contentdata['visibility']==2) { $viewstaticon = 'fa-user-times'; }
            if ($contentdata['visibility']==3) { $viewstaticon = 'fa-user-check'; }
            if ($contentdata['visibility']==4) { $viewstaticon = 'fa-user-friends'; }
            
            $item.= '<div class="dd-action custom-action"><div class="dropdown">';
            $item.= '<a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false">';
            if ($selectable) {
                if ($contentdata['visibility']==0) {
                    $item.= '<i id="viewstat-'.$contentdata['cid'].'" class="far '.$viewstaticon.'"></i>';
                } else {
                    $item.= '<i id="viewstat-'.$contentdata['cid'].'" class="fas '.$viewstaticon.'"></i>';
                }
            } else {
                $item.= '<i class="fas fa-ban"></i>';
            }
            $item.= '</a>';
            $item.= '<ul class="dropdown-menu dropdown-menu-left">';
            if ($selectable) {
                $item.= '<li class="dd-disabled">';
                $item.= '<a onclick="doEdit(\''.$contentdata['cid'].'\')"><i class="fas fa-pencil-alt"></i>'.returnIntLang('str edit').'</a></li>';
                $item.= '<li class="dd-disabled"><a onclick="doClone(\''.$contentdata['cid'].'\')"><i class="fas fa-clone"></i>'.returnIntLang('str clone').'</a></li>';
                if ($contentdata['visibility']<2) {
                    $item.= '<li class="dd-disabled"><a onclick="doShowHide(\''.$contentdata['cid'].'\')">';
                    $item.= '<span id="viewstat-'.$contentdata['cid'].'-hidden" '.(($contentdata['visibility']==1)?' style="display: none;" ':'').'><i class="fas '.$viewstaticon.'"></i>'.returnIntLang('str show').'</span>';
                    $item.= '<span id="viewstat-'.$contentdata['cid'].'-shown" '.(($contentdata['visibility']==0)?' style="display: none;" ':'').'><i class="far '.$viewstaticon.'"></i>'.returnIntLang('str hide').'</span>';
                    $item.= '</a></li>';
                }
            }
            $item.= '<li class="dd-disabled"><a onclick="doDelete(\''.$contentdata['cid'].'\',\''.prepareTextField(strip_tags(implode(' - ', $jsinfo))).'\')"><i class="fas fa-trash"></i>'.returnIntLang('str delete').'</a></li>';
            $item.= '</ul>';
            $item.= '</div></div>';
        }
        $item.= "<div class='custom-content' style='padding-left: ".(($mode==0)?'90':'50')."px;'>";
        
        $item.= "<div class='right custom-content-right'>";
        $item.= ((date('Y-m-d', $contentdata['lastchange'])==date('Y-m-d'))?"<i class='far fa-clock'></i>  ".date(returnIntLang('format time'), $contentdata['lastchange']):"<i class='far fa-calendar'></i>  ".date(returnIntLang('format date2'), $contentdata['lastchange']));
        $item.= " | ".returnUserData('shortcut', $contentdata['uid'])."</div>";
        
        $item.= "<p class='custom-content-desc'><span class='custom-content-text'>";
        if (count($contentinfo)>0) {
            $item.= implode(" - </span><span class='custom-content-text'>", $contentinfo); 
        }
        $item.= "</span></p>";
        $item.= "</div>";
        $item.= "</li>\n";
        return $item;
        }
}

// filesystem related functions
// cleanup and remove a directory below document root except(!!!)
if (!(function_exists('removeDir'))):
function removeDIR($path = '') {
    die ('removeDIR is deprecated, please use deleteFolder');
    // SECURE directories that of THIS AND CHILDREN cannot be removed by THIS function
    $scrdir = array(
        '/^\/'.WSP_DIR.'\//'
        );
    // EXCEPTIONS for children of secure directories that can be removed by THIS function
    $xcptdir = array(
        '/^\/'.WSP_DIR.'\/tmp\//'
        );
    $remove = (trim($path)!="")?true:false;
    while (substr($path, 0, 1)=='.'): $path = substr($path, 1); endwhile;
    foreach ($scrdir AS $sdv):
        if (preg_match($sdv, $path)):
            $remove = false;
            foreach ($xcptdir AS $xdv):
                if (preg_match($xdv, $path)):
                    $remove = true;
                endif;
            endforeach;
        endif;
    endforeach;
    if($remove):
		if (is_dir(DOCUMENT_ROOT.$path)):
			$dir = opendir (DOCUMENT_ROOT.$path);
			while ($entry = readdir($dir)):
				if ($entry == '.' || $entry == '..') continue;
				if (is_dir ("..".$path.'/'.$entry)):
					removeDir($path.'/'.$entry);
				elseif (is_file ("..".$path.'/'.$entry) || is_link ("..".$path.'/'.$entry)):
					ftpDeleteFile(FTP_BASE.$path.'/'.$entry, false);
					@unlink(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", DOCUMENT_ROOT."/".$path."/".$entry))));
				endif;
			endwhile;
			closedir ($dir);
			ftpDeleteDir(FTP_BASE.$path, false);
			@rmdir(str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", DOCUMENT_ROOT.$path))));
		endif;
    endif;
	}
endif;

if (!(function_exists('CleanupFolder'))): function CleanupFolder($path = '') { 
    return "function deprecated"; 
} endif;


//	define("gmlTable", 0);
//	define("gmlSelect", 1);
//	define("gmlContent", 3);
//	define("gmlSelectwo", 4);
//	define("gmlPublisher", 5);
//	define("gmlSelectwoID", 6);
//	define("gmlFieldset", 7);
//	define("gmlSortableList", 8);
//	define("gmlPreview", 9);
//	define("showSitemap", 10);
//	define("gmlMIDArray", 11);






//
// reversepath zu einer gegebenen mid ermitteln => rueckgabe array mit allen mid's auf dem weg zur gegebenen mid
//
if (!(function_exists('returnReverseStructure'))):
	function returnReverseStructure($givenmid, $midpath = '') {
		if ($midpath==""):
			$midpath = array($givenmid);
		endif;
		$reverse_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($givenmid);
		$reverse_res = mysql_query($reverse_sql);
		$reverse_num = 0;
		if ($reverse_res) $reverse_num = mysql_num_rows($reverse_res);
		if ($reverse_num>0):
			array_push($midpath, mysql_result($reverse_res,0));
			returnReverseStructure(mysql_result($reverse_res,0), $midpath);
		else:
			$GLOBALS['midpath'] = $midpath;
		endif;
		}
endif;

if (!(function_exists('returnMIDList'))):
	function returnMIDList($givenmid, $midpath = array()) {
		return 'returnMIDList was replaced by returnIDTree';
		}
endif;



if (!(function_exists('getTemplateID'))):
// get template id for given mid up to main template
function getTemplateID($mid) {
    $templateID = 0;
    $mid_sql = "SELECT `templates_id`, `connected` FROM `menu` WHERE `mid` = ".intval($mid);
    $mid_res = doSQL($mid_sql);
    if ($mid_res['num']>0) {
        $templateID = intval($mid_res['set'][0]['templates_id']);
        if ($templateID==0 && intval($mid_res['set'][0]['connected'])>0) {
            $templateID = getTemplateID(intval($mid_res['set'][0]['connected']));
        }
    }
    // get the base template ID if nothing else was found
    if ($templateID==0) {
        $templateID = getWSPProperties('templates_id');
    }
    return intval($templateID);
    }	// getTemplateID()
endif;

if (!(function_exists('getTemplateTree'))):
// get all mid (downwards given connection) using template id even if they are connected only with "use upper template"
function getTemplateTree($tid) {
    
    $tempusage_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE (`templates_id` = ".intval($tid)." OR `templates_id` = 0) AND `trash` = 0";
    $tempusage_res = doSQL($tempusage_sql);
    $usage = array();
    foreach ($tempusage_res['set'] AS $tuk => $tuv):
        if ($tuv['templates_id']==intval($tid)):
            // page uses THIS template
            $usage[] = intval($tuv['mid']);
        else:
            // check for pages that use parent pages template    
            if (getTemplateID(intval($tuv['mid']))==intval($tid)):
                $usage[] = intval($tuv['mid']);
            endif;
        endif;
    endforeach;
    sort($usage);
    
    return $usage;
    
    }	// getTemplateTree()
endif;

// get template vars for given template id
if (!(function_exists('getTemplateVars'))) {
	function getTemplateVars($tid) {
		$tempinfo_sql = "SELECT * FROM `templates` WHERE `id` = ".intval($tid);
		$tempinfo_res = doSQL($tempinfo_sql);
		$tempinfo = array();
		if ($tempinfo_res['num']>0) {
			$tempinfo['contentareas'] = array();
			$template_content = $tempinfo_res['set'][0]['template'];
            $templatevarsregexp = "!(\[%)([A-Z0-9]).*([A-Z0-9])(%\])!";
			preg_match_all($templatevarsregexp, $template_content, $arr, PREG_PATTERN_ORDER);
			$tempinfo['templatevars'] = $arr[0];
            foreach ($tempinfo['templatevars'] AS $tvk => $tvv) {
                $regm = '/(\[\%MENUVAR:)([A-Z0-9\-]*)/';
                $regc = '/(\[\%CONTENTVAR)([:]?)[0-9]{0,}\%\]/';
                if (preg_match($regm, $tvv, $matches, PREG_OFFSET_CAPTURE, 0)==1) {
                    $tempinfo['menuvars'] = trim($matches[2][0]);
                }
                if (preg_match($regc, $tvv, $matches, PREG_OFFSET_CAPTURE, 0)==1) {
                    preg_match('!\d+!', $tvv, $num, PREG_OFFSET_CAPTURE, 0);
                    if (isset($num[0][0])) {
                        $tempinfo['contentareas'][] = intval($num[0][0]);
                    } else {
                        $tempinfo['contentareas'][] = 0;
                    }
                }
            }
            return $tempinfo;
		} else {
            return false;
        }
    }	// getTemplateVars()
}

if (!(function_exists('getTemplateName'))) {
	function getTemplateName($tid) {
		$tempinfo_sql = "SELECT `name` FROM `templates` WHERE `id` = ".intval($tid);
		$tempinfo_res = doSQL($tempinfo_sql);
        return $tempinfo_res['set'][0]['name'];
    }	// getTemplateName()
}

if (!(function_exists('subpMenu'))) {
    function subpMenu($mid) {
        $returnsub = array();
        $connected_sql = "SELECT `mid` FROM `menu` WHERE trash = 0 AND `connected` = ".intval($mid)." ORDER BY `position` ASC, `visibility` ASC";
        $connected_res = doSQL($connected_sql);
        if ($connected_res['num'] > 0) {
            foreach ($connected_res['set'] AS $crsk => $crsv) {
                $returnsub[] = intval($crsv['mid']);
                $subsub = subpMenu(intval($crsv['mid']));
                if (is_array($subsub) && count($subsub)>0) {
                    $returnsub = array_merge($returnsub, $subsub);
                }
            }
        }
        return $returnsub;
	} //subpMenu();
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

// uebergabe des menuepunktes, und WAS geaendert wurde
// rueckgabe des status, der zu setzen ist
if (!(function_exists('contentChangeStat'))) {
	function contentChangeStat($mid, $updated) {
		$nccres = 0; $ccres = 0;
		if (trim($updated)=='content'): $updated = 2; // content
		elseif (trim($updated)=='structure'): $updated = 1; // structure
		elseif (trim($updated)=='complete'): $updated = 3; // structure+content
		elseif (intval($updated)>0): $updated = intval($updated); endif;
		$ccres_sql = "SELECT `contentchanged` FROM `menu` WHERE `mid` = ".intval($mid);
		$ccres = doResultSQL($ccres_sql);
		if ($updated==1) {
			if ($ccres==0) { $nccres = 1; } else if ($ccres==1) { $nccres = 1; } else if ($ccres==2) { $nccres = 3; } else { $nccres = 3; }
        }
		else if ($updated==2) {
			if ($ccres==0) { $nccres = 2; } else if ($ccres==1) { $nccres = 3; } else if ($ccres==2) { $nccres = 2; } else { $nccres = 3; }
        }
		else if ($updated==3) {
			$nccres = 3;
		}
		return $nccres;
    }
}

if (!(function_exists('setContentChangeStat'))) {
    function setContentChangeStat($mid, $updated) {
        if (trim($updated)=='structure') { $updated = 1; }
        else if (trim($updated)=='content') { $updated = 2; }
        else if (trim($updated)=='complete') { $updated = 3; }
        else if (intval($updated)>0) { $updated = intval($updated); }
        doSQL("UPDATE `menu` SET `contentchanged` = ".contentChangeStat(intval($mid), $updated)." WHERE `mid` = ".intval($mid));
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

if (!(function_exists('showMenuDesign'))) {
    // creates code array from string based definition db entry
    function showMenuDesign($code) {
        $coderows = explode("LEVEL", $code);
        $menucode = array();
        $level_buf = 0;
        foreach ($coderows AS $levelvalue) {
            if (trim($levelvalue)!="") {			
                $levelrows = explode("\n", str_replace("[","", str_replace("]","", str_replace("{","", str_replace("}","", stripslashes(trim($levelvalue)))))));
                if (trim($levelrows[0]) != ""):
                    $level_buf = trim($levelrows[0]);
                else:
                    $level_buf++;				
                endif;
                $menucode[($level_buf-1)] = array();
                foreach ($levelrows AS $codevalue) {
                    if (trim($codevalue)!="") {
                        $codeset = explode("=", trim($codevalue));
                        if (isset($codeset[1])) $menucode[($level_buf-1)][(trim($codeset[0]))] = str_replace("'", "", trim($codeset[1])); // 7.5.2015
                    }
                }
            }
        }
        return $menucode;
	} 
} // showMenuDesign();

if (!(function_exists('getMIDfromMenuvar'))) {
    function getMIDfromMenuvar($guid, $mid = NULL) {
        $allMIDs = array();
        // Level und Connected vom zu veröffentlichenden MP
        $mp_akt_sql = "SELECT `connected`,`level` FROM `menu` WHERE `mid` = ".intval($mid);
        $mp_akt_res = doSQL($mp_akt_sql);
        if ($mp_akt_res['num']>0) {
            $mp_akt_lev = intval($mp_akt_res['set'][0]['level']);
            $mp_akt_con = intval($mp_akt_res['set'][0]['connected']);
        }
        $template_sql = "SELECT `id`,`code`,`startlevel` FROM `templates_menu` WHERE `guid` = '".escapeSQL($guid)."'";
        $template_res = doSQL($template_sql);
        if ($template_res['num']>0) {
            $tid = intval($template_res['set'][0]['id']);
			$sl = intval($template_res['set'][0]['startlevel']);
			$code = showMenuDesign(trim($template_res['set'][0]['code']));
			// test if MENU.SHOW exists in menucode
			foreach ($code AS $celem => $cdata) {
                if (isset($cdata['MENU.SHOW']) && trim($cdata['MENU.SHOW'])!='') {
                    $el = explode(";", $cdata['MENU.SHOW']);
                    foreach ($el AS $elk => $elv) {
                        $allMIDs[] = intval($elv);
                    }
                }
                if (isset($cdata['MENU.LIST']) && trim($cdata['MENU.LIST'])!='') {
                    $el = explode(";", $cdata['MENU.LIST']);
                    foreach ($el AS $elk => $elv) {
                        $allMIDs[] = intval($elv);
                    }
                }
            }
            /*
            if(is_array($mshow)) {
				else {
					preg_match_all("/LEVEL.*{/",$code, $mlevel);
                    if(is_array($mlevel[0]) && count($mlevel[0])>0) {
						$level_anz = count($mlevel[0]);
						if (($sl<=$mp_akt_lev) && (($sl+$level_anz)>$mp_akt_lev)) {
							$mt = returnIDTree($mid);
							if ($sl>1) {
								$tmp_tree = returnIDRootMaxLevel($mt[$sl-1],($sl+$level_anz));
                            }
							else {
                                
                                echo "<pre>";
                                echo $mid;
                                var_export($mt);
                                echo "<hr/>";
                                echo "</pre>";
                                
								$tmp_tree = returnIDRootMaxLevel($mt[1],($sl+$level_anz));
							}
							foreach ($tmp_tree AS $tmp_mid) {
								if (getTemplateID($tmp_mid)==getTemplateID($mid)) {
									$allMIDs[] = $tmp_mid;
								}
							}
						}
					}
				}
			}
            */
        }
        return $allMIDs;
    }
}

if (!(function_exists('getTmplAndMv'))):
// get all menuvars » AllM
// get all menuvars found in template » TtoM
// get all template ids using menuvar » MtoT
function getTmplAndMv() {
	$allTmpls = array();
	$allTmpls['AllM'] = array();
	$allTmpls['TtoM'] = array();
	$allTmpls['MtoT'] = array();
	$template_sql = "SELECT `id`, `template` FROM `templates`";
	$template_res = doSQL($template_sql);
    if ($template_res['num']>0) {
        $allMVsTemp = array();
        foreach ($template_res['set'] AS $trsk => $trsv) {
            $template = trim($trsv['template']);
            $template_id = intval($trsv['id']);
            if($template!="") {
                @preg_match_all("/\[%MENUVAR:.*%\]/",$template, $mvars);
                if (is_array($mvars) && count($mvars[0])>0) {
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
                }
            }
        }
        $allTmpls['AllM'] = array_unique($allMVsTemp);
	}	
	return $allTmpls;
	}
endif;

// returns an array with all 'mid', that have a structured relation to given mid and a max level DOWNWARDS
if (!(function_exists('returnIDRootMaxLevel'))) {
    function returnIDRootMaxLevel($mid, $maxLevel, $midlist = array()) {
        $connected_sql = "SELECT `mid` FROM `menu` WHERE `trash` != 1 AND `connected` = ".intval($mid)." AND `level`<= " . intval($maxLevel) . " ORDER BY `isindex` DESC, `position` ASC";
        $connected_res = doSQL($connected_sql);
        if ($connected_res['num']>0) {
            foreach ($connected_res['set'] AS $crsk => $crsv) {
                $midlist[] = intval($crsv['mid']);
                $midlist = array_merge($midlist, returnIDRootMaxLevel($crsv['mid'], $maxLevel, $midlist));
            }
        }
        $midlist = array_unique($midlist);
        return $midlist;
    }
}


// get all mid related to a special template id
if (!(function_exists('returnTemplatesMID'))) {
    function returnTemplatesMID($tid) {
        $tplMID = array();
        $tplmid_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0";
        $tplmid_res = getResultSQL($tplmid_sql);
        if (is_array($tplmid_res)) {
            foreach ($tplmid_res AS $tmk => $tmv) {
                if (getTemplateID($tmv)==$tid) {
                    $tplMID[] = intval($tmv);
                }
            }
        }
    return $tplMID;	
    }
}

if (!(function_exists('getAffectedMID'))) {
    function getAffectedMID($mid) {
        $AffectedMID = array();
        if($mid>0):
            $work_array = getTmplAndMv();
            $alltypes = $work_array['AllM'];
            foreach($alltypes AS $mv):
                $mtype_parts = explode(":",$mv);
                $mtype = substr($mtype_parts[1],0,strlen($mtype_parts[1])-2);
                switch ($mtype) {
                    case "FULLLIST":
                        foreach($work_array['MtoT'][$mv] AS $template_id):
                            $AffectedMID = array_merge($AffectedMID,returnTemplatesMID($template_id));
                        endforeach;
                        break;
                    case "FULLSELECT":
                        foreach($work_array['MtoT'][$mv] AS $template_id):
                            $AffectedMID = array_merge($AffectedMID,returnTemplatesMID($template_id));
                        endforeach;
                        break;
                    case "HORIZONTALLIST":
                        $mt = returnIDTree($mid);
                        $lev = array_search($mid,$mt);
                        if($lev>1):
                            $AffectedMID = array_merge($AffectedMID,returnIDRoot($mt[$lev-1]));
                        elseif (isset($mt[1])):
                            $AffectedMID = array_merge($AffectedMID,returnIDRoot($mt[1]));
                        endif;
                        break;
                    case "HORIZONTALDIV":
                        $mt = returnIDTree($mid);
                        $lev = array_search($mid,$mt);
                        if($lev>1):
                            $AffectedMID = array_merge($AffectedMID,returnIDRoot($mt[$lev-1]));
                        elseif (isset($mt[1])):
                            $AffectedMID = array_merge($AffectedMID,returnIDRoot($mt[1]));
                        endif;
                        break;
                    case "HORIZONTALSELECT":
                        break;
                        $mt = returnIDTree($mid);
                        $lev = array_search($mid,$mt);
                        if($lev>1):
                            $AffectedMID = array_merge($AffectedMID,returnIDRoot($mt[$lev-1]));
                        else:
                            $AffectedMID = array_merge($AffectedMID,returnIDRoot($mt[1]));
                        endif;
                    case "SUBLIST":
                        $AffectedMID = array_merge($AffectedMID,returnIDRoot($mid));
                        break;
                    case "SUBDIV":
                        $AffectedMID = array_merge($AffectedMID,returnIDRoot($mid));
                        break;
                    case "SUBSELECT":
                        $AffectedMID = array_merge($AffectedMID,returnIDRoot($mid));
                        break;
                    case "LINKLAST":
                        break;
                    case "LINKNEXT":
                        break;
                    case "LINKUP":
                        break;
                    default:
                        if(isGUID($mtype)):
                            $AffectedMID = array_merge($AffectedMID,getMIDfromMenuvar($mtype, $mid));
                        else:

                        endif;
                }
            endforeach;
        else:
            echo "4";
        endif;
        return $AffectedMID;
	}
}

if (!(function_exists('getEffectedMPs_alt'))):
function getEffectedMPs_alt($mid) {
	$effectedMPs = array();
//	$template_id = getTemplateID($mid);
//	if($template_id>0):
	if($mid>0):
//		$template_sql = "SELECT `template` FROM `templates` WHERE `id` = ".intval($template_id);
		$template_sql = "SELECT `id`,`template` FROM `templates`";
		$template_res = mysql_query($template_sql);
		if ($template_res):
			$template_num = mysql_num_rows($template_res);
			if ($template_num > 0):
			
			for($alltmpl=0;$alltmpl<$template_num;$alltmpl++):
			
//				$template = mysql_result($template_res,0);
				$template = mysql_result($template_res,$alltmpl,"template");
				$template_id = mysql_result($template_res,$alltmpl,"id");
				if($template!=""):
					@preg_match_all("/\[%MENUVAR:.*%\]/",$template, $mvars);
					if(is_array($mvars) && count($mvars[0])>0):
						if(is_array($mvars[0])):
							$alltypes = array_unique($mvars[0]);
						else:
							$alltypes = array();
						endif;
						if(count($alltypes)>0):
							foreach($alltypes AS $mv):
								$mtype_parts = explode(":",$mv);
								$mtype = substr($mtype_parts[1],0,strlen($mtype_parts[1])-2);
								switch ($mtype) {
									case "FULLLIST":
//										$effectedMPs = array_merge($effectedMPs,returnIDRoot(0));
										
										$effectedMPs = array_merge($effectedMPs,returnTemplatesMID($template_id));
										break;
									case "FULLSELECT":
//										$effectedMPs = array_merge($effectedMPs,returnIDRoot(0));
										$effectedMPs = array_merge($effectedMPs,returnTemplatesMID($template_id));
										break;
									case "HORIZONTALLIST":
										// returnIDRoot($_POST['mid']),returnIDTree($_POST['mid'])
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
						endif;
						
					endif;
				else:
					echo "1";
				endif;
			endfor;
			else:
				echo "2";
			endif;
		else:
			echo "3";
		endif;
	else:
		echo "4";
	endif;
	return $effectedMPs;
	}
endif;

/**
 * sitestruktur ausgeben
 */
if (!(function_exists('getMenuStructure'))):
	/* call from sitestructure.php as admin: getMenuStructure(0, array, '', integer, 'structure', 'de'); */ 
	/* call from contentstructure.php as admin: getMenuStructure(0, array, '', 0, 'contents') */
	/* call from contentstructure.php as user: getMenuStructure(0, Array, Array, 0, 'contents') */
	function getMenuStructure($parent = 0, $aSelectIDs = array(), $op = '', $showmidpath = '', $outputtype = null, $showlang = 'de') {
		/* get all menu information to parent connector */
		$gms_sql = "SELECT * FROM `menu` WHERE `connected` = ".intval($parent)." ORDER BY `position`";
		$gms_res = mysql_query($gms_sql);
		if ($gms_res):
			$gms_num = mysql_num_rows($gms_res);
		endif;
		if ($gms_num>0):
			$getList = '';
			for ($gmsres=0; $gmsres<$gms_num; $gmsres++):
				/* get informationen about submenupoints */
				$gmsub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".mysql_result($gms_res, $gmsres, "mid")." ORDER BY `position`";
				$gmsub_res = mysql_query($gmsub_sql);
				if ($gmsub_res): 
					$gmsub_num = mysql_num_rows($gmsub_res);
				endif;
				/* building array with facts */
				$mpfacts = array(
					'rest' => false, /* rest|riction */
					'sel' => false, /* sel|ected */
					'lvl' => 1, /* l|e|v|e|l */
					'forw' => false, /* forw|arding */
					'ext' => false, /* ext|ernlink */
					'int' => false, /* int|ernlink */
					'drag' => false, /* drag|able */
					'sub' => false, /* sub|structure */
					'sd' => '', /* title information */
					'act' => false, /* act|ion allowed */
					'amd' => false, /* a|ction m|enupoint d|elete */
					'amc' => false, /* a|ction m|enupoint c|lone */
					'ams' => false, /* a|ction m|enupoint s|ubmenu */
					'amv' => false, /* a|ction m|enupoint v|isibility */
					'ama' => false, /* a|ction m|enupoint a|ddcontent */
					'con' => false /* con|tent editing allowed */
					);
				/* get information about access restrictions */
				if (is_array($op)):
					/* access restrictions exist */
					if ((in_array(mysql_result($gms_res, $gmsres, "mid"), $op))):
						$mpfacts['rest'] = false;
					else:
						$mpfacts['rest'] = true;
					endif;
				endif;
				
				/* is menupoint in array of selected menupoints */
				if (is_array($aSelectIDs)):
					/* case selection is defined */
					if ((in_array(mysql_result($gms_res, $gmsres, "mid"), $aSelectIDs))):
						/* case THIS point is in selection */
						$mpfacts['sel'] = true;
					endif;
				endif;
				/* get menupoint level */
				$mpfacts['lvl'] = intval(mysql_result($gms_res, $gmsres, "level"));
				/* get type of menupoint to set right icon */
				if (mysql_result($gms_res, $gmsres, "forwardmenu")==1):
					$mpfacts['forw'] = true;
				endif;
				if (trim(mysql_result($gms_res, $gmsres, "offlink")!="")):
					$mpfacts['ext'] = true;
				elseif (intval(mysql_result($gms_res, $gmsres, "internlink_id"))>0):
					$mpfacts['int'] = true;
				endif;
				/* set information about subpoints */
				if ($gmsub_num>0):
					$mpfacts['sub'] = true;
				endif;
				$mpfacts['subhidden'] = " <input type=\"hidden\" name=\"sub_".mysql_result($gms_res, $gmsres, "mid")."\" id=\"sub_".mysql_result($gms_res, $gmsres, "mid")."\" value=\"";
				if ($gmsub_num>0):
					$midblock = array();
					for ($gmsubres=0; $gmsubres<$gmsub_num; $gmsubres++):
						$midblock[] = mysql_result($gmsub_res, $gmsubres);
					endfor;
					$mpfacts['subhidden'].= implode(",", $midblock);
				endif;
				$mpfacts['subhidden'].= "\" />";
				// 3. namen und templateinformationen
				$mpfacts['sd'] = stripslashes(mysql_result($gms_res, $gmsres, "description"));
				if (trim($mpfacts['sd'])==""):
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				endif;
				$sdarray = unserialize(mysql_result($gms_res, $gmsres, "langdescription"));
				if (is_array($sdarray) && trim($sdarray[$showlang])!=""):
					$mpfacts['sd'] = trim(stripslashes($sdarray[$showlang]));
					if ($mpfacts['sd']==stripslashes(mysql_result($gms_res, $gmsres, "description")) && $showlang!='de'):
						$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [".returnIntLang('int', false)."]";
					endif;
				elseif ($showlang!='de'):
					$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [".returnIntLang('de', false)."]";
				endif;
				if (trim($mpfacts['sd'])==""):
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				endif;
				if ($_SESSION['wspvars']['rights']['sitestructure']==1):
					/* get dragdrop information */
					$mpfacts['drag'] = true;
					$mpfacts['act'] = true;
					$mpfacts['amd'] = true;
					$mpfacts['amc'] = true;
					$mpfacts['ams'] = true;
					$mpfacts['amv'] = true;
					$mpfacts['ama'] = true;
				endif;
				if (array_key_exists('structuremidlist', $_SESSION) && is_array($_SESSION['structuremidlist'])):
					if ($_SESSION['wspvars']['rights']['sitestructure']==7 && in_array(mysql_result($gms_res, $gmsres, "mid"), $_SESSION['structuremidlist'])):
						/* get dragdrop information */
						$mpfacts['drag'] = true;
						$mpfacts['act'] = true;
						$mpfacts['amd'] = true;
						if (mysql_result($gms_res, $gmsres, "mid")==$_SESSION['structuremidlist'][0]):
							$mpfacts['amc'] = false;
						else:
							$mpfacts['amc'] = true;
						endif;
						$mpfacts['ams'] = true;
						$mpfacts['amv'] = true;
						$mpfacts['ama'] = true;
					endif;
				endif;

				/* start output */
				if ($mpfacts['act']):
					$getList.= "<li id=\"li_".mysql_result($gms_res, $gmsres, "mid")."\">\n";
					$getList.= "<table id=\"conttab_".mysql_result($gms_res, $gmsres, "mid")."\" class=\"contenttable noborder\" style=\"margin-bottom: 1px;\">";
					/* development output */
					if ($_SESSION['wspvars']['devcontent']):
						$getList.= "<tr class=\"tablehead\"><td colspan='20'>".str_replace("\"hidden", "\"_hidden", serialize($mpfacts))."</td></tr>\n";
					endif;
					/* realtime output */
					$getList.= "<tr class=\"\">\n";
					if ($outputtype=="structure"):
						for ($lv=0; $lv<$mpfacts['lvl']-1; $lv++):
							$getList.= "<td nowrap><span class=\"bubblemessage hidden\">".returnIntLang('bubble showsub', false)."</span></td>\n";
						endfor;
						$getList.= "<td nowrap id=\"showspan_".mysql_result($gms_res, $gmsres, "mid")."\" >";
						/* output link, if no restriction exists */
						if (!($mpfacts['rest']) && $mpfacts['sub']):
							/* case submenupoints exist */ 
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"addShowSub(".mysql_result($gms_res, $gmsres, "mid").", ".mysql_result($gms_res, $gmsres, "level").");\">";
						elseif (!($mpfacts['rest']) && !($mpfacts['int']) && !($mpfacts['ext']) && !($mpfacts['forw'])):
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"addShowNew(".mysql_result($gms_res, $gmsres, "mid").", ".mysql_result($gms_res, $gmsres, "level").");\">";
						endif;
						$getList.= "<span class=\"bubblemessage ";
						if ($mpfacts['sub']):
							$getList.= " orange ";
						endif;
						if ($mpfacts['rest'] || (($mpfacts['int'] || $mpfacts['ext'] || $mpfacts['forw']) && !($mpfacts['sub']))):
							$getList.= " disabled ";
						endif;
						$getList.= "\">";
						
						if ($mpfacts['sub']):
							$getList.= returnIntLang('bubble showsub', false);
						elseif ($mpfacts['ext']):
							$getList.= returnIntLang('bubble externlink', false);
						elseif ($mpfacts['int']):
							$getList.= returnIntLang('bubble internlink', false);
						elseif ($mpfacts['forw']):
							$getList.= returnIntLang('bubble forwarder', false);
						else:
							$getList.= returnIntLang('bubble showsub', false);
						endif;
						$getList.= "</span>";
						if (!($mpfacts['rest'])):
							$getList.= "</a>";
						endif;
						$getList.= "</td>\n";
						if ($mpfacts['drag']):
							if (mysql_result($gms_res, $gmsres, "mid")!=intval($_SESSION['structuremidlist'][0])):
								$getList.= "<td nowrap><table id=\"handletable_".mysql_result($gms_res, $gmsres, "mid")."\" border=\"0\" cellspacing=\"0\" cellpadding=\"1\"><tr><td><div class=\"handle\" id=\"handle_".mysql_result($gms_res, $gmsres, "mid")."\" style=\"cursor: move; float: right;\" onmouseover=\"document.getElementById('li_".mysql_result($gms_res, $gmsres, "mid")."').className = 'hoverclass';\" onmouseout=\"document.getElementById('li_".mysql_result($gms_res, $gmsres, "mid")."').className = 'nohover'; searchStructure(document.getElementById('searchStructure').value, document.getElementById('highlightstructure').value);\"><span class=\"bubblemessage orange\">".returnIntLang('bubble move', false)."</span></div></td></tr></table></td>\n";
							endif;
						
							// description and edit-click-area
							$getList.= "<td nowrap width=\"100%\">";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a style=\"cursor: pointer;\" onclick=\"editMenupoint(".mysql_result($gms_res, $gmsres, "mid").")\">".$mpfacts['sd']."</a>";
							
							// development
							if ($_SESSION['wspvars']['devcontent']):
								$getList.= " [mid".mysql_result($gms_res, $gmsres, "mid")."]";
							endif;				
							$templatedesc = "";
							if (intval(mysql_result($gms_res, $gmsres, "templates_id"))==0):
								$templatedesc.= "^ ";
							endif;
							$tmplid = getTemplateID(intval(mysql_result($gms_res, $gmsres, "mid")));
							$tplname_sql = "SELECT `name` FROM `templates` WHERE `id` = ".$tmplid;
							$tplname_res = mysql_query($tplname_sql);
							if ($tplname_res):
								$tplname_num = mysql_num_rows($tplname_res);
							endif;
							if ($tplname_num>0):
								$templatedesc.= mysql_result($tplname_res, 0);
							else:
								// else status main template
								$templatedesc.= "undefined";
							endif;
							
							$status = 'extern';
							$status = 'forwarding';
							$status = 'structure';
							
							$getList.= " ".helpText(returnIntLang('str menutypestat', false).': '.returnIntLang("structure menutypestat ".$status, false).'<br />'.returnIntLang("str filename", false).': '.mysql_result($gms_res, $gmsres, "filename").'.php<br />'.returnIntLang("str template", false).': '.$templatedesc.'<br />'.returnIntLang("structure lastchange", false).': '.date("Y-m-d H:i:s", mysql_result($gms_res, $gmsres, "changetime")), false)." ";						
							$getList.= "</span>";
							
							$getList.= $mpfacts['subhidden']."</td>\n";
						elseif ($_SESSION['wspvars']['rights']['sitestructure']==3 || ($_SESSION['wspvars']['rights']['sitestructure']==4 && in_array(mysql_result($gms_res, $gmsres, "mid"), $_SESSION['wspvars']['rights']['sitestructure_array']))):
							// description and edit-click-area
							$getList.= "<td width=\"100%\" nowrap>";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a style=\"cursor: pointer;\" onclick=\"setEdit(".mysql_result($gms_res, $gmsres, "mid").")\">".$mpfacts['sd']."</a></span><span class=\"handle\"></span>";
							$getList.= $mpfacts['subhidden']."</td>\n";
						elseif ($_SESSION['wspvars']['rights']['sitestructure']==7 && in_array(mysql_result($gms_res, $gmsres, "mid"), $_SESSION['structuremidlist'])):
							// description and edit-click-area
							$getList.= "<td width=\"100%\" nowrap>";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a href=\"".$_SERVER['PHP_SELF']."?action=edit&mid=".mysql_result($gms_res, $gmsres, "mid")."\">".$mpfacts['sd']."</a></span><span class=\"handle\"></span>";
							$getList.= $mpfacts['subhidden']."</td>\n";
						else:
							// description
							$getList.= "<td width=\"100%\" nowrap>";
							$getList.= "<span style=\"float: left; margin-right: 4px;\">".$mpfacts['sd']."</span><span class=\"handle\" style=\"display: none;\"></span>";
							$getList.= $mpfacts['subhidden']."</td>\n";
						endif;
						//
						// menupoint actions
						//
						if ($mpfacts['act']):
							$getList.= "<td nowrap>";
							/* delete menupoint */ 
							$getList.= " <a onclick=\"confirmDelete(".intval(mysql_result($gms_res, $gmsres, "mid")).",'".str_replace("\"", "‘", $mpfacts['sd'])."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a>\n";
							// duplicate menupoint
							$getList.= " <a onclick=\"confirmClone(".intval(mysql_result($gms_res, $gmsres, "mid")).");\"><span class=\"bubblemessage orange\">".returnIntLang('bubble clone', false)."</span></a>\n";
							// ad submenupoint to THIS menupoint
							$getList.= " <a onclick=\"document.getElementById('newmenuitem').focus(); document.getElementById('subpointfrom').value = '".mysql_result($gms_res, $gmsres, 'mid')."' ; menuTemp('".mysql_result($gms_res, $gmsres, 'mid')."');\"><span class=\"bubblemessage orange\">".returnIntLang('bubble addsubmenu', false)."</span></a>\n";
							// change visibility
							if (intval(mysql_result($gms_res, $gmsres, "visibility"))==1):
								$getList.= " <a id=\"acv_" . intval(mysql_result($gms_res, $gmsres, "mid")) . "\" onclick=\"return confirmVisibility(".intval(mysql_result($gms_res, $gmsres, "mid")).", 'hide');\"><span class=\"bubblemessage green\">".returnIntLang('bubble hide', false)."</span></a>\n";
							else:
								$getList.= " <a id=\"acv_" . intval(mysql_result($gms_res, $gmsres, "mid")) . "\" onclick=\"return confirmVisibility(".intval(mysql_result($gms_res, $gmsres, "mid")).", 'show');\"><span class=\"bubblemessage red\">".returnIntLang('bubble show', false)."</span></a>\n";
							endif;
							$getList.= "</td>";
						endif;
					
						
					elseif ($outputtype=="contents"):
						
						$getList.= "<td nowrap>";
						if ($mpfacts['sub']):
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"showSub(".mysql_result($gms_res, $gmsres, "mid").", ".mysql_result($gms_res, $gmsres, "level").");\"><span class=\"bubblemessage orange\">".returnIntLang('bubble showsub', false)."</span></a>\n";
						elseif ($mpfacts['int']):
							$getList.= "<span class=\"bubblemessage disabled\">".returnIntLang('bubble forwarder', false)."</span>\n";
						else:
							$getList.= "<span class=\"bubblemessage orange disabled\">".returnIntLang('bubble showsub', false)."</span>\n";
						endif;
							
						if ($mpfacts['con'] && !($mpfacts['int'])):
							$getList.= " <a style=\"cursor: pointer;\" onclick=\"addShowContents(".mysql_result($gms_res, $gmsres, "mid").", ".mysql_result($gms_res, $gmsres, "level").");\"><span class=\"bubblemessage blue\">".returnIntLang('bubble showcontent', false)."</span></a>";
						else:
							$getList.= " <span class=\"bubblemessage blue disabled\">".returnIntLang('bubble showcontent', false)."</span>";
						endif;
						$getList.= "</td>\n";
						
						// output page name
						$getList.= "<td nowrap id=\"contentheadcell_".mysql_result($gms_res, $gmsres, "mid")."\">";
						$getList.= "<span style=\"float: left;\" id=\"contenthead_".mysql_result($gms_res, $gmsres, "mid")."\">".$mpfacts['sd'];
						// development
						if ($_SESSION['wspvars']['devcontent']):
							$getList.= " [mid".mysql_result($gms_res, $gmsres, "mid")."]";
						endif;
						$getList.= "</span>";
						$getList.= $mpfacts['subhidden']."</td>\n";
						// output count content areas and count contents
						// get content area count
						$tempinfo_sql = "SELECT `template` FROM `templates` WHERE `id` = ".getTemplateID(mysql_result($gms_res, $gmsres, "mid"));
						$tempinfo_res = mysql_query($tempinfo_sql);
						$tempinfo_num = mysql_num_rows($tempinfo_res);
						if ($tempinfo_num>0):
							$template_content = mysql_result($tempinfo_res,0);
							unset($c);
							$contentareas = array();
							$c=0;
							while (str_replace("[%CONTENTVAR".$c."%]","[%CONTENT%]",$template_content)!=$template_content):
								$c++;
								$contentareas[] = $c;
							endwhile;
						endif;
						// get content count
						$contents = 0;
						foreach ($contentareas AS $cavalue):
							$ccount_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval(mysql_result($gms_res, $gmsres, "mid"))." AND `content_area` = ".intval($cavalue);
							$ccount_res = mysql_query($ccount_sql);
							if ($ccount_res):
								$contents = $contents + mysql_num_rows($ccount_res);
							endif;
						endforeach;
						if ($mpfacts['con'] && !($mpfacts['int'])):
							$getList.= "<td width=\"100%\">[".intval($contents)." ";
							if ($contents!=1):
								$getList.= returnIntLang('str contents', true)." ";
							else:
								$getList.= returnIntLang('str content', true)." ";
							endif;
							$getList.= returnIntLang('str in', true);
							$getList.= " ".count($contentareas)." ";
							if (count($contentareas)!=1):
								$getList.= returnIntLang('str contentareas', true); 
							else:
								$getList.= returnIntLang('str contentarea', true);
							endif;
							$getList.= "]</td>\n";
						else:
							$getList.= "<td width=\"100%\">&nbsp;</td>";
						endif;
						// split here with rights system if adding is allowed 
						if ($mpfacts['con'] && ($_SESSION['wspvars']['rights']['contents']!=3 && $_SESSION['wspvars']['rights']['contents']!=4) && !($mpfacts['int'])):
							$getList.= "<td nowrap><a onclick=\"addContent(".intval(mysql_result($gms_res, $gmsres, "mid")).", 0);\"><span class=\"bubblemessage orange\">".returnIntLang('bubble addcontent to page', false)."</span></a></td>\n";
						endif;
					elseif ($outputtype=="preview"):
						// thumbstat 0 = anzeigen ; thumbstat 1 = verbergen
						$thumbstat = intval(@mysql_result(mysql_query("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'websitethumb'"), 0));
						$getList.= "<td nowrap width=\"100%\">";
						if ($_SESSION['wspvars']['rights']['publisher']==1 || ($_SESSION['wspvars']['rights']['publisher']==2 && in_array(mysql_result($gms_res, $gmsres, "mid"), $_SESSION['wspvars']['rights']['publisher_array']))):
							$getList.= "<span class=\"handle\"></span><span style=\"float: left;\"><a onClick=\"document.getElementById('previewid').value = '".intval(mysql_result($gms_res, $gmsres, "mid"))."'; document.getElementById('previewform').submit(); return false;\" style=\"cursor: pointer;\">".$mpfacts['sd']."</a> [".mysql_result($gms_res, $gmsres, "filename")."]</span>";
						else:
							$getList.= "<span class=\"handle\"></span><span style=\"float: left;\">".$mpfacts['sd']." [".mysql_result($gms_res, $gmsres, "filename")."]</span>";
						endif;
						$getList.= $mpfacts['subhidden']."</td>\n";
	//				else:
	//					$getList.= "<td nowrap width=\"100%\">";
	//					$getList.= "<span style=\"float: left;\">".$mpfacts['sd']."</span>";
	//					$getList.= $mpfacts['subhidden']."</td>\n";
					endif;
					
					$getList.= "</tr></table>\n";
					/* add content table area */
					if ($outputtype=="contents"):
						$getList.= "<span id=\"contenttable_".mysql_result($gms_res, $gmsres, "mid")."\"></span>\n";
					endif;
					/* add dragable submenu list */
					$getList.= "<ul id=\"ul_".mysql_result($gms_res, $gmsres, "mid")."\" class=\"dragable\">";
					returnReverseStructure($showmidpath);
					if (in_array(mysql_result($gms_res, $gmsres, "mid"), $GLOBALS['midpath'])):
						$menu = getMenuStructure(mysql_result($gms_res, $gmsres, "mid"), $aSelectIDs, $op, $showmidpath, $outputtype, $showlang);
						$getList.= $menu[0];
					endif;
					$getList.= "</ul>\n</li>\n";
				endif;
			endfor;
		endif;
		
		return array($getList);
		} // getMenuStructure
endif;

if (!(function_exists('getMenuOption'))):
	
	
endif;

if (!(function_exists('getjMenuStructure'))):
	/* call from sitestructure.php as admin: getjMenuStructure(0, array, '', array, 'structure', 'de'); */ 
	/* call from contentstructure.php as admin: getjMenuStructure(0, array, '', 0, 'contents') */
	/* call from contentstructure.php as user: getjMenuStructure(0, Array, Array, 0, 'contents') */
	/* call from publisher.php as admin: getjMenuStructure(0, array, '', array, 'publisher', lang) */
	function getjMenuStructure($parent = 0, $aSelectIDs = array(), $op = '', $showmidpath = array(), $outputtype = 'structure', $showlang = 'de') {
		// define empty output var
		$getList = '';
		/* get all menu information to parent connector */
		$gms_sql = "SELECT * FROM `menu` WHERE `trash` = 0 AND `connected` = ".intval($parent)." ORDER BY `position`";
		$gms_data = doSQL($gms_sql);
		// get count publishing required information from menu
		$pub_sql = "SELECT * FROM `menu` WHERE `trash` = 0 AND `visibility` = 1 AND `editable` = 1 AND `contentchanged` != 0";
		$pub_res = mysql_query($pub_sql);
		$pub_num = 0; if ($pub_res): $pub_num = mysql_num_rows($pub_res); endif;
		// get template information to display in menuedit		
		$tplopt_sql = "SELECT `id`, `name` FROM `templates`";
		$tplopt_res = mysql_query($tplopt_sql);
		$tplopt_num = 0; if ($tplopt_res): $tplopt_num = mysql_num_rows($tplopt_res); endif;
		// run loop
		if ($gms_data['num']>0):
			for ($gmsres=0; $gmsres<$gms_data['num']; $gmsres++):
				/* get informationen about submenupoints */
				$gmsub_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0 AND `connected` = ".intval($gms_data['set'][$gmsres]["mid"])." ORDER BY `position`";
				$gmsub_data = doSQL($gmsub_sql);

				/* building array with facts */
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
				if (is_array($op)):
					/* access restrictions exist */
					if ((in_array(intval($gms_data['set'][$gmsres]['mid']), $op))):
						$mpfacts['rest'] = false;
					else:
						$mpfacts['rest'] = true;
					endif;
				endif;
				
				/* is menupoint in array of selected menupoints */
				if (is_array($aSelectIDs)):
					/* case selection is defined */
					if ((in_array(intval($gms_data['set'][$gmsres]['mid']), $aSelectIDs))):
						/* case THIS point is in selection */
						$mpfacts['sel'] = true;
					endif;
				endif;
				/* get menupoint level */
				$mpfacts['lvl'] = intval($gms_data['set'][$gmsres]['level']);
				// get editable stat
				if ($gms_data['set'][$gmsres]['editable']!=1):
					$mpfacts['edit'] = false;
				endif;
				/* get type of menupoint to set right icon */
				if ($gms_data['set'][$gmsres]['forwardmenu']==1):
					$mpfacts['forw'] = true;
				endif;
				if (trim($gms_data['set'][$gmsres]['offlink']!="")):
					$mpfacts['ext'] = true;
				elseif (intval($gms_data['set'][$gmsres]['internlink_id'])>0):
					$mpfacts['int'] = true;
				endif;
				/* set information about subpoints */
				if ($gmsub_data['num']>0):
					$mpfacts['sub'] = true;
				endif;
				$mpfacts['subhidden'] = " <input type=\"_hidden\" name=\"sub_".intval($gms_data['set'][$gmsres]['mid'])."\" id=\"sub_".intval($gms_data['set'][$gmsres]['mid'])."\" value=\"";
				if ($gmsub_data['num']>0):
					$midblock = array();
					for ($gmsubres=0; $gmsubres<$gmsub_data['num']; $gmsubres++):
						$midblock[] = intval($gmsub_data['set'][$gmsubres]['mid']);
					endfor;
					$mpfacts['subhidden'].= implode(",", $midblock);
				endif;
				$mpfacts['subhidden'].= "\" />";
				// 2. contentinformationen sammeln
				if ($_SESSION['wspvars']['rights']['contents']==1):
					$mpfacts['con'] = true;
					$mpfacts['act'] = true;
				else:
					if (is_array($op)):
						if (in_array(intval($gms_data['set'][$gmsres]['mid']), $op)):
							$mpfacts['act'] = true;
						endif;
					else:
						$mpfacts['act'] = true;
						$mpfacts['con'] = true;
					endif;
					if (is_array($aSelectIDs)):
						if (in_array(intval($gms_data['set'][$gmsres]['mid']), $aSelectIDs)):
							$mpfacts['con'] = true; 
						endif;
					endif;
				endif;
				// 3. namen und templateinformationen
				$mpfacts['sd'] = stripslashes($gms_data['set'][$gmsres]['description']);
				if (trim($mpfacts['sd'])==""):
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				endif;
				$sdarray = unserializeBroken($gms_data['set'][$gmsres]['langdescription']);
				$dlarray = unserializeBroken($gms_data['set'][$gmsres]['denylang']);
				if (is_array($sdarray) && array_key_exists($showlang, $sdarray) && trim($sdarray[$showlang])!="" && count($_SESSION['wspvars']['lang'])>1):
					$mpfacts['sd'] = trim(stripslashes($sdarray[$showlang]));
					if ($mpfacts['sd']==stripslashes($gms_data['set'][$gmsres]['description']) && $showlang!='de'):
						$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [int]";
					endif;
				elseif ($showlang!='de'):
					$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [".$_SESSION['wspvars']['wspbaselang']."]";
				endif;
				if (trim($mpfacts['sd'])==""):
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				endif;
				
				if ($_SESSION['wspvars']['rights']['sitestructure']==1):
					/* get dragdrop information */
					$mpfacts['drag'] = true;
					$mpfacts['act'] = true;
					$mpfacts['amd'] = true;
					$mpfacts['amc'] = true;
					$mpfacts['ams'] = true;
					$mpfacts['amv'] = true;
					$mpfacts['ama'] = true;
				endif;
				if (isset($_SESSION['structuremidlist']) && is_array($_SESSION['structuremidlist'])):
					if ($_SESSION['wspvars']['rights']['sitestructure']==7 && in_array(intval($gms_data['set'][$gmsres]['mid']), $_SESSION['structuremidlist'])):
						/* get dragdrop information */
						$mpfacts['drag'] = true;
						$mpfacts['act'] = true;
						$mpfacts['amd'] = true;
						if (intval($gms_data['set'][$gmsres]['mid'])==$_SESSION['structuremidlist'][0]):
							$mpfacts['amc'] = false;
						else:
							$mpfacts['amc'] = true;
						endif;
						$mpfacts['ams'] = true;
						$mpfacts['amv'] = true;
						$mpfacts['ama'] = true;
					endif;
				endif;
				
				$q_sql = "SELECT `id` FROM `wspqueue` WHERE `param` = ".intval(intval($gms_data['set'][$gmsres]['mid']))." AND `done` = 0";
				$q_res = mysql_query($q_sql);
				$q_num = 0; if ($q_res): $q_num = mysql_num_rows($q_res); endif;
				if ($q_num>0): $mpfacts['que'] = true; endif;
				
				// start output
				if ($mpfacts['act']):
					$getList.= "<li id=\"li_".intval($gms_data['set'][$gmsres]['mid'])."\" class=\"moveable\">\n";
					
					$getList.= "<ul id=\"conttab_".intval($gms_data['set'][$gmsres]['mid'])."\" class=\"tablelist ";
					if ($outputtype=="structure"):
						$getList.= " structure ";
						if (intval(mysql_result($gms_res, $gmsres, "visibility"))==0):
							$getList.= " hiddenstructure ";
						else:
							if (is_array($dlarray) && in_array($showlang, $dlarray)):
								$getList.= " hiddenstructure ";
							else:
								$getList.= " shownstructure ";
							endif;
						endif;
					endif;
					$getList.= " level-".$mpfacts['lvl']." ";
					$getList.= "\">";
					
					if ($outputtype=="structure"):
						
						// avaiable options
						$getList.= "<li class=\"tablecell two\">";
						// show sublist
						if (!($mpfacts['ext'])):
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"showSub(".intval($gms_data['set'][$gmsres]['mid']).", ".intval($gms_data['set'][$gmsres]['level']).");\">";
						endif;
						$getList.= "<span id=\"showspan_".intval($gms_data['set'][$gmsres]['mid'])."\" class=\"bubblemessage ";
						if ($mpfacts['sub']):
							$getList.= " orange ";
						endif;
						if ($mpfacts['ext']):
							$getList.= " disabled ";
						endif;
//						if ($mpfacts['rest'] || (($mpfacts['int'] || $mpfacts['ext'] || $mpfacts['forw']) && !($mpfacts['sub']))):
//							$getList.= " disabled ";
//						endif;
						$getList.= "\" ";
						if ($mpfacts['sub']):
							$getList.= " title='".returnIntLang('bubble showsub icondesc', false)."' ";
						elseif ($mpfacts['ext']):
							$getList.= " title='".returnIntLang('bubble externlink icondesc', false)."' ";
						elseif ($mpfacts['int']):
							$getList.= " title='".returnIntLang('bubble internlink icondesc', false)."' ";
						elseif ($mpfacts['forw']):
							$getList.= " title='".returnIntLang('bubble forwarder icondesc', false)."' ";
						else:
							$getList.= " title='".returnIntLang('bubble showsub nosub icondesc', false)."' ";
						endif;
						$getList.= ">";
						if ($mpfacts['sub']):
							$getList.= returnIntLang('bubble showsub', false);
						elseif ($mpfacts['ext']):
							$getList.= returnIntLang('bubble externlink', false);
						elseif ($mpfacts['int']):
							$getList.= returnIntLang('bubble internlink', false);
						elseif ($mpfacts['forw']):
							$getList.= returnIntLang('bubble forwarder', false);
						else:
							$getList.= returnIntLang('bubble showsub', false);
						endif;
						$getList.= "</span>";
						if (!($mpfacts['ext'])):
							$getList.= "</a>";
						endif;
						
						if ($mpfacts['drag']):
							// dragable
							if (!(key_exists(0, $_SESSION['structuremidlist'])) || intval($gms_data['set'][$gmsres]['mid'])!=intval($_SESSION['structuremidlist'][0])):
								if (array_key_exists('useiconfont', $_SESSION['wspvars']) && $_SESSION['wspvars']['useiconfont']==1):
									$getList.= " <span class=\"icon orange handle\" id=\"handle_".intval($gms_data['set'][$gmsres]['mid'])."\">".returnIntLang('icon move', false)."</span> ";
								else:
									$getList.= " <span class=\"bubblemessage orange handle\" id=\"handle_".intval($gms_data['set'][$gmsres]['mid'])."\" title='".returnIntLang('bubble move structure icondesc', false)."'>".returnIntLang('bubble move', false)."</span> ";
								endif;
							endif;
							
							$getList.= " <a style=\"cursor: pointer;\" onclick=\"editMenupoint(".intval($gms_data['set'][$gmsres]['mid']).")\" title='".returnIntLang('bubble edit structure icondesc', false)."'><span class=\"bubblemessage orange\">".returnIntLang('bubble edit', false)."</span></a>";
							
							// menupoint actions
							if ($mpfacts['act']):
								// delete menupoint
								$getList.= " <a onclick=\"confirmDelete(".intval(mysql_result($gms_res, $gmsres, 'mid')).",'".str_replace("\"", "’", $mpfacts['sd'])."');\">";
								$getList.= "<span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span>";
								$getList.= "</a>\n";
								// duplicate menupoint
								$getList.= " <a onclick=\"confirmClone(".intval(intval($gms_data['set'][$gmsres]['mid'])).",'".$mpfacts['sd']."');\">";
								$getList.= "<span class=\"bubblemessage orange\">".returnIntLang('bubble clone', false)."</span>";
								$getList.= "</a>\n";
								// ad submenupoint to THIS menupoint
								$getList.= " <a onclick=\"addCreateSub(".mysql_result($gms_res, $gmsres, 'mid').")\">";
								if (array_key_exists('useiconfont', $_SESSION['wspvars']) && $_SESSION['wspvars']['useiconfont']==1):
									$getList.= "<span class=\"icon orange\">".returnIntLang('icon addsubmenu', false)."</span>";
								else:
									$getList.= "<span class=\"bubblemessage orange\">".returnIntLang('bubble addsubmenu', false)."</span>";
								endif;
								$getList.= "</a>\n";
								// change visibility
								if (intval(mysql_result($gms_res, $gmsres, "visibility"))==0):
									$getList.= " <a id=\"acv_".intval($gms_data['set'][$gmsres]['mid'])."\" onclick=\"return confirmVisibility(".intval($gms_data['set'][$gmsres]['mid']).");\">";
									$getList.= "<span class=\"bubblemessage red\">".returnIntLang('bubble show', false)."</span>";
									$getList.= "</a>\n";
								else:
									if (is_array($dlarray) && in_array($showlang, $dlarray)):
										$getList.= " <a id=\"acv_" . intval(intval($gms_data['set'][$gmsres]['mid'])) . "\" onclick=\"return confirmVisibility(".intval($gms_data['set'][$gmsres]['mid']).");\">";
										$getList.= "<span class=\"bubblemessage red\">".returnIntLang('bubble show', false)."</span>";
										$getList.= "</a>\n";
									else:
										$getList.= " <a id=\"acv_" . intval(intval($gms_data['set'][$gmsres]['mid'])) . "\" onclick=\"confirmVisibility(".intval($gms_data['set'][$gmsres]['mid']).");\">";
										$getList.= "<span class=\"bubblemessage green\">".returnIntLang('bubble hide', false)."</span>";
										$getList.= "</a>\n";
									endif;
								endif;
							endif;

							$getList.= "</li>";
							// end of options
							
							// description and edit-click-area
							$getList.= "<li class=\"tablecell three\">";
							$getList.= "<span id=\"\" class=\"levelclass\"></span>";
							$getList.= "<a style=\"cursor: pointer;\" onclick=\"editMenupoint(".intval($gms_data['set'][$gmsres]['mid']).")\">".$mpfacts['sd']."</a>";
							// development
							if ($_SESSION['wspvars']['devcontent']):
								$getList.= " [mid".intval($gms_data['set'][$gmsres]['mid'])."]";
							endif;
							// gathering information about the menupoint for the helpbox
							$templatedesc = "";
							if (intval(mysql_result($gms_res, $gmsres, "templates_id"))==0):
								$templatedesc.= "^ ";
							endif;
							$tmplid = getTemplateID(intval($gms_data['set'][$gmsres]['mid']));
							$tplname_sql = "SELECT `name` FROM `templates` WHERE `id` = ".$tmplid;
							$tplname_res = mysql_query($tplname_sql);
							if ($tplname_res):
								$tplname_num = mysql_num_rows($tplname_res);
							endif;
							if ($tplname_num>0):
								$templatedesc.= mysql_result($tplname_res, 0);
							else:
								// else status main template
								$templatedesc.= returnIntLang('str undefined', false);
							endif;
							
							$status = 'extern';
							$status = 'forwarding';
							$status = 'structure';
							
							if (intval(mysql_result($gms_res, $gmsres, "changetime"))>10000): $changedate = date("Y-m-d H:i:s", mysql_result($gms_res, $gmsres, "changetime")); else: $changedate = returnIntLang('structure lastchange not set', false); endif;
							
							// infobox
							$getList.= " ".helpText(
								returnIntLang('str menutypestat', false).': '.
								returnIntLang("structure menutypestat ".$status, false).'<br />'.
								returnIntLang("str filename", false).': '.mysql_result($gms_res, $gmsres, "filename").'<br />'.
								returnIntLang("str template", false).': '.$templatedesc.'<br />'.
								returnIntLang("structure lastchange", false).': '.$changedate.'<br />'.
								returnIntLang("structure mid", false).': '.intval($gms_data['set'][$gmsres]['mid'])
								, false)." ";
							
							// lockpage vs. bindcontentview
							
							if ((isset($_SESSION['wspvars']['bindcontentview']) && intval($_SESSION['wspvars']['bindcontentview'])==1) && intval(mysql_result($gms_res, $gmsres, "lockpage"))==1): $getList.= " <span class=\"bubblemessage\">".returnIntLang('structure bubble contentlock2', false)."</span> "; elseif (intval(mysql_result($gms_res, $gmsres, "lockpage"))==1): $getList.= " <span class=\"bubblemessage\">".returnIntLang('structure bubble contentlock1', false)."</span> "; endif;
							
							// is index file
							if (mysql_result($gms_res, $gmsres, "isindex")) $getList.= " <span class=\"bubblemessage green\">".returnIntLang('structure bubble indexpage', false)."</span> ";
							// has user limit
							if (mysql_result($gms_res, $gmsres, "login")==1) $getList.= " <span class=\"bubblemessage blue\">".returnIntLang('structure bubble locked', false)."</span> ";
							// has images
							if (mysql_result($gms_res, $gmsres, "imageon")!='' || mysql_result($gms_res, $gmsres, "imageoff")!='' || mysql_result($gms_res, $gmsres, "imageakt")!='' || mysql_result($gms_res, $gmsres, "imageclick")!='') $getList.= " <span class=\"bubblemessage blue\">".returnIntLang('structure bubble imginfo', false)."</span> ";
							// has time limit
							if (trim(mysql_result($gms_res, $gmsres, "showtime"))!='' || trim(mysql_result($gms_res, $gmsres, "weekday"))!=0) $getList.= " <span class=\"bubblemessage blue\">".returnIntLang('structure bubble time', false)."</span> ";
							
							$getList.= "</li>";
	
						elseif ($_SESSION['wspvars']['rights']['sitestructure']==3 || ($_SESSION['wspvars']['rights']['sitestructure']==4 && in_array(intval($gms_data['set'][$gmsres]['mid']), $_SESSION['wspvars']['rights']['sitestructure_array']))):
							// description and edit-click-area
							$getList.= "<li class=\"tablecell three\">";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a style=\"cursor: pointer;\" onclick=\"setEdit(".intval($gms_data['set'][$gmsres]['mid']).")\">".$mpfacts['sd']."</a></span><span class=\"handle\"></span>";
							$getList.= "</li>\n";
						elseif ($_SESSION['wspvars']['rights']['sitestructure']==7 && in_array(intval($gms_data['set'][$gmsres]['mid']), $_SESSION['structuremidlist'])):
							// description and edit-click-area
							$getList.= "<li class=\"tablecell three\">";
							$getList.= "<span style=\"float: left; margin-right: 4px;\"><a href=\"".$_SERVER['PHP_SELF']."?action=edit&mid=".intval($gms_data['set'][$gmsres]['mid'])."\">".$mpfacts['sd']."</a></span><span class=\"handle\"></span>";
							$getList.= "</li>\n";
						else:
							// description
							$getList.= "<li class=\"tablecell three\">";
							$getList.= "<span style=\"float: left; margin-right: 4px;\">".$mpfacts['sd']."</span><span class=\"handle\" style=\"display: none;\"></span>";
							$getList.= "</li>\n";
						endif;

						if ($tplopt_num>0):
							// preview button
							$getList.= "<li class=\"tablecell one alignright\">";
							
							for ($t=0; $t<$tplopt_num; $t++):
								$getList.= "<div class='chstpl mid".intval($gms_data['set'][$gmsres]['mid'])." tpl".intval(mysql_result($tplopt_res, $t, 'id'))."' ";
								if (intval($tmplid)!=intval(mysql_result($tplopt_res, $t, 'id'))): $getList.= " style='display: none;' "; endif;
								$getList.= "><a href=\"showpreview.php?previewid=".intval($gms_data['set'][$gmsres]['mid'])."&previewlang=".$_SESSION['wspvars']['workspacelang']."&previewtpl=".intval(mysql_result($tplopt_res, $t, 'id'))."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('structure bubble preview', false)."</span></a>&nbsp;</div>";
							endfor;
							
							$getList.= "</li>";
							// template chooser
							$getList.= "<li class=\"tablecell two\">";
							$getList.= "<select id='usepreviewtemplate-".intval($gms_data['set'][$gmsres]['mid'])."' class='one full' onchange='showPreviewTemplate(".intval($gms_data['set'][$gmsres]['mid']).", this.value)'>";
							$getList.= "<option value='".$tmplid."'>".returnIntLang('structure choose preview template')."</option>";
							for ($t=0; $t<$tplopt_num; $t++):
								$getList.= "<option value='".intval(mysql_result($tplopt_res, $t, 'id'))."' ";
								if (intval($tmplid)==intval(mysql_result($tplopt_res, $t, 'id'))): $getList.= " selected='selected' "; endif;
								$getList.= ">".mysql_result($tplopt_res, $t, 'name')."</option>";	
							endfor;
							$getList.= "</select></li>";
						else:
							$getList.= "<li class=\"tablecell three alignright\"><a href=\"showpreview.php?previewid=".intval($gms_data['set'][$gmsres]['mid'])."&previewlang=".$_SESSION['wspvars']['workspacelang']."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('publisher bubble preview', false)."</span></a>&nbsp;</li>";
						endif;
						
					elseif ($outputtype=="contents"):
						
						if (!($mpfacts['ext'] && !($mpfacts['sub'])) && !($mpfacts['forw'] && !($mpfacts['sub'])) && !($mpfacts['int'] && !($mpfacts['sub']))): // show only lines not forwarding and NO subs

							$getList.= "<li class=\"tablecell two\">";
							// show sublist
							if ($mpfacts['sub']):
								$getList.= "<a style=\"cursor: pointer;\" onclick=\"showSub(".intval($gms_data['set'][$gmsres]['mid']).", ".intval($gms_data['set'][$gmsres]['level']).");\">";
							endif;
							
							$getList.= "<span id=\"showspan_".intval($gms_data['set'][$gmsres]['mid'])."\" class=\"bubblemessage ";
							if ($mpfacts['sub']):
								$getList.= " orange ";
							endif;
							if (!($mpfacts['sub'])):
								$getList.= " disabled ";
							endif;
							$getList.= "\">";
							if ($mpfacts['sub']):
								$getList.= returnIntLang('bubble showsub', false);
							elseif ($mpfacts['int']):
								$getList.= returnIntLang('bubble internlink', false);
							else:
								$getList.= returnIntLang('bubble showsub', false);
							endif;
							$getList.= "</span>";
							if ($mpfacts['sub']):
								$getList.= "</a>";
							endif;
							
							// output count content areas and count contents
							// get content area count
							$realtemp = getTemplateID(intval(intval($gms_data['set'][$gmsres]['mid'])));
							$templatevars = getTemplateVars($realtemp);
							// get content count
							$contents = 0;
							foreach ($templatevars['contentareas'] AS $cavalue):
								$ccount_sql = "SELECT `cid` FROM `content` WHERE `trash` = 0 AND `mid` = ".intval($gms_data['set'][$gmsres]['mid'])." AND `content_area` = ".intval($cavalue);
								$ccount_sql.= " AND (`content_lang` = '".mysql_real_escape_string(trim($_SESSION['wspvars']['workspacelang']))."' OR `content_lang` = '')";
								$ccount_res = mysql_query($ccount_sql);
								if ($ccount_res):
									$contents = $contents + mysql_num_rows($ccount_res);
								endif;
							endforeach;
						
							// if contentdisplay is allowed
							if (count($templatevars['contentareas'])>0 && $mpfacts['edit']):
								if ($mpfacts['con'] && !($mpfacts['int'])):
									$getList.= " <a style=\"cursor: pointer;\" onclick=\"showContent(".intval($gms_data['set'][$gmsres]['mid']).", ".intval($gms_data['set'][$gmsres]['level']).");\"><span class=\"bubblemessage blue\">".returnIntLang('bubble showcontent', false)."</span></a>";
								else:
									$getList.= " <span class=\"bubblemessage blue disabled\">".returnIntLang('bubble showcontent', false)."</span>";
								endif;
							else:
								$getList.= " <span class=\"bubblemessage hidden\">".returnIntLang('bubble showcontent', false)."</span>";
							endif;
							// if adding is allowed 
							if (count($templatevars['contentareas'])>0 && $mpfacts['edit']):
								if ($mpfacts['con'] && ($_SESSION['wspvars']['rights']['contents']!=3 && $_SESSION['wspvars']['rights']['contents']!=4) && !($mpfacts['int'])):
									$getList.= " <a onclick=\"addContent(".intval($gms_data['set'][$gmsres]['mid']).", 1);\"><span class=\"bubblemessage orange\">".returnIntLang('bubble addcontent', false)."</span></a> \n";
								endif;
							else:
								$getList.= " <span class=\"bubblemessage hidden\">".returnIntLang('bubble addcontent', false)."</span>";
							endif;
							// preview content
							$getList.= " <a href=\"showpreview.php?previewid=".intval($gms_data['set'][$gmsres]['mid'])."&previewlang=".$_SESSION['wspvars']['workspacelang']."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('publisher bubble preview', false)."</span></a>";
							
							$getList.= "</li>";
						
							// output page name
							$getList.= "<li class=\"tablecell four\" id=\"contenthead_".intval($gms_data['set'][$gmsres]['mid'])."\">";
							$getList.= $mpfacts['sd'];
							// development
							if ($_SESSION['wspvars']['devcontent']):
								$getList.= " [mid".intval($gms_data['set'][$gmsres]['mid'])."]";
							endif;
							$getList.= "</li>\n";
							
							if ($mpfacts['con'] && !($mpfacts['int']) && $mpfacts['edit']):
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
							else:
								$getList.= "<li class=\"tablecell two\">&nbsp;</li>";
							endif;
							
						endif; // end show only lines not forwarding and NO subs
						
					endif;
					
					$getList.= "</ul>";
					
					if ($outputtype=="contents"):
						$getList.= "<ul id=\"ulc_".intval($gms_data['set'][$gmsres]['mid'])."\" class=\"tablelist sub sortable\" style=\"margin: 0px; width: 100%; padding: 0px; display: none;\"></ul>";
						$getList.= "<ul id=\"ul_".intval($gms_data['set'][$gmsres]['mid'])."\" class=\"tablelist sub sortable\" style=\"";
						if (is_array($showmidpath) && in_array(intval($gms_data['set'][$gmsres]['mid']), $showmidpath)):
							$getList.= "display: block;";
						else:
							$getList.= "display: none;";
						endif;
						$getList.= "margin: 0px; width: 100%; padding: 0px; \" >";
					else:
						$getList.= "<ul id=\"ul_".intval($gms_data['set'][$gmsres]['mid'])."\" class=\"tablelist sub sortable\" style=\"";
						if (is_array($showmidpath) && in_array(intval($gms_data['set'][$gmsres]['mid']), $showmidpath)):
							$getList.= "display: block;";
						else:
							$getList.= "display: none;";
						endif;
						$getList.= "margin: 0px; width: 100%; padding: 0px; \" >";
					endif;
					if (is_array($showmidpath) && in_array(intval($gms_data['set'][$gmsres]['mid']), $showmidpath)):
						$getList.= getjMenuStructure(intval($gms_data['set'][$gmsres]['mid']), $aSelectIDs, $op, $showmidpath, $outputtype, $showlang);
					endif;
					
					$getList.= "</ul>\n";
					$getList.= "</li>\n";
				endif;
			endfor;
			if ($parent!=0 && $outputtype!="publisher"):
				$getList.= "<li class=\"structurelistspacer\"></li>";
			endif;
		endif;
		
		return $getList;
		} // getjMenuStructure
endif;

if (!(function_exists('getPublisherStructure'))):
	/* call from publisher.php as admin: getPublisherStructure(0, array, array, lang, selector, search) */
	function getPublisherStructure($parent = 0, $aSelectIDs = array(), $showmidpath = array(), $publishlang = 'de', $select = 'all', $search = '') {
		// define empty output var
		$getList = '';
		// get all menu information to parent connector
		$gms_sql = "SELECT * FROM `menu` WHERE `trash` = 0 AND `connected` = ".intval($parent)." ORDER BY `position`";
		$gms_res = mysql_query($gms_sql);
		$gms_num = 0; if ($gms_res): $gms_num = mysql_num_rows($gms_res); endif;
		if ($gms_num>0):
			for ($gmsres=0; $gmsres<$gms_num; $gmsres++):
				// get informationen about submenupoints
				$gmsub_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0 AND `connected` = ".mysql_result($gms_res, $gmsres, "mid")." ORDER BY `position`";
				$gmsub_res = mysql_query($gmsub_sql);
				$gmsub_num = 0; if ($gmsub_res): $gmsub_num = mysql_num_rows($gmsub_res); endif;
				// building array with facts
				$mpfacts = array(
					'edit' => true, /* edit|able */
					'forw' => false, /* forw|arding */
					'ext' => false, /* ext|ernlink */
					'sub' => false, /* sub|structure */
					'sd' => '', /* title information */
					'con' => false, /* con|tent editing allowed */
					'que' => false, /* in |que|ue */
					);
				
				// get editable stat
				if (mysql_result($gms_res, $gmsres, "editable")!=1):
					$mpfacts['edit'] = false;
				endif;
				/* get type of menupoint to set right icon */
				if (mysql_result($gms_res, $gmsres, "forwardmenu")==1):
					$mpfacts['forw'] = true;
				endif;
				if (trim(mysql_result($gms_res, $gmsres, "offlink")!="")):
					$mpfacts['ext'] = true;
				endif;
				/* set information about subpoints */
				if ($gmsub_num>0):
					$mpfacts['sub'] = true;
				endif;
				// 2. contentinformationen sammeln
				
				// 3. namen und templateinformationen
				$mpfacts['sd'] = stripslashes(mysql_result($gms_res, $gmsres, "description"));
				if (trim($mpfacts['sd'])==""):
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				endif;
				$sdarray = unserializeBroken(mysql_result($gms_res, $gmsres, "langdescription"));
				if (is_array($sdarray) && array_key_exists($publishlang, $sdarray) && trim($sdarray[$publishlang])!="" && count($_SESSION['wspvars']['lang'])>1):
					$mpfacts['sd'] = trim(stripslashes($sdarray[$publishlang]));
					if ($mpfacts['sd']==stripslashes(mysql_result($gms_res, $gmsres, "description")) && $publishlang!='de'):
						$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [int]";
					endif;
				elseif ($publishlang!='de'):
					$mpfacts['sd'] = stripslashes($mpfacts['sd'])." [".$_SESSION['wspvars']['wspbaselang']."]";
				endif;
				if (trim($mpfacts['sd'])==""):
					$mpfacts['sd'] = "-- ".returnIntLang('structure no name defined', false)." --";
				endif;
				
				$q_sql = "SELECT `id` FROM `wspqueue` WHERE `param` = ".intval(mysql_result($gms_res, $gmsres, "mid"))." AND `done` = 0";
				$q_res = mysql_query($q_sql);
				$q_num = 0; if ($q_res): $q_num = mysql_num_rows($q_res); endif;
				if ($q_num>0): $mpfacts['que'] = true; endif;
				
				// start output
				// show only menu with subpoints, if no defined extern forwarding or editable
				if ((!($mpfacts['edit']) && !($mpfacts['sub'])) || ($mpfacts['ext'] && !($mpfacts['sub'])) || ($mpfacts['forw'] && !($mpfacts['sub']))):
					// not editable & no subs
					// extern link & no subs
					// forwarding & no subs
				else:
					// show only menupoints with selected publish attribute
					// case: all
					// case: only publish required
					// case: 
					$selected = true;
					if ($select=='publishrequired' && intval(mysql_result($gms_res, $gmsres, "contentchanged"))==0):
						$selected = false;	
					endif;
					if ($select=='publishcontent' && (intval(mysql_result($gms_res, $gmsres, "contentchanged"))!=2 && intval(mysql_result($gms_res, $gmsres, "contentchanged"))!=3 && intval(mysql_result($gms_res, $gmsres, "contentchanged"))!=5)):
						$selected = false;
					endif;
					if ($select=='publishstructure' && (intval(mysql_result($gms_res, $gmsres, "contentchanged"))!=1)):
						$selected = false;
					endif;
					
					if ($selected && trim($search)!=''):
						$selected = false;
						if (stristr(trim(mysql_result($gms_res, $gmsres, "description")), trim($search))):
							$selected = true;
						elseif (stristr(trim(returnPath(mysql_result($gms_res, $gmsres, 'mid'), 2)), trim($search))):
							$selected = true;
						endif;
					endif;
					
					if ($selected):
					
						$getList.= "<tr>";
						$getList.= "<td class=\"tablecell two ";
						if ($mpfacts['edit']):
							$getList.= " itempublish ";
							// adding information publishing required
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))>0): $getList.= " publishrequired "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==1 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))>2): $getList.= " publishstructure "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==2 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==3 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==5): $getList.= " publishcontent "; endif;
							// adding information in queue
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
							
							$getList.= " item".intval(mysql_result($gms_res, $gmsres, "mid"))."\" ";
							if ($_SESSION['wspvars']['rights']['publisher']<100):
								$getList.= "onclick=\"selectPublish('item".intval(mysql_result($gms_res, $gmsres, "mid"))."','item'); return true;\"";
							endif;
							$getList.= "><span class=\"levelclass\"></span>";
						else:
							$getList.= " locked ";
							$getList.= "\"><span class=\"levelclass\"></span>";
						endif;
						$getList.= $mpfacts['sd'];
						if (intval(mysql_result($gms_res, $gmsres, "isindex"))==1): $getList.= " <span class=\"bubblemessage green\">ROOT</span>"; endif;
						if ($mpfacts['edit']):
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==1):
								$getList.= " <span class=\"bubblemessage\">MENU</span>";
							elseif (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==2):
								$getList.= " <span class=\"bubblemessage\">CNT</span>";
							elseif (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==3):
								$getList.= " <span class=\"bubblemessage\">MENU+CNT</span>";
							elseif (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==4):
								$getList.= " <span class=\"bubblemessage\">STCR</span>";
							elseif (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==5):
								$getList.= " <span class=\"bubblemessage\">STCR+CNT</span>";
							endif;
						else:
							$getList.= " <span class=\"bubblemessage red\">LOCKED</span>";
						endif;
						
						// output some more facts ?!?!!?
						$getList.= "</td>\n";
						if ($mpfacts['edit']):
							$getList.= "<td class=\"tablecell four itempublish ";
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))>0): $getList.= " publishrequired "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==1 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))>2): $getList.= " publishstructure "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==2 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==3 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==5): $getList.= " publishcontent "; endif;
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
							$getList.= " item".intval(mysql_result($gms_res, $gmsres, "mid"))."\" ";
							if ($_SESSION['wspvars']['rights']['publisher']<100):
								$getList.= "onclick=\"selectPublish('item".intval(mysql_result($gms_res, $gmsres, "mid"))."','item'); return true;\"";
							endif;
							$getList.= ">";
						else:
							$getList.= "<td class=\"tablecell four locked\">";
						endif;
						// display filenames
						$getName = "";
						if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1):
							if (intval(mysql_result($gms_res, $gmsres, "isindex"))==1 && intval(mysql_result($gms_res, $gmsres, "connected"))==0):
								$getName.= "/";
							elseif (intval(mysql_result($gms_res, $gmsres, "isindex"))==1):
								if (intval($gms_data['set'][$gmsres]['level'])==1):
									$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(mysql_result($gms_res, $gmsres, 'mid'), 1)."/"));
								else:
									$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(mysql_result($gms_res, $gmsres, 'mid'), 1)."/"));
								endif;
							else:
								$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(mysql_result($gms_res, $gmsres, 'mid'), 1)."/"));
							endif;
						else:
							if (intval(mysql_result($gms_res, $gmsres, "isindex"))==1 && intval($gms_data['set'][$gmsres]['level'])==1):
								$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(mysql_result($gms_res, $gmsres, 'mid'), 0)."/index.php"));
							else:
								$getName.= str_replace("//", "/", str_replace("//", "/", returnPath(mysql_result($gms_res, $gmsres, 'mid'), 2)));
							endif;
						endif;
						// shorten toooooo long names ...
						if (strlen(trim($getName))>60):
							$getNameEx = explode("/", $getName);
							if (is_array($getNameEx) && count($getNameEx)>1):
								foreach($getNameEx AS $gNk => $gNv):
									if (strlen($gNv)>10 && $gNk<(count($getNameEx)-2)):
										$getNameEx[$gNk] = substr($gNv,0,8)."...";
									endif;
								endforeach;
								$getName = implode("/", $getNameEx);
							endif;
						endif;
						$getList.= $getName;					
						$getList.= "</td>";
						// show date & time of last succesfull publishing action
						$getList.= "<td class=\"tablecell one ";
						if ($mpfacts['edit']):
							$getList.= " itempublish ";
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))>0): $getList.= " publishrequired "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==1 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))>2): $getList.= " publishstructure "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==2 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==3 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==5): $getList.= " publishcontent "; endif;
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
						else:
							$getList.= " locked ";
						endif;
						$getList.= " item".intval(mysql_result($gms_res, $gmsres, "mid"))."\" ";
						if ($_SESSION['wspvars']['rights']['publisher']<100):
							$getList.= " onclick=\"selectPublish('item".intval(mysql_result($gms_res, $gmsres, "mid"))."','item'); return true;\" ";
						endif;
						$getList.= ">";
						$lp_sql = "SELECT `done` FROM `wspqueue` WHERE `param` = ".intval(mysql_result($gms_res, $gmsres, "mid"))." AND `done` != 0 ORDER BY `done` DESC LIMIT 0,1";
						$lp_res = mysql_query($lp_sql);
						$lp_num = 0; if ($lp_res): $lp_num = mysql_num_rows($lp_res); endif;
						if ($lp_num>0): 
							if (date('Y-m-d', mysql_result($lp_res, 0, 'done'))==date('Y-m-d')):
								$getList.= date(returnIntLang('format time', false), mysql_result($lp_res, 0, 'done'));
							else:
								$getList.= date(returnIntLang('format date', false), mysql_result($lp_res, 0, 'done'));
							endif;
						else:
							$getList.= "-";	
						endif;
						$getList.= "</td>";
						
						$getList.= "<td class=\"tablecell one ";
						if ($mpfacts['edit']):
							$getList.= " itempublish ";
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))>0): $getList.= " publishrequired "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==1 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))>2): $getList.= " publishstructure "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==2 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==3 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==5): $getList.= " publishcontent "; endif;
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
						else:
							$getList.= " locked ";
						endif;
						$getList.= " item".intval(mysql_result($gms_res, $gmsres, "mid"))."\" >";
						if ($mpfacts['edit'] && $_SESSION['wspvars']['rights']['publisher']<100):
							$getList.= "<input type=\"checkbox\" class=\"itempublishbox";
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))>0): $getList.= " publishrequired "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==1 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))>2): $getList.= " publishstructure "; endif;
							if (intval(mysql_result($gms_res, $gmsres, "contentchanged"))==2 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==3 || intval(mysql_result($gms_res, $gmsres, "contentchanged"))==5): $getList.= " publishcontent "; endif;
							if ($mpfacts['que']): $getList.= " inqueue "; endif;
							$getList.= "\" ";
							if ($mpfacts['que']): $getList.= " disabled=\"disabled\" readonly=\"readonly\" "; endif;
							$getList.= " name=\"publishitem[]\" value=\"".intval(mysql_result($gms_res, $gmsres, "mid"))."\" ";
							$getList.= " id=\"checkitem".intval(mysql_result($gms_res, $gmsres, "mid"))."\" ";
							$getList.= " onchange=\"selectPublish('item".intval(mysql_result($gms_res, $gmsres, "mid"))."','item'); return true;\" />";
							// output checkbox ..
							$getList.= " <a href=\"showpreview.php?previewid=".intval(mysql_result($gms_res, $gmsres, "mid"))."&previewlang=".$publishlang."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('publisher bubble preview', false)."</span></a> &nbsp;";
						elseif ($mpfacts['edit'] && $_SESSION['wspvars']['rights']['publisher']>100):
							$getList.= " <a href=\"showpreview.php?previewid=".intval(mysql_result($gms_res, $gmsres, "mid"))."&previewlang=".$publishlang."\" target=\"_blank\"><span class=\"bubblemessage green\">".returnIntLang('publisher bubble preview', false)."</span></a> &nbsp;";
						endif;
						$getList.= "</td>\n";
						$getList.= "</tr>\n";
					endif;
					$getList.= getPublisherStructure(mysql_result($gms_res, $gmsres, "mid"), $aSelectIDs, $showmidpath, $publishlang, $select, $search);
				endif;
			endfor;
		endif;
		
		return $getList;
		} // getPublisherStructure
endif;

// delete menupoint with existing submenu
if (!(function_exists('deleteMenuItems'))):
	function deleteMenuItems($mid, $ftp) {
		// dateien des zu loeschenden menuepunktes loeschen
		$sql = "SELECT `mid`,`filename` FROM `menu` WHERE `connected`=".$mid;
		$rs = mysql_query($sql);
		if (mysql_num_rows($rs) > 0):
			while ($row = mysql_fetch_assoc($rs)):
				deleteMenuItems($row['mid'], $ftp);
			endwhile;
		endif;
		ftpDeleteFile(returnPath($mid, FTP_BASE, 2));
		if (mysql_num_rows($rs) > 0) ftpDeleteDir(returnPath($mid, FTP_BASE, 1));
		$GLOBALS['errormsg'] = "";
		$GLOBALS['deleteMenuItems'][$mid] = $mid;
		}
endif;

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
		$menulevel_data = doSQL($menulevel_sql);
		
//		$menulevel_res = mysql_query($menulevel_sql);
//		$menulevel_num = 0; if ($menulevel_res): $menulevel_num = mysql_num_rows($menulevel_res); endif;
		
		if ($menulevel_data['num']>0) {
			$spacer = ""; for ($i=0; $i<$spaces; $i++): $spacer .= "&nbsp;"; endfor;
			$i = 1;
			foreach ($menulevel_data['set'] AS $mlk => $mlv):
				$menuItem = "";
				$getsubs = true;
				// get original description
				$menudescription = trim($mlv['description']);
				// get workspace language
				$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
				if (intval(count($worklang['languages']['shortcut']))>1):
					$langdescription = unserializeBroken(trim($mlv['langdescription']));
					if (array_key_exists($_SESSION['wspvars']['workspacelang'], $langdescription) && trim($langdescription[$_SESSION['wspvars']['workspacelang']])!=''):
						$menudescription = trim($langdescription[$_SESSION['wspvars']['workspacelang']]);
					else:
						$menudescription = trim($mlv['description'])." [".$_SESSION['wspvars']['lang'][0][0]."]";
					endif;
				endif;
				if (intval($modi)==0):
					// gmlTable
					// find all subselects
					$sql = "SELECT `mid` FROM `menu` WHERE trash != 1 AND `connected` = ".intval($mlv['mid']);
					$data = doSQL($sql);
					if ($data['num']>0):
						for ($rsub=0;$rsub<$data['num'];$rsub++):
							$fillmid = $row['mid'];
							$fillres = mysql_result($rsHasSubs,$rsub);
							$GLOBALS['system']['menustructure'][$fillmid][$fillres] = $rsub;
						endfor;
					endif;
				
				endif;
				
			endforeach;
			
			while ($row = mysql_fetch_array($menulevel_res)):
				
				if ($modi == gmlTable) {
	
					$sql = "SELECT `mid` FROM `menu` WHERE trash != 1 AND `connected` = '".$row['mid']."'";
					$rsHasSubs = mysql_query($sql);
					if ($rsHasSubs):
						if (mysql_num_rows($rsHasSubs)>0):
							for ($rsub=0;$rsub<mysql_num_rows($rsHasSubs);$rsub++):
								$fillmid = $row['mid'];
								$fillres = mysql_result($rsHasSubs,$rsub);
								$GLOBALS['system']['menustructure'][$fillmid][$fillres] = $rsub;
							endfor;
						endif;
					endif;
	
					// hier muessen irgendwie die unterpunkte festgestellt werden,
					// damit diese durchnummeriert und per id angesprochen werden koennen
	
					if ($GLOBALS['system']['menustructure']['connected'][($row['mid'])]==$row['connected']):
						$qmid = $row['connected'];
						$qres = $row['mid'];
						$menuItem .= "<div id=\"sub_".$row['connected']."_".$GLOBALS['system']['menustructure'][$qmid][$qres]."\" style=\"clear: both; width: 98%; height: 20px; display: none;\" onMouseOver=\"this.style.background = '#EEEEEE';\" onMouseOut=\"this.style.background = '#E2E7EF';\">";
					else:
						$menuItem .= "<div id=\"mid_".$row['mid']."\" style=\"clear: both; width: 98%; height: 20px;\" onMouseOver=\"this.style.background = '#EEEEEE';\" onMouseOut=\"this.style.background = '#E2E7EF';\">";
					endif;
	
					$editable = (($op == 'no') || (($op == 'some') && (in_array($row['mid'], $aSelectIDs))));
	
					$menuItem .= "<div style=\"float: left;\">";
	
					$menuItem .= str_replace("&nbsp;&nbsp;&nbsp;","<img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/no.gif\" width=\"12\" height=\"12\" border=\"0\" style=\"float: left; margin-right: 2px;\" />",$spacer);
	
					if ($editable) {
						$menuItem .= "<a href=\"menueditdetails.php?usevar=$usevar&op=editdetails&mid=".$row['mid']."\" title=\"Men&uuml;punkt bearbeiten\" onmouseover=\"status='Men&uuml;punkt bearbeiten'; return true;\" onmouseout=\"status=''; return true;\" style=\"float: left; margin-right: 5px;\">";
					}	// if
					if ($row['visibility'] != "yes") {
						$menuItem .= "<span style=\"text-decoration: line-through;\">";
					}	// if
					$menuItem .= $menudescription;
					if ($row['visibility'] != "yes") {
						$menuItem .= "</span>";
					}	// if
					if ($editable) {
						$menuItem .= "</a>";
					}	// if
	
					if ($menulevel_num >$i && $editable):
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=".$_SESSION['wspvars']['usevar']."&mid=".$row['mid']."&op=posdn\" title=\"Men&uuml;punkt eine Position nach unten verschieben\" onmouseover=\"window.status='Men&uuml;punkt eine Position nach unten verschieben'; return true;\" onmouseout=\"window.status=''; return true;\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/btn_down.gif\" alt=\"&#x2193;\" width=\"10\" height=\"10\" border=\"1\" style=\"float: left; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a>";
					else:
						$menuItem .= "<img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/btn_down.gif\" alt=\"&#x2193;\" width=\"10\" height=\"10\" border=\"1\" style=\"float: left; margin-right: 2px; opacity: 0.2; filter: alpha(opacity: 20); font-size: 9px; text-align: center; line-height: 8px;\" />";
					endif;
	
					if (($i > 1) && ($editable)) {
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=$usevar&mid=".$row['mid']."&op=posup\" title=\"Men&uuml;punkt eine Position nach oben verschieben\" onmouseover=\"window.status='Men&uuml;punkt eine Position nach oben verschieben'; return true;\" onmouseout=\"window.status=''; return true;\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/btn_up.gif\" alt=\"&#x2191;\" width=\"10\" height=\"10\" border=\"1\" style=\"float: left; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a>";
					}
					else {
						$menuItem .= "<img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/btn_up.gif\" alt=\"&#x2191;\" width=\"10\" height=\"10\" border=\"1\" style=\"float: left; margin-right: 2px; opacity: 0.2; filter: alpha(opacity: 20); font-size: 9px; text-align: center; line-height: 8px;\" />";
					}	// if
	
					if (mysql_num_rows($rsHasSubs)>0):
						$menuItem .= "<span id=\"".$row['mid']."_close\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
						$menuItem .= "document.getElementById('".$row['mid']."_open').style.display = 'block';";
						$menuItem .= " document.getElementById('".$row['mid']."_close').style.display = 'none';";
	
						for ($sRow=0;$sRow<mysql_num_rows($rsHasSubs);$sRow++):
							$menuItem .= " document.getElementById('sub_".$row['mid']."_".$sRow."').style.display = 'block';";
						endfor;
	
						$menuItem .= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/expand.gif\" alt=\"+\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"float: left; margin-top: 0px; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";
						$menuItem .= "<span id=\"".$row['mid']."_open\" style=\"display: none;\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
						$menuItem .= "document.getElementById('".$row['mid']."_open').style.display = 'none';";
						$menuItem .= " document.getElementById('".$row['mid']."_close').style.display = 'block';";
	
						for ($sRow=0;$sRow<mysql_num_rows($rsHasSubs);$sRow++):
							$menuItem .= " document.getElementById('sub_".$row['mid']."_".$sRow."').style.display = 'none';";
						endfor;
	
						$menuItem .= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/collapse.gif\" alt=\"-\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"float: left; margin-top: 0px; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";
		
						for ($rsub=0;$rsub<mysql_num_rows($rsHasSubs);$rsub++):
							$fillres = mysql_result($rsHasSubs,$rsub);
							$GLOBALS['system']['menustructure']['connected'][$fillres] = $row['mid'];
						endfor;
		
					endif;
	
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
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=$usevar&mid=".$row['mid']."&op=delete\" onclick=\"return confirmDelete();\" title=\"Men&uuml;punkt mit allen Untermen&uuml;punkten und Content-Elementen l&ouml;schen\" onmouseover=\"status='Men&uuml;punkt mit allen Untermen&uuml;punkten und Content-Elementen l&ouml;schen'; return true;\" onmouseout=\"status=''; return true;\" class=\"red\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/delete.gif\" alt=\"X\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #C0000D; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						
						// klonen
						//
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=".$_SESSION['wspvars']['usevar']."&mid=".$row['mid']."&op=cloneit\" title=\"Men&uuml;punkt klonen\" onmouseover=\"status='Men&uuml;punkt klonen'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/clone.gif\" alt=\"x2\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						
						// verschieben
						//
						$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=".$_SESSION['wspvars']['usevar']."&mid=".$row['mid']."&op=repos\" title=\"Men&uuml;punkt beliebig verschieben\" onmouseover=\"status='Men&uuml;punkt beliebig verschieben'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/resort.gif\" alt=\" &#x2195;\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						
						// einfuegen
						//
						$menuItem .= "<a href=\"#\" onclick=\"document.getElementById('newmenuitem').focus();SelectBox.selectOptionByValue( ".$row['mid']." );\" title=\"Submen&uuml;punkt zu DIESEM Men&uuml;punkt hinzuf&uuml;gen\" onmouseover=\"status='Submen&uuml;punkt zu DIESEM Men&uuml;punkt hinzuf&uuml;gen'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/createsub.gif\" alt=\"=\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						
						// sichtbarkeit
						//
						if ($row['visibility'] == 1):
							$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=$usevar&mid=".$row['mid']."&op=hide\" onclick=\"return confirmHide();\" title=\"Men&uuml;punkt verstecken\" onmouseover=\"status='Men&uuml;punkt verstecken'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/hide.gif\" alt=\"-\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						else:
							$menuItem .= "<a href=\"".$_SERVER['PHP_SELF']."?usevar=".$usevar."&mid=".$row['mid']."&op=show\" onclick=\"return confirmShow();\" title=\"Men&uuml;punkt anzeigen\" onmouseover=\"status='Men&uuml;punkt anzeigen'; return true;\" onmouseout=\"status=''; return true;\" class=\"orange\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/view.gif\" alt=\"O\" width=\"16\" height=\"16\" border=\"1\" style=\"float: left; margin-left: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 16px;\"></a>";
						endif;
	
						$menuItem .= "</div>";
					}	// if
	
					$menuItem .= "</div>\n";
				}
				else if ($modi == gmlSortableList) {
					
					if ($i == 1):
						$menuItem = "\n<ul id=\"ulholder".$parent."\" style=\"list-style-type: square;\">\n";
					endif;
					
					$sql = "SELECT `mid` FROM `menu` WHERE trash != 1 AND `connected` = '".$row['mid']."'";
					$rsHasSubs = mysql_query($sql);
	
					if (mysql_num_rows($rsHasSubs)>0):
						for ($rsub=0;$rsub<mysql_num_rows($rsHasSubs);$rsub++):
							$fillmid = $row['mid'];
							$fillres = mysql_result($rsHasSubs,$rsub);
							$GLOBALS['system']['menustructure'][$fillmid][$fillres] = $rsub;
						endfor;
					endif;
	
					// hier muessen irgendwie die unterpunkte festgestellt werden,
					// damit diese durchnummeriert und per id angesprochen werden koennen
	
					if ($GLOBALS['system']['menustructure']['connected'][($row['mid'])]==$row['connected']):
						$qmid = $row['connected'];
						$qres = $row['mid'];
						$menuItem .= "\n<li id=\"sub_".$row['connected']."_".$GLOBALS['system']['menustructure'][$qmid][$qres]."\" style=\"clear: both; width: 98%; height: 20px; display: none;\" onMouseOver=\"this.style.background = '#EEEEEE';\" onMouseOut=\"this.style.background = '#E2E7EF';\">";
					else:
						$menuItem .= "\n<li id=\"mid_".$row['mid']."\" style=\"clear: both; width: 98%; height: 20px;\" onMouseOver=\"this.style.background = '#EEEEEE';\" onMouseOut=\"this.style.background = '#E2E7EF';\">";
					endif;
	
					$editable = (($op == 'no') || (($op == 'some') && (in_array($row['mid'], $aSelectIDs))));
	
					$menuItem .= "\n<div style=\"float: left;\">";
	
					$menuItem .= str_replace("&nbsp;&nbsp;&nbsp;","<img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/no.gif\" width=\"12\" height=\"12\" border=\"0\" style=\"float: left; margin-right: 2px;\" />",$spacer);
	
					if ($editable):
						$menuItem .= "<a href=\"menueditdetails.php?usevar=$usevar&op=editdetails&mid=".$row['mid']."\" title=\"Men&uuml;punkt bearbeiten\" onmouseover=\"status='Men&uuml;punkt bearbeiten'; return true;\" onmouseout=\"status=''; return true;\" style=\"float: left; margin-right: 5px;\">";
					endif;
					if ($row['visibility'] != "yes"):
						$menuItem .= "<span style=\"text-decoration: line-through;\">";
					endif;
					
					$menuItem .= $menudescription;
					if ($row['visibility'] != "yes"):
						$menuItem .= "</span>";
					endif;
					if ($editable):
						$menuItem .= "</a>";
					endif;
	
					if (mysql_num_rows($rsHasSubs)>0):
					$menuItem .= "<span id=\"".$row['mid']."_close\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
					$menuItem .= "document.getElementById('".$row['mid']."_open').style.display = 'block';";
					$menuItem .= " document.getElementById('".$row['mid']."_close').style.display = 'none';";
					for ($sRow=0;$sRow<mysql_num_rows($rsHasSubs);$sRow++):
					$menuItem .= " document.getElementById('sub_".$row['mid']."_".$sRow."').style.display = 'block';";
					endfor;
					$menuItem .= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/expand.gif\" alt=\"+\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"float: left; margin-top: 0px; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";
					$menuItem .= "<span id=\"".$row['mid']."_open\" style=\"display: none;\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
					$menuItem .= "document.getElementById('".$row['mid']."_open').style.display = 'none';";
					$menuItem .= " document.getElementById('".$row['mid']."_close').style.display = 'block';";
					for ($sRow=0;$sRow<mysql_num_rows($rsHasSubs);$sRow++):
					$menuItem .= " document.getElementById('sub_".$row['mid']."_".$sRow."').style.display = 'none';";
					endfor;
					$menuItem .= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/collapse.gif\" alt=\"-\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"float: left; margin-top: 0px; margin-right: 2px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";
	
					for ($rsub=0;$rsub<mysql_num_rows($rsHasSubs);$rsub++):
						$fillres = mysql_result($rsHasSubs,$rsub);
						$GLOBALS['system']['menustructure']['connected'][$fillres] = $row['mid'];
					endfor;
	
					endif;
	
					$menuItem .= "</div>";
					
					$menuItem .= "</li>\n";
				}
				else if ($modi == gmlSelect) {
					if (is_array($op)):
						if (count($op)>0):
							if (in_array($row['mid'], $op)):
								if (($gmlVisible==0 && $row['visibility']==0) || $gmlVisible==1 || ($gmlVisible==2 && $row['visibility']==1)):
									$menuItem = "<option value=\"".$row['mid']."\"";
									if (!(array_search($row['mid'], $aSelectIDs) === false)) {
										$menuItem .= " selected=\"selected\"";
									}	// if
									$menuItem .= ">".$spacer.$menudescription."</option>";
								endif;			
							endif;
						else:
							if (($gmlVisible==0 && $row['visibility']==0) || $gmlVisible==1 || ($gmlVisible==2 && $row['visibility']==1)):
								$menuItem = "<option value=\"".$row['mid']."\"";
								if (!(array_search($row['mid'], $aSelectIDs) === false)) {
									$menuItem .= " selected=\"selected\"";
								}	// if
								$menuItem .= ">".$spacer.$menudescription."</option>";
							endif;
						endif;
					else:
						if (($gmlVisible==0 && $row['visibility']==0) || $gmlVisible==1 || ($gmlVisible==2 && $row['visibility']==1)):
							if (!($allowselfselect) && $row['mid']==intval($_SESSION['wspvars']['editmenuid'])):
								// find upper
								$topm_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($row['mid']);
								$topm_res = mysql_query($topm_sql);
								$topm_num = 0; if ($topm_res): $topm_num = mysql_num_rows($topm_res); endif;
								$topmid = 0; if ($topm_num>0): $topmid = intval(mysql_result($topm_res,0,'connected')); endif;
								$menuItem = "<option value=\"".$topmid."\" disabled=\"disabled\">".$spacer.$menudescription." - ".returnIntLang('structure edit property can not be set to itself', false)."</option>";
								$getsubs = false;
							else:
								$menuItem = "<option value=\"".$row['mid']."\"";
								if (!(array_search($row['mid'], $aSelectIDs) === false)) {
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
					$menuItem .= "<li id=\"repos".$row['mid']."\" style=\"list-style-type:none;\"><a href=\"#\" onclick=\"makeRepos(".$row['mid']."); return false;\">".$menudescription."</a></li>\n";
				}
				else if ($modi == gmlFieldset) {
					$menuItem = "<fieldset>";
					$menuItem.= "<legend>".$menudescription."</legend>";
	
				}
				else if ($modi == gmlContent) {
					
					$substructure_sql = "SELECT `mid` FROM `menu` WHERE `trash` != 1 AND `connected` = '".intval($row['mid'])."'";
					$rsHasSubs = mysql_query($substructure_sql);
					if ($rsHasSubs):
						$substructure_num = mysql_num_rows($rsHasSubs);
					endif;
									
					if ($substructure_num>0):
						for ($rsub=0;$rsub<$substructure_num;$rsub++):
							$fillmid = $row['mid'];
							$fillres = mysql_result($rsHasSubs,$rsub);
							$GLOBALS['system']['menustructure'][$fillmid][$fillres] = $rsub;
						endfor;
					endif;
					
					$this_description = $menudescription;
					$this_template = $row['templates_id'];
					
					$maintemp_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'";
					$maintemp_res = mysql_query($maintemp_sql);
					if ($maintemp_res):
						$maintemp_num = mysql_num_rows($maintemp_res);
					endif;
					if ($maintemp_num>0):
						$maintemp = mysql_result($maintemp_res,0);
					else:
						$maintemp = 0;
					endif;
					
					if ($this_template==0):
						returnReverseStructure($row['mid']);
						foreach ($GLOBALS['midpath'] AS $value):
							$toptemplate_sql = "SELECT `templates_id` FROM `menu` WHERE `trash` != 1 `mid` = ".intval($value);
							$toptemplate_res = mysql_query($toptemplate_sql);
							$toptemplate_num = mysql_num_rows($toptemplate_res);
							
							if ($toptemplate_num>0 && $this_template==0):
								$this_template = mysql_result($toptemplate_res, 0);
							endif;
						endforeach;
					endif;
					
					if ($this_template==0):
						$this_template = $maintemp;
					endif;
					
					$tempinfo_sql = "SELECT `template` FROM `templates` WHERE `id` = ".$this_template;
					$tempinfo_res = mysql_query($tempinfo_sql);
					$tempinfo_num = mysql_num_rows($tempinfo_res);
					
					if ($tempinfo_num>0):
						$template_content = mysql_result($tempinfo_res,0);
						unset($c);
						$contentareas = array();
						while (str_replace("[%CONTENTVAR".$c."%]","[%CONTENT%]",$template_content)!=$template_content):
							$c++;
							$contentareas[] = $c;
						endwhile;
					endif;
					
					$this_visibility = $row['visibility'];
	
					if ($GLOBALS['system']['menustructure']['connected'][($row['mid'])]==$row['connected']):
						$qmid = $row['connected'];
						$qres = $row['mid'];
						$menuItem .= "<div id=\"sub_".$row['connected']."_".$GLOBALS['system']['menustructure'][$qmid][$qres]."\" style=\"clear: both; width: 100%; display: none;\">";
					else:
						$menuItem .= "<div id=\"mid_".$row['mid']."\" style=\"clear: both; width: 100%;\">";
					endif;
					
					$editable = ($op == 'no' || ($op == 'some' && (in_array($row['mid'], $aSelectIDs))));
					
					$fullsubstructure = getSiteStructure($row['mid'], '', gmlMIDArray, array(), '', '');
					if (!(is_array($fullsubstructure))):
						$fullsubstructure = array();
					endif;
					$checkforactivesub = array_intersect ($aSelectIDs, $fullsubstructure);
					
					if (!($editable) && (count($checkforactivesub)==0)):
						//
						// no action to NOT display content table
						//
					else:
					
					$menuItem .= "<table    >\n\t<tr style=\"line-height: 16px;\">\n\t\t";
					
					$menuItem .= str_replace("&nbsp;&nbsp;&nbsp;","<td><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/no.gif\" width=\"12\" height=\"12\" border=\"0\" style=\"float: left; margin: 0px 3px;\" /></td>\n\t\t",$spacer);
					
					if ($substructure_num>0):
						$structure = "<span id=\"".$row['mid']."_close\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
						$structure.= "document.getElementById('".$row['mid']."_open').style.display = 'inline';";
						$structure.= " document.getElementById('".$row['mid']."_close').style.display = 'none';";
						for ($sRow=0;$sRow<$substructure_num;$sRow++):
							$structure.= " document.getElementById('sub_".$row['mid']."_".$sRow."').style.display = 'block';";
						endfor;
						$structure.= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/structure.gif\" alt=\"S\" title=\"Sitestruktur einblenden\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"margin: 0px 3px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";
						$structure.= "<span id=\"".$row['mid']."_open\" style=\"display: none;\"><a href=\"javascript:;\" class=\"orange\" onClick=\"";
						$structure.= "document.getElementById('".$row['mid']."_open').style.display = 'none';";
						$structure.= " document.getElementById('".$row['mid']."_close').style.display = 'inline';";
						for ($sRow=0;$sRow<$substructure_num;$sRow++):
							$structure.= " document.getElementById('sub_".$row['mid']."_".$sRow."').style.display = 'none';";
						endfor;
						$structure.= "\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/structure.gif\" alt=\"S\" title=\"Sitestruktur ausblenden\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"margin: 0px 3px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></span>";
	
						for ($rsub=0;$rsub<$substructure_num;$rsub++):
							$fillres = mysql_result($rsHasSubs,$rsub);
							$GLOBALS['system']['menustructure']['connected'][$fillres] = $row['mid'];
						endfor;
					else:
						$structure = "<img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/structure.gif\" alt=\"S\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"margin: 0px 3px; border: 1px solid #E8994B; font-size: 9px; opacity: 0.2; text-align: center; line-height: 8px;\" />";
					endif;
					
					$menuItem .= "<td>".$structure."</td>";
					
					// attach exisiting contents to menupoint
					
					$sql = "SELECT COUNT(`cid`) FROM `content` WHERE `mid` = ".$row['mid'];
					$rsContentCount = mysql_query($sql);
					
					if (mysql_db_name($rsContentCount, 0, 0) > 0):
						if ($editable):
							$menuItem .= "<td><a href=\"#\" onclick=\"changeVisibleContent(".$row['mid']."); return false;\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/content.gif\" alt=\"C\" title=\"Inhalte anzeigen\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"margin: 0px 3px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></td>";
						else:
							$menuItem .= "<td><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/content.gif\" alt=\"C\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"margin: 0px 3px; border: 1px solid #E8994B; opacity: 0.2; font-size: 9px; text-align: center; line-height: 8px;\" /></td>";
						endif;
					else:
						$menuItem .= "<td><a href=\"#\" onclick=\"changeVisibleContent(".$row['mid']."); return false;\"><img src=\"/".$_SESSION['wspvars']['wspbasedir']."/media/screen/content.gif\" alt=\"C\" title=\"Inhalte anzeigen\" width=\"10\" height=\"10\" border=\"1\" align=\"absbottom\" style=\"margin: 0px 3px; border: 1px solid #E8994B; font-size: 9px; text-align: center; line-height: 8px;\" /></a></td>";
					endif;
					$menuItem .= "<td id=\"sr".$row['mid']."\" nowrap style=\"padding: 3px;\">".$this_description;
					if ($row['visibility'] == "yes"):
						$menuItem .= " (&#149;)";
					endif;
					$menuItem .= "</td>\n\t\t\t<td width=\"100%\">&nbsp;--</td>\n\t\t</tr>\n\t</table>\n";
					endif;
					$menuItem .= "<div style=\"display: none;\" id=\"mc".$row['mid']."\">\n";
					
				}	// 
				else if ($modi == 4) {
					if ($spaces=="-1"):
					$spacer = "";
					endif;
					$menuItem = "<option value=\"".$row['mid']."\"";
					if(is_array($aSelectIDs)):
						if (is_int(array_search(intval($row['mid']), $aSelectIDs))) {
							$menuItem .= " selected=\"selected\"";
						}	// if
					endif;
					$menuItem .= ">".$spacer.$menudescription."</option>\n";
				}
				else if ($modi == 12) {
					if ($spaces=="-1"):
					$spacer = "";
					endif;
					$menuItem = "<option value=\"".$row['mid']."\"";
					if(is_array($aSelectIDs)):
						if (is_int(array_search(intval($row['mid']), $aSelectIDs))) {
							$menuItem .= " selected=\"selected\"";
						}	// if
					endif;
					$menuItem .= ">".$spacer.$menudescription."</option>";
				}
				else if ($modi == gmlSelectwoID) {
					if (!in_array($row['mid'],$aSelectIDs)):
					$menuItem = "<option value=\"".$row['mid']."\"";
					if (is_int(array_search(intval($row['mid']), $aSelectIDs))):
					$menuItem .= " selected=\"selected\"";
					endif;
					$menuItem .= ">".$spacer.$menudescription."</option>";
					else:
					$getsubs = false;
					endif;
				}
				else if ($modi == gmlPublisher) {
					$editable = (($op == 'no') || (($op == 'some') && (in_array($row['mid'], $aSelectIDs))));
					if($row['editable']==0):
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
					if ($row['contentchanged']==1):
						$menuItem .= " publishcontent";
					elseif ($row['contentchanged']==2):
						$menuItem .= " publishmenu";
					endif;
					$menuItem .= "\" style=\"position: relative;";
					if ($editable && $blocked):
						$menuItem .= " cursor: pointer;\" id=\"m".$row['mid']."\"";
//						$menuItem .= " cursor: pointer;\" id=\"m".$row['mid']."\" onclick=\"selectItem('m".$row['mid']."'); return true;\"";
					else:
						$menuItem .= " color: #cccccc;\"";
					endif;
					$menuItem .= "><span id=\"m".$row['mid']."text\" style=\"width: 99%; float: left;\"";
					if ($editable && $blocked):
						$menuItem .= " onclick=\"selectItem('m".$row['mid']."'); return true;\"";
					endif;
					$menuItem .= ">";
//					$menuItem .= "><span id=\"m".$row['mid']."text\" style=\"width: 99%; float: left;\">";
					$menuItem .= $spacer.$menudescription."</span>";
					if (intval($row['contentchanged'])>0):
						$menuItem .= "";
					endif;
					$menuItem .= "&nbsp;</li>";
					}
				else if ($modi == gmlPreview) {
					$editable = (($op == 'no') || (($op == 'some') && (in_array($row['mid'], $aSelectIDs))));
					$menuItem .= "<div class=\""; 
					if ($row['contentchanged']==1):
						$menuItem .= "publishrequired";
					else:
						$menuItem .= "nopublish";
					endif;
					$menuItem .= "\" style=\"position: relative;";
					if ($editable):
						$menuItem .= " cursor: pointer;\" id=\"m".$row['mid']."\"><a href=\"javascript:;\" onClick=\"document.getElementById('previewid').value = '".$row['mid']."'; document.getElementById('previewform').submit(); return false;\" target=\"_blank\">";
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
					echo getContents($row['mid'], ($spaces+3), 0, null, $editable, $contentareas);
					echo "</div>";
				endif;
				
				if ($spaces=="-1"):
					getMenuLevel($row['mid'], $spaces, $modi, $aSelectIDs, $op, $gmlVisible, $allowselfselect);
				else:
					echo "";
					if (!isset($getsubs)):
						getMenuLevel($row['mid'], $spaces+3, $modi, $aSelectIDs, $op, $gmlVisible, $allowselfselect);
						if ($modi == gmlContent):
							echo "</div>\n";
						endif;
					elseif ($getsubs):
						getMenuLevel($row['mid'], $spaces+3, $modi, $aSelectIDs, $op, $gmlVisible, $allowselfselect);
					endif;
				endif;
				$i++;
			endwhile;
			
			if ($modi == 2 || $modi == gmlSortableList):
				if ($i > 1):
					echo "</ul>\n";
				endif;
			endif;
		}	// if
	}	// getMenuLevel()
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
		$skelhdl = opendir($skel);
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
	}	// copySkel()
endif;

/**
 * ein komplettes Verzeichnis incl. aller Untervzeichnisse und enthaltener Dateien lï¿½schen
 * deaktivierung durch s.haendler am 6.2.2008 wegen des fehlers vom 5.2.2008
 */
if (!(function_exists('delTree'))):
	function delTree($dir) {
		$nondel = array(
			DOCUMENT_ROOT,
			DOCUMENT_ROOT.'/data',
			DOCUMENT_ROOT.'/media',
			DOCUMENT_ROOT.'/'.WSP_DIR
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
	$fullpath = DOCUMENT_ROOT."/".WSP_DIR."/tmp/".$_SESSION['wspvars']['usevar']."/";
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
if (!(function_exists('createDirFTP'))) {
	function createDirFTP($path, $chmod = NULL, $ftpcon = false) {
		$path = str_replace("//", "/", str_replace(FTP_BASE, "/", $path));
		$ftp = false;
        if (substr($path, 0, 1)=="/"):
			// $_SESSION['wspvars']['usevar'];
			$path = substr($path, 1);
		endif;
		$pathstructure = explode("/", $path);
		$fullpath = str_replace("//", "/", FTP_BASE."/");
		if ($ftpcon===false) {
            $ftp = doFTP();
        }
        if ($ftp!==false) {
            foreach ($pathstructure AS $pathvalue) {
                if (trim($pathvalue)!="") {
                    $fullpath = cleanPath($fullpath.'/'.$pathvalue."/");
                    @ftp_mkdir($ftp, $fullpath);
                }
            }
            if (intval($chmod)>0) {
                ftp_chmod($ftp, $fullpath, intval($chmod));
            }
            if ($ftpcon===false) {
                ftp_close($ftp);
            }
            return true;
        }
        else {
            return false;
        }
    }
}

if (!(function_exists('showHeadlineRow'))) {
    function showHeadlineRow($hldesc = '', $hlvalue = '', $htdesc = '', $htvalue = '') {
        if (trim($hldesc)=='') { $hldesc = returnIntLang('str headline', false); }
        if (trim($htdesc)=='') { $htdesc = returnIntLang('str headtype', false); }
        $text = '<div class="row">';
        $text.= '<div class="col-md-3">';
        $text.= '<p>'.$hldesc.'</p>';
        $text.= '</div>';
        $text.= '<div class="col-md-3">';
        $text.= '<p><input name="field[headline]" id="field_headline" type="text" value="'.prepareTextField($hlvalue).'" class="form-control" /></p>';
        $text.= '</div>';
        $text.= '<div class="col-md-3">';
        $text.= '<p>'.$htdesc.'</p>';
        $text.= '</div>';
        $text.= '<div class="col-md-3">';
        $text.= '<p><select name="field[headtype]" id="field_headtype" size="1" class="form-control">';
        $text.= '<option value="h1" '.(($htvalue=="h1")?'selected="selected"':'').'>Überschrift 1 (H1)</option>';
        $text.= '<option value="h2" '.(($htvalue=="h2")?'selected="selected"':'').'>Überschrift 1 (H2)</option>';
        $text.= '<option value="h3" '.(($htvalue=="h3")?'selected="selected"':'').'>Überschrift 3 (H3)</option>';
        $text.= '<option value="h4" '.(($htvalue=="h4")?'selected="selected"':'').'>Überschrift 4 (H4)</option>';
        $text.= '<option value="h5" '.(($htvalue=="h5")?'selected="selected"':'').'>Überschrift 5 (H5)</option>';
        $text.= '<option value="h6" '.(($htvalue=="h6")?'selected="selected"':'').'>Überschrift 6 (H6)</option>';
        $text.= '<option value="p" '.(($htvalue=="p")?'selected="selected"':'').'>Absatz (p)</option>';
        $text.= '</select></p>';
        $text.= '</div>';
        $text.= '</div>';
        return $text;
    }
}

if (!(function_exists('createDynamicMenu'))):
function createDynamicMenu($topMID = 0, $pParam = array(), $cleanSub = true, $returnMID = false) {
    // topMID = mid to connect dynamic menu
    // pParam = param array with 
    //          required:
    //          fromtable source,
    //          filename source,
    //          description source;
    //          optional:
    //          WHERE clause
    //          ORDER BY clause
    //          additional facts:
    //          visibility
    //          lockpage
    //          mobileexclude
    // cleanSub = if true, the folder will be cleaned first 
    // returnMID = if true, set of created mids will be returned
    // storeChanges first gets param and will be reset if actions are done
    $storeChanges = array($topMID, $pParam, $cleanSub, $returnMID);
    if (isset($pParam['filename']) && trim($pParam['filename'])!='' && 
        isset($pParam['description']) && trim($pParam['description'])!='' && 
        isset($pParam['fromtable']) && trim($pParam['fromtable'])!='') {
        // create base statement
        $plugincontent_sql = "SELECT `".trim($pParam['filename'])."` AS filename, `".trim($pParam['description'])."` AS description FROM `".trim($pParam['fromtable'])."`";
        // create WHERE statement
        if (isset($pParam['where']) && is_array($pParam['where']) && count($pParam['where'])>0) {
            $statement = array();
            $combine = array();
            foreach ($pParam['where'] AS $ppk => $ppv) {
                if (trim($ppv)!='') {
                    $statement[] = "`".$pParam['where'][$ppk]."` ".(isset($pParam['whereopt'][$ppk])?$pParam['whereopt'][$ppk]:'=')." '".$pParam['whereval'][$ppk]."'";
                    $combine[] = $pParam['wherecombine'][$ppk];
                }
            }
            $fullstatement = array(); $combo = false;
            foreach ($combine AS $ck => $cv) {
                if (strpos($cv, 'COMBO')!==false && $combo===false) {
                    $fullstatement[] = '(';
                    $combo = true;
                }
                $fullstatement[] = $statement[$ck];
                if (strpos($cv, 'COMBO')===false && $combo===true) {
                    $fullstatement[] = ')';
                    $combo = false;
                }
                $fullstatement[] = str_replace("COMBO", "", $cv);
            }
            $plugincontent_sql.= " WHERE (".implode(" ", $fullstatement).")";
        }
        // create ORDER BY 
        if (trim($pParam['order'])!='') {
            $plugincontent_sql.= " ORDER BY `".trim($pParam['order'])."`";
            if (trim($pParam['orderdir'])!='') {
                $plugincontent_sql.= " ".trim($pParam['orderdir']);
            }
        }
        // get result
        $plugincontent_res = doSQL($plugincontent_sql);
        // run the plugin with found contents 
        if ($plugincontent_res['num']>0) {
            // set store changes var to optionally return if returnMID = true
            $storeChanges = array('delete' => array(), 'update' => array(), 'create' => array(), 'posvalues' => $plugincontent_res['num'], 'updatecontent' => array());
            // setup empty array to delete menupoints
            $deletemid = array();
            // get ALL connected menupoints
            $existmid_sql = "SELECT `mid` FROM `menu` WHERE `editable` = 7 AND `trash` = 0 AND `connected` = ".intval($topMID);
            $existmid = getResultSQL($existmid_sql);
            // prepare an array with all mid that just have to be updated
            $updatemid = array();
            foreach ($plugincontent_res['set'] AS $pcrk => $pcrv) {
                // get THE connected menupoint
                $conmid_sql = "SELECT `mid` FROM `menu` WHERE `editable` = 7 AND `trash` = 0 AND `description` = '".escapeSQL(setUTF8(trim($pcrv['description'])))."' AND `filename` = '".escapeSQL(urltext(trim($pcrv['filename'])))."' AND `connected` = ".intval($topMID);
                if (doResultSQL($conmid_sql)) {
                    $updatemid[] = intval(doResultSQL($conmid_sql));
                    $storeChanges['updatecontent'][] = array(
                        'mid' => intval(doResultSQL($conmid_sql)),
                        'filename' => urltext(trim($pcrv['filename'])),
                        'description' => setUTF8(trim($pcrv['description'])),
                    );
                    // remove key-value-pair from $plugincontent_res['set'] to prevent new INSERT
                    // BUT update the entry later with new param
                    unset($plugincontent_res['set'][$pcrk]);
                    // everything what stays in $plugincontent_res['set'] is NEW content
                }
            }
            // compare all connected menupoints with updatetable menupoints
            if (is_array($existmid)) {
                // and put all not updateable menupoints to deletion queue 
                $deletemid = array_diff($existmid, $updatemid);
            }
            // remove all unrelated menupoints
            foreach ($deletemid AS $dmk => $dmv) {
                if ($cleanSub===true && $returnMID===false) {
                    $dynamiccleanup_sql = "UPDATE `menu` SET `trash` = 1 WHERE `editable` = 7 AND `trash` = 0 AND `connected` = ".intval($topMID)." AND `mid` = ".intval($dmv);
                    doSQL($dynamiccleanup_sql);
                    $dynamiccleanup_sql = "UPDATE `content` SET `trash` = 1 WHERE `mid` = ".intval($dmv);
                    doSQL($dynamiccleanup_sql);
                }
                // store information in $storeChanges
                $storeChanges['delete'][] = intval($dmv);
            }
            // get some additional information about the connected MID that must be used in dynamic menus
            $mid_sql = "SELECT `level`, `addscript`, `addcss`, `addclass`, `weekday`, `showtime`, `login`, `logincontrol` FROM `menu` WHERE `mid` = ".intval($topMID);
            $mid_res = doSQL($mid_sql);
            // do the REAL action by creating or updating menupoints ...
            if ($mid_res['num']>0) {
                // set additional information to variables
                $level = intval($mid_res['set'][0]['level']);
                $tmpaddscript = $mid_res['set'][0]['addscript'];
                $tmpaddcss = $mid_res['set'][0]['addcss'];
                $tmpuseclass = $mid_res['set'][0]['addclass'];
                $tmpweekday = intval($mid_res['set'][0]['weekday']);
                $tmpshowtime = $mid_res['set'][0]['showtime'];
                $tmplogin = $mid_res['set'][0]['login'];
                $tmplogincontrol = $mid_res['set'][0]['logincontrol'];
                // update all dynamic menupoints that are already OR still connected
                foreach ($updatemid AS $umk => $umv) {
                    if ($returnMID===false) {
                        $dynamicudpate_sql = "UPDATE `menu` SET `visibility` = ".(isset($_POST['pluginconfig']['visibility'])?intval($_POST['pluginconfig']['visibility']):0).", `level` = ".(intval($level)+1).", `connected` = ".intval($topMID).", `contentchanged` = 1, `changetime` = ".time().", `addscript` = '".$tmpaddscript."', `addcss` = '".$tmpaddcss."', `addclass` = '".$tmpuseclass."', `mobileexclude` = ".(isset($_POST['pluginconfig']['mobileexclude'])?intval($_POST['pluginconfig']['mobileexclude']):'').", `weekday` = ".$tmpweekday.", `showtime` = '".$tmpshowtime."', `login` = ".$tmplogin.", `logincontrol` = '".$tmplogincontrol."', `lockpage` = ".(isset($_POST['pluginconfig']['lockpage'])?intval($_POST['pluginconfig']['lockpage']):0).", `structurechanged` = ".time().", `menuchangetime` = ".time().", `lastchange` = ".time()." WHERE `mid` = ".intval($umv);
                        $dynamicudpate_res = doSQL($dynamicudpate_sql);
                    }
                    // store information in $storeChanges
                    $storeChanges['update'][] = intval($umv);
                }
                // insert additional dynamic menupoints to menu
                foreach ($plugincontent_res['set'] AS $pcrk => $pcrv) {
                    if ($returnMID===false) {
                        $dynamicinsert_sql = "INSERT INTO `menu` SET `editable` = 7, `position` = ".intval($pcrk).", `visibility` = ".(isset($_POST['pluginconfig']['visibility'])?intval($_POST['pluginconfig']['visibility']):0).", `description` = '".escapeSQL(setUTF8(trim($pcrv['description'])))."', `templates_id` = 0, `level` = ".(intval($level)+1).", `connected` = ".intval($topMID).", `filename` = '".escapeSQL(urltext(trim($pcrv['filename'])))."', `contentchanged` = 1, `changetime` = ".time().", `addscript` = '".$tmpaddscript."', `addcss` = '".$tmpaddcss."', `addclass` = '".$tmpuseclass."', `isindex` = 0, `trash` = 0, `mobileexclude` = ".(isset($_POST['pluginconfig']['mobileexclude'])?intval($_POST['pluginconfig']['mobileexclude']):0).", `weekday` = ".$tmpweekday.", `showtime` = '".$tmpshowtime."', `login` = ".$tmplogin.", `logincontrol` = '".$tmplogincontrol."', `lockpage` = ".(isset($_POST['pluginconfig']['lockpage'])?intval($_POST['pluginconfig']['lockpage']):0).", `structurechanged` = ".time().", `menuchangetime` = ".time().", `lastchange` = ".time();
                        $dynamicinsert_res = doSQL($dynamicinsert_sql);
                        if ($dynamicinsert_res['inf']>0) {
                            // store information in $storeChanges['create']
                            $storeChanges['create'][] = intval($dynamicinsert_res['inf']);
                            // setup $storeChanges['updatecontent'] to find and setup contents 
                            $storeChanges['updatecontent'][] = array(
                                'mid' => intval($dynamicinsert_res['inf']),
                                'filename' => urltext(trim($pcrv['filename'])),
                                'description' => setUTF8(trim($pcrv['description'])),
                            );
                        }
                    } else {
                        $storeChanges['create'][] = urltext(trim($pcrv['filename']));
                    }
                }
                // do the content insert
                foreach ($storeChanges['updatecontent'] AS $uck => $ucv) {
                    // find contents of dynamic menupoint
                    $dynamiccontent_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($topMID)." AND `trash` = 0 ORDER BY `content_area`, `position`";
                    $dynamiccontent_res = doSQL($dynamiccontent_sql);
                    if ($dynamiccontent_res['num']>0) {
                        // trash all older contents
                        $removecontent_sql = "UPDATE `content` SET `trash` = 1 WHERE `mid` = ".intval($ucv['mid']); 
                        doSQL($removecontent_sql);
                        foreach ($dynamiccontent_res['set'] AS $dcrk => $dcrv) {
                            $dcrv['mid'] = $ucv['mid'];
                            // get dynamic valuefields and fill it with contents
                            $dyncontent = unserializeBroken($dcrv['valuefields']);
                            $valuefields = array();
                            if (is_array($dyncontent) && array_key_exists('isdynamic', $dyncontent)) {
                                foreach ($dyncontent AS $dck => $dcv) {
                                    if ($dck!='isdynamic') {
                                        $valuefields_sql = "SELECT `".str_replace("`", "", $dcv['selectfield'])."` AS value FROM `".str_replace("`", "", $dcv['selecttable'])."` WHERE `".trim($_POST['pluginconfig']['filename'])."` = '".trim($ucv['filename'])."' AND `".trim($_POST['pluginconfig']['description'])."` = '".trim($ucv['description'])."'";
                                        if (trim(str_replace("`", "", $dcv['where']))!='') {
                                            $valuefields_sql.= " AND (".trim($dcv['where']).")";
                                        }
                                        $valuefields[$dck] = doResultSQL($valuefields_sql);
                                    }
                                }
                            }
                            $dcrv['valuefields'] = serialize($valuefields);
                            $insertdata_sql = "INSERT INTO `content` SET `mid` = ".intval($ucv['mid']).", `globalcontent_id` = ".intval($dcrv['globalcontent_id']).", `connected` = 0, `position` = ".intval($dcrv['position']).", `visibility` = ".intval($dcrv['visibility']).", `sid` = ".intval($dcrv['sid']).", `valuefields` = '".escapeSQL($dcrv['valuefields'])."', `lastchange` = ".time().", `interpreter_guid` = '".escapeSQL($dcrv['interpreter_guid'])."', `content_area` = ".intval($dcrv['content_area']).", `content_lang` = '".escapeSQL($dcrv['content_lang'])."', `showday` = ".intval($dcrv['showday']).", `showtime` = '".escapeSQL($dcrv['showtime'])."', `container` = ".intval($dcrv['container']).", `containerclass` = '".escapeSQL($dcrv['containerclass'])."', `trash` = 0, `containeranchor` = '".escapeSQL($dcrv['containeranchor'])."', `displayclass` = ".intval($dcrv['displayclass']).", `login` = ".intval($dcrv['login']).", `logincontrol` = '".escapeSQL($dcrv['logincontrol'])."', `uid` = ".intval(intval($_SESSION['wspvars']['userid'])).", `description` = 'dynamiccontent'";
                            $insertdata_res = doSQL($insertdata_sql);
                            $storeChanges['insertedcontentid'][] = $insertdata_res['inf'];
                        }
                    }
                }
            }
        }
        // OR RUN the plugin if $cleanSub = true AND remove all submenupoints if no results were found 
        else if ($cleanSub===true) {
            $storeChanges = array('delete' => array(), 'update' => array(), 'create' => array(), 'posvalues' => $plugincontent_res['num'], 'updatecontent' => array());
            $deletemid_sql = "SELECT `mid` FROM `menu` WHERE `editable` = 7 AND `trash` = 0 AND `connected` = ".intval($topMID);
            $deletemid = getResultSQL($deletemid_sql);
            if (is_array($deletemid)) {
                foreach ($deletemid AS $dmk => $dmv) {
                    if ($returnMID===false) {
                        $dynamiccleanup_sql = "UPDATE `menu` SET `trash` = 1 WHERE `editable` = 7 AND `trash` = 0 AND `connected` = ".intval($topMID)." AND `mid` = ".intval($dmv);
                        doSQL($dynamiccleanup_sql);
                        $dynamiccleanup_sql = "UPDATE `content` SET `trash` = 1 WHERE `mid` = ".intval($dmv);
                        doSQL($dynamiccleanup_sql);
                    }
                    // store information in $storeChanges
                    $storeChanges['delete'][] = intval($dmv);
                }
            }
        }
    }
    return $storeChanges;
}
endif;

// is the same function as subPMenu !?!?!?!?!??!?!?!?!??!?!!
if (!(function_exists('subMID'))):
	function subMID($mid) {
		$connected_sql = "SELECT `mid` FROM `menu` WHERE `trash` != 1 AND `connected` = ".intval($mid);
		$connected_res = mysql_query($connected_sql);
		$connected_num = 0;
		if ($connected_res)	$connected_num = mysql_num_rows($connected_res);
		if ($connected_num > 0):
			while ($row = mysql_fetch_assoc($connected_res)):
				$GLOBALS['midlist'][] = intval($row['mid']);
				subMID($row['mid']);
			endwhile;
		endif;
		} //subMID();
endif;

// creates an structured array with all mids relating to given startmid (e.g. 0 => full tree)
if (!(function_exists('returnStructureArray'))) {
	function returnStructureArray($startmid=0, $depth=999, $visarg = false, $recreate = false, $firstrun = true) {
		if (isset($_SESSION['wspvars']['mnu']) && is_array($_SESSION['wspvars']['mnu']) && $recreate===false && $firstrun===true) {
            // return the stored data
            return $_SESSION['wspvars']['mnu'];
        }
        else {
            $vissql = '';
            if ($visarg==false) {
                $vissql = 'AND `visibility` = 0';
            }
            else if ($visarg==true) {
                $vissql = 'AND `visibility` = 1';
            }
            $str_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($startmid)." AND `trash` = 0 ".$vissql." ORDER BY `position` ASC";
            $str_res = doSQL($str_sql);
            if ($str_res['num']>0) {
                // setup every single entry
                foreach($str_res['set'] AS $srsk => $srsv) {
                    $mnu[intval($srsv['mid'])] = NULL;
                }
                if ($depth>1) {
                    foreach($str_res['set'] AS $srsk => $srsv) {
                        $sub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($srsv['mid'])." AND `trash` = 0 ".$vissql." ORDER BY `position` ASC";
                        $sub_res = doSQL($sub_sql);
                        if ($sub_res['num']>0) { 
                            // recreate is set to true because deeper views are only reached if recreate was already set to true. 
                            $mnu[intval($srsv['mid'])] = returnStructureArray(intval($srsv['mid']), ($depth-1), $visarg, true, false);
                        }
                    }
                }
            }
            if ($firstrun===true) {
                $_SESSION['wspvars']['mnu'] = $mnu;
            }
            return $mnu;
        }
    }
}   // returnStructureArray();

if (!(function_exists('returnStructureShow'))):
    //    $datatable » menu-table to be selected as string
    //    $mid » base mid as integer (for full structure use 0)
    //    $showsub » show sub structure as boolean 
    //    $maxlevel » max levels to show as integer (for no limitation use 9999)
    //    $openpath » array of open path to a selected mid
    //    $datatype » list, option
    //    $visible » 0 = no limitations, 1 = only visible, 2 = only visible and NON-forwarding 
    function returnStructureShow($datatable = 'menu', $structure=array(), $showsub = false, $maxlevel = 9999, $openpath = array(), $datatype = 'list', $visible = 0) {
        if (is_array($structure)):
            $item = '';
            if ($datatype=='list'):
                $item = "<ul>";
            endif;
            foreach ($structure AS $sk => $sv) {
                $item.= returnStructureItem($datatable, $sk, false, $maxlevel, $openpath, $datatype, array('visible'=>$visible));
                if (is_array($sv)) {
                    $item.= returnStructureShow($datatable, $sv, $showsub, $maxlevel, $openpath, $datatype, $visible);
                }
            }
            if ($datatype=='list'):
                $item.= "</ul>";
            endif;
            return $item;
        else:
            return false;
        endif;
    }
endif;

// from setup-routine
// creates an array based on database-xml
if (!(function_exists('createDBArrFromDBXML'))) {
    function createDBArrFromDBXML($dbxml='') {
        if (trim($dbxml)=='') {
            return array();
        } else {
            $dbarr = xml_parser_create();
            xml_parse_into_struct($dbarr, $dbxml, $dbval, $dbidx);
            xml_parser_free($dbarr);
            $updtable = array();
            $updtablename = array();
            foreach ($dbval as $dbtables) {
                if ($dbtables['tag']=='TABLENAME') {
                    $updtable[$dbtables['value']] = array();
                    $updtablenametmp = '';
                    $col = 0;
                    foreach ($dbval as $dbk => $dbcols) {
                        if($dbcols['tag']=='TABLENAME') { $updtablenametmp = $dbcols['value']; }
                        if($updtablenametmp==$dbtables['value']) {
                            if($dbcols['tag']=='FIELD') $updtable[$dbtables['value']][$col]['Field'] = (isset($dbcols['value'])?$dbcols['value']:'tmpfield'.$dbk);
                            if($dbcols['tag']=='TYPE') $updtable[$dbtables['value']][$col]['Type'] = (isset($dbcols['value'])?strtolower($dbcols['value']):'text');
                            if($dbcols['tag']=='NULL') $updtable[$dbtables['value']][$col]['Null'] = (isset($dbcols['value'])?$dbcols['value']:'NO');
                            if($dbcols['tag']=='KEY') $updtable[$dbtables['value']][$col]['Key'] = (isset($dbcols['value'])?strtoupper($dbcols['value']):'');
                            if($dbcols['tag']=='DEFAULT') $updtable[$dbtables['value']][$col]['Default'] = (isset($dbcols['value'])?((trim($dbcols['value'])=='NULL')?'NULL':trim(' '.$dbcols['value'])):NULL);
                            if($dbcols['tag']=='EXTRAS') $updtable[$dbtables['value']][$col]['Extra'] = (isset($dbcols['value'])?$dbcols['value']:'');
                        }
                        // find closing column tag and count col to begin a new element
                        if($dbcols['tag']=='COLUMN' && $dbcols['type']=='close') { $col++; }
                    }
                }
            }
            return $updtable;
        }
    }
}
// creates and or updates database-tables
if (!(function_exists('installUpdateDBTable'))) {
    function installUpdateDBTable($dbname, $tablename, $tabledata) {
        // return array(stat(bool), return(string));
        $status = NULL;
        $return = '';
        $sysexists = doResultSQL("SHOW TABLES LIKE '".($tablename)."'");
        if ($sysexists===false) {
            // create table
            $create = "CREATE TABLE `".$dbname."`.`".$tablename."` ( ";
            $createcol = array();
            foreach ($tabledata AS $ck => $cv) {
                $createcol[] = "`".$cv['Field']."` ".$cv['Type']." ".(($cv['Null']=='YES')?'NULL':'NOT NULL')." ".(($cv['Default']!==NULL)?"DEFAULT '".$cv['Default']."'":'')." ".(($cv['Extra']!==false)?strtoupper($cv['Extra'])."":'');
            }
            $create.= implode(" , ", $createcol);
            $primary = '';
            foreach ($tabledata AS $ck => $cv) {
                if ($primary=='') {
                    if ($cv['Key']=='PRI') { $primary = ", PRIMARY KEY (`".$tabledata[$ck]['Field']."`)"; }
                }
            }
            $create.= $primary.")";
            $created = doSQL($create);
            if ($created['res']===true) {
                // table could be created
                $status = true;
                // add some uniques if required
                foreach ($tabledata AS $ck => $cv) {
                    if ($cv['Key']=='UNI') {
                        doSQL("ALTER TABLE `".$dbname."`.`".$tablename."` ADD UNIQUE(`".$cv['Field']."`)");
                    }
                }
            } else {
                $status = false;
                $return = "`".$tablename."` could not be created";
            }
        } else {
            $systsql = "DESCRIBE `".$dbname."`.`".$tablename."`";
            $systres = doSQL($systsql);
            if ($systres['num']>0) {
                // compare fields
                $sysfields = array();
                $updfields = array();
                foreach ($systres['set'] AS $ck => $cv) { $sysfields[$ck] = $cv['Field']; }
                foreach ($tabledata AS $ck => $cv) { $updfields[$ck] = $cv['Field']; }
                // adding new fields if structures doesn't match
                if ($sysfields!=$updfields) {
                    // get difference
                    $added = array_diff($updfields, $sysfields);
                    // moving cols is not implemented 2019-10-28
                    // $moved = array_diff_assoc($updfields, $sysfields);
                    if (count($added)>0) {
                        foreach ($added AS $ak => $av) {
                            $res = doSQL(trim("ALTER TABLE `".$dbname."`.`".$tablename."` ADD `".$tabledata[$ak]['Field']."` ".$tabledata[$ak]['Type']." ".(($tabledata[$ak]['Null']=='YES')?'NULL':'NOT NULL')." ".(($tabledata[$ak]['Default']!==NULL)?"DEFAULT '".$tabledata[$ak]['Default']."'":"")." ".(($ak>0)?" AFTER `".$tabledata[($ak-1)]['Field']."`":"")));
                            $_SESSION['msg'][] = "added col `".$tabledata[$ak]['Field']."` to table `".$dbname."`.`".$tablename."`";
                            // remove inserted field from update structure to prevent double comparsion in next step
                            unset($tabledata[$ak]);
                        }
                    }
                }
                // running ALL fields to check for changes of every col
                // var_export($sysfields);
                // echo "<hr />";
                // var_export($updfields);
                // echo "<hr />";
                foreach ($systres['set'] AS $ck => $cv) { 
                    // get the correct key of updater field
                    $comp = array_keys($updfields, $cv['Field'])[0];
                    if ($systres['set'][$ck]!=$tabledata[$comp]) {
                        // fieldname is same, but some facts changed
                        $res = doSQL(trim("ALTER TABLE `".$dbname."`.`".$tablename."` CHANGE `".$tabledata[$comp]['Field']."` `".$tabledata[$comp]['Field']."` ".$tabledata[$comp]['Type']." ".(($tabledata[$comp]['Null']=='YES')?'NULL':'NOT NULL')." ".(($tabledata[$comp]['Default']!==NULL)?"DEFAULT '".$tabledata[$comp]['Default']."'":'')));
                        $_SESSION['msg'][] = "altered col `".$tabledata[$comp]['Field']."` from table `".$dbname."`.`".$tablename."`";
                        // checking for unique key
                        if ($tabledata[$comp]['Key']=='UNI') {
                            doSQL("ALTER TABLE `".$dbname."`.`".$tablename."` ADD UNIQUE(`".$tabledata[$comp]['Field']."`)");
                        }
                    }          
                }
                // checking for primary key changes
                $prisys = false; 
                $priupd = false; 
                // compare primary keys
                foreach ($systres['set'] AS $ck => $cv) { if ($cv['Key']=='PRI' && $prisys===false) { $prisys = $ck; }}
                foreach ($tabledata AS $ck => $cv) { if ($cv['Key']=='PRI' && $priupd===false) { $priupd = $ck; }}
                if ($prisys!==false && $priupd!==false) {
                    
                }
                $systsql = "DESCRIBE `".$dbname."`.`".$tablename."`";
                $systres = doSQL($systsql);
                $sysdata = $systres['set'];
                array_multisort($sysdata);
                array_multisort($tabledata);
                if ($sysdata==$tabledata) {
                    $status = true;
                } else {
                    $return = "`".$tablename."` could not be merged with actual structure";
                    $status = false;
                }
            }
        }
        return array('status' => $status, 'return' => $return);
    }
}

























/* xml-reader funktionen */
if (!(function_exists('startElement'))):
	function startElement($parser, $name, $attrs) {
		global $insideitem, $tag, $title, $description, $link, $updated, $countitem;
		if ($insideitem):
		$tag = $name;
		elseif ($name == "ENTRY"):
		$insideitem = true;
		$countitem = $countitem + 1;
		endif;
	}	// startElement()
endif;

if (!(function_exists('endElement'))):
	function endElement($parser, $name) {
		global $insideitem, $tag, $title, $description, $link, $updated, $countitem, $topvalue, $xmlstyle;
		if ($name == "ENTRY" && $countitem<=$topvalue):
			
//			if ($xmlstyle=="utf"):
				printf("<p><a href='http://%s' target=\"_blank\">%s</a></p>",trim($link),trim($title));
				printf("<p><em>%s</em></p>",substr(trim($updated),0,10));
				printf("<p>%s</p>", trim($description));
//			else:
//				printf("<p><a href='http://%s' target=\"_blank\">%s</a></p>",trim($link),trim($title));
//				printf("<p><em>%s</em></p>",substr(trim($updated),0,10));
//				printf("<p>%s</p>",trim($description));
//			endif;
			
			$title = "";
			$description = "";
			$desc = "";
			$link = "";
			$updated = "";
			$insideitem = false;
		endif;
		}	// endElement()
endif;

if (!(function_exists('characterData'))):
	function characterData($parser, $data) {
		global $insideitem, $tag, $title, $description, $link, $updated, $countitem;
		if ($insideitem):
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
		endif;
	}	// characterData()
endif;

// ende xml-reader funktionen

if (!(function_exists('feedReader'))):
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
		if (strpos($data,"</item>")>0):
			/* regular rss feed */
			preg_match_all("/<item.*>(.+)<\/item>/Uism", $data, $items);
			$atom = 0;
		elseif (strpos($data,"</entry>")>0):
			/* atom feed */
			preg_match_all("/<entry.*>(.+)<\/entry>/Uism", $data, $items);
			$atom = 1;
		endif;
		/* encoding */
		if($encode == "auto"):
			preg_match("/<?xml.*encoding=\"(.+)\".*?>/Uism", $data, $encodingarray);
			$encoding = $encodingarray[1];
		else:
			$encoding = $encode;
		endif;
		echo "<div class=\"feedreader_area\">\n";
		/* linked channel title */
		if ($mode==1 || $mode==3 || $mode==5):
			if(strpos($data,"</item>")>0):
				$data = preg_replace("/<item.*>(.+)<\/item>/Uism", '', $data);
			else:
				$data = preg_replace("/<entry.*>(.+)<\/entry>/Uism", '', $data);
			endif;
			preg_match("/<title.*>(.+)<\/title>/Uism", $data, $channeltitle);
			if ($atom==0):
				preg_match("/<link>(.+)<\/link>/Uism", $data, $channellink);
			elseif ($atom==1):
				preg_match("/<link.*alternate.*text\/html.*href=[\"\'](.+)[\"\'].*\/>/Uism", $data, $channellink);
			endif;

			$channeltitle = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $channeltitle);
			$channellink = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $channellink);
	
			echo "<h1 class=\"feedreader_channel\"><a href=\"".$channellink[1]."\" title=\"";
			if ($encode != "no"):
				echo htmlentities($channeltitle[1],ENT_QUOTES,$encoding);
			else:
				echo $channeltitle[1];
			endif;
			echo "\">";
			if ($encode!="no"):
				echo htmlentities($channeltitle[1],ENT_QUOTES,$encoding);
			else:
				echo $channeltitle[1];
			endif;
			echo "</a></h1>\n";
		endif;
		/* items */
		// Titel, Link und Beschreibung der Items
		foreach ($items[1] as $item):
			preg_match("/<title.*>(.+)<\/title>/Uism", $item, $title);
			if ($atom==0):
				preg_match("/<link>(.+)<\/link>/Uism", $item, $link);
				preg_match("/<description>(.*)<\/description>/Uism", $item, $description);
				preg_match("/<pubDate>(.*)<\/pubDate>/Uism", $item, $published);
			elseif ($atom==1):
				preg_match("/<link.*alternate.*text\/html.*href=[\"\'](.+)[\"\'].*\/>/Uism", $item, $link);
				preg_match("/<summary.*>(.*)<\/summary>/Uism", $item, $description);
				preg_match("/<updated>(.*)<\/updated>/Uism", $item, $published);
			endif;
			
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
			
			if($mode==4 && ($published[1]!="" && $published[1]!=" "))
			{
				echo "<p class=\"feedreader_date\">\n";
				if($encode != "no")
				{echo htmlentities($published[1],ENT_QUOTES,$encoding)."\n";}
				else
				{echo $published[1];}
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
		endforeach;
		echo "</div>\n\n";
		} // feedReader
		/* base script developed by: Sebastian Gollus, http://www.web-spirit.de */
endif;

if (!(function_exists('panelOpener'))):
// $showAlways » boolean » if true, opener/closer will be shown always
// $showOn » array(
//        string » 'var that must exist (will be checked by getParamVar)' OR 
//        array » set of vars, that must exist, 
//        [array() » value(s), the var can have, so the opener/closer will be shown, 
//        array() » matching the keys of 'val' bool describes if panel will be shown or not ]) if only the first argument is given, the opener/closer will show up with $showPanel arg if the requested var is given
// $showPanel » boolean » if true, the panel will be shown
//
// $storePanel not yet implemented - 2017-09-08
// $storePanel » string » if set, the panel stat will be stored to $_SESSION['wspvars']['panelstat'][$storePanel] as boolean
//
// examples:
// panelOpener(true, array(), true) » opener will be shown always (so second argument is obsolete) and will be shown as open
// panelOpener(false, array('op',array('edit','new','save'),array(true, true, false)), true, 'existingvar') » opener will be shown if checkParamVar('op') returns values 'edit','new' or 'save' and will set the panel open, open or closed, third argument will be ignored and the actual stat of the panel will be stored to $_SESSION['wspvars']['panelstat']['existingvar']
function panelOpener($showAlways = false, $showOn = array(), $showPanel = true, $storePanel = '') {
    // get an random value to locate the buttons for jquery
    if (trim($storePanel)=='') {
        $randID = uniqid('panel');
    } else {
        $randID = 'panelbtn-'.trim($storePanel);
    }
    if ($showAlways) {
        echo "<div class='right'>";
        if ((trim($storePanel)!='' && isset($_SESSION['wspvars']['panelstat'][$storePanel]) && intval($_SESSION['wspvars']['panelstat'][$storePanel])==1) || $showPanel) {
            echo "<button id='".$randID."' type='button' class='btn-toggle-panel'><i class='fa fa-minus'></i></button>";
        }
        else {
            echo "<button id='".$randID."' type='button' class='btn-toggle-panel'><i class='fa fa-plus'></i></button>";
            echo "<script type=\"text/javascript\">\n\n\$(document).ready(function(){\$('#".$randID."').parents('.panel').find('.panel-body').hide(1);});\n\n</script>";
        }
        echo "</div>";
    }
    else {
        if (is_array($showOn) && isset($showOn[0]) && (is_array($showOn[0]) || trim($showOn[0])!='')) {
            if (isset($showOn[1]) && is_array($showOn[1]) && isset($showOn[2]) && is_array($showOn[2]) && count($showOn[1])>0 && count($showOn[1])==count($showOn[2])) {
                if (in_array(checkParamVar(trim($showOn[0])), $showOn[1])) {
                    echo "<div class='right'>";
                    if ($showOn[2][array_search(checkParamVar(trim($showOn[0])), $showOn[1])]) {
                        echo "<button id='".$randID."' type='button' class='btn-toggle-panel'><i class='fa fa-minus'></i></button>";
                    }
                    else {
                        echo "<button id='".$randID."' type='button' class='btn-toggle-panel'><i class='fa fa-plus'></i></button>";
                        echo "<script type=\"text/javascript\">\n\n\$(document).ready(function(){\$('#".$randID."').parents('.panel').find('.panel-body').hide(1);});\n\n</script>";
                    }
                    echo "</div>";
                }
            }
            else if (is_array($showOn[0]) && isset($showOn[2]) && is_bool($showOn[2])) {
                echo "<div class='right'>";
                $stat = true;
                foreach ($showOn[0] AS $sok => $sov) {
                    if (checkParamVar($sov)===false) {
                        $stat = false;
                    } 
                }
                if ($stat===$showOn[2]) {
                    echo "<button id='".$randID."' type='button' class='btn-toggle-panel'><i class='fa fa-minus'></i></button>";
                } 
                else {
                    echo "<button id='".$randID."' type='button' class='btn-toggle-panel'><i class='fa fa-plus'></i></button>";
                    echo "<script type=\"text/javascript\">\n\n\$(document).ready(function(){\$('#".$randID."').parents('.panel').find('.panel-body').css('display','none');});\n\n</script>";
                }
                echo "</div>";
            }
            else if (checkParamVar(trim($showOn[0]))!==false) {
                echo "<div class='right'>";
                if ($showPanel) {
                    echo "<button id='".$randID."' type='button' class='btn-toggle-panel'><i class='fa fa-minus'></i></button>";
                }
                else {
                    echo "<button id='".$randID."' type='button' class='btn-toggle-panel'><i class='fa fa-plus'></i></button>";
                    echo "<script type=\"text/javascript\">\n\n\$(document).ready(function(){\$('#".$randID."').parents('.panel').find('.panel-body').css('display','none');});\n\n</script>";
                }
                echo "</div>";
            }
        }
    }
}
endif;

if (!(function_exists('showOpenerCloser'))):
	function showOpenerCloser($idtag,$status) {
		return "function deprecated";
		} // showOpenerCloser()
endif;

if (!(function_exists('legendOpenerCloser'))):
	function legendOpenerCloser($idtag, $stat = 1) {
		if (array_key_exists($idtag, $_SESSION['opentabs'])): $status = $_SESSION['opentabs'][$idtag]; else: $status = false; endif;
		$soc = " <span class='locbutton'></span>\n";
		if (($stat==1 && $status==1) || ($stat==1 && $status===false) || ($status==1)):
			$_SESSION['opentabs'][$idtag] = 1;
			$soc.= "<script language=\"JavaScript\" type=\"text/javascript\">\n";
			$soc.= "\$(document).ready(function() {\n";
			$soc.= "	\$('#".$idtag." .locbutton').html('<i class=\'fa fa-minus-square\' aria-hidden=\'true\'></i>')\n";
			$soc.= "	\$('#".$idtag."').children('.legend').addClass('ocb').attr('rel', '".$idtag."')\n";
			$soc.= "	\$('#".$idtag."').children(':not(.legend):not(.table)').css('display', 'block')\n";
			$soc.= "	\$('#".$idtag."').children('.table').css('display', 'table')\n";
			$soc.= "});\n";
			$soc.= "</script>";
		else:
			$_SESSION['opentabs'][$idtag] = 0;
			$soc.= "<script language=\"JavaScript\" type=\"text/javascript\">\n";
			$soc.= "\$(document).ready(function() {\n";
			$soc.= "	\$('#".$idtag." .locbutton').html('<i class=\'fa fa-plus-square\' aria-hidden=\'true\'></i>')\n";
			$soc.= "	\$('#".$idtag."').children('.legend').addClass('ocb').attr('rel', '".$idtag."')\n";
			$soc.= "	\$('#".$idtag."').has('.legend').addClass('hidden').children(':not(.legend)').css('display', 'none')\n";
			$soc.= "});\n";
			$soc.= "</script>";
		endif;
		return $soc;
	} // legendOpenerCloser()
endif;

if (!(function_exists('lengthQuality'))):
	function lengthQuality($string,$best,$max) {
		if (strlen($string)<=ceil($best/4)):
		$qualstat = 1;
		elseif (strlen($string)<=ceil($best/4*3)):
		$qualstat = 2;
		elseif (strlen($string)<=$best):
		$qualstat = 3;
		elseif (strlen($string)<=($best+($max-$best)/3)):
		$qualstat = 4;
		elseif (strlen($string)<=($best+($max-$best)/3*2)):
		$qualstat = 5;
		else:
		$qualstat = 6;
		endif;
		return $qualstat;
	} // lengthQuality()
endif;

if (!(function_exists('showHumanSize'))){ function showHumanSize($sizeval = 0, $maxsize = 'TB', $space = '', $round = 2) { $sizes = array('B','KB','MB','GB','TB'); $s = 0; if ($sizeval>0) { while (intval($sizeval)>1024 && $sizes[$s]!=$maxsize) { $sizeval = $sizeval/1024; $s++; }} return (round($sizeval,$round).$space.$sizes[$s]);}}

// convert doubleslashes in strings to ascii-expression to use with inputfields
if (!(function_exists('prepareTextField'))):
	function prepareTextField($givenstring) {
		$string = str_replace("\"","&#34;",$givenstring);
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

// deprecated ;)
if (!(function_exists('mediaDirList'))):
	function mediaDirList($path, $basefolder) {
        addWSPMsg('errormsg', 'mediaDirList() is deprecated');
    }
endif;

// 2018-07-21
if (!(function_exists('dirList'))):
// replaces mediaDirList()
// $path » something below DOCUMENT_ROOT; can not start with a .
// $basepath » string will be replaced with empty string in returned pathstrings
// $sub » go through all subdirectories
// $children » return structured data
// $build ???
// $folder » separete folder below media/
function dirList($path, $basepath, $sub = true, $children = true, $build = false, $folder = '') {
    while (substr($path, 0, 1)=='.'): $path = substr($path, 1); endwhile;
    while (substr($path, 0, 1)=='/'): $path = substr($path, 1); endwhile;
    $dirpath = str_replace("//", "/", str_replace("//", "/", DOCUMENT_ROOT."/".$path));
    $dirscan = scandir($dirpath); 
    $dirlist = array();
    // get hidden directories
    $hiddendir = explode(",", getWSPProperties('hiddenmedia'));
    // create hidden directory array with full document_root path to compare with dirscan path
    foreach($hiddendir AS $hdk => $hdv) { if (trim($hdv)=='') { unset($hiddendir[$hdk]); } else { $hiddendir[$hdk] = cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.trim($hdv)); }}
    // rebuild hiddendir array to normalized array
    $hiddendir = array_values($hiddendir);
    foreach ($dirscan AS $dsk => $dsv):
        if (is_dir($dirpath."/".$dsv) && $dsv!='.' && $dsv!='..' && !(in_array(cleanPath($dirpath."/".$dsv), $hiddendir))):
            if ($sub):
                if ($children):
                    $filecount = count(scanfiles(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv)));
                    $dircount = count(scandirs(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv)));
                    $dirlist[] = (dirList($path.'/'.$dsv, $basepath, $sub, $children, $build, $folder))?array(
                        'id' => urltext(cleanPath($path."/".$dsv)),
                        'text' => ($filecount>0)?cleanPath(str_replace($basepath, "", $path).$dsv)." <span class='badge inline-badge'>".$filecount."</span>":cleanPath(str_replace($basepath, "", $path).$dsv),
                        'children' => dirList($path.'/'.$dsv, $basepath.'/'.$dsv, $sub, $children, $build, $folder),
                        'state' => array(
                            'opened' => ($build?false:((strstr($_SESSION['wspvars']['activemedia'][$_SESSION['wspvars']['upload']['basetarget']], cleanPath("/".$path."/".$dsv."/")))?true:false)),
                            'selected' => ($build?false:((strstr($_SESSION['wspvars']['activemedia'][$_SESSION['wspvars']['upload']['basetarget']], cleanPath("/".$path."/".$dsv."/")))?true:false)),
                        ),
                        'a_attr' => array(
                            'rel' => base64_encode(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv.DIRECTORY_SEPARATOR)),
                            'onclick' => 'showFiles($(this).attr("rel"))',
                        ),
                    ):array(
                        'id' => urltext(cleanPath($path.DIRECTORY_SEPARATOR.$dsv)),
                        'scandircount' => $dircount,
                        'dirlistcount' => count(dirList($path.DIRECTORY_SEPARATOR.$dsv, '', false, true, $build, $folder)),
                        'text' => ($filecount>0)?str_replace("//", "/", str_replace("//", "/", str_replace($basepath, "", $path).$dsv))." <span class='badge inline-badge'>".$filecount."</span>":str_replace("//", "/", str_replace("//", "/", str_replace($basepath, "", $path).$dsv)),
                        'state' => array(
                            'opened' => ($build?false:((strstr($_SESSION['wspvars']['activemedia'][$_SESSION['wspvars']['upload']['basetarget']], cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv.DIRECTORY_SEPARATOR)))?true:false)),
                            'selected' => ($build?false:((strstr($_SESSION['wspvars']['activemedia'][$_SESSION['wspvars']['upload']['basetarget']], cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv.DIRECTORY_SEPARATOR)))?true:false)),
                        ),
                        'a_attr' => array(
                            'rel' => base64_encode(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv.DIRECTORY_SEPARATOR)),
                            'onclick' => 'showFiles($(this).attr("rel"))',
                        ),
                    );
                else:
                    $dirlist[] = str_replace("//", "/", str_replace("//", "/", "/".str_replace($basepath, "", $path)."/".$dsv."/"));
                    $subdirlist = dirList(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv.DIRECTORY_SEPARATOR), $basepath, $sub, $children, $build, $folder);
                    if ($subdirlist!==false):
                        $dirlist = array_merge($dirlist, $subdirlist);
                    endif;
                endif;
            else:
                if ($children):
                    $dirlist[] = array(
                        'id' => urltext(cleanPath(str_replace($basepath, "", $path).DIRECTORY_SEPARATOR.$dsv)),
                        'text' => ((count(scandir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv)))-count(dirList($path.'/'.$dsv, '', false, true, $build, $folder))-1)>0)?str_replace("//", "/", str_replace("//", "/", str_replace($basepath, "", $path).$dsv))." <span class='badge inline-badge'>".(count(scandir(DOCUMENT_ROOT.$path.'/'.$dsv))-count(dirList($path.'/'.$dsv, '', false, true, $build, $folder))-1)."</span>":str_replace("//", "/", str_replace("//", "/", str_replace($basepath, "", $path).$dsv)),
                        'a_attr' => array(
                            'rel' => base64_encode(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv.DIRECTORY_SEPARATOR)),
                            'onclick' => 'showFiles($(this).attr("rel"))'
                        ),
                        'state' => array(
                            'opened' => ($build?false:((strstr($_SESSION['wspvars']['activemedia'][$_SESSION['wspvars']['upload']['basetarget']], cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv.DIRECTORY_SEPARATOR)))?true:false)),
                            'selected' => ($build?false:((strstr($_SESSION['wspvars']['activemedia'][$_SESSION['wspvars']['upload']['basetarget']], cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$dsv.DIRECTORY_SEPARATOR)))?true:false)),
                        ),
                        );
                else:
                    $dirlist[] = cleanPath(str_replace($basepath, "", $path).DIRECTORY_SEPARATOR.$dsv.DIRECTORY_SEPARATOR);
                endif;
            endif;
        endif;
    endforeach;
    if (count($dirlist)==0): $dirlist = array(); endif;
    return $dirlist;
}
endif;

// 2018-07-21
if (!(function_exists('simpledirlist'))):
// $path » something below DOCUMENT_ROOT; can not start with a .
function simpledirlist($path, $sub = true) {
    while (substr($path, 0, 1)=='.'): $path = substr($path, 1); endwhile;
    while (substr($path, 0, 1)=='/'): $path = substr($path, 1); endwhile;
    $dirpath = str_replace("//", "/", str_replace("//", "/", DOCUMENT_ROOT."/".$path));
    $dirscan = scandir($dirpath); 
    $simpledirlist = array();
    foreach ($dirscan AS $dsk => $dsv):
        if (is_dir($dirpath."/".$dsv) && $dsv!='.' && $dsv!='..'):
            $simpledirlist[] = str_replace("//", "/", str_replace("//", "/", $path.$dsv));
            if ($sub):
                $subsimpledirlist = simpledirlist($path.'/'.$dsv.'/', $sub);
                if ($subsimpledirlist!==false):
                    $simpledirlist = array_merge($simpledirlist, $subsimpledirlist);
                endif;
            endif;
        endif;
    endforeach;
    if (count($simpledirlist)==0): $simpledirlist = false; endif;
    return $simpledirlist;
}
endif;

if (!(function_exists('scandirs'))) {
    function scandirs($directory, $sorting_order = SCANDIR_SORT_ASCENDING, $showhidden = false) {
        $values = @scandir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory), $sorting_order);
        if (is_array($values)) {
            foreach ($values AS $vk => $cv) {
                if (!(is_dir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$cv)))) {
                    unset($values[$vk]);
                }
                if (!$showhidden && substr($cv,0,1)=='.') {
                    unset($values[$vk]);
                }
            }
            $values = array_values($values);
            return $values;
        } else {
            return false;
        }
    }
}

// scans a directory for FILES only (on the other hand SAME usability as scandir)
// array scanfiles ( string $directory [, int $sorting_order = SCANDIR_SORT_ASCENDING [, resource $context ]] )
if (!(function_exists('scanfiles'))) {
    function scanfiles($directory , $sorting_order = SCANDIR_SORT_ASCENDING , $showhidden = false , $fileending = null ) {
        $values = @scandir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory), $sorting_order);
        if (is_array($values)) {
            foreach ($values AS $vk => $cv) {
                if (!(is_file(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$cv)))) {
                    unset($values[$vk]);
                }
                if (!$showhidden && substr($cv,0,1)=='.') {
                    unset($values[$vk]);
                }
            }
            // check for given file ending (a small filetype check)
            if ($fileending!==null) {
                // create array from fileending, if only a string is given
                if (!(is_array($fileending))) { $fileending = array($fileending); }
                // only do loops, when it's really an array with elements
                if (count($fileending)>0) {
                    // run the $fileending array and remove false positive dots
                    foreach ($fileending AS $fk => $fv) { $fileending[$fk] = str_replace('.', '', $fv); }
                    foreach ($values AS $vk => $vv) {
                        $ending = substr(basename($vv), (strrpos(basename($vv), '.')+1));
                        if (!(in_array($ending, $fileending))) {
                            unset($values[$vk]);
                        }
                    }
                }
            }
            $values = array_values($values);
            return $values;
        } else {
            return false;
        }
    }
}

if (!(function_exists('fileData'))):
// $path AS subdirectory of /media/
function fileData($path) {
    $path = cleanPath("/".$path);
    $part = explode("/", $path);
    $base = basename($path);
    if ($part[1]=='media'):
        $data = array(
            'filehash' => '',
            'filename' => '',
            'filepath' => '',
            'filedesc' => '',
            'filekeys' => '',
            'filesize' => 0, 
            'filedate' => 0, 
            'filetype' => '', 
            'fileuser' => 0, 
            'filedata' => false, 
            'fileusage' => false
        );
        var_export($data);
    else:
        return false;
    endif;
}
endif;

// returns data for FILES
if (!(function_exists('showMediaFiles'))) {
function showMediaFiles($directory = '', $filelist = array(), $sorting = 'filename', $sorting_order = 'ASC') {
    if (!(is_array($filelist)) || count($filelist)==0) {
        $filelist = scanfiles(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory));
    }
    if (is_array($filelist) && count($filelist)>0):
        $showlist = array(); $datelist = array(); $sizelist = array();
        
        $mediafolder = cleanPath(DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR);
        $foldertmp = explode("/", $mediafolder);
        $folderarray = array();
        foreach ($foldertmp AS $ftk => $ftv) { if (trim($ftv)!='') { $folderarray[] = trim($ftv); }}
        $mediatoptarget = cleanPath(DIRECTORY_SEPARATOR.$folderarray[0].DIRECTORY_SEPARATOR.$folderarray[1].DIRECTORY_SEPARATOR);

        $thumbfolder = cleanPath("/".$mediatoptarget."/thumbs/");
        $originalfolder = cleanPath("/".$mediatoptarget."/original/");
        $previewfolder = cleanPath("/".$mediatoptarget."/preview/");
        
        $moduleusage_sql = "SELECT `affectedcontent` FROM `modules` WHERE `affectedcontent` != '' && `affectedcontent` IS NOT NULL";
        $moduleusage_res = doSQL($moduleusage_sql);
        
        foreach($filelist AS $flk => $flv) {
            // init datavalue
            $showlist[$flv] = array('filehash' => '','filename' => $flv,'filepath' => cleanPath("/".$mediafolder."/".$flv),'filedesc' => '','filekeys' => '','filesize' => 0, 'filedate' => 0, 'filetype' => '', 'fileuser' => 0, 'filedata' => false, 'fileusage' => false); $datelist[$flv] = 0; $sizelist[$flv] = 0;
            // 1. get real data from filesystem
            $sizelist[$flv] = $showlist[$flv]['filesize'] = filesize(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$flv));
            $datelist[$flv] = $showlist[$flv]['filedate'] = filemtime(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$flv));
            $showlist[$flv]['filehash'] = base64_encode(serialize(array($mediafolder, $showlist[$flv]['filepath'])));
            $showlist[$flv]['filextns'] = cleanPath(substr($flv, strrpos($flv, ".")));
            $showlist[$flv]['filetype'] = mime_content_type(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$flv));
            $showlist[$flv]['fileuser'] = fileowner(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$flv));
            $filedata = @getImageSize(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$flv));        
            if (is_array($filedata)) {
                $showlist[$flv]['filedata'] = array('width' => $filedata[0], 'height' => $filedata[1]); 
            }
            $thmb = false; 
            if (file_exists(DOCUMENT_ROOT.str_replace($mediafolder, $thumbfolder, $showlist[$flv]['filepath']))) {
                $showlist[$flv]['filethmb'] = str_replace($mediafolder, $thumbfolder, $showlist[$flv]['filepath']); 
                $thmb = true;
            }
            $orig = false;
            if (file_exists(DOCUMENT_ROOT.str_replace($mediafolder, $originalfolder, $showlist[$flv]['filepath']))) {
                $showlist[$flv]['fileorig'] = str_replace($mediafolder, $originalfolder, $showlist[$flv]['filepath']); 
                $orig = true; 
            }
            $prev = false; 
            if (file_exists(DOCUMENT_ROOT.str_replace($mediafolder, $previewfolder, $showlist[$flv]['filepath']))) { 
                $showlist[$flv]['fileprev'] = str_replace($mediafolder, $previewfolder, $showlist[$flv]['filepath']);
                $prev = true;
            }
            // 2. get more data from database
            $filedata_sql = "SELECT `filedesc` FROM `wspmedia` WHERE `filepath` = '".escapeSQL(cleanPath($showlist[$flv]['filepath']))."'";
            $filedata_res = doResultSQL($filedata_sql);
            if ($filedata_res!==false) {
                $showlist[$flv]['filedesc'] = trim($filedata_res);
            }
            else {
                $fileins_sql = "INSERT INTO `wspmedia` SET `filepath` = '".escapeSQL(cleanPath($showlist[$flv]['filepath']))."', `filename` = '".escapeSQL($showlist[$flv]['filename'])."', `filetype` = '".escapeSQL($showlist[$flv]['filetype'])."', `filekey` = '".$showlist[$flv]['filehash']."', `filesize` = ".intval($showlist[$flv]['filesize']).", `filedate` = ".intval($showlist[$flv]['filedate']).", `thumb` = ".intval($thmb).", `preview` = ".intval($prev).", `original` = ".intval($orig).",  `lastchange` = ".time();
                doSQL($fileins_sql);
            }
            // 3. get usage ?!?!?!
            $filecontent_sql = "SELECT `cid` FROM `content` WHERE `valuefields` LIKE '%".escapeSQL(cleanPath($showlist[$flv]['filepath']))."%' AND `trash` = 0";
            $filecontent_num = getNumSQL($filecontent_sql);
            $filegc_sql = "SELECT `id` FROM `content_global` WHERE `valuefields` LIKE '%".escapeSQL(cleanPath($showlist[$flv]['filepath']))."%' AND `trash` = 0";
            $filegc_num = getNumSQL($filegc_sql);
            $showlist[$flv]['fileusage'] = (((intval($filecontent_num)+intval($filegc_num))>0)?true:false);
            // run modular table fields to get information about usage
            if ($moduleusage_res['num']>0) {
                foreach ($moduleusage_res['set'] AS $murk => $murv) {
                    $grepdata = unserializeBroken($murv['affectedcontent']);
                    foreach ($grepdata AS $table => $fieldnames) {
                        $fileval_sql = array();
                        foreach ($fieldnames AS $fieldname) {
                            $fileval_sql[] = " `".$fieldname."` LIKE '%".escapeSQL(cleanPath($showlist[$flv]['filepath']))."%' ";
                        }
                        $filemod_sql = "SELECT * FROM `".$table."` WHERE (".implode(" OR ", $fileval_sql).")";
                        $filemod_num = getNumSQL($filemod_sql);
                        if ($filemod_num>0) {
                            $showlist[$flv]['fileusage'] = true;
                        }
                    }
                }
            }
            
        }

        if ($sorting=='filesize'):
            if ($sorting_order=='DESC'): arsort($sizelist, SORT_NUMERIC); else: asort($sizelist, SORT_NUMERIC); endif;
            foreach ($sizelist AS $slk => $slv): $sizelist[$slk] = $showlist[$slk]; endforeach; $showlist = $sizelist;
        elseif ($sorting=='filedate'):
            if ($sorting_order=='DESC'): arsort($datelist, SORT_NUMERIC); else: asort($datelist, SORT_NUMERIC); endif;
            foreach ($datelist AS $dlk => $dlv): $datelist[$dlk] = $showlist[$dlk]; endforeach; $showlist = $datelist;
        else:
            if ($sorting_order=='DESC'): krsort($showlist, SORT_STRING); endif;
        endif;
        return $showlist;
    else:
        return false;
    endif;
}
}

// creates a new folder below FTP_BASEDIR
// will be removed in 7.2
if (!(function_exists('createNewFolder'))) {
    function createNewFolder($path='/') {
        addWSPMsg('errormsg', 'createNewFolder() is deprecated and was replaced with createFolder(). createNewFolder() will be removed in version 7.2');
        return(createFolder($path));
    }
}

// creates a new folder below FTP_BASEDIR
if (!(function_exists('createFolder'))) {
    function createFolder($path=DIRECTORY_SEPARATOR) {
        // define path always as a subfolder to DOCUMENT_ROOT OR FTP_BASE
        $path = cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR);
        // do the creation
        if (isset($_SESSION['wspvars']['ftp']) && $_SESSION['wspvars']['ftp']!==false) {
            // try to create by ftp
            $path = cleanPath(FTP_BASE.DIRECTORY_SEPARATOR.cleanPath($path));
            $pathparts = explode(DIRECTORY_SEPARATOR, $path);
            $try = true;
            $tp = '';
            foreach ($pathparts AS $ppk => $ppv) {
                $tp = cleanPath(DIRECTORY_SEPARATOR.$tp.DIRECTORY_SEPARATOR.$ppv.DIRECTORY_SEPARATOR);
                if (@ftp_chdir($ftp, $tp)) {
                    // changedir is possible, so some of the upper directories already exists
                    // no returning of an error message
                } else if (!(@ftp_mkdir($ftp, $tp))) {
                    $try = false;
                }
            }
            return $try;
        } else if (isset($_SESSION['wspvars']['srv']) && $_SESSION['wspvars']['srv']!==false) {
            // try to create by srv
            $pathparts = explode("/", $path);
            $startpath = DOCUMENT_ROOT;
            foreach ($pathparts AS $pk => $pv) {
                @mkdir(cleanPath($startpath.DIRECTORY_SEPARATOR.$pv));
                $startpath = cleanPath($startpath.DIRECTORY_SEPARATOR.$pv);
            }
            if (is_dir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$path))) {
                return true;
            } else {
                if (defined('WSP_DEV') && WSP_DEV) {
                    addWSPMsg( 'errormsg', '<em>createFolder</em> could not create folder by srv' );
                }
                return false;
            }
        } else {
            if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg( 'errormsg', '<em>createFolder</em> could not create folder in any way' );
            }
            return false;
        }
    }
}

// renames a folder below FTP_BASEDIR
if (!(function_exists('renameFolder'))) {
    function renameFolder($oldpath = '/', $newpath = '/', $emptyrequired = true) {
        if ($oldpath!=$newpath) {
            $oldpath = cleanPath(DIRECTORY_SEPARATOR.$oldpath.DIRECTORY_SEPARATOR);
            $newpath = cleanPath(DIRECTORY_SEPARATOR.$newpath.DIRECTORY_SEPARATOR);
            if (count(explode(DIRECTORY_SEPARATOR, $oldpath))==count(explode(DIRECTORY_SEPARATOR, $newpath))) {
                // converts file-path to an absolute path and sets THEN relative to FTP_BASE
                $oldpath = cleanPath($_SESSION['wspvars']['ftp_base']."/".cleanPath($oldpath));
                $newpath = cleanPath($_SESSION['wspvars']['ftp_base']."/".cleanPath($newpath));
                // create ftp-connection
                $ftp = doFTP();
                if ($ftp!==false) {
                    if (ftp_rename($ftp, $oldpath, $newpath)) {
                        return true;
                    }
                    else {
                        return false;
                    }
                    ftp_close($ftp);
                }
                else {
                    return false;
                }
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
}

if (!(function_exists('doFTP'))) {
    function doFTP($condata = false) {
        $ftp = false;
        $ftp = (((isset($condata['ftp_ssl'])?$condata['ftp_ssl']:(defined('FTP_SSL')?FTP_SSL:false))===true)?ftp_ssl_connect((isset($condata['ftp_host'])?$condata['ftp_host']:(defined('FTP_HOST')?FTP_HOST:false)), (isset($condata['ftp_port'])?$condata['ftp_port']:(defined('FTP_PORT')?FTP_PORT:false))):ftp_connect((isset($condata['ftp_host'])?$condata['ftp_host']:(defined('FTP_HOST')?FTP_HOST:false)), (isset($condata['ftp_port'])?$condata['ftp_port']:(defined('FTP_PORT')?FTP_PORT:false)))); if ($ftp!==false) {if (!ftp_login($ftp, (isset($condata['ftp_user'])?$condata['ftp_user']:FTP_USER), (isset($condata['ftp_pass'])?$condata['ftp_pass']:FTP_PASS))) { $ftp = false; }} if ($ftp!==false) { ftp_pasv($ftp, (isset($condata['ftp_pasv'])?$condata['ftp_pasv']:(defined('FTP_PASV')?FTP_PASV:false))); }
        return $ftp;
    }
}

if (!(function_exists('cleanToBase'))) {
    function cleanToBase($path) {
        if (strpos(strval(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR)), strval(cleanPath(DIRECTORY_SEPARATOR.DOCUMENT_ROOT.DIRECTORY_SEPARATOR)))===0) {
            // the given path begins with the document_root information 
            $path = cleanPath(DIRECTORY_SEPARATOR.substr(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR), strlen(strval(cleanPath(DIRECTORY_SEPARATOR.DOCUMENT_ROOT.DIRECTORY_SEPARATOR)))).DIRECTORY_SEPARATOR);
        }
        return $path;
    }
}

if (!(function_exists('clearFolder'))) {
    // clears real file system folder contents by filextension and or filetype
    function clearFolder($directory, $fileextensions = array(), $filetypes = array()) {
        $directory = cleanToBase(cleanPath(DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR));
        $cleandir = opendir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory));
        while ($cleanname = readdir($cleandir)) {
            if ($cleanname!="." && $cleanname!="..") {
                if (is_dir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$cleanname))) {
                    $cleared = clearFolder(cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname), $fileextensions, $filetypes);
                    if ($cleared===true) {
                        deleteFolder(cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                    }
                }
                else if (is_file(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$cleanname))) {
                    if (is_array($fileextensions) && count($fileextensions)>0) {
                        if (in_array(substr(basename($cleanname), strrpos(basename($cleanname), '.')), $fileextensions)) {
                            if (substr(basename($cleanname),0,1)=='.') {
                                // if this would be a hidden file » remove it
                                deleteFile(cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                                addWSPMsg('devmsg', "disallowed hidden file: ".cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                            } else if (is_array($filetypes) && count($filetypes)>0) {
                                if (!(in_array(mime_content_type(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$cleanname)), $filetypes))) {
                                    deleteFile(cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                                    addWSPMsg('devmsg', "disallowed filetype ".mime_content_type(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$cleanname)).": ".cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                                }
                            }
                        } else {
                            deleteFile(cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                            addWSPMsg('devmsg', "disallowed file extension ".substr(basename($cleanname), strrpos(basename($cleanname), '.')).": ".cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                        }
                    } 
                    else if (is_array($filetypes) && count($filetypes)>0) {
                        if (!(in_array(mime_content_type(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$cleanname)), $filetypes))) {
                            deleteFile(cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                            addWSPMsg('devmsg', "disallowed filetype ".mime_content_type(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$cleanname)).": ".cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                        }
                    } 
                    else {
                        // if nothing special defined, just delete "all" files
                        deleteFile(cleanPath($directory.DIRECTORY_SEPARATOR.$cleanname));
                    }
                }
            }
        }
        $scanfiles = scanfiles($directory);
        $scandirs = scandirs($directory);
        if (count($scanfiles)==0 && count($scandirs)==0) { 
            // return true for empty directories 
            return true;
        }
        else {
            return false;
        }
    }
}

if (!(function_exists('copyFolder'))) {
    // copies real file system folder, folder can be a ftp-folder OR in temporary structure
    // optionally with an existing ftp connection
    function copyFolder($frompath = '/', $targetpath = '/', $move = true) {
        $fromdir = cleanPath(DIRECTORY_SEPARATOR.$frompath.DIRECTORY_SEPARATOR);
        $targetdir = cleanPath(DIRECTORY_SEPARATOR.cleanPath($targetpath).DIRECTORY_SEPARATOR);
        // do copy for every file
        $folderlist = scandirs($frompath);
        $filelist = scanfiles($frompath);
        // setup return var
        $return = true;
        foreach ($filelist AS $flk => $flv) {
            $copyfile = copyFile(cleanPath($fromdir.DIRECTORY_SEPARATOR.$flv), cleanPath($targetdir.DIRECTORY_SEPARATOR.$flv));
            if (!$copyfile) { $return = false; }
        }
        foreach ($folderlist AS $fldk => $fldv) {
            $copyfolder = copyFolder(cleanPath($fromdir.DIRECTORY_SEPARATOR.$fldv.DIRECTORY_SEPARATOR), cleanPath($targetdir.DIRECTORY_SEPARATOR.$fldv.DIRECTORY_SEPARATOR), $move);
            if (!$copyfolder) { $return = false; }
        }
        return $return;
    }
}

// tries to remove all FILES from a folder below FTP_BASEDIR
if (!(function_exists('emptyFolder'))) {
    function emptyFolder($path = false) {
        /*
        if ($path && cleanPath($path)!=cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR)) {
            $filelist = scanfiles(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR));
            if (is_array($filelist)) {
                foreach ($filelist AS $fk => $fv) {
                    deleteFile(cleanPath($path.DIRECTORY_SEPARATOR.$fv));
                }
            }
            $filelist = scanfiles(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR));
            if (is_array($filelist)) {
                return false;
            }
            else {
                return true;
            }
        }
        else {
            addWSPMsg('errormsg', returnIntLang('path not allowed to empty', false));
            return false;
        }
        */
        
        if ($path && cleanPath($path)!=cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR)) {
            $filelist = scanfiles(cleanPath(DIRECTORY_SEPARATOR.$path));
            if (count($filelist)>0) {
                // create ftp-connection
                $ftp = doFTP();
                if ($ftp!==false) {
                    foreach($filelist AS $flk => $flv) {
                        // try to do it by FTP
                        if (@ftp_delete($ftp, cleanPath(FTP_BASE.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$flv))) {
                            $f++;
                        // try to do it by file system
                        } 
                        else if (@unlink(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$flv))) {
                            $f++;
                        }
                    }
                    ftp_close($ftp);
                    if (count($filelist)!=$f) {
                        return false;
                    } else {
                        return true;
                    }
                }
                else {
                    return false;
                }
            }
            else {
                addWSPMsg('noticemsg', returnIntLang('emptyFolder() found no files in folder', false));
                return false;
            }
        }
        else {
            addWSPMsg('errormsg', returnIntLang('path not allowed to empty', false));
            return false;
        }
    }
}

// deletes a folder
if (!(function_exists('deleteFolder'))) {
    function deleteFolder($path = false, $emptyrequired = true) {
        if (trim($path)!='' && cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR)!=DIRECTORY_SEPARATOR && cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR)!=cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR)) {
            if ($emptyrequired) {
                // try to remove directories without checking for empty dir
                // try different connection modes
                if (isset($_SESSION['wspvars']['ftp']) && $_SESSION['wspvars']['ftp']!==false) {
                    // create ftp-connection
                    $ftp = doFTP();
                    if ($ftp!==false) {
                        // try to do it by FTP
                        if (@ftp_rmdir($ftp, cleanPath(FTP_BASE.DIRECTORY_SEPARATOR.$path))) {
                            return true;
                        } else {
                            if (defined('WSP_DEV') && WSP_DEV) {
                                addWSPMsg( 'errormsg', '<em>deleteFolder</em> could not remove folder' );
                            }
                            return false;
                        }
                        ftp_quit($ftp);
                    } else {
                        if (defined('WSP_DEV') && WSP_DEV) {
                            addWSPMsg( 'errormsg', '<em>deleteFolder</em> could not connect by ftp' );
                        }
                        return false;
                    }
                } else if (isset($_SESSION['wspvars']['srv']) && $_SESSION['wspvars']['srv']!==false) {
                    // try with srv connection
                    if (@rmdir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$path))) {
                        return true;
                    } else {
                        if (defined('WSP_DEV') && WSP_DEV) {
                            addWSPMsg( 'errormsg', '<em>deleteFolder</em> could not connect by srv' );
                        }
                        return false;
                    }
                } else {
                    if (defined('WSP_DEV') && WSP_DEV) {
                        addWSPMsg( 'errormsg', '<em>deleteFolder</em> could not connect in any way' );
                    }
                    return false;
                }
            } 
            else {
                $folderlist = scandirs(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR), SCANDIR_SORT_ASCENDING, true);
                $filelist = scanfiles(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR), SCANDIR_SORT_ASCENDING, true);
                $removed = array();
                if (is_array($folderlist)) {
                    foreach ($folderlist AS $fk => $fv) {
                        if ($fv!='.' && $fv!='..') {
                            $removed[] = deleteFolder(cleanPath($path.DIRECTORY_SEPARATOR.$fv), false);
                        }
                    }
                }
                if (is_array($filelist) && count($filelist)>0) {
                    foreach ($filelist AS $fk => $fv) {
                        if ($fv!='.' && $fv!='..') {
                            $removed[] = deleteFile(cleanPath($path.DIRECTORY_SEPARATOR.$fv));
                        }
                    }
                }
                // recheck for empty folder
                $folderlist = scandirs(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR));
                $filelist = scanfiles(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR));
                if (!in_array(false, $removed)) {
                    return deleteFolder($path);
                } else {
                    return false;
                }
            }
        } else if (defined('WSP_DEV') && WSP_DEV===true) {
            addWSPMsg('errormsg', returnIntLang('path not allowed to empty', false));
        }
    }
}

// deletes a file 
if (!(function_exists('deleteFile'))) {
    function deleteFile($path = false) {
        if (is_file(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$path))) {

            if (isset($_SESSION['wspvars']['ftp']) && $_SESSION['wspvars']['ftp']!==false) {
                // create ftp-connection
                $ftp = doFTP();
                if ($ftp!==false) {
                    // try to do it by FTP
                    if (@ftp_delete($ftp, cleanPath(FTP_BASE.DIRECTORY_SEPARATOR.$path))) {
                        return true;
                    } else {
                        if (defined('WSP_DEV') && WSP_DEV) {
                            addWSPMsg( 'errormsg', '<em>deleteFile</em> could not remove file' );
                        }
                        return false;
                    }
                    ftp_quit($ftp);
                } else {
                    if (defined('WSP_DEV') && WSP_DEV) {
                        addWSPMsg( 'errormsg', '<em>deleteFile</em> could not connect by ftp' );
                    }
                    return false;
                }
            } else if (isset($_SESSION['wspvars']['srv']) && $_SESSION['wspvars']['srv']!==false) {
                // try with srv connection
                if (@unlink(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$path))) {
                    return true;
                } else {
                    if (defined('WSP_DEV') && WSP_DEV) {
                        addWSPMsg( 'errormsg', '<em>deleteFile</em> could not connect by srv' );
                    }
                    return false;
                }
            } else {
                if (defined('WSP_DEV') && WSP_DEV) {
                    addWSPMsg( 'errormsg', '<em>deleteFile</em> could not connect in any way' );
                }
                return false;
            }    
        } else {
            if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg( 'errormsg', '<em>deleteFile</em> could not find file' );
            }
            return false;
        }
    }
}

if (!(function_exists('copyFile'))) {
    function copyFile($from = false, $to = false) {
        // check for final directory and create if not exists
        if ($return && !is_dir(cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.dirname(cleanPath($to)).DIRECTORY_SEPARATOR))) {
            $return = createFolder(cleanPath(DIRECTORY_SEPARATOR.dirname(cleanPath($to)).DIRECTORY_SEPARATOR));
        } else {
            $return = true;
        }
        // try to copy by ftp
        if ($return && isset($_SESSION['wspvars']['ftp']) && $_SESSION['wspvars']['ftp']!==false) {
            $ftp = doFTP();
            if ($ftp!==false) {
                if (ftp_put($ftp, cleanPath(FTP_BASE.DIRECTORY_SEPARATOR.cleanPath($to)), $from, FTP_BINARY)) {
                    return true;
                } else {
                    if (defined('WSP_DEV') && WSP_DEV) {
                        addWSPMsg('errormsg', '<em>copyFile</em> could not copy <strong>'.$from.'</strong> to <strong>'.$to.'</strong> by ftp');
                    }
                    return false;
                }
            } else {
                if (defined('WSP_DEV') && WSP_DEV) {
                    addWSPMsg('errormsg', 'no ftp con');
                }
                return false;
            }
        } else if ($return && isset($_SESSION['wspvars']['srv']) && $_SESSION['wspvars']['srv']!==false) {
            if (move_uploaded_file($from, cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.cleanPath($to)))) {
                return true;
            } else {
                if (rename($from, cleanPath(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.cleanPath($to)))) {
                    return true;
                } else {
                    if (defined('WSP_DEV') && WSP_DEV) {
                        addWSPMsg('errormsg', '<em>copyFile</em> could not copy <strong>'.$from.'</strong> to <strong>'.$to.'</strong> by srv');
                    }
                    return false;
                }
            }
        } else if ($return) {
            if (defined('WSP_DEV') && WSP_DEV) {
                addWSPMsg( 'errormsg', '<em>copyFile</em> could not copy in any way' );
            }
            return false;
        }
    }
}

// ?????? special function for WHAT !?!?
if (!(function_exists('cleanupDirList'))) {
    function cleanupDirList($list) {
        return deleteFile (cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR.$_SESSION['wspvars']['usevar'].DIRECTORY_SEPARATOR.trim($list).".json"));
    }
}

/**
 * ermittelt alle Bild-Dateien und gibt sie fuer ein select aufbereitet zurueck
 * new since 2015-03-17
 * @return $mediafiles
 */
if (!(function_exists('imageSelect'))):
	// path => startpfad der suche 
	// toppath => point in path, from which data will be returned as value (e.g. path = / , toppath = /images => return will start below /images)
	// hidepath => hide path in selection (show only filenames)
	// selected => array ausgewählter Dateien
	function imageSelect($path = '/', $toppath = '', $hidepath = false, $selected = array() , $trimname = 60, $buildforjs = true) {
		if (isset($_SESSION['wspvars']['stripfilenames']) && intval($_SESSION['wspvars']['stripfilenames'])>intval($trimname)) {
			$trimname = intval($_SESSION['wspvars']['stripfilenames']);
		}
		// get hidemedia option
		$hide_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddenmedia'";
		$hide_res = doSQL($hide_sql);
		$hide_num = intval($hide_res['num']);
		// define hidemedia sql statement
		$hidemedia = "";
		if ($hide_num>0): 
			$hiddenmedia = explode(",", trim($hide_res['set'][0]['varvalue']));
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
		if ($l_res['num']>0):
			$mediafiles.= '<optgroup label="last uploaded">';
			$mediafiles .= "<option value=\"#\" >last uploaded</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			if (!($buildforjs)): $mediafiles .= "\n"; endif;
			for ($r=0; $r<$l_num; $r++):
				$value = str_replace("//", "/", str_replace("//", "/", trim("/".mysql_result($l_res,$r,'filefolder')."/".mysql_result($l_res,$r,'filename'))));
				$mediafiles .= "<option value=\"".$value."\" >";
				$mediadesc = '';
				$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".$value."%'";
				$desc_res = mysql_query($desc_sql);
				if ($desc_res):
					$desc_num = mysql_num_rows($desc_res);
					if ($desc_num>0):
						$mediadesc = mysql_result($desc_res, 0, "filedesc");
					endif;
				endif;
				if (trim($toppath)!="" && $toppath!="/"):
					$value = str_replace($toppath, "", $value);
				endif;
				if (trim($mediadesc)!=""):
					$mediafiles .= $mediadesc;
				elseif (strlen($value)>$trimname):
					$mediafiles .= substr($value,0,5)."...".substr($value,-($trimname-5));
				else:
					$mediafiles .= $value;
				endif;
				$mediafiles .= "</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			endfor;
			$mediafiles .= "<option value=\"#\" >--------</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			$mediafiles.= '</optgroup>';
			if (!($buildforjs)): $mediafiles .= "\n"; endif;
		endif;
		
		// setup empty arrays
		$files = array();
		$dir = array();
		if (is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/".$path)))):
			$d = dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media/".$path)));
			while (false !== ($entry = $d->read())):
				// get only folders with images in
				if (substr($entry, 0, 1)!='.' && (stristr($path.$entry, 'images') || stristr($path.$entry, 'screen')) && !(in_array($entry, $hiddenmedia))):
					if (is_file(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media/'.$path."/".$entry)))):
						$files[] = str_replace("//", "/", str_replace("//", "/", "/media/".$path."/".$entry));
					elseif (is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media/'.$path."/".$entry)))):
						$dir[] = str_replace("//", "/", str_replace("//", "/", "/".$path."/".$entry."/"));
					endif;
				endif;
			endwhile;
			$d->close();
			sort($files);
			sort($dir);
			$mediafiles .= "<optgroup label=\"".str_replace("//", "/", str_replace("//", "/", "/media/".$path))."\">"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			foreach($files AS $value):
				$returnvalue = $value;
				if (trim($toppath)!='/' && strpos($value, $toppath)===0):
					$returnvalue = str_replace("//", "/", str_replace("//", "/", "/".substr($value, strlen(trim($toppath)))));
				endif;
				$showvalue = $value;
				if ($hidepath):
					$showvalue = substr($value, (strrpos($value, "/")+1));
				endif;
				$mediafiles .= "<option value=\"".$returnvalue."\"";
				if (in_array($value, $selected)):
					$mediafiles .= " selected=\"selected\"";
				endif;
				$mediafiles .= ">";
				$mediadesc = '';
				$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//", "/", str_replace("//", "/", $value))."%'";
				$desc_res = doSQL($desc_sql);
                if ($desc_res['num']>0):
                    $mediadesc = trim($desc_res['set'][0]['filedesc']);
                endif;
				if (trim($mediadesc)!=""):
					$mediafiles .= $mediadesc;
				elseif (strlen($showvalue)>$trimname):
					$mediafiles .= substr($showvalue,0,5)."...".substr($showvalue,-($trimname-5));
				else:
					$mediafiles .= $showvalue;
				endif;
				$mediafiles .= "</option>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			endforeach;
			$mediafiles .= "</optgroup>"; if (!($buildforjs)): $mediafiles .= "\n"; endif;
			foreach($dir AS $value):
				if (trim(imageSelect($value, $toppath, $hidepath, $selected, $trimname, $buildforjs))!=''):
					$mediafiles .= imageSelect($value, $toppath, $hidepath, $selected, $trimname, $buildforjs);
				endif;
			endforeach;
		endif;
		return $mediafiles;
		}	// imageSelect()
endif;

/**
 * ermittelt alle Media-Dateien und gibt ein aufbereitetes array zurueck
 * new since 2018-09-11
 * @return $mediafiles
 */
if (!(function_exists('mediaArray'))) {
	// path => startpfad der suche 
	// toppath => point in path, from which data will be returned as value (e.g. path = / , toppath = /images => return will start below /images)
	// hidepath => hide path in selection (show only filenames)
	// selected => array ausgewählter Dateien
	function mediaArray($path = '/media/', $toppath = '/media/', $hidepath = false, $selected = array(), $trimname = 100, $countlast = 10) {
		if (isset($_SESSION['wspvars']['stripfilenames']) && intval($_SESSION['wspvars']['stripfilenames'])>intval($trimname)):
			$trimname = intval($_SESSION['wspvars']['stripfilenames']);
		endif;
		// get hidemedia option
		$hide_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddenmedia'";
		$hide_res = doResultSQL($hide_sql);
        if ($hide_res && trim($hide_res)!=''):
			$hiddenmedia = explode(",",$hide_res);
			$hideoption = array(" `filepath` NOT LIKE '".cleanPath("/".$path."/thumbs/")."%' ");
			foreach ($hiddenmedia AS $k => $v):
				$hideoption[] = " `filepath` NOT LIKE '".cleanPath("/".$path."/".$v."/")."%' ";
			endforeach;
			$hidemedia = " AND (".implode(" AND ", $hideoption).") ";	
		endif;
		// prepare selected as array
		if (!(is_array($selected)) && trim($selected)!=''): $selected = array($selected); endif;
		// prepare toppath as path
		$toppath = cleanPath("/".$toppath."/");
		// unset mediafiles
		$lastmediaarray = array();
        $allmediaarray = array();
		// get last X uploads
		$lastsql = "SELECT `filepath`, `filename`, `filedesc` FROM `wspmedia` WHERE `filepath` != '' AND `filepath` LIKE '".$path."%' ".$hidemedia." ORDER BY `filedate` DESC, `filepath` ASC, `filename` ASC LIMIT 0,".$countlast;
		$lastres = doSQL($lastsql);
        if ($lastres['num']>0):
			$lastmediaarray[returnIntLang('str last uploaded media', false)] = array();
			foreach ($lastres['set'] AS $lrk => $lrv):
                $filepath = cleanPath(trim("/".$lrv['filepath']));
				$lastmediaarray[returnIntLang('str last uploaded media', false)][$filepath] = $lrv['filename'];
                if (strlen($lastmediaarray[returnIntLang('str last uploaded media', false)][$filepath])>$trimname):
					$lastmediaarray[returnIntLang('str last uploaded media', false)][$filepath] = substr($lrv['filename'],0,5)."...".substr($lrv['filename'],-($trimname-5));
				endif;
				if (trim($lrv['filedesc'])!=''):
					$lastmediaarray[returnIntLang('str last uploaded media', false)][$filepath] = trim($lrv['filedesc']);
				endif;
        	endforeach;
            ksort($lastmediaarray[returnIntLang('str last uploaded media', false)]);
        endif;
        $allsql = "SELECT `filepath`, `filename`, `filedesc` FROM `wspmedia` WHERE `filepath` != '' AND `filepath` LIKE '".$path."%' ".$hidemedia." ORDER BY `filename` ASC";
        $allres = doSQL($allsql);
        if ($allres['num']>0):
            foreach ($allres['set'] AS $ark => $arv):
                $showpath = cleanPath(str_replace($arv['filename'], '/', cleanPath(str_replace($toppath,'/',substr($arv['filepath'],0,strlen($toppath))).substr($arv['filepath'],strlen($toppath)))));
                $filepath = cleanPath(trim("/".$arv['filepath']));
                if (!(isset($allmediaarray[$showpath]))): $allmediaarray[$showpath] = array(); endif;
                $allmediaarray[$showpath][$filepath] = $arv['filename'];
                if (strlen($allmediaarray[$showpath][$filepath])>$trimname):
					$allmediaarray[$showpath][$filepath] = substr($arv['filename'],0,5)."...".substr($arv['filename'],-($trimname-5));
				endif;
				if (trim($arv['filedesc'])!=''):
					$allmediaarray[$showpath][$filepath] = trim($arv['filedesc']);
				endif;
            endforeach;
            ksort($allmediaarray);
        endif;
		return array_merge($lastmediaarray, $allmediaarray);
    }	// mediaArray()
}

if (!(function_exists('visibleMediaSelect'))):
    // creates a searchable select (if extensional js is loaded) 
    // $fieldName » name of form field
    // $fieldID » id of form field
    function visibleMediaSelect($fieldName, $fieldID, $path = '/media/', $toppath = '/media/', $hidepath = false, $selected = array(), $trimname = 100, $countlast = 10) {
        if (trim($fieldName)=='') { $fieldName = substr(md5(trim($path).time().rand()), 0, 12); }
        if (trim($fieldID)=='') { $fieldID = 'field_'.$fieldName; }
        if (!(is_array($selected))) { $selected = array(trim($selected)); }
        echo "<select name='".$fieldName."' id='".$fieldID."' class='form-control'>";
        echo mediaSelect('/media/screen/', '/media/screen/', false, $selected, 150, 0);
        echo "</select>";
    
    }
endif;

if (!(function_exists('mediaSelect'))):
    function mediaSelect($path = '/media/', $toppath = '/media/', $hidepath = false, $selected = array(), $trimname = 100, $countlast = 10) {
        if (!(is_array($selected))) { $selected = array(trim($selected)); }
        $mediaarray = mediaArray($path,$toppath,$hidepath,$selected,$trimname,$countlast);
        $mediaselect = '<option value=" ">'.returnIntLang('str please choose media', false).'</option>';
        if (count($mediaarray)>0):
            foreach ($mediaarray AS $mak => $mav):
                $mediaselect.= '<optgroup label="'.$mak.'">';
                foreach ($mav AS $mavk => $mavv):
                    $mediaselect.= '<option value="'.$mavk.'"';
                    if (in_array($mavk, $selected)): $mediaselect.= ' selected="selected" '; endif;
                    $mediaselect.= '>'.$mavv.'</option>';
                endforeach;
                $mediaselect.= '</optgroup>';
            endforeach;
        else:
            $mediaselect = '<option value=" ">'.returnIntLang('str no media avaiable', false).'</option>';
        endif;
        return $mediaselect;
    }
endif;

if (!(function_exists('mediaJSON'))):
    function mediaJSON($path = '/media/', $toppath = '/media/', $hidepath = false, $selected = array(), $trimname = 100, $countlast = 10) {
        $mediaarray = mediaArray($path,$toppath,$hidepath,$selected,$trimname,$countlast);
        $mediaselect = array();
        if (count($mediaarray)>0):
            foreach ($mediaarray AS $mak => $mav):
                $mediaselect[] = array(
                    'html',
                    '<optgroup label="'.$mak.'">',
                    );
                foreach ($mav AS $mavk => $mavv):
                    $mediaselect[] = array(
                        'option',
                        $mavk,
                        $mavv,
                    );
                endforeach;
                $mediaselect[] = array(
                    'html',
                    '</optgroup>',
                    );
            endforeach;
        endif;
        return $mediaselect;
    }
endif;



/**
 * ermittelt alle Media-Dateien und gibt sie fuer ein select aufbereitet zurueck
 *
 * @param string $path Unterverzeichnis, das aufgelistet werden soll
 * @return $mediafiles
 */
if (!(function_exists('getMediaDownload'))):
	function getMediaDownload($path = '/', $selected = array() , $toppath = '', $trimname = 40, $buildforjs = true) {
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
			while (false !== ($entry = $d->read())):
				if (substr($entry, 0, 1)!='.'):
					if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media'.$path.$entry)):
						$files[] = $path.$entry;
					elseif (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media'.$path.$entry) && str_replace("/","",trim($entry))!="thumbs" && str_replace("/","",trim($entry))!="flash" && str_replace("/","",trim($entry))!="screen"):
						$dir[] = $path.$entry;
					endif;
				endif;
			endwhile;
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
				$desc_res = mysql_query($desc_sql);
				if ($desc_res):
					$desc_num = mysql_num_rows($desc_res);
					if ($desc_num>0):
						$mediadesc = mysql_result($desc_res, 0, "filedesc");
					endif;
				endif;
				if (trim($toppath)!="" && $toppath!="/"):
					$value = str_replace($toppath, "", $value);
				endif;
				if (trim($mediadesc)!=""):
					$mediafiles .= $mediadesc;
				elseif (strlen($value)>$trimname):
					$mediafiles .= substr($value,0,5)."...".substr($value,-($trimname-5));
				else:
					$mediafiles .= $value;
				endif;
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
	function writeMySQLError($sql = "") {
		$_SESSION['wspvars']['errormsg'].= "<p>MySQL-Fehler ".mysql_errno().":</p>";
		$_SESSION['wspvars']['errormsg'].= "<p>".mysql_error()."<br />".$sql."</p>\n";
		$backtrace = debug_backtrace();
		$_SESSION['wspvars']['errormsg'].= "<p>";
		foreach ($backtrace as $trace) {
			$_SESSION['wspvars']['errormsg'].= "Fehler in Zeile ".$trace['line']." in Datei ".$trace['file']."<br />\n";
		}	// foreach
		$_SESSION['wspvars']['errormsg'].= "</p>\n";
	}	// writeMySQLError()
endif;

// logfile 
if (!(function_exists('addWSPLog'))) {
	function addWSPLog($uid = 0, $msgtype = 0, $msg = '', $status = 0, $id = false) {
		if ($id===false) {
            doSQL("INSERT INTO `wsplog` SET `uid` = ".intval($uid).", `type` = ".intval($msgtype).", `msg` = '".escapeSQL($msg)."', `status` = ".intval($status));
        } else {
            doSQL("UPDATE `wsplog` SET `status` = ".intval($status)." WHERE `id` = ".intval($id));
        }
    }
}

// output messages 
if (!(function_exists('addWSPMsg'))):
	function addWSPMsg($msgtarget, $msg = '', $paragraph = true) {
		if (isset($msgtarget) && $msgtarget!='' && array_key_exists($msgtarget, $_SESSION['wspvars'])):
			($paragraph) ? $_SESSION['wspvars'][$msgtarget].= "<p>".strip_tags($msg, '<strong><a><i><em><b>')."</p>" : $_SESSION['wspvars'][$msgtarget].= $msg;
		else:
			($paragraph) ? $_SESSION['wspvars'][$msgtarget] = "<p>".strip_tags($msg, '<strong><a><i><em><b>')."</p>" : $_SESSION['wspvars'][$msgtarget] = $msg;	
		endif;
		}
endif;

// output messages 
if (!(function_exists('showWSPMsg'))) {
	function showWSPMsg($msgtarget = 0) {
        if (isset($_SESSION['wspvars']['shownotice']) && intval($_SESSION['wspvars']['shownotice'])==1 && $msgtarget==1) {
            echo "<div class='row' id='showwspmsg'><div class='col-md-12'>";
            if (isset($_SESSION['wspvars']['noticemsg'])) {
                echo "<div class='alert alert-info alert-dismissible' role='alert'>";
                echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button>";
                echo "<i class='fa fa-info-circle'></i>";
                echo $_SESSION['wspvars']['noticemsg'];
                echo "</div>";
            }
            if (isset($_SESSION['wspvars']['errormsg'])) {
                echo "<div class='alert alert-danger alert-dismissible' role='alert'>";
                echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button>";
                echo "<i class='fa fa-warning'></i>";
                echo $_SESSION['wspvars']['errormsg'];
                echo "</div>";
            }
            if (isset($_SESSION['wspvars']['resultmsg'])) {
                echo "<div class='alert alert-success alert-dismissible' role='alert'>";
                echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button>";
                echo "<i class='fa fa-warning'></i>";
                echo $_SESSION['wspvars']['resultmsg'];
                echo "</div>";
            }
            if (defined('WSP_DEV') && WSP_DEV===true && isset($_SESSION['wspvars']['devmsg'])) {
                echo "<div class='alert alert-warning alert-dismissible' role='alert'>";
                echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button>";
                echo "<i class='fa fa-warning'></i>";
                echo $_SESSION['wspvars']['devmsg'];
                echo "</div>";
                unset($_SESSION['wspvars']['devmsg']);
            }
            unset($_SESSION['wspvars']['noticemsg']);
            unset($_SESSION['wspvars']['errormsg']);
            unset($_SESSION['wspvars']['resultmsg']);
            echo "</div></div>";
        } else if (!(isset($_SESSION['wspvars']['shownotice'])) || isset($_SESSION['wspvars']['shownotice']) && $_SESSION['wspvars']['shownotice']==0 && $msgtarget==0) {
			echo "<div id='alert-holder' style='position: fixed; top: 1vh; right: 1vw; width: 98%; max-width: 400px; display: block; z-index: 1999; min-height: 1px; max-height: 98vh; overflow-y: auto; display: none;'>";
            if (isset($_SESSION['wspvars']['noticemsg'])) {
                echo "<div class='alert alert-info alert-dismissible' role='alert'>";
                echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button>";
                echo "<i class='fa fa-info-circle'></i>";
                echo $_SESSION['wspvars']['noticemsg'];
                echo "</div>";
            }
            if (isset($_SESSION['wspvars']['errormsg'])) {
                echo "<div class='alert alert-danger alert-dismissible' role='alert'>";
                echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button>";
                echo "<i class='fa fa-warning'></i>";
                echo $_SESSION['wspvars']['errormsg'];
                echo "</div>";
            }
            if (isset($_SESSION['wspvars']['resultmsg'])) {
                echo "<div class='alert alert-success alert-dismissible' role='alert'>";
                echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button>";
                echo "<i class='fa fa-warning'></i>";
                echo $_SESSION['wspvars']['resultmsg'];
                echo "</div>";
            }
            if (defined('WSP_DEV') && WSP_DEV===true && isset($_SESSION['wspvars']['devmsg'])) {
                echo "<div class='alert alert-warning alert-dismissible' role='alert'>";
                echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>×</span></button>";
                echo "<i class='fa fa-warning'></i>";
                echo $_SESSION['wspvars']['devmsg'];
                echo "</div>";
                unset($_SESSION['wspvars']['devmsg']);
            }
            unset($_SESSION['wspvars']['noticemsg']);
            unset($_SESSION['wspvars']['errormsg']);
            unset($_SESSION['wspvars']['resultmsg']);
            echo "</div>";
		} else if ($msgtarget==2 && defined('WSP_DEV') && WSP_DEV) {
            if (isset($_SESSION['wspvars']['noticemsg'])) {
                echo "noticemsg<hr />";
                echo $_SESSION['wspvars']['noticemsg'];
                echo "<br/>";
                unset($_SESSION['wspvars']['noticemsg']);
            }
            if (isset($_SESSION['wspvars']['errormsg'])) {
                echo "errormsg<hr />";
                echo $_SESSION['wspvars']['errormsg'];
                echo "<br/>";
                unset($_SESSION['wspvars']['errormsg']);
            }
            if (isset($_SESSION['wspvars']['resultmsg'])) {
                echo "resultmsg<hr />";
                echo $_SESSION['wspvars']['resultmsg'];
                echo "<br/>";
                unset($_SESSION['wspvars']['resultmsg']);
            }
            if (isset($_SESSION['wspvars']['devmsg'])) {
                echo "devmsg<hr />";
                echo $_SESSION['wspvars']['devmsg'];
                echo "<br/>";
                unset($_SESSION['wspvars']['devmsg']);
            }
        }
    }
}

if (!(function_exists('showWSPWidget'))) {
    function showWSPWidget($widget, $showcheck = false) {
        
        $wvalue = array(
            'activepages' => intval(getNumSQL('SELECT `mid` FROM `menu` WHERE `trash` = 0')).'/<small>'.intval(getNumSQL('SELECT `mid` FROM `menu`')).'</small>',
            'activecontents' => intval(getNumSQL('SELECT `cid` FROM `content` WHERE `trash` = 0')).'/<small>'.intval(getNumSQL('SELECT `cid` FROM `content`')).'</small>',
            'freespace' => 'panel',
            'publishqueue' => intval(getNumSQL("SELECT `id` FROM `wspqueue` WHERE `done` = 0")),
            'imagecount' => intval(getNumSQL("SELECT `mid` FROM `wspmedia` WHERE `filepath` LIKE '/media/images/%'")),
            'documentcount' => intval(getNumSQL("SELECT `mid` FROM `wspmedia` WHERE `filepath` LIKE '/media/download/%'")),
            'gdlib' => preg_replace(array('/\D+/', '/\D+$/'), "", trim(gd_info()['GD Version']), 1),
            'phpversion' => phpversion(),
            'zend' => zend_version(),
            'uploadmax' => ini_get('upload_max_filesize'),
            'postmax' => ini_get('post_max_size'),
            'mysqlserver' => (isset($_SESSION['wspvars']['db'])?@mysqli_wsp_server_version():'-'),
            'mysqlclient' => (isset($_SESSION['wspvars']['db'])?@mysqli_wsp_client_version():'-'),
        );
        $wicon = array(
            'activepages' => 'far fa-sitemap',
            'activecontents' => 'far fa-file-alt',
            'imagecount' => 'far fa-image',
            'documentcount' => 'far fa-file',
            'gdlib' => 'fa fa-image',
            'phpversion' => 'fa fa-code',
            'zend' => 'fa fa-cubes',
            'uploadmax' => 'fa fa-upload',
            'postmax' => 'fa fa-tasks',
            'mysqlserver' => 'fa fa-tasks',
            'mysqlclient' => 'fa fa-tasks',
            'publishqueue' => 'fa fa-globe',
        );
        $wdesc = array(
            'activepages' => 'home widget activepages',
            'activecontents' => 'home widget activecontents',
            'imagecount' => 'home widget imagecount',
            'documentcount' => 'home widget documentcount',
            'publishqueue' => 'home widget publish queue',
            'gdlib' => 'system gdlib version',
            'phpversion' => 'system php version',
            'zend' => 'system zend version',
            'uploadmax' => 'system upload size',
            'postmax' => 'system post size',
            'mysqlserver' => 'system mysql server',
            'mysqlclient' => 'system mysql client',
        );
        $wtarget = array(
            'activepages' => './structure.php',
            'activecontents' => './contents.php',
            'imagecount' => './imagemanagement.php',
            'documentcount' => './documentmanagement.php',
            'publishqueue' => './publishqueue.php',
        );
        $wpanel = array(
            'freespace' => 'freespace.inc.php',
        );
        
        if (array_key_exists($widget, $wvalue)) {
            echo '<div class="col-md-'.((strlen(strip_tags($wvalue[$widget]))>15)?6:3).'">';
            echo '<div class="widget widget-metric_6">';
            if (isset($wpanel[$widget])) {
                require('./data/panels/'.$wpanel[$widget]);
            } 
            else if (isset($wvalue[$widget])) { 
                if (isset($wtarget[$widget])) {
                    echo '<a href="'.$wtarget[$widget].'"><span class="icon-wrapper custom-bg-yellow"><i class="'.$wicon[$widget].'"></i></span></a>';
                    echo '<div class="right">';
                    echo '<span class="value"><a href="'.$wtarget[$widget].'">'.$wvalue[$widget].'</a></span>';
                    echo '<span class="title">'.returnIntLang($wdesc[$widget]).'</span>';
                    echo '</div>';
                } else {
                    echo '<span class="icon-wrapper custom-bg-yellow"><i class="'.$wicon[$widget].'"></i></span>';
                    echo '<div class="right">';
                    echo '<span class="value">'.$wvalue[$widget].'</span>';
                    echo '<span class="title">'.returnIntLang($wdesc[$widget]).'</span>';
                    echo '</div>';
                }
            }
            if ($showcheck===true) {
                echo '<span style="position: absolute; top: 10px; right: 25px; "><input type="checkbox" name="widget['.$widget.']" value="1" '.((getWSPProperties('widget_'.$widget)==1)?' checked="checked" ':'').' onchange="updateWidget(\''.$widget.'\', this.checked);" /></span>';
                }
            echo '</div>';
            echo '</div>';
        }
    }
}

function getWSPqueue( $uid = null ) {
    // param uid if only user related publishing is requested
    $op_res = doSQL("SELECT DISTINCT `param` FROM `wspqueue` WHERE `done` = 0".(($uid!==null)?" AND `uid` = ".intval($uid):''));
    if (intval($op_res['num'])>0) {
        $param = array();
        foreach ($op_res['set'] AS $oprk => $oprv) {
            $param[] = intval($oprv['param']);
        }
        if (count($param)>0) {
            $q_res = doSQL("SELECT `id` FROM `wspqueue` WHERE `done` = 0".(($uid!==null)?" AND `uid` = ".intval($uid):'')." AND `param` IN ('".implode("','", $param)."')");
            return($q_res['num']);
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}

// copyImage()-function to copy one single image from modules, etc.
if (!(function_exists('copyImage'))):
	function copyImage($tmpdata = '', $tmpfilename = '') {
		if (trim($tmpdata)!='' && $tmpfilename!=''):
			// try ftp-login
			$ftp = ftp_connect($_SESSION['wspvars']['ftphost'], $_SESSION['wspvars']['ftpport']);
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

// thumbnail function with gdlib
if (!(function_exists('resizeGDimage'))) {
    function resizeGDimage($orig, $dest, $factor=0, $width=0, $height=0, $format=1) {
//      $imgsize[0] = orig width
//      $imgsize[1] = orig height
//      $imgsize['mime'] = type
        $imgsize = getimagesize($orig);
        $error = false;
        if ($imgsize['mime']=="image/jpeg") {
            $img = imagecreatefromjpeg($orig);
        } else if ($imgsize['mime']=="image/gif") {
            $img = imagecreatefromgif($orig);
        } else if ($imgsize['mime']=="image/png") {
            $img = imagecreatefrompng($orig);
        } else {
            $error = true;
            addWSPMsg('errormsg', returnIntLang('resizegdimage filetype is not supported'));
        }
        if (intval($imgsize[0])==0 || intval($imgsize[1])==0) {
            $error = true;
            addWSPMsg('errormsg', returnIntLang('resizegdimage file dimensions error'));
        }
        if (intval($factor)>0) {
            // faktorierte skalierung
            $newwidth = ceil((intval($factor)/100)*intval($imgsize[0]));
            $newheight = ceil((intval($factor)/100)*intval($imgsize[1]));
        }
        else if ((intval($width)>0 || intval($height)>0) && $format==1) {
            // breite und/oder hoehe gegeben und format bleibt erhalten
            if (intval($width)>0 && intval($height)==0):
                // breite gegeben
                $newwidth = intval($width);
                $scale = $newwidth/intval($imgsize[0]);
                $newheight = ceil($scale*intval($imgsize[1]));
            elseif (intval($width)==0 && intval($height)>0):
                // hoehe gegeben
                $newheight = intval($height);
                $scale = $newheight/intval($imgsize[1]);
                $newwidth = ceil($scale*intval($imgsize[0]));
            elseif (intval($width)>0 && intval($height)>0):
                $newwidth = intval($width);
                $scale = $newwidth/intval($imgsize[0]);
                $newheight = ceil($scale*intval($imgsize[1]));
                if ($newheight>$height):
                    $newheight = intval($height);
                    $scale = $newheight/intval($imgsize[1]);
                    $newwidth = ceil($scale*intval($imgsize[0]));
                endif;
            else:
                $error = true;
                addWSPMsg('errormsg', returnIntLang('resizegdimage conversion error - file dimensions'));
            endif;
        }
        else if (intval($width)>0 && intval($height)>0) {
            // breite und hoehe gegeben
            $newwidth = intval($width);
            $newheight = intval($height);
        }
        else {
            $error = true;
            addWSPMsg('errormsg', returnIntLang('resizegdimage conversion error - scaling'));
        }
        // reset new dimensions if larger than original
        if ($newwidth>intval($imgsize[0]) && $newheight>intval($imgsize[1])) {
            $newwidth = intval($imgsize[0]);
            $newheight = intval($imgsize[1]);
        }
        if (!$error && $img) {
            if ($imgsize['mime']=="image/gif") {
                $new = imagecreate($newwidth, $newheight);
                imagecopyresized($new, $img, 0, 0, 0, 0, $newwidth, $newheight, $imgsize[0], $imgsize[1]);
                imagepng($new, $dest, 5);
                return true;
            }
            else if ($imgsize['mime']=="image/png") {
                // creating jpg-type cause error with transparent pngs
                $colortype = imagecolorstotal($img);
                $new = imagecreatetruecolor($newwidth, $newheight);
                imagealphablending($new, false );
                imagesavealpha($new, true );
                imagecopyresized($new, $img, 0, 0, 0, 0, $newwidth, $newheight, $imgsize[0], $imgsize[1]);
                imagepng($new, $dest, 5);
                return true;
            } 
            else {
                $new = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($new, $img, 0, 0, 0, 0, $newwidth, $newheight, $imgsize[0], $imgsize[1]);
                imagepng($new, $dest, 5);
                return true;
            }
        } else {
            return false;
        }
	}
}

// check serialized arrays for broken contents and repair them
// thx to martin dordel for developing this function
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

if (!(function_exists('checkandsendMail'))) {
	function checkandsendMail($maildata = array()) {
		$status = false;
		if(count($maildata)>0) {
			$maildata['useHTML'] = ((isset($maildata['useHTML']) && intval($maildata['useHTML'])==1)?true:false);
            $maildata['doHTML'] = true;
            // check for files to use HTML-Mode
            if ($maildata['useHTML']) {
                // lookup phpmailer
                if (!(is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/include/phpmailer/PHPMailer.php"))) {
                    $maildata['doHTML'] = false;
                }
                if (!(is_file(DOCUMENT_ROOT."/".WSP_DIR."/data/templates/htmlmail.html"))) {
                    $maildata['doHTML'] = false;
                }
            }
			if ($maildata['useHTML'] && $maildata['doHTML']) {	
            
//              require DOCUMENT_ROOT."/".WSP_DIR."/data/include/phpmailer/Exception.php";
                require DOCUMENT_ROOT."/".WSP_DIR."/data/include/phpmailer/PHPMailer.php";
                require DOCUMENT_ROOT."/".WSP_DIR."/data/include/phpmailer/SMTP.php";
				$mail = new PHPMailer\PHPMailer\PHPMailer;
		
                $mail->IsSMTP(); // per SMTP verschicken
                $mail->Host = SMTP_HOST; // SMTP-Server
                $mail->SMTPAuth = true; // SMTP mit Authentifizierung benutzen
                $mail->Username = SMTP_USER; // SMTP-Benutzername
                $mail->Password = SMTP_PASS; // SMTP-Passwort
                $mail->Port = SMTP_PORT;
                
                $mail->CharSet = 'UTF-8';
                $mail->MessageID = 'wsp'.date('YmdHis').md5(SMTP_HOST); 
                
                if (isset($maildata['mailHTML']) && trim($maildata['mailHTML'])!='') {
                    $mail->IsHTML(true);
                    $html = file_get_contents(DOCUMENT_ROOT."/".WSP_DIR."/data/templates/htmlmail.html");
                    $html = str_replace('[%CONTENT%]', $maildata['mailHTML'], $html);
                    $html = str_replace('[%SUBJECT%]', setUTF8($maildata['mailSubject']), $html);
                    $mail->Body = $html;
                    if (isset($maildata['mailTXT']) && trim($maildata['mailTXT'])!='') {
                        $mail->AltBody = $maildata['mailTXT'];
                    }
                }
                else {
                    $mail->IsHTML(false);
                    $mail->Body = setUTF8(trim($maildata['mailTXT']));
                }
				$mail->From = trim($maildata['mailFrom'][0]);
				$mail->FromName = setUTF8(trim($maildata['mailFrom'][1]));
				
                if (isset($maildata['mailTo']) && is_array($maildata['mailTo'])) { 
                    for ($to=0;$to<count($maildata['mailTo']);$to++) {
                        $mail->AddAddress(trim($maildata['mailTo'][$to][0]),trim($maildata['mailTo'][$to][1]));
                    }
                } 
                else if (defined('BASEMAIL')) {
                    $mail->AddAddress(BASEMAIL);
                    $maildata['mailSubject'].= ' [NO OR FALSE RECIPIENT DEFINED]';
                }
				
                /*
                if (isset($maildata['mailCC']) && is_array($maildata['mailCC'])) { for($cc=0;$cc<count($maildata['mailCC']);$cc++) {
                    if (isset($maildata['mailCC'][$cc][0])) {
                        $mail->AddCC(trim($maildata['mailCC'][$cc][0]),(isset($maildata['mailCC'][$cc][1])?trim($maildata['mailCC'][$cc][1]):''));
                    }
                }}
				if (isset($maildata['mailBCC']) && is_array($maildata['mailBCC'])) { for($bcc=0;$bcc<count($maildata['mailBCC']);$bcc++) { if (isset($maildata['mailBCC'][$bcc][0])) { $mail->AddBCC(trim($maildata['mailBCC'][$bcc][0]),(isset($maildata['mailBCC'][$bcc][1])?trim($maildata['mailBCC'][$bcc][1]):'')); }}}
                */
                
				$mail->WordWrap = 50; // Zeilenumbruch einstellen
				// $mail->AddAttachment("/var/tmp/file.tar.gz");      // Attachment
				// $mail->AddAttachment("/tmp/image.jpg", "new.jpg");
				$mail->Subject  = setUTF8($maildata['mailSubject']);

                if ($mail->Send()) {
                    $status = true;
                } 
                else {
                    $status = false;
                    addWSPMsg('errormsg', returnIntLang('checkandsendmail could not use phpmailer()', false));
                }
            }
            // else try sending by mail function
			else {
                $message[0] = trim($maildata['mailTo'][0][1]). " <" . trim($maildata['mailTo'][0][0]).">";
				$message[1] = $maildata['mailSubject'];
				$message[2] = $maildata['mailTXT'];
				$message[3] = "";
				if($maildata['mailReturnPath']!=""):
					$message[3].= "Return-Path: <" . trim($maildata['mailReturnPath']) .">\n";
				endif;
				$message[3].= "X-Mailer: WSP Mailer\n";
				$message[3].= "MIME-Version: 1.0\n";
				$message[3].= "From: " . trim($maildata['mailFrom'][1]) . " <" . trim($maildata['mailFrom'][0]) .">\n";
				$message[3].= "Reply-To: " . trim($maildata['mailReply'][0]) . " <" . trim($maildata['mailReply'][0]) .">\n";
				$message[3].= "Content-Transfer-Encoding: quoted-printable\n";
				$message[3].= "Content-Type: text/plain; charset=UTF-8\n\n";
				if(mail($message[0],$message[1],$message[2],$message[3])) {
					$status = true;
				} else {
                    $status = false;
                    addWSPMsg('errormsg', returnIntLang('checkandsendmail could not use mail()', false));
                }
			}
		}
		return $status;
	}
}

if (!(function_exists('createfilename'))):
	function createfilename($newmenuitem = "index", $subfromitem = 0) {
		$newmenuitem = strtolower(removeSpecialChar($newmenuitem)); // convert filename
		$usedname_sql = "SELECT * FROM `menu` WHERE `filename`= '" . $newmenuitem . "' AND `connected` = " . $subfromitem;
		$usedname_res = mysql_query($usedname_sql);
		if ($usedname_res):
			$usedname_num = mysql_num_rows($usedname_res);
			if ($usedname_num>0):
				$nameok= false;
				while(!$nameok):
					$newmenuitem.= "1";
					$usedname_sql = "SELECT * FROM `menu` WHERE `filename`= '" . $newmenuitem . "' AND  `connected` = " . $subfromitem;
					$usedname_res = mysql_query($usedname_sql);
					if ($usedname_res):
						$usedname_num = mysql_num_rows($usedname_res);
						if ($usedname_num==0):
							$nameok = true;
						endif;
					else:
						$nameok = true;
					endif;
				endwhile;
			endif;
		endif;

		return $newmenuitem;
	}
endif;



if (!(function_exists("checkTree"))):
	function checkTree($basedir, $src){
		$aDirFile = array();
		$dh = dir($basedir.$src);
		while (false !== ($entry = $dh->read())):
			if (($entry != '.') && ($entry != '..')) {
				if (is_dir($basedir."/".$src."/".$entry)) {
					$aDirFile = array_merge($aDirFile, checkTree($basedir, $src."/".$entry));
				}elseif (is_file($basedir."/".$src."/".$entry)){
					$aDirFile[] = $src;
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
// returns real path to given mid
if (!(function_exists('fileNamePath'))) {
    function fileNamePath($mid, $depth = 0, $index = 0, $show = 1) {
        // depth = 0 => call as env params
        // depth = 1 => call as file
        // depth = 2 => call as directory
        // depth = 11 => call as file, but only ... 
        // depth = 12 => call as directory, but only ...
        // index = 0 => isindex from db
        // index = 1 => override isindex and return original filename
        // show = 1
        // 
        // returns a string with filename if show = 1, but an array with all files, that have to be written, if show = 0
        // 
        $parsedir = intval(getWSPProperties('parsedirectories'));
        $filetype = trim(getWSPProperties('parsetype')); // not yet introduced so we'll set this to '.php'
        $filetype = '.php';
        // set explicit as PARSEDIR if param is defined 
        if ($parsedir>0 || $depth==2 || $depth==12) { $parsedir=1; }
        if ($depth==1 || $depth==11) { $parsedir=0; }
        $path_sql = "SELECT `connected`, `filename`, `isindex`, `level` FROM `menu` WHERE `mid` = ".intval($mid);
        $path_res = doSQL($path_sql);
        if ($path_res['num']>0) {
            // if isindex = 1 AND parse as directories is activated, but parent file has ANY visible contents this MUST override the isindex-rule
            $topcnt_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($path_res['set'][0]['connected'])." AND `trash` = 0 AND `visibility` != 0";
            $topcnt_res = intval(getNumSQL($topcnt_sql));
            if ($topcnt_res>0 && $parsedir==1) {
                $path_res['set'][0]['isindex'] = 0;
            }
            // override isindex by given function param
            if ($index==1) {
                $path_res['set'][0]['isindex'] = 0;
            }
            if ($path_res['set'][0]['level']>1 && $parsedir==1) {
                $path_res['set'][0]['isindex'] = 0;
            }
            if ($parsedir==0) {
                // for first call in row AND parsedir = false create filename with correct ending
                if (intval($path_res['set'][0]['isindex'])==1) {
                    $file = '/'."index".$filetype;
                } else {
                    $file = '/'.$path_res['set'][0]['filename'].$filetype;
                }
            } else {
                if (intval($path_res['set'][0]['isindex'])==1) {
                    $file = '/';
                } else {
                    $file = '/'.$path_res['set'][0]['filename']."/";
                }
            }
            $parent = returnIDTree(intval($mid));
            foreach ($parent AS $pk => $pv) {
                if ($pv>0) {
                    $fn_sql = "SELECT `filename` FROM `menu` WHERE `mid` = ".intval($pv);
                    $fn_res = doResultSQL($fn_sql);
                    if (trim($fn_res)!='') {
                        $file = '/'.$fn_res.'/'.$file;
                    }
                }
            }
            if ($show===false || $show==0) {
                if ($parsedir==1) {
                    $files = array();
                    $files['orig'] = fileNamePath($mid, 1, 1, 1);
                    $files['file'] = fileNamePath($mid, 1, 0, 1);
                    $files['filefolder'] = cleanPath(fileNamePath($path_res['set'][0]['connected'], 0, 1, 1));
                    if ($path_res['set'][0]['level']==1 && $path_res['set'][0]['isindex']==1) {
                        $files['folderfile'] = "/index.php";
                    }
                    else {
                        $files['folderfile'] = cleanPath(fileNamePath($mid, 0, 1, 1)."/index.php");
                    }
                    $files['folder'] = cleanPath(fileNamePath($mid, 0, 1, 1));
                } else {
                    $files = array();
                    $files['orig'] = fileNamePath($mid, 1, 1, 1);
                    $files['file'] = fileNamePath($mid, 1, 0, 1);
                    $files['filefolder'] = cleanPath(fileNamePath($path_res['set'][0]['connected'], 2, 1, 1));
                    $files['folderfile'] = cleanPath(fileNamePath($mid, 2, 1, 1)."/index.php");
                    $files['folder'] = cleanPath(fileNamePath($mid, 0, 1, 1));
                }
                if (trim($files['filefolder'])=='' && trim($files['file'])=='/index.php') {
                    $files['path'] = "/";
                } else {
                    $files['path'] = str_replace("/index.php", "/", $files['folderfile']);
                }
                return $files;
            } 
            else {
                return cleanPath($file);
            }
        } else {
            if ($show===false || $show==0) {
                return false;
            }
            else {
                return '';
            }
        }
    }
}

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



if (!(function_exists('returnPath'))):
	function returnPath($mid, $depth = 0, $basepath = '', $baselang = 'de') {
		// depth 0 => rueckgabe des pfades bis hin zum hoeheren verzeichnis
		// depth 1 => rueckgabe des pfades bis hin zum verzeichnis
		// depth 2 => rueckgabe des pfades bis hin zur datei
        // depth 3 => rueckgabe des pfades bis hin zum hoeheren verzeichnis ab parent dir
		$path_sql = "SELECT `connected`, `filename`, `isindex`, `level` FROM `menu` WHERE `mid` = ".intval($mid);
		$path_res = doSQL($path_sql);
		if ($path_res['num']>0):
			$parent = intval($path_res['set'][0]['connected']);
			$fullpath = array();
			$fullfile = array();
			$p = 0; $cpath = true;
			while ($cpath===true):
				$fullpath[$p] = trim($path_res['set'][0]["filename"]);
				if (intval($path_res['set'][0]['isindex'])==1 && intval($path_res['set'][0]["level"])>1): 
					$fullfile[$p] = 'index';
				elseif (intval($path_res['set'][0]['isindex'])==1 && intval($path_res['set'][0]["connected"])==0):
					$fullfile[$p] = 'index';
				else:
					$fullfile[$p] = trim($path_res['set'][0]["filename"]);
				endif;
        		if (intval($parent)==0):
					$cpath = false;
				endif;
				$spath_sql = "SELECT `connected`, `filename`, `isindex`, `level` FROM `menu` WHERE `mid` = ".intval($parent);
				$spath_res = doSQL($spath_sql);
				if ($spath_res['num']>0): 
                    $parent = intval($spath_res['set'][0]['connected']); 
                else: 
                    $parent = 0; 
                    $cpath = false; 
                endif;
				$p++;
			endwhile;
			
        // BAUSTELLE
        
            $fullpath = array_reverse($fullpath);
			$givebackpath = '';
			if ($depth==0):
				$throwdir = array_pop($fullpath);
				$givebackpath = cleanPath($basepath."/".implode("/", $fullpath)."/");
			elseif ($depth==1):
				$givebackpath = cleanPath($basepath."/".implode("/", $fullpath)."/");
			elseif ($depth==2):
				$throwdir = array_pop($fullpath);
				$givebackpath = cleanPath($basepath."/".implode("/", $fullpath)."/".array_shift($fullfile).".php");
			else:
				$givebackpath = cleanPath($basepath."/");
			endif;
		else:
			$givebackpath = cleanPath($basepath."/");
		endif;
		// setting up language information
		if ($baselang!='de'):
			$givebackpath = cleanPath("/".$baselang."/".$givebackpath);
		endif;
		return cleanPath($givebackpath);
	}	// returnPath()
endif;


// returns path from Interpreter to given mid in shortened publisher style
if (!(function_exists('returnPublisherPath'))) {
    function returnPublisherPath($mid, $baselang = 'de') {
		// just check, if mid is set in database
		$mid_sql = "SELECT `offlink`, `externtarget`, `filename` FROM `menu` WHERE `mid` = ".intval($mid);
		$mid_res = doSQL($mid_sql);
        $parsedir = intval(getWSPProperties('parsedirectories'));
		$offlink = ''; if ($mid_res['num']>0) { if (trim($mid_res['set'][0]['offlink'])!='') { $offlink = trim($mid_res['set'][0]['offlink']); }}
		if ($offlink!='') {
			$givebackpath = $offlink;
		}
        else if (isset($_SESSION['preview']) && intval($_SESSION['preview'])==1) {
			$givebackpath = '?previewid='.intval($mid).'&previewlang='.trim($_SESSION['previewlang']);
		} 
        else if ($mid_res['num']>0 && $parsedir==1) {
            $givebackpath = returnPath(intval($mid), 1, '', $baselang);
        }
        else if ($mid_res['num']>0 && $parsedir==0) {
            $givebackpath = returnPath(intval($mid), 2, '', $baselang);
        }
        else {
            $givebackpath = "/";
        }
		return $givebackpath;
	}	// returnInterpreterPath()
}

// returns path from Interpreter to given mid
if (!(function_exists('returnInterpreterPath'))) {
    function returnInterpreterPath($mid, $baselang = 'de') {
		// just check, if mid is set in database
		$mid_sql = "SELECT `offlink`, `externtarget`, `filename` FROM `menu` WHERE `mid` = ".intval($mid);
		$mid_res = doSQL($mid_sql);
        $parsedir = intval(getWSPProperties('parsedirectories'));
		$offlink = ''; if ($mid_res['num']>0) { if (trim($mid_res['set'][0]['offlink'])!='') { $offlink = trim($mid_res['set'][0]['offlink']); }}
		if ($offlink!='') {
			$givebackpath = $offlink;
		}
        else if (isset($_SESSION['preview']) && intval($_SESSION['preview'])==1) {
			$givebackpath = '?previewid='.intval($mid).'&previewlang='.trim($_SESSION['previewlang']);
		} 
        else if ($mid_res['num']>0 && $parsedir==1) {
            $givebackpath = returnPath(intval($mid), 1, '', $baselang);
        }
        else if ($mid_res['num']>0 && $parsedir==0) {
            $givebackpath = returnPath(intval($mid), 2, '', $baselang);
        }
        else {
            $givebackpath = "/";
        }
		return $givebackpath;
	}	// returnInterpreterPath()
}

// returns path from Interpreter to given mid
if (!(function_exists('returnLinkedText'))) {
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
				$sql = "SELECT `filename` FROM `menu` WHERE `mid` = ".intval($linkID);
				$res = mysql_query($sql);
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
		// replace links to pages
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
		return $text;
	}	// returnLinkedText()
}

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
			for ($s=0; $s<=$vstep; $s++):
				if (!(isset($old[$s]))): $old[$s] = 0; endif;
				if (!(isset($new[$s]))): $new[$s] = 0; endif;
				if (intval($old[$s])<intval($new[$s])):
					$newer = 1;
					break;
				elseif (intval($old[$s])>intval($new[$s])):
					break;
				endif;
			endfor;
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

// ftp functions

// delete ftp-structure below given directory
if (!(function_exists("ftpDeleteDir"))):
function ftpDeleteDir($dir, $output = true) {
	// create ftp-connection
	$ftphdl = @ftp_connect($_SESSION['wspvars']['ftphost'], $_SESSION['wspvars']['ftpport']);
	// check for successful ftp-connection
	if ($ftphdl):
		$login = @ftp_login($ftphdl, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass']);
		if ($login):
			// delete directory
			ftp_chdir($ftphdl, $_SESSION['wspvars']['ftpbasedir']);
		    if ($output && @ftp_rmdir($ftphdl, $dir)):
				addWSPMsg('noticemsg', returnIntLang('ftp removed dir1', false)." ".str_replace($_SESSION['wspvars']['ftpbasedir'], "", str_replace("//", "/", $dir))." ".returnIntLang('ftp removed dir2', false));
		    elseif ($output):
				addWSPMsg('errormsg', returnIntLang('ftp could not remove dir1', false)." ".str_replace($_SESSION['wspvars']['ftpbasedir'], "", str_replace("//", "/", $dir))." ".returnIntLang('ftp could not remove dir2', false));
		    endif;
			// close ftp-connection
		elseif ($output):
			addWSPMsg('errormsg', returnIntLang('ftp could not login to host', false));
		endif;
		ftp_quit($ftphdl);
	elseif ($output):
		addWSPMsg('errormsg', returnIntLang('ftp could not connect to host', false));
	endif;
	}	// ftpDeleteDir()
endif;

// delete files by ftp
if (!(function_exists("ftpDeleteFile"))):
function ftpDeleteFile($file, $output = true) {
	// converts file-path to an absolute path and sets THEN relative to FTP_BASE
    $file = cleanPath(FTP_BASE."/".cleanPath($file));
    // create ftp-connection
	$ftphdl = @ftp_connect(FTP_HOST, FTP_PORT);
	// check for successful ftp-connection
	if ($ftphdl):
		$login = @ftp_login($ftphdl, FTP_USER, FTP_PASS);
		if ($login):
			// remove file and do return optional info
			if ($output && @ftp_delete($ftphdl, $file)):
				addWSPMsg('noticemsg', returnIntLang('ftp removed file1', false)." ".cleanPath("/".str_replace(FTP_BASE, "", $file))." ".returnIntLang('ftp removed file2', false));
			elseif ($output):
				addWSPMsg('errormsg', returnIntLang('ftp could not remove file1', false)." ".cleanPath("/".str_replace(FTP_BASE, "", $file))." ".returnIntLang('ftp could not remove file2', false));
			endif;
		elseif ($output):
			addWSPMsg('errormsg', returnIntLang('ftp could not login to host', false));
		endif;
		// closing ftp-connection
		ftp_quit($ftphdl);
	elseif ($output):
		addWSPMsg('errormsg', returnIntLang('ftp could not connect to host', false));
	endif;
	}	// ftpDeleteFile()
endif;


if (!function_exists('buildModMenu')):
	function buildModMenu($parent, $spaces, $rights) {
		$modmenu = null;
        $checkrights = array();
		if (is_array($rights)):
			foreach ($rights AS $key => $value) {
				if ($value==1): $checkrights[] = $key; endif;
			}
		endif;
		$wspmenu_sql = "SELECT 
            `id`, `title`, `link`, `parent_id`, `position`, `guid` FROM `wspmenu` WHERE `parent_id` = ".intval($parent)." ORDER BY `position`, `title`";
		$wspmenu_res = doSQL($wspmenu_sql);
		if ($wspmenu_res['num']>0) {
            foreach ($wspmenu_res['set'] AS $wrk => $wrv) {
                // rights set to user OR user is admin
                if (in_array($wrv["guid"], $checkrights) || (isset($_SESSION['wspvars']['usertype']) && $_SESSION['wspvars']['usertype']==1)) {
                    $modmenu[($wrv["guid"])][] = array(
//                        'spaces' => $spaces,
                        'id' => intval($wrv['id']),
//                        'parent' => intval($wrv['parent_id']),
                        'title' => trim($wrv['title']),
//                        'link' => trim($wrv['link']),
                        'sub' => buildModMenu(intval($wrv['id']), ($spaces+1), $rights)
                    );
                }
            }
        }
        return $modmenu;
    }
endif;

if (!(function_exists('showMenuDesign'))) {
    // creates code array from string based definition db entry
    function showMenuDesign($code) {
        $coderows = explode("LEVEL", $code);
        $menucode = array();
        $level_buf = 0;
        foreach ($coderows AS $levelvalue):
        if (trim($levelvalue) != ""):			
			$levelrows = explode("\n", str_replace("[","", str_replace("]","", str_replace("{","", str_replace("}","", stripslashes(trim($levelvalue)))))));
			if (trim($levelrows[0]) != ""):
				$level_buf = trim($levelrows[0]);
			else:
				$level_buf++;				
			endif;
			$menucode[($level_buf-1)] = array();
			foreach ($levelrows AS $codevalue):
				if (trim($codevalue) != ""):
					$codeset = explode("=", trim($codevalue));
					if (isset($codeset[1])) $menucode[($level_buf-1)][(trim($codeset[0]))] = str_replace("'", "", trim($codeset[1])); // 7.5.2015
				endif;
			endforeach;
		endif;
	endforeach;
	return $menucode;
	} 
}

// deprecated 2018-09-11
if (!(function_exists('getImageFiles'))):
function getImageFiles($path = '/', $selected = array(), $toppath = '', $trimname = 40, $buildforjs = true) {
    $mediafiles = '<option value="">Please use function mediaSelect()</option>';
    return $mediafiles;
    }	// getImageFiles()
endif;

// deprecated 2018-09-11
if (!(function_exists('getDownloadFiles'))):
function getDownloadFiles($path='/', $selected = array() , $toppath = '', $trimname = 40, $buildforjs = true) {
    $mediafiles = '<option value="">Please use function mediaSelect()</option>';
    return $mediafiles;
    }	// getDownloadFiles()
endif;

// deprecated 2018-09-11
if (!(function_exists('getFlashFiles'))):
function getFlashFiles( $path='/' , $selected = array() , $toppath = '' , $trimname = 40 , $buildforjs = true) {
    $mediafiles = '<option value="">Please use function mediaSelect()</option>';
    return $mediafiles;
    }	// getFlashFiles()
endif;
