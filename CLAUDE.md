# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## このリポジトリについて

EC-CUBE 4 系の**クーポンプラグイン**。管理画面でクーポン（値引き額／値引き率・利用条件・対象商品／カテゴリ）を登録・管理し、フロントの購入フローでクーポンコードによる値引きを適用できる。

- 管理画面: クーポンの一覧・登録・編集・有効無効化・削除、対象商品/カテゴリの検索モーダル（`Controller/Admin/CouponController.php`, `Controller/Admin/CouponSearchModelController.php`）。管理ナビに「クーポン」を追加（`Nav.php`）。
- フロント: 購入画面でクーポンコードを入力・適用（`Controller/CouponShoppingController.php`）。購入フローでクーポン明細を追加・状態制御（`Service/PurchaseFlow/Processor/CouponProcessor.php` / `CouponStateProcessor.php`）。
- 購入画面・マイページ・受注編集画面へのクーポン情報の差し込みは `Event.php`（`TemplateEvent`）が担当。

プラグインコードは `Coupon44`、Composer パッケージ名は `ec-cube/coupon44`。コード中の Twig 名前空間（`@Coupon44`）・クラス名前空間（`Plugin\Coupon44\...`）はすべて `Coupon44` 接頭辞を使う。

### ブランチ運用

ブランチ名が対応する EC-CUBE 本体バージョンを表す（`4.0` / `4.2` / `4.4` など）。`4.2` がデフォルトブランチで EC-CUBE 4.2/4.3（`Coupon42`）に対応し、`4.4` ブランチは EC-CUBE 4.4（Symfony 7.4 / Doctrine ORM 3.0 / PHP 8.2+、`Coupon44`）に対応する。**4.3 と 4.4 はアノテーション必須/属性必須・ORM 2/3 の違いで非互換**のため、別ブランチ・別コードで保守する。

## 開発・テストコマンド

このプラグイン単体では動作せず、**EC-CUBE 本体に組み込んだ状態**で開発・テストする。本体の取得・インストール・プラグイン有効化は `docker-compose.dev.yml` の entrypoint が自動実行する。

```bash
# 開発環境 (SQLite) の起動 — 本体インストール・プラグイン有効化まで自動
export COMPOSE_FILE=docker-compose.yml:docker-compose.dev.yml
docker compose up -d --wait

# MySQL / PostgreSQL で起動する場合
export COMPOSE_FILE=docker-compose.yml:docker-compose.mysql.yml:docker-compose.dev.yml
export COMPOSE_FILE=docker-compose.yml:docker-compose.pgsql.yml:docker-compose.dev.yml

# PHP バージョン切り替え（8.2-apache-4.4 / 8.3-apache-4.4 / 8.4-apache-4.4 / 8.5-apache-4.4）
TAG=8.3-apache-4.4 docker compose up -d --wait
```

起動後は管理画面 `http://localhost:8080/admin`（`admin` / `password`）、メールは MailCatcher `http://localhost:1080`。

### PHPUnit

テストは `Tests/` 配下の PHPUnit。`phpunit.xml.dist` により `APP_ENV=test` で実行される。

```bash
docker compose exec ec-cube bash -lc \
  "APP_ENV=test bin/console cache:clear --no-warmup && ./vendor/bin/phpunit -c app/Plugin/Coupon44/phpunit.xml.dist app/Plugin/Coupon44/Tests"
```

**注意（コンパイル済みキャッシュ）**: 有効化したプラグインのルーティングは、コンテナのコンパイル時に `dtb_plugin` を読む `EccubeExtension` で確定する。有効化直後の test キャッシュには反映されていないことがあるため、**PHPUnit 実行前に `APP_ENV=test` でキャッシュをクリアする**。これを怠るとコントローラのルートが `RouteNotFoundException` になる。

### E2E（Playwright）

`Tests/` の PHPUnit とは別に、`e2e/` に Playwright の E2E テストがある。`docker compose` で起動した
EC-CUBE（プラグイン有効化済み・`APP_ENV=dev`）に実ブラウザでアクセスして検証する。CI は
`.github/workflows/playwright.yml`（PHPUnit の `ci.yml` とは別ワークフロー）。

```bash
# EC-CUBE を起動しておく（上記の開発環境と同じ）
export COMPOSE_FILE=docker-compose.yml:docker-compose.dev.yml
docker compose up -d --wait

npm ci
npx playwright install chromium
npx playwright test              # ヘッドレス実行
npx playwright test --headed     # ブラウザを表示して実行
```

接続先は `ECCUBE_BASE_URL`（既定 `http://localhost:8080`）で切り替えられる。

**PHPUnit で書けないケースの受け皿**: 受注ステータスを「注文取消し」へ遷移させると本体の
`StockReduceProcessor` が `EntityManager::lock(PESSIMISTIC_WRITE)` を行うが、`APP_ENV=test` では
dama/doctrine-test-bundle のトランザクションが DBAL `Connection` 上で開いていないため
`TransactionRequiredException` になる（EC-CUBE/ec-cube#7016。`Tests/Web/Admin/OrderControllerTest::testOrderEditWithCouponCancel`
はこのためスキップしている）。E2E は prod と同じく `TransactionListener` が有効な実リクエストのため
この制約を受けない。**受注ステータス遷移を伴う検証は E2E に書くこと。**

E2E は 1 つの EC-CUBE インスタンスを共有し、クーポンの発行枚数や受注ステータスというグローバルな
状態を書き換えるため、`playwright.config.ts` で `workers: 1` / `fullyParallel: false` にしている。
クーポンコードはテストごとに `E2E${Date.now()}` で一意にし、テスト間の干渉を避ける。

### 静的解析・整形（任意）

EC-CUBE 本体（コンテナ内）の vendor を使って実行する。

