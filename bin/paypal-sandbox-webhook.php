<?php

/**
 * Registers (or reuses) the PayPal webhook for the shop's current public URL
 * and stores its id in the module settings.
 *
 * The PayPal webhook endpoint is derived from the shop's SSL URL, so every
 * time the ngrok tunnel is restarted the previously registered webhook points
 * at a dead host. This script is run by bin/paypal-sandbox-tunnel.sh after the
 * shop is repointed at the new URL.
 *
 * Before refreshing, webhooks left over from a previous tunnel URL are removed;
 * then Webhook::ensureWebhook() reuses the current endpoint's webhook or
 * registers a fresh one for it.
 *
 * Prints the webhook id on success. Run inside the shop container:
 *   docker exec o3shop-shop-ce-shop-1 php /var/www/html/bin/paypal-sandbox-webhook.php
 */

require '/var/www/html/vendor/autoload.php';
require '/var/www/html/source/bootstrap.php';

use OxidSolutionCatalysts\PayPal\Core\Onboarding\Webhook;

try {
    $webhook = oxNew(Webhook::class);
    $endpoint = $webhook->getWebhookEndpoint();
    fwrite(STDERR, "Webhook endpoint: {$endpoint}\n");

    $allHooks = $webhook->getAllRegisteredWebhooks();

    // Drop osc_paypal webhooks left over from a previous tunnel URL so they do
    // not accumulate and cannot be picked up as the "current" one.
    foreach ($allHooks as $hook) {
        $url = $hook['url'] ?? '';
        $id = $hook['id'] ?? '';
        if ($id !== '' && $url !== $endpoint && strpos($url, 'oscpaypalwebhook') !== false) {
            fwrite(STDERR, "Removing stale webhook {$id} ({$url})\n");
            $webhook->removeWebhook($id);
        }
    }

    $newId = (string) $webhook->ensureWebhook();

    if ($newId === '') {
        fwrite(STDERR, "ERROR: PayPal did not return a webhook id.\n");
        exit(1);
    }

    $webhook->saveWebhookId($newId);
    echo $newId . "\n";
} catch (\Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
