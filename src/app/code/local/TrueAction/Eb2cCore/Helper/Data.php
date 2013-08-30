<?php
/**
 * @category   TrueAction
 * @package    TrueAction_Eb2c
 * @copyright  Copyright (c) 2013 True Action Network (http://www.trueaction.com)
 */
class TrueAction_Eb2cCore_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Service URI has the following format:
	 * https://{env}-{rr}.gsipartners.com/v{M}.{m}/stores/{storeid}/{service}/{operation}{/parameters}.{format}
	 * - env - GSI Environment to access
	 * - rr - Geographic region - na, eu, ap
	 * - M - major version of the API
	 * - m - minor version of the API
	 * - storeid - GSI assigned store identifier
	 * - service - API call service/subject area
	 * - operation - specific API call of the specified service
	 * - parameters - optionally any parameters needed by the call
	 * - format - extension of the requested response format. Currently only xml is supported
	 */
	const URI_FORMAT = 'https://%s-%s.gsipartners.com/v%s.%s/stores/%s/%s/%s%s.%s';
	/**
	 * Get the API URI for the given service/request.
	 * @param string $service
	 * @param string $operation
	 * @param array $params
	 * @param string $format
	 */
	public function getApiUri($service, $operation, $params=array(), $format='xml')
	{
		$config = Mage::getModel('eb2ccore/config_registry')
			->addConfigModel(Mage::getSingleton('eb2ccore/config'));

		return sprintf(
			self::URI_FORMAT,
			$config->apiEnvironment,
			$config->apiRegion,
			$config->apiMajorVersion,
			$config->apiMinorVersion,
			$config->storeId,
			$service,
			$operation,
			(!empty($params)) ? '/' . implode('/', $params) : '',
			$format
		);
	}
	/**
	 * Create and return a new instance of TrueAction_Dom_Document.
	 * @return TrueAction_Dom_Document
	 */
	public function getNewDomDocument()
	{
		return new TrueAction_Dom_Document('1.0', 'UTF-8');
	}
}
