# Import Processing Details

## Import Products into Different Magento Websites

The combination of incoming catalog_id, gsi_client_id and gsi_store_id are mapped to Magento Websites.

Product import first gathers all the websites for the Magento Instance, and then for each Magento Website,
extracts relevant information from each of the feed files according to these rules:

* The ‘catalog_id’, if present, must match the Magento-webite’s catalog_id. All Magento-websites for an instance use the same catalog_id, this effectively filters out and items that have a catalog_id, and that catalog_id does not match the Magento-website.
* The ‘client_id’, if present, must match the Magento-webite’s client_id.
* The ’store_id’, if present, must match the Magento-webite’s store_id.

It is important to note that the absence of an an attribute in the incoming feed effectively acts as a wildcard.

Consider a Magento installation with 2 websites, configured with the same client_id but different store_ids.
An incoming feed that specifies only the client_id will be assigned to **both** websites.

An incoming feed node specifying **none** of the these attributes will be assigned to **all** websites.
