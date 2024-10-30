<?php

class cbpwt_autoloader {

	/**
	 * Path to the includes directory
	 * @var string
	 */
	private $include_path = '';

	/**
	 * The Constructor
	 */
	public function __construct() {
		if (function_exists("__autoload")) {
			spl_autoload_register("__autoload");
		}

		spl_autoload_register(array($this, 'autoload'));

		$this->include_path = untrailingslashit(__dimwwt_dir) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Auto-load classes on demand to reduce memory consumption.
	 *
	 * @param string $class
	 */
	public function autoload($class) {
		$class = strtolower($class);
		$file = $this->get_file_name_from_class($class);
		$this->load_file($this->include_path . $file);
	}

	/**
	 * Take a class name and turn it into a file name
	 * @param  string $class
	 * @return string
	 */
	private function get_file_name_from_class($class) {
		return 'class-' . str_replace('_', '-', strtolower($class)) . '.php';
	}

	/**
	 * Include a class file
	 * @param  string $path
	 * @return bool successful or not
	 */
	private function load_file($path) {
		if ($path && is_readable($path)) {
			include_once $path;
			return true;
		}
		return false;
	}
}

new cbpwt_autoloader();