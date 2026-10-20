<?php

declare(strict_types=1);

namespace MageSuite\TfaDoNotForceAllProviders\Test\Unit\Plugin\Magento\TwoFactorAuth\Block\ChangeProvider;

class ShowAllProvidersTest extends \PHPUnit\Framework\TestCase
{
    protected const REQUEST_CONFIG_URL = 'https://example.com/admin/tfa/tfa/requestconfig/';

    protected \Magento\Framework\Serialize\Serializer\Json $serializer;

    protected function setUp(): void
    {
        $this->serializer = new \Magento\Framework\Serialize\Serializer\Json();
    }

    public function testItLeavesLayoutUntouchedWhenCoreListsEverySwitchableProvider(): void
    {
        $block = $this->createBlock('google');
        $block->expects($this->never())->method('getUrl');
        $result = $this->serializeProviders([
            ['code' => 'u2fkey', 'name' => 'U2F', 'auth' => 'https://example.com/admin/tfa/u2f/configure/', 'icon' => 'u2fkey.png', 'is_configured' => false],
        ]);

        $actual = $this->createPlugin(['google', 'u2fkey'])->afterGetJsLayout($block, $result);

        $this->assertSame($result, $actual);
    }

    public function testItAddsConfigurationRequestLinkForProvidersMissingFromCoreLayout(): void
    {
        $block = $this->createBlock('google');
        $duoEntry = ['code' => 'duo_security', 'name' => 'Duo', 'auth' => 'https://example.com/admin/tfa/duo/auth/', 'icon' => 'duo.png'];
        $result = $this->serializeProviders([$duoEntry]);

        $actual = $this->createPlugin(['google', 'authy', 'duo_security'])->afterGetJsLayout($block, $result);

        $expectedProviders = [
            ['code' => 'authy', 'name' => 'authy name', 'auth' => self::REQUEST_CONFIG_URL, 'icon' => 'authy.png'],
            $duoEntry,
        ];
        $this->assertSame($expectedProviders, $this->serializer->unserialize($actual)['components']['tfa-change-provider']['providers']);
    }

    protected function createPlugin(array $userProviderCodes): \MageSuite\TfaDoNotForceAllProviders\Plugin\Magento\TwoFactorAuth\Block\ChangeProvider\ShowAllProviders
    {
        $tfa = $this->createMock(\Magento\TwoFactorAuth\Api\TfaInterface::class);
        $tfa->method('getUserProviders')->with(1)->willReturn(array_map([$this, 'createProvider'], $userProviderCodes));

        $userContext = $this->createStub(\Magento\Authorization\Model\UserContextInterface::class);
        $userContext->method('getUserId')->willReturn(1);

        return new \MageSuite\TfaDoNotForceAllProviders\Plugin\Magento\TwoFactorAuth\Block\ChangeProvider\ShowAllProviders($this->serializer, $tfa, $userContext);
    }

    protected function createProvider(string $code): \Magento\TwoFactorAuth\Api\ProviderInterface
    {
        $provider = $this->createStub(\Magento\TwoFactorAuth\Api\ProviderInterface::class);
        $provider->method('getCode')->willReturn($code);
        $provider->method('getName')->willReturn(sprintf('%s name', $code));
        $provider->method('getIcon')->willReturn(sprintf('%s.png', $code));

        return $provider;
    }

    protected function createBlock(string $currentProviderCode): \Magento\TwoFactorAuth\Block\ChangeProvider&\PHPUnit\Framework\MockObject\MockObject
    {
        $block = $this->createMock(\Magento\TwoFactorAuth\Block\ChangeProvider::class);
        $block->method('getData')->with('provider')->willReturn($currentProviderCode);
        $block->method('getUrl')->with('tfa/tfa/requestconfig')->willReturn(self::REQUEST_CONFIG_URL);
        $block->method('getViewFileUrl')->willReturnArgument(0);

        return $block;
    }

    protected function serializeProviders(array $providers): string
    {
        return $this->serializer->serialize(['components' => ['tfa-change-provider' => ['providers' => $providers]]]);
    }
}
