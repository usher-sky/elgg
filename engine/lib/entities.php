<?php
/**
 * Procedural code for creating, loading, and modifying \ElggEntity objects.
 */

use Elgg\Project\Paths;

/**
 * Return the id for a given subtype.
 *
 * \ElggEntity objects have a type and a subtype.  Subtypes
 * are defined upon creation and cannot be changed.
 *
 * Plugin authors generally don't need to use this function
 * unless writing their own SQL queries.  Use {@link \ElggEntity::getSubtype()}
 * to return the string subtype.
 *
 * Subtypes are stored in the entity_subtypes table.  There is a foreign
 * key in the entities table.
 *
 * @param string $type    Type
 * @param string $subtype Subtype
 *
 * @return int Subtype ID
 * @see get_subtype_from_id()
 */
function get_subtype_id($type, $subtype) {
	return _elgg_services()->subtypeTable->getId($type, $subtype);
}

/**
 * Gets the denormalized string for a given subtype ID.
 *
 * @param int $subtype_id Subtype ID from database
 * @return string|false Subtype name, false if subtype not found
 * @see get_subtype_id()
 * @access private
 */
function get_subtype_from_id($subtype_id) {
	return _elgg_services()->subtypeTable->getSubtype($subtype_id);
}

/**
 * Return the class name for a registered type and subtype.
 *
 * Entities can be registered to always be loaded as a certain class
 * with add_subtype() or update_subtype(). This function returns the class
 * name if found and null if not.
 *
 * @param string $type    The type
 * @param string $subtype The subtype
 *
 * @return string|null a class name or null
 * @see get_subtype_from_id()
 * @see get_subtype_class_from_id()
 */
function get_subtype_class($type, $subtype) {
	return _elgg_services()->subtypeTable->getClass($type, $subtype);
}

/**
 * Returns the class name for a subtype id.
 *
 * @param int $subtype_id The subtype id
 *
 * @return string|null
 * @see get_subtype_class()
 * @see get_subtype_from_id()
 * @access private
 */
function get_subtype_class_from_id($subtype_id) {
	return _elgg_services()->subtypeTable->getClassFromId($subtype_id);
}

/**
 * Register \ElggEntities with a certain type and subtype to be loaded as a specific class.
 *
 * By default entities are loaded as one of the 4 parent objects: site, user, object, or group.
 * If you subclass any of these you can register the classname with add_subtype() so
 * it will be loaded as that class automatically when retrieved from the database with
 * {@link get_entity()}.
 *
 * @warning This function cannot be used to change the class for a type-subtype pair.
 * Use update_subtype() for that.
 *
 * @param string $type    The type you're subtyping (site, user, object, or group)
 * @param string $subtype The subtype
 * @param string $class   Optional class name for the object
 *
 * @return int
 * @see update_subtype()
 * @see remove_subtype()
 * @see get_entity()
 */
function add_subtype($type, $subtype, $class = "") {
	return _elgg_services()->subtypeTable->add($type, $subtype, $class);
}

/**
 * Removes a registered \ElggEntity type, subtype, and classname.
 *
 * @warning You do not want to use this function. If you want to unregister
 * a class for a subtype, use update_subtype(). Using this function will
 * permanently orphan all the objects created with the specified subtype.
 *
 * @param string $type    Type
 * @param string $subtype Subtype
 *
 * @return bool
 * @see add_subtype()
 * @see update_subtype()
 */
function remove_subtype($type, $subtype) {
	return _elgg_services()->subtypeTable->remove($type, $subtype);
}

/**
 * Update a registered \ElggEntity type, subtype, and class name
 *
 * @param string $type    Type
 * @param string $subtype Subtype
 * @param string $class   Class name to use when loading this entity
 *
 * @return bool
 */
function update_subtype($type, $subtype, $class = '') {
	return _elgg_services()->subtypeTable->update($type, $subtype, $class);
}

