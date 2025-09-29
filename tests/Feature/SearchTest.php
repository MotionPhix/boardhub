<?php

use App\Models\Billboard;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a tenant for tests and bind it to the application
    $this->tenant = Tenant::factory()->create();
    app()->instance('tenant', $this->tenant);
});

it('can search across multiple models', function () {
    // Create test data
    $client = Client::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Acme Marketing Agency',
        'company' => 'Acme Corp',
        'email' => 'contact@acme.com',
    ]);

    $contract = Contract::factory()->create([
        'tenant_id' => $this->tenant->id,
        'client_id' => $client->id,
        'contract_number' => 'CNT-2025-12345',
        'notes' => 'Premium billboard package for Acme',
    ]);

    $billboard = Billboard::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'City Center Premium',
        'location' => 'Lilongwe City Center',
        'description' => 'High-traffic location near shopping mall',
    ]);

    // Test client search by name
    $response = $this->getJson('/api/search?q=Acme');

    $response->assertSuccessful()
        ->assertJson([
            'count' => 2, // Client and contract should match
        ])
        ->assertJsonPath('data.0.type', 'Client')
        ->assertJsonPath('data.0.title', 'Acme Marketing Agency (Acme Corp)');

    // Test billboard search by location
    $response = $this->getJson('/api/search?q=Lilongwe');

    $response->assertSuccessful()
        ->assertJson([
            'count' => 1,
        ])
        ->assertJsonPath('data.0.type', 'Billboard')
        ->assertJsonPath('data.0.title', 'Billboard: City Center Premium at Lilongwe City Center');

    // Test contract search by number
    $response = $this->getJson('/api/search?q=CNT-2025');

    $response->assertSuccessful()
        ->assertJson([
            'count' => 1,
        ])
        ->assertJsonPath('data.0.type', 'Contract')
        ->assertJsonPath('data.0.title', 'Contract CNT-2025-12345');
});

it('validates search query requirements', function () {
    // Test missing query parameter
    $response = $this->getJson('/api/search');
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);

    // Test query too short
    $response = $this->getJson('/api/search?q=x');
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);

    // Test query too long
    $longQuery = str_repeat('a', 101);
    $response = $this->getJson('/api/search?q='.$longQuery);
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);
});

it('returns empty results for no matches', function () {
    $response = $this->getJson('/api/search?q=nonexistent');

    $response->assertSuccessful()
        ->assertJson([
            'data' => [],
            'count' => 0,
        ]);
});

it('handles wildcard searches correctly', function () {
    Billboard::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Downtown Billboard'
    ]);
    Billboard::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Uptown Billboard'
    ]);
    Client::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Billboard Experts Ltd'
    ]);

    $response = $this->getJson('/api/search?q=Billboard');

    $response->assertSuccessful();

    expect($response->json('count'))->toBeGreaterThanOrEqual(3);
    expect($response->json('data'))->toHaveCount($response->json('count'));
});

it('returns search results for clients', function () {
    // Create a client that should match the search
    Client::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Acme SearchCo',
        'company' => 'SearchCo',
        'email' => 'search@example.test',
    ]);

    $response = $this->get('/api/search?q=Acme');

    $response->assertSuccessful();

    $data = $response->json('data');

    expect(is_array($data))->toBeTrue();
    expect(count($data))->toBeGreaterThan(0);
});
