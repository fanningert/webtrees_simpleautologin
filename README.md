[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](http://www.gnu.org/licenses/gpl-3.0)

# Simple Auto Login for Webtrees
This module provides a simple way to add a SSO auto login for webtrees in combination with a authentication proxy  (like [oauth2-proxy](https://github.com/oauth2-proxy/oauth2-proxy)).

## oauth2-proxy

In my installation I have Caddy a first line reverse proxy. 

```
CADDY -> oauth2-proxy -> webtrees
             |
             v
           Keycloak
```

### CADDY configuration
```yaml
webtrees.example.com {
  import webconf
  import hideserver
  import securityheader

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
2. Upload the downloaded file to your web server.
3. Unzip the package into your ``modules_v4`` directory.
4. Rename the folder to ``webtrees_simpleautologin``

## Enable module
After installation, the module is allways on.
