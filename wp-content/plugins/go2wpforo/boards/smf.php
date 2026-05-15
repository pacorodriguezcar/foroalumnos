<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class smf extends Board {
	public function __construct() {
		parent::__construct();
		WPF_GO2()->add_filter_to_all_text_contents( [ $this, 'specific_bb2normal' ], 9 );
		WPF_GO2()->add_filter_to_posts_text_contents( [ $this, 'do_attachs' ], 12, 2 );
		add_filter( 'go2wpf_do_avatar_from', [ $this, 'filter_avatar_from' ], 10, 2 );
		add_filter( 'go2wpf_before_do_attach', [ $this, 'filter_attach' ] );
	}

	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "members`" ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		if( $count = $this->db->get_var(
			"SELECT SUM(cnt) FROM (
                                          SELECT COUNT(*) AS cnt FROM `" . $this->db->prefix . "categories`
			                                UNION
			                              SELECT COUNT(*) FROM `" . $this->db->prefix . "boards`
			                            ) AS tbl"
		) ) {
			return $count;
		}

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
		$sql = "SELECT `id_group` AS groupid,
                       `group_name` AS `name`
        FROM `" . $this->db->prefix . "membergroups`
        ORDER BY `name`";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_users( $lastid = 0 ) {
		$sql = "SELECT  `id_member` as old_id, 
                        `id_group` as groupid, 
                        `member_name` as user_login, 
                        `real_name` as display_name, 
                        `email_address` as user_email, 
                        IF(`date_registered`, FROM_UNIXTIME(`date_registered`), '0000-00-00 00:00:00') as user_registered, 
                        IF(`last_login`, FROM_UNIXTIME(`last_login`), '0000-00-00 00:00:00') as online_time, 
                        IF(`avatar`, `avatar`, 'custom') AS avatar,
                        `website_url` AS site,
                        `signature` AS signature,
                        `personal_text` AS about
		FROM `" . $this->db->prefix . "members`
		WHERE `id_member` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_extracats() {
		$sql = "SELECT  `id_cat`  AS old_id, 
                        `name`    AS title,
                        ''        AS description, 
                        0         AS parentid 
		FROM `" . $this->db->prefix . "categories`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_forums() {
		$sql = "SELECT  `id_board` AS old_id, 
                        `name` AS title, 
                        `description` AS description,
                        `id_cat` AS extracat,
                        `id_parent` AS parentid
		FROM `" . $this->db->prefix . "boards`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT  t.`id_topic` AS old_id,
                        p.`id_msg` AS old_postid,
                        p.`id_member` AS userid, 
                        p.`poster_name` AS `name`,
                        p.`poster_email` AS email,
                        p.`subject` AS title, 
                        p.`body` AS body, 
                        t.`id_board` AS forumid, 
                        t.`num_views` AS views,
                        t.`is_sticky` AS `type`,
                        IF(p.`poster_time`, FROM_UNIXTIME(p.`poster_time`), '0000-00-00 00:00:00') AS created 
		FROM `" . $this->db->prefix . "topics` t
		INNER JOIN `" . $this->db->prefix . "messages` p ON p.`id_msg` = t.`id_first_msg`
		WHERE t.`id_topic` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT  p.`id_msg` AS old_id,
                        p.`id_msg` AS old_postid,
                        p.`id_member` AS userid,
                        p.`poster_name` AS `name`,
                        p.`poster_email` AS email,
                        p.`subject` AS title,
                        p.`body` AS body,
                        p.`id_topic` AS topicid, 
                        IF(p.`poster_time`, FROM_UNIXTIME(p.`poster_time`), '0000-00-00 00:00:00') AS created
		FROM `" . $this->db->prefix . "messages` p 
		INNER JOIN `" . $this->db->prefix . "topics` t ON t.`id_topic` = p.`id_topic` AND p.`id_msg` != t.`id_first_msg`
		WHERE p.`id_msg` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	private function _attach_sql_select( $where ) {
		if( ! $where ) $where = '';
		$sql = "SELECT  a.`id_attach` AS attachid,   
                        a.`id_msg` AS postid, 
                        a.`id_member` AS userid,
                        a.`filename` AS filename, 
                        CONCAT(a.`id_attach`, '_', a.`file_hash`) AS hash_filename,
                        IF(a.`id_thumb`, CONCAT(t.`id_attach`, '_', t.`file_hash`), '') AS hash_thumb_filename,
                        a.`fileext` AS ext
        FROM `" . $this->db->prefix . "attachments` a
        LEFT JOIN `" . $this->db->prefix . "attachments` t ON t.`id_attach` = a.`id_thumb`
        WHERE a.`filename` NOT LIKE '%.%_thumb'" . $where;

		return $sql;
	}

	public function get_attach( $arg ) {
		if( ! $arg ) return null;
		$where = " AND " . ( is_numeric( $arg ) ? "a.`id_attach` = %d" : "a.`filename` = %s" );
		$sql   = $this->_attach_sql_select( $where );

		return $this->db->get_row( $this->db->prepare( $sql, $arg ), ARRAY_A );
	}

	public function get_attachs( $args ) {
		if( ! $args ) return null;

		$wheres = [];
		if( ! empty( $args['postids_in'] ) && $postids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_in'] ) ) ) {
			$wheres[] = "a.`id_msg` IN(" . implode( ',', $postids_in ) . ")";
		}

		if( ! empty( $args['postids_not_in'] ) && $postids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_not_in'] ) ) ) {
			$wheres[] = "a.`id_msg` NOT IN(" . implode( ',', $postids_not_in ) . ")";
		}

		if( ! empty( $args['attachids_in'] ) && $attachids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_in'] ) ) ) {
			$wheres[] = "a.`id_attach` IN(" . implode( ',', $attachids_in ) . ")";
		}

		if( ! empty( $args['attachids_not_in'] ) && $attachids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_not_in'] ) ) ) {
			$wheres[] = "a.`id_attach` NOT IN(" . implode( ',', $attachids_not_in ) . ")";
		}

		if( $args != 'all' && ! $wheres ) return null;

		$where = ( $wheres ? " AND " . implode( ' AND ', $wheres ) : '' );
		$sql   = $this->_attach_sql_select( $where );

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function specific_bb2normal( $text ) {
		$text = preg_replace( '#\[/?attachment[^\[\]]*?]#iu', '', (string) $text );

		return $text;
	}

	public function filter_avatar_from( $from, $user ) {
		if( ! file_exists( $from ) ) {
			if( $user['avatar'] != 'custom' ) {
				$basename = trim( (string) $user['avatar'], '/' );
				$from     = WPF_GO2()->sdirs_avatar . "/" . $basename;
			} else {
				$avatar = $this->db->get_row( "SELECT * FROM `" . $this->db->prefix . "attachments` WHERE `id_member` = " . intval( $user['old_id'] ) . " AND `filename` LIKE 'avatar_" . intval( $user['old_id'] ) . "_%'", ARRAY_A );
				if( ! empty( $avatar ) && isset( $avatar['file_hash'] ) ) {
					$file = WPF_GO2()->sdirs_avatar . "/" . $avatar['id_attach'] . '_' . $avatar['file_hash'];
					if( file_exists( $file ) ) return $file;
				}
				$from = WPF_GO2()->sdirs_avatar . "/custom_avatar/custom_avatar/avatar_" . $user['old_id'];
				if( file_exists( $from . '.jpg' ) ) {
					return $from . '.jpg';
				} elseif( file_exists( $from . '.jpeg' ) ) {
					return $from . '.jpeg';
				} elseif( file_exists( $from . '.png' ) ) {
					return $from . '.png';
				} elseif( file_exists( $from . '.gif' ) ) {
					return $from . '.gif';
				} elseif( file_exists( $from . '.JPG' ) ) {
					return $from . '.JPG';
				} elseif( file_exists( $from . '.JPEG' ) ) {
					return $from . '.JPEG';
				} elseif( file_exists( $from . '.PNG' ) ) {
					return $from . '.PNG';
				} elseif( file_exists( $from . '.GIF' ) ) {
					return $from . '.GIF';
				}
			}
		}

		return $from;
	}

	public function filter_attach( $attach ) {
		if( ! empty( $attach['hash_filename'] ) ) {
			$from = go2wpf_fix_directory( WPF_GO2()->sdirs_attach . "/" . $attach['hash_filename'] );
			if( ! file_exists( $from ) ) $attach['hash_filename'] .= '.dat';
		}
		if( ! empty( $attach['hash_thumb_filename'] ) ) {
			$from_thumb = go2wpf_fix_directory( WPF_GO2()->sdirs_attach . "/" . $attach['hash_thumb_filename'] );
			if( ! file_exists( $from_thumb ) ) $attach['hash_thumb_filename'] .= '.dat';
		}

		return $attach;
	}
}

$this->smf = new smf();
