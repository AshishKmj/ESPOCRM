<?php
return [
  'database' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'charset' => NULL,
    'dbname' => 'espocrm',
    'user' => 'root',
    'password' => '',
    'platform' => 'Mysql'
  ],
  'smtpPassword' => NULL,
  'logger' => [
    'path' => 'data/logs/espo.log',
    'level' => 'WARNING',
    'rotation' => true,
    'maxFileNumber' => 30,
    'printTrace' => false,
    'databaseHandler' => false,
    'sql' => false,
    'sqlFailed' => false
  ],
  'restrictedMode' => false,
  'cleanupAppLog' => true,
  'cleanupAppLogPeriod' => '30 days',
  'webSocketMessager' => 'ZeroMQ',
  'clientSecurityHeadersDisabled' => false,
  'clientCspDisabled' => false,
  'clientCspScriptSourceList' => [
    0 => 'https://maps.googleapis.com'
  ],
  'adminUpgradeDisabled' => false,
  'adminUpgrade' => false,
  'adminExtensionUpload' => true,
  'microtimeInternal' => 1791796990.397743,
  'cryptKey' => '9d187a52f6a1868002073b72611b91f0',
  'actualDatabaseType' => 'mariadb',
  'actualDatabaseVersion' => '10.11.10'
];
