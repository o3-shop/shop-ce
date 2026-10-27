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

    // Remove only this shop's previously stored webhook, and only when it points
    // at a different endpoint. Deleting every webhook whose URL contains
    // "oscpaypalwebhook" would also delete webhooks of other developers who
    // share the same PayPal sandbox app.
    $previousId = $webhook->getStoredWebhookId();
    if ($previousId !== '') {
        foreach ($allHooks as $hook) {
            if (($hook['id'] ?? '') !== $previousId) {
                continue;
            }
            $url = $hook['url'] ?? '';
            if ($url !== $endpoint) {
                fwrite(STDERR, "Removing previous webhook {$previousId} ({$url})\n");
                $webhook->removeWebhook($previousId);
            }
            break;
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
