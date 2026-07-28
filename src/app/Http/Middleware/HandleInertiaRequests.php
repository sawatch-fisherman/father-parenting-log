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
     * `lang/{locale}/*.php` を読み込み、ファイル名をキーとした連想配列にまとめて返す。
     *
     * @return array<string, array<string, mixed>>
     */
    private function loadMessages(string $locale): array
    {
        $path = lang_path($locale);

        if (! File::isDirectory($path)) {
            return [];
        }

        $messages = [];

        foreach (File::files($path) as $file) {
            $messages[$file->getFilenameWithoutExtension()] = require $file->getPathname();
        }

        return $messages;
    }
}
