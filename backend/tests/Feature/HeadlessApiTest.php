<?php

test('root url returns structured json service status', function () {
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('service', config('app.name', 'Summit API'))
        ->assertJsonPath('version', 'v1')
        ->assertJsonPath('api_prefix', '/api/v1');
});

test('root url forces json even when request explicitly accepts text/html', function () {
    $response = $this->withHeaders([
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9',
    ])->get('/');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('status', 'success');
});

test('unregistered web route returns pure json 404', function () {
    $response = $this->withHeaders([
        'Accept' => 'text/html',
    ])->get('/unregistered/random/route');

    $response->assertStatus(404)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('error_code', 'ERR_NOT_FOUND');
});

test('unregistered api route returns pure json 404', function () {
    $response = $this->get('/api/v1/unregistered/random/endpoint');

    $response->assertStatus(404)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('error_code', 'ERR_NOT_FOUND');
});

test('unsupported http method returns pure json 405', function () {
    $response = $this->post('/');

    $response->assertStatus(405)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('error_code', 'ERR_METHOD_NOT_ALLOWED');
});
