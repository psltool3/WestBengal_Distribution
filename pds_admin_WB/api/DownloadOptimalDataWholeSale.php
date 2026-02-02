<?php

require('../util/Connection.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


// Check if format is specified in GET request
if (isset($_GET['format'])) {
    $format = $_GET['format'];
    $tableName = $_GET['tableName'];

    // Validate table name to prevent SQL injection
    if (strpos($tableName, 'fci_') !== 0) {
        die("Invalid table name");
    }

    $columns = ["district", "name", "id", "type", "latitude", "longitude", "demand", "demand_rice", "demand_frice"];
    // Header names for display
    $headerNames = ["District", "Name of FCI", "FCI ID", "Type of FCI", "Latitude", "Longitude", "Wheat Procurement(Qtl)", "Rice Procurement(Qtl)", "FRice Procurement(Qtl)"];

    $query = "SELECT * FROM " . $tableName . " WHERE 1";

    $result = mysqli_query($con, $query);
    $numrows = mysqli_num_rows($result);
    $tableData = array();
    array_push($tableData, $headerNames);

    if ($numrows > 0) {
        while ($row = mysqli_fetch_array($result)) {
            $temp = array();
            for ($i = 0; $i < count($columns); $i++) {
                array_push($temp, $row[$columns[$i]]);
            }
            array_push($tableData, $temp);
        }
    }

    // Filename for the downloaded file
    $filename = $tableName . '_data';

    // Set headers for the chosen format
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            outputCSV($tableData);
            break;

        case 'xlsx':
            // Create a new PhpSpreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Insert data tableData
            $rowIndex = 1;
            foreach ($tableData as $rowData) {
                $columnIndex = 1;
                foreach ($rowData as $value) {
                    $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
                    $columnIndex++;
                }
                $rowIndex++;
            }


            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        case 'pdf':
            require('fpdf/fpdf.php');

            $pdf = new FPDF('L', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 15); // Set initial font size

            // Calculate column width based on the number of columns and page width
            $pageWidth = $pdf->GetPageWidth() - 20; // Subtract margins (10 mm each side)
            $numCols = count($tableData[0]);
            $colWidth = $pageWidth / $numCols;
            $originalColWidth = $colWidth;

            // Function to add a row to the PDF with dynamic font size adjustment
            function addRow($pdf, $row, $colWidth, $isHeader = false)
            {
                global $originalColWidth;
                $pdf->SetFillColor($isHeader ? 200 : 255, $isHeader ? 220 : 255, $isHeader ? 255 : 255);
                foreach ($row as $col) {
                    $currentColWidth = $originalColWidth;
                    $fontSize = 10;
                    $pdf->SetFont('Arial', $isHeader ? 'B' : '', $fontSize);
                    // Reduce font size if text is too wide for the cell
                    while ($pdf->GetStringWidth($col) > $currentColWidth - 2 && $fontSize > 5) {
                        $fontSize -= 1;
                        $pdf->SetFont('Arial', $isHeader ? 'B' : '', $fontSize);
                    }
                    $pdf->Cell($currentColWidth, 10, $col, 1, 0, 'C', true);
                }
                $pdf->Ln();
            }

            // Add the header
            addRow($pdf, $tableData[0], $colWidth, true);

            // Add the data rows
            $rowHeight = 10;
            // Subtract margins (10 mm each top and bottom)

            for ($i = 1; $i < count($tableData); $i++) {
                if ($pdf->GetY() + $rowHeight > $pdf->GetPageHeight() - 10) { // Check if we need to add a new page
                    $pdf->AddPage();
                    addRow($pdf, $tableData[0], $colWidth, true); // Add the header again on the new page
                }
                addRow($pdf, $tableData[$i], $colWidth);
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
            echo $pdf->Output('S');
            break;

        default:
            echo 'Error : Invalid format specified.';
            break;
    }
} else {
    echo 'Error : Please specify a format in the GET request (e.g., ?format=pdf).';
}



// Function to output CSV data
function outputCSV($data)
{
    $output = fopen('php://output', 'w');
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
}

exit();
