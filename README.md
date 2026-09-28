# ssp — php:8.5-apache / mysql の 2 コンテナ構成

元は `Dockerfile.origin`（ubuntu + supervisor で nginx・php-fpm・mysqld・sshd を1コンテナに同居）
だったものを docker compose で分離した。web サーバと PHP は **公式 `php:8.5-apache`（mod_php）
の 1 コンテナ**にまとめ、DB だけ分けている。sshd はホスト側の bind mount でファイルを直接
編集できるため廃止。

```
compose.yaml
.env.example               設定値の雛形（cp して .env を作る）
Dockerfile.origin          元の単一コンテナ版（参考用・未使用）
web/  Dockerfile           php:8.5-apache + mysqli/pdo_mysql + mod_userdir
      apache/zz-ssp.conf   UserDir 設定（/~user/ を homes/ に向ける）
      php/zz-ssp.ini       display_errors=On など
db/   Dockerfile           mysql:8.4 + 設定 + 初期化 SQL
      conf.d/ssp.cnf       skip-name-resolve
      init/                初回起動時に実行される SQL（db/README.md 参照）
html/                      ドキュメントルート        -> /var/www/html
homes/                     ユーザーディレクトリ      -> /home
```

## 使い方

```sh
cp .env.example .env             # 初回だけ
docker compose up -d --build     # 起動（初回はビルド）
docker compose logs -f web       # ログ（apache のアクセス/エラーログ）
docker compose exec web bash     # web コンテナに入る
docker compose exec db mysql -uroot -pdbpass sspdb
docker compose down              # 停止（DB データは volume に残る）
docker compose down -v           # 停止 + DB データ削除
```

- <http://127.0.0.1:10800/> … `./html`
- <http://127.0.0.1:10800/~sspuser/> … `./homes/sspuser/public_html`（autoindex 有効）

MySQL はホストに公開していない（元の `EXPOSE 22 80`（3306 なし）と同じ方針）。

## 設定値

変更できる値はすべて `.env` にある（`.env.example` が雛形。`.env` は git 管理外）。
compose や Dockerfile 側に既定値は置いていないので、`.env` が唯一の定義場所。

| 変数 | 既定 | 効く先 |
| --- | --- | --- |
| `HTTP_BIND` / `HTTP_PORT` | `127.0.0.1` / `10800` | compose の `ports` |
| `SERVER_NAME` | `localhost` | apache の `ServerName`（`web/apache/zz-ssp.conf`） |
| `TZ` | `Asia/Tokyo` | 両コンテナの `/etc/localtime` と php の `date.timezone`（`web/php/zz-ssp.ini`） |
| `PHP_IMAGE` / `MYSQL_IMAGE` | `php:8.5-apache` / `mysql:8.4` | 各 Dockerfile の `FROM`（build arg） |
| `MYSQL_ROOT_PASSWORD` / `MYSQL_DATABASE` | `dbpass` / `sspdb` | mysql の初期化、web の `DB_PASSWORD` / `DB_NAME` |
| `DB_HOST` / `DB_USER` | `db` / `root` | web の環境変数（`html/index.php` が `getenv` で読む） |

`.ini` と apache conf の `${TZ}` / `${SERVER_NAME}` は、どちらも読み込み時に
プロセスの環境変数で展開される（コンテナを作り直さなくても `.env` を変えて
`docker compose up -d` すれば反映される）。

ユーザーを増やすときは **`homes/<name>/public_html` を作るだけ**でよい
（コンテナの再ビルドも compose の編集も不要。すぐ `/~<name>/` で見える）。

## 元の単一コンテナ版との対応

| 元 (1コンテナ) | 分離後 |
| --- | --- |
| supervisor の `[program:nginx]` + `[program:php-fpm]` | `web` サービス (php:8.5-apache / mod_php) |
| supervisor の `[program:mysqld]` | `db` サービス (mysql:8.4) + `db-data` volume |
| supervisor の `[program:sshd]`、sspuser/ssppass | 廃止（ホストのファイルを直接編集） |
| nginx の site 設定 | 不要（公式イメージの `docker-php.conf` が DocumentRoot と `.php` ハンドラを設定済み） |
| `fastcgi_pass unix:/run/php/php8.5-fpm.sock` | 不要（mod_php なのでプロセス間通信そのものが無い） |
| php.ini の `display_errors = On` | `web/php/zz-ssp.ini` |
| `/home/sspuser/public_html` を mkdir | `./homes:/home` の bind mount + `mod_userdir` |
| `my.cnf` へ `skip-name-resolve` 追記 | `db/conf.d/ssp.cnf` |
| ビルド中に mysqld を起動して root@'%' を作成 | `MYSQL_ROOT_PASSWORD`（公式イメージが初回起動時に作成） |

## 実装メモ

- **なぜ nginx + php-fpm に分けないか**: 分けると nginx と php-fpm の両コンテナに
  *同じディレクトリを同じパスで* mount する必要がある（php-fpm が
  `SCRIPT_FILENAME` を開けないと "No input file specified" になる）。ローカル1台の
  演習環境では nginx/php を別々にスケールする利点が無く、この二重管理だけが残る。
  mod_php なら mount 先は1箇所で済む。
- **`UserDir /home/*/public_html` のワイルドカード形式を使っている理由**:
  既定の `UserDir public_html` はホームディレクトリを `/etc/passwd` から引くため、
  コンテナ内に該当ユーザーのアカウントが必要になる。ワイルドカード形式は
  パスワードデータベースを見ないので、`homes/` にディレクトリを作るだけで
  ユーザーを追加できる。
- `conf-enabled/` は `mods-enabled/` より後に読まれるので、`zz-ssp.conf` で
  `mods-available/userdir.conf` の `UserDir` を上書きできる。
- `display_errors = On` など開発向け設定のため、本番利用時は
  `web/php/zz-ssp.ini` と DB パスワードを見直すこと。
