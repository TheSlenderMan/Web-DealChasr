<?php
include "../../api.almanacmedia.co.uk/classes/settings/settings.php";
include "../../api.almanacmedia.co.uk/classes/email/email.php";
include "../../api.almanacmedia.co.uk/classes/content/content.php";

$content = new content();

$conn = new PDO('mysql:dbname=' . DS_DATABASE_NAME . ';host=' . DS_DATABASE_HOST, DS_DATABASE_USERNAME, DS_DATABASE_PASSWORD);

$monthAgoToday = date("Y-m-d 00:00:00", strtotime("-1 month"));
$day = date("d", time());
$today = date("Y-m-d 23:59:59", time());

$getVenues = $conn->prepare("SELECT id, vEmail, active, tier, created FROM ds_venues
							WHERE DAY(created) = :d");
$getVenues->bindParam(":d", $day);
$getVenues->execute();

$venues = $getVenues->fetchAll();

$invoices = array();

foreach($venues AS $vk => $vv){
	$check = $conn->prepare("SELECT * FROM ds_invoices WHERE venueID = :vid
							AND MONTH(invoiceDate) = MONTH(CURRENT_DATE())");
	$check->bindParam(':vid', $vv['id']);
	$check->execute();
	$getCheck = $check->fetchAll();
	
	if(count($getCheck) < 1 && $vv['active'] == 1){
		$getVouchers = $conn->prepare("SELECT * FROM ds_vouchers 
										WHERE venueID = :vid 
										AND created >= :ma
										AND created <= :t");
		$getVouchers->bindParam(":vid", $vv['id']);
		$getVouchers->bindParam(":ma", $monthAgoToday);
		$getVouchers->bindParam(":t", $today);
		$getVouchers->execute();
		
		$vouchers = $getVouchers->fetchAll();
		
		$invoices[$vv['id']] = array("total" => 0, "redemptions" => 0);
		
		foreach($vouchers AS $k => $v){
			
			$invoices[$vv['id']][$v['id']] = array();
			
			$getRedemptions = $conn->prepare("SELECT * FROM ds_redemptions
												WHERE voucherID = :vid
												AND redeemed >= :ma
												AND redeemed <= :t");
			$getRedemptions->bindParam(":vid", $v['id']);
			$getRedemptions->bindParam(":ma", $monthAgoToday);
			$getRedemptions->bindParam(":t", $today);
			$getRedemptions->execute();
			
			$redemptions = $getRedemptions->fetchAll();
			
			$invoices[$vv['id']]['total'] = ($invoices[$vv['id']]['total'] + (.5 * count($redemptions)));
			$invoices[$vv['id']]['redemptions'] = ($invoices[$vv['id']]['redemptions'] + count($redemptions));
			$invoices[$vv['id']][$v['id']] = $redemptions;
		}
		
		if($vv['tier'] == 3){
			$emailTotal = ($invoices[$vv['id']]['total'] + 9.99);
			$sub = 9.99;
		} else {
			$sub = 0;
		}
		
		$created = $vv['created'];
		$start = date("Y-m-d 00:00:00", strtotime("-1 month"));
		$end = date("Y-m-d 23:59:59", time());
		
		echo $created . ' - ' . $start . ' : ' . $end;
		
		if($created >= $start && $created <= $end){
			$promo = 1;
		} else {
			$promo = 0;
		}
		
		$email = new email($vv['vEmail']);
		$email->setBody($content->getContent("INVOICE", array($emailTotal, $invoices[$vv['id']]['redemptions'],
		$vv['tier'], strtoupper(date("M Y", time())))));
		$email->setSubject("Your DealChasr Invoice " . strtoupper(date("M Y", time())));
		$email->executeMail();
		
		if($invoices[$vv['id']]['total'] == 0 || $vv['tier'] == 1 || $promo){
			$paid = 1;
		} else {
			$paid = 0;
		}
		
		$addInvoice = $conn->prepare("INSERT INTO ds_invoices (invoicePaid, invoiceSent, venueID, amount, subscription, promo, redemptions, invoiceDate)
									VALUES (:paid, 1, :vid, :amount, :sub, :promo, :red, :date)");
		$addInvoice->bindParam(":paid", $paid);
		$addInvoice->bindParam(":vid", $vv['id']);
		$addInvoice->bindParam(":amount", $invoices[$vv['id']]['total']);
		$addInvoice->bindParam(":sub", $sub);
		$addInvoice->bindParam(":promo", $promo);
		$addInvoice->bindParam(":red", $invoices[$vv['id']]['redemptions']);
		$addInvoice->bindParam(":date", date("Y-m-d H-i-s", time()));
		$addInvoice->execute();
	}
}
