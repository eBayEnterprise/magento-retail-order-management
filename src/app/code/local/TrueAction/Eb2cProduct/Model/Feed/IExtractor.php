<?php
/**
 * A common interface for all eb2cproduct feed extractor class  to implement
 */
interface TrueAction_Eb2cProduct_Model_Feed_IExtractor {
	public function extract(DOMDocument $doc);
}
