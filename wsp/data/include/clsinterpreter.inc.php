<?php
/**
 * basic interpreter class
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-11-05
 */

if (!class_exists("clsInterpreter")):
	class clsInterpreter {
		var $title = "";
		var $classname = "";
		var $htmlmode = "yes";
		var $parsefile = "";
		var $maxfields = 1;
		var $version = "";
		var $guid = "";
		var $multilang = false;
		var $flexible = false;
        var $multifields = false;
        var $dbopen = false;
		// array();
		var $xajax = false;
		// true || false
	
		// get content title for preview
		function getView($value, $mid, $cid) {
            
            if (!(is_array($value))) { $value = unserializeBroken(trim($value)); }
            $mid = (isset($mid))?intval($mid):0; $cid = (isset($cid))?intval($cid):0;
            
			return false;
			} // getView()
	
        // dynamic values
        function getDynamicValues() {
            return "<em>This Interpreter doesn't support dynamic values.</em>";
            // should return an array with all field names as normaly written in following syntax
            // name="field[fieldname]" for all form elements
            // so a return for input with name field[description] must be 
            //
            // return array('description');
        }
        
        // host, db, user, pass 
        function openInterpreterDB($interpreterHost, $interpreterDB, $interpreterUser, $interpreterPass) { 
            if (isset($_SESSION['wspvars']['db'])) {
                if (mysqli_ping($_SESSION['wspvars']['db'])) {
                    // close existing db-connection
                    $mysqli = mysqli_init();
                    // suppression of error is required to prevent from warning output
                    if (@(!$mysqli->real_connect($interpreterHost, $interpreterUser, $interpreterPass, $interpreterDB))) {
                        $this->dbopen = false;
                    } else {
                        mysqli_close($_SESSION['wspvars']['db']);
                        $this->dbopen = true;
                        $_SESSION['wspvars']['db'] = $mysqli->real_connect($interpreterHost, $interpreterUser, $interpreterPass, $interpreterDB);
                    }
                }
            }
        }
        
        // no args required
        function closeInterpreterDB() {
            if ($this->dbopen===true) {
                mysqli_close($_SESSION['wspvars']['db']);
                $_SESSION['wspvars']['db'] = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                $this->dbopen = false;
            }
        }

        function getMultiHeader($value, $mid = 0, $cid = 0, $area = 0) {
            return false;
            // returns array with title and option to show opener/closer
            //
            // $header = array(
            //    array('Darstellungsoptionen', false),
            //    array('Dateiliste', false)
            // )
            // return $header[$area];
        }
        
        function getMultiEdit($value, $mid, $cid, $area) {
            return "<em>This Interpreter doesn't support multiple edit fields.</em>";
        }

		// edit (single) contents
		function getEdit($value, $mid, $cid) {
			
            if (!(is_array($value))) { $value = unserializeBroken(trim($value)); }
            $mid = (isset($mid))?intval($mid):0;
            $cid = (isset($cid))?intval($cid):0;
            
			ob_start();
	
			?>
			<div class="row">
                <div class="col-md-3"><p><?php echo returnIntLang('str content', false); ?></p></div>
                <div class="col-md-9"><textarea name="field['content']" id="field_content"><?php echo $value['content']; ?></textarea></div>
            </div>
			<?php
			
			$edit = ob_get_contents();
			ob_end_clean();
	
			return $edit;
			} // getEdit()
		
		// prepare data to save
		function getSave() { return serialize($_POST['field']); }
		
		// returns an array with all predefined xajax functions used from this interpreter
		// deprecated since WSP 7.0
        // will be removed in future versions
        function getXAJAXfuncs() {
			return false;
			} // getXAJAXfuncs()
		
        // return some header information
        // returns data that will be placed in PHP-Section on top of published file
        function getHeader($value, $mid, $cid, $publishlang) {
            
            if (!(is_array($value))) { $value = unserializeBroken(trim($value)); }
            $mid = (isset($mid))?intval($mid):0; $cid = (isset($cid))?intval($cid):0;
            $publishlang = (isset($publishlang) && trim($publishlang)!='')?trim($publishlang):(defined('WSP_LANG')?WSP_LANG:'de');
            
            return false;
        }
        
		// parse contents
		function getContent($value, $mid, $cid, $publishlang) {
			
            if (!(is_array($value))) { $value = unserializeBroken(trim($value)); }
            $mid = (isset($mid))?intval($mid):0; $cid = (isset($cid))?intval($cid):0;
            $publishlang = (isset($publishlang) && trim($publishlang)!='')?trim($publishlang):(defined('WSP_LANG')?WSP_LANG:'de');
            
			$value = unserializeBroken($value);

            $parser = "<!-- getContent() to CID ".$cid." in MID ".$mid." for lang ".$publishlang." is no set -->";
			$parser.= $value['content'];
						
			return $parser;
	
			} // getContent()
	
	}	// class clsInterpreter
endif;

?>
