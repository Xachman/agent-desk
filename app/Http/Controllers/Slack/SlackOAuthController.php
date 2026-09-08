<?php

namespace App\Http\Controllers\Slack;

use App\Http\Controllers\Controller;
use App\Services\SlackAppProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SlackOAuthController extends Controller
{
    protected SlackAppProvisioningService $provisioning;

    public function __construct(SlackAppProvisioningService $provisioning)
    {
        $this->provisioning = $provisioning;
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $error = $request->input('error');

        if ($error) {
            Log::warning('Slack OAuth callback error', ['error' => $error]);

            return redirect()->route('dashboard')->with('error', "Slack authorization failed: {$error}");
        }

        if (!$code || !$state) {
            return redirect()->route('dashboard')->with('error', 'Invalid Slack authorization response.');
        }

        try {
            $workspace = $this->provisioning->exchangeCode($code, $state);

            return redirect()->route('slack.settings', ['agent' => $workspace->agent_id])
                ->with('success', "Connected to Slack workspace {$workspace->slack_team_name}.");
        } catch (\Exception $e) {
            Log::error('Slack OAuth callback failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard')->with('error', 'Failed to connect Slack: ' . $e->getMessage());
        }
    }
}
