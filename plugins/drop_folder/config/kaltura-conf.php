<?php
// This file generated by Propel  convert-conf target
// from XML runtime conf file C:\web\kaltura\alpha\config\runtime-conf.xml
return array (
  'datasources' => 
  array (
    'kaltura' => 
    array (
      'adapter' => 'mysql',
      'connection' => 
      array (
        'phptype' => 'mysql',
        'database' => 'kaltura',
        'hostspec' => 'localhost',
        'username' => 'root',
        'password' => 'root',
      ),
    ),
    'default' => 'kaltura',
  ),
  'log' => 
  array (
    'ident' => 'kaltura',
    'level' => '7',
  ),
  'generator_version' => '1.4.2',
  'classmap' => 
  array (
    'DropFolderTableMap' => 'lib/model/map/DropFolderTableMap.php',
    'DropFolderPeer' => 'lib/model/DropFolderPeer.php',
    'DropFolder' => 'lib/model/DropFolder.php',
    'DropFolderFileTableMap' => 'lib/model/map/DropFolderFileTableMap.php',
    'DropFolderFilePeer' => 'lib/model/DropFolderFilePeer.php',
    'DropFolderFile' => 'lib/model/DropFolderFile.php',
  ),
);