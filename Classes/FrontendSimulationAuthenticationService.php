<?php

declare(strict_types=1);

namespace Plan2net\FrontendUserSimulation;

use Doctrine\DBAL\Exception;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FrontendSimulationAuthenticationService extends AbstractAuthenticationService
{
    private const STATUS_CODE_UNAUTHENTICATED = 100;
    private const STATUS_CODE_AUTHENTICATED = 200;
    /**
     * @var array<array-key, mixed>
     */
    private static ?array $user = [];
    private ServerRequestInterface $request;

    public function __construct(
        private readonly VerificationHashService $verificationHashService
    ) {
    }

    public function initAuth($mode, $loginData, $authInfo, $pObj): void
    {
        $this->request = $authInfo['request'];
        parent::initAuth($mode, $loginData, $authInfo, $pObj);
    }

    /**
     * @throws Exception
     */
    public function authUser(array $user): int
    {
        $statusCode = self::STATUS_CODE_UNAUTHENTICATED;

        if (empty(self::$user)) {
            $this->getUser();
        }
        if (isset(self::$user['uid']) && (int) self::$user['uid'] === (int) $user['uid']) {
            $statusCode = self::STATUS_CODE_AUTHENTICATED;
        }

        return $statusCode;
    }

    /**
     * @throws Exception
     */
    public function getUser(): array|false
    {
        $user = [];
        $arguments = $this->request->getQueryParams()['tx_frontendusersimulation'] ?? [];
        if (!isset($arguments['verification'])) {
            return false;
        }

        $cookieName = BackendUserAuthentication::getCookieName();
        if (empty($_COOKIE[$cookieName])) {
            throw new \RuntimeException('No backend cookie found');
        }

        $sessionId = $_COOKIE[$cookieName];
        $verificationHash = $arguments['verification'];
        unset($arguments['verification']);
        if ($this->verificationHashService->generateVerificationHash($sessionId, $arguments) === $verificationHash && $arguments['timeout'] > time()) {
            self::$user = $user = $this->queryUserData($arguments['userid']);
        }

        return !empty($user) ? $user : false;
    }

    /**
     * @throws Exception
     */
    public function queryUserData($userid): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)?->getQueryBuilderForTable('fe_users');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $user = $queryBuilder
            ->select('*')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($userid, \PDO::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $user ?: null;
    }
}
