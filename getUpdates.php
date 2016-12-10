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

// Add you bot's API key and name
$API_KEY = '308804586:AAF4wou8LjZp_DOCEzd7KRr3Dt7P6qr7w4c';
$BOT_NAME = 'MarishkoBot';

$MARISHKO_API_URL = 'http://localhost:4567/';
// Define a path for your custom commands
//$commands_path = __DIR__ . '/Commands/';

// Enter your MySQL database credentials
$mysql_credentials = [
	'host'     => 'localhost',
	'user'     => 'skyscream',
	'password' => 'bountykiller',
	'database' => 'ai_telegram',
];

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

	// Handle telegram getUpdates request
	$serverResponse = $telegram->handleGetUpdates();

	if ($serverResponse->isOk()) {
		$result = $serverResponse->getResult();
		$updateCount = count($serverResponse->getResult());
		//echo date('Y-m-d H:i:s', time()) . ' - Processed ' . $updateCount . ' updates';
		foreach ($result as $item) {
			var_dump($item);
echo PHP_EOL.PHP_EOL;
			if (!isset($item->message['text'])) return;

			$userName = $item->message['from']['first_name'].'_'.$item->message['from']['username'].'_'.$item->message['from']['last_name'].'_'.$item->message['from']['id'];
			$message = $item->message['text'];
			$chat_id = $item->message['chat']['id'];

			$message = str_replace('@'.$BOT_NAME, '', $message);
			$message = trim($message);
			Longman\TelegramBot\Request::sendChatAction(['chat_id' => $chat_id, 'action' => 'typing']);
var_dump($message);
			$myCurl = curl_init();
			curl_setopt_array($myCurl, array(
				CURLOPT_URL => $MARISHKO_API_URL.'0.1/getAnswer/',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => http_build_query(array('userName'=>$userName, 'phrase'=>$message))
			));
			$response = curl_exec($myCurl);
			curl_close($myCurl);

			$response = json_decode($response, true);
			var_dump($response);
			if ($response == false || !isset($response['answer'])) return;

			echo PHP_EOL.$userName.':'.$message.PHP_EOL;
			echo 'bot:'.$response['answer'].PHP_EOL;

			$result = Longman\TelegramBot\Request::sendMessage(['chat_id' => $chat_id, 'text' => $response['answer']]);

		}


	} else {
		echo date('Y-m-d H:i:s', time()) . ' - Failed to fetch updates' . PHP_EOL;
		echo $serverResponse->printError();
	}
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
	echo $e;
	// Log telegram errors
	Longman\TelegramBot\TelegramLog::error($e);
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
	// Catch log initilization errors
	echo $e;
}