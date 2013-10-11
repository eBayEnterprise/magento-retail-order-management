<?php
$installer = $this;

$productIdViewNameIndexColumns = array(
	'sku',
	'view',
	'name',
	'height',
	'width',
);

$table = $installer->getConnection()
	->newTable($installer->getTable('eb2cmedia/images'))
	->addColumn('id',
		Varien_Db_Ddl_Table::TYPE_INTEGER,
		null,
		array(
			'unsigned' => true,
			'identity' => true,
			'nullable' => false,
			'primary'  => true,
		),
		'Image id'
	)
	->addColumn('sku',
		Varien_Db_Ddl_Table::TYPE_TEXT,
		64,
		array(),
		'SKU'
	)
	->addColumn('view',
		Varien_Db_Ddl_Table::TYPE_TEXT,
		128,
		array(),
		'Image View'
	)
	->addColumn('name',
		Varien_Db_Ddl_Table::TYPE_TEXT,
		128,
		array(),
		'Image Name'
	)
	->addColumn('height',
		Varien_Db_Ddl_Table::TYPE_INTEGER,
		null,
		array(),
		'Image Height'
	)
	->addColumn('width',
		Varien_Db_Ddl_Table::TYPE_INTEGER,
		null,
		array(),
		'Image Width'
	)
	->addColumn('url',
		Varien_Db_Ddl_Table::TYPE_TEXT,
		1024,
		array( 'nullable' => true,),
		'Image URL'
	)
	->addColumn('created_at',
		Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
		null,
		array(
			'nullable' => false,
			'default'  => Varien_Db_Ddl_Table::TIMESTAMP_INIT
		),
		'Created At'
	)
	->addColumn('updated_at',
		Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
		null,
		array(
			'nullable' => true
		),
		'Updated At'
	)
	->addIndex(
		$installer->getIdxName(
			'eb2cmedia/images',
			$productIdViewNameIndexColumns
		),
		$productIdViewNameIndexColumns,
		array (
			'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
		)
	);

$installer->getConnection()->createTable($table);
