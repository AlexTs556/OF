<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Service\History;

use OneMoveTwo\Offers\Api\OfferHistoryManagementInterface;
use OneMoveTwo\Offers\Api\OfferHistoryRepositoryInterface;
use OneMoveTwo\Offers\Api\OfferRepositoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferHistoryInterface;
use OneMoveTwo\Offers\Api\Data\OfferHistoryInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface as EventManager;

readonly class OfferHistoryManagementService implements OfferHistoryManagementInterface
{
    public function __construct(
        private OfferHistoryRepositoryInterface $historyRepository,
        private OfferRepositoryInterface        $offerRepository,
        private OfferHistoryInterfaceFactory    $historyFactory,
        private SearchCriteriaBuilder           $searchCriteriaBuilder,
        private SortOrderBuilder                $sortOrderBuilder,
        private AdminSession                    $adminSession,
        private CustomerSession                 $customerSession,
        private State                           $appState,
        private EventManager                    $eventManager
    ) {
    }

    /**
     * Add history record with automatic user detection
     *
     * @param int $offerId
     * @param string $status
     * @param string|null $comment
     * @param bool $isCustomerNotified
     * @param bool $visibleOnStorefront
     * @return OfferHistoryInterface
     * @throws LocalizedException
     */
    public function addRecord(
        int $offerId,
        string $status,
        ?string $comment = null,
        bool $isCustomerNotified = false,
        bool $visibleOnStorefront = false
    ): OfferHistoryInterface {
        // Validate offer exists
        try {
            $offer = $this->offerRepository->getById($offerId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('Offer with ID %1 does not exist.', $offerId));
        }

        // Get current user info
        $userInfo = $this->getCurrentUserInfo();

        // Create history record
        $history = $this->historyFactory->create();
        $history->setOfferId($offerId);
        $history->setStatus($status);
        $history->setComment($comment);
        $history->setIsCustomerNotified($isCustomerNotified);
        $history->setVisibleOnStorefront($visibleOnStorefront);
        $history->setCreatedById((int)$userInfo['id']);
        $history->setCreatedByName($userInfo['name']);

        // Save history record
        $savedHistory = $this->historyRepository->save($history);

        // Dispatch event for notifications
        $this->eventManager->dispatch('offer_history_record_added', [
            'history' => $savedHistory,
            'offer' => $offer,
            'is_customer_notified' => $isCustomerNotified
        ]);

        return $savedHistory;
    }

    /**
     * Get history with pagination and filtering
     *
     * @param int $offerId
     * @param bool $customerVisibleOnly
     * @param int $limit
     * @param int $offset
     * @return OfferHistoryInterface[]
     * @throws LocalizedException
     */
    public function getOfferHistory(
        int $offerId,
        bool $customerVisibleOnly = false,
        int $limit = 50,
        int $offset = 0
    ): array {
        // Validate offer exists
        try {
            $this->offerRepository->getById($offerId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('Offer with ID %1 does not exist.', $offerId));
        }

        // Build search criteria
        $this->searchCriteriaBuilder
            ->addFilter('offer_id', $offerId)
            ->setPageSize($limit)
            ->setCurrentPage($offset > 0 ? (int)ceil($offset / $limit) + 1 : 1);

        // Filter for customer visibility if needed
        if ($customerVisibleOnly) {
            $this->searchCriteriaBuilder->addFilter('visible_on_storefront', 1);
        }

        // Sort by created date descending
        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDirection('DESC')
            ->create();
        $this->searchCriteriaBuilder->setSortOrders([$sortOrder]);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->historyRepository->getList($searchCriteria);

        return $searchResults->getItems();
    }

    /**
     * TODO:: Проверить надо ли этот метод
     *
     * Update history record visibility
     *
     * @param int $historyId
     * @param bool $visibleOnStorefront
     * @return OfferHistoryInterface
     * @throws LocalizedException
     */
    public function updateVisibility(int $historyId, bool $visibleOnStorefront): OfferHistoryInterface
    {
        try {
            $history = $this->historyRepository->get($historyId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('History record with ID %1 does not exist.', $historyId));
        }

        // Check permissions - only allow admin area updates
        if ($this->appState->getAreaCode() !== \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            throw new LocalizedException(__('History visibility can only be updated from admin area.'));
        }

        $history->setVisibleOnStorefront($visibleOnStorefront);
        return $this->historyRepository->save($history);
    }

    /**
     * Get current user information
     *
     * @return array
     */
    private function getCurrentUserInfo(): array
    {
        try {
            $areaCode = $this->appState->getAreaCode();

            if ($areaCode === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
                // Admin area
                $adminUser = $this->adminSession->getUser();
                if ($adminUser) {
                    return [
                        'id' => $adminUser->getId(),
                        'name' => $adminUser->getFirstname() . ' ' . $adminUser->getLastname()
                    ];
                }
            } elseif ($areaCode === \Magento\Framework\App\Area::AREA_FRONTEND) {
                // Frontend area
                if ($this->customerSession->isLoggedIn()) {
                    $customer = $this->customerSession->getCustomer();
                    return [
                        'id' => $customer->getId(),
                        'name' => $customer->getFirstname() . ' ' . $customer->getLastname()
                    ];
                }

                return [
                    'id' => null,
                    'name' => 'Guest Customer'
                ];
            }
        } catch (\Exception $e) {
            // Fallback for CLI or other areas
        }

        return [
            'id' => null,
            'name' => 'System'
        ];
    }
}
