<?php
date_default_timezone_set('UTC');
include("lib/can-full-size.php");

$CHK = new CheckGenerator;

// http://127.0.0.1:8000/?default=albert.json&overrides=overrides-albert.json

$defaultFileName = isset($_GET['default']) ? $_GET['default'] : 'config.json';
$overridesFileName = isset($_GET['overrides']) ? $_GET['overrides'] : 'overrides.json';

// Ensure files exist before reading
if (!file_exists($defaultFileName)) {
    die("Error: Default file '$defaultFileName' not found.");
}
if (!file_exists($overridesFileName)) {
    die("Error: Overrides file '$overridesFileName' not found.");
}

$defaultCheckData = json_decode(file_get_contents($defaultFileName), true);
$checksData = json_decode(file_get_contents($overridesFileName), true);

// $defaultCheckData = json_decode(file_get_contents('config.json'), true);
// $checksData = json_decode(file_get_contents('overrides.json'), true);

foreach ($checksData as $customData) {
    $check = new Check($customData, $defaultCheckData);
    $CHK->AddCheck($check->getAll());
}

if (isset($_SERVER['REMOTE_ADDR'])) {
    header('Content-Type: application/octet-stream', false);
    header('Content-Type: application/pdf', false);
    $CHK->PrintChecks($defaultCheckData);
} else {
    ob_start();
    $CHK->PrintChecks($defaultCheckData);
    $pdf = ob_get_clean();
    file_put_contents('checks.pdf', $pdf);
    echo "Saved to file: checks.pdf\n";
}

/**
 * Class Check
 */

class Check
{
    private $checkData;
    private $logoMap;
    private $logoSizeMap;
    private $micrMap;

    public function __construct(array $data, array $defaultData = [])
    {
        $logoConfig = json_decode(file_get_contents('banks.json'), true);

        $this->logoMap = $logoConfig['logoMap'] ?? [];
        $this->logoSizeMap = $logoConfig['logoSize'] ?? [];
        $this->micrMap = $logoConfig['micr_spacing'] ?? [];

        $this->checkData = array_merge($defaultData, $data);
        $this->setBankInfo();
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

    private function setBankInfo(): void
    {
        $instNumber = $this->checkData['inst_number'] ?? null;

        error_log('Setting bank info for instNumber: ' . var_export($instNumber, true));

        $this->checkData['micr_spacing'] = $this->micrMap[$this->checkData['inst_number']] ?? 3;

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