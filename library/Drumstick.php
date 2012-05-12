<?php
/**
 * Drumstick Test Generation Library
 *
 * @category Drumstick
 * @package package
 * @copyright Copyright (c) 2010 Jamie Talbot (http://jamietalbot.com)
 * @version $Id$
 */

/**
 * Generates skeleton test class files from test definitions.
 *
 * @category Drumstick
 * @package package
 */
class Drumstick {

	/**
	 * The class used to write the definitions.
	 *
	 * @var string
	 */
	protected static $_writerClass = null;

	/**
	 * The array of writers used to persist definitions.
	 *
	 * @var array
	 */
	protected static $_writers = array();

	/**
	 * The path to the test definitions.
	 *
	 * @var string
	 */
	protected static $_definitionPath = null;

	/**
	 * Path to the root of the tests directory.
	 *
	 * @var string
	 */
	protected static $_rootPath = null;

	protected static $_disallowedCharacters = array(
		" ", ".", "-", "\n"
	);

	/**
	 * Determines whether the supplied classname is a controller definition.
	 *
	 * @param string $className
	 * @return bool
	 */
	public static function isBehaviourDefinition($className) {
		return (false !== strstr($className, "Behaviour"));
	}

	/**
	 * Determines whether the supplied classname is a model definition.
	 *
	 * @param string $className
	 * @return bool
	 */
	public static function isModelDefinition($className) {
		return ((false !== strstr($className, "Model")) && (2 < count(explode("_", $className))));
	}

	/**
	 * Processes a single tests file and generates or updates a skeleton from the definition.
	 *
	 * @param string $file
	 */
	public static function processFile($file) {
		$tests = file(self::$_definitionPath . DIRECTORY_SEPARATOR . $file);
		$className = trim(array_shift($tests)) . "Test";

		foreach ($tests as & $test) {
			$test = "test" . str_replace(self::$_disallowedCharacters, "", ucwords($test));
		}

		$writer = self::getWriter($className);

		// Determine the type of the definition file.
		$baseClass = self::isBehaviourDefinition($className) ? "Celsus_Test_PHPUnit_ControllerTestCase_Http" : "PHPUnit_Framework_TestCase";

		if ($writer->definitionsExist()) {
			// Now we have to make sure we don't overwrite existing files and tests.
			$existingTests = $writer->load();

			$missingTests = array_diff($tests, $existingTests);

			if ($missingTests) {
				$source = $writer->getSource();
				$source = self::_appendTests($source, $missingTests);
			}
		} else {
			// Create new definition.
			$source = self::_getClassDefinition($className, $baseClass, $tests);
		}

		if (isset($source)) {
			// Sanity check.
			if (self::_sanityCheckContents($source, $baseClass)) {
				$writer->write($source);
			}
		}
	}

	/**
	 * Sets the paths to be used for definitions.
	 *
	 * @param string $root
	 */
	public static function setPaths($root) {
		self::setRootPath($root);
		self::setDefinitionPath($root . "/definitions");
	}

	/**
	 * Sets the root path.
	 *
	 * @param string $root
	 */
	public static function setRootPath($root) {
		self::$_rootPath = $root;
	}

	/**
	 * Sets the path to definitions.
	 *
	 * @param string $definitionPath
	 */
	public static function setDefinitionPath($definitionPath) {
		self::$_definitionPath = $definitionPath;
	}

	/**
	 * Gets the root path currently in use.
	 *
	 * @return string
	 */
	public static function getRootPath() {
		return self::$_rootPath;
	}

	/**
	 * Processes a directory of test definitions.
	 *
	 * @param string $path
	 */
	public static function processDirectory($path) {
		self::setPaths($path);
		$files = scandir(self::$_definitionPath);

		foreach ($files as $file) {
			list($filename, $extension) = explode('.', $file);
			if ('tests' === $extension) {
				self::processFile($file);
			}
		}
	}

	/**
	 * Main entry point into the application.
	 *
	 * @param string $path
	 */
	public static function main($path) {
		echo "Running $path\n";
		self::processDirectory($path);
	}

	/**
	 * Appends test definitions to an existing test class.
	 *
	 * @param string $source
	 * @param array $tests
	 * @return string
	 */
	protected static function _appendTests($source, $tests) {
		$source = substr($source, 0, strlen($source) - 2);
		foreach ($tests as $test) {
			$source .= self::_getFunctionDefinition($test);
		}
		$source .= "\n}";
		return $source;
	}

	/**
	 * Gets the writer which commits the tests
	 *
	 * @return Drumstick_Writer_Interface
	 */
	public static function getWriter($className) {
		if (!array_key_exists($className, self::$_writers)) {
			$writerClass = self::getWriterClass();
			self::$_writers[$className] = new $writerClass(self::$_rootPath, $className);
		}
		return self::$_writers[$className];
	}

	/**
	 * Sets the writer class to use.
	 *
	 * @param string $class
	 */
	public static function setWriterClass($class) {
		self::$_writerClass = $class;
	}

	/**
	 * Gets the writer class currently in use.
	 *
	 * @return string
	 */
	public static function getWriterClass() {
		if (null === self::$_writerClass) {
			self::$_writerClass = 'Drumstick_Writer_File';
		}
		return self::$_writerClass;

	}

	/**
	 * Attempts to load and parse the generated PHP file to check for errors.
	 *
	 * @param string $contents
	 * @param string $baseClass
	 * @return boolean
	 */
	protected static function _sanityCheckContents($contents, $baseClass) {
		$sanityCheckContents = <<<SANITY_CHECK
<?php class $baseClass {} ?>

		$contents
SANITY_CHECK;
		file_put_contents("__drumstick__.php", $sanityCheckContents);
		exec("php __drumstick__.php", $output, $return);
		unlink("__drumstick__.php");
		return !$return;
	}

	/**
	 * Generates the code skeleton for a single test function.
	 *
	 * @param string $function
	 */
	protected static function _getFunctionDefinition($function) {
		return <<<FUNCTION_DEFINITION


	public function $function() {
		\$this->markTestIncomplete("Not implemented yet.");
	}
FUNCTION_DEFINITION;

	}

	/**
	 * Generates the code skeleton for a single test class.
	 *
	 * @param string $class
	 * @param string $baseClass
	 * @param array $functions
	 */
	protected static function _getClassDefinition($class, $baseClass, $functions) {
		$class = <<<CLASS_HEADER_DEFINITION
<?php

class $class extends $baseClass {

	// Tests


CLASS_HEADER_DEFINITION;

		foreach ($functions as $function) {
			$class .= self::_getFunctionDefinition($function);
		}

		return "$class\n}";
	}
}