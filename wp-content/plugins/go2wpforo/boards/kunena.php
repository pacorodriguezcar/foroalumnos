<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class kunena extends Board {
	public function __construct() {
		parent::__construct();
		WPF_GO2()->add_filter_to_all_text_contents( [ $this, 'do_inline_attachs' ], 11 );
		WPF_GO2()->add_filter_to_posts_text_contents( [ $this, 'do_attachs' ], 12, 2 );
		add_filter( 'go2wpf_do_avatar_from', [ $this, 'filter_avatar_from' ], 10, 2 );
	}

	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->base_prefix . "users`" ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "categories`" ) ) return $count;

		return 0;
	}

	public function get_topics_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "topics`" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "messages`" ) ) return $count;

		return 0;
	}

	public function get_usergroups() {
		$sql = "SELECT `id` AS groupid,
                       `title` AS `name`
        FROM `" . $this->db->base_prefix . "usergroups`
        ORDER BY `name`";

		if( ! $usergroups = $this->db->get_results( $sql, ARRAY_A ) ) {
			$sql        = "SELECT `id` AS groupid,
                       `name` AS `name`
            FROM `" . $this->db->base_prefix . "core_acl_aro_groups`
            ORDER BY `name`";
			$usergroups = $this->db->get_results( $sql, ARRAY_A );
		}

		return $usergroups;
	}

	public function get_users( $lastid = 0 ) {
		$sql = "SELECT  j.`id` as old_id, 
                        j.`username` as user_login, 
                        j.`name` as display_name, 
                        j.`email` as user_email, 
                        IF(j.`registerDate`,  j.`registerDate`,  '0000-00-00 00:00:00') as user_registered, 
                        IF(j.`lastvisitDate`, j.`lastvisitDate`, '0000-00-00 00:00:00') as online_time, 
                        k.`avatar` as avatar,
                        k.`websiteurl` as site,
                        k.`icq` as icq,
                        k.`yim` as yahoo,
                        k.`facebook` as facebook,
                        k.`twitter` as twitter,
                        k.`skype` as skype,
                        k.`signature` as signature,
                        k.`personalText` as about,
                        k.`location` as location
		FROM `" . $this->db->base_prefix . "users` j
		LEFT JOIN `" . $this->db->prefix . "users` k ON k.`userid` = j.`id`
		WHERE j.`id` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_groupid( $userid ) {
		$sql = "SELECT `group_id` AS groupid 
			FROM `" . $this->db->base_prefix . "user_usergroup_map` 
			WHERE `user_id` = " . wpforo_bigintval( $userid ) . "
			ORDER BY groupid = 8 DESC, groupid = 7 DESC, groupid ASC LIMIT 1";

		return $this->db->get_var( $sql );
	}

	public function get_secondary_groupids( $userid ) {
		$sql = "SELECT `group_id` AS groupid 
			FROM `" . $this->db->base_prefix . "user_usergroup_map` 
			WHERE `user_id` = " . wpforo_bigintval( $userid );

		return $this->db->get_col( $sql );
	}

	public function get_forums() {
		$sql = "SELECT  `id` AS old_id,
                        `name` AS title, 
                        `description` AS description, 
                        `parent_id` AS parentid
		FROM `" . $this->db->prefix . "categories`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT  t.`id` AS old_id,
                        p.`id` AS old_postid,
                        p.`userid` AS userid,
                        p.`name` AS `name`,
                        p.`email` AS email,
                        t.`subject` AS title, 
                        pt.`message` AS body, 
                        t.`category_id` AS forumid, 
                        t.`hits` AS views,
                        IF(p.`time`, FROM_UNIXTIME(p.`time`), '0000-00-00 00:00:00') AS created 
		FROM `" . $this->db->prefix . "topics` t
		INNER JOIN `" . $this->db->prefix . "messages` p ON p.`id` = t.`first_post_id`
		INNER JOIN `" . $this->db->prefix . "messages_text` pt ON pt.`mesid` = p.`id`
		WHERE t.`id` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT  p.`id` AS old_id,
                        p.`id` AS old_postid,
                        p.`parent` AS parentid,
                        p.`userid` AS userid,
                        p.`name` AS `name`,
                        p.`email` AS email,
                        IF(p.`subject`, p.`subject`, t.`subject`) AS title,
                        pt.`message` AS body,
                        p.`thread` AS topicid, 
                        IF(p.`time`, FROM_UNIXTIME(p.`time`), '0000-00-00 00:00:00') AS created
		FROM `" . $this->db->prefix . "messages` p 
		INNER JOIN `" . $this->db->prefix . "topics` t ON t.`id` = p.`thread` AND p.`id` != t.`first_post_id`
		INNER JOIN `" . $this->db->prefix . "messages_text` pt ON pt.`mesid` = p.`id`
		WHERE p.`id` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	private function _attach_sql_select( $where ) {
		if( ! $where ) $where = '';
		$sql = "SELECT  `id` as attachid,   
                        `mesid` as postid, 
                        `userid` as userid,
                        `filename` as filename, 
                        CONCAT( TRIM(BOTH '/' FROM REPLACE(`folder`, 'media/kunena/attachments/', '')), '/', `filename` ) as hash_filename,
                        '' as hash_thumb_filename
             FROM `" . $this->db->prefix . "attachments`" . $where;

		return $sql;
	}

	public function get_attach( $arg ) {
		if( ! $arg ) return null;
		$where = " WHERE " . ( is_numeric( $arg ) ? "`id` = %d" : "`filename` = %s" );
		$sql   = $this->_attach_sql_select( $where );

		return $this->db->get_row( $this->db->prepare( $sql, $arg ), ARRAY_A );
	}

	public function get_attachs( $args ) {
		if( ! $args ) return null;

		$wheres = [];
		if( ! empty( $args['postids_in'] ) && $postids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_in'] ) ) ) {
			$wheres[] = "`mesid` IN(" . implode( ',', $postids_in ) . ")";
		}

		if( ! empty( $args['postids_not_in'] ) && $postids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_not_in'] ) ) ) {
			$wheres[] = "`mesid` NOT IN(" . implode( ',', $postids_not_in ) . ")";
		}

		if( ! empty( $args['attachids_in'] ) && $attachids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_in'] ) ) ) {
			$wheres[] = "`id` IN(" . implode( ',', $attachids_in ) . ")";
		}

		if( ! empty( $args['attachids_not_in'] ) && $attachids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_not_in'] ) ) ) {
			$wheres[] = "`id` NOT IN(" . implode( ',', $attachids_not_in ) . ")";
		}

		if( $args != 'all' && ! $wheres ) return null;

		$where = ( $wheres ? " WHERE " . implode( ' AND ', $wheres ) : '' );
		$sql   = $this->_attach_sql_select( $where );

		return $this->db->get_results( $sql, ARRAY_A );
	}

	private function do_inline_attach( $match ) {
		$replacement = '<!-- ' . $match[0] . ' -->';

		if( WPF_GO2()->sdirs_attach ) {
			$arg = null;
			if( isset( $match['attachid'] ) && $match['attachid'] ) {
				$arg = $match['attachid'];
			} elseif( isset( $match['filename'] ) && $match['filename'] ) {
				$arg = $match['filename'];
			}

			if( $arg && $attach = $this->get_attach( $arg ) ) {
				if( $html = WPF_GO2()->do_attach( $attach ) ) $replacement = $html;
			}
		}

		return $replacement;
	}

	public function do_inline_attachs( $text ) {
		$pattern = '#\[attachment(?:[\r\n\t\s\0]*=?[\r\n\t\s\0]*(?<attachid>\d+)[\r\n\t\s\0]*|[\r\n\t\s\0]*)\][\r\n\t\s\0]*(?<filename>[^\[\]]+)[\r\n\t\s\0]*\[/attachment\]#isu';
		$text    = preg_replace_callback( $pattern, [ $this, 'do_inline_attach' ], (string) $text );

		return $text;
	}

	public function filter_avatar_from( $from, $user ) {
		if( ! file_exists( $from ) && ! empty( $user['avatar'] ) ) {
			$from = WPF_GO2()->sdirs_avatar . "/" . trim( (string) $user['avatar'], '/' );
		}

		return $from;
	}
}

$this->kunena = new kunena();
