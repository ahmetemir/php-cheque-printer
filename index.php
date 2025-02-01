<?php
date_default_timezone_set('UTC');
include("lib/can-full-size.php");

class Check
{
    private $logoMap = [
        "001" => "logos/bmo.png",
        "002" => "logos/bns.png",
        "003" => "logos/rbc.png",
        "004" => "logos/td.png",
        "006" => "logos/nbc.png",
        "010" => "logos/cibc.png"
    ];

    private $checkData = [];
    private $defaultData = [];

    public function __construct($data, $defaultData = [])
    {
        $this->defaultData = $defaultData;
        $this->checkData = array_merge($this->defaultData, $data);
        $this->setBankLogo();
    }

    public function set($key, $value)
    {
        $this->checkData[$key] = $value;
    }

    public function get($key)
    {
        return $this->checkData[$key] ?? null;
    }

    public function getAll()
    {
        return $this->checkData;
    }

    private function setBankLogo()
    {
        error_log("inst number: " . ($this->checkData['inst_number'] ?? 'N/A'));
        if (!empty($this->checkData['inst_number']) && array_key_exists($this->checkData['inst_number'], $this->logoMap)) {
            $this->checkData['bank_logo'] = $this->logoMap[$this->checkData['inst_number']];
        } else {
            error_log('Nothing found for institution number');
            $this->checkData['bank_logo'] = "";
        }
    }
}

$CHK = new CheckGenerator;

$defaultCheckData = [
    'inst_number' => "002",
    'my_logo' => "",
    'from_name' => "Your Name",
    'from_address1' => "234 Bay Street",
    'from_address2' => "Toronto, ON M5K 1B2",
    'transit_number' => "12345",
    'account_number' => "123-456-789",
    'bank_1' => "Bank of Canada",
    'bank_2' => "234 Wellington Street",
    'bank_3' => "Ottawa, ON K1A 0G9",
    'bank_4' => "",
    'signature' => "./signatures/thor.png",
    'pay_to' => "YOUR MOM",
    'amount' => '9999999.99',
    'date' => "2020-01-01",
    'memo' => "TEST",
    'check_number' => 1000
];

$customCheckData1 = [
    'transit_number' => '9999999',
    'pay_to' => "John Doe",
    'amount' => '5000.00',
    'memo' => "Salary Payment"
];

$check1 = new Check($customCheckData1, $defaultCheckData);
$CHK->AddCheck($check1->getAll());

$customCheckData2 = [
    'check_number' => $check1->get('check_number') + 1,
    'pay_to' => "Jane Smith",
    'amount' => '7500.00'
];

$check2 = new Check($customCheckData2, $defaultCheckData);
$CHK->AddCheck($check2->getAll());


$check3 = new Check($customCheckData2, $defaultCheckData);
$CHK->AddCheck($check3->getAll());

if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
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
