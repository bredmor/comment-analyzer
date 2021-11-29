<?php declare(strict_types=1);

namespace Tests\Unit;

final class ApiKeyTest extends \Tests\TestCase
{
    public function testApiKeyWasAdded(): void
    {
        $this->assertArrayHasKey("PERSPECTIVE_API_KEY", $_ENV);
        $this->assertNotEquals("YOUR_API_KEY_HERE", $_ENV['PERSPECTIVE_API_KEY'], "Please copy phpunit.xml.dist to phpunit.xml and add your API Key to the Environment Variables!");
    }

}