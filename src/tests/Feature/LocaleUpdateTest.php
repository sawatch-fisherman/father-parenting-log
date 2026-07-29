<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LocaleUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_updating_locale_sets_cookie_and_switches_subsequent_requests(): void
    {
        $response = $this->post('/locale', ['locale' => 'en']);

        $response->assertRedirect();
        $response->assertCookie('locale', 'en');

        $this->withCookie('locale', 'en')->get('/');

        $this->assertSame('en', App::getLocale());
    }

    public function test_rejects_unsupported_locale_without_touching_the_cookie(): void
    {
        $response = $this->post('/locale', ['locale' => 'fr']);

        $response->assertSessionHasErrors('locale');
        $response->assertCookieMissing('locale');
    }

    public function test_defaults_to_japanese_without_a_locale_cookie(): void
    {
        $this->get('/');

        $this->assertSame('ja', App::getLocale());
    }

    public function test_ignores_an_unsupported_locale_cookie(): void
    {
        $this->withCookie('locale', 'fr')->get('/');

        $this->assertSame(Config::string('app.locale'), App::getLocale());
    }

    public function test_default_and_fallback_locales_are_part_of_the_supported_list(): void
    {
        $supportedLocales = Config::array('totoops.supported_locales');

        $this->assertContains(Config::string('app.locale'), $supportedLocales);
        $this->assertContains(Config::string('app.fallback_locale'), $supportedLocales);
    }
}
