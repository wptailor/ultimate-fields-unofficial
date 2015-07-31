<?php
/**
 * This exception is thrown when a second field tries to use the same key within the same datastore.
 * 
 * This is done to prevent multiple fields from saving into the same cell in the DB.
 */
class UF_Unavailable_Key_Exception extends Exception {};