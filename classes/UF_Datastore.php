<?php
/**
 * This interface lists all the methods that are required from a datastore.
 * 
 * A datastore is an object that is used to connect fields with the database.
 * By providing an interface for that, the field is not tightly connected
 * to a specific table or functions.
 */
interface UF_Datastore {
	/**
	 * Retrieve a single value
	 * 
	 * @param string $key The key of the value
	 * @return mixed An empty string if the value is not available or the value itself
	 */
	function get_value( $key );

	/**
	 * Retrieve multiple values - an array or something else.
	 * Can be used when the datastore uses specific format
	 * for saving values.
	 * 
	 * @param string $key The key of the value
	 * @return mixed[] The value in the database
	 * 
	 * If the particular datastore does not have a specific method for saving,
	 * redirect this method to get_value
	 */
	function get_multiple( $key );

	/**
	 * Saves values in the dabase. Might as well update existing ones
	 * 
	 * @param string $key The key which the value is saved with
	 * @param mixed $value The value to be saved
	 */
	function save_value($key, $value);

	/**
	 * Saves multiple values, like arrays.
	 * Can be used when the datastore uses a specific format for saving
	 * multiple values.
	 * 
	 * @param string $key The key which the value is saved with.
	 * @param mixed[] $values
	 */
	function save_multiple($key, $values = array());

	/**
	 * Deletes values based on their key, no matter if they are multiple or not
	 * 
	 * @param string $key The key of the setting
	 */
	function delete_value($key);
}