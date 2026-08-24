# Third-Party APIs

Project-specific integration details only. General 36Blocks API knowledge is covered by the `36blocks-integration` skill — not duplicated here.

## 36Blocks authentication middleware

`App\Http\Middleware\Authenticate36Blocks` (`app/Http/Middleware/Authenticate36Blocks.php`):

- Reads the auth token from the `proxy_auth_token` request header. Rejects with `401` if it's missing.
- Calls 36Blocks `GET /c/getDetails` (base URL from `config('services.36blocks.base_url')`) with the token forwarded as the `proxy_auth_token` header.
- Rejects with `401` if the request to 36Blocks fails, or if the response has no `data.0` (i.e. no user).
- Validates that the returned user's `feature_configuration_id` matches `config('services.36blocks.feature_configuration_id')`. Rejects with `403` if it doesn't match.
- On success, sets the validated user on the request (see below) and passes the request along.

## Request attribute added by the middleware

The middleware sets:

```php
$request->attributes->set('user', $user);
```

`$user` is `data.0` from the 36Blocks `/c/getDetails` response — i.e. the raw 36Blocks user object, after the middleware's validation (token verified, `feature_configuration_id` matched). Downstream code (controllers, form requests, etc.) reads it with:

```php
$request->attributes->get('user');
```

### Shape of `user`

Based on a real `/c/getDetails` response (see `fixtures/third-party-api/36blocks-get-details.json`), the fields relevant to this application:

| Field | Type | Notes |
|---|---|---|
| `id` | int | 36Blocks user id |
| `name` | string | |
| `email` | string | |
| `mobile` | string\|null | |
| `client_id` | int | |
| `feature_configuration_id` | int | Already validated by the middleware against `config('services.36blocks.feature_configuration_id')`. |
| `is_block` | bool | |
| `is_password_verified` | int (0/1) | |
| `profile_picture_url` | string\|null | |
| `c_companies` | array | List of companies the user belongs to. |
| `currentCompany` | object | The user's active company, including `role` and `permissions`. |

For the full field list and nested structure of `c_companies` / `currentCompany`, see the fixture directly — do not hardcode assumptions beyond what's used in application code.
