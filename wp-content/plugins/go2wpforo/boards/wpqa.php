<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


class wpqa extends Board {
	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->users ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->term_taxonomy . "` WHERE `taxonomy` LIKE 'cma_category'" ) ) return $count;

		return 0;
	}

	public function get_topics_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->posts . " WHERE post_status IN('publish', 'private') AND post_type LIKE 'cma_thread'" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->comments . "` WHERE `comment_type` LIKE 'cma_answer'" ) ) return $count + $this->get_topics_count();

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
		$sql = "SELECT tt.`term_taxonomy_id` AS old_id, 
				t.`name` AS title, 
				t.`slug` AS slug, 
				tt.`description` AS description, 
				tt.`parent` AS parentid 
			FROM `" . $this->db->term_taxonomy . "` tt
			INNER JOIN `" . $this->db->terms . "` t ON t.`term_id` = tt.`term_id`
			WHERE tt.`taxonomy` LIKE 'cma_category'
			ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT p.ID AS old_id, 
				p.post_author AS userid, 
				p.post_title AS title, 
				p.post_name AS slug, 
				p.post_content AS body, 
				MIN(tr.`term_taxonomy_id`) AS forumid, 
				p.post_date AS created 
			FROM " . $this->db->posts . " p
			INNER JOIN `" . $this->db->term_relationships . "` tr ON tr.`object_id` = p.`ID`
			INNER JOIN `" . $this->db->term_taxonomy . "` tt ON tt.`term_taxonomy_id` = tr.`term_taxonomy_id` AND tt.`taxonomy` LIKE 'cma_category'
			WHERE p.ID > $lastid
				AND p.post_status IN('publish', 'private') 
				AND p.post_type = 'cma_thread'
			GROUP BY old_id
			ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT comment_ID AS old_id, 
				user_id AS userid, 
				CONCAT('Answer: ', p.`post_title`) AS title, 
				comment_content AS body, 
				comment_post_ID AS topicid, 
				comment_date AS created
			FROM `" . $this->db->comments . "` c
			INNER JOIN `" . $this->db->posts . "` p ON p.`ID` = c.`comment_post_ID`
			INNER JOIN `" . $this->db->term_relationships . "` tr ON tr.`object_id` = p.`ID`
			INNER JOIN `" . $this->db->term_taxonomy . "` tt ON tt.`term_taxonomy_id` = tr.`term_taxonomy_id` AND tt.`taxonomy` LIKE 'cma_category'
			WHERE comment_ID > $lastid
				AND comment_approved = '1'
				AND comment_type LIKE 'cma_answer' 
			ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

}

$this->wpqa = new wpqa();
