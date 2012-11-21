<?php

  /* ##########################################################################################
      This example assumes: 
          1)  A database table exists named 'users' 
          2)  That the table has at least two fields, 'admin' and 'username'
          3)  That the global var $db is an instantiated MySQL PDO connection object (see bottom)
  */ ##########################################################################################
  
  require_once('lib/base_model.php'); // Require the base_model class
  
  #############################################
  #####   Define the 'User' model
  #############################################
  class User extends base_model { 
    
    public function isAdmin() { 
      if($this->admin == 1) { 
        return true;
      }
      return false;
    }
    
    public function validate() { 
      if( $this->username && strlen(trim($this->username)) > 5 ) { // Make sure the username exists & is > 5 chars
        return true;
      } else { 
        return false;
      }
    }
  }
  
  ### DB Connection
  $db = new PDO('mysql:host='.$YOUR_DB_HOSTNAME.';dbname='.$YOUR_DB_NAME , $YOUR_DB_USERNAME, $YOUR_DB_PASSWORD);
  
  #############################################
  #####   Try it out - Create a new User!
  #############################################
  $new_user = new User();
  $new_user->username = 'Zach Morris';
  $new_user->admin = 0;
  $new_user->save();  
  
  #############################################
  #####   Try it out - Edit an existing User
  #############################################
  $last_user = User::last();
  $last_user->admin = 1; // Make him an admin!
  $last_user->save();    
    