/**
 * Determine if a given user can write to an entity container.
 *
 * An entity can be a container for any other entity by setting the
 * container_guid.  container_guid can differ from owner_guid.
 *
 * A plugin hook container_permissions_check:$entity_type is emitted to allow granular
 * access controls in plugins.
 *
 * @param int    $user_guid      The user guid, or 0 for logged in user.
 * @param int    $container_guid The container, or 0 for the current page owner.
 * @param string $type           The type of entity we want to create (default: 'all')
 * @param string $subtype        The subtype of the entity we want to create (default: 'all')
 *
 * @return bool
 * @deprecated 2.2
 */
function can_write_to_container($user_guid = 0, $container_guid = 0, $type = 'all', $subtype = 'all') {
	elgg_deprecated_notice(__FUNCTION__ . ' is deprecated. Use ElggEntity::canWriteToContainer()', '2.2');
	if (!$container_guid) {
		$container_guid = elgg_get_page_owner_guid();
	}
	$container = get_entity($container_guid);
	if (!$container) {
		return false;
	}

	return $container->canWriteToContainer($user_guid, $type, $subtype);
}

/**
 * Returns a database row from the entities table.
 *
 * @tip Use get_entity() to return the fully loaded entity.
 *
 * @warning This will only return results if a) it exists, b) you have access to it.
 * see {@link _elgg_get_access_where_sql()}.
 *
 * @param int $guid The GUID of the object to extract
 *
 * @return \stdClass|false
 * @see entity_row_to_elggstar()
 * @access private
 */
function get_entity_as_row($guid) {
	return _elgg_services()->entityTable->getRow($guid);
}

/**
 * Return the site specific details of a site by a row.
 *
 * @param int $guid The site GUID
 *
 * @return mixed
 * @access private
 */
function get_site_entity_as_row($guid) {
	$guid = (int) $guid;
	$prefix = _elgg_config()->dbprefix;
	return get_data_row("SELECT * FROM {$prefix}sites_entity WHERE guid = $guid");
}

/**
 * Return the object specific details of a object by a row.
 *
 * @param int $guid The guid to retrieve
 *
 * @return bool
 * @access private
 */
function get_object_entity_as_row($guid) {
	$dbprefix = _elgg_config()->dbprefix;
	$sql = "SELECT * FROM {$dbprefix}objects_entity WHERE guid = :guid";
	$params = [
		':guid' => (int) $guid,
	];
	return _elgg_services()->db->getDataRow($sql, null, $params);
}

/**
 * Return the user specific details of a user by a row.
 *
 * @param int $guid The \ElggUser guid
 *
 * @return mixed
 * @access private
 */
function get_user_entity_as_row($guid) {
	return _elgg_services()->usersTable->getRow($guid);
}

/**
 * Create an Elgg* object from a given entity row.
 *
 * Handles loading all tables into the correct class.
 *
 * @param \stdClass $row The row of the entry in the entities table.
 *
 * @return \ElggEntity|false
 * @see get_entity_as_row()
 * @see add_subtype()
 * @see get_entity()
 * @access private
 *
 * @throws ClassException|InstallationException
 */
function entity_row_to_elggstar($row) {
	return _elgg_services()->entityTable->rowToElggStar($row);
}

/**
 * Loads and returns an entity object from a guid.
 *
 * @param int $guid The GUID of the entity
 *
 * @return \ElggEntity The correct Elgg or custom object based upon entity type and subtype
 */
function get_entity($guid) {
	if ($guid == 1) {
		return _elgg_config()->site;
	}
	return _elgg_services()->entityTable->get($guid);
}

/**
 * Does an entity exist?
 *
 * This function checks for the existence of an entity independent of access
 * permissions. It is useful for situations when a user cannot access an entity
 * and it must be determined whether entity has been deleted or the access level
 * has changed.
 *
 * @param int $guid The GUID of the entity
 *
 * @return bool
 * @since 1.8.0
 */
