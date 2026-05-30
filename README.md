# middle_laravel

Backend API viết bằng Laravel 12, tổ chức theo kiểu module, dùng JWT cho xác thực API, hỗ trợ đa ngôn ngữ `vi`/`en`, và có sẵn lệnh export Postman collection từ route hiện tại.

## Tính năng chính

- Laravel 12, PHP 8.2+
- Cấu trúc module trong `app/Http/Modules/*`
- JWT auth với các endpoint đăng ký, đăng nhập, đăng xuất, lấy thông tin user, refresh token
- Tự load route API từ từng module
- Middleware locale dùng `?locale=`, `X-Locale`, `X-Lang`
- Chuẩn response JSON dùng chung cho controller và exception
- Export Postman collection/environment bằng Artisan command
- Có sẵn seed tài khoản admin local

## Yêu cầu môi trường

- PHP `^8.2`
- Composer
- Node.js + npm
- Một trong các database sau:
  - MySQL/MariaDB
  - SQLite

## Cấu trúc thư mục chính

```text
app/
  Console/Commands/          # Artisan command custom
  Core/                      # Helper, trait, base class dùng chung
  Http/
    Middleware/
    Modules/
      Auth/                  # Module auth đang hoạt động
      Organization/          # Module scaffold mẫu, chưa có logic
bootstrap/app.php            # Tự nạp route API từ các module
config/
  auth.php                   # Guard `api` dùng JWT
  jwt.php                    # Cấu hình JWT
  modules.php                # Đường dẫn module và stub
database/
  migrations/
  seeders/
postman/                     # Collection và environment sinh tự động
stubs/modules/               # Stub cho các lệnh tạo module
```

## Cài đặt project

### Cách khuyến nghị

1. Cài dependency backend và frontend:

```bash
composer install
npm install
```

2. Tạo file môi trường từ `.env.example`.

3. Cấu hình database trong `.env`.

Ví dụ với MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=middle_laravel
DB_USERNAME=root
DB_PASSWORD=
```

Ví dụ với SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Project đã có sẵn file `database/database.sqlite`, nên local quick start bằng SQLite là cách gọn nhất nếu bạn chưa muốn dựng MySQL.

4. Sinh app key và JWT secret:

```bash
php artisan key:generate
php artisan jwt:secret
```

5. Chạy migration và seed dữ liệu mẫu:

```bash
php artisan migrate --seed
```

6. Chạy project:

```bash
composer dev
```

### Dùng `composer setup`

Project có sẵn script:

```bash
composer setup
```

Script này sẽ:

- `composer install`
- tạo `.env` nếu chưa có
- `php artisan key:generate`
- `php artisan migrate --force`
- `npm install`
- `npm run build`

Sau đó bạn vẫn cần chạy thêm:

```bash
php artisan jwt:secret
php artisan db:seed
composer dev
```

Lý do là `composer setup` hiện chưa sinh `JWT_SECRET` và chưa seed user mẫu.

## Chạy local

`composer dev` sẽ chạy đồng thời:

- `php artisan serve`
- `php artisan queue:listen --tries=1 --timeout=0`
- `php artisan pail --timeout=0`
- `npm run dev`

URL local mặc định:

- Web: `http://127.0.0.1:8000`
- API prefix mặc định: `http://127.0.0.1:8000/api/v1`

`APP_API_PREFIX` đang được đọc từ `.env`, nên nếu bạn đổi prefix thì toàn bộ route API cũng đổi theo.

## Tài khoản seed mặc định

Khi chạy:

```bash
php artisan db:seed
```

project sẽ tạo hoặc cập nhật tài khoản:

- Email: `admin@example.com`
- Username: `admin`
- Password: `password`
- Role: `admin`

## Các biến môi trường đáng chú ý

```env
APP_API_PREFIX=api/v1
APP_LOCALE=vi
APP_FALLBACK_LOCALE=en
APP_SUPPORTED_LOCALES=en,vi

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

JWT_SECRET=
JWT_TTL=
JWT_REFRESH_TTL=
```

Lưu ý:

- `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE` đang để `database`, nên cần chạy migration trước khi chạy app.
- `JWT_SECRET` bắt buộc phải có nếu muốn login bằng guard `api`.

## API hiện có

Với `APP_API_PREFIX=api/v1`, các endpoint hiện tại là:

| Method | Endpoint | Auth | Mô tả |
| --- | --- | --- | --- |
| POST | `/api/v1/auth/register` | Không | Đăng ký tài khoản và trả JWT |
| POST | `/api/v1/auth/login` | Không | Đăng nhập bằng `username` hoặc `email` |
| POST | `/api/v1/auth/logout` | Bearer token | Đăng xuất |
| GET | `/api/v1/auth/me` | Bearer token | Lấy user hiện tại |
| GET | `/api/v1/auth/refresh` | Bearer token | Refresh token |
| GET | `/api/v1/user` | Sanctum | Route mẫu mặc định của Laravel |

`/api/v1/user` hiện dùng `auth:sanctum`, không cùng flow với JWT auth ở module `Auth`. Đây là route mẫu còn lại từ skeleton Laravel.

## Ví dụ gọi API

### 1. Đăng ký

