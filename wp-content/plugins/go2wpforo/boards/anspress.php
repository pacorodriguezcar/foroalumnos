<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


class anspress extends Board {
	public function get_users_count() {
		if( $count = (int) $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->users ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		return 1;
	}

	public function get_topics_count() {
		if( $count = (int) $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->posts . " WHERE post_status IN('publish', 'private') AND post_type LIKE 'question'" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = (int) $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->posts . " WHERE post_status IN('publish', 'private') AND post_type LIKE 'answer'" ) ) return $count + $this->get_topics_count();

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

	public function get_forums() {
		$sql = "SELECT 1 AS old_id, 'AnsPress Forum' AS title";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT p.ID AS old_id, 
				p.post_author AS userid, 
				p.post_title AS title, 
				p.post_name AS slug, 
				p.post_content AS body, 
				1 AS forumid, 
				p.post_date AS created 
			FROM " . $this->db->posts . " p
			WHERE p.ID > $lastid
				AND p.post_status IN('publish', 'private') 
				AND p.post_type = 'question'
			ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT p.`ID` AS old_id, 
			p.`post_author` AS userid, 
			CONCAT('Answer: ', p.`post_title`) AS title, 
			p.`post_content` AS body, 
			p.`post_parent` AS topicid, 
			p.`post_date` AS created
		FROM `" . $this->db->posts . "` p
		WHERE p.`ID` > $lastid
			AND p.post_status IN('publish', 'private') 
			AND p.`post_type` LIKE 'answer' 
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_comments( $lastid = 0 ) {
		$sql = "SELECT c.`comment_ID` AS old_id, 
			c.`user_id` AS userid, 
			CONCAT('Comment: ', p.`post_title`) AS title, 
			c.`comment_content` AS body, 
			IF(p.`post_type` = 'answer', p.`post_parent`, p.`ID`) AS topicid, 
			p.`ID` AS parentid,
			c.`comment_date` AS created
		FROM `" . $this->db->comments . "` c
		INNER JOIN `" . $this->db->posts . "` p ON p.`ID` = c.`comment_post_ID` AND p.`post_type` IN('question', 'answer')
		WHERE c.`comment_ID` > $lastid
			AND p.`post_status` IN('publish', 'private')
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}
}

$this->anspress = new anspress();
