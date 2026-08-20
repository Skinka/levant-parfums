# Ansible: production deployment

Плейбуки рассчитаны на чистый сервер Ubuntu 22.04, 24.04 или 26.04. Они устанавливают
Nginx, MySQL 8, PHP 8.3 (PHP 8.5 на Ubuntu 26.04) + расширения Laravel, Composer, Node.js 22, FFmpeg и
сервисы Laravel для очереди и планировщика.

`provision.yml` готовит сервер и базу данных. `deploy.yml` локально собирает
Vite-ресурсы, отправляет код и готовые assets на сервер через rsync, выполняет
миграции и включает виртуальный хост Nginx.

## Первоначальная настройка

1. Установите требуемую Ansible-коллекцию:

   ```bash
   ansible-galaxy collection install -r ansible/collections/requirements.yml
   ```

2. Создайте инвентарь и заполните адрес сервера:

   ```bash
   cp ansible/inventory/production.example.ini ansible/inventory/production.ini
   ```

3. Отредактируйте `ansible/inventory/group_vars/web/defaults.yml`: укажите
   `app_url`, доменные имена в `nginx_server_names`, почту и адрес для заявок
   `app_forms_admin_email`.

4. Создайте файл секретов и зашифруйте его. Значение `vault_app_key` получают
   один раз командой `php artisan key:generate --show` в локальной копии проекта.
   Для `vault_app_db_password` удобно использовать `openssl rand -hex 24`:
   такой пароль безопасно записывается в `.env`.

   ```bash
   cp ansible/inventory/group_vars/web/vault.yml.example ansible/inventory/group_vars/web/vault.yml
   ansible-vault encrypt ansible/inventory/group_vars/web/vault.yml
   ```

   Не меняйте `vault_app_key` после первого запуска: это сделает старые сессии и
   зашифрованные данные недоступными.

5. Запускайте `deploy.yml` на компьютере с рабочей копией проекта: он собирает
   frontend локально и использует SSH из inventory для rsync. На сервер не
   передаются `node_modules`, `vendor`, `.env`, `storage` и служебные папки.

## Запуск

```bash
ansible-playbook -i ansible/inventory/production.ini ansible/playbooks/provision.yml --ask-vault-pass
ansible-playbook -i ansible/inventory/production.ini ansible/playbooks/deploy.yml --ask-vault-pass
```

Для последующих обновлений достаточно второй команды. Плейбук не выполняет
`migrate:fresh`, поэтому существующие данные MySQL сохраняются.

Для HTTPS укажите в `defaults.yml` `nginx_tls_enabled: true` и канонический
домен в `nginx_tls_domain`. Перед первым HTTPS-деплоем выпустите сертификат
Let's Encrypt для всех имён из `nginx_server_names`, например:

```bash
sudo certbot --nginx -d levantparfums.com -d www.levantparfums.com
```

Шаблон Nginx использует сертификат из `/etc/letsencrypt/live/`, поэтому
последующие деплои сохранят HTTPS. HTTP перенаправляется на канонический домен.

## Временное закрытие сайта паролем

При `nginx_basic_auth_enabled: true` Nginx запрашивает HTTP Basic Auth для
сайта. Имя пользователя находится в `nginx_basic_auth_user`, а пароль — в
`vault_nginx_basic_auth_password` внутри зашифрованного `vault.yml`.

`nginx_disable_default_site` по умолчанию выключен, чтобы не затронуть другие
сайты на уже используемом сервере. На выделенном сервере его можно включить.
