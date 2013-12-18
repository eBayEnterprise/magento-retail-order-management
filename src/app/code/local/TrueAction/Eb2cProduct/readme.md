# Feed Definition Xml Specification
---

definition
	item_master
		base_xpath
			"Path/To/Unit"
		config
			local_path
				"contentFeedLocalPath"
			remote_path
				"contentFeedRemotePath"
			file_pattern
				"contentFeedFilePattern"
		extractors
			operation_type_example
				model
					"eb2cproduct/feed_extractor_specialized_operationtype"
				init
					xpath_parameter_example
						"x/path/to/operation"

			xpath_extractor_example
				model
					"eb2cproduct/feed_extractor_xpath"
				init
					mapping_parameter_example
						output_field_name
							"x/path/to/node"
						...
			mappinglist_extractor_example
				model
					"eb2cproduct/feed_extractor_mappinglist"
				init
					the_mapping_parent
						the_parent_field
							"x/path/to/operation"
					the_mapping
						the_field_name
							"x/path/to/node"
						...
	another_feed_type
	...

### Element Definitions
---
definition: serves as the root node for the definition document

definition/feed_type_element: identifies the type of feed.
 - should be mappable to the event type in the feed's header message.
 - serves as the root element for a feed type's definition.

//feed_type_element/base_xpath: the xpath string used to split the document into units.

//feed_type_element/config: contain names for use when getting information from the config registry

//config/local_path: name used to get the local path value from the config registry.
 - @see eb2cpayment/config for the names
 - @see [Config Registry Documentation](https://trueaction.atlassian.net/wiki/display/EBC/Core+Config+Class)

//config/remote_path: name used to get the remote path value from the config registry.
 - @see local_path_config

//config/file_pattern: name used with the config registry to get the pattern glob needed to select files for the specific feed.
 - @see local_path_config

//extractors: contains definitions for the different extractors used to parse the feed.

//extractors/extractor_element: contains necessary information to setup an extractor model.

//extractor_element/model: alias string for the extractor model

//extractor_element/init: container for the initialization parameters for the model

### Initialization Parameters
---
* Parameter names are not hard set right now, but should be kept consistent between structures used to represent the same type of parameter.
* When giving parameters a name, remember that sibling elements with the same name will overwrite each other in the order they're loaded by magento.
* The structure used for the mappinglist extractor is also suitable for the feed_extractor_color model.
