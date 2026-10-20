<?php

declare(strict_types=1);

namespace MageSuite\TfaDoNotForceAllProviders\Plugin\Magento\TwoFactorAuth\Block\ChangeProvider;

class ShowAllProviders
{
    public function __construct(
        protected \Magento\Framework\Serialize\SerializerInterface $serializer,
        protected \Magento\TwoFactorAuth\Api\TfaInterface $tfa,
        protected \Magento\Authorization\Model\UserContextInterface $userContext
    ) {
    }

    public function afterGetJsLayout(\Magento\TwoFactorAuth\Block\ChangeProvider $subject, string $result): string
    {
        $jsLayout = $this->serializer->unserialize($result);
        $listedProviders = $this->getListedProviders($jsLayout);
        $switchableProviders = $this->getSwitchableProviders($subject);

        if (empty(array_diff_key($switchableProviders, $listedProviders))) {
            return $result;
        }

        $providers = [];

        foreach ($switchableProviders as $code => $provider) {
            $providers[] = $listedProviders[$code] ?? $this->getConfigurationRequestEntry($subject, $provider);
        }

        $jsLayout['components']['tfa-change-provider']['providers'] = $providers;

        return $this->serializer->serialize($jsLayout);
    }

    protected function getListedProviders(array $jsLayout): array
    {
        $providers = $jsLayout['components']['tfa-change-provider']['providers'] ?? [];

        return array_column($providers, null, 'code');
    }

    protected function getSwitchableProviders(\Magento\TwoFactorAuth\Block\ChangeProvider $block): array
    {
        $providers = [];

        foreach ($this->tfa->getUserProviders((int)$this->userContext->getUserId()) as $provider) {
            if ($provider->getCode() === $block->getData('provider')) {
                continue;
            }

            $providers[$provider->getCode()] = $provider;
        }

        return $providers;
    }

    protected function getConfigurationRequestEntry(\Magento\TwoFactorAuth\Block\ChangeProvider $block, \Magento\TwoFactorAuth\Api\ProviderInterface $provider): array
    {
        return [
            'code' => $provider->getCode(),
            'name' => $provider->getName(),
            'auth' => $block->getUrl('tfa/tfa/requestconfig'),
            'icon' => $block->getViewFileUrl($provider->getIcon()),
        ];
    }
}
