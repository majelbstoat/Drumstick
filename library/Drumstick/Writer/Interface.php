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
 * Interface for persisting definitions.
 *
 * @category Drumstick_Writer
 * @package package
 */
interface Drumstick_Writer_Interface {

	/**
	 * Constructor.
	 *
	 * @param string $rootPath
	 * @param string $className
	 */
	public function __construct($rootPath, $className);

	/**
	 * Determines whether definitions have already been created for the specified class.
	 *
	 * @return bool
	 */
	public function definitionsExist();

	/**
	 * Gets the source code from the existing persistent location.
	 *
	 * @return string
	 */
	public function getSource();

	/**
	 * Loads definitions and returns an array of test names already defined.
	 *
	 * @return array
	 */
	public function load();

	/**
	 * Writes the definitions to a persistent layer.
	 */
	public function write($source);

}