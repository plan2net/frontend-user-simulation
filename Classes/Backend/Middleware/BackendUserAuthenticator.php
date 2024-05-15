<?php

namespace Plan2net\FrontendUserSimulation\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Middleware\BackendUserAuthenticator as CoreBackendUserAuthenticator;

class BackendUserAuthenticator extends CoreBackendUserAuthenticator
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isBackendUserLoggedIn() && $this->isBackendUserLoggingOut($request)) {
            // TODO: log out the frontend-user
            $response = parent::process($request, $handler);
        } else {
            $response = parent::process($request, $handler);
        }

        return $response;
    }

    private function isBackendUserLoggedIn(): bool
    {
        return $this->context->getAspect('backend.user')->isLoggedIn();
    }

    private function isBackendUserLoggingOut(ServerRequestInterface $request): bool
    {
        return '/logout' === $request->getAttribute('route')->getPath();
    }
}
