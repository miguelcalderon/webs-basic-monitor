<?php
session_start();
$settings = require_once('../../config.php');
require_once('../../db.php');
$db = new bd($settings['dbs'], $settings['usr'], $settings['pwd']);
$db->checkTable($settings['webs_table'], join(',', $settings['web_params']));

if ((!isset($_GET['action']) || $_GET['action'] === 'add') && (isset($_POST['url']))) {
	$values = array();
	if (isset($_POST['url']) && strlen(trim($_POST['url'])) > 0) {
		$values['url'] = $_POST['url'];
	}
	if (isset($_POST['branch']) && strlen(trim($_POST['branch'])) > 0) {
		$values['branch'] = $_POST['branch'];
	}
	if (isset($values['url']) && isset($values['branch'])) {
		$values['added'] = date('Y-m-d H:i:s');
		$res = $db->insert($settings['webs_table'], $values);
		if (!$res) {
			$_SESSION['message'] = $db->msg;
		}
	} else {
		$_SESSION['message'] = "Data missing.";
	}
} else {
	if ($_GET['action'] === 'delete') {
		$values = array();
		if (isset($_POST['url'])) {
			$values['url'] = $_POST['url'];
		}
		$res = $db->delete($settings['webs_table'], $values);
		if (!$res) {
			$_SESSION['message'] = $db->msg;
		}
	} else {
		if (isset($_GET['param'])) {
			switch ($_GET['param']) {
				case 'status':
					if (isset($_GET['url'])) {
						$curl = curl_init();
						curl_setopt($curl, CURLOPT_URL, $_GET['url']);
						curl_setopt($curl, CURLOPT_FILETIME, true);
						curl_setopt($curl, CURLOPT_NOBODY, true);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curl, CURLOPT_HEADER, true);
						curl_setopt($curl, CURLOPT_VERBOSE, true);
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
						curl_setopt($curl, CURLOPT_FAILONERROR, true);
						curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
						$header = curl_exec($curl);
						if (!curl_errno($curl)) {
							$info = curl_getinfo($curl);
							header('X-PHP-Response-Code: '.$info['http_code'], true, intval($info['http_code']));
							echo($info['http_code']);
						} else {
							header('X-PHP-Response-Code: 400', true, 400);
							echo curl_errno($curl);
							echo $header;
						}
						exit();
					}
					break;
				case 'links':
					if (isset($_GET['url'])) {
						$broken_link_marker = '├─BROKEN─';
						$cmdl = "blc ".$_GET['url']." -ro --user-agent 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'";
						$result = shell_exec($cmdl);
						$lineas = split("[\r|\n]", trim($result));
						$result_broken = array();
						foreach	($lineas as $linea) {
							if (substr($linea, 0, strlen($broken_link_marker)) === $broken_link_marker) {
								$result_broken[] = '{ "url": "'.substr($linea, strlen($broken_link_marker) + 1).'" }';
							}
						}
						echo '['.implode(',', $result_broken).']';
						exit();
					}
					break;
				default:
					$_SESSION['message'] = "Unknown action: ".$GET['action'];
					break;
			}
		} else {
			$_SESSION['message'] = "Unknown action: ".$GET['action'];
		}
	}
}
header('Location: /');
exit();