```bash
curl --request POST http://127.0.0.1:8000/api/v1/auth/register \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data "{
    \"username\": \"demo_user\",
    \"email\": \"user@example.com\",
    \"password\": \"secret123\",
    \"password_confirmation\": \"secret123\"
  }"
```

### 2. Đăng nhập

```bash
curl --request POST http://127.0.0.1:8000/api/v1/auth/login \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data "{
    \"username\": \"admin@example.com\",
    \"password\": \"password\"
  }"
```

### 3. Lấy thông tin user hiện tại

```bash
curl http://127.0.0.1:8000/api/v1/auth/me \
  --header "Accept: application/json" \
  --header "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Quy ước response

### Success response

Controller đang dùng trait `ApiResponse`, nên response thành công có dạng:

```json
{
  "message": "Thành công.",
  "status_code": 200,
  "metadata": {},
  "path": "/api/v1/auth/me",
  "timestamp": "2026-05-30 10:00:00"
}
```

### Error response

Exception ở tầng global đang được format JSON trong `bootstrap/app.php`:

```json
{
  "message": "Dữ liệu gửi lên không hợp lệ.",
  "statusCode": 422,
  "metadata": {
    "username": [
      "Trường username là bắt buộc."
    ]
  },
  "path": "/api/v1/auth/login",
  "timestamp": "2026-05-30T10:00:00.000000Z"
}
```

Lưu ý hiện tại key status ở success là `status_code`, còn ở exception là `statusCode`.

## Đa ngôn ngữ

Locale được resolve theo thứ tự:

1. Query string `?locale=vi`
2. Header `X-Locale`
3. Header `X-Lang`
4. `APP_LOCALE`
5. `APP_FALLBACK_LOCALE`

Các locale hỗ trợ hiện tại:

- `vi`
- `en`

Response sẽ trả thêm header `Content-Language`.

## Postman

Collection và environment đang nằm tại:

- `postman/collections/laravel-api.postman_collection.json`
- `postman/environments/local.postman_environment.json`

Để sinh lại từ route hiện tại:

```bash
php artisan postman:export
```

Hoặc:

```bash
composer postman
```

Sau khi import vào Postman:

1. Chạy `login` hoặc `register`
2. Collection sẽ tự lấy `metadata.access_token` từ response và cập nhật vào biến môi trường `jwt_token`
3. Gọi các API cần Bearer auth

Ngoài ra:

- `refresh` cũng sẽ tự ghi đè `jwt_token` bằng token mới
- `logout` sẽ tự set `jwt_token` về rỗng

Mỗi khi thay đổi route hoặc request payload, nên export lại collection để tránh lệch tài liệu.

## Làm việc theo module

Project tự nạp tất cả file:

```text
app/Http/Modules/*/Routes/api.php
```

ngay trong `bootstrap/app.php`, với prefix lấy từ `APP_API_PREFIX`.

### Tạo module mới

```bash
php artisan make:module Blog
```

Lệnh này tạo các thư mục:

- `Controllers`
- `DTOs`
- `Interfaces`
- `Middlewares`
- `Models`
- `Repositories`
- `Requests`
- `Routes`
- `Services`

### Tạo file trong module

```bash
php artisan module:controller Post Blog
php artisan module:service Post Blog
php artisan module:repo Post Blog
php artisan module:dto CreatePostData Blog
```

Lưu ý:

- Các lệnh trên chỉ scaffold file.
- Nếu dùng interface/service/repository, bạn vẫn phải tự bind vào container, ví dụ trong `AppServiceProvider`.
- Route của module sẽ chỉ có hiệu lực khi bạn khai báo trong `Routes/api.php` của module đó.

## Command custom hiện có

```bash
php artisan make:module {ModuleName}
php artisan module:controller {ControllerName} {ModuleName}
php artisan module:service {ServiceName} {ModuleName}
php artisan module:repo {RepositoryName} {ModuleName}
php artisan module:dto {DtoName} {ModuleName}
php artisan postman:export
```

## Test và kiểm tra nhanh

Chạy test:

```bash
composer test
```

Repo hiện đã có test cho:

- auth route
- module generator command
- Postman export command
- example test mặc định của Laravel

Các file test chính đang nằm trong `tests/Feature/AuthRoutesTest.php`, `tests/Feature/ModuleCommandsTest.php` và `tests/Feature/PostmanExportCommandTest.php`.

Để kiểm tra route API:

```bash
php artisan route:list --path=api
```

## Một số lỗi thường gặp

### `JWT_SECRET` chưa được cấu hình

Triệu chứng:

- Login/register không hoạt động đúng
- Guard `api` dùng JWT nhưng không ký được token

Cách xử lý:

```bash
php artisan jwt:secret
```

### Lỗi bảng `cache`, `jobs`, `sessions`

Triệu chứng:

- Chạy app báo thiếu bảng liên quan tới cache, queue hoặc session

Cách xử lý:

```bash
php artisan migrate
```

hoặc đổi các driver này sang `file` trong `.env` nếu không muốn dùng database cho local.

### Đổi API prefix nhưng Postman vẫn gọi URL cũ

Cách xử lý:

```bash
php artisan postman:export
```

và import lại collection/environment.
