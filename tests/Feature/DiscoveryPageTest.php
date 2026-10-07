<?php

use App\Models\User;

it('requires authentication for the search page', function () {
    $this->get('/search')->assertRedirect('/login');
});

it('renders search with existing layout and safe query handling', function () {
    $response = $this->actingAs(User::factory()->create())->get('/search?q=%3Cscript%3E&type=users')->assertOk()
        ->assertSee('discoverySearch()', false)->assertSee('search-query')
        ->assertSee('bg-ground font-roboto', false)->assertSee('Jenis hasil pencarian')
        ->assertDontSee('<script>alert(', false);

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//aside//input[@id="search-query"]')->length)->toBe(1)
        ->and($xpath->query('//main//input[@id="search-query"]')->length)->toBe(0)
        ->and($xpath->query('//main//input[@id="search-query-mobile"]')->length)->toBe(1);
});

it('shows global feed controls only on dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('dashboard-search')
        ->assertSee('Pilihan feed')->assertSee('discoveryFeed', false);
    $this->actingAs($user)->get('/my/threads')->assertOk()->assertDontSee('dashboard-search')
        ->assertDontSee('Pilihan feed')->assertSee('My Threads');
});
