<?php
declare(strict_types=1);

namespace MageSuite\TfaDoNotForceAllProviders\Plugin\Magento\TwoFactorAuth\Block\ChangeProvider;

class ShowAllProviders
{
    protected \Magento\Framework\Serialize\SerializerInterface $serializer;

    protected \Magento\TwoFactorAuth\Api\TfaInterface $tfa;

    protected \Magento\Authorization\Model\UserContextInterface $userContext;

    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\TwoFactorAuth\Api\TfaInterface $tfa,
        \Magento\Authorization\Model\UserContextInterface $userContext
    ) {
        $this->serializer = $serializer;
        $this->tfa = $tfa;
        $this->userContext = $userContext;
    }

    public function afterGetJsLayout(
        \Magento\TwoFactorAuth\Block\ChangeProvider $subject,
        string $result
    ): string {
        $result = $this->serializer->unserialize($result);
        $providers = [];

        foreach ($this->getProvidersList($subject) as $provider) {
            $providers[] = [
                'code' => $provider->getCode(),
                'name' => $provider->getName(),
                'auth' => $provider->isActive($this->userContext->getUserId())
                    ? $subject->getUrl($provider->getAuthAction())
                    : $subject->getUrl('tfa/tfa/requestconfig'),
                'icon' => $subject->getViewFileUrl($provider->getIcon()),
            ];
        }

        $result['components']['tfa-change-provider']['switchIcon'] = $subject->getViewFileUrl('Magento_TwoFactorAuth::images/change_provider.png');
        $result['components']['tfa-change-provider']['providers'] = $providers;

        return $this->serializer->serialize($result);
    }

    protected function getProvidersList(\Magento\TwoFactorAuth\Block\ChangeProvider $block): array
    {
        $res = [];
        $providers = $this->tfa->getUserProviders((int)$this->userContext->getUserId());

        foreach ($providers as $provider) {
            if ($provider->getCode() === $block->getData('provider')) {
                continue;
            }

            $res[] = $provider;
        }

        return $res;
    }
}
