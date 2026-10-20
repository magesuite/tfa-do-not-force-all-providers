<?php

declare(strict_types=1);

namespace MageSuite\TfaDoNotForceAllProviders\Test\Integration\Plugin\Magento\TwoFactorAuth\Api\UserConfigManagerInterface;

/**
 * Aitoc_Core's feed check calls reinit() on the first admin request after a cache flush, which drops #[Config] overrides, so it is disabled here.
 */
#[\Magento\TestFramework\Fixture\DbIsolation(true)]
class ReplaceInactiveDefaultProviderTest extends \Magento\TwoFactorAuth\TestFramework\TestCase\AbstractBackendController
{
    protected const TFA_INDEX_URI = 'backend/tfa/tfa/index';

    protected ?\Magento\TwoFactorAuth\Api\TfaInterface $tfa;

    protected ?\Magento\TwoFactorAuth\Api\UserConfigManagerInterface $userConfigManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tfa = $this->_objectManager->get(\Magento\TwoFactorAuth\Api\TfaInterface::class);
        $this->userConfigManager = $this->_objectManager->get(\Magento\TwoFactorAuth\Api\UserConfigManagerInterface::class);
    }

    #[\Magento\TestFramework\Fixture\AppArea('adminhtml')]
    #[\Magento\TestFramework\Fixture\Config('twofactorauth/general/force_providers', 'google,u2fkey')]
    #[\Magento\TestFramework\Fixture\Config('aitoc_core/notifications/notice_enable', 0, 'store', 'default')]
    public function testUserWithOnlyU2fKeyActiveIsChallengedWithU2fKeyWhenNoDefaultProviderIsStored(): void
    {
        $userId = $this->getUserId();
        $this->tfa->getProvider(\Magento\TwoFactorAuth\Model\Provider\Engine\U2fKey::CODE)->activate($userId);
        $this->userConfigManager->setDefaultProvider($userId, '');

        $this->dispatch(self::TFA_INDEX_URI);

        $this->assertRedirect($this->stringContains('u2f/auth'));
    }

    #[\Magento\TestFramework\Fixture\AppArea('adminhtml')]
    #[\Magento\TestFramework\Fixture\Config('twofactorauth/general/force_providers', 'google,u2fkey')]
    #[\Magento\TestFramework\Fixture\Config('aitoc_core/notifications/notice_enable', 0, 'store', 'default')]
    public function testActiveStoredDefaultProviderIsKeptWhenOtherProvidersAreActiveToo(): void
    {
        $userId = $this->getUserId();
        $this->tfa->getProvider(\Magento\TwoFactorAuth\Model\Provider\Engine\Google::CODE)->activate($userId);
        $this->tfa->getProvider(\Magento\TwoFactorAuth\Model\Provider\Engine\U2fKey::CODE)->activate($userId);
        $this->userConfigManager->setDefaultProvider($userId, \Magento\TwoFactorAuth\Model\Provider\Engine\U2fKey::CODE);

        $this->dispatch(self::TFA_INDEX_URI);

        $this->assertRedirect($this->stringContains('u2f/auth'));
    }

    protected function getUserId(): int
    {
        return (int)$this->_session->getUser()->getId();
    }
}
