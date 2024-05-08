<?php

declare(strict_types=1);

namespace Plan2net\FrontendUserSimulation;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;

final readonly class OnModifyRecordListRecordActions
{
    public function __construct(
        private VerificationHashService $verificationHashService
    ) {
    }

    /**
     * @throws Exception
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

    private function getActionLink(ModifyRecordListRecordActionsEvent $event): string
    {
        $iconFactory = GeneralUtility::makeInstance('TYPO3\CMS\Core\Imaging\IconFactory');
        $switchUserIcon = $iconFactory->getIcon('actions-system-backend-user-switch', Icon::SIZE_SMALL)->render();
        $url = $this->getSimulationUrlFor($event->getRecord()['uid'], $event->getRecord()['pid']);

        $userid = $event->getRecord()['uid'];
        if ($userid === 625706) {
            $usergroupids = $this->getUsergroupOfUser($userid);

            foreach ($usergroupids as $usergroupid) {
                $usergroupid = (int) $usergroupid;
                if ($usergroupid === 33841) {
                    $redirectPid = $this->getRedirectPidOfUsergroup($usergroupid);
                    print_r($redirectPid);
                    print_r($this->getUrlByPageId($redirectPid));
                }
            }

            die();
        }


        return '<a  href="' . $url . '" target="_blank" class="btn btn-default">' . $switchUserIcon . '</a>';
    }

    private function getUsergroupOfUser(int $userid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)?->getQueryBuilderForTable('fe_users');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $usergroups = $queryBuilder
            ->distinct()
            ->select('usergroup')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($userid, \PDO::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (isset($usergroups['usergroup'])) {
            return explode(',', $usergroups['usergroup']);
        } else {
            return [];
        }
    }

    private function getRedirectPidOfUsergroup(int $usergroupid): ?int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)?->getQueryBuilderForTable('fe_groups');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $redirectPid = $queryBuilder
            ->select('felogin_redirectPid')
            ->from('fe_groups')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($usergroupid, \PDO::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (isset($redirectPid['felogin_redirectPid'])) {
            return (int) $redirectPid['felogin_redirectPid'];
        } else {
            return null;
        }
    }

    /**
     * @throws SiteNotFoundException
     */
    private function getSimulationUrlFor(int $userId, int $pageId): string
    {
        $cookieName = BackendUserAuthentication::getCookieName();
        $sessionId = $_COOKIE[$cookieName];
        $arguments['userid'] = (string) $userId;
        $arguments['timeout'] = (string) (time() + 3600);
        $arguments['verification'] = $this->verificationHashService->generateVerificationHash($sessionId, $arguments);

        return $this->getUrlByPageId($pageId) . '?' .
            GeneralUtility::implodeArrayForUrl('tx_frontendusersimulation', $arguments);
    }

    private function getUrlByPageId(int $pageId): string
    {
        $siteFinder = new SiteFinder();
        $site = $siteFinder->getSiteByPageId($pageId);

        return (string) $site->getBase();
    }
}
