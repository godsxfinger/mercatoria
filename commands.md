DEV ONLY WARNING
- This file contains local development commands only.
- Never reuse these credentials or command values in staging/production.
- Rotate RPC credentials before any non-dev deployment.
- Keep RPC bound to localhost only (`127.0.0.1`).

Start the server: `php artisan serve`

Run the wallet:
```bash
/home/blackbox/monero-rpc/monero-x86_64-linux-gnu-v0.18.4.5/monero-wallet-rpc \
--rpc-bind-ip 127.0.0.1 \
--rpc-bind-port 18083 \
--daemon-address xmr.surveillance.monster:443 \
--wallet-file mercatoria-wallet-rpc \
--password-file /tmp/mercatoria-wallet-rpc.pass \
--trusted-daemon \
--daemon-ssl enabled \
--daemon-ssl-allow-any-cert \
--rpc-login mercatoriarpc:change-this-now \
--log-file /home/user/monero-rpc/monero-x86_64-linux/logs/monero-wallet-rpc.log \
--log-level 1
```
