<?php
/**
 * 
 */
class TrueAction_Eb2cFraud_Helper_Http extends Mage_Core_Helper_Http
{
    /**
     * Retrieve HTTP USER AGENT
     *
     * @param boolean $clean clean non UTF-8 characters
     * @return string
     */
    public function getHttpAccept($clean = true)
    {
        return $this->_getHttpCleanValue('HTTP_ACCEPT', $clean);
    }

    /**
     * Retrieve HTTP ACCEPT LANGUAGE
     *
     * @param boolean $clean clean non UTF-8 characters
     * @return string
     */
    public function getHttpAcceptEncoding($clean = true)
    {
        return $this->_getHttpCleanValue('HTTP_ACCEPT_ENCODING', $clean);
    }
}
