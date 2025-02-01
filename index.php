<?php
date_default_timezone_set('UTC');
include("lib/can-full-size.php");

class Check
{
    private $checkData;
    private $logoMap;

    public function __construct(array $data, array $defaultData = [])
    {
        $defaultConfig = json_decode(file_get_contents('config.json'), true);
        $this->logoMap = $defaultConfig['logoMap'] ?? [];

        unset($defaultConfig['logoMap']);
        $this->checkData = array_merge($defaultConfig, $defaultData, $data);
        $this->setBankLogo();
    }

    public function set(string $key, $value): void
    {
        $this->checkData[$key] = $value;
    }

    public function get(string $key)
    {
        return $this->checkData[$key] ?? null;
    }

    public function getAll(): array
    {
        return $this->checkData;
    }

    private function setBankLogo(): void
    {
        $instNumber = $this->checkData['inst_number'] ?? null;
        $this->checkData['bank_logo'] = $this->logoMap[$instNumber] ?? "";
    }
}

$CHK = new CheckGenerator;

$defaultCheckData = json_decode(file_get_contents('config.json'), true);
$checksData = json_decode(file_get_contents('overrides.json'), true);

foreach ($checksData as $customData) {
    $check = new Check($customData, $defaultCheckData);
    $CHK->AddCheck($check->getAll());
}

if (isset($_SERVER['REMOTE_ADDR'])) {
    header('Content-Type: application/octet-stream', false);
    header('Content-Type: application/pdf', false);
    $CHK->PrintChecks();
} else {
    ob_start();
    $CHK->PrintChecks();
    $pdf = ob_get_clean();
    file_put_contents('checks.pdf', $pdf);
    echo "Saved to file: checks.pdf\n";
}
