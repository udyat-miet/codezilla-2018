<?php

/////////////////////////////
//////// OUTPUT /////////////
/////////////////////////////


// {
//		"team_id" : "string"
//		"team_member" : {[
//			{ "name" : "string" }, // member 1
//			{ "name" : "string" } // member 2, if there is one
//		]}

// an email will also be sent to team members.
// Each team will be assigned a committee member for contact via Reply-To email.


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

function generate_response($code, $data) {
	return json_encode(
		array(
			'code' => $code,
			'data' => $data
		)
	);
}

$team = array(
	'name' => filter_input(INPUT_POST, "team_name", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH),
	'is_miet' => filter_input(INPUT_POST, "is_miet", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH),
	'email' => filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL)
);

fetch_member($team, 1);
fetch_member($team, 2);

if (filter_var($team['email'], FILTER_VALIDATE_EMAIL) === FALSE)
		die(generate_response(406, "Incorrect contact email!"));

if (empty($team['name']) === TRUE)
		die(generate_response(400, "Team name can not be left blank!"));

if (empty($team['is_miet']) === TRUE
	|| ($team['is_miet'] !== 'mietian' && $team['is_miet'] !== 'non_mietian'))
		die(generate_response(406, "Incorrect value for MIETian?"));

if (validate_member_details($team["member"][0]) === FALSE)
	die(generate_response(406, "Incomplete/incorrect details entered for Team Member #1!"));

if (validate_member_details($team["member"][1], FALSE) === FALSE)
	die(generate_response(406, "Incomplete/incorrect details entered for Team Member #2!"));

try {
	$db = new DatabaseManager();

	if ($team['is_miet'] === 'mietian') {
		$team['member'][1]['college'] =
			$team['member'][0]['college'] =
				"Meerut Institute of Engineering & Technology, Meerut";
	}

	$team['id'] = bin2hex(random_bytes(4));
	if ($db->insert($team) === FALSE)
		die(generate_response(500, "An unknown error occurred!"));

	echo generate_response(202, $team['id']);
} catch (DatabaseManagerExecption $e) {
	die(generate_response(500, "An internal server error occurred!"));
}