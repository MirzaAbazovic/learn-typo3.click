<?php

$currentApplicationContext = \TYPO3\CMS\Core\Core\Environment::getContext();
$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] .= ' [' . strtoupper((string)$currentApplicationContext) . ']';

$contextConfigFile = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/../Configuration/' . (string)$currentApplicationContext . '/Settings.php';
if (file_exists($contextConfigFile)) {
    require($contextConfigFile);
}
