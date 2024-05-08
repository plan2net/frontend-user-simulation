<?php

declare(strict_types=1);

namespace Plan2net\FrontendUserSimulation;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
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

        return '<a  href="' . $url . '" target="_blank" class="btn btn-default">' . $switchUserIcon . '</a>';
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
        $siteFinder = new SiteFinder();
        $site = $siteFinder->getSiteByPageId($pageId);

        return $site->getBase() . '?' .
            GeneralUtility::implodeArrayForUrl('tx_frontendusersimulation', $arguments);
    }
}
