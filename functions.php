<?php
function logging($userid, $name, $text) {
	include 'config.php';
	//Only runs if logging is set to true
	if ($log) {
		//Makes logging directory if it doesnt exits and chmods it
		if (!is_dir($logdir)) {
			mkdir($logdir, $logdirchmod);
		}
		file_put_contents($logdir . '/' . $logfile, "$userid($name): $text\n", FILE_APPEND);
	}
}

function basic_response($text, $name, $userid) {
	$responses = read_array('responses.php');
	foreach ($responses as $element) {
		if (stripos($text, $element[0]) !== FALSE) {
			$message = $element[1];
			$message = str_replace('%n', $name, $message);
			$message = str_replace('%u', $userid, $message);
			send($message);
		}
	}
}

function weather_response($text) {
	include 'config.php';
	if (stripos($text, 'weather') !== FALSE) {
		if (isset($wutoken) && isset($wuloc)) {
			$rawweather = json_decode(file_get_contents("https://api.wunderground.com/api/$wutoken/conditions/q/$wuloc.json"));
			$temperature = $rawweather->current_observation->feelslike_string;
			$weather = $rawweather->current_observation->weather;
			$icon = $rawweather->current_observation->icon_url;
			$forecast = "The weather is $weather with a temperature of $temperature";
			send_img($forecast, $icon);
		} else {
			send('WUnderground token and location are not set');
		}
	}
}

function curl_post($postfields) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.groupme.com/v3/bots/post');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_exec($ch);
	curl_close($ch);
}

function send($message) {
	include 'config.php';
	$postdata = [
		'bot_id' => $bottoken,
		'text' => $message
	];
	curl_post($postdata);
}

function send_img($message, $image) {
	include 'config.php';
	$attachments = [
		'type' => 'image',
		'url' => $image
	];
	$postdata = [
		'bot_id' => $bottoken,
		'text' => $message,
		'attachments' => [$attachments]
	];
	curl_post($postdata);
}

function store_array($array, $file) {
	$array = json_encode($array);
	file_put_contents($file, "<?php\n" . $array);
}

function read_array($file) {
	$array = file_get_contents($file);
	$array = str_replace('<?php', null, $array);
	$array = json_decode($array);
	return $array;
}

function get_bot_group() {
	include 'config.php';
	$bots = json_decode(file_get_contents("https://api.groupme.com/v3/bots?token=$apitoken"));
	foreach($bots->response as $element) {
		if ($element->bot_id == $bottoken) {
			return $element->group_id;
		}
	}
}

function get_user_id($name) {
	include 'config.php';
	$user_id = 'No member with that name found';
	$groupid = get_bot_group();
	$groups = json_decode(file_get_contents("https://api.groupme.com/v3/groups?token=$apitoken"));
	foreach($groups->response as $element) {
		if ($element->id == $groupid) {
			foreach($element->members as $member) {
				if (stripos($member->nickname, $name) !== FALSE) {
					$user_id = $member->user_id;
				}
			}
		}
	}
	return $user_id;
}

function get_name($userid) {
	include 'config.php';
	$name = 'Invalid userid';
	$groupid = get_bot_group();
	$groups = json_decode(file_get_contents("https://api.groupme.com/v3/groups?token=$apitoken"));
	foreach($groups->response as $element) {
		if ($element->id == $groupid) {
			foreach($element->members as $member) {
				if ($userid == $member->user_id) {
					$name = $member->nickname;
				}
			}
		}
	}
	return $name;
}

function parse_cmd($command) {
	$command = explode('-"', $command);
	array_splice($command, 0, 1);
	if (count($command) > 1) {
		$command[0] = substr($command[0], 0, strlen($command[0]) -2);
		$command[1] = substr($command[1], 0, strlen($command[1]) -1);
	} elseif (count($command) == 1) {
		$command[0] = substr($command[0], 0, strlen($command[0]) -1);
	}
	return $command;
}

function disp_help() {
	$help = <<<'EOHELP'
		'/help' displays this message
		'/responses' displays all current responses
		'/addresponse -"find" -"respond"' adds a response to the "find" phrase %n = name, %u = userid
		'/delresponse -"find"' deletes a response for phrase "find"
		'/admins' displays all current admins
		'/getuserid -"name"' displays user id of a member of the group
		'/addadmin -"userid" adds the specified user ID to the admin list
		'/deladmin -"userid" adds the specified user ID to the admin list
EOHELP;
	send($help);
}

function list_responses() {
	$message = null;
	$responses = read_array('responses.php');
	foreach($responses as $element) {
		$message .= "$element[0] -> $element[1]\n";
	}
	send($message);
}

function search_responses($needle) {
	$responses = read_array('responses.php');
	$counter = 0;
	$position = false;
	foreach($responses as $element) {
		if (stripos($element[0], $needle) !== FALSE || stripos($needle, $element[0]) !== FALSE) {
			$position = $counter;
		}
	$counter++;
	}
	return $position;
}

function add_response($find, $response) {
	$responses = read_array('responses.php');
	$message = "Something bad happened :(";
	if (search_responses($find) !== FALSE) {
		$message = "There is already a similar response for $find";
	} else {
		$responses[count($responses)] = [$find, $response];
		store_array($responses, 'responses.php');
		$message = "Added response $find -> $response";
	}
	return $message;
}

function del_response($find) {
	$responses = read_array('responses.php');
	$message = "Something bad happened :(";
	if (search_responses($find) !== FALSE) {
		array_splice($responses, search_responses($find), 1);
		store_array($responses, 'responses.php');
		$message = "Deleted response for $find";
	} else {
		$message = "There is not a response for $find, nothing to delete";
	}
	return $message;
}

function list_admins() {
	$message = null;
	$admins = read_array('admins.php');
	foreach($admins as $element) {
		$name = get_name($element);
		$message .= "$element($name)\n";
	}
	send($message);
}

function add_admin($userid) {
	$admins = read_array('admins.php');
	$message = "Something bad happened :(";
	$name = get_name($userid);
	if (!in_array($userid, $admins)) {
		if ($name !== 'Invalid userid') {
			$admins[count($admins)] = $userid;
			store_array($admins, 'admins.php');
			$message = "$userid($name) has beed added to the admin list";
		} else {
			$message = "No member associated with User ID \"$userid\" is in the group";
		}
	} else {
		$message = "$userid($name) is already an admin";
	}
	return $message;
}

function del_admin($userid) {
	$admins = read_array('admins.php');
	$message = "Something bad happened :(";
	$name = get_name($userid);
	if (in_array($userid, $admins)) {
		array_splice($admins, array_search($userid, $admins), 1);
		$message = "$userid($name) was removed from the admin list";
		store_array($admins, 'admins.php');
	} else {
		$message = "$userid($name) is not an admin";
	}
	return $message;
}
