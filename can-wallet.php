<?php
date_default_timezone_set('UTC');
include("lib/can-wallet.php");

$CHK = new CheckGenerator;


$check['logo'] = "";
$check['from_name'] = "Your Name";

$check['from_address1'] = "234 Bay Street";
$check['from_address2'] = "Toronto, ON M5K 1B2";

$check['transit_number'] = "12345";
$check['account_number'] = "123456789";
$check['inst_number'] = "001";

$check['bank_1'] = "Bank of Canada";
$check['bank_2'] = "234 Wellington Street";
$check['bank_3'] = "Ottawa, ON K1A 0G9";
$check['bank_4'] = "";

$check['signature'] = "";

$check['pay_to'] = "YOUR MOM";
$check['amount'] = '12345.56';
$check['date'] = "2020-01-01";
$check['memo'] = "TEST";

// 3 checks per page

$check['check_number'] = 1000;
$CHK->AddCheck($check);

$check['check_number']++;
$CHK->AddCheck($check);

$check['check_number']++;
$CHK->AddCheck($check);


if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
  // Called from a browser
  header('Content-Type: application/octet-stream', false);
  header('Content-Type: application/pdf', false);
  $CHK->PrintChecks();
} else {
  // Called from the command line
  ob_start();
  $CHK->PrintChecks();
  $pdf = ob_get_clean();
  file_put_contents('checks.pdf', $pdf);
  echo "Saved to file: checks.pdf\n";
}
