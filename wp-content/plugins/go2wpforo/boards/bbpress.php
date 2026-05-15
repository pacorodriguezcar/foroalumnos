<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


class bbpress extends Board {
	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->users ) ) return $count;
		
		return 0;
	}
	
	public function get_forums_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->posts . " WHERE post_status IN('publish', 'private', 'hidden', 'closed') AND post_type = 'forum'" ) ) return $count;
		
		return 0;
	}
	
	public function get_topics_count() {
		if( $count = $this->db->get_var(
			"SELECT COUNT(*) FROM " . $this->db->posts . " WHERE post_status IN('publish', 'private', 'hidden', 'closed') AND post_parent <> 0 AND post_type = 'topic'"
		) ) {
			return $count;
		}
		
		return 0;
	}
	
	public function get_posts_count() {
		if( $count = $this->db->get_var(
			"SELECT COUNT(*) FROM " . $this->db->posts . " WHERE post_status IN('publish', 'private', 'hidden', 'closed') AND post_parent <> 0 AND post_type IN('topic', 'reply')"
		) ) {
			return $count;
		}
		
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
		$sql = "SELECT ID AS old_id,
				post_title AS title,
				post_name AS slug,
				post_content AS description,
				post_parent AS parentid
			FROM " . $this->db->posts . "
			WHERE post_status IN('publish', 'private', 'hidden', 'closed')
				AND post_type = 'forum'
			ORDER BY post_parent, ID";
		
		return $this->db->get_results( $sql, ARRAY_A );
	}
	
	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT ID AS old_id,
				post_author AS userid,
				post_title AS title,
				post_name AS slug,
				post_content AS body,
				post_parent AS forumid,
				post_date AS created,
				IF(post_status = 'private', 1, 0) AS private
			FROM " . $this->db->posts . "
			WHERE ID > $lastid
				AND post_status IN('publish', 'private', 'hidden', 'closed')
				AND post_parent <> 0
				AND post_type = 'topic'
			ORDER BY 1";
		
		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}
	
	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT ID AS old_id,
				post_author AS userid,
				post_title AS title,
				post_content AS body,
				post_parent AS topicid,
				post_date AS created
			FROM " . $this->db->posts . "
			WHERE ID > $lastid
				AND post_status IN('publish', 'private', 'hidden', 'closed')
				AND post_parent <> 0
				AND post_type = 'reply'
			ORDER BY 1";
		
		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}
	
	public function get_sticky_topicids() {
		$sticky_topicids = [];
		$sql             = "SELECT meta_value AS srlzs FROM " . $this->db->postmeta . " WHERE meta_key = '_bbp_sticky_topics'";
		if( $srlzs = $this->db->get_col( $sql ) ) {
			foreach( $srlzs as $srlz ) {
				if( ( $_srlz = unserialize( $srlz ) ) && is_array( $_srlz ) ) {
					$sticky_topicids = array_merge( $sticky_topicids, $_srlz );
				}
			}
		}
		
		return $sticky_topicids;
	}
	
}

$this->bbpress = new bbpress();
