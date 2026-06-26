<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect Bank Account</title>
    <script src="https://cdn.plaid.com/link/v2/stable/link-initialize.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; }
        .btn { padding: 12px 24px; font-size: 16px; background: #0055ff; color: white; border: none; border-radius: 6px; cursor: pointer; }
        .btn:hover { background: #0033cc; }
        .status { margin-top: 1rem; padding: 1rem; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<h1>Connect Your Bank Account</h1>
<button class="btn" id="link-btn">Connect with Plaid</button>
<div id="status"></div>

<script>
    const statusDiv = document.getElementById('status');
    const BASE_URL = '<?= base_url('index.php/plaid') ?>';  // Full URL with index.php

    document.getElementById('link-btn').addEventListener('click', async () => {
        try {
            statusDiv.innerHTML = 'Creating link token...';
            statusDiv.className = '';

            const tokenRes = await fetch(BASE_URL + '/create-link-token', {
                method: 'POST',
                credentials: 'include',  // Important for session
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!tokenRes.ok) throw new Error(`Token request failed: ${tokenRes.status}`);

            const tokenData = await tokenRes.json();
            if (!tokenData.link_token) throw new Error('No link_token returned');

            console.log('Link token:', tokenData.link_token);

            const handler = Plaid.create({
                token: tokenData.link_token,
                onSuccess: async (public_token, metadata) => {
                    console.log('onSuccess → public_token:', public_token);  // Should be a long string

                    statusDiv.innerHTML = 'Exchanging token with server...';

                    const exchangeRes = await fetch(BASE_URL + '/exchange-token', {
                        method: 'POST',
                        credentials: 'include',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ public_token })  // ← This sends correct JSON
                    });

                    const result = await exchangeRes.json();

                    if (result.success) {
                        statusDiv.className = 'status success';
                        statusDiv.innerHTML = `
                            <strong>Success!</strong><br>
                            Connected: ${metadata.institution?.name || 'Bank'}<br>
                            Accounts: ${metadata.accounts.length}<br>
                            <small>Item ID: ${result.item_id || 'saved'}</small><br>
                            Page reloading...
                        `;
                        setTimeout(() => location.reload(), 3000);
                    } else {
                        throw new Error(result.message || 'Exchange failed');
                    }
                },
                onExit: (err, metadata) => {
                    statusDiv.className = 'status error';
                    statusDiv.innerHTML = err
                        ? `Error: ${err.display_message || err.error_message || 'Unknown'}`
                        : 'Exited Plaid Link.';
                }
            });

            handler.open();

        } catch (err) {
            console.error('Plaid error:', err);
            statusDiv.className = 'status error';
            statusDiv.textContent = 'Failed: ' + err.message;
        }
    });
</script>
</body>
</html>