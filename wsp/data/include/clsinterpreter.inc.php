<?php
/**
 * basic interpreter class
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.9
 * @lastchange 2020-05-07
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
		var $phpvars = false;
		var $bodyfunc = false;
		var $metascript = false;
		var $multilang = false;
		var $flexible = false;
        var $dbopen = false;
		var $xajax = false;
	
		// get content title for preview
		function getView($value, $mid, $cid) {
			return NULL;
			} // getView()
	   
        // holds session-based variables through all contents on page
        function getPage($value, $mid) {
            if (!(isset($_SESSION['getpage']))) { $_SESSION['getpage'] = array(); }
            if (!(isset($_SESSION['getpage'][$this->guid]))) { $_SESSION['getpage'][$this->guid] = array(); }
            if (!(isset($_SESSION['getpage'][$this->guid][intval($mid)]))) { $_SESSION['getpage'][$this->guid][intval($mid)] = array(); }
            $_SESSION['getpage'][$this->guid][intval($mid)][] = unserializeBroken($value);
        }
        
        function getDynamicValues() {
            echo "<em>This Interpreter doesn't support dynamic values.</em>";
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
                    mysqli_close($_SESSION['wspvars']['db']);
                }
                // create a new connection to given param
                $_SESSION['wspvars']['db'] = new mysqli($interpreterHost, $interpreterUser, $interpreterPass, $interpreterDB);
                if (mysqli_ping($_SESSION['wspvars']['db'])!==true) {
                    addWSPMsg('errormsg', "Interpreter \"".$this->title."\" could not connect to it's desired database. The Interpreter file may not work properly.");
                } else {
					$this->dbopen = true;
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
        
		//
		// edit contents
		//
		function getEdit($value, $mid, $cid) {
			
			ob_start();
	
			?>
			<script type="text/javascript" language="javascript">
			<!--
				function submitForm() {
				}	// submitForm()
			//-->
			</script>
			<fieldset>
				<legend>Inhalte</legend>
				<span id="fieldset_content">
					<table class="tablelist">
						<tr>
							<td class="tablecell two">Beschreibung</td>
							<td class="tablecell six"><input name="field['desc']" id="field_desc" value="<?php echo prepareTextField($value['content']); ?>" /></td>
						</tr>
						<tr>
							<td class="tablecell two">Inhalt</td>
							<td class="tablecell six"><textarea name="field['content']" id="field_content"><?php echo $value['content']; ?></textarea></td>
						</tr>
					</table>
				</span>
			</fieldset>
			<?php
			
			$edit = ob_get_contents();
			ob_end_clean();
	
			return $edit;
			} // getEdit()
		
		// prepare data to save
		function getSave() { return serialize($_POST['field']); }
		
		// returns an array with all predefined xajax functions used from this interpreter
		function getXAJAXfuncs() {
			// deprecated since 6.3
			return false;
			} // getXAJAXfuncs()
		
        // optional functions referred in fileparser but not notwendig
        // function getHeader($value, $mid, $cid, $publishlang) { return false; }
        // function getBodyFunction($value, $mid, $cid, $publishlang) { return false; }
        // function getMetaScript($value, $mid, $cid, $publishlang) { return false; }
        
		// parse contents
		function getContent($value, $mid, $cid, $publishlang) {
			
			$value = unserializeBroken($value);

			$parser = "<!-- getContent() to CID ".$cid." in MID ".$mid." for lang ".$publishlang." is no set -->";
			$parser.= $value['content'];
						
			return $parser;
	
			} // getContent()
	
	}	// class clsInterpreter
endif;

// EOF ?>