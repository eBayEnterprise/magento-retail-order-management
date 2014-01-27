<?xml version = "1.0" encoding = "utf-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml"/>
	<xsl:template match="ItemMaster|ContentMaster|Prices|iShip">
		<product_to_be_deleted>
			<xsl:for-each select="Item|Content|PricePerItem">
				<xsl:if test="@operation_type='Delete'">
					<sku><xsl:value-of select="ItemId/ClientItemId|UniqueID|ClientItemId" /></sku>
				</xsl:if>
			</xsl:for-each>
		</product_to_be_deleted>
	</xsl:template>
</xsl:stylesheet>