<?php
class TrueAction_Eb2cCore_Model_Config
	extends TrueAction_Eb2cCore_Model_Config_Abstract {

	protected $_configPaths = array(
		'api_environment'         => 'eb2ccore/api/environment',
		'api_key'                 => 'eb2ccore/api/key',
		'api_major_version'       => 'eb2ccore/api/major_version',
		'api_minor_version'       => 'eb2ccore/api/minor_version',
		'api_region'              => 'eb2ccore/api/region',
		'api_timeout'             => 'eb2ccore/api/timeout',
		'test_mode'               => 'eb2ccore/development/test_mode',
		'feed_destination_type'   => 'eb2ccore/feed/destination_type',
		'sftp_location'           => 'eb2ccore/feed/filetransfer_sftp_host',
		'sftp_username'           => 'eb2ccore/feed/filetransfer_sftp_username',
		'sftp_auth_type'          => 'eb2ccore/feed/filetransfer_sftp_auth_type',
		'sftp_password'           => 'eb2ccore/feed/filetransfer_sftp_password',
		'sftp_public_key'         => 'eb2ccore/feed/filetransfer_sftp_ssh_pub_key',
		'sftp_private_key'        => 'eb2ccore/feed/filetransfer_sftp_ssh_prv_key',
		'catalog_id'              => 'eb2ccore/general/catalog_id',
		'client_id'               => 'eb2ccore/general/client_id',
		'store_id'                => 'eb2ccore/general/store_id',
		'service_order_timeout'   => 'eb2ccore/service/order/timeout',
		'service_payment_timeout' => 'eb2ccore/service/payment/timeout',
		'feed_available_models'   => 'eb2ccore/feed/available_models',
	);
}
