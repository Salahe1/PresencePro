# JWT keys (Lexik)

RSA key paths are set in `.env` as `JWT_SECRET_KEY` and `JWT_PUBLIC_KEY` (see `config/packages/lexik_jwt_authentication.yaml`).

Generate keys once per environment (from project root):

```bash
php bin/console lexik:jwt:generate-keypair --skip-if-exists
```

Use the same `JWT_PASSPHRASE` as in `.env` when the command asks for a passphrase, or set a new passphrase and update `.env` accordingly.

Do not commit `private.pem` or `public.pem`.
