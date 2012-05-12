<?php
/**
 * Drumstick Test Generation Library
 *
 * @category Drumstick_Writer
 * @package package
 * @copyright Copyright (c) 2010 Jamie Talbot (http://jamietalbot.com)
 * @version $Id$
 */

/**
 * Writes definitions to a standard file structure.
 *
 * @category Drumstick_Writer
 * @package package
 */
class Drumstick_Writer_File implements Drumstick_Writer_Interface {

	protected $_path = null;

	protected $_filename = null;

	protected $_className = null;

	protected static $_behaviourPath = "/application/behaviours";

	protected static $_modelPath = "/application/models";

	protected static $_testLibraryPath = "/application/library";

	public function __construct($rootPath, $className) {
		$this->_className = $className;
		$classComponents = explode("_", $className);
		$this->_filename = array_pop($classComponents) . ".php";

		if (Drumstick::isBehaviourDefinition($className)) {
			$this->_path = $rootPath . self::$_behaviourPath;
		} elseif (Drumstick::isModelDefinition($className)) {
			$this->_path = $rootPath . self::$_modelPath;
		} else {
			$basePath = $rootPath . self::$_testLibraryPath;
			$classPath = implode(DIRECTORY_SEPARATOR, $classComponents);
			$this->_path = $basePath . DIRECTORY_SEPARATOR . $classPath;
		}
	}

	public function definitionsExist() {
		return file_exists($this->_path . DIRECTORY_SEPARATOR . $this->_filename);
	}

	public function getSource() {
		return trim(file_get_contents($this->_path . DIRECTORY_SEPARATOR . $this->_filename));
	}

	public function load() {
		// @todo Secure this.
		include_once ($this->_path . DIRECTORY_SEPARATOR . $this->_filename);

		$reflection = new ReflectionClass($this->_className);
		$existingTests = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

		$return = array();
		foreach ($existingTests as $existingTest) {
			$return[] = $existingTest->getName();
		}
		return $return;
	}

	public function write($source) {
		// Create directory structure if necessary.
		if (!is_dir($this->_path)) {
			mkdir($this->_path, 0777, true);
		}
		file_put_contents($this->_path . DIRECTORY_SEPARATOR . $this->_filename, $source);
	}
}