function elgg_entity_exists($guid) {
	return _elgg_services()->entityTable->exists($guid);
}

/**
 * Enable an entity.
 *
 * @param int  $guid      GUID of entity to enable
 * @param bool $recursive Recursively enable all entities disabled with the entity?
 *
 * @return bool
 * @since 1.9.0
 */
function elgg_enable_entity($guid, $recursive = true) {
	return _elgg_services()->entityTable->enable($guid, $recursive);
}

/**
 * Get the current site entity
 *
 * @return \ElggSite
 * @since 1.8.0
 */
function elgg_get_site_entity() {
	return _elgg_config()->site;
}

/**
 * Returns an array of entities with optional filtering.
 *
 * Entities are the basic unit of storage in Elgg.  This function
 * provides the simplest way to get an array of entities.  There
 * are many options available that can be passed to filter
 * what sorts of entities are returned.
 *
 * @tip To output formatted strings of entities, use {@link elgg_list_entities()} and
 * its cousins.
 *
 * @tip Plural arguments can be written as singular if only specifying a
 * single element.  ('type' => 'object' vs 'types' => array('object')).
 *
 * @param array $options Array in format:
 *
 * 	types => null|STR entity type (type IN ('type1', 'type2')
 *           Joined with subtypes by AND. See below)
 *
 * 	subtypes => null|STR entity subtype (SQL: subtype IN ('subtype1', 'subtype2))
 *              Use ELGG_ENTITIES_NO_VALUE to match the default subtype.
 *              Use ELGG_ENTITIES_ANY_VALUE to match any subtype.
 *
 * 	type_subtype_pairs => null|ARR (array('type' => 'subtype'))
 *                        array(
 *                            'object' => array('blog', 'file'), // All objects with subtype of 'blog' or 'file'
 *                            'user' => ELGG_ENTITY_ANY_VALUE, // All users irrespective of subtype
 *                        );
 *
 *	guids => null|ARR Array of entity guids
 *
 * 	owner_guids => null|ARR Array of owner guids
 *
 * 	container_guids => null|ARR Array of container_guids
 *
 * 	order_by => null (time_created desc)|STR SQL order by clause
 *
 *  reverse_order_by => BOOL Reverse the default order by clause
 *
 * 	limit => null (from settings)|INT SQL limit clause (0 means no limit)
 *
 * 	offset => null (0)|INT SQL offset clause
 *
 * 	created_time_lower => null|INT Created time lower boundary in epoch time
 *
 * 	created_time_upper => null|INT Created time upper boundary in epoch time
 *
 * 	modified_time_lower => null|INT Modified time lower boundary in epoch time
 *
 * 	modified_time_upper => null|INT Modified time upper boundary in epoch time
 *
 * 	count => true|false return a count instead of entities
 *
 * 	wheres => array() Additional where clauses to AND together
 *
 * 	joins => array() Additional joins
 *
 * 	preload_owners => bool (false) If set to true, this function will preload
 * 					  all the owners of the returned entities resulting in better
 * 					  performance when displaying entities owned by several users
 *
 * 	callback => string A callback function to pass each row through
 *
 * 	distinct => bool (true) If set to false, Elgg will drop the DISTINCT clause from
 *				the MySQL query, which will improve performance in some situations.
 *				Avoid setting this option without a full understanding of the underlying
 *				SQL query Elgg creates.
 *
 *  batch => bool (false) If set to true, an Elgg\BatchResult object will be returned instead of an array.
 *           Since 2.3
 *
 *  batch_inc_offset => bool (true) If "batch" is used, this tells the batch to increment the offset
 *                      on each fetch. This must be set to false if you delete the batched results.
 *
 *  batch_size => int (25) If "batch" is used, this is the number of entities/rows to pull in before
 *                requesting more.
 *
 * @return \ElggEntity[]|int|mixed If count, int. Otherwise an array or an Elgg\BatchResult. false on errors.
 *
 * @since 1.7.0
 * @see elgg_get_entities_from_metadata()
 * @see elgg_get_entities_from_relationship()
 * @see elgg_get_entities_from_access_id()
 * @see elgg_get_entities_from_annotations()
 * @see elgg_list_entities()
 */
