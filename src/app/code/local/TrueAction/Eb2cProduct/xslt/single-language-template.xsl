<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" indent="yes" encoding="UTF-8"/>
	<xsl:strip-space elements="*" />

	<!-- 
		The purpose of this transform is to deliver only those elements relevant to 'lang_code'.
		'lang_code' comparison is case-insensitive, although the output will be present unchanged.
	-->
	<xsl:param name="lang_code">en-US</xsl:param>
	<xsl:variable name="lower" select="'abcdefghijklmnopqrstuvwxyz'" />
	<xsl:variable name="upper" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" />

	<!--
		Normalize the outer structure of the DOM:
		Replace root node with an Items node and the
		iterable child nodes with Item nodes
	-->
	<xsl:template match="/*">
		<Items><xsl:apply-templates /></Items>
	</xsl:template>

	<!--
		Need to copy over any SKU data which may be within any of the three nodes
	-->
	<xsl:template match="ItemId|UniqueID|ClientItemId">
		<xsl:copy-of select="."/>
	</xsl:template>

	<!--
		The "main" section of this. Catches any node and then looks for it to contain
		any child nodes that need to be copier over. When it does, copy the current
		node and recurse over the children.
	-->
	<xsl:template match="node()">
		<xsl:choose>
			<!--
				Recurse over any nodes that have the proper xml:lang or children
				with the proper xml:lang, first copying the current node and any
				attributes of the node.
			-->
			<xsl:when test="*[.//*[translate(@xml:lang, $upper, $lower)=translate($lang_code, $upper, $lower)]]|*[translate(@xml:lang, $upper, $lower)=translate($lang_code, $upper, $lower)]">
				<xsl:copy>
					<xsl:copy-of select="@*" />
					<xsl:apply-templates select="*" />
				</xsl:copy>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="ColorAttributes/Color|SizeAttributes/Size">
		<xsl:copy>
			<xsl:copy-of select="Code" />
			<xsl:apply-templates/>
		</xsl:copy>
	</xsl:template>

	<!--
		Any nodes with an xml:lang attribute encountered should be for the proper
		languages, else they would have been filtered out already.
	-->
	<xsl:template match="*[@xml:lang]">
		<xsl:if test="translate(@xml:lang, $upper, $lower) = translate($lang_code, $upper, $lower)">
			<xsl:copy-of select="." />
		</xsl:if>
	</xsl:template>

	<xsl:template match="/*/*">
		<xsl:if test="*[.//*[translate(@xml:lang, $upper, $lower)=translate($lang_code, $upper, $lower)]]">
			<Item>
				<xsl:copy-of select="@*" />
				<xsl:apply-templates/>
			</Item>
		</xsl:if>
	</xsl:template>

	<!--
		Only want items that will be added/updated so filter out any deletes.
	-->
	<xsl:template match="/*/Item[@operation_type='Delete']" />

</xsl:stylesheet>
