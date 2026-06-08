## Connecting Monero Wallet RPC to Mercatoria Marketplace

This guide will walk you through connecting a Monero wallet RPC to your Mercatoria marketplace installation.

## Prerequisites
Before we begin, ensure you're in your Downloads folder where we'll store the Monero CLI tools:
```bash
cd Downloads
```

## Downloading and Extracting Monero CLI
First, let's download the Monero CLI package:
```bash
wget https://downloads.getmonero.org/cli/linux64
```

Extract the downloaded package:
```bash
bunzip2 linux64
tar xvf linux64.out
```

Navigate to the extracted directory (your version number might differ):
```bash
cd monero-x86_64-linux-gnu-v0.18.3.4
```

## Creating Your Marketplace Wallet
Now we'll create a dedicated wallet for your marketplace:
```bash
./monero-wallet-cli
```

When prompted:
1. Enter "mercatoria-wallet" as your wallet name
2. Type "Y" to confirm
3. Password is optional - press Enter to skip
4. Select language by typing "1" for English
5. **Important**: Securely store the 25-word seed phrase that appears
6. Choose whether to enable background mining (type "Yes" or "No")

To exit the wallet CLI, press CTRL+C.

## Connecting to a Monero Node
You have two options for connecting to the Monero network:
- Set up your own node (better privacy but requires more time and storage)
- Connect to a remote node (faster setup but less private)

For this guide, we'll use a remote node. You can find trusted remote nodes at [xmr.ditatompel.com/remote-nodes/](https://xmr.ditatompel.com/remote-nodes/).

First, let's get your wallet's exact location:
```bash
realpath mercatoria-wallet
```

## Starting the Wallet RPC Server
Use the following command to start the RPC server (replace paths and credentials with your own values):
```bash
./monero-wallet-rpc \
--rpc-bind-ip 127.0.0.1 \
--rpc-bind-port 18083 \
--daemon-address xmr.surveillance.monster:443 \
--wallet-file /home/blackbox/monero-rpc/monero-x86_64-linux-gnu-v0.18.4.5/mercatoria-wallet-rpc-20260227 \
--password-file /tmp/mercatoria-wallet-rpc-20260227.pass \
--trusted-daemon \
--daemon-ssl enabled \
--daemon-ssl-allow-any-cert \
--rpc-login mercatoriarpc:change-this-now \
--log-file /home/blackbox/monero-rpc/monero-x86_64-linux-gnu-v0.18.4.5/logs/monero-wallet-rpc.log \
--log-level 1
```

Create the password file once (recommended) and lock its permissions:
```bash
printf '%s\n' 'your_wallet_password_here' > /tmp/mercatoria-wallet-rpc-20260227.pass
chmod 600 /tmp/mercatoria-wallet-rpc-20260227.pass
```

In your Mercatoria `.env`, use:
```env
MONERO_RPC_HOST=127.0.0.1
MONERO_RPC_PORT=18083
MONERO_RPC_SSL=false
MONERO_RPC_USERNAME=mercatoriarpc
MONERO_RPC_PASSWORD=change-this-now
```

For production, replace `--daemon-ssl-allow-any-cert` with certificate pinning or a CA bundle (`--daemon-ssl-allowed-fingerprints` or `--daemon-ssl-ca-certificates`).

## Optional: Using Screen Sessions
If you're using a CLI-based system, you can run the wallet RPC in a screen session:

Create a new screen session:
```bash
screen -S mercatoria_session
```

Run the wallet RPC command in this session, then detach by pressing CTRL+A+D.

To reattach to the session later:
```bash
screen -r mercatoria_session
```

## Important Notes
- Never share your wallet's seed phrase with anyone
- Never share your RPC credentials or wallet password file
- Always properly close the wallet RPC using CTRL+C when needed
- Consider using Tor or I2P networks for enhanced privacy when connecting to remote nodes

Your Monero wallet RPC should now be properly configured and ready to use with your Mercatoria marketplace installation.
