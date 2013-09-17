<?php
/**
 * A common interface that all eb2c classes which process feeds must implement. This represents
 * a 'contract' between the feed classes, and the Feed Shell classes that run them.
 */
interface TrueAction_Eb2cCore_Model_Feed_Interface
	{
	/**
	 * Must be defined by implementor - this is the point of entry to start process a feed.
	 */
	public function processFeeds();
}
