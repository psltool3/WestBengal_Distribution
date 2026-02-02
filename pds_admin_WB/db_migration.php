<?php
require('util/Connection.php');

// Drop columns
$drop_query1 = "ALTER TABLE WholeSale DROP COLUMN demand";
$drop_query2 = "ALTER TABLE WholeSale DROP COLUMN demand_rice";
$drop_query3 = "ALTER TABLE WholeSale DROP COLUMN demand_frice";

// Add column (using VARCHAR(255) as per plan, though DECIMAL might be better for quantity, sticking to plan/existing pattern)
$add_query = "ALTER TABLE WholeSale ADD COLUMN storage VARCHAR(255)";

if (mysqli_query($con, $drop_query1)) {
    echo "Dropped 'demand' successfully.<br>";
} else {
    echo "Error dropping 'demand': " . mysqli_error($con) . "<br>";
}

if (mysqli_query($con, $drop_query2)) {
    echo "Dropped 'demand_rice' successfully.<br>";
} else {
    echo "Error dropping 'demand_rice': " . mysqli_error($con) . "<br>";
}

if (mysqli_query($con, $drop_query3)) {
    echo "Dropped 'demand_frice' successfully.<br>";
} else {
    echo "Error dropping 'demand_frice': " . mysqli_error($con) . "<br>";
}

if (mysqli_query($con, $add_query)) {
    echo "Added 'storage' successfully.<br>";
} else {
    echo "Error adding 'storage': " . mysqli_error($con) . "<br>";
}

mysqli_close($con);
?>
