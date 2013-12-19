<?php
require_once 'abstract.php';
class TrueAction_Eb2cShell_Attribute_Analyzer extends Mage_Shell_Abstract
{
	const SEP = "\t";
	const NL  = "\n";

	private $_analyzer;

	/**
	 * Gets our product analyzer model
	 */
	public function _construct()
	{
		parent::_construct();
		$this->_analyzer = Mage::getModel('eb2cproduct/feed_analyzer');
	}

	/**
	 * Standard Magento Shell run method
	 */
	public function run()
	{
		if( !count($this->_args) ) {
			echo $this->usageHelp();
			return 0;
		}

		$files = preg_split('/[\s,]+/', $this->getArg('files')); // Split file names on whitespace
		if( !count($files) ) {
			echo $this->usageHelp();
			return 0;
		}

		$mode = $this->getArg('mode');

		foreach( $files as $fileName ) {
			if (file_exists($fileName)) {
				// If you'd like to add output types, just add an arg, and change the callback. You'll get called
				// with an array of Varien Objects pulled from the CustomAttribute Node. You don't need a callback
				// if you don't want one - in that case, the DOM Extraction engine just runs against the file. 
				if ($mode === 'verbose') {
					$this->_verboseHeader($fileName);
					$this->_analyzer
						->setCallback(__CLASS__ . '::verboseParse')
						->processFile($fileName);
				} else  {
					// The Default:
					$this->_defaultHeader($fileName);
					$this->_analyzer
						->setCallback(__CLASS__ . '::defaultParse')
						->processFile($fileName);
					$this->defaultParse(null);
				}
			} else {
				echo "File $fileName not found.\n";
			}
		}
	}

	/**
	 * Get the SKU from the Data Unit - SKUs are at ClientItemId in Master and Pricing; in UniqueId in Content
	 *
	 */
	private static function _getSku(Varien_Object $dataUnit)
	{
		return ($dataUnit->getClientItemId() ? $dataUnit->getClientItemId() : $dataUnit->getUniqueId());
	}


	/**
	 * Header for the verbose method-style of output
	 */
	protected function _verboseHeader($fileName)
	{
		echo "File: $fileName\n";
		echo 'sku' . self::SEP . 'attribute' . self::SEP . 'suggestion' . self::SEP . 'operation' . self::SEP . 'lang' . self::SEP . 'value'. self::NL;
	}

	/**
	 * Dump an array of Varien_Objects populated by feed_analyzer
	 */
	public static function verboseParse(Varien_Object $dataUnit)
	{
		$sku = self::_getSku($dataUnit);
		$attributes = $dataUnit->getCustomAttributes();

		foreach ($attributes as $attribute) {
			$obj = self::_toVarienObject($attribute);
			echo $sku . self::SEP . $obj->getName() . self::SEP . $obj->getSuggestion() . self::SEP . $obj->getOperationType() . self::SEP . $obj->getLang() . self::SEP . str_replace(self::SEP,' ',$obj->getValue()) . self::NL;
		}
	}

	/**
	 * Header for the default style of output
	 */
	protected function _defaultHeader($fileName)
	{
		echo "File: $fileName\n";
	}

	/**
	 * Default style of output is to condense all the attributes down. 
	 */
	public static function defaultParse(Varien_Object $dataUnit)
	{
		static $condensedAttributes;
		if ($dataUnit) {
			$attributes = $dataUnit->getCustomAttributes();
			foreach ($attributes as $attribute) {
				$obj = self::_toVarienObject($attribute);
				$condensedAttributes[$obj->getName()]['attribute_code'] = $obj->getAttributeCode();
				$condensedAttributes[$obj->getName()]['name_too_long'] = $obj->getNameTooLong();
				$condensedAttributes[$obj->getName()]['occurs']++;
			}
		} else {
			// Let's either return or call a separate function here please.
			$attributeSettings  = TrueAction_Eb2cProduct_Model_Attributes::getInitialData();
			$settingsHeaderCols = '';
			$settingsValueCols  = '';

			foreach ($attributeSettings as $setting=>$value) {
				$settingsHeaderCols .= $setting . self::SEP;
				$settingsValueCols  .= $value . self::SEP;
			}
			echo 'raw_name' . self::SEP . 'attribute_code' . self::SEP .  'occurs' . self::SEP . $settingsHeaderCols . self::NL;
			ksort($condensedAttributes);
			foreach ($condensedAttributes as $rawName => $info) {
				$tooLong = strlen($rawName > Mage_Eav_Model_Entity_Attribute::ATTRIBUTE_CODE_MAX_LENGTH);
				echo $rawName . self::SEP . ($tooLong ? '?' :  $info['suggested_name']) . self::SEP . $info['occurs'] . self::SEP . $settingsValueCols. self::NL;
			}
		}
	}

	/**
	 * Convert an attribute array into a Varien_Object 
	 * return Varien_Object
	 */
	private static function _toVarienObject($attribute)
	{
		$parsedAttribute = new Varien_Object();
		// This is the raw Attribute Name
		$attributeName = $attribute['name'];

		// If we have consecutive caps, we'll end up with crazy _underscore() results.
		// So if we do, we just strtowlower the whole thing.
		$testStudlyCaps = str_replace(array('~','_'), '', $attributeName);
		if (preg_match('/[A-Z]{2,}/', $testStudlyCaps)) {
			$attributeName = strtolower(str_replace('~', '_', $attributeName));
		}

		// The cooked name is alphanumeric and may contain underscores.
		// This filters out things like "SPECIFICATION~Entr&eacute;"
		$attributeNameCooked = preg_replace('/[^a-z0-9_]+/i', '', $attributeName);

		// '$suggestion' is a mildy educated guess at how to make this attribute name suitable for Magento
		// If the cooked name is longer than the Max Attribute Code Length, you need to rethink the whole thing
		if( strcasecmp($attribute['name'], $attributeNameCooked) ) {
			// The raw name is unusable, but we have a decent suggestion.
			$suggestion = sprintf('Bad attribute name. Try "%s" instead.', self::_suggestedAttributeName($attributeNameCooked));
		} else {
			// The raw name may be usable as-is
			$suggestion = self::_suggestedAttributeName($attributeNameCooked);
		}

		$parsedAttribute->setData(
			array (
				'operation_type' => strtolower($attribute['operation_type']),
				'lang'           => (isset($attribute['lang']) ? strtolower($attribute['lang']) : ''),
				'name'           => $attribute['name'],
				'value'          => $attribute['value'],
				'attribute_code' => self::_suggestedAttributeName($attributeNameCooked),
				'suggestion'     => $suggestion,
			)
		);
		return $parsedAttribute;
	}

	/**
	 * Make a Magento-like suggestion for this attribute name
	 */
	private static function _suggestedAttributeName($name)
	{
		return strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
	}


	/**
	 * Return some help text
	 * @return string
	 */
	public function usageHelp()
	{
		$scriptName = basename(__FILE__);
		return <<<USAGE

Usage: php -f $scriptName -- [options]
  --files file1,file2    list of files (please pass shell escaped - quoted, or csv with no spaces)
  --mode  verbose        (every instance of every attribute along with its sku and value)
          default        (Received Attribute Name, Suggested Name, Number of occurences, Magento Settings)
  help       This help

USAGE;
	}
}

$runner = new TrueAction_Eb2cShell_Attribute_Analyzer();
$runner->run();
exit(0);
