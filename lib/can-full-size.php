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
        $config = json_decode(file_get_contents('config.json'), true);
        $positionsToPrint = $config['config']['positions'] ?? ["top", "middle", "bottom"];
        $check_positions = ["top" => 0, "middle" => 1, "bottom" => 2];

        ////////////////////////////
        // label-specific variables
        $page_width = 8.5;
        $page_height = 11;

        $top_margin = 0.12; # measure distance from top to decorative line
        $left_margin = 0.25;

        $columns = 1;
        $gutter = 3 / 16;
        $rows = 3; // Three possible check positions: top, middle, bottom

        // $label_height = $page_height / 3 - $top_margin;
        $label_height = 3.50; # measure distance from top to first perforation
        $label_width  = 8.5;

        // cell margins
        $cell_left = 0.25;
        $cell_top  = 0.25;
        $cell_bot  = 0.25;

        ////////////////////////////
        $my_logo_width = 0.3;
        $bank_logo_width = 0.3;

        // Create a PDF with inches as the unit
        $pdf = new FPDF('P', 'in', array($page_width, $page_height));

        $pdf->AddFont('Twcen', '', 'twcen.php');
        $pdf->AddFont('Micr', '', 'micr.php');
        $pdf->AddFont('Courier', '', 'courier.php');

        $pdf->SetMargins($left_margin, $top_margin);
        $pdf->SetDisplayMode("fullpage", "continuous");
        $pdf->AddPage();

        $lpos = 0;
        $print_cut_lines = true;

        foreach ($this->checks as $check) {
            $pos = $lpos % ($rows * $columns);
            $positionName = array_search($pos, $check_positions);

            error_log("position " .  $pos . $positionName);

            // calculate coordinates of top-left corner of current cell
            //    margin        cell offset
            $x = $left_margin + (($pos % $columns) * ($label_width + $gutter));
            //    margin        cell offset
            $top_edge = $top_margin  + (floor($pos / $columns) * $label_height);

            error_log("top_edge" . $top_edge);

            // Check if the current position should be printed
            if (!in_array($positionName, $positionsToPrint)) {
                error_log('Skipping check position ' . $positionName);
                // Change the Y position and then skip to the next check
                $lpos++;
                continue;
            }

            // set up check template
            $pdf->SetFont('Twcen', '', 11);

            // bottom side 
            $aligning_edge = $top_edge + $label_height + $top_margin + 0.25;

            // right side
            $leading_edge = 8.5;

            // print check number
            $pdf->SetXY($leading_edge - 1.5, $top_edge + 0.25);
            $pdf->Cell(0, 0, $check['check_number'], 0, 'R');

            // Your logo
            $logo_offset = 0;  // offset to print name if logo is inserted
            if (array_key_exists('my_logo', $check) && $check['my_logo'] != "") {
                // logo should be: 0.71" x 0.29"
                $logo_offset = $my_logo_width + 0.005;  // width of logo
                $pdf->Image($check['my_logo'], $x + $cell_left, $top_edge + $cell_top + .12, $my_logo_width);
            } 

            $pdf->SetFont('Twcen', '', 8);

            // Your name
            $pdf->SetXY($x + $cell_left + $logo_offset, $top_edge + $cell_top + .1);
            $pdf->SetFont('Twcen', '', 10);
            $pdf->Cell(2, 0.15, strtoupper($check['from_name']), 0, 2);
            $pdf->SetFont('Twcen', '', 8);
            $pdf->Cell(2, 0.1, strtoupper($check['from_address1']), 0, 2);
            $pdf->Cell(2, 0.1, strtoupper($check['from_address2']), 0, 2);

            // date
            $date_line_y = $top_edge + .6;
            $date_line_x = $leading_edge - 2;
            $pdf->SetFont('Twcen', '', 8);
            $pdf->Line($date_line_x, $date_line_y, $leading_edge - 0.7, $date_line_y);
            // date label
            $pdf->SetXY($date_line_x, $date_line_y - 0.1);
            $date_str = $this->matchcase($check['from_name'], "DATE");
            $pdf->Cell(0, 0.1, $date_str);

            // convenience amount rectangle
            $con_amount_width = 0.3;
            $con_amount_length = 1.2;

            // this is the real aligning edge after accounting for margins
            $aligning_edge_content = $aligning_edge - 0.5;
            $con_box_start_x = $leading_edge - $con_amount_length - 0.65;
            $con_box_start_y = $aligning_edge_content - 2.75 + 0.25;
            $pdf->Rect($con_box_start_x, $con_box_start_y, $con_amount_length, $con_amount_width);

            // pay to the order of
            $pay_order_line_length = 5.5;
            $pay_order_name_line = $con_box_start_y + 0.28;
            $pdf->Line($x + $cell_left, $pay_order_name_line, $x + $cell_left + $pay_order_line_length, $pay_order_name_line);
            $pdf->SetXY($x + $cell_left, $pay_order_name_line - 0.2);

            $pay_str = strtoupper("pay to the order of");
            $pdf->MultiCell(0.7, 0.1, $pay_str, 0);

            // dollar sign
            $pdf->SetFont('Twcen', '', 16);
            $pdf->SetXY($con_box_start_x - 0.2, $con_box_start_y + 0.15);
            $pdf->Cell(0, 0, '$');

            //set font back to twcen
            $pdf->SetFont('Twcen', '', 8);


            if ($print_cut_lines) { 
                // print the top line
                $pdf->Line(0, $top_margin, $leading_edge, $top_margin);
                // separator between cheques
                $pdf->Line(0, $aligning_edge_content, $leading_edge, $aligning_edge_content);
            }

            // written amount line
            $written_amt_line_offset = $top_edge + 1.7;
            $written_amount_line_length = $pay_order_line_length + 2;
            $pdf->SetFont('Twcen', '', 10);
            $pdf->Line($x + $cell_left, $written_amt_line_offset, $x + $cell_left + $written_amount_line_length, $written_amt_line_offset);

            // "DOLLARS" Label 
            // dynamic, y position follows $written_amt_line_offset so it's under the line
            $dollar_str = "DOLLARS";
            $pdf->SetXY($leading_edge, $written_amt_line_offset - .2);

            //manually adjust for X pos
            $dollar_text_x_pos = -0.45;
            $pdf->Cell($dollar_text_x_pos, .6, $dollar_str, '', '', 'R');

            // bank info content
            $pdf->SetFont('Twcen', '', 8);
            
            // Bank Logo
            $bank_logo_offset = 0;  // offset to print name if logo is inserted
            if (array_key_exists('bank_logo', $check) && $check['bank_logo'] != "") {
                // logo should be: 0.71" x 0.29"
                $bank_logo_offset = $bank_logo_width + 0.005;  // width of logo
                $pdf->Image($check['bank_logo'], $x + $cell_left, $written_amt_line_offset + 0.1, $bank_logo_width);
            }

            // dynamic, y position follows $written_amt_line_offset so it's under the line
            $pdf->SetXY($x + $cell_left + $bank_logo_offset, $written_amt_line_offset + 0.1);
            $pdf->Cell(2, 0.1, strtoupper($check['bank_1']), 0, 2);
            $pdf->Cell(2, 0.1, strtoupper($check['bank_2']), 0, 2);
            $pdf->Cell(2, 0.1, strtoupper($check['bank_3']), 0, 2);
            $pdf->Cell(2, 0.1, strtoupper($check['bank_4']), 0, 2);

            // memo heading
            $memo_sig_offset = $top_edge + 2.5;

            $pdf->SetFont('Twcen', '', 8);
            $pdf->Line($x + $cell_left, $memo_sig_offset, $x + $cell_left + 2.9, $memo_sig_offset);
            $pdf->SetXY($x + $cell_left, $memo_sig_offset);

            $memo_str = "MEMO";
            $pdf->Cell(0, -0.1, $memo_str);

            // signature line
            $pdf->Line($x + 4.25, $memo_sig_offset, $x + 5 + 2.375, $memo_sig_offset);

            ///////////////// CONTENT ////////////////
            $pdf->SetFont('Courier', '', 11);

            // date content
            if ($check['date'] != "") {
                // y pos dynamic
                $pdf->SetXY($date_line_x + 0.3, $date_line_y - 0.1);
                $pdf->Cell(0, 0, $check['date']);
            }

            // pay to the order ofcontent
            if ($check['pay_to'] != "") {
                // y pos is dynamic
                $pdf->SetXY($x + $cell_left + 1, $pay_order_name_line - 0.1);
                $pdf->Cell(0, 0, strtoupper($check['pay_to']));
            }

            // written amount content
            if ($check['amount'] > 0) {
                $dollars = intval($check['amount']);
                $cents = round(($check['amount'] - $dollars) * 100);
                $numtxt = new TextualNumber($dollars);
                $dollars_str = $numtxt->numToWords($dollars);

                $amt_string = $this->getAsterisks($dollars_str) . ucfirst(strtoupper($dollars_str)) . " DOLLARS";

                if ($cents > 0) {
                    $amt_string .= " AND " . $cents . "/100";
                } else {
                    $amt_string .= " AND 00/100";
                }

                // written amount formatting
                $pdf->SetFont('Courier', '', 9);
                // automatically follows the line
                $pdf->SetXY($x + $cell_left, $written_amt_line_offset - 0.2);
                $pdf->Cell(1, .25, $amt_string);

                // error_log($amt_string);

                # numerical amount content
                $amt = number_format($check['amount'], 2);
                $pdf->SetXY($con_box_start_x + 0.1, $con_box_start_y - 0.025);
                $pdf->Cell(1, 0.35, $amt);
            }

            // memo content
            $pdf->SetFont('Courier', '', 8);
            $pdf->SetXY($x + $cell_left + 0.5, $memo_sig_offset - 0.16);
            $pdf->Cell(1, .2, $check['memo']);
            $pdf->SetFont('Courier', '', 11);

            // routing and account number
            // t = transit number symbol
            // o = on-us symbol
            // d = dash
            $pdf->SetFont('Micr', '', 10);
            $routingstring = "o" . $check['check_number'] . "o  t" . $check['transit_number'] . "d" . $check['inst_number'] . "t" . $this->getSpacesByInstitution($check['inst_number']) . $this->replaceDashesWithD($check['account_number']) . "o";
            if (array_key_exists('codeline', $check))
                $routingstring = $check['codeline'];

            $pdf->SetXY(1, $aligning_edge - 5 / 8 - 0.25);
            $pdf->Cell(0, 0, $routingstring);


            // adjust based on picture dimensions
            $sig_offset_y = 0.5;
            $sig_offset_x = 4.5;
            $sig_width = 1.75;  // width of signature

            // signature
            if (substr($check['signature'], -3) == 'png') {
                $pdf->Image($check['signature'], $x + $cell_left + $sig_offset_x, $memo_sig_offset - $sig_offset_y, $sig_width);
            } else {
                $pdf->SetFont('Arial', 'i', 10);
                if ($check['signature'] != "") {
                    $pdf->SetXY($x + $cell_left + 4.5, $memo_sig_offset - 0.2);
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

    // defines separation between inst number and account number
    function getSpacesByInstitution($institutionNumber)
    {
        $spacesMap = [
            '001' => 2, // BMO
            '002' => 1, // BNS
            '003' => 3, // RBC
            '004' => 1, // TD
            '006' => 3, // NBC
            '010' => 1, // CIBC
            '815' => 3, // DESJ
            '828' => 0, // CU
        ];

        // Default spaces if the institution number is not found
        $defaultSpaces = 3;

        // Get the number of spaces for the given institution number or fallback to default
        $numSpaces = $spacesMap[$institutionNumber] ?? $defaultSpaces;

        // Return the spaces as a string
        return str_repeat(' ', $numSpaces);
    }

    // pads the amount text with asterisks to reach 80 characters
    function getAsterisks($input)
    {
        $maxLength = 80;
        $inputLength = strlen($input);

        if ($inputLength >= $maxLength) {
            return ''; // No stars needed if the string is too long
        }

        $asterisksCount = $maxLength - $inputLength;
        return str_repeat('*', $asterisksCount);
    }
}
