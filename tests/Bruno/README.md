# Bruno Tests - Classement API

## Environment Configuration

This project uses environment variables to easily manage authentication tokens and URLs.

### Available Environments

- **Local.bru**: For local testing (localhost:8000)
- **Production.bru**: For production testing (to be configured, at your own risk)

### Available Variables

| Variable            | Description                                     | Example                           |
| ------------------- | ----------------------------------------------- | --------------------------------- |
| `{{base_url}}`      | API base URL                                    | `http://localhost:8000`           |
| `{{auth_token}}`    | Authentication token                            | `ikhrl7xp6g0k8s8kssswco0wk0csgsg` |

### Usage

1. Select the desired environment in Bruno (Local or Production)
2. Variables will be automatically injected into your requests
3. To modify a token, simply edit the corresponding environment file

### Auto-update Token

The `user/localhost-8000-api-login.bru` file automatically updates the `auth_token` variable after a successful login. You no need to manually copy-paste the token!

**How it works:**

1. Execute the login request
2. If login succeeds, the token is automatically extracted from the response
3. The `{{auth_token}}` variable is updated in the active environment
4. All subsequent requests will automatically use the new token

### Example Usage in a Request

```
headers {
  X-AUTH-TOKEN: {{auth_token}}
}

get {
  url: {{base_url}}/api/admin/users
}
```

### Adding a New Environment

Create a new `.bru` file in the `environments/` folder:

```
vars {
  base_url: https://staging.example.com
  auth_token: YOUR_STAGING_AUTH_TOKEN
}
```
