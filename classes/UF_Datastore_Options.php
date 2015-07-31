<?php
class UF_Datastore_Options implements UF_Datastore {
	private $separator = '__';

	# Serialization is currently preffered because of
	# the limitation of 64 chars for option name in wp_options
	private $serialize = true;

	/* @type string[] List of unique field keys */
	static protected $field_keys = array();

	function get_value($key) {
		return get_option($key);
	}

	function get_multiple($key) {
		if($this->serialize) {
			$data = maybe_unserialize( get_option($key) );
		} else {
			global $wpdb;

			$sql = "SELECT
						option_name as name, option_value as value
					FROM $wpdb->options
					WHERE option_name LIKE '" . addslashes($key) . "%'
					ORDER BY option_id ASC";
			$res = $wpdb->get_results($sql);

			$data = array();
			foreach($res as $row) {
				$row_name = preg_replace('~^' . $key . $this->separator . '(.+)$~i', '$1', $row->name);
				$keys     = explode($this->separator, $row_name);

				eval("\$data['" . implode($keys, "']['") . "'] = '$row->value';");
			}
		}

		return $data;
	}

	function save_value($key, $value) {
		return update_option($key, $value);
	}

	function save_multiple($key, $values = array()) {
		global $wpdb;

		if($this->serialize) {
			update_option($key, $values);
		} else {
			$this->delete_value($key);

			$options = $this->generate_options($key, $values);
			
			$pairs = array();
			foreach($options as $key => $pair) {
				$pairs[] = "('$key', '" . addslashes($pair) . "')";
			}
			$processed = implode($pairs, ',');
			$sql = $wpdb->prepare( "INSERT INTO $wpdb->options(option_name, option_value) VALUES $processed" );
			$wpdb->query($sql);
		}
	}

	function generate_options($option_key, $values = array()) {
		$options = array();

		foreach($values as $key => $value) {
			$my_key = $option_key . $this->separator . $key;

			if(is_array($value)) {
				$options += $this->generate_options($my_key, $value);
			} else {
				$options[$my_key] = $value;
			}
		}

		return $options;
	}

	function delete_value($key) {
		global $wpdb;

		$sql = "DELETE FROM $wpdb->options WHERE option_name LIKE '" . addslashes($key) . $this->separator . "%' OR option_name = '$key'";
		return $wpdb->query($sql);
	}

	/**
	 * Check if a field with the same key has been registered
	 *
	 * @param string $key The key of the field
	 */
	function check_field_id( $key ) {
		if( isset( UF_Datastore_Options::$field_keys[ $key ] ) ) {
			UF_Exceptions::add( sprintf( __( 'Error: Trying to register an option field with the %s key twice!', 'uf' ), $key ), 'unavailable_field_key' );
			return false;
		} else {
			UF_Datastore_Options::$field_keys[ $key ] = 1;
			return true;
		}
	}
}