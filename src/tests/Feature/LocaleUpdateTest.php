<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocaleUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_updating_locale_sets_cookie_and_switches_subsequent_requests(): void
    {
        $response = $this->post('/locale', ['locale' => 'en']);

        $response->assertRedirect();
        $response->assertCookie('locale', 'en');

        $this->withCookie('locale', 'en')->get('/');

        $this->assertSame('en', App::getLocale());
    }

    public function test_rejects_unsupported_locale(): void
    {
        $response = $this->post('/locale', ['locale' => 'fr']);

        $response->assertSessionHasErrors('locale');
    }
}
