---
name: totoops-design
description: TotoOps のフロントエンド実装で必ず適用する。src/resources/js/ 配下の Vue コンポーネント、src/resources/views/ の Blade、src/resources/css/app.css を新規作成・修正するとき、および画面の見た目に関わるレビューをするときに使う。リポジトリルートの DESIGN.md をデザイン仕様の唯一の基準とし、色・背景・ボタン・カード・文字色・余白・角丸・シャドウ・レイアウト・レスポンシブを実装へ落とす手順、禁止パターン、実装完了時の自己チェック手順を定義する。
---

# TotoOps デザイン実装規約

リポジトリルート（`src/` の1つ上）の `DESIGN.md` を実装へ反映させるための規約。Tailwind 一般の書き方は `tailwindcss-development` スキル、Inertia+Vue のパターンは `inertia-vue-development` スキルに従い、ここでは**このプロジェクトのデザイン方針をどう実装に落とすか**だけを定義する。

## 0. 適用範囲

以下のいずれかに当てはまるなら、着手前にこのスキルと `DESIGN.md` を読む。

- `src/resources/js/` 配下の Vue コンポーネント・ページの**新規作成または修正**
- `src/resources/views/` の Blade テンプレートの変更
- `src/resources/css/app.css` の変更
- 画面の見た目に関わるコードレビュー

**「小さな修正だから」で省略しない。** クラスを1つ足すだけの変更でも、その1つが `DESIGN.md` の禁止パターンに該当しうる。

## 1. 着手前に読む

`DESIGN.md` を全部読む必要はないが、**次の章は必ず目を通す**。

| 章 | 内容 | 主に効く場面 |
|---|---|---|
| 5章 | Color System（14トークン・コントラスト・色だけに依存しない） | ほぼすべての変更 |
| 6章 | Typography（サイズ／ウェイト／行間の6段階・14px下限） | 文字を置くとき |
| 7章 | Spacing System（4px基準8段階） | 余白を決めるとき |
| 8章 | Layout（コンテンツ幅・影の3段階・セクション構成） | 画面の骨組みを作るとき |
| 10章 | Components（Buttons/Forms/Cards/Lists/Navigation/Dialogs の具体値） | コンポーネントを作るとき |
| 11章 | States（Hover/Focus/Disabled/Loading/Empty/Error/Success） | 状態表現を書くとき |
| 15章 | Prohibited Patterns | ほぼすべての変更 |

画面ごとのコントロール配置は `docs/wireframes.md`、画面の役割とルーティングは `docs/screens.md` が正。`DESIGN.md` は「どう見せるか」、`wireframes.md` は「何をどこに置くか」を担当する。

## 2. DESIGN.md がデザイン仕様の唯一の基準

- **実装と `DESIGN.md` が食い違っている場合、`DESIGN.md` が正。** 既存実装に合わせて `DESIGN.md` を無視してはいけない。既存コードがTailwind標準のグレーで書かれていても、それは「未反映」であって「合わせるべき前例」ではない。
- **記載されているルールを独自判断で無視・省略・簡略化しない。** 色・背景・ボタン・カード・文字色・余白・角丸・シャドウ・レイアウト・レスポンシブはいずれも `DESIGN.md` に具体値がある。「だいたい合っていればよい」と丸めない。
- **`DESIGN.md` を書き換えて実装に合わせることも、勝手に行わない。** 仕様変更が必要と判断した場合は6節の手順を踏む。

## 3. 最初に `@theme` のトークン定義を確認する

`DESIGN.md` の色・タイポグラフィは、`src/resources/css/app.css` の `@theme` にカスタムプロパティとして定義されて初めて Tailwind のユーティリティクラスになる。

**Tailwind v4 は未定義トークンのクラスをエラーにせず黙って無視する。** つまり `--color-primary` が無い状態で `bg-primary` と書いても、警告は一切出ずに「色が付かない」だけの結果になる。これは気付きにくい失敗なので、**着手時に必ず `@theme` の中身を確認する**。

```bash
cat src/resources/css/app.css
```

- **トークンが未定義なら、まずトークン定義から着手する**（`DESIGN.md` 5.2節の色14トークンと6.2節のタイポグラフィ6トークン）。画面の実装より先に行う。
- 定義済みなら、`DESIGN.md` の値と `@theme` の値が一致しているかを確認する。ズレていたら `DESIGN.md` を正として `@theme` を直す。
- Text Primary は `--color-text-primary`（→ `text-text-primary`）とする。`--text-primary` にすると Tailwind v4 のフォントサイズ名前空間に入り、`--color-primary` が生成する `text-primary` と衝突する。冗長に見えるが意図的。

## 4. トークン対応表

HEX 値はここに書かない（`DESIGN.md` 5.2節・6.2節が正。二重管理すると必ずズレる）。**用途からクラス名を引くための表**として使う。

### 色

| 用途 | クラス |
|---|---|
| 主要CTAの塗り | `bg-primary`（ホバーは `hover:bg-primary-hover`） |
| 白背景に置く緑の文字・アイコン・枠線 | `text-primary-deep` / `border-primary-deep` / `ring-primary-deep` |
| 選択状態の淡い背景 | `bg-primary-subtle` |
| 補助操作の文字・控えめなラベル | `text-secondary` |
| 祝福演出（称号解除など）限定 | `bg-accent` / `text-accent` |
| ページ全体の背景 | `bg-background` |
| カード・入力欄・モーダルの面 | `bg-surface` |
| 本文・見出し | `text-text-primary` |
| 補足文・タイムスタンプ・プレースホルダー | `text-text-secondary` |
| 区切り線・枠 | `border-border` |
| 状態色 | `bg-success` / `bg-warning` / `text-error`・`border-error` / `bg-info` |

