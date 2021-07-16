[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](http://www.gnu.org/licenses/gpl-3.0)

# Simple Auto Login for Webtrees
This module provides a simple way to add a SSO auto login for webtrees in combination with a authentication proxy  (like [oauth2-proxy](https://github.com/oauth2-proxy/oauth2-proxy)).

## oauth2-proxy

In my installation, I have [Caddy](https://caddyserver.com/) as a first line reverse proxy. Behind this is a authentication proxy ([oauth2-proxy](https://github.com/oauth2-proxy/oauth2-proxy)) for the oauth authentication with [keycloak](https://www.keycloak.org/).

```
caddy -> oauth2-proxy -> webtrees
             |
             v
          Keycloak
```

### caddy configuration
```yaml
webtrees.example.com {
  reverse_proxy <oauth-proxy: https://x.x.x.x:port> {
    transport http {
      tls_insecure_skip_verify
    }
  }
}
```

### oauth2-proxy configuration
I am running oauth2-proxy as container (podman).
```bash
podman create --name "oauthproxy_core" --pod "oauthproxy" \
              -v "/etc/localtime:/etc/localtime:ro" \
              quay.io/oauth2-proxy/oauth2-proxy \
              --provider=oidc \
              --provider-display-name="Keycloak" \
              --client-id="app_webtrees" \
              --client-secret="<client-secret>" \
              --email-domain=* \
              --oidc-issuer-url="http(s)://<keycloak host>/auth/realms/<realm>" \
              --login-url="http(s)://<keycloak host>/auth/realms/<realm>/protocol/openid-connect/auth" \
              --redeem-url="http(s)://<keycloak host>/auth/realms/<realm>/protocol/openid-connect/token" \
              --validate-url="http(s)://<keycloak host>/auth/realms/<realm>/protocol/openid-connect/userinfo" \
              --allowed-group="<allowed_user_group>" \
              --whitelist-domain="<.example.com>" \
              --cookie-domain="<webtrees.example.com>" \
              --cookie-secure=true \
              --cookie-secret="${COOKIE_SECRET}" \
              --scope="openid profile email roles" \
              --http-address="127.0.0.1:4180" \
              --upstream="<webtrees url>" \
              --ssl-upstream-insecure-skip-verify="true" \
              --reverse-proxy="true" \
              --insecure-oidc-allow-unverified-email=true \
              --skip-provider-button=true

```
More information can be find [here](https://oauth2-proxy.github.io/oauth2-proxy/docs/configuration/oauth_provider#keycloak-auth-provider).

### Keycloak configuration


## Installation
Requires webtrees 2.0.

### Using Git
If you are using ``git``, you could also clone the current main branch directly into your ``modules_v4`` directory 
by calling:

```
git clone https://github.com/fanningert/webtrees_simpleautologin.git modules_v4/webtrees_simpleautologin
```

### Manual installation
To manually install the module, perform the following steps:

1. Download the [latest release](https://github.com/fanningert/webtrees_simpleautologin/releases/latest).
1. Upload the downloaded file to your web server.
1. Unzip the package into your ``modules_v4`` directory.
1. Rename the folder to ``webtrees_simpleautologin``

## Enable
1. Visit the Control Panel
1. Click "All modules"
1. Scroll to "Simple Auto Login"
1. Clear the checkbox for this module.
1. Scroll to the bottom.
1. Click the "save" button.
1. Add ``trusted_header_authenticated_user`` to the ``config.ini.php`` of webtrees
  * oauth2-proxy: HTTP_X_FORWARDED_PREFERRED_USERNAME
  * Apache mod_ssl: SSL_CLIENT_S_DN_CN
  * general: REMOTE_USER
  Example: trusted_header_authenticated_user="REMOTE_USER";

## Disable
1. Visit the Control Panel
1. Click "All modules"
1. Scroll to "Simple Auto Login"
1. Clear the checkbox for this module.
1. Scroll to the bottom.
1. Click the "save" button.

Alternatively, you can unload the module by renaming ``modules_v4/webtrees_simpleautologin/`` to ``modules_v4/webtrees_simpleautologin.disable/``

## Uninstall
It is safe to delete the ``webtrees_simpleautologin`` directory at any time.
