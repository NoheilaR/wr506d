<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiKeyControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test GET /api/me/api-key without authentication
     */
    public function testGetApiKeyWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/me/api-key');

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * Test GET /api/me/api-key when user has no API key
     */
    public function testGetApiKeyWhenNoKeyExists(): void
    {
        $this->markTestIncomplete(
            'This test requires JWT authentication setup. ' .
            'To implement: 1) Authenticate a test user, 2) Request API key status.'
        );

        // Example implementation:
        // $token = $this->getAuthToken('test@example.com', 'password');
        // $this->client->request('GET', '/api/me/api-key', [], [], [
        //     'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        // ]);
        // $this->assertResponseIsSuccessful();
        // $response = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertFalse($response['hasApiKey']);
    }

    /**
     * Test POST /api/me/api-key to generate a new API key
     */
    public function testGenerateApiKey(): void
    {
        $this->markTestIncomplete(
            'This test requires JWT authentication setup. ' .
            'To implement: 1) Authenticate a test user, 2) Generate an API key, 3) Verify response.'
        );

        // Example implementation:
        // $token = $this->getAuthToken('test@example.com', 'password');
        // $this->client->request('POST', '/api/me/api-key', [], [], [
        //     'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        // ]);
        // $this->assertResponseStatusCodeSame(201);
        // $response = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertArrayHasKey('apiKey', $response);
        // $this->assertArrayHasKey('prefix', $response);
        // $this->assertEquals(64, strlen($response['apiKey']));
        // $this->assertEquals(16, strlen($response['prefix']));
    }

    /**
     * Test POST /api/me/api-key regenerates key if one already exists
     */
    public function testRegenerateApiKey(): void
    {
        $this->markTestIncomplete(
            'This test requires: 1) User with existing API key, 2) Regenerate, 3) Verify old key is invalid.'
        );
    }

    /**
     * Test PATCH /api/me/api-key to enable/disable API key
     */
    public function testToggleApiKeyEnabled(): void
    {
        $this->markTestIncomplete(
            'This test requires: 1) User with API key, 2) Toggle enabled status, 3) Verify change.'
        );

        // Example implementation:
        // $token = $this->getAuthToken('test@example.com', 'password');
        // $this->client->request('PATCH', '/api/me/api-key', [], [], [
        //     'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        //     'CONTENT_TYPE' => 'application/json',
        // ], json_encode(['enabled' => false]));
        // $this->assertResponseIsSuccessful();
        // $response = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertFalse($response['enabled']);
    }

    /**
     * Test PATCH /api/me/api-key with invalid request body
     */
    public function testToggleApiKeyWithInvalidBody(): void
    {
        $this->markTestIncomplete(
            'This test requires: 1) Authenticated user, 2) Send invalid PATCH request.'
        );

        // Example implementation:
        // $token = $this->getAuthToken('test@example.com', 'password');
        // $this->client->request('PATCH', '/api/me/api-key', [], [], [
        //     'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        //     'CONTENT_TYPE' => 'application/json',
        // ], json_encode(['invalid_field' => true]));
        // $this->assertResponseStatusCodeSame(400);
    }

    /**
     * Test DELETE /api/me/api-key to revoke API key
     */
    public function testRevokeApiKey(): void
    {
        $this->markTestIncomplete(
            'This test requires: 1) User with API key, 2) Revoke, 3) Verify key is deleted.'
        );

        // Example implementation:
        // $token = $this->getAuthToken('test@example.com', 'password');
        // $this->client->request('DELETE', '/api/me/api-key', [], [], [
        //     'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        // ]);
        // $this->assertResponseIsSuccessful();
        // $response = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertStringContainsString('revoked', $response['message']);
    }

    /**
     * Test DELETE /api/me/api-key when no API key exists
     */
    public function testRevokeNonExistentApiKey(): void
    {
        $this->markTestIncomplete(
            'This test requires: 1) User without API key, 2) Attempt to revoke.'
        );

        // Example implementation:
        // $token = $this->getAuthToken('test@example.com', 'password');
        // $this->client->request('DELETE', '/api/me/api-key', [], [], [
        //     'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        // ]);
        // $this->assertResponseStatusCodeSame(400);
    }

    /**
     * Helper method to get JWT auth token (to be implemented)
     */
    private function getAuthToken(string $email, string $password): string
    {
        $this->client->request('POST', '/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        $response = json_decode($this->client->getResponse()->getContent(), true);
        return $response['token'] ?? '';
    }
}