```bash
# php-cs-fixer
docker compose exec ec-cube bash -lc \
  "cd app/Plugin/Coupon44 && /var/www/html/vendor/bin/php-cs-fixer fix --config=Resource/.php-cs-fixer.dist.php --dry-run --diff"

# rector（再移行・検証用）
# composer-based セットが vendor/composer/installed.json を読むため、本体のルートで実行する
# （プラグインディレクトリに cd すると "The installed package json not found" で失敗する）
docker compose exec ec-cube bash -lc \
  "cd /var/www/html && ./vendor/bin/rector process --config=app/Plugin/Coupon44/Resource/rector.php --dry-run"

# phpstan
docker compose exec ec-cube bash -lc \
  "cd app/Plugin/Coupon44 && /var/www/html/vendor/bin/phpstan analyse"
```

phpstan は level 6。Repository は `@extends AbstractRepository<Entity>` を付与して `find()` 等の戻り値型を確定させている。新規コードもこの水準を維持すること。

## アーキテクチャ

- **Entity** (`Entity/Coupon.php`, `CouponDetail.php`, `CouponOrder.php`): `#[ORM\*]` 属性 + 型付きプロパティ。`plg_coupon` / `plg_coupon_detail` / `plg_coupon_order` テーブル。`Coupon` は複数の `CouponDetail`（対象商品/カテゴリ）を OneToMany で保持。`CouponOrder` は注文へのクーポン適用実績。
- **Controller** (`Controller/`): `#[Route]`/`#[Template]` 属性。`CouponController` は一覧/登録/編集/有効無効化(enable)/削除(delete)。enable/delete は `#[MapEntity(id: 'id')]` で `Coupon` を解決（4.3 までの `@ParamConverter` から移行）。
- **Form** (`Form/Type/`): `CouponType`（本体フォーム）, `CouponDetailType`（対象明細コレクション）, `CouponSearchCategoryType`（カテゴリ検索）, `CouponUseType`（フロントのクーポン入力）。
- **Service** (`Service/CouponService.php`): 値引き額の再計算・クーポン受注情報の保存/削除・利用可否判定。
- **PurchaseFlow** (`Service/PurchaseFlow/Processor/`): `CouponProcessor`（`#[ShoppingFlow]`、クーポン明細の追加/検証）, `CouponStateProcessor`（`#[OrderFlow]`、受注ステータス変更時のクーポン利用枚数の増減）。
- **Event** (`Event.php`): 購入画面・確認画面・マイページ・管理受注編集画面にクーポン情報スニペットを差し込む。
- **PluginManager** (`PluginManager.php`): 有効化時にクーポン利用ページ（`plugin_coupon_shopping`）の Page/PageLayout を生成、テンプレートブロックをコピー。

## 規約・移行メモ

### 開発ツール設定ファイルは `Resource/` 配下に置く（rector.php / .php-cs-fixer.dist.php）

`rector.php` や `.php-cs-fixer.dist.php` を**プラグインのルート直下に置いてはならない**。`Resource/` 配下に置く。

**理由**: EC-CUBE 本体の `config/eccube/services.yaml` がプラグインを丸ごと PSR-4 サービス検出対象として読み込む:

```yaml
Plugin\:
    resource: '../../../app/Plugin/*'
    exclude: '../../../app/Plugin/*/{Entity,Resource,ServiceProvider,Tests,Codeception,DoctrineMigrations}'
```

ルート直下の `*.php` は「サービスクラス」として読み込まれるため、`rector.php` を置くと Symfony が `Plugin\Coupon44\rector` クラスを期待し、見つからず **EC-CUBE 全体が 500 エラー**になる。`exclude` に `Resource` が含まれるため `Resource/` 配下なら衝突しない。`phpstan.neon.dist` は `.php` ではないためルートに置ける。

**将来「本体に合わせてルートへ戻す」とリグレッションするため、この配置を変更しないこと。**

### docker 環境は `APP_ENV=dev` で起動する

ブラウザログインには実セッション（`session.storage.factory.native`）が必要。`APP_ENV=test` ではモックストレージ（`mock_file`）になりログインできない。また EC-CUBE 4.4（Symfony 7）は既定 `cookie_samesite: none` のため、HTTP 環境では `dockerbuild/dev-framework.yaml`（`cookie_secure:false` / `cookie_samesite:lax`）を `app/config/eccube/packages/dev/framework.yaml` に重ねて回避している。

### プラグイン有効化後は `cache:clear` を 2 回実行する（TemplateEvent 対策）

本プラグインは `Event.php` が `TemplateEvent` で core テンプレート（購入画面・マイページ・受注編集）にスニペットを注入する。これらプラグイン由来のフックは、**`eccube:plugin:enable` 直後の 1 回の `cache:clear` では確定しない**ことがある。検証の結果、enable とは別パスで **`cache:clear` をもう一度実行**すると確定するため、`docker-compose.dev.yml` の entrypoint は有効化後に `bin/console cache:clear` を **2 回** 実行する。

### E2E の資産はプラグインの配布物に含めない

`e2e/` `playwright.config.ts` `package.json` `package-lock.json` は開発用で、プラグインの配布物では
ないため、`docker-compose.dev.yml` と `.github/workflows/ci.yml` の tar から `--exclude` する。

### プラグインの導入方法（tar + plugin:install）

`docker-compose.dev.yml` はマウントしたプラグインを `./*` で tar 化し `eccube:plugin:install --path` で導入する。`eccube:composer:require` はパッケージ API（`extra.id`）を要求するため path プラグインでは使えない。また **`PharData` は先頭の `./` エントリで展開に失敗する**ため、プラグインディレクトリ内で `./*` を対象に tar 化する（`-C dir .` は不可）。
