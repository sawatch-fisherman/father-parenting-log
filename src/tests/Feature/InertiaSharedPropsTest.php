<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * i18n 基盤の中核である Inertia 共有props（`locale` / `messages`）を検証する。
 *
 * @see docs/decisions.md §1.3「多言語化（i18n）」
 */
class InertiaSharedPropsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * 全ページ共通のInertia propsとして、現在のロケールと翻訳メッセージが渡ることを検証する。
     *
     * `/` は M1 以降 `auth` ミドルウェア配下（`home`）のため、未認証でも確認できる `/login` で検証する。
     */
    public function test_shares_current_locale_and_messages(): void
    {
        // Act & Assert
        $this->get('/login')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'ja')
            ->where('messages.nav.record', '記録'),
        );
    }

    /**
     * 通常のセッションフラッシュ（`session()->flash('success', ...)`）を積んでも、
     * 全ページ共通のInertia props（`page.props.flash`）には`success`が含まれないことを検証する。
     *
     * 以前は`success`もここで共有していたが、通常の共有propsはInertiaがブラウザのhistory state
     * にキャッシュするため、ブラウザバックで復元したページに古い成功メッセージが再表示される
     * 不具合があった（review-results/pr-10-review-2.md）。保存成功トーストは`Inertia::flash()`
     * （`page.flash`。history stateに乗らない専用チャンネル）経由に切り替えたため、通常の
     * セッションフラッシュへ`success`を積む経路自体がもう存在しないことを固定する。
     * `page.flash.success`自体は
     * `CareLogControllerTest::test_it_flashes_a_success_message_naming_the_recorded_care_action`
     * で、`flash.error`（引き続き通常の共有props）は`GoogleAuthenticationTest`で別途検証済み。
     */
    public function test_shared_flash_props_do_not_include_success(): void
    {
        // Arrange
        session()->flash('success', 'おむつ交換を記録しました');

        // Act & Assert
        $this->get('/login')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('flash.error', null)
            ->missing('flash.success'),
        );
    }

    /**
     * 翻訳ファイルが未投入のロケールを選んでも、メッセージが日本語にフォールバックすることを検証する。
     *
     * `locale` prop 自体は選択したロケールのまま渡り、メッセージだけが補われる。
     */
    public function test_untranslated_locale_falls_back_to_japanese_messages(): void
    {
        // Arrange & Act & Assert: Cookieの付与からpropsの検証までが1つの連鎖になっている
        $this->withCookie('locale', 'en')
            ->get('/login')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('locale', 'en')
                // `lang/en/` 未投入の間は日本語で補う（`nav.record` のような生キーを画面に出さない）。
                ->where('messages.nav.record', '記録'),
            );
    }
}
