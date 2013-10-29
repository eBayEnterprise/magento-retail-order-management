<?php
class TrueAction_Eb2cCore_Model_Config extends TrueAction_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = array(
		'api_environment'             => 'eb2ccore/api/environment',
		'api_key'                     => 'eb2ccore/api/key',
		'api_major_version'           => 'eb2ccore/api/major_version',
		'api_minor_version'           => 'eb2ccore/api/minor_version',
		'api_namespace'               => 'eb2ccore/api/xml_namespace',
		'api_region'                  => 'eb2ccore/api/region',
		'api_timeout'                 => 'eb2ccore/api/timeout',
		'api_xsd_path'                => 'eb2ccore/api/xsd_path',
		'catalog_id'                  => 'eb2ccore/general/catalog_id',
		'client_customer_id_prefix'   => 'eb2ccore/general/client_customer_id_prefix',
		'client_id'                   => 'eb2ccore/general/client_id',
		'client_order_id_prefix'      => 'eb2ccore/general/client_order_id_prefix',
		'feed_available_models'       => 'eb2ccore/feed/available_models',
		'feed_destination_type'       => 'eb2ccore/feed/destination_type',
		'feed_enabled_reindex'        => 'eb2ccore/feed/enabled_reindex',
		'feed_fetch_connect_attempts' => 'eb2ccore/feed/filetransfer_remote_connect_attempts',
		'feed_fetch_retry_timer'      => 'eb2ccore/feed/filetransfer_remote_retry_timer',
		'service_order_timeout'       => 'eb2ccore/service/order/timeout',
		'service_payment_timeout'     => 'eb2ccore/service/payment/timeout',
		'sftp_auth_type'              => 'eb2ccore/feed/filetransfer_sftp_auth_type',
		'sftp_location'               => 'eb2ccore/feed/filetransfer_sftp_host',
		'sftp_password'               => 'eb2ccore/feed/filetransfer_sftp_password',
		'sftp_private_key'            => 'eb2ccore/feed/filetransfer_sftp_ssh_prv_key',
		'sftp_public_key'             => 'eb2ccore/feed/filetransfer_sftp_ssh_pub_key',
		'sftp_username'               => 'eb2ccore/feed/filetransfer_sftp_username',
		'store_id'                    => 'eb2ccore/general/store_id',
	);
}
