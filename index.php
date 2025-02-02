<?php
date_default_timezone_set('UTC');
include("lib/can-full-size.php");

class Check
{
    private $checkData;
    private $logoMap;
    private $logoSizeMap;

    public function __construct(array $data, array $defaultData = [])
    {
        $defaultConfig = json_decode(file_get_contents('config.json'), true);
        $this->logoMap = $defaultConfig['logoMap'] ?? [];
        $this->logoSizeMap = $defaultConfig['logoSize'] ?? [];

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
        if ($instNumber === null) {
            error_log("Error: inst_number is not set in checkData!");
            return;
        }

        $custom_logo = isset($this->logoSizeMap[$instNumber]) ? $this->logoSizeMap[$instNumber] : null;

        error_log("Setting logo: " . var_export($instNumber, true) .
            " custom_logo: " . var_export($custom_logo, true));

        if ($instNumber === '010' && stripos($this->checkData['bank_1'], 'simplii') !== false) {
            error_log("Simplii detected");
            $this->checkData['bank_logo'] = $this->logoMap['simplii'] ?? "";
            $this->checkData['bank_logo_size'] = $this->logoSizeMap['simplii'] ?? null;
        } elseif ($custom_logo !== null) {
            $this->checkData['bank_logo'] = $this->logoMap[$instNumber] ?? "";
            $this->checkData['bank_logo_size'] = $custom_logo;
            error_log("Bank logo size: " . var_export($this->checkData['bank_logo_size'], true));
        } else {
            $this->checkData['bank_logo'] = $this->logoMap[$instNumber] ?? "";
            error_log("Warning: No custom logo found for instNumber: " . var_export($instNumber, true));
        }
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
