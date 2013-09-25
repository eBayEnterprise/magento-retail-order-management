<?php
$installer = $this;

$table = $installer->getConnection()
	->newTable($installer->getTable('eb2cproduct/images'))
	->addColumn('value_id',
		Varien_Db_Ddl_Table::TYPE_INTEGER,
		null,
		array(
			'unsigned' => true,
			'identity' => true,
			'nullable' => false,
			'primary'  => true,
		),
		'Image id')
	->addColumn('product_id',
		Varien_Db_Ddl_Table::TYPE_INTEGER,
		null,
		array(),
		'Product Id')
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
	->addColumn('url',
		Varien_Db_Ddl_Table::TYPE_TEXT,
		255,
		array( 'nullable' => true,),
		'Image URL'
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
	);

$installer->getConnection()->createTable($table);
