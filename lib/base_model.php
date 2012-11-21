<?php

  class base_model { 
    public $last_error_message;
    protected $db;
    protected $properties = array();
    
    # Returns the string table name based on the [very basic] pluralized ModelName
    public static function table_name() { 
      return strtolower(get_called_class().'s');
    }
    
    ############################################################
    ### FINDER METHODS
    ############################################################
    
    public static function last() { 
      global $db;
      $sql = "SELECT * FROM ". self::table_name() ." ORDER BY id DESC LIMIT 1";
      $q = $db->prepare($sql);
      $q->execute();
      $q->setFetchMode(PDO::FETCH_CLASS , get_called_class());
      $return_var = $q->fetch();
      return $return_var;
    }
    
    public static function all($extra_unsafe_sql = false) { 
      global $db;
      $sql = "SELECT * FROM ". self::table_name();
      if($extra_unsafe_sql) { 
        $sql .= " ".$extra;
      }
      
      $q = $db->prepare($sql);
      $q->setFetchMode(PDO::FETCH_CLASS , get_called_class());
      $q->execute();
      return $q->fetchAll();
    }
    
    public static function find($id) { 
      global $db;
      $sql = "SELECT * FROM ".self::table_name()." WHERE id = ?";
      $q = $db->prepare($sql);
      $q->execute(array($id));
      $q->setFetchMode(PDO::FETCH_CLASS , get_called_class());
      $objects = $q->fetchAll();

      if(count($objects) == 1) { 
        return $objects[0];
      } else { 
        return $objects;
      }
    }
    
    public static function findByArray( array $array) { 
      global $db;
      
      if(count($array) == 0) { return self::all(); }
      
      // Build the SQL && Bind-Var Array
      $sql_where = "";
      $bind_vars = array();
      foreach($array as $col => $val) { 
        $bind_vars[":".$col] = $val;
        $sql_where .= $col."=:".$col." AND ";
      }
      $sql_where .= "1";
      
      $sql = "SELECT * FROM ".self::table_name()." WHERE ".$sql_where;

      $q = $db->prepare($sql);
      $q->execute($bind_vars);
      $q->setFetchMode(PDO::FETCH_CLASS , get_called_class());
      $objects = $q->fetchAll();

      if(count($objects) == 1) { 
        return $objects[0];
      } else { 
        return $objects;
      }
    }
    
    
    ############################################################
    ### MAGIC METHODS - Constructor / Getter / Setter
    ############################################################
    
    public function __construct($data_array = null) { 
      if(isset($data_array) && is_array($data_array)) { 
        $this->properties = $data_array;
      }
      $this->setDB();
    }
    
    public function setDB() { 
      global $db;
      $this->db = $db;
    }
        
    public function __get($key) { 
      return $this->properties[$key];
    }
    
    public function __set($key, $value) { 
      return $this->properties[$key] = $value;
    }
    
    ############################################################
    ### INSTANCE METHODS - Validation, Load, Save
    ############################################################
    
    # Placeholder; Override this within individual models!
    public function validate() { 
      return true;
    }
    
    public function exists() { 
      if(isset($this->properties) && isset($this->properties['id']) && is_numeric($this->id)) { 
        return true;
      } else { 
        return false;
      }
    }
    
    protected function loadPropertiesFromDatabase() { 
      $sql = "SELECT * FROM ". self::table_name() ." WHERE id = ? ";
      $q = $this->db->prepare($sql);
      $q->execute(array($this->id));
      $this->properties = $q->fetch(PDO::FETCH_ASSOC);
    }

    public function save() { 
      # Validations MUST pass!
      if($this->validate() === false) { return false; }
      
      # Table Name && Created/Updated Fields
      $table_name = self::table_name();
      $this->updated_at = date('Y-m-d H:i:s');
      if($this->exists() === false) { $this->created_at = date('Y-m-d H:i:s'); }

      # Create SQL Query
      $sql_set_string = "";
      $total_properties_count = count($this->properties);
      $x = 0;
      foreach($this->properties as $k => $v) { 
        $x++;
        if($k == 'id') { continue; }
        $sql_set_string .= $k."=".":".$k;
        if($x != $total_properties_count) { $sql_set_string .= ", "; }
      }
      
      # Final SQL Statement
      $sql = $table_name." SET ".$sql_set_string;
      if($this->exists()) { 
        $final_sql = "UPDATE ".$sql." WHERE id=:id";
      } else { 
        $final_sql = "INSERT INTO ".$sql;
      }

      # Bind Vars
      foreach($this->properties as $k => $v) { 
        $bind_vars[(":".$k)] = $v;
      }
            
      # Run the Insert or Update
      $q = $this->db->prepare($final_sql);
      $run = $q->execute($bind_vars);
      
      # Update the Object if SUCCESS
      if($run === true) { 
        if(!$this->exists()) {
          $this->id = $this->db->lastInsertId();
        }
        $this->loadPropertiesFromDatabase();
        return true;
      } else { 
        $this->sql_error = $q->errorInfo();
        return false;
      }
    }
  }