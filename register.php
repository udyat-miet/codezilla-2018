<?php

require_once 'inc/DatabaseManager.class.php';
require_once 'inc/lib/random_bytes_compat/random.php';

// i in { 1, 2 }
function fetch_member(&$team, $i) {
	$team['member'][] = array(
		'name' => filter_input(INPUT_POST, "member$i", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH),
		'codechef' => filter_input(INPUT_POST, "codechef$i", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH),
		'college' => filter_input(INPUT_POST, "college$i", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH),
		'roll' => filter_input(INPUT_POST, "roll$i", FILTER_SANITIZE_NUMBER_INT),
		'branch' => filter_input(INPUT_POST, "branch$i", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH),
		'year' => filter_input(INPUT_POST, "year$i", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH)
	);
}

function validate_member_details($team_member, $reqd = TRUE) {
	if ($reqd === FALSE) {
		if(empty($team_member['name']) === TRUE && empty($team_member['codechef']) === TRUE
			&& empty($team_member['college']) === TRUE && empty($team_member['branch']) === TRUE
			&& empty($team_member['roll']))
				return TRUE;
		else
			return FASLE;
	}
	
	if (empty($team_member['name']) === TRUE || empty($team_member['codechef']) === TRUE
		|| empty($team_member['college']) === TRUE || empty($team_member['branch']) === TRUE
		|| empty($team_member['year']) === TRUE
		|| filter_var($team_member['roll'], FILTER_VALIDATE_INT) === FALSE)
			return FALSE;

	return TRUE;
}

function format_response($code, $data) {
	return json_encode(
		array(
			'code' => $code,
			'data' => $data
		)
	);
}

function load_email_template($team) {
	$message = file_get_contents('inc/email.html.template');
	$message =
		str_replace('#leader_name', $team['member'][0]['name'],
			str_replace('#team_name', $team['name'],
				str_replace('#team_id', $team['id'], $message)));

	if (empty($team['member'][1]['name']) === FALSE) {
		$message = str_replace(
			'#members',
			$team['member'][0]['name'] . ", " . $team['member'][1]['name'],
			$message
		);
	} else {
		$message = str_replace('#members', $team['member'][0]['name'], $message);
	}

	if ($team['is_miet'] === 'mietian') {
		$message = 
			str_replace(
				'#timings',
				'<ul><li>24 March, 2018 2:00PM-5:00PM</li>'
					. '<li>31 March, 2018 1:00PM-4:00PM</li></ul>',
				$message
			);
	} else {
		$message = str_replace('#timings', '31 March, 2018 1:00PM-4:00PM', $message);
	}

	return $message;
}

$team = array(
	'name' => filter_input(INPUT_POST, "team_name", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH),
	'is_miet' => filter_input(INPUT_POST, "is_miet", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH),
	'email' => filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL),
	'phone' => filter_input(INPUT_POST, "phone", FILTER_SANITIZE_NUMBER_INT)
);

fetch_member($team, 1);
fetch_member($team, 2);

if (filter_var($team['email'], FILTER_VALIDATE_EMAIL) === FALSE)
		die(format_response(406, "Incorrect contact email!"));

if (filter_var($team['phone'], FILTER_VALIDATE_INT) === FALSE
	|| $team['phone'] < 1000000000) // less than 10-digits
		die(format_response(406, "Incorrect phone number!"));

if (empty($team['name']) === TRUE)
		die(format_response(400, "Team name can not be left blank!"));

if (empty($team['is_miet']) === TRUE
	|| ($team['is_miet'] !== 'mietian' && $team['is_miet'] !== 'non_mietian'))
		die(format_response(406, "Incorrect value for MIETian?"));

if (validate_member_details($team["member"][0]) === FALSE)
	die(format_response(406, "Incomplete/incorrect details entered for Team Member #1!"));

if (validate_member_details($team["member"][1], FALSE) === FALSE)
	die(format_response(406, "Incomplete/incorrect details entered for Team Member #2!"));

try {
	$db = new DatabaseManager();

	if ($team['is_miet'] === 'mietian') {
		$team['member'][1]['college'] =
			$team['member'][0]['college'] =
				"Meerut Institute of Engineering & Technology, Meerut";
	}

	$team['id'] = bin2hex(random_bytes(4));
	if ($db->insert($team) === FALSE)
		die(format_response(500, "An unknown error occurred!"));

	$from = $contact_emails[random_int(0, count($contact_emails) - 1)];
	mail(
		$team['email'],
		"Registration details for CodeZilla 2018 @ MIET, Meerut",
		load_email_template($team),
		"From: $from\r\n"
			. "Reply-To: $from\r\n"
			. "MIME-Version: 1.0\r\n"
			. "Content-Type: text/html; charset=ISO-8859-1\r\n"
	);
	echo format_response(202, $team['id']);
} catch (DatabaseManagerExecption $e) {
	die(format_response(500, "An internal server error occurred!"));
}
