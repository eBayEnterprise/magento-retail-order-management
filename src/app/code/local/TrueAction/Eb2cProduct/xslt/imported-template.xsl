<?xml version = "1.0" encoding = "utf-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml"/>
	<xsl:template match="ItemMaster|ContentMaster|Prices|iShip">
		<product_to_be_imported>
			<xsl:for-each select="Item|Content|PricePerItem">
				<xsl:choose>
					<xsl:when test="@operation_type"><!-- the attribute exists in the node -->
						<xsl:if test="@operation_type!='Delete'"> <!-- the attribute doesn't equal to 'delete'-->
							<sku><xsl:value-of select="ItemId/ClientItemId|UniqueID|ClientItemId" /></sku>
						</xsl:if>
					</xsl:when>
					<xsl:otherwise> <!-- when the operation_type attribute doesn't exists in Content, PricePerItem node -->
						<sku><xsl:value-of select="ItemId/ClientItemId|UniqueID|ClientItemId" /></sku>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
		</product_to_be_imported>
	</xsl:template>
</xsl:stylesheet>