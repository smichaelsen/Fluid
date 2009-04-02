<?php
declare(ENCODING = 'utf-8');
namespace F3\Fluid\Service;
/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage Service
 * @version $Id: XSDGenerator.php 1962 2009-03-03 12:10:41Z k-fish $
 */

/**
 * XML Schema (XSD) Generator. Will generate an XML schema which can be used for autocompletion
 * in schema-aware editors like Eclipse XML editor.
 *
 * @package Fluid
 * @subpackage Service
 * @version $Id: XSDGenerator.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DocbookGenerator {

	/**
	 * Object manager.
	 *
	 * @var \F3\FLOW3\Object\Manager
	 */
	protected $objectManager;

	/**
	 * The reflection class for AbstractViewHelper. Is needed quite often, that's why we use a pre-initialized one.
	 *
	 * @var \F3\FLOW3\Reflection\ClassReflection
	 */
	protected $abstractViewHelperReflectionClass;

	/**
	 * The doc comment parser.
	 *
	 * @var \F3\FLOW3\Reflection\DocCommentParser
	 */
	protected $docCommentParser;

	/**
	 * Constructor. Sets $this->abstractViewHelperReflectionClass
	 *
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct() {
		$this->abstractViewHelperReflectionClass = new \F3\FLOW3\Reflection\ClassReflection('F3\Fluid\Core\AbstractViewHelper');
		$this->docCommentParser = new \F3\FLOW3\Reflection\DocCommentParser();
	}

	/**
	 * Inject the object manager.
	 *
	 * @param \F3\FLOW3\Object\Manager $objectManager the object manager to inject
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Generate the XML Schema definition for a given namespace.
	 *
	 * @param string $namespace Namespace identifier to generate the XSD for, without leading Backslash.
	 * @return string XML Schema definition
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function generateDocbook($namespace) {
		$tmp = str_replace('\\', '/', $namespace);

		if (substr($namespace, -1) !== '\\') {
			$namespace .= '\\';
		}

		$classNames = $this->getClassNamesInNamespace($namespace);

		$xmlRootNode = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<section version="5.0" xmlns="http://docbook.org/ns/docbook"
         xmlns:xl="http://www.w3.org/1999/xlink"
         xmlns:xi="http://www.w3.org/2001/XInclude"
         xmlns:xhtml="http://www.w3.org/1999/xhtml"
         xmlns:svg="http://www.w3.org/2000/svg"
         xmlns:ns="http://docbook.org/ns/docbook"
         xmlns:mathml="http://www.w3.org/1998/Math/MathML">
    <title>Standard View Helper Library</title>

    <para>Should be autogenerated from the tags.</para>
</section>');

		foreach ($classNames as $className) {
			$this->generateXMLForClassName($className, $namespace, $xmlRootNode);
		}

		return $xmlRootNode->asXML();
	}

	/**
	 * Get all class names inside this namespace and return them as array.
	 *
	 * @param string $namespace
	 * @return array Array of all class names inside a given namespace.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getClassNamesInNamespace($namespace) {
		$viewHelperClassNames = array();

		$registeredObjectNames = array_keys($this->objectManager->getRegisteredObjects());
		foreach ($registeredObjectNames as $registeredObjectName) {
			if (strncmp($namespace, $registeredObjectName, strlen($namespace)) === 0) {
				$viewHelperClassNames[] = $registeredObjectName;
			}
		}
		sort($registeredObjectNames);
		return $registeredObjectNames;
	}

	/**
	 * Generate the XML Schema for a given class name.
	 *
	 * @param string $className Class name to generate the schema for.
	 * @param string $namespace Namespace prefix. Used to split off the first parts of the class name.
	 * @param \SimpleXMLElement $xmlRootNode XML root node where the xsd:element is appended.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function generateXMLForClassName($className, $namespace, \SimpleXMLElement $xmlRootNode) {
		$reflectionClass = new \F3\FLOW3\Reflection\ClassReflection($className);
		if (!$reflectionClass->isSubclassOf($this->abstractViewHelperReflectionClass)) {
			return;
		}

		$tagName = $this->getTagNameForClass($className, $namespace);

		$docbookSection = $xmlRootNode->addChild('section');

		$docbookSection->addChild('title', $tagName);
		$this->docCommentParser->parseDocComment($reflectionClass->getDocComment());
		$this->addDocumentation($this->docCommentParser->getDescription(), $docbookSection);

		$argumentsSection = $docbookSection->addChild('section');
		$argumentsSection->addChild('title', 'Arguments');
		$this->addArguments($className, $argumentsSection);

		return $docbookSection;
	}

	/**
	 * Get a tag name for a given ViewHelper class.
	 * Example: For the View Helper F3\Fluid\ViewHelpers\Form\SelectViewHelper, and the
	 * namespace prefix F3\Fluid\ViewHelpers\, this method returns "form.select".
	 *
	 * @param string $className Class name
	 * @param string $namespace Base namespace to use
	 * @return string Tag name
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getTagNameForClass($className, $namespace) {
		$strippedClassName = substr($className, strlen($namespace));
		$classNameParts = explode('\\', $strippedClassName);

		if (count($classNameParts) == 1) {
			$tagName = lcfirst(substr($classNameParts[0], 0, -10)); // strip the "ViewHelper" ending
		} else {
			$tagName = lcfirst($classNameParts[0]) . '.' . lcfirst(substr($classNameParts[1], 0, -10));
		}
		return $tagName;
	}

	/**
	 * Add attribute descriptions to a given tag.
	 * Initializes the view helper and its arguments, and then reads out the list of arguments.
	 *
	 * @param string $className Class name where to add the attribute descriptions
	 * @param \SimpleXMLElement $xsdElement XML element to add the attributes to.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function addArguments($className, \SimpleXMLElement $docbookSection) {
		$viewHelper = $this->objectManager->getObject($className);
		$argumentDefinitions = $viewHelper->prepareArguments();

		if (count($argumentDefinitions) === 0) {
			$docbookSection->addChild('para', 'No arguments defined.');
			return;
		}
		$argumentsTable = $docbookSection->addChild('table');
		$argumentsTable->addChild('title', 'Arguments');
		$tgroup = $argumentsTable->addChild('tgroup');
		$tgroup['cols'] = 4;
		$this->addArgumentTableRow($tgroup->addChild('thead'), 'Name', 'Type', 'Required', 'Description', 'Default');

		$tbody = $tgroup->addChild('tbody');

		foreach ($argumentDefinitions as $argumentDefinition) {
			$this->addArgumentTableRow($tbody, $argumentDefinition->getName(), $argumentDefinition->getType(), $argumentDefinition->isRequired(), $argumentDefinition->getDescription(), $argumentDefinition->getDefaultValue());
		}
	}
	private function addArgumentTableRow(\SimpleXMLElement $parent, $name, $type, $required, $description, $default) {
		$row = $parent->addChild('row');

		$row->addChild('entry', $name);
		$row->addChild('entry', $type);
		$row->addChild('entry', $required);
		$row->addChild('entry', $description);
		$row->addChild('entry', $default);
	}

	/**
	 * Add documentation XSD to a given XML node
	 *
	 * As Eclipse renders newlines only on new <xsd:documentation> tags, we wrap every line in a new
	 * <xsd:documentation> tag.
	 * Furthermore, eclipse strips out tags - the only way to prevent this is to have every line wrapped in a
	 * CDATA block AND to replace the < and > with their XML entities. (This is IMHO not XML conformant).
	 *
	 * @param string $documentation Documentation string to add.
	 * @param \SimpleXMLElement $xsdParentNode Node to add the documentation to
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function addDocumentation($documentation, \SimpleXMLElement $docbookSection) {
		$splitRegex = '/^\s*(=[^=]+=)$/m';
		$regex = '/^\s*(=([^=]+)=)$/m';

		$matches = preg_split($splitRegex, $documentation, -1,  PREG_SPLIT_NO_EMPTY  |  PREG_SPLIT_DELIM_CAPTURE );

		$currentSection = $docbookSection;
		foreach ($matches as $singleMatch) {
			if (preg_match($regex, $singleMatch, $tmp)) {
				$currentSection = $docbookSection->addChild('section');
				$currentSection->addChild('title', trim($tmp[2]));
			} else {
				$this->addText(trim($singleMatch), $currentSection);
			}
		}
	}

	protected function addText($text, \SimpleXMLElement $parentElement) {
		$splitRegex = '/
		(<code(?:.*?)>
			(?:.*?)
		<\/code>)/xs';

		$regex = '/
		<code(.*?)>
			(.*?)
		<\/code>/xs';
		$matches = preg_split($splitRegex, $text, -1,  PREG_SPLIT_NO_EMPTY  |  PREG_SPLIT_DELIM_CAPTURE );
		foreach ($matches as $singleMatch) {

			if (preg_match($regex, $singleMatch, $tmp)) {
				preg_match('/title="([^"]+)"/', $tmp[1], $titleMatch);

				$example = $parentElement->addChild('example');
				$example->addChild('title', trim($titleMatch[1]));
				$example->addChild('programlisting', trim($tmp[2]));
			} else {
				$textParts = explode("\n", $singleMatch);
				foreach ($textParts as $text) {
					if (trim($text) === '') continue;
					$parentElement->addChild('para', trim($text));
				}
			}
		}

	}

}
?>