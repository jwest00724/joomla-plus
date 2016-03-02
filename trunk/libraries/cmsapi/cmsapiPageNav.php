<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// Don't allow direct linking
if (!defined( '_VALID_MOS' ) AND !defined('_JEXEC')) die( 'Direct Access to this location is not allowed.' );

		class cmsapiPageNav extends mosPageNav {
		function listFormEnd ($pagecontrol=true) {
			$act = $_REQUEST['act'];

			if ($pagecontrol) {
				echo <<<PAGE_CONTROL1

			<tfoot>
			<tr>
	    		<th align="center" colspan="13">

PAGE_CONTROL1;
					$this->writePagesLinks();
					echo <<<PAGE_CONTROL2

			</th>
			</tr>
			<tr>
				<td align="center" colspan="13">

PAGE_CONTROL2;
					$this->writeLimitBox();
					$this->writePagesCounter();
					echo <<<PAGE_CONTROL3

			</td>
			</tr>

PAGE_CONTROL3;

			}
			else {
				echo <<<END_PAGE

			<tfoot>
			<tr>
	    		<th align="center" colspan="13">&nbsp;</th>
			</tr>

END_PAGE;

			}
				$repnum = intval(isset($_REQUEST['repnum']) ? $_REQUEST['repnum'] : 1);
				echo <<<HIDDEN_HTML
				
			<tr>
				<td>
					<input type="hidden" name="option" value="com_remository" />
					<input type="hidden" name="repnum" value="$repnum" />
					<input type="hidden" name="task" value="" />
					<input type="hidden" name="act" value="$act" />
					<input type="hidden" name="boxchecked" value="0" />
				</td>
			</tr>
			</tfoot>
				
HIDDEN_HTML;
		}
	}
