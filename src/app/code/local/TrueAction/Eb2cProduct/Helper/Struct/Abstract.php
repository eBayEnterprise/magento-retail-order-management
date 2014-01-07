<?php
abstract class TrueAction_Eb2cProduct_Helper_Struct_Abstract
{
	const VALUE = 'undefined';
	public function getValue()
	{
		// Late static binding so subclasses can override VALUE without overriding getValue.
		return static::VALUE;
	}

}
