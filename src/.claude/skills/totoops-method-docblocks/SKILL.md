---
name: totoops-method-docblocks
description: TotoOps の PHP アプリケーションコード（src/app/ 配下：Controller・Model・Middleware・FormRequest・Policy・Service など）でメソッドを新規作成・変更するときに必ず適用する。各メソッドの直前に日本語1行サマリのdocblockを書く規約を定義する。コードレビュー時に実装を読まずに概要を把握できるようにするための規約。
---

# TotoOps メソッドdocblock規約

`src/app/` 配下の PHP メソッドに適用する。目的は**コードレビュー時に実装本文を読まなくても、そのメソッドが何をするか1行で分かるようにすること**。テストメソッドの docblock 規約（AAAコメントとの併用など）は `totoops-testing` スキルに従う。

## 1. 各メソッドに日本語1行サマリを書く

`public`／`protected`／`private` を問わず、実装を持つメソッドの直前に `/** ... */` docblock を置き、1行目を「〜する。」で終わる日本語1文にする。

```php
/**
 * プロフィール登録画面（S2）を表示する。
 */
public function create(): Response
{
    // ...
}
```

複数の処理をまとめて行うメソッドは、「主目的＋副次的に行うこと」まで1文に収める。

```php
/**
 * プロフィールを新規登録し、初期スロット設定を作成する。
 */
public function store(ProfileRequest $request): RedirectResponse
```

## 2. 既存の `@return` 等アノテーションとの併記

`@return`・`@param` などの型アノテーションがある場合は、1行目のサマリの後に1行空けて続ける（`totoops-testing` の日本語docblockと同じ並び）。

```php
/**
 * 年代の選択肢一覧を返す（未回答を除く）。
 *
 * @return array<int, array{value: int, label: string}>
 */
private function ageGroupOptions(): array
```

既に長い説明コメント（設計上の理由・背景など）がある場合も、1行目をサマリ文にして本文はその後ろに続ける。既存のインラインコメント（`//`）を書き換えて docblock に統合する必要はない（本文の実装意図の説明は `//` のままでよい。docblock の1行目はあくまで「何をするか」の要約）。

## 3. 省略してよいケース

以下は書かなくてよい（書いても実害はないが、名前と型だけで自明なため冗長になりやすい）：

- 引数なし・処理を持たない `__construct`（プロパティ昇格のみ）
- Laravel の規約により処理内容が名前から一意に決まる薄いラッパー（例：`casts()` のような1行 return のみのモデルメソッド）で、かつ `laravel-best-practices` スキルの命名規約（`isAccessible` のような説明的な名前）に既に従っている場合

迷ったら**書く方を選ぶ**。レビュー時に読む側は実装を追わずに済む方を優先する。

## 4. 確認

メソッドを追加・変更したら `docker compose exec app vendor/bin/pint --dirty --format agent` を実行する。docblock の追加のみでも整形対象になるため省略しない。