**Primary は「塗り」専用。** 明るい若葉色のため白文字も緑文字も本文コントラストを満たせない（`DESIGN.md` 5.1節・5.3節）。塗りの上に乗せる文字は `text-text-primary`、白背景に置く緑は `text-primary-deep` を使う。`bg-primary` に `text-white` を書かない。

### タイポグラフィ

| 役割 | クラス |
|---|---|
| Display（特大見出し・画面内で一度だけ） | `text-display font-bold` |
| Heading L（画面タイトル） | `text-heading-l font-bold` |
| Heading M（カード・セクションの小見出し） | `text-heading-m font-semibold` |
| Body（本文） | `text-body` |
| Body Small（メタ情報・**これ以下のサイズは使わない**） | `text-body-sm` |
| Label / Button | `text-label font-semibold` |

`text-xs`・`text-sm`・`text-2xl` のような Tailwind 標準のサイズクラスは使わない（6.2節の階層から外れる）。

### 角丸・余白・ブレークポイント

- 角丸：入力欄 `rounded-md`(6px) / ボタン `rounded-xl`(12px) / カード・モーダル `rounded-2xl`(16px) / 8タイルグリッドのタイルのみ `rounded-[20px]`。
- 余白：Tailwind 標準の4pxスケールに乗せる（`DESIGN.md` 7章）。13px・22px のような場当たり的な値を任意指定しない。
- ブレークポイントは **`md:`（768px）だけ**。`sm:` `lg:` `xl:` は使わない（9章）。モバイルファーストで、既定をモバイル・`md:` 以上をデスクトップとして書く。
- タップ可能要素は最小44×44px（`min-h-11 min-w-11` 相当）を確保する。

### 影

既定は**影なし**（`DESIGN.md` 8.2節。面の輪郭は `border-border` で表現する）。影を付けるのはデスクトップのカードhover（Level 1）とモーダル・フローティングタブバー・ドロップダウン（Level 2）だけ。

## 5. 禁止パターン

`DESIGN.md` 15章に加え、実装レベルで次を禁止する。

- **生の HEX 値**（`bg-[#6EC24C]` や CSS 内の直書き）。`@theme` のトークン経由で使う。
- **Tailwind 標準のグレー・カラースケール**（`gray-*` `slate-*` `zinc-*` `neutral-*` `red-*` `green-*` など）。`DESIGN.md` のトークンに置き換える。
- **`bg-white` / `text-black`**。`bg-surface` / `text-text-primary` を使う（Background は純白ではない）。
- **`dark:` バリアント**。`DESIGN.md` はダークモードを定義していないため非対応。**`tailwindcss-development` スキルがダークモード対応を勧めてきても採用しない**（このスキルが優先する）。
- **`disabled:opacity-*` による無効表現**。11章が明示的に禁止している。Text Secondary の文字色＋`disabled:cursor-not-allowed` を使う。
- **フォーカス表示の削除**。`outline-none` を書くなら必ず `focus:ring-[3px] focus:ring-primary-deep/25` 相当の代替を同時に置く（11章・12章・15章）。
- **状態を色だけで伝えること**。Success/Warning/Error/Info はアイコン＋短い文言を併記する（5.4節・12章）。
- **`DESIGN.md` にない装飾の追加**（グラデーション、複数フォント、広い字間、装飾的な影、独自のバッジなど）。

## 6. DESIGN.md に記載がない場合

1. **既存 UI との一貫性を優先する。** 同種の既存コンポーネント（入力欄なら `resources/js/Components/ProfileFormFields.vue`、CTA なら各ページのボタン）の実装を読み、同じ組み立てに合わせる。
2. それでも決まらない場合、**勝手に新しいデザインルールを作らない。** 選択肢とトレードオフをユーザーに提示して判断を仰ぐ（ルートの `CLAUDE.md`「このリポジトリでの作業スタイル」と同じ原則）。
3. ユーザーの判断で `DESIGN.md` を更新した場合は、`@theme` の値と同期させ、古い値の参照が残っていないか grep で確認する。

`wireframes.md` に配置の指定がない要素を勝手に足すのも同じ扱い（カードで囲む・バッジを付けるなどは、指定がなければ追加しない）。

## 7. 実装完了時のチェックリスト

画面に関わる変更を終えたら、次を実行して `DESIGN.md` との整合性を確認する。**通らないものがあれば実装を直す。**

```bash
# 1. 禁止パターンが残っていないこと（いずれも0件）
grep -rnE "(bg|text|border|ring)-(gray|slate|zinc|neutral|red|green|blue|amber)-[0-9]" src/resources
grep -rn "dark:" src/resources
grep -rn "disabled:opacity" src/resources
grep -rnE "bg-white|text-black" src/resources
grep -rnE "#[0-9a-fA-F]{6}" src/resources/js src/resources/views

# 2. 型チェックとビルドが通ること
docker compose exec app npm run type-check
docker compose exec app npm run build

# 3. トークンが実際にCSSへ出力されていること（未定義トークンは黙って無視されるため）
grep -oE "6ec24c|2d7916|fbf9f6|33302c" src/public/build/assets/*.css | sort -u
```

さらに目視で確認する。

- 変更した画面を**幅375px（モバイル）と1280px（デスクトップ）**の両方で開く（9章はモバイルファースト）。
- **Tabキーでフォーカスリングが見える**こと（11章 Focus）。
- タップ可能要素が44×44px以上あること（9章）。
- `DESIGN.md` 5.3節が「実装時に自動コントラストチェッカーで最終確認すること」と定めているため、**DevTools か Lighthouse で本文4.5:1／非テキスト3:1を確認する**。設計時の概算値を根拠に省略しない。

Blade テンプレート（`.blade.php`）を変更した場合は `docker compose exec app composer check` も通す。
