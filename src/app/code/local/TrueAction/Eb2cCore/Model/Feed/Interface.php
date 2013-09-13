<?php
/**
 * A common interface for all eb2c classes which process feeds.
 * Note that phpcs insisted I indent the first brace '{'. As this file is so small and singular,
 * I complied, but may be tempted later to figure out why phpcs wanted it this way.
 */
interface TrueAction_Eb2cCore_Model_Feed_Interface
	{
	/**
	 * Must be defined by implementor - this is the point of entry to start process a feed.
	 */
	public function processFeeds();
}
