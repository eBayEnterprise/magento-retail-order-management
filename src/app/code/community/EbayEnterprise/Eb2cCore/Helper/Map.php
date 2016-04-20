<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class EbayEnterprise_Eb2cCore_Helper_Map extends EbayEnterprise_Eb2cCore_Helper_Data
{
    /**
     * extract the first element of a dom node list and return a string value
     * @param DOMNodeList $nodes
     * @return string
     */
    public function extractStringValue(DOMNodeList $nodes)
    {
        return ($nodes->length)? $nodes->item(0)->nodeValue : null;
    }
    /**
     * extract the first element of a dom node list and return a boolean
     * value of the extract string
     * @param DOMNodeList $nodes
     * @return bool
     */
    public function extractBoolValue(DOMNodeList $nodes)
    {
        return $this->parseBool(($nodes->length)? $nodes->item(0)->nodeValue : null);
    }
    /**
     * extract the first element of a dom node list and return the string value cast as integer value
     * @param DOMNodeList $nodes
     * @return int
     */
    public function extractIntValue(DOMNodeList $nodes)
    {
        return ($nodes->length)? (int) $nodes->item(0)->nodeValue : 0;
    }
}