function elgg_get_entities(array $options = []) {
	return _elgg_services()->entityTable->getEntities($options);
}

/**
 * Returns SQL where clause for owner and containers.
 *
 * @param string     $column Column name the guids should be checked against. Usually
 *                           best to provide in table.column format.
 * @param null|array $guids  Array of GUIDs.
 *
 * @return false|string
 * @since 1.8.0
 * @access private
 */
function _elgg_get_guid_based_where_sql($column, $guids) {
	return _elgg_services()->entityTable->getGuidBasedWhereSql($column, $guids);
}

/**
 * Returns SQL where clause for entity time limits.
 *
 * @param string   $table              Entity table prefix as defined in
 *                                     SELECT...FROM entities $table
 * @param null|int $time_created_upper Time created upper limit
 * @param null|int $time_created_lower Time created lower limit
 * @param null|int $time_updated_upper Time updated upper limit
 * @param null|int $time_updated_lower Time updated lower limit
 *
 * @return false|string false on fail, string on success.
 * @since 1.7.0
 * @access private
 */
function _elgg_get_entity_time_where_sql($table, $time_created_upper = null,
		$time_created_lower = null, $time_updated_upper = null, $time_updated_lower = null) {
	return _elgg_services()->entityTable->getEntityTimeWhereSql($table,
		$time_created_upper, $time_created_lower, $time_updated_upper, $time_updated_lower);
}

/**
 * Returns a string of rendered entities.
 *
 * Displays list of entities with formatting specified by the entity view.
 *
 * @tip Pagination is handled automatically.
 *
 * @note Internal: This also provides the views for elgg_view_annotation().
 *
 * @note Internal: If the initial COUNT query returns 0, the $getter will not be called again.
 *
 * @param array    $options Any options from $getter options plus:
 *                   item_view => STR Optional. Alternative view used to render list items
 *                   full_view => BOOL Display full view of entities (default: false)
 *                   list_type => STR 'list', 'gallery', or 'table'
 *                   columns => ARR instances of Elgg\Views\TableColumn if list_type is "table"
 *                   list_type_toggle => BOOL Display gallery / list switch
 *                   pagination => BOOL Display pagination links
 *                   no_results => STR|Closure Message to display when there are no entities
 *
 * @param callback $getter  The entity getter function to use to fetch the entities.
 * @param callback $viewer  The function to use to view the entity list.
 *
 * @return string
 * @since 1.7
 * @see elgg_get_entities()
 * @see elgg_view_entity_list()
 */
function elgg_list_entities(array $options = [], $getter = 'elgg_get_entities',
	$viewer = 'elgg_view_entity_list') {

	elgg_register_rss_link();

	$offset_key = isset($options['offset_key']) ? $options['offset_key'] : 'offset';

	$defaults = [
		'offset' => (int) max(get_input($offset_key, 0), 0),
		'limit' => (int) max(get_input('limit', _elgg_config()->default_limit), 0),
		'full_view' => false,
		'list_type_toggle' => false,
		'pagination' => true,
		'no_results' => '',
	];

	$options = array_merge($defaults, $options);

	$entities = [];
	
	if (!$options['pagination']) {
		$options['count'] = false;
		$entities = call_user_func($getter, $options);
		unset($options['count']);
	} else {
		$options['count'] = true;
		$count = call_user_func($getter, $options);
	
		if ($count > 0) {
			$options['count'] = false;
			$entities = call_user_func($getter, $options);
		}

		$options['count'] = $count;
	}
	
	return call_user_func($viewer, $entities, $options);
}

