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
 * Why not just call Webhook::ensureWebhook(): its lookup helper getHookForUrl()
 * has a bug - `foreach ($allClientHooks as $hook)` leaves $hook as the last
 * element, so when no webhook matches the current endpoint it returns that last
 * webhook instead of an empty array. ensureWebhook() then "reuses" a stale
 * webhook registered for a previous tunnel URL. This script looks the match up
 * itself, drops stale osc_paypal webhooks, and registers a fresh one only when
 * the current endpoint has none.
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

    // Reuse an existing webhook for this exact endpoint, if there is one.
    $existingId = '';
    foreach ($allHooks as $hook) {
        if (($hook['url'] ?? '') === $endpoint) {
            $existingId = $hook['id'] ?? '';
            break;
        }
    }

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

    if ($existingId !== '') {
        $webhook->saveWebhookId($existingId);
        echo $existingId . "\n";
        exit(0);
    }

    // registerWebhooks() is protected; call it directly so we always register
    // for the current endpoint instead of going through the buggy lookup.
    $register = new ReflectionMethod($webhook, 'registerWebhooks');
    $register->setAccessible(true);
    $newId = (string) $register->invoke($webhook);

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
