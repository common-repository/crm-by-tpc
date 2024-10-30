<?php
include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "TPC" . DIRECTORY_SEPARATOR . "Autoloader" . DIRECTORY_SEPARATOR . "LoaderClass.php" );

// Add the Autoloader
$loader = new TPC_Autoloader_LoaderClass( "TPC", dirname( __FILE__ ) );
$loader->register( );
