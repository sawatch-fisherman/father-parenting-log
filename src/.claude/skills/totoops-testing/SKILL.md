---
name: totoops-testing
description: TotoOps の PHP テスト（src/tests/ 配下）を追加・修正するときに必ず適用する。AAA（Arrange-Act-Assert）コメントの付け方、各テストメソッドに書く日本語 docblock の書式、AAA が当てはまらないテストの扱い、PHPUnit 12 で使ってはいけない記法を定義する。テストを新規作成するとき、既存テストにメソッドを足すとき、テストコードをレビューするときに使う。
---

# TotoOps テストコード規約

`src/tests/` 配下の PHP テストに適用する。Laravel 一般のテスト作法（`LazilyRefreshDatabase` の使用、ファクトリの使い方など）は `laravel-best-practices` スキルの `rules/testing.md` に従い、ここでは**このプロジェクト固有の書き方**だけを定義する。

## 1. AAA コメントを付ける

各テストメソッドの本文を Arrange / Act / Assert に区切り、`// Arrange`・`// Act`・`// Assert` の行コメントを置く。

```php
public function test_withdrawal_keeps_every_care_log(): void
{
    // Arrange
    $user = User::factory()->create();
    CareLog::factory()->count(3)->create(['user_id' => $user->id]);

    // Act
    $user->forceFill(['provider' => 'withdrawn', 'withdrawn_at' => now()])->save();

    // Assert
    $this->assertSame(3, CareLog::where('user_id', $user->id)->count());
}
```

規則：

- **マーカーは英語1語のみ。** 補足が要る箇所だけ `// Act: 発行されたCookieを次のリクエストに乗せる` のように日本語を後置する。
- **該当するブロックが無ければ、そのマーカーは書かない。** 準備不要のテストに空の `// Arrange` を置かない。
- **1メソッドに Act/Assert が2巡以上あってもよい。** 「POST でCookieが発行される → その Cookie で後続リクエストの表示が変わる」のように因果の連結そのものが検証対象になる Feature テストでは、マーカーを2回ずつ書く。この構造を崩してテストを分割しない（分割するとCookie値をハードコードすることになり、因果の検証が失われる）。

## 2. Act の定義

**Act は「テスト対象に入力を与える操作」**（書き込み・HTTP リクエスト・Artisan コマンド）を指す。**検証のために読み出すだけのクエリは Act ではなく Arrange に含める。**

この基準により、Seeder 投入済みの状態やマスタデータの整合性そのものを検証するテストは **Act を持たない**形になる。その場合は Act のマーカーを書かず、docblock に理由を1行書く。

```php
/**
 * 初期おすすめ8個が、重複のない8件のTotoOps標準育児行動を指していることを検証する。
 *
 * 設定値とSeeder投入済みデータの整合性チェックのため、実行（Act）にあたる操作を持たない。
 */
```

クラス内のほぼ全メソッドが Act 不在になる場合は、メソッドごとに繰り返さずクラスの docblock に1回だけ書く（`TitleSeedIntegrityTest` がその例）。

## 3. AAA の順序どおりに書けない場合

**無理に当てはめない。** 順序が崩れる場合は理由をコメントに書いて、意図的であることが分かるようにする。

- **`expectException` を使うテスト** — PHPUnit の仕様上 Assert が Act より前に来る。`// Assert: 例外の期待はPHPUnitの仕様上Actより前に宣言する` と注記する。
- **Arrange と Act が1文に融合している場合** — `// Arrange & Act`、`// Act & Assert` のように連結して書く。メソッドチェーン（`$this->withCookie(...)->get('/')->assertInertia(...)`）を分解するためにコードを書き換えない。
- **AAA のどれでもない後始末** — `// 後始末: 後続テストが引き継ぐスキーマを元に戻す` のように、AAA マーカーを使わず日本語で書く。`// Assert` を流用しない。

## 4. 各テストメソッドに日本語の docblock を書く

**メソッド名は英語のまま**（`test_snake_case`）、**説明は日本語の docblock** で書く。

```php
/**
 * 手で書き換えられた未対応ロケールのCookieを無視し、既定ロケールで表示することを検証する。
 *
 * Cookieはクライアント側で自由に改変できるため、`POST /locale` のバリデーションとは別に
 * 読み取り時にも防ぐ必要がある。
 */
```

規則：

- **1行目は「〜を検証する。」で終わる1文**にする。IDE のホバーやテスト一覧では1行目しか出ないため、1行目だけで何を検証しているか分かる状態にする。
- **背景・設計上の理由は1行空けて2段落目に書く。** 「なぜこの検証が必要か」から書き始めて、結局何を検証しているのか分からない docblock にしない。
- 関連する仕様は `docs/data-model.md ④` `docs/decisions.md §1.3` のようにドキュメント名とセクションで参照する。
- 書式は docblock (`/** */`) に統一する。AAA マーカーが行コメント (`//`) なので、混ぜると視覚的に区別できなくなる。

## 5. 使ってはいけない記法

- **`@testdox` アノテーションは使わない。** PHPUnit 12 では docblock 内のメタデータのサポートが無くなっており、書いても警告すら出ずに黙って無視される（PHPUnit 12.5.31 で実測確認済み）。
- **`#[TestDox]` 属性も現時点では使わない。** 属性形式は `vendor/bin/phpunit --testdox` でのみ有効で、このプロジェクトの標準コマンドである `composer test`（= `php artisan test`）の出力には反映されない。概要は日本語 docblock に書く方針で統一する。

## 6. 確認

テストを追加・修正したら `docker compose exec app composer check`（pint → phpstan level 8 → test）を通す。コメントの追加だけでも pint の整形対象になるため省略しない。
