<?php
/**
 * A common interface for all eb2c classes which process feeds. 
 */
interface TrueAction_Eb2cCore_Model_Feed_Interface
	{
	/**
	 * Must be defined by implementor - this is the point of entry to start process a feed.
	 *
	 */
	public function processFeeds();
}
