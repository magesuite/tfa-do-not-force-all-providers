<?php
declare(strict_types=1);

namespace MageSuite\TfaDoNotForceAllProviders\Plugin\Magento\TwoFactorAuth\Api\TfaInterface;

class SkipSecondProviderActivation
{
    protected \Magento\Framework\App\Request\Http $request;

    protected array $forbiddenActionList = [
        'tfa_tfa_requestconfig'
    ];

    public function __construct(\Magento\Framework\App\Request\Http $request)
    {
        $this->request = $request;
    }

    public function aroundGetProvidersToActivate(
        \Magento\TwoFactorAuth\Api\TfaInterface $subject,
        callable $proceed,
        int $userId
    ): array {
        $providersToActive = $proceed($userId);

        if (empty($providersToActive) || $this->isActionForbidden()) {
            return $providersToActive;
        }

        $forcedProviders = $subject->getForcedProviders();

        if (count($providersToActive) < count($forcedProviders)) {
            return [];
        }

        return $providersToActive;
    }

    protected function isActionForbidden(): bool
    {
        $actionName = $this->request->getFullActionName();

        if ($actionName == 'tfa_tfa_index' && $this->request->getParam('tfat')) {
            return true;
        }

        return in_array($actionName, $this->forbiddenActionList);
    }
}
