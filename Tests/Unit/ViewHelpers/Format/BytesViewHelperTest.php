<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Format;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Fluid".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test for \TYPO3\Fluid\ViewHelpers\Format\BytesViewHelper
 */
class BytesViewHelperTest extends UnitTestCase {

	/**
	 * @var \TYPO3\Fluid\ViewHelpers\Format\NumberViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		$this->viewHelper = $this->getMock('TYPO3\Fluid\ViewHelpers\Format\BytesViewHelper', array('renderChildren'));
	}

	/**
	 * @return array
	 */
	public function valueDataProvider() {
		return array(

				// invalid values
			array(
				'value' => 'invalid',
				'decimals' => NULL,
				'decimalSeparator' => NULL,
				'thousandsSeparator' => NULL,
				'expected' => '0 B'
			),
			array(
				'value' => '',
				'decimals' => 2,
				'decimalSeparator' => NULL,
				'thousandsSeparator' => NULL,
				'expected' => '0.00 B'
			),
			array(
				'value' => array(),
				'decimals' => 2,
				'decimalSeparator' => ',',
				'thousandsSeparator' => NULL,
				'expected' => '0,00 B'
			),
			array(
				'value' => PHP_INT_MAX + 1,
				'decimals' => NULL,
				'decimalSeparator' => NULL,
				'thousandsSeparator' => NULL,
				'expected' => '0 B'
			),

				// valid values
			array(
				'value' => 123,
				'decimals' => NULL,
				'decimalSeparator' => NULL,
				'thousandsSeparator' => NULL,
				'expected' => '123 B'
			),
			array(
				'value' => 1024,
				'decimals' => 1,
				'decimalSeparator' => NULL,
				'thousandsSeparator' => NULL,
				'expected' => '1.0 KB'
			),
			array(
				'value' => 1023,
				'decimals' => 2,
				'decimalSeparator' => NULL,
				'thousandsSeparator' => NULL,
				'expected' => '1,023.00 B'
			),
			array(
				'value' => 1073741823,
				'decimals' => 1,
				'decimalSeparator' => NULL,
				'thousandsSeparator' => '.',
				'expected' => '1.024.0 MB'
			),
		);
	}

	/**
	 * @param $value
	 * @param $decimals
	 * @param $decimalSeparator
	 * @param $thousandsSeparator
	 * @param $expected
	 * @test
	 * @dataProvider valueDataProvider
	 */
	public function renderCorrectlyConvertsAValue($value, $decimals, $decimalSeparator, $thousandsSeparator, $expected) {
		$actualResult = $this->viewHelper->render($value, $decimals, $decimalSeparator, $thousandsSeparator);
		$this->assertEquals($expected, $actualResult);
	}

	/**
	 * @test
	 */
	public function renderUsesChildNodesIfValueArgumentIsOmitted() {
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
		$actualResult = $this->viewHelper->render();
		$this->assertEquals('12 KB', $actualResult);
	}
}

?>