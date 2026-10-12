<?php
$date = $_REQUEST['date'] ?? '';
$course = $_REQUEST['course'] ?? '';

if ($date === '') 
{
	echo 'Preferred Date is required';
} 
else if($course === '')
{
    echo 'Course is required';
}   
else
{
	echo "Date: $date";
	echo "<br>Course: $course";
}
?>
