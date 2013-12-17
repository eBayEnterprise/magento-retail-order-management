<?php
class TrueAction_Eb2cProduct_Model_Feed_Analyzer extends TrueAction_Eb2cProduct_Model_Feed
{
	private $_callback   = null;

	/**
	 * Sets an optional callback, which is called with the parsed values of a CustomAttributes node
	 * @return self
	 */
	public function setCallback($function) {
		$this->_callback = $function;
		return $this;
	}

	/**
	 * process a dom document
	 * @param  TrueAction_Dom_Document $doc
	 * @return self
	 */
	public function processDom(TrueAction_Dom_Document $doc)
	{
		$units = $this->_getIterableFor($doc);
		foreach ($units as $unit) {
			$isValid = $this->_eventTypeModel->getUnitValidationExtractor()
				->getValue($this->_xpath, $unit);
			if ($isValid) {
				$data = $this->_extractData($unit);
				if (is_callable($this->_callback)) {
					$sku = $data->getClientItemId() ? $data->getClientItemId() : $data->getUniqueId();
					call_user_func($this->_callback, $sku, $data);
				}
			}
		}
		return $this;
	}
}