/**
 * Gets entities based upon attributes in secondary tables.
 * Also accepts all options available to elgg_get_entities(),
 * elgg_get_entities_from_metadata(), and elgg_get_entities_from_relationship().
 *
 * @warning requires that the entity type be specified and there can only be one
 * type.
 *
 * @see elgg_get_entities
 * @see elgg_get_entities_from_metadata
 * @see elgg_get_entities_from_relationship
 *
 * @param array $options Array in format:
 *
 * 	attribute_name_value_pairs => ARR (
 *                                   'name' => 'name',
 *                                   'value' => 'value',
 *                                   'operand' => '=', (optional)
 *                                   'case_sensitive' => false (optional)
 *                                  )
 * 	                             If multiple values are sent via
 *                               an array ('value' => array('value1', 'value2')
 *                               the pair's operand will be forced to "IN".
 *
 * 	attribute_name_value_pairs_operator => null|STR The operator to use for combining
 *                                        (name = value) OPERATOR (name = value); default is AND
 *
 * @return \ElggEntity[]|mixed If count, int. If not count, array. false on errors.
 * @since 1.9.0
 * @throws InvalidArgumentException
 * @todo Does not support ordering by attributes or using an attribute pair shortcut like this ('title' => 'foo')
 */
function elgg_get_entities_from_attributes(array $options = []) {
	return _elgg_services()->entityTable->getEntitiesFromAttributes($options);
}

/**
 * Get the join and where clauses for working with entity attributes
 *
 * @return false|array False on fail, array('joins', 'wheres')
 * @since 1.9.0
 * @access private
 * @throws InvalidArgumentException
 */
function _elgg_get_entity_attribute_where_sql(array $options = []) {
	return _elgg_services()->entityTable->getEntityAttributeWhereSql($options);
}

/**
 * Returns a list of months in which entities were updated or created.
 *
 * @tip Use this to generate a list of archives by month for when entities were added or updated.
 *
 * @todo document how to pass in array for $subtype
 *
 * @warning Months are returned in the form YYYYMM.
 *
 * @param string $type           The type of entity
 * @param string $subtype        The subtype of entity
 * @param int    $container_guid The container GUID that the entities belong to
 * @param int    $ignored        Ignored parameter
 * @param string $order_by       Order_by SQL order by clause
 *
 * @return array|false Either an array months as YYYYMM, or false on failure
 */
function get_entity_dates($type = '', $subtype = '', $container_guid = 0, $ignored = 0, $order_by = 'time_created') {
	return _elgg_services()->entityTable->getDates($type, $subtype, $container_guid, $order_by);
}

/**
 * Registers an entity type and subtype as a public-facing entity that should
 * be shown in search and by {@link elgg_list_registered_entities()}.
 *
 * @warning Entities that aren't registered here will not show up in search.
 *
 * @tip Add a language string item:type:subtype to make sure the items are display properly.
 *
 * @param string $type    The type of entity (object, site, user, group)
 * @param string $subtype The subtype to register (may be blank)
 *
 * @return bool Depending on success
 * @see get_registered_entity_types()
 */
function elgg_register_entity_type($type, $subtype = null) {
	$type = strtolower($type);
	if (!in_array($type, \Elgg\Config::getEntityTypes())) {
		return false;
	}

	$entities = _elgg_config()->registered_entities;
	if (!$entities) {
		$entities = [];
	}

	if (!isset($entities[$type])) {
		$entities[$type] = [];
	}

	if ($subtype) {
		$entities[$type][] = $subtype;
	}

	_elgg_config()->registered_entities = $entities;

	return true;
}

/**
 * Unregisters an entity type and subtype as a public-facing type.
 *
 * @warning With a blank subtype, it unregisters that entity type including
 * all subtypes. This must be called after all subtypes have been registered.
 *
 * @param string $type    The type of entity (object, site, user, group)
 * @param string $subtype The subtype to register (may be blank)
 *
 * @return bool Depending on success
 * @see elgg_register_entity_type()
 */
