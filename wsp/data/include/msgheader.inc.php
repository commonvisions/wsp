<?php
/**
 * msgheader
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2018-09-18
 */
?>
<script type="text/javascript">
			
function showInnerMsg(msgtype) {
	$.post("xajax/ajax.showinnermsg.php", { 'msgtype': msgtype })
		.done (function(data) {
			if (data) {
				$('#'+msgtype).html(data);
				$('#'+msgtype).toggle('blind');
				}
			})
	}

</script>
<div id="infoholder">
<fieldset id="noticemsg" style="display: none;"></fieldset>
<fieldset id="errormsg" style="display: none;"></fieldset>
<fieldset id="resultmsg" style="display: none;"></fieldset>
</div>
<?php // EOF ?>