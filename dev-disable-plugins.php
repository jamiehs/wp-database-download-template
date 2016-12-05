<?php

// Never allow this file to be web-executable.
if( !isset( $GLOBALS['argv'] ) ) {
  exit( "This can only be executed from the command line." );
}

/** Define ABSPATH as this file's directory */
define( 'ABSPATH', dirname(__FILE__) . '/' );

/*
 * Copied & modified from wp-load.php
 * If wp-config.php exists in the WordPress root, or if it exists in the root and wp-settings.php
 * doesn't, load wp-config.php. The secondary check for wp-settings.php has the added benefit
 * of avoiding cases where the current directory is a nested installation, e.g. / is WordPress(a)
 * and /blog/ is WordPress(b).
 *
 * If neither set of conditions is true, exit.
 */
if ( file_exists( ABSPATH . 'wp-config.php') ) {

  /** The config file resides in ABSPATH */
  require_once( ABSPATH . 'wp-config.php' );

} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {

  /** The config file resides one level above ABSPATH but is not part of another install */
  require_once( dirname( ABSPATH ) . '/wp-config.php' );
} else {
  exit( "No database credentials found." );
}


/**
 * Database class that runs a simple query and
 * returns an associative array formatted result.
 */
class db {
  static function query( $sql, $args = array() ) {
    try {
      $conn = new PDO( "mysql:host=localhost;dbname=" . getenv('LOCAL_DATABASE_NAME'), DB_USER, DB_PASSWORD, array( PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8' ) );
      $q = $conn->prepare( $sql );
      $q->execute( $args );

      $obj = $q->fetch( PDO::FETCH_ASSOC );

      unset( $conn );
    } catch( PDOException $e ) {
      die( "Database connection error!" );
    }

    return $obj;
  }
}

// Get the active plugins
$active_plugins = db::query( "SELECT option_value FROM ${getenv('BLOG_PREFIX')}_options WHERE option_name = 'active_plugins' " );
// array_merge() used to remove any key indexing for proper itteration
$active_plugins = (array) array_merge( array(), unserialize( $active_plugins['option_value'] ) );

$disable_plugins_regex = getenv('LOCAL_DATABASE_NAME');

// Remove any plugins that should be deactivated
for( $i = ( count( $active_plugins ) - 1 ); $i >= 0; $i-- ) {
  if( preg_match( "/^(". $disable_plugins_regex .")/", $active_plugins[$i] ) ) {
   unset( $active_plugins[$i] );
  }
}

// Update active plugins
db::query( "UPDATE ${getenv('BLOG_PREFIX')}_options SET option_value = '" . serialize( $active_plugins ) . "' WHERE option_name = 'active_plugins'" );
