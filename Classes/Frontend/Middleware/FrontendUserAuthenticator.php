<?php

declare(strict_types=1);

namespace Plan2net\FrontendUserSimulation\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Frontend\Middleware\FrontendUserAuthenticator as CoreFrontendUserAuthenticator;

final class FrontendUserAuthenticator extends CoreFrontendUserAuthenticator
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $originalAlwaysFetchUserSetting = $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] ?? null;

        if ($this->isUserSimulationActive($request)) {
            $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;
        }

        $response = parent::process($request, $handler);

        if (null !== $originalAlwaysFetchUserSetting) {
            $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = $originalAlwaysFetchUserSetting;
        } else {
            unset($GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser']);
        }

        return $response;
    }

    private function isUserSimulationActive(ServerRequestInterface $request): bool
    {
        $arguments = $request->getQueryParams()['tx_frontendusersimulation'] ?? [];

        return isset($arguments['userid']) && $arguments['userid'] > 0;
    }
}
