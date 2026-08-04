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
        // Act
        $response = $this->post('/locale', ['locale' => 'en']);

        // Assert
        $response->assertRedirect();
        $response->assertCookie('locale', 'en');

        // Act: 発行されたCookieを次のリクエストに乗せる
        $this->withCookie('locale', 'en')->get('/');

        // Assert
        $this->assertSame('en', App::getLocale());
    }

    public function test_rejects_unsupported_locale_without_touching_the_cookie(): void
    {
        // Act
        $response = $this->post('/locale', ['locale' => 'fr']);

        // Assert
        $response->assertSessionHasErrors('locale');
        $response->assertCookieMissing('locale');
    }

    public function test_defaults_to_japanese_without_a_locale_cookie(): void
    {
        // Act
        $this->get('/');

        // Assert
        $this->assertSame('ja', App::getLocale());
    }

    public function test_ignores_an_unsupported_locale_cookie(): void
    {
        // Arrange & Act: 未対応ロケールのCookieを載せてリクエストする
        $this->withCookie('locale', 'fr')->get('/');

        // Assert
        $this->assertSame(Config::string('app.locale'), App::getLocale());
    }

    /**
     * 設定値同士の整合性チェックのため、実行（Act）にあたる操作を持たない。
     */
    public function test_default_and_fallback_locales_are_part_of_the_supported_list(): void
    {
        // Arrange
        $supportedLocales = Config::array('totoops.supported_locales');

        // Assert
        $this->assertContains(Config::string('app.locale'), $supportedLocales);
        $this->assertContains(Config::string('app.fallback_locale'), $supportedLocales);
    }
}
