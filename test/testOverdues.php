<?php
include "../../api.almanacmedia.co.uk/classes/settings/settings.php";
include "../../api.almanacmedia.co.uk/classes/email/email.php";
include "../../api.almanacmedia.co.uk/classes/content/content.php";

$content = new content();

$conn = new PDO('mysql:dbname=' . DS_DATABASE_NAME . ';host=' . DS_DATABASE_HOST, DS_DATABASE_USERNAME, DS_DATABASE_PASSWORD);

$now              = date("Y-m-d", time());
$nowPlusThreeDays = date("Y-m-d", strtotime("+3 days"));

$getOverdueInvoices = $conn->prepare("SELECT i.invoiceDate, i.venueID, i.id, v.vEmail FROM ds_invoices AS i
									JOIN ds_venues AS v
									ON v.id = i.venueID
									WHERE i.overdue = 0
									AND i.invoiceDate > :date
									AND v.active = 0");
$getOverdueInvoices->bindParam(":date", $nowPlusThreeDays);
$getOverdueInvoices->execute();

$invoices = $getOverdueInvoices->fetchAll();

foreach($invoices AS $k => $v) {
	
}