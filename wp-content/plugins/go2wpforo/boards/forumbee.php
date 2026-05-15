<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class forumbee extends Board {
	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "users`" ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(DISTINCT `category.categoryName`) FROM `" . $this->db->prefix . "posts`" ) ) return $count;

		return 0;
	}

	public function get_topics_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "posts` WHERE `typeLabel` IN('article','question','discussion')" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "posts` WHERE `typeLabel` IN('article','question','discussion', 'reply')" ) ) return $count;

		return 0;
	}

	public function get_usergroups() {
		$sql = "SELECT DISTINCT `role` AS `name`
        FROM `" . $this->db->prefix . "users`
        ORDER BY `name`";
		if( $usergroups = $this->db->get_results( $sql, ARRAY_A ) ) {
			foreach( $usergroups as $k => $usergroup ) {
				$usergroups[ $k ]['groupid'] = go2wpf_str_to_int_id( $usergroup['name'] );
			}
		}

		return $usergroups;
	}

	public function get_users( $lastid = 0 ) {
		$sql = "SELECT  `userid` as old_id, 
                        `role` as groupid, 
                        `email` as user_login, 
                        `userKey` as user_nicename,
                        `name` as display_name, 
                        `email` as user_email, 
                        `joined` as user_registered, 
                        `joined` as online_time, 
                        `label` as title,
                        `profile.tagline` as about
		FROM `" . $this->db->prefix . "users`
		WHERE `userid` > $lastid
		ORDER BY old_id";
		if( $users = $this->db->get_results( $sql . $this->limit, ARRAY_A ) ) {
			foreach( $users as $k => $user ) {
				$users[ $k ]['groupid'] = go2wpf_str_to_int_id( $user['groupid'] );
			}
		}

		return $users;
	}

	public function get_forums() {
		$sql = "SELECT DISTINCT `category.categoryName` AS title
		FROM `" . $this->db->prefix . "posts`";
		if( $forums = $this->db->get_results( $sql, ARRAY_A ) ) {
			foreach( $forums as $k => $forum ) {
				$forums[ $k ]['old_id'] = go2wpf_str_to_int_id( $forum['title'] );
			}
		}

		return $forums;
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT  p.`topicid` AS old_id,
                        p.`id` AS old_postid,
                        p.`userid` AS userid,
                        p.`author.name` AS `name`,
                        p.`author.email` AS email,
                        p.`title` AS title, 
                        p.`text` AS body, 
                        p.`category.categoryName` AS forumid, 
                        p.`viewCount` AS views,
                        p.`posted` AS created 
		FROM `" . $this->db->prefix . "posts` p
		WHERE p.`id` > $lastid
		AND p.`typeLabel` IN('article','question','discussion')
		ORDER BY old_id";
		if( $topics = $this->db->get_results( $sql . $this->limit, ARRAY_A ) ) {
			foreach( $topics as $k => $topic ) {
				$topics[ $k ]['forumid'] = go2wpf_str_to_int_id( $topic['forumid'] );
			}
		}

		return $topics;
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT  p.`id` AS old_id,
                        p.`id` AS old_postid,
                        p.`parentid` AS parentid,
                        p.`userid` AS userid,
                        p.`author.name` AS `name`,
                        p.`author.email` AS email,
                        p.`title` AS title,
                        p.`text` AS body,
                        p.`topicid` AS topicid, 
                        p.`posted` AS created
		FROM `" . $this->db->prefix . "posts` p 
		WHERE p.`id` > $lastid
		AND p.`typeLabel` = 'reply'
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}
}

$this->forumbee = new forumbee();
