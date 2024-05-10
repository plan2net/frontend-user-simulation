<?php

declare(strict_types=1);

namespace Plan2net\FrontendUserSimulation;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class OnModifyRecordListRecordActions
{
    public function __construct(
        private VerificationHashService $verificationHashService
    ) {
    }

    /**
     * @throws Exception|SiteNotFoundException
     */
    public function modifyRecordActions(ModifyRecordListRecordActionsEvent $event): void
    {
        $currentTable = $event->getTable();

        if ('fe_users' === $currentTable
            && !$event->hasAction('simulateAction')
            && 0 === $event->getRecord()['disable']
        ) {
            $event->setAction(
                action: $this->getActionLink($event),
                actionName: 'simulateAction',
                group: 'primary',
                after: 'edit',
            );
        }
    }

    /**
     * @throws Exception
     * @throws SiteNotFoundException
     */
    private function getActionLink(ModifyRecordListRecordActionsEvent $event): string
    {
        $iconFactory = GeneralUtility::makeInstance('TYPO3\CMS\Core\Imaging\IconFactory');
        $switchUserIcon = $iconFactory->getIcon('actions-system-backend-user-switch', Icon::SIZE_SMALL)->render();

        $userId = $event->getRecord()['uid'];

        $redirectPid = $this->getUserGroup($userId);

        $redirectSlug = '';

        if ($redirectPid) {
            $redirectSlug = $this->getSlugForPid($redirectPid);
        }

        $url = $this->getSimulationUrlFor($userId, $event->getRecord()['pid'], $redirectSlug);

        return '<a  href="' . $url . '" target="_blank" class="btn btn-default">' . $switchUserIcon . '</a>';
    }

    /**
     * @throws Exception
     */
    private function getUserGroup($userId): ?int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)?->getQueryBuilderForTable('fe_users');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $userGroups = $queryBuilder
            ->select('usergroup')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($userId, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        $firstUserGroup = null;

        if (isset($userGroups['usergroup'])) {
            $userGroups = explode(',', $userGroups['usergroup']);
            if ($userGroups && isset($userGroups[0])) {
                $firstUserGroup = (int) $userGroups[0];
            }
        }

        return $firstUserGroup;
    }

    /**
     * @throws Exception
     */
    private function getSlugForPid($firstUserGroup): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)?->getQueryBuilderForTable('fe_groups');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $slug = $queryBuilder->select('p.slug')
            ->from('pages', 'p')
            ->join(
                'p',
                'fe_groups', 'fg',
                $queryBuilder->expr()->eq('fg.felogin_redirectPid', $queryBuilder->quoteIdentifier('p.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq('fg.uid', $queryBuilder->createNamedParameter($firstUserGroup, \PDO::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        return false !== $slug ? $slug : '';
    }

    /**
     * @throws SiteNotFoundException
     */
    private function getSimulationUrlFor(int $userId, $pageId, $redirectSlug): string
    {
        $cookieName = BackendUserAuthentication::getCookieName();
        $sessionId = $_COOKIE[$cookieName];
        $arguments['userid'] = (string) $userId;
        $arguments['timeout'] = (string) (time() + 3600);
        $arguments['verification'] = $this->verificationHashService->generateVerificationHash($sessionId, $arguments);

        $siteFinder = new SiteFinder();
        $site = $siteFinder->getSiteByPageId($pageId);

        return $site->getBase() . $redirectSlug . '?' .
            GeneralUtility::implodeArrayForUrl('tx_frontendusersimulation', $arguments);
    }
}
