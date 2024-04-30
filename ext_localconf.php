<?php

use Plan2net\FrontendUserSimulation\Frontend\Middleware\FrontendUserAuthenticator;
use Plan2net\FrontendUserSimulation\FrontendSimulationAuthenticationService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addService(
    'frontend_user_simulation',
    'auth',
    FrontendSimulationAuthenticationService::class,
    [
        'title' => 'Frontend user simulation authentication',
        'description' => 'Authenticate a frontend user using a link',
        'subtype' => 'getUserFE,authUserFE',
        'available' => true,
        'priority' => 70,
        'quality' => 70,
        'className' => FrontendSimulationAuthenticationService::class
    ]
);

// Trigger authentication without setting FE_alwaysFetchUser globally
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Frontend\Middleware\FrontendUserAuthenticator::class] = [
    'className' => FrontendUserAuthenticator::class
];
