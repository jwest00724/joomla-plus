<?php
/**
 * Plugin for Joomla 1.5 - Version 1.5.1
 * License: http://www.gnu.org/copyleft/gpl.html
 * Authors: marco maria leoni
 * Copyright (c) 2010 marco maria leoni web consulting - http: www.mmleoni.net
 * Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * http://www.mmleoni.net/extended-debug-for-joomla-with-ip-restriction
 * *** Last update: Sep, 5th 2010 ***
*/

/**
 * Adapted and integrated into Jaliro
 *
 */

defined ( '_JEXEC' ) OR die( 'Direct Access to this location is not allowed.' );

class cmsapiDebug {
	private $params = null;

	public function __construct ($params) {
		$this->params = $params;
	}

	public function debugOutput () {
		global $database;

		ob_start();
		echo '<div id="system-debug" class="profiler">';

		// Display General Warning
		echo '<div id="system-debug-general-warning" class="system-debug-block">';
		echo '<h4>General Warning</h4>';
		if ($this->params->get('limit_to_ip', '')) {
			echo "Debug info sent to: " . $this->params->get('limit_to_ip');
		}else{
			echo '<span class="system-debug-alert">Attention: Debug info sent to All!!</span>';
		}
		echo '</div>';



		// Display remote client Information
		if ($this->params->get('remote_client', 0)) {
			echo '<div id="system-debug-remote-client" class="system-debug-block">';
			echo '<h4>Client Information</h4>';
			echo '<ul>';
			echo "<li>REMOTE_ADDR    :{$_SERVER['REMOTE_ADDR']}</li>";
			echo "<li>HTTP_USER_AGENT: {$_SERVER['HTTP_USER_AGENT']}</li>";
			echo "<li>REQUEST_METHOD : {$_SERVER['REQUEST_METHOD']}</li>";
			echo "<li>QUERY_STRING   : {$_SERVER['QUERY_STRING']}</li>";
			echo "<li>HTTP_REFERER   :{$_SERVER['HTTP_REFERER']}</li>";
			echo '</ul>';
			echo '</div>';
		}

		// Display GPC super globals Information
		if ($this->params->get('namespaces', 'GET,POST') != 'NONE') {
			echo '<div id="system-debug-namespaces" class="system-debug-block">';
			echo '<h4>GPC Namespaces</h4>';


			$wr=array();
			foreach(explode(',', $this->params->get('namespaces', 'GET,POST' )) as $nsp){
				switch ($nsp){
					case 'GET':
						$nameSpace = $_GET;
						break;
					case 'POST':
						$nameSpace = $_POST;
						break;
					case 'COOKIE':
						$nameSpace = $_COOKIE;
						break;
					case 'REQUEST':
						$nameSpace = $_REQUEST;
						break;
				}
				echo '<h5>Namespace: $_'.$nsp.'</h5>';
				echo '<ul>';
				$i=0;
				foreach($nameSpace as $k => $v){
					echo "<li>$k: <pre class=\"system-debug-row-" . ($i++ % 2) . "\">";
					var_dump ($v);
					echo '</pre></li>';
				} // namespace
				echo '</ul>';
			} //namespaces
			echo '</div>';
		}


		// Display $_SESSION vars Information
		if ($this->params->get('display_session', '1')) {
			echo '<div id="system-debug-session" class="system-debug-block">';
			echo '<h4>Sessions Namespace</h4>';
			echo '<ul>';
			$i=0;
			foreach($_SESSION as $k => $v){
				echo "<li>$k: <pre class=\"system-debug-row-" . ($i++ % 2) . "\">";
				var_dump ($v);
				echo '</pre></li>';
			} // namespace
			echo '</ul>';
			echo '</div>';
		}

		if ($this->params->get('memory', 1)) {
			echo '<div id="system-debug-memory" class="system-debug-block">';
			echo '<h4>Memory Usage</h4>';
			echo memory_get_usage();
			echo '</div>';
		}

		if ($this->params->get('queries', 1))
		{
			$geshi = new GeSHi( '', 'sql' );
			$geshi->set_header_type(GESHI_HEADER_DIV);
			//$geshi->enable_line_numbers( GESHI_FANCY_LINE_NONE );

			$newlineKeywords = '/<span class="system-debug-queries-keys">'
				.'(FROM|LEFT|INNER|OUTER|WHERE|SET|VALUES|ORDER|GROUP|HAVING|LIMIT|ON|AND)'
				.'<\\/span>/i'
			;

			echo '<div id="system-debug-queries" class="system-debug-block">';
			echo '<h4>'.JText::sprintf( 'Queries logged',  $database->getTicker() ).'</h4>';

			if ($log = $db->getLog())
			{
				echo '<ol>';
				$i=0;
				foreach ($log as $k=>$sql)				{
					$geshi->set_source($sql);
					$text = $geshi->parse_code();
					$text = preg_replace($newlineKeywords, '<br />&nbsp;&nbsp;\\0', $text);
					echo '<li class="system-debug-row-' . ($i++ % 2) . '">'.$text.'</li>';
				}
				echo '</ol>';
			}
		}

		$debug = ob_get_clean();

		$body = JResponse::getBody();
		$body = str_replace('</body>', $debug.'</body>', $body);
		JResponse::setBody($body);
	}
}