<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in with correct credentials and returns a token', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email'],
        ]);
});

it('rejects login with an incorrect password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('rejects login with an email that does not exist', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'nobody@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('blocks unauthenticated access to protected student routes', function () {
    $response = $this->getJson('/api/students');

    $response->assertStatus(401);
});

it('allows an authenticated user to reach protected student routes', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/students');

    $response->assertStatus(200);
});