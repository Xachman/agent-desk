<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$deployment = App\Models\AgentDeployment::factory()->make();
$service = app(App\Services\AgentDeploymentService::class);
$manifest = $service->generateManifest($deployment);

echo "Manifest docs: " . count($manifest) . "\n";
echo "PVCs: " . count(array_filter($manifest, fn($d) => ($d['kind'] ?? '') === 'PersistentVolumeClaim')) . "\n";
echo "ConfigMaps: " . count(array_filter($manifest, fn($d) => ($d['kind'] ?? '') === 'ConfigMap')) . "\n";
echo "Deployment: " . (count(array_filter($manifest, fn($d) => ($d['kind'] ?? '') === 'Deployment')) ? 'yes' : 'no') . "\n";
echo "Service: " . (count(array_filter($manifest, fn($d) => ($d['kind'] ?? '') === 'Service')) ? 'yes' : 'no') . "\n";
echo "IngressRoute: " . (count(array_filter($manifest, fn($d) => ($d['kind'] ?? '') === 'IngressRoute')) ? 'yes' : 'no') . "\n";
echo "YAML length: " . strlen($deployment->yaml_snapshot) . "\n";
