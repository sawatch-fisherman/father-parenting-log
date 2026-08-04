<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * 初回ページ訪問時に読み込まれるルートテンプレート。
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * 現在のアセットバージョンを判定する。
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * デフォルトで共有するpropsを定義する。
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'locale' => App::getLocale(),
            'messages' => $this->loadMessages(App::getLocale()),
        ];
    }

    /**
     * 現在ロケールの翻訳を、フォールバックロケールの翻訳に上書きマージして返す。
     *
     * PHP 側の `__()` は未翻訳キーを `fallback_locale`（ja）で補うので、Vue 側の `t()` も
     * 同じ挙動になるよう「フォールバック → 現在ロケール」の順に重ねる。これをしないと
     * `lang/en/` が未投入の間、英語表示にした瞬間に `nav.record` のような生キーが画面に出る。
     *
     * @see docs/decisions.md §1.3「多言語化（i18n）」／§2 未決#18
     *
     * @return array<string, mixed>
     */
    private function loadMessages(string $locale): array
    {
        $fallbackLocale = App::getFallbackLocale();

        $messages = $this->loadLocaleFiles($fallbackLocale);

        if ($locale !== $fallbackLocale) {
            $messages = array_replace_recursive($messages, $this->loadLocaleFiles($locale));
        }

        return $messages;
    }

    /**
     * `lang/{locale}/*.php` を読み込み、ファイル名をキーとした連想配列にまとめて返す。
     *
     * TODO: 翻訳キーが増えたら `Cache::rememberForever("messages.{$locale}")` 等でのキャッシュ、
     * もしくはロケール別の静的 JSON 配信を検討する（現状は全 Inertia レスポンスに毎回同梱される）。
     *
     * @return array<string, mixed>
     */
    private function loadLocaleFiles(string $locale): array
    {
        $path = lang_path($locale);

        if (! File::isDirectory($path)) {
            return [];
        }

        $messages = [];

        foreach (File::files($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $loaded = require $file->getPathname();

            $messages[$file->getFilenameWithoutExtension()] = is_array($loaded) ? $loaded : [];
        }

        return $messages;
    }
}
