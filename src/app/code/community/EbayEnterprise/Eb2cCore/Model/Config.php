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

class EbayEnterprise_Eb2cCore_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
	protected $_configPaths = [
		'api_hostname'                => 'eb2ccore/api/hostname',
		'api_key'                     => 'eb2ccore/api/key',
		'api_major_version'           => 'eb2ccore/api/major_version',
		'api_minor_version'           => 'eb2ccore/api/minor_version',
		'api_namespace'               => 'eb2ccore/api/xml_namespace',
		'api_timeout'                 => 'eb2ccore/api/timeout',
		'api_xsd_path'                => 'eb2ccore/api/xsd_path',
		'catalog_id'                  => 'eb2ccore/general/catalog_id',
		'client_customer_id_prefix'   => 'eb2ccore/general/client_customer_id_prefix',
		'client_id'                   => 'eb2ccore/general/client_id',
		'client_order_id_prefix'      => 'eb2ccore/general/client_order_id_prefix',
		'delete_remote_feed_files'    => 'eb2ccore/feed/delete_remote_feed_files',
		'error_feed'                  => 'eb2ccore/feed/filetransfer_exports/error_confirmations',
		'error_feed_filename_format'  => 'eb2ccore/feed/filetransfer_exports/error_confirmations/filename_format',
		// ack filetransfer imports for acks from eb2c for feeds we've sent
		'feed_ack_inbox'              => 'eb2ccore/feed/filetransfer_imports/acknowledgements/local_directory',
		'feed_ack_error_directory'    => 'eb2ccore/feed/filetransfer_imports/acknowledgements/local_error_directory',
		// ack filetransfer exports for acks we send for files eb2c sent us
		'feed_ack_export'             => 'eb2ccore/feed/filetransfer_exports/acknowledgements',
		'feed_ack_filename_format'    => 'eb2ccore/feed/filetransfer_exports/acknowledgements/filename_format',
		'feed_ack_outbox'             => 'eb2ccore/feed/filetransfer_exports/acknowledgements/local_directory',
		'feed_ack_timestamp_format'   => 'eb2ccore/feed/filetransfer_exports/acknowledgements/timestamp_format',
		'feed_ack_xsd'                => 'eb2ccore/feed/filetransfer_exports/acknowledgements/xsd',
		'feed_available_models'       => 'eb2ccore/feed/available_models',
		'feed_destination_type'       => 'eb2ccore/feed/destination_type',
		'feed_enabled_reindex'        => 'eb2ccore/feed/enabled_reindex',
		'feed_export_archive'         => 'eb2ccore/feed/export_archive',
		'feed_header_template'        => 'eb2ccore/feed/header_template',
		'feed_import_archive'         => 'eb2ccore/feed/import_archive',
		'feed_outbox'                 => 'eb2ccore/feed/filetransfer_exports/eb2c_outbox',
		'feed_outbox_directory'       => 'eb2ccore/feed/filetransfer_exports/eb2c_outbox/local_directory',
		'feed_processing_directory'   => 'eb2ccore/feed/processing_directory',
		'feed_sent_directory'         => 'eb2ccore/feed/filetransfer_exports/eb2c_outbox/sent_directory',
		'inventory_expiration_time'   => 'eb2ccore/service/inventory/expiration',
		'language_code'               => 'eb2ccore/general/language_code',
		'service_order_timeout'       => 'eb2ccore/service/order/timeout',
		'service_payment_timeout'     => 'eb2ccore/service/payment/timeout',
		'sftp_config'                 => 'eb2ccore/feed',
		'sftp_auth_type'              => 'eb2ccore/feed/filetransfer_sftp_auth_type',
		'sftp_location'               => 'eb2ccore/feed/filetransfer_sftp_host',
		'sftp_password'               => 'eb2ccore/feed/filetransfer_sftp_password',
		'sftp_port'                   => 'eb2ccore/feed/filetransfer_sftp_port',
		'sftp_private_key'            => 'eb2ccore/feed/filetransfer_sftp_ssh_prv_key',
		'sftp_username'               => 'eb2ccore/feed/filetransfer_sftp_username',
		'store_id'                    => 'eb2ccore/general/store_id',
		'ack_resend_time_limit'       => 'eb2ccore/feed/export/ack/resend_time_limit',
		'shipping_method_map'         => 'eb2ccore/shipmap',
	];
}
