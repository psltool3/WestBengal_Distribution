<?php
require('../util/Connection.php');
require('../util/SessionCheck.php');

$district = $_SESSION['district_district'];

$mapData = [
    "District" => "district",
    "Name of WholeSale" => "name",
    "WholeSale ID" => "id",
    "Type" => "type",
    "Latitude" => "latitude",
    "Longitude" => "longitude",
    "Storage(Qtl)" => "storage",
    "Active/Not-Active" => "active"
];

// Reverse mapping
$reverseMapData = array_flip($mapData);

// Filter the excel data 
function filterData(&$str)
{
    $str = preg_replace("/\t/", "\\t", $str);
    $str = preg_replace("/\r?\n/", "\\n", $str);
    if (strstr($str, '"'))
        $str = '"' . str_replace('"', '""', $str) . '"';
}

// Excel file name for download 
$fileName = "WholeSaleData_" . date('d-m-Y') . ".csv";

$columns = array();

$query = "SHOW COLUMNS FROM WholeSale";
$result = mysqli_query($con, $query);
$numrows = mysqli_num_rows($result);
if ($numrows > 0) {
    while ($row = mysqli_fetch_array($result)) {
        if ($row['Field'] != "uniqueid") {
            array_push($columns, $reverseMapData[$row['Field']]);
        }
    }
}


// Headers for download 
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

// Display column names as first row 
$excelDataColumns = implode(",", array_values($columns)) . "\n";

// Render excel data 
echo $excelDataColumns;

$query = "SELECT * FROM WholeSale WHERE district='$district'";
$result = mysqli_query($con, $query);
$numrows = mysqli_num_rows($result);
if ($numrows > 0) {
    while ($row = mysqli_fetch_array($result)) {
        for ($i = 0; $i < count($columns); $i++) {
            if ($columns[$i] !== "uniqueid") {
                filterData($row[$mapData[$columns[$i]]]);
                echo '"' . $row[$mapData[$columns[$i]]] . '",';
            }
        }
        echo "\n";
    }
}

exit();

?>