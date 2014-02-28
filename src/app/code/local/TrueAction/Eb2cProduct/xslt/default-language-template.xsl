<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" indent="yes" encoding="UTF-8"/>
	<xsl:strip-space elements="*" />

	<!-- Although you can specify lang_code in any case, we'll test it as all-lower -->
	<xsl:param name="lang_code">en-US</xsl:param>

	<!--
		We are XSLT 1.0 conformant, so we aren't using XSLT 2.0's upper-case() and lower-case().
		We have to translate(thing, $upper, $lower) - so we declare 'lower' and 'upper'
		as we'll need to translate more than once
	 -->
	<xsl:variable name="lower" select="'abcdefghijklmnopqrstuvwxyz'" />
	<xsl:variable name="upper" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />

	<!--
		Apply templates when we match any node that doesn't specify a language,
		/or/ any node that does specify a language /and/ it's the language we're looking for.
		/or/ any other attribute
	-->
	<xsl:template match="node()|@*">
		<xsl:copy>
			<xsl:apply-templates select="*[not(@xml:lang)]|*[normalize-space(translate(@xml:lang, $upper, $lower))=normalize-space(translate($lang_code, $upper, $lower))]|@*|text()" />
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

	<xsl:template match="@xml:lang">
		<xsl:attribute name="xml:lang">
			<xsl:value-of select="normalize-space(translate(., $upper, $lower))" />
		</xsl:attribute>
	</xsl:template>

	<!--
		Only want items that will be added/updated so filter out any deletes.
	-->
	<xsl:template match="/*/Item[@operation_type='Delete']" />

</xsl:stylesheet>
