<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
});

it('can display login page', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

it('can authenticate user with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@adpro.test',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@adpro.test',
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

it('cannot authenticate user with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@adpro.test',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@adpro.test',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

it('logs successful authentication', function () {
    $user = User::factory()->create([
        'email' => 'test@adpro.test',
        'password' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => 'test@adpro.test',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});