function elgg_unregister_entity_type($type, $subtype = null) {
	$type = strtolower($type);
	if (!in_array($type, \Elgg\Config::getEntityTypes())) {
		return false;
	}

	$entities = _elgg_config()->registered_entities;
	if (!$entities) {
		return false;
	}

	if (!isset($entities[$type])) {
		return false;
	}

	if ($subtype) {
		if (in_array($subtype, $entities[$type])) {
			$key = array_search($subtype, $entities[$type]);
			unset($entities[$type][$key]);
		} else {
			return false;
		}
	} else {
		unset($entities[$type]);
	}

	_elgg_config()->registered_entities = $entities;
	return true;
}

/**
 * Returns registered entity types and subtypes
 *
 * @param string $type The type of entity (object, site, user, group) or blank for all
 *
 * @return array|false Depending on whether entities have been registered
 * @see elgg_register_entity_type()
 */
function get_registered_entity_types($type = null) {
	$registered_entities = _elgg_config()->registered_entities;
	if (!$registered_entities) {
		return false;
	}

	if ($type) {
		$type = strtolower($type);
	}

	if (!empty($type) && empty($registered_entities[$type])) {
		return false;
	}

	if (empty($type)) {
		return $registered_entities;
	}

	return $registered_entities[$type];
}

/**
 * Returns if the entity type and subtype have been registered with {@link elgg_register_entity_type()}.
 *
 * @param string $type    The type of entity (object, site, user, group)
 * @param string $subtype The subtype (may be blank)
 *
 * @return bool Depending on whether or not the type has been registered
 */
function is_registered_entity_type($type, $subtype = null) {
	$registered_entities = _elgg_config()->registered_entities;
	if (!$registered_entities) {
		return true;
	}

	$type = strtolower($type);

	// @todo registering a subtype implicitly registers the type.
	// see #2684
	if (!isset($registered_entities[$type])) {
		return false;
	}

	if ($subtype && !in_array($subtype, $registered_entities[$type])) {
		return false;
	}
	return true;
}

/**
 * Returns a viewable list of entities based on the registered types.
 *
 * @see elgg_view_entity_list
 *
 * @param array $options Any elgg_get_entity() options plus:
 *
 * 	full_view => BOOL Display full view entities
 *
 * 	list_type_toggle => BOOL Display gallery / list switch
 *
 * 	allowed_types => true|ARRAY True to show all types or an array of valid types.
 *
 * 	pagination => BOOL Display pagination links
 *
 * @return string A viewable list of entities
 * @since 1.7.0
 */
function elgg_list_registered_entities(array $options = []) {
	elgg_register_rss_link();

	$defaults = [
		'full_view' => false,
		'allowed_types' => true,
		'list_type_toggle' => false,
		'pagination' => true,
		'offset' => 0,
		'types' => [],
		'type_subtype_pairs' => [],
	];

	$options = array_merge($defaults, $options);

	$types = get_registered_entity_types();

	foreach ($types as $type => $subtype_array) {
		if (in_array($type, $options['allowed_types']) || $options['allowed_types'] === true) {
			// you must explicitly register types to show up in here and in search for objects
			if ($type == 'object') {
				if (is_array($subtype_array) && count($subtype_array)) {
					$options['type_subtype_pairs'][$type] = $subtype_array;
				}
			} else {
				if (is_array($subtype_array) && count($subtype_array)) {
					$options['type_subtype_pairs'][$type] = $subtype_array;
				} else {
					$options['type_subtype_pairs'][$type] = ELGG_ENTITIES_ANY_VALUE;
				}
			}
		}
	}

	if (!empty($options['type_subtype_pairs'])) {
		$count = elgg_get_entities(array_merge(['count' => true], $options));
		if ($count > 0) {
			$entities = elgg_get_entities($options);
		} else {
			$entities = [];
		}
	} else {
		$count = 0;
		$entities = [];
	}

	$options['count'] = $count;
	return elgg_view_entity_list($entities, $options);
}

