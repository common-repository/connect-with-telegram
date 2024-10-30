<?php
require_once __dimwwt_dir . 'inc' . DIRECTORY_SEPARATOR . 'class-dimwwt-listener.php';

/**
 * Class For telegram mode
 */
class dimwwt_telegram_listener extends dimwwt_listener {
	function apiRequestWebhook($method, $parameters) {
		if (!is_string($method)) {
			$this->error_log("Method name must be a string\n");
			return false;
		}

		if (!$parameters) {
			$parameters = array();
		} else if (!is_array($parameters)) {
			$this->error_log("Parameters must be an array\n");
			return false;
		}

		$parameters["method"] = $method;

		header("Content-Type: application/json");
		echo json_encode($parameters);
		return true;
	}

	function apiRequestJson($method, $parameters) {
		if (!is_string($method)) {
			$this->error_log("Method name must be a string\n");
			return false;
		}

		if (!$parameters) {
			$parameters = array();
		} else if (!is_array($parameters)) {
			$this->error_log("Parameters must be an array\n");
			return false;
		}

		$parameters["method"] = $method;

		$handle = curl_init(dimwwt_admin_menu::get('bot-api-url') . 'bot' . dimwwt_admin_menu::get('bot-token') . '/');
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($handle, CURLOPT_TIMEOUT, 60);
		curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
		curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

		return $this->exec_curl_request($handle);
	}

}