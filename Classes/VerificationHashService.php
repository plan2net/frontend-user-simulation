<?php

declare(strict_types=1);

namespace Plan2net\FrontendUserSimulation;

use TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend;

final readonly class VerificationHashService
{
    public function __construct(
        private DatabaseSessionBackend $databaseSessionBackend
    ) {
    }

    public function generateVerificationHash(string $sessionId, array $arguments): string
    {
        $hashedSessionId = $this->databaseSessionBackend->hash($sessionId);

        return md5(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] .
            $hashedSessionId .
            serialize($arguments)
        );
    }
}
