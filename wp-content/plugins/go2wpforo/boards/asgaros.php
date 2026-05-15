<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


class asgaros extends Board {
	private $topic_table = 'forum_threads';

	public function __construct() {
		parent::__construct();

		if( ! $this->db->get_var( "SHOW TABLES LIKE '" . $this->db->prefix . $this->topic_table . "'" ) ) {
			$this->topic_table = 'forum_topics';
		}

		$this->topic_table = $this->db->prefix . $this->topic_table;
	}

	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->users ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		if( $count = $this->db->get_var(
			"SELECT SUM(cnt) FROM (SELECT COUNT(*) AS cnt FROM `" . $this->db->term_taxonomy . "` WHERE `taxonomy` = 'asgarosforum-category'
			UNION
			SELECT COUNT(*) FROM `" . $this->db->prefix . "forum_forums`) AS tbl"
		) ) {
			return $count;
		}

		return 0;
	}

	public function get_topics_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->topic_table . "`" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "forum_posts`" ) ) return $count;

		return 0;
	}

	public function get_usergroups() {
		$sql = "SELECT DISTINCT `meta_value` AS `name` 
          FROM " . $this->db->usermeta . " 
          WHERE `meta_key` LIKE '" . $this->db->prefix . "capabilities'
          ORDER BY `name`";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_users( $lastid = 0 ) {
		$sql = "SELECT  u.`ID` as old_id,  
            u.`user_login` as user_login, 
            u.`display_name` as display_name, 
            u.`user_email` as user_email, 
            u.`user_registered` as user_registered, 
            u.`user_url` as site,
            um.`meta_value` as group_name
        FROM " . $this->db->users . " u
        LEFT JOIN " . $this->db->usermeta . " um 
            ON um.`meta_key` LIKE '" . $this->db->prefix . "capabilities'
            AND um.`user_id` = u.`ID`
        WHERE u.`ID` > $lastid
        ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_extracats() {
		$sql = "SELECT tt.`term_taxonomy_id` AS old_id, 
			t.`name` AS title,
			t.`slug` AS slug,
			tt.`description` AS description
		FROM `" . $this->db->term_taxonomy . "` tt
		INNER JOIN `" . $this->db->terms . "` t ON t.`term_id` = tt.`term_id`
		WHERE tt.`taxonomy` = 'asgarosforum-category'
		ORDER BY old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_forums() {
		$sql = "SELECT `id` AS old_id, 
			`name` AS title, 
			`description` AS description, 
			`parent_forum` AS parentid,
			`parent_id` AS extracat
		FROM `" . $this->db->prefix . "forum_forums`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT t.`id` AS old_id, 
			p.`author_id` AS userid, 
			t.`name` AS title,
			p.`text` AS body, 
			t.`parent_id` AS forumid, 
			p.`date` AS created 
		FROM `" . $this->topic_table . "` t
		INNER JOIN `" . $this->db->prefix . "forum_posts` p ON p.`parent_id` = t.`id` AND p.`id` = 
		( SELECT MIN(f.`id`) FROM `" . $this->db->prefix . "forum_posts` f WHERE f.`parent_id` = t.`id` )
		WHERE t.`id` > $lastid
		ORDER BY t.`id`";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT p.`id` AS old_id, 
			p.`author_id` AS userid, 
			CONCAT('RE: ', t.`name`) AS title, 
			p.`text` AS body, 
			t.`id` AS topicid, 
			p.`date` AS created
		FROM `" . $this->db->prefix . "forum_posts` p 
		INNER JOIN `" . $this->topic_table . "` t ON t.`id` = p.`parent_id`
		WHERE p.`id` != ( SELECT MIN(f.`id`) FROM `" . $this->db->prefix . "forum_posts` f WHERE f.`parent_id` = t.`id`  )
		AND p.`id` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

}

$this->asgaros = new asgaros();
