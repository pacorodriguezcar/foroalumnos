<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class simplepress extends Board {
	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->users ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		if( $count = $this->db->get_var(
			"SELECT SUM(cnt) FROM (SELECT COUNT(*) AS cnt FROM `" . $this->db->prefix . "sfgroups`
			UNION
			SELECT COUNT(*) FROM `" . $this->db->prefix . "sfforums`) AS tbl"
		) ) {
			return $count;
		}

		return 0;
	}

	public function get_topics_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "sftopics`" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "sfposts`" ) ) return $count;

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
            sfm.`display_name` as display_name, 
            u.`user_email` as user_email, 
            u.`user_registered` as user_registered, 
            u.`user_url` as site,
            um.`meta_value` as group_name,
            sfm.`signature` as signature
        FROM " . $this->db->users . " u
        LEFT JOIN " . $this->db->usermeta . " um 
            ON um.`meta_key` LIKE '" . $this->db->prefix . "capabilities'
            AND um.`user_id` = u.`ID`
        LEFT JOIN " . $this->db->prefix . "sfmembers sfm
            ON sfm.`user_id` = u.`ID`
        WHERE u.`ID` > $lastid
        ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_extracats() {
		$sql = "SELECT `group_id` AS old_id, 
			`group_name` AS title,
			`group_desc` AS description, 
			0 AS parentid 
		FROM `" . $this->db->prefix . "sfgroups`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_forums() {
		$sql = "SELECT `forum_id` AS old_id, 
			`forum_name` AS title, 
			`forum_slug` AS slug, 
			`forum_desc` AS description, 
			`parent` AS parentid,
			`group_id` AS extracat
		FROM `" . $this->db->prefix . "sfforums`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT t.`topic_id` AS old_id, 
			t.`user_id` AS userid, 
			t.`topic_name` AS title, 
			t.`topic_slug` AS slug, 
			p.`post_content` AS body, 
			t.`forum_id` AS forumid, 
			t.`topic_date` AS created 
		FROM `" . $this->db->prefix . "sftopics` t
		INNER JOIN `" . $this->db->prefix . "sfposts` p ON p.`topic_id` = t.`topic_id` AND p.`post_id` = 
		( SELECT MIN(f.`post_id`) FROM `" . $this->db->prefix . "sfposts` f WHERE f.`topic_id` = t.`topic_id` )
		WHERE t.`topic_id` > $lastid
		ORDER BY t.`topic_id`";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT p.`post_id` AS old_id, 
			p.`user_id` AS userid, 
			CONCAT('RE: ', t.`topic_name`) AS title, 
			p.`post_content` AS body, 
			p.`topic_id` AS topicid, 
			p.`post_date` AS created
		FROM `" . $this->db->prefix . "sfposts` p 
		INNER JOIN `" . $this->db->prefix . "sftopics` t ON t.`topic_id` = p.`topic_id`
		WHERE p.`post_id` != ( SELECT MIN(f.`post_id`) FROM `" . $this->db->prefix . "sfposts` f WHERE f.`topic_id` = p.`topic_id`  )
		AND p.`post_id` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

}

$this->simplepress = new simplepress();
