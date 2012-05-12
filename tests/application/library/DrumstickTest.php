<?php

require_once 'vfsStream/vfsStream.php';

class DrumstickTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('tests'));
		Drumstick::setPaths(vfsStream::url('tests'));
		vfsStreamWrapper::getRoot()->addChild(new vfsStreamDirectory('definitions'));
	}

	protected function _prepareDefinition($filename) {
		$sourceData = file_get_contents(TEST_PATH . "/fixtures/definitions/$filename.tests");
		$file = new vfsStreamFile("$filename.tests");
		$file->setContent($sourceData);
		vfsStreamWrapper::getRoot()->getChild('definitions')->addChild($file);
	}

	public function testShouldNotTruncateFunctionsThatAlreadyExist() {
		// Simulate the source file.
		$this->_prepareDefinition('ExistingClass');

		// Simulate the existing definitions file.
		$expected = file_get_contents(TEST_PATH . "/fixtures/matches/ExistingClassTest.php");
		vfsStreamWrapper::getRoot()->addChild(new vfsStreamDirectory('application'));
		$expectedFile = new vfsStreamFile('ExistingClassTest.php');
		$expectedFile->setContent($expected);
		vfsStreamWrapper::getRoot()->getChild('application')->addChild(new vfsStreamDirectory('library'));
		vfsStreamWrapper::getRoot()->getChild('application')->getChild('library')->addChild($expectedFile);

		$modifiedTime = filemtime('vfs://tests/application/library/ExistingClassTest.php');

		Drumstick::processFile('ExistingClass.tests');

		$this->assertEquals($modifiedTime, filemtime('vfs://tests/application/library/ExistingClassTest.php'), "File was modified when it shouldn't have been.");
		$this->assertEquals($expected, file_get_contents('vfs://tests/application/library/ExistingClassTest.php'), "File content has changed.");
	}

	public function testShouldGenerateAFileInTheLibraryHierarchyForGeneralDefinitions() {
		// Simulate the source file.
		$this->_prepareDefinition('DemoClass');

		$this->assertFalse(file_exists('vfs://tests/application/library/DemoClassTest.php'));

		Drumstick::processFile('DemoClass.tests');

		$this->assertTrue(file_exists('vfs://tests/application/library/DemoClassTest.php'));
	}

	public function testShouldGenerateAFileInTheModelHierarchyForModelDefinitions() {
		// Simulate the source file.
		$this->_prepareDefinition('DemoModel');

		$this->assertFalse(file_exists('vfs://tests/application/models/DemoTest.php'));

		Drumstick::processFile('DemoModel.tests');

		$this->assertTrue(file_exists('vfs://tests/application/models/DemoTest.php'));
	}

	public function testShouldAppendNewFunctionsToExistingClassFiles() {
		$this->_prepareDefinition('AppendedClass');

		// Simulate the existing definitions file.
		$before = file_get_contents(TEST_PATH . "/fixtures/matches/AppendedClassBeforeProcessing.php");
		vfsStreamWrapper::getRoot()->addChild(new vfsStreamDirectory('application'));
		$expectedFile = new vfsStreamFile('AppendedClassTest.php');
		$expectedFile->setContent($before);
		vfsStreamWrapper::getRoot()->getChild('application')->addChild(new vfsStreamDirectory('library'));
		vfsStreamWrapper::getRoot()->getChild('application')->getChild('library')->addChild($expectedFile);

		$this->assertTrue(file_exists('vfs://tests/application/library/AppendedClassTest.php'));

		Drumstick::processFile('AppendedClass.tests');

		$after = file_get_contents(TEST_PATH . "/fixtures/matches/AppendedClassAfterProcessing.php");

		$this->assertEquals($after, file_get_contents("vfs://tests/application/library/AppendedClassTest.php"));
	}

	public function testShouldNotGenerateAControllerDefinitionFileForControllerPluginDefinitions() {
		$this->_prepareDefinition('ControllerPlugin');

		$this->assertFalse(file_exists('vfs://tests/application/controllers/MagicTest.php'), "Output file already exists in the incorrect place.");
		$this->assertFalse(file_exists('vfs://tests/application/library/Test/Controller/Plugin/MagicTest.php'), "Output file already exists in the correct place.");

		Drumstick::processFile('ControllerPlugin.tests');

		// As this is a controller plugin, it should not have created a controller test.
		$this->assertFalse(file_exists('vfs://tests/application/controllers/MagicTest.php'), "File was created in the wrong hierarchy.");

		// As this is a controller plugin, it should have created a test suite in the library hierarchy.
		$this->assertTrue(file_exists('vfs://tests/application/library/Test/Controller/Plugin/MagicTest.php'), "File was not created in the right hierarchy.");
	}

	public function testShouldStripNonAllowableCharactersFromFunctionNames() {
		$this->_prepareDefinition('DisallowedCharacter');

		$this->assertFalse(file_exists('vfs://tests/application/library/DisallowedTest.php'));

		Drumstick::processFile('DisallowedCharacter.tests');

		// As this is a controller plugin, it should have created a test suite in the library hierarchy.
		$this->assertTrue(file_exists('vfs://tests/application/library/DisallowedTest.php'));
	}

	public function testShouldGenerateAFileInTheBehaviourHierarchyForBehaviourDefinitions() {
		// Simulate the source file.
		$this->_prepareDefinition('DemoBehaviour');

		$this->assertFalse(file_exists('vfs://tests/application/behaviours/DemoBehaviourTest.php'));

		Drumstick::processFile('DemoBehaviour.tests');

		$this->assertTrue(file_exists('vfs://tests/application/behaviours/DemoBehaviourTest.php'));
	}

	public function testShouldNotGenerateAModelDefinitionFileForAModelWithNoTertiaryName() {
		$this->_prepareDefinition('ModelBase');

		$this->assertFalse(file_exists('vfs://tests/application/models/ModelTest.php'));
		$this->assertFalse(file_exists('vfs://tests/application/library/Test/ModelTest.php'));

		Drumstick::processFile('ModelBase.tests');

		// As this is a controller plugin, it should not have created a controller test.
		$this->assertFalse(file_exists('vfs://tests/application/models/ModelTest.php'));

		// As this is a controller plugin, it should have created a test suite in the library hierarchy.
		$this->assertTrue(file_exists('vfs://tests/application/library/Test/ModelTest.php'));
	}
}