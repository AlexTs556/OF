<?php

declare(strict_types=1);

namespace OneMoveTwo\Offers\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

readonly class ConfigProvider
{
    /**
     * Configuration paths
     */
    const string XML_PATH_GENERAL_ENABLED = 'offers_general/general/enabled';
    const string XML_PATH_GENERAL_OFFER_PREFIX = 'offers_general/general/offer_prefix';
    const string XML_PATH_GENERAL_DEFAULT_EXPIRY_DAYS = 'offers_general/general/default_expiry_days';
    const string XML_PATH_GENERAL_AUTO_GENERATE_NUMBER = 'offers_general/general/auto_generate_number';
    const string XML_PATH_GENERAL_DEFAULT_STATUS = 'offers_general/statuses/default_status';
    const string XML_PATH_GENERAL_OFFER_STATUSES = 'offers_general/statuses/offer_statuses_grid';
    const string XML_PATH_NOTIFICATIONS_ADMIN_ENABLED = 'offers_notifications/admin_notifications/notify_on_customer_actions';
    const string XML_PATH_NOTIFICATIONS_RECIPIENTS = 'offers_notifications/admin_notifications/notification_recipients';
    const string XML_PATH_NOTIFICATIONS_SENDER = 'offers_notifications/admin_notifications/notification_sender';
    const string XML_PATH_NOTIFICATIONS_CUSTOMER_CREATED = 'offers_notifications/customer_emails/send_offer_created_email';
    const string XML_PATH_NOTIFICATIONS_CUSTOMER_STATUS = 'offers_notifications/customer_emails/send_status_change_email';
    const string XML_PATH_NOTIFICATIONS_EMAIL_TEMPLATE = 'offers_notifications/customer_emails/offer_email_template';
    const string XML_PATH_API_ENABLED = 'offers_api/api_settings/enable_api';
    const string XML_PATH_API_RATE_LIMIT = 'offers_api/api_settings/api_rate_limit';
    const string XML_PATH_FILES_ENABLED = 'offers_files/file_settings/enable_file_upload';
    const string XML_PATH_FILES_EXTENSIONS = 'offers_files/file_settings/allowed_file_extensions';
    const string XML_PATH_FILES_MAX_SIZE = 'offers_files/file_settings/max_file_size';
    const string XML_PATH_FILES_MAX_COUNT = 'offers_files/file_settings/max_files_per_offer';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private SerializerInterface $serializer
    ) {
    }

    public function isEnabled($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_GENERAL_ENABLED, $scopeType, $scopeCode);
    }

    public function getOfferPrefix($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_GENERAL_OFFER_PREFIX, $scopeType, $scopeCode) ?: 'OF';
    }

    public function getDefaultExpiryDays($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_GENERAL_DEFAULT_EXPIRY_DAYS, $scopeType, $scopeCode) ?: 30;
    }

    public function isAutoGenerateNumberEnabled($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_GENERAL_AUTO_GENERATE_NUMBER, $scopeType, $scopeCode);
    }

    public function getDefaultStatus($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_GENERAL_DEFAULT_STATUS, $scopeType, $scopeCode) ?: 'draft';
    }

    public function getOfferStatuses($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): array
    {
        $configValue = $this->scopeConfig->getValue(self::XML_PATH_GENERAL_OFFER_STATUSES, $scopeType, $scopeCode);

        if ($configValue) {
            try {
                $statuses = $this->serializer->unserialize($configValue);
                if (is_array($statuses)) {
                    usort($statuses, function($a, $b) {
                        return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
                    });
                    return $statuses;
                }
            } catch (\Exception $e) {
                // Fall back to default
            }
        }

        return $this->getDefaultStatuses();
    }

    public function getStatusConfig(string $statusCode, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): ?array
    {
        $statuses = $this->getOfferStatuses($scopeType, $scopeCode);

        foreach ($statuses as $status) {
            if ($status['code'] === $statusCode) {
                return $status;
            }
        }

        return null;
    }

    public function isStatusLocked(string $statusCode, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        $statusConfig = $this->getStatusConfig($statusCode, $scopeType, $scopeCode);
        return $statusConfig ? !empty($statusConfig['is_locked']) : false;
    }

    public function isStatusFinalized(string $statusCode, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        $statusConfig = $this->getStatusConfig($statusCode, $scopeType, $scopeCode);
        return $statusConfig ? !empty($statusConfig['is_finalized']) : false;
    }

    public function isAdminNotificationsEnabled($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_NOTIFICATIONS_ADMIN_ENABLED, $scopeType, $scopeCode);
    }

    public function getNotificationRecipients($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): array
    {
        $recipients = $this->scopeConfig->getValue(self::XML_PATH_NOTIFICATIONS_RECIPIENTS, $scopeType, $scopeCode);
        return $recipients ? array_map('trim', explode(',', $recipients)) : [];
    }

    public function getNotificationSender($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_NOTIFICATIONS_SENDER, $scopeType, $scopeCode) ?: 'general';
    }

    public function isSendOfferCreatedEmailEnabled($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_NOTIFICATIONS_CUSTOMER_CREATED, $scopeType, $scopeCode);
    }

    public function isSendStatusChangeEmailEnabled($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_NOTIFICATIONS_CUSTOMER_STATUS, $scopeType, $scopeCode);
    }

    public function getOfferEmailTemplate($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_NOTIFICATIONS_EMAIL_TEMPLATE, $scopeType, $scopeCode);
    }

    public function isApiEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_API_ENABLED);
    }

    public function getApiRateLimit(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_API_RATE_LIMIT) ?: 0;
    }

    public function isFileUploadEnabled($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_FILES_ENABLED, $scopeType, $scopeCode);
    }

    public function getAllowedFileExtensions($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): array
    {
        $extensions = $this->scopeConfig->getValue(self::XML_PATH_FILES_EXTENSIONS, $scopeType, $scopeCode);
        return $extensions ? array_map('trim', explode(',', strtolower($extensions))) : ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
    }

    public function getMaxFileSize($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): int
    {
        $sizeMb = (int)$this->scopeConfig->getValue(self::XML_PATH_FILES_MAX_SIZE, $scopeType, $scopeCode) ?: 10;
        return $sizeMb * 1024 * 1024; // Convert MB to bytes
    }

    public function getMaxFilesPerOffer($scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_FILES_MAX_COUNT, $scopeType, $scopeCode) ?: 0; // 0 = unlimited
    }

    private function getDefaultStatuses(): array
    {
        return [
            ['code' => 'draft', 'label' => 'Draft', 'is_active' => '1', 'is_locked' => '0', 'is_finalized' => '0', 'sort_order' => '10'],
            ['code' => 'pending', 'label' => 'Pending Review', 'is_active' => '1', 'is_locked' => '0', 'is_finalized' => '0', 'sort_order' => '20'],
            ['code' => 'sent', 'label' => 'Sent to Customer', 'is_active' => '1', 'is_locked' => '1', 'is_finalized' => '0', 'sort_order' => '30'],
            ['code' => 'accepted', 'label' => 'Accepted', 'is_active' => '1', 'is_locked' => '1', 'is_finalized' => '1', 'sort_order' => '40'],
            ['code' => 'rejected', 'label' => 'Rejected', 'is_active' => '1', 'is_locked' => '1', 'is_finalized' => '1', 'sort_order' => '50'],
            ['code' => 'expired', 'label' => 'Expired', 'is_active' => '1', 'is_locked' => '1', 'is_finalized' => '1', 'sort_order' => '60']
        ];
    }
}
