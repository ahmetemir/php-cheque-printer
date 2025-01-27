<?php
date_default_timezone_set('UTC');
include("lib/can-full-size.php");

$CHK = new CheckGenerator;

function setBankLogo(&$check)
{
  // Define a mapping of transit numbers to logo file paths
  $logoMap = [
    "001" => "logos/bmo.png",
    "002" => "logos/bns.png",
    "003" => "logos/rbc.png",
    "004" => "logos/td.png",
    "006" => "logos/nbc.png",
    "010" => "logos/cibc.png"
  ];

  // Log the institution number for debugging
  error_log("inst number: " . $check['inst_number']);

  // Check if the institution number exists in the mapping
  if (isset($check['inst_number']) && array_key_exists($check['inst_number'], $logoMap)) {
    $check['bank_logo'] = $logoMap[$check['inst_number']];
  } else {
    error_log('Nothing found');
    // Set a default logo if the inst number is not found
    $check['bank_logo'] = "";
  }
}

$check['inst_number'] = "002";
setBankLogo($check);

$check['my_logo'] = "";
$check['from_name'] = "Your Name";

$check['from_address1'] = "234 Bay eStreet";
$check['from_address2'] = "Toronto, ON M5K 1B2";

$check['transit_number'] = "12345";
// include dashes in your account number
// BMO - 123456-123
// BNS - 12345-12
// RBC - 123-123-1
// TD - 1234-1234567
// NBC - 12-123-12
// CIBC - 12-12345
// DESJ - 123-123-1
// CU - 12345678-123
$check['account_number'] = "123-456-789";

$check['bank_1'] = "Bank of Canada";
$check['bank_2'] = "234 Wellington Street";
$check['bank_3'] = "Ottawa, ON K1A 0G9";
$check['bank_4'] = "";

$check['signature'] = "";

$check['pay_to'] = "YOUR MOM";
$check['amount'] = '9999999.99';
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
