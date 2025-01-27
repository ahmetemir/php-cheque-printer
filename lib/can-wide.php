<?php
require_once dirname(__FILE__) . "/fpdf.php";
require_once dirname(__FILE__) . "/textualnumber.php";

class CheckGenerator
{

    var $checks = array();

    function AddCheck($check)
    {
        $required_fields = array(
            'transit_number',
            'account_number',
            'inst_number',
            'check_number',
            'pay_to',
            'amount',
            'date',
            'from_name',
            'from_address1',
            'from_address2',
            'bank_1',
            'bank_2',
            'bank_3',
            'bank_4',
            'memo'
        );

        $valid = true;

        foreach ($required_fields as $r) {
            if (!array_key_exists($r, $check)) {
                $valid = false;
            }
        }

        if ($valid) {
            $this->checks[] = $check;
            return true;
        } else {
            echo "Missing data for check:<br>";
            print_r(array_diff(array_keys($check), $required_fields));
            print_r(array_diff($required_fields, array_keys($check)));
            return false;
        }
    }


    function PrintChecks()
    {

        ////////////////////////////
        // label-specific variables
        $page_width = 8.5;
        $page_height = 11;

        $top_margin = 0;
        $left_margin = 0.25;

        $columns = 1;
        $gutter = 3 / 16;
        $rows = 3;      // only used for making page breaks, no position calculations

        $label_height = 3.7;
        $label_width  = 8.5;

        // cell margins
        $cell_left = 0.25;
        $cell_top  = 0.25;
        $cell_bot  = 0.25;

        ////////////////////////////

        $img_ratio = 1.4; // loqisaur
        $img_ratio = .47; // cyan
        $img_ratio = 1.71; // marvelous labs
        $logo_width = 0.66; // loqisaur
        $logo_width = 0.2; // cyan
        $logo_width = 0.5; // marvelous labs

        // Create a PDF with inches as the unit
        $pdf = new FPDF('P', 'in', array($page_width, $page_height));

        $pdf->AddFont('Twcen', '', 'twcen.php');
        $pdf->AddFont('Micr', '', 'micr.php');
        $pdf->AddFont('Courier', '', 'courier.php');

        $pdf->SetMargins($left_margin, $top_margin);
        $pdf->SetDisplayMode("fullpage", "continuous");
        $pdf->AddPage();

        $lpos = 0;
        foreach ($this->checks as $check) {

            $pos = $lpos % ($rows * $columns);

            // calculate coordinates of top-left corner of current cell
            //    margin        cell offset
            $x = $left_margin + (($pos % $columns) * ($label_width + $gutter));
            //    margin        cell offset
            $y = $top_margin  + (floor($pos / $columns) * $label_height);


            /////////////////
            // set up check template

            $pdf->SetFont('Twcen', '', 11);

            // print check number
            $pdf->SetXY($x + 6.5, $y + 0.2);
            $pdf->Cell(1, (11 / 72), $check['check_number'], 0, 'R');

            $logo_offset = 0;  // offset to print name if logo is inserted
            if (array_key_exists('logo', $check) && $check['logo'] != "") {
                // logo should be: 0.71" x 0.29"
                $logo_offset = $logo_width + 0.005;  // width of logo
                $pdf->Image($check['logo'], $x + $cell_left, $y + $cell_top + .12, $logo_width);
            }

            $pdf->SetFont('Twcen', '', 8);

            // name
            $pdf->SetXY($x + $cell_left + $logo_offset, $y + $cell_top + .1);
            $pdf->SetFont('Twcen', '', 10);
            $pdf->Cell(2, (10 / 72), strtoupper($check['from_name']), 0, 2);
            $pdf->SetFont('Twcen', '', 8);
            $pdf->Cell(2, (7 / 72), strtoupper($check['from_address1']), 0, 2);
            $pdf->Cell(2, (7 / 72), strtoupper($check['from_address2']), 0, 2);

            // date
            $pdf->SetFont('Twcen', '', 8);
            $pdf->Line($x + 5, $y + .58, $x + 6.3, $y + .58);
            // date label
            $pdf->SetXY($x + 5, $y + .48);
            $date_str = $this->matchcase($check['from_name'], "DATE");
            $pdf->Cell(1, (7 / 72), $date_str);



            $length_of_line = 5.75;
            // pay to the order of
            $pdf->Line($x + $cell_left, $y + 1.1, $x + $cell_left + $length_of_line, $y + 1.1);
            $pdf->SetXY($x + $cell_left, $y + .88);
            $pay_str = strtoupper("pay to the order of");
            $pdf->MultiCell(0.7, (7 / 72), $pay_str, 0);


            // dollar sign
            $pdf->SetFont('Twcen', '', 16);
            // X coordinate
            $pdf->Cell(6.3);
            $pdf->Cell(-.25, -.15, '$');

            //set font back to twcen
            $pdf->SetFont('Twcen', '', 8);

            // convenience amount rectangle
            $pdf->Rect($x + 6.5, $y + .85, 1.1, .3);

            // written amount
            $pdf->SetFont('Twcen', '', 10);
            $pdf->Line($x + $cell_left, $y + 1.6, $x + $cell_left + $length_of_line + 1, $y + 1.6);
            $pdf->SetXY($x + $cell_left + 3.75, $y + 1.5);

            // Dollars text
            $dollar_str = "DOLLARS";
            $pdf->Cell(3, 0.4, $dollar_str, '', '', 'R');

            // bank info content
            $pdf->SetFont('Twcen', '', 8);
            $pdf->SetXY($x + $cell_left, $y + 1.7);
            $pdf->Cell(2, (7 / 72), strtoupper($check['bank_1']), 0, 2);
            $pdf->Cell(2, (7 / 72), strtoupper($check['bank_2']), 0, 2);
            $pdf->Cell(2, (7 / 72), strtoupper($check['bank_3']), 0, 2);
            $pdf->Cell(2, (7 / 72), strtoupper($check['bank_4']), 0, 2);


            // memo heading
            $pdf->SetFont('Twcen', '', 8);
            $pdf->Line($x + $cell_left, $y + 2.325, $x + $cell_left + 2.9, $y + 2.325);
            $pdf->SetXY($x + $cell_left, $y + 2.225);
            $memo_str = "MEMO";
            $pdf->Cell(1, (7 / 72), $memo_str);

            // signature line
            $pdf->Line($x + 4.25, $y + 2.325, $x + 5 + 2.375, $y + 2.325);

            ///////////////// CONTENT ////////////////
            $pdf->SetFont('Courier', '', 11);

            // date content
            if ($check['date'] != "") {
                $pdf->SetXY($x + 5 + .3, $y + .38);
                $pdf->Cell(1, .25, $check['date']);
            }

            // pay to content
            if ($check['pay_to'] != "") {
                $pdf->SetXY($x + $cell_left + 1, $y + .88);
                $pdf->Cell(1, .25, $check['pay_to']);
            }

            // amount content
            if ($check['amount'] > 0) {
                $dollars = intval($check['amount']);
                $cents = round(($check['amount'] - $dollars) * 100);
                $numtxt = new TextualNumber($dollars);
                $dollars_str = $numtxt->numToWords($dollars);

                $amt_string = "*****" . ucfirst(strtoupper($dollars_str)) . " DOLLARS";
                if ($cents > 0) {
                    $amt_string .= " AND " . $cents . "/100";
                } else {
                    $amt_string .= " AND 00/100";
                }

                // written amount formatting
                $pdf->SetFont('Courier', '', 9);
                $pdf->SetXY($x + $cell_left, $y + 1.4);
                $pdf->Cell(1, .25, $amt_string);

                # box amount content
                $amt = number_format($check['amount'], 2);
                $pdf->SetXY($x + 4.5 + 2.1, $y + .83);
                $pdf->Cell(1, 0.35, $amt);
            }

            // memo content
            $pdf->SetFont('Courier', '', 8);
            $pdf->SetXY($x + $cell_left + 0.5, $y + 2.15);
            $pdf->Cell(1, .2, $check['memo']);
            $pdf->SetFont('Courier', '', 11);

            // routing and account number
            $pdf->SetFont('Micr', '', 10);
            // t = transit number symbol
            // o = on-us symbol
            // d = dash
            $routingstring = "o" . $check['check_number'] . "o   t" . $check['transit_number'] . "d" . $check['inst_number'] . "t  " . $this->replaceDashesWithD($check['account_number']) . "o";
            if (array_key_exists('codeline', $check))
                $routingstring = $check['codeline'];

            $pdf->SetXY($x + $cell_left, $y + 2.65);
            $pdf->Cell(5, 0.16, $routingstring);


            // signature
            if (substr($check['signature'], -3) == 'png') {
                $sig_offset = 1.75;  // width of signature
                $pdf->Image($check['signature'], $x + $cell_left + 3.4, $y + 1.88, $sig_offset);
            } else {
                $pdf->SetFont('Arial', 'i', 10);
                if ($check['signature'] != "") {
                    $pdf->SetXY($x + $cell_left + 3.4, $y + 2.01);
                    $pdf->Cell(1, .25, $check['signature']);
                }
            }

            if ($pos == (($rows * $columns) - 1) && !($lpos == count($this->checks) - 1)) {
                $pdf->AddPage();
            }

            $lpos++;
        }

        $pdf->Output();
    }


    // private, returns $str capitalized to match with $name
    function matchcase($name, $str)
    {
        // check if first letter is uppercase
        if (strtoupper(substr($name, 0, 1)) == substr($name, 0, 1)) {
            return ucfirst($str);
        } else {
            return strtolower($str);
        }
    }

    function replaceDashesWithD($accountNumber)
    {
        return str_replace('-', 'd', $accountNumber);
    }
}
