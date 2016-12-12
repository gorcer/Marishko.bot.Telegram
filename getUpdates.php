#!/usr/bin/env php
<?php
/**
 * README
 * This configuration file is intented to run the bot with the getUpdates method
 * Uncommented parameters must be filled
 */

// Bash script
// while true; do ./getUpdatesCLI.php; done

// Load composer
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

try {
	// Create Telegram API object
	$telegram = new Longman\TelegramBot\Telegram($API_KEY, $BOT_NAME);

	// Error, Debug and Raw Update logging
	//Longman\TelegramBot\TelegramLog::initialize($your_external_monolog_instance);
	//Longman\TelegramBot\TelegramLog::initErrorLog($path . '/' . $BOT_NAME . '_error.log');
	//Longman\TelegramBot\TelegramLog::initDebugLog($path . '/' . $BOT_NAME . '_debug.log');
	//Longman\TelegramBot\TelegramLog::initUpdateLog($path . '/' . $BOT_NAME . '_update.log');

	// Enable MySQL
	$telegram->enableMySql($mysql_credentials);

	// Enable MySQL with table prefix
	//$telegram->enableMySql($mysql_credentials, $BOT_NAME . '_');

	// Add an additional commands path
	//$telegram->addCommandsPath($commands_path);

	// Enable admin user(s)
	//$telegram->enableAdmin(your_telegram_id);
	//$telegram->enableAdmins([your_telegram_id, other_telegram_id]);

	// Add the channel you want to manage
	//$telegram->setCommandConfig('sendtochannel', ['your_channel' => '@type_here_your_channel']);

	// Here you can set some command specific parameters,
	// for example, google geocode/timezone api key for /date command:
	//$telegram->setCommandConfig('date', ['google_api_key' => 'your_google_api_key_here']);

	// Set custom Upload and Download path
	//$telegram->setDownloadPath('../Download');
	//$telegram->setUploadPath('../Upload');

	// Botan.io integration
	//$telegram->enableBotan('your_token');

	$waitSomething=[];
		$sleepTime = 5;
		while (true) {

				sleep($sleepTime);
				// Handle telegram getUpdates request
				$serverResponse = $telegram->handleGetUpdates();

				if ($serverResponse->isOk()) {

					$result = $serverResponse->getResult();
					$updateCount = count($serverResponse->getResult());
					//echo date('Y-m-d H:i:s', time()) . ' - Processed ' . $updateCount . ' updates';

					$forReply=[];

					if (sizeof($result) > 0) {
						$sleepTime=4;
					} else {
						if ($sleepTime < 20) {
							$sleepTime++;
						}
					}

					foreach ($result as $item) {

						var_dump($item);echo PHP_EOL.PHP_EOL;

						if (!isset($item->message['text']) || !isset($item->message['from'])) continue;

						$userName =(isset($item->message['from']['first_name']) ? $item->message['from']['first_name'].'_':'').
									(isset($item->message['from']['last_name']) ? $item->message['from']['last_name'].'_':'').
									$item->message['from']['id'];
						$userId = $item->message['from']['id'];
						$message = $item->message['text'];
						$chat_id = $item->message['chat']['id'];

						if ($userId == 141455495 && $message == '/stop') { echo 'Buy buy'; die();}

						if ($item->message['chat']['type'] !== 'private' && strpos($message, $BOT_NAME) === false) continue;

						$message = str_replace('@'.$BOT_NAME, '', $message);
						$message = trim($message);

						Longman\TelegramBot\Request::sendChatAction(['chat_id' => $chat_id, 'action' => 'typing']);

						if (isset($forReply[$chat_id][$userId])) {
							$forReply[$chat_id][$userId]['message'].=' '.$message;
						}
						else {
							$forReply[$chat_id][$userId]['message'] = $message;
							$forReply[$chat_id][$userId]['userName'] = $userName;
						}

						$forReply[$chat_id][$userId]['lastDt'] = $item->message['date'];

					}

					foreach ($forReply as $chat_id => $chat)
						foreach($chat as $item) {

							$message = $item['message'];
							$userName = $item['userName'];

							$myCurl = curl_init();


							if ($message == '/start') {
								//echo "Hello!".PHP_EOL;
								curl_setopt_array($myCurl, array(
									CURLOPT_URL => $MARISHKO_API_URL.'0.1/getHello/',
									CURLOPT_RETURNTRANSFER => true,
									CURLOPT_POST => true,
									CURLOPT_POSTFIELDS => http_build_query(array('userName'=>$userName))
								));
							} else {

								curl_setopt_array($myCurl, array(
									CURLOPT_URL => $MARISHKO_API_URL.'0.1/getAnswer/',
									CURLOPT_RETURNTRANSFER => true,
									CURLOPT_POST => true,
									CURLOPT_POSTFIELDS => http_build_query(array('userName'=>$userName, 'phrase'=>$message))
								));
							}


							$response = curl_exec($myCurl);
							curl_close($myCurl);

						//	var_dump($response);

							$response = json_decode($response, true);

							if ($response == false || !isset($response['msg'])) {
								$waitSomething[$chat_id]['time'] = time();
								$waitSomething[$chat_id]['userName'] = time();
								continue;
							} else {
								unset($waitSomething[$chat_id]);
							}

							echo PHP_EOL.$userName.':'.$message.PHP_EOL;
							echo 'bot:'.$response['msg'].PHP_EOL;

							$result = Longman\TelegramBot\Request::sendMessage(['chat_id' => $chat_id, 'text' => $response['msg']]);
						}

					// Say something
					foreach($waitSomething as $chat_id => $item) {

//var_dump(time() - $item['time']);

						if (time() - $item['time'] > 10) {

							unset($waitSomething[$chat_id]);

							Longman\TelegramBot\Request::sendChatAction(['chat_id' => $chat_id, 'action' => 'typing']);

							$myCurl = curl_init();
							curl_setopt_array($myCurl, array(
								CURLOPT_URL => $MARISHKO_API_URL.'0.1/getSomething/',
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_POST => true,
								CURLOPT_POSTFIELDS => http_build_query(array('userName'=>$item['userName']))
							));
							$response = curl_exec($myCurl);
							curl_close($myCurl);

							$response = json_decode($response, true);

							if ($response == false || !isset($response['msg'])) continue;

							$result = Longman\TelegramBot\Request::sendMessage(['chat_id' => $chat_id, 'text' => $response['msg']]);
						}
					}



				} else {
					echo date('Y-m-d H:i:s', time()) . ' - Failed to fetch updates' . PHP_EOL;
					echo $serverResponse->printError();
				}
	}
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
	echo $e;
	// Log telegram errors
	Longman\TelegramBot\TelegramLog::error($e);
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
	// Catch log initilization errors
	echo $e;
}