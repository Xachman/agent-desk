<?php

namespace App\Services;

use App\Models\SlackWorkspace;
use Illuminate\Http\Request;

class SlackSignatureVerifier
{
    public function verify(Request $request, SlackWorkspace $workspace): bool
    {
        $secret = $workspace->slack_signing_secret;

        if (!$secret) {
            return false;
        }

        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        if (!$timestamp || !$signature) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $body = $request->getContent();
        $base = "v0:{$timestamp}:{$body}";
        $expected = 'v0=' . hash_hmac('sha256', $base, $secret);

        return hash_equals($expected, $signature);
    }
}
