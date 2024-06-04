<?php

namespace App\Utils;

use \Notification;
use App\Business;
use App\Notifications\CustomerNotification;
use App\Notifications\RecurringInvoiceNotification;
use App\Notifications\RecurringExpenseNotification;

use App\Notifications\SupplierNotification;

use App\NotificationTemplate;
use App\Restaurant\Booking;
use App\System;
use App\Transaction;
use Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsUtil extends Util
{	public function sendSmsMessage($messageText,$toNumbersCsv,$mask,$sessionKey)
	{
		// dd($messageText);
		global $planetbeyondApiSendSmsUrl;
		$planetbeyondApiSendSmsUrl="api.bizsms.pk/api-send-branded-sms.aspx?username=#username#&pass=#password#&text=#message_text#&masking=#masking#&destinationnum=#to_number_csv#&language=English";

		// var_dump($planetbeyondApiSendSmsUrl);
		//$sessionKey=$this->getApiToken();

		$userName = 'skechers@bizsms.pk';
		$password = '@skechers123748';
	
	
	
		$url=str_replace("#message_text#",urlencode($messageText),$planetbeyondApiSendSmsUrl);
		$url=str_replace("#to_number_csv#",$toNumbersCsv,$url);
		
		// $url=str_replace("#from_number#",$fromNumber,$url);
		$url=str_replace("#masking#",$mask,$url);
		$url=str_replace("#username#",$userName,$url);
		$url=str_replace("#password#",$password,$url);

		// $urlWithSessionKey=str_replace("#session_id#",$sessionKey,$url);
		// if($mask!=null)
		// {
		// $urlWithSessionKey = $urlWithSessionKey . "&mask=" . $mask;
		// }
        // dd($url);
		$xml=$this->sendApiCall($url);
		return true;
	}
	public function sendApiCall($url)
	{
	// $response = file_get_contents($url);
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $response = curl_exec($ch);
    curl_close($ch);
	 $xml=simplexml_load_string($response) or die("Error: Cannot create object");
	
	 if($xml && !empty($xml->response))
	 {
	return $xml;
	}
	return "";
	}

}