<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" indent="yes" encoding="UTF-8"/>
	<xsl:strip-space elements="*" />
	<xsl:param name="lang_code">en-US</xsl:param>

	<xsl:template match="node()|@*">
		<xsl:copy>
			<xsl:apply-templates select="*[not(@xml:lang)]|*[@xml:lang=$lang_code]|@*|text()" />
		</xsl:copy>
	</xsl:template>

	<!--
		Normalize the outer structure of the DOM:
		Replace root node with an Items node and the
		iterable child nodes with Item nodes
	-->
	<xsl:template match="/*">
		<Items><xsl:apply-templates /></Items>
	</xsl:template>
	<xsl:template match="/*/Content|/*/Item|/*/PricePerItem">
		<Item>
			<xsl:copy-of select="@*" />
			<xsl:apply-templates />
		</Item>
	</xsl:template>

	<!--
		Only want items that will be added/updated so filter out any deletes.
	-->
	<xsl:template match="/*/Item[@operation_type='Delete']" />

</xsl:stylesheet>
