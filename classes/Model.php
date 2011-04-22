<?php
/**
 * Base class for all models
 *
 * Based on code provided by Zachary Fox (http://www.zacharyfox.com/blog/php/simple-model-crud-with-php-5-3)
 *
 * @author Joe Tan
 * 
 */

class NowSpots_Exception extends Exception {};

abstract class NowSpots_Model {
    /**
     * Pass properties to construct
     *
     * @param mixed[] $properties The object properties
     * 
     * @throws Exception
     */
     
    public $id, $Status, $CreatedDate, $ModifiedDate;
    

    protected function __construct(Array $properties)
    {
        $reflect = new ReflectionObject($this);

        foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!array_key_exists($property->name, $properties) || ($properties[$property->name] === null)) {
            	if (in_array($property->name, array('CreatedDate', 'ModifiedDate'))) { // give dates a default of now
	            	$properties[$property->name] = current_time('mysql');
				} elseif ($property->name == 'id') {
					$properties['id'] = null;
				} elseif ($property->name == 'Status') {
					$properties['Status'] = 'Active';
				} elseif (array_key_exists($property->name, $properties)) {
					$properties[$property->name] = '';
            	} else {
	                throw new NowSpots_Exception("Unable to create object. Missing property: {$property->name}");
				}
            	
            }
            
            if (in_array($property->name, array('CreatedDate', 'ModifiedDate', 'StartDate', 'EndDate'))) {
            	$this->{$property->name} = date('Y-m-d H:i:s', strtotime($properties[$property->name]));
            } else {
	            $this->{$property->name} = $properties[$property->name];
	        }
        }
        
        
    }

    /**
     * Get all class properties
     *
     * @return string[]
     */
    protected static function getFields()
    {
        static $fields = array();
        $called_class  = get_called_class();

        if (!array_key_exists($called_class, $fields)) {
            $reflection_class = new ReflectionClass($called_class);

            $properties = array();

            foreach ($reflection_class->getProperties() as $property) {
                $properties[] = $property->name;
            }

            $fields[$called_class] = $properties;
        }

        return $fields[$called_class];
    }

    /**
     * Get the select statement
     *
     * @return string
     */
    protected static function getSelect()
    {
        return "SELECT " . implode(', ', self::getFields()) . " FROM " . self::getTableName();
    }

    /**
     * Save this object
     *
     * @return void
     */
    protected function save()
    {
        global $wpdb;

		$data = array();
		$fields = self::getFields();
		foreach ($fields as $field) {
			$data[$field] = trim($this->$field);
		}
		
        $wpdb->replace(self::getTableName(), $data);
        /*
        
        $replace  = "REPLACE INTO " . self::getTableName() . "(" . implode(',', $fields) . ")";

        $function = function ($value) {
            return ':' . $value;
        };

        $replace .= " VALUES (" . implode(',', array_map($function, $fields)) . ")";

        $statement = $db->prepare($replace);

        foreach ($fields as $field) {
            $statement->bindParam($field, $this->$field);
        }

        $statement->execute();
        */
    }

    /**
     * Get a single object by id
     *
     * @param integer $id
     * @return Object
     */
    public static function get($id)
    {
        global $wpdb;
        $data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::getTableName()." WHERE `id` = %d", $id), ARRAY_A);
        return new static($data);
        /*

        $select    = self::getSelect() . " WHERE `id` = :id";
        $statement = $db->prepare($select);

        $statement->bindParam(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        return new static($result[0]);
        */
    }

    /**
     * Get all objects
     *
     * @return Object[]
     */
    public static function getAll()
    {
        global $wpdb;
        $return = array();
        $results = $wpdb->get_results("SELECT * FROM ".self::getTableName(), ARRAY_A);
        foreach ($results as $row) {
        	$return[] = new static($row);
        }
        return $return;
        /*

        $return = array();
        foreach ($db->query(self::getSelect(), PDO::FETCH_ASSOC) as $row) {
            $return[] = new static($row);
        }

        return $return;
        */
    }
    
    
    public static function find($params) {
    	global $wpdb;
    	$return = array();
    	$where = 'WHERE true ';
    	foreach ($params as $field => $val) {
    		$where .= $wpdb->prepare(" AND `$field` = %s", $val);
    	}
		$results = $wpdb->get_results("SELECT * FROM ".self::getTableName() .' '. $where, ARRAY_A);
		foreach ($results as $row) {
			$return[] = new static($row);
		}
		return $return;
    }
    
    
    public static function fetch_recent($params=array(), $limit=10) {
    	global $wpdb;
    	$return = array();
    	
    	$where = 'WHERE true ';
    	foreach ($params as $field => $val) {
    		$where .= $wpdb->prepare(" AND `$field` = %s", $val);
    	}
    	
    	$sql = $wpdb->prepare("SELECT * FROM ".self::getTableName() . " $where ORDER BY CreatedDate DESC, id DESC LIMIT $limit");
    	$results = $wpdb->get_results($sql, ARRAY_A);
    	
    	foreach ($results as $row) {
    		$return[] = new static($row);
    	}
    	return $return;
    }
    public static function fetch_most_recent($params) {
    	if ($rows = self::fetch_recent($params, 1)) {
	    	return $rows[0];
    	} else {
    		return null;
    	}
    }
    
    
    public function getID() {
    	return $this->id;
    }

	/**
     * Create a new blank default object
     *
     * @param mixed[] $properties Properties
     * 
     * @return Object
     */
    public static function blank()
    {
    	$properties = array_fill_keys(self::getFields(), null);
		return new static($properties);
    }
    /**
     * Create a new object
     *
     * @param mixed[] $properties Properties
     * 
     * @return Object
     */
    public static function create(Array $properties)
    {
        global $wpdb;

        $object = new static($properties);
        $object->save();
        if ($wpdb->insert_id) {
        	$object->id = $wpdb->insert_id;
        }
        return $object;
    }

    /**
     * Update an object
     *
     * @param mixed[] $properties Properties
     * 
     * @return void
     */
    public function update($properties, $val = null)
    {
    	if (is_array($properties)) {
	        foreach ($properties as $key => $value) {
	            if (property_exists($this, $key)) {
	                $this->$key = $value;
	            }
	        }
	    } elseif (property_exists($this, $properties)) {
	    	$this->$properties = $val;
	    }
        $this->save();
    }

    /**
     * Update a single property
     *
     * @param string $key   Property name
     * @param mixed  $value Property value
     * 
     * @return void
     */
    public function updateProperty($key, $value)
    {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        }
        $this->save();
    }

    /**
     * Delete an object
     *
     * @return void
     */
    public function delete()
    {
        global $wpdb;

		return $wpdb->query($wpdb->prepare( "DELETE FROM " . self::getTableName() . " WHERE `id` = %s", $this->id));
    }
    
    private function getTableName() {
    	global $wpdb;
    	return $wpdb->prefix . preg_replace('/NowSpots_/', 'nowspots_', get_called_class());
    }
}