/**
 * Checks if $entity is an \ElggEntity and optionally for type and subtype.
 *
 * @tip Use this function in actions and views to check that you are dealing
 * with the correct type of entity.
 *
 * @param mixed  $entity  Entity
 * @param string $type    Entity type
 * @param string $subtype Entity subtype
 *
 * @return bool
 * @since 1.8.0
 */
function elgg_instanceof($entity, $type = null, $subtype = null) {
	$return = ($entity instanceof \ElggEntity);

	if ($type) {
		/* @var \ElggEntity $entity */
		$return = $return && ($entity->getType() == $type);
	}

	if ($subtype) {
		$return = $return && ($entity->getSubtype() == $subtype);
	}

	return $return;
}

/**
 * Checks options for the existing of site_guid or site_guids contents and reports a warning if found
 *
 * @param array $options array of options to check
 *
 * @return void
 */
function _elgg_check_unsupported_site_guid(array $options = []) {
	$site_guid = elgg_extract('site_guid', $options, elgg_extract('site_guids', $options));
	if ($site_guid === null) {
		return;
	}
	
	$backtrace = debug_backtrace();
	// never show this call.
	array_shift($backtrace);

	if (!empty($backtrace[0]['class'])) {
		$warning = "Passing site_guid or site_guids to the method {$backtrace[0]['class']}::{$backtrace[0]['file']} is not supported.";
		$warning .= "Please update your usage of the method.";
	} else {
		$warning = "Passing site_guid or site_guids to the function {$backtrace[0]['function']} in {$backtrace[0]['file']} is not supported.";
		$warning .= "Please update your usage of the function.";
	}

	_elgg_services()->logger->warn($warning);
}

/**
 * Update the last_action column in the entities table for $guid.
 *
 * @warning This is different to time_updated.  Time_updated is automatically set,
 * while last_action is only set when explicitly called.
 *
 * @param int $guid   Entity annotation|relationship action carried out on
 * @param int $posted Timestamp of last action
 *
 * @return int|false Timestamp or false on failure
 * @access private
 * @deprecated 2.3
 */
function update_entity_last_action($guid, $posted = null) {
	elgg_deprecated_notice(__FUNCTION__ . ' has been deprecated. Refrain from updating last action timestamp manually', '2.3');

	$result = false;
	$ia = elgg_set_ignore_access(true);
	$entity = get_entity($guid);
	if ($entity) {
		$result = $entity->updateLastAction($posted);
	}
	elgg_set_ignore_access($ia);
	return $result;
}

/**
 * Runs unit tests for the entity objects.
 *
 * @param string $hook   unit_test
 * @param string $type   system
 * @param array  $value  Array of tests
 *
 * @return array
 * @access private
 */
function _elgg_entities_test($hook, $type, $value) {
	$value[] = Paths::elgg() . 'engine/tests/ElggEntityTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggCoreAttributeLoaderTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggCoreGetEntitiesTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggCoreGetEntitiesFromAnnotationsTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggCoreGetEntitiesFromMetadataTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggCoreGetEntitiesFromPrivateSettingsTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggCoreGetEntitiesFromRelationshipTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggCoreGetEntitiesFromAttributesTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggEntityPreloaderIntegrationTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggSiteTest.php';
	$value[] = Paths::elgg() . 'engine/tests/ElggObjectTest.php';
	return $value;
}

/**
 * Entities init function; establishes the default entity page handler
 *
 * @return void
 * @elgg_event_handler init system
 * @access private
 */
function _elgg_entities_init() {
	elgg_register_plugin_hook_handler('unit_test', 'system', '_elgg_entities_test');
}

/**
 * @see \Elgg\Application::loadCore Do not do work here. Just register for events.
 */
return function(\Elgg\EventsService $events, \Elgg\HooksRegistrationService $hooks) {
	$events->registerHandler('init', 'system', '_elgg_entities_init');
};
