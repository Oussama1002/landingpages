<?php
/**
 * WhatsApp Business API Webhook for LUXORA (Store 1)
 *
 * Callback URL: http://79.143.180.186:8088/Store%201%20-%20Watches%20%26%20Bracelets/webhook.php
 * Verify Token: LUXORA_WH_2026_SECURE_TOKEN
 */

$VERIFY_TOKEN = 'LUXORA_WH_2026_SECURE_TOKEN';

// ── Webhook Verification (GET) ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $VERIFY_TOKEN) {
        http_response_code(200);
        echo $challenge;
        exit;
    } else {
        http_response_code(403);
        echo 'Verification failed';
        exit;
    }
}

// ── Incoming Messages/Status Updates (POST) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Log incoming webhooks
    $logFile = __DIR__ . '/webhook_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] " . $input . "\n", FILE_APPEND);

    // Process message status updates
    if (isset($data['entry'])) {
        foreach ($data['entry'] as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                // Incoming messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $from = $message['from'] ?? '';
                        $text = $message['text']['body'] ?? '';
                        file_put_contents($logFile, "[$timestamp] MESSAGE from $from: $text\n", FILE_APPEND);
                    }
                }

                // Message status (sent, delivered, read)
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $recipientId = $status['recipient_id'] ?? '';
                        $statusType = $status['status'] ?? '';
                        file_put_contents($logFile, "[$timestamp] STATUS $recipientId: $statusType\n", FILE_APPEND);
                    }
                }
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'received']);
    exit;
}

http_response_code(405);
echo 'Method not allowed';
