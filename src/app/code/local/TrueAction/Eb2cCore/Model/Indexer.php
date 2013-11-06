<?php
class TrueAction_Eb2cCore_Model_Indexer
{
	/**
	 * Reindex everything; basically does what the command line shell script does
	 *
	 */
	public function reindexAll()
	{
		$indexerCollection = Mage::getModel('enterprise_index/indexer')->getProcessesCollection();

		Mage::dispatchEvent('shell_reindex_init_process');
		foreach ($indexerCollection as $process) {
			if ($process->getIndexer()->isVisible() !== false) {
				try {
					$process->reindexEverything();
					Mage::dispatchEvent($process->getIndexerCode() . '_shell_reindex_after');
					Mage::log('[ ' . __CLASS__ . ' ] ' . $process->getIndexer()->getName() . ' index rebuilt successfully', Zend_Log::INFO);
				} catch (Mage_Core_Exception $e) {
					Mage::logException($e);
				} catch (Exception $e) {
					Mage::logException($e);
				}
			}
		}
		Mage::dispatchEvent('shell_reindex_finalize_process');
	}
}
