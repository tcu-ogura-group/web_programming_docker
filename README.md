# ssp — nginx / php-fpm / mysql の 3 コンテナ構成

元は `Dockerfile.origin`（ubuntu + supervisor で nginx・php-fpm・mysqld・sshd を1コンテナに同居）
だったものを docker compose で分離した。sshd はホスト側の bind mount でファイルを直接
編集できるため廃止。

```
compose.yaml
.env.example               設定値の雛形（cp して .env を作る）
Dockerfile.origin          元の単一コンテナ版（参考用・未使用）
web/  default.conf         nginx の site 設定（元の perl 置換を展開したもの）
      fastcgi-php.conf     Debian の snippets/fastcgi-php.conf 相当
php/  Dockerfile           php:8.5-fpm + mysqli/pdo_mysql
      conf.d/zz-ssp.ini    display_errors=On など
db/   Dockerfile           mysql:8.4 + 設定 + 初期化 SQL
      conf.d/ssp.cnf       skip-name-resolve
      init/                初回起動時に実行される SQL（db/README.md 参照）
html/                      ドキュメントルート        -> /var/www/html
homes/sspuser/public_html/ ユーザーディレクトリ      -> /home/sspuser/public_html
```

## 使い方

```sh
cp .env.example .env             # 初回だけ
docker compose up -d --build     # 起動（初回はビルド）
docker compose logs -f web php   # ログ
docker compose exec php bash     # php コンテナに入る
docker compose exec db mysql -uroot -pdbpass sspdb
docker compose down              # 停止（DB データは volume に残る）
docker compose down -v           # 停止 + DB データ削除
```

- <http://127.0.0.1:10800/> … `./html`
- <http://127.0.0.1:10800/~sspuser/> … `./homes/sspuser/public_html`（autoindex 有効）

MySQL はホストに公開していない（元の `EXPOSE 22 80`（3306 なし）と同じ方針）。
必要なら `compose.yaml` の `db.ports` のコメントを外す。

## 設定値

変更できる値はすべて `.env` にある（`.env.example` が雛形。`.env` は git 管理外）。
compose や Dockerfile 側に既定値は置いていないので、`.env` が唯一の定義場所。

| 変数 | 既定 | 効く先 |
| --- | --- | --- |
| `HTTP_BIND` / `HTTP_PORT` | `127.0.0.1` / `10800` | compose の `ports` |
| `TZ` | `Asia/Tokyo` | 各コンテナの `/etc/localtime` と php の `date.timezone`（`php/conf.d/zz-ssp.ini`） |
| `NGINX_IMAGE` | `nginx:1.29-alpine` | web の `image` |
| `PHP_IMAGE` / `MYSQL_IMAGE` | `php:8.5-fpm` / `mysql:8.4` | php / db の Dockerfile の `FROM`（build arg） |
| `MYSQL_ROOT_PASSWORD` / `MYSQL_DATABASE` | `dbpass` / `sspdb` | mysql の初期化、php の `DB_PASSWORD` / `DB_NAME` |
| `DB_HOST` / `DB_USER` | `db` / `root` | php の環境変数（`html/index.php` が `getenv` で読む） |

`php/conf.d/zz-ssp.ini` の `${TZ}` は php 起動時にプロセスの環境変数で展開される。
nginx の `default.conf` は環境変数を展開しないので、`server_name` は `_` 固定。

## 元の単一コンテナ版との対応

| 元 (1コンテナ) | 分離後 |
| --- | --- |
| supervisor の `[program:nginx]` | `web` サービス (nginx:1.29-alpine) |
| supervisor の `[program:php-fpm]` | `php` サービス (php:8.5-fpm) |
| supervisor の `[program:mysqld]` | `db` サービス (mysql:8.4) + `db-data` volume |
| supervisor の `[program:sshd]`、sspuser/ssppass | 廃止（ホストのファイルを直接編集） |
| `fastcgi_pass unix:/run/php/php8.5-fpm.sock` | `fastcgi_pass php:9000`（コンテナ間 TCP） |
| php.ini の `display_errors = On` | `php/conf.d/zz-ssp.ini` |
| `my.cnf` へ `skip-name-resolve` 追記 | `db/conf.d/ssp.cnf` |
| ビルド中に mysqld を起動して root@'%' を作成 | `MYSQL_ROOT_PASSWORD`（公式イメージが初回起動時に作成） |
| `/home/sspuser/public_html`（コンテナ内） | `./homes/sspuser/public_html`（ホスト bind mount） |

## 注意点

- `web` と `php` は **同じパス**（`/var/www/html`, `/home/sspuser/public_html`）に
  同じディレクトリを mount する必要がある。nginx が渡す `SCRIPT_FILENAME` を
  php-fpm 側が同じパスで開けないと "No input file specified" になる。
- ユーザーを増やす場合は `homes/<name>/public_html` を作り、`web`/`php` 両方の
  volumes に追記する（`./homes:/home` にまとめて mount してもよい）。
- `display_errors = On` など開発向け設定のため、本番利用時は
  `php/conf.d/zz-ssp.ini` と DB パスワードを見直すこと。
