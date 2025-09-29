<?php

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

it('returns health check successfully', function () {
    $response = $this->get('/health');

    $response->assertStatus(200)
             ->assertJson([
                 'status' => 'healthy'
             ]);
});
