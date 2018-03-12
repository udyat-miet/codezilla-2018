<?php

require_once 'config.php';

class DatabaseManager {

	function __construct() {
		$this->link = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		if ($this->link->connect_errno)
			throw new DatabaseManagerException($this->link->connect_error);
	}

	function insert($team) {
		$statement = $this->link->prepare(
			"INSERT INTO " . DB_TABLE . " VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);"
		);
		$statement->bind_param(
			"isssssisisssisi", $team['id'], $team['name'], $team['email'],
			$team['member'][0]['name'], $team['member'][0]['codechef'],
			$team['member'][0]['college'], $team['member'][0]['roll'],
			$team['member'][0]['branch'], $team['member'][0]['year'],
			$team['member'][1]['name'], $team['member'][1]['codechef'],
			$team['member'][1]['college'], $team['member'][1]['roll'],
			$team['member'][1]['branch'], $team['member'][1]['year']
		);
		return $statement->execute();
	}

	function __destruct() {
		$this->link->close();
	}
}

class DatabaseManagerException extends Exception { }