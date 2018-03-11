<?php
include "../../api.almanacmedia.co.uk/classes/settings/settings.php";
include "../../api.almanacmedia.co.uk/classes/email/email.php";
include "../../api.almanacmedia.co.uk/classes/content/content.php";

$content = new content();

$conn = new PDO('mysql:dbname=' . DS_DATABASE_NAME . ';host=' . DS_DATABASE_HOST, DS_DATABASE_USERNAME, DS_DATABASE_PASSWORD);

$day   = date('d', time());
$month = date('m', strtotime('-1 month'));
$year  = date('Y', time());
$lastDateString = $year . '-' . $month . '-' . $day;

$startDate = date('Y-m-d H:i:s', strtotime($lastDateString));
$endDate   = date('Y-m-d H:i:s', time());

$getVouchers = $conn->prepare("SELECT id, venueID FROM ds_vouchers WHERE created > :start AND created < :end");
$getVouchers->bindParam(":start", $startDate);
$getVouchers->bindParam(":end", $endDate);
$getVouchers->execute();
$vouchers = $getVouchers->fetchAll();

$invoiceData = array();
foreach($vouchers AS $k => $v){
	$getRedemptions = $conn->prepare("SELECT * FROM ds_redemptions WHERE voucherID = :voucher
										AND redeemed > :start AND redeemed < :end");
	$getRedemptions->bindParam(":voucher", $v['id']);
	$getRedemptions->bindParam(":start", $startDate);
	$getRedemptions->bindParam(":end", $endDate);
	$getRedemptions->execute();
	$redemptions = $getRedemptions->fetchAll();
	if(!empty($redemptions)){
		$invoiceData[$v['venueID']][$v['id']] = $redemptions;
	}
}

foreach($invoiceData AS $k => $v){
	$getVenueDetails = $conn->prepare("SELECT * FROM ds_venues WHERE id = :vid");
	$getVenueDetails->bindParam(':vid', $k);
	$getVenueDetails->execute();
	
	$check = $conn->prepare("SELECT * FROM ds_invoices WHERE venueID = :vid AND invoiceSent = 1 AND invoicePaid = 0");
	$check->bindParam(':vid', $k);
	$check->execute();
	$getCheck = $check->fetchAll();
	
	if(count($getCheck) < 1){
		$venueDetails = $getVenueDetails->fetch();
		
		if($venueDetails['active'] == 1 && $venueDetails['tier'] > 1){
			$venueDetails['redemptionsThisMonth'] = 0;
			$venueDetails['amount'] = 0;
			
			foreach($v AS $rk => $r){
				$venueDetails['redemptionsThisMonth'] = ($venueDetails['redemptionsThisMonth'] + count($r));
				$venueDetails['amount'] = ($venueDetails['amount'] + (.5 * count($r)));
			}
			
			$email = new email($venueDetails['vEmail']);
			$email->setBody($content->getContent("INVOICE", array($venueDetails['amount'],$venueDetails['redemptionsThisMonth'],
			$venueDetails['tier'], strtoupper(date("M y", time())))));
			$email->setSubject("Your DealChasr Invoice " . strtoupper(date("M y", time())));
			$email->executeMail();
			
			$addInvoice = $conn->prepare("INSERT INTO ds_invoices (invoicePaid, invoiceSent, venueID, amount, redemptions, invoiceDate)
										VALUES (0, 1, :vid, :amount, :red, :date)");
			$addInvoice->bindParam(":vid", $k);
			$addInvoice->bindParam(":amount", $venueDetails['amount']);
			$addInvoice->bindParam(":red", $venueDetails['redemptionsThisMonth']);
			$addInvoice->bindParam(":date", date("Y-m-d H-i-s", time()));
			$addInvoice->execute();
		}
	}
}