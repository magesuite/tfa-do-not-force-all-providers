<?php

declare(strict_types=1);

namespace MageSuite\TfaDoNotForceAllProviders\Plugin\Magento\TwoFactorAuth\Api\UserConfigManagerInterface;

class ReplaceInactiveDefaultProvider
{
    public function __construct(
        protected \Magento\TwoFactorAuth\Api\TfaInterface $tfa
    ) {
    }

    public function afterGetDefaultProvider(\Magento\TwoFactorAuth\Api\UserConfigManagerInterface $subject, string $result, int $userId): string
    {
        $activeProviderCodes = $this->getActiveProviderCodes($userId);

        if (empty($activeProviderCodes) || in_array($result, $activeProviderCodes, true)) {
            return $result;
        }

        return $activeProviderCodes[0];
    }

    protected function getActiveProviderCodes(int $userId): array
    {
        $activeProviderCodes = [];

        foreach ($this->tfa->getUserProviders($userId) as $provider) {
            if ($provider->isActive($userId)) {
                $activeProviderCodes[] = $provider->getCode();
            }
        }

        return $activeProviderCodes;
    }
}
