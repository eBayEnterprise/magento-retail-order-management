<?php

class TrueAction_Eb2cPayment_Overrides_Helper_Data extends Enterprise_GiftCardAccount_Helper_Data
{
	/**
	 * Maximal gift card pan length according to database table definitions (longer codes are truncated)
	 */
	const GIFT_CARD_PAN_MAX_LENGTH = 22;

	/**
	 * Maximal gift card pin length according to database table definitions (longer codes are truncated)
	 */
	const GIFT_CARD_PIN_MAX_LENGTH = 8;
}
