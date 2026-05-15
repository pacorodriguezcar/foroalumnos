<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class xxxxx extends Board {
	public function __construct() {
		parent::__construct();
		WPF_GO2()->add_filter_to_all_text_contents( [ $this, 'specific_bb2normal' ], 9 );
		WPF_GO2()->add_filter_to_all_text_contents( [ $this, 'do_inline_attachs' ], 11 );
		WPF_GO2()->add_filter_to_posts_text_contents( [ $this, 'do_attachs' ], 12, 2 );
		add_filter( 'go2wpf_do_avatar_from', [ $this, 'filter_avatar_from' ], 10, 2 );
		add_filter( 'go2wpf_before_do_attach', [ $this, 'filter_attach' ] );
	}

	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "xxxxx`" ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "xxxxx`" ) ) return $count;

		return 0;
	}

	public function get_topics_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "xxxxx`" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "xxxxx`" ) ) return $count;

		return 0;
	}

	public function get_usergroups() {
		$sql = "SELECT `xxxxx` AS groupid,
                       `xxxxx` AS `name`
        FROM `" . $this->db->prefix . "xxxxx`
        ORDER BY `name`";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_users( $lastid = 0 ) {
		$sql = "SELECT  `xxxxx` as old_id, 
                        `xxxxx` as groupid, 
                        `xxxxx` as user_login, 
                        `xxxxx` as display_name, 
                        `xxxxx` as user_email, 
                        IF(`xxxxx`, FROM_UNIXTIME(`xxxxx`), '0000-00-00 00:00:00') as user_registered, 
                        IF(`xxxxx`, FROM_UNIXTIME(`xxxxx`), '0000-00-00 00:00:00') as online_time, 
                        `xxxxx` as avatar,
                        `xxxxx` as site,
                        `xxxxx` as icq,
                        `xxxxx` as aim,
                        `xxxxx` as msn,
                        `xxxxx` as yahoo,
                        `xxxxx` as facebook,
                        `xxxxx` as twitter,
                        `xxxxx` as gtalk,
                        `xxxxx` as skype,
                        `xxxxx` as signature,
                        `xxxxx` as about,
                        `xxxxx` as occupation,
                        `xxxxx` as location,
                        `xxxxx` as timezone
		FROM `" . $this->db->prefix . "xxxxx`
		WHERE `xxxxx` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_groupid( $userid ) {
		$sql = "SELECT `xxxxx` AS groupid 
			FROM `" . $this->db->prefix . "xxxxx` 
			WHERE `xxxxx` = " . wpforo_bigintval( $userid ) . "
			ORDER BY groupid = xxx__admin_id__xxx DESC, groupid ASC LIMIT 1";

		return $this->db->get_var( $sql );
	}

	public function get_secondary_groupids( $userid ) {
		$sql = "SELECT `xxxxx` AS groupid 
			FROM `" . $this->db->prefix . "xxxxx` 
			WHERE `xxxxx` = " . wpforo_bigintval( $userid );

		return $this->db->get_col( $sql );
	}

	public function get_extracats() {
		$sql = "SELECT  `xxxxx` AS old_id, 
                        `xxxxx` AS title,
                        `xxxxx` AS description, 
                        0       AS parentid 
		FROM `" . $this->db->prefix . "xxxxx`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_forums() {
		$sql = "SELECT  `xxxxx` AS old_id,
                        `xxxxx` AS title, 
                        `xxxxx` AS description, 
                        `xxxxx` AS extracat, 
                        `xxxxx` AS parentid
		FROM `" . $this->db->prefix . "xxxxx`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT  t.`xxxxx` AS old_id,
                        p.`xxxxx` AS old_postid,
                        p.`xxxxx` AS userid,
                        p.`xxxxx` AS `name`,
                        p.`xxxxx` AS email,
                        t.`xxxxx` AS title, 
                        p.`xxxxx` AS body, 
                        t.`xxxxx` AS forumid, 
                        t.`xxxxx` AS views,
                        t.`xxxxx` AS `type`,
                        IF(t.`xxxxx`, FROM_UNIXTIME(t.`xxxxx`), '0000-00-00 00:00:00') AS created 
		FROM `" . $this->db->prefix . "xxxxx` t
		INNER JOIN `" . $this->db->prefix . "xxxxx` p ON p.`xxx_postid_xxx` = t.`xxx_first_postid_xxx`
		WHERE t.`xxxxx` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT  p.`xxxxx` AS old_id,
                        p.`xxxxx` AS old_postid,
						p.`xxxxx` AS parentid,       
                        p.`xxxxx` AS userid,
                        p.`xxxxx` AS `name`,
                        p.`xxxxx` AS email,
                        IF(p.`xxxxx`, p.`xxxxx`, t.`xxxxx`) AS title,
                        p.`xxxxx` AS body,
                        p.`xxxxx` AS topicid, 
                        IF(p.`xxxxx`, FROM_UNIXTIME(p.`xxxxx`), '0000-00-00 00:00:00') AS created
		FROM `" . $this->db->prefix . "xxxxx` p 
		INNER JOIN `" . $this->db->prefix . "xxxxx` t ON t.`xxx_topicid_xxx` = p.`xxx_topicid_xxx` AND p.`xxx_postid_xxx` != t.`xxx_first_postid_xxx`
		WHERE p.`xxxxx` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_comments( $lastid = 0 ) {
		$sql = "SELECT  c.`xxxxx` AS old_id, 
                        c.`xxxxx` AS userid,
                        p.`xxxxx` AS `name`,
                        p.`xxxxx` AS email, 
                        CONCAT('Comment: ', p.`xxxxx`) AS title, 
                        c.`xxxxx` AS body, 
                        IF(p.`post_type` = 'answer', p.`xxx_parentid_xxx`, p.`xxx_postid_xxx`) AS topicid, 
                        p.`xxx_postid_xxx` AS parentid,
                        c.`xxxxx` AS created
		FROM `" . $this->db->prefix . "xxxxx` c
		INNER JOIN `" . $this->db->prefix . "xxxxx` p ON p.`xxx_postid_xxx` = c.`xxx_postid_xxx`
		WHERE c.`xxxxx` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	private function _attach_sql_select( $where ) {
		if( ! $where ) $where = '';
		$sql = "SELECT  `xxxxx` as attachid,   
                        `xxxxx` as postid, 
                        `xxxxx` as userid,
                        `xxxxx` as filename, 
                        `xxxxx` as hash_filename,
                        IF(`xxxxx`, `xxxxx`, '') as hash_thumb_filename,
                        `xxxxx` as ext
             FROM `" . $this->db->prefix . "xxxxx`" . $where;

		return $sql;
	}

	public function get_attach( $arg ) {
		if( ! $arg ) return null;
		$where = " WHERE " . ( is_numeric( $arg ) ? "`xxx_attachid_xxx` = %d" : "`xxx_filename_xxx` = %s" );
		$sql   = $this->_attach_sql_select( $where );

		return $this->db->get_row( $this->db->prepare( $sql, $arg ), ARRAY_A );
	}

	public function get_attachs( $args ) {
		if( ! $args ) return null;

		$wheres = [];
		if( ! empty( $args['postids_in'] ) && $postids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_in'] ) ) ) {
			$wheres[] = "`xxx_postid_xxx` IN(" . implode( ',', $postids_in ) . ")";
		}

		if( ! empty( $args['postids_not_in'] ) && $postids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_not_in'] ) ) ) {
			$wheres[] = "`xxx_postid_xxx` NOT IN(" . implode( ',', $postids_not_in ) . ")";
		}

		if( ! empty( $args['attachids_in'] ) && $attachids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_in'] ) ) ) {
			$wheres[] = "`xxx_attachid_xxx` IN(" . implode( ',', $attachids_in ) . ")";
		}

		if( ! empty( $args['attachids_not_in'] ) && $attachids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_not_in'] ) ) ) {
			$wheres[] = "`xxx_attachid_xxx` NOT IN(" . implode( ',', $attachids_not_in ) . ")";
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

	public function specific_bb2normal( $text ) {
		/*$text = preg_replace('#\[([^\:\[\]]+)\:[\w]+\]#isu', '[$1]', (string) $text);
		$text = preg_replace('#<\!--.*?-->#isu', '', (string) $text);*/
		return $text;
	}

	public function filter_avatar_from( $from, $user ) {
		/*if( !file_exists($from) && !empty($user['avatar']) ){
			$basename = preg_replace('#\?[^\?]*$#isu', '', basename( (string) $user['avatar']));
			$from = WPF_GO2()->sdirs_avatar . "/" . $basename;
		}*/
		return $from;
	}

	public function filter_attach( $attach ) {
		/*$dir = implode('/', str_split($attach['userid'], 1) );
		$attach['hash_filename'] = trim((string) $dir, '/') . '/' . $attach['hash_filename'];*/
		return $attach;
	}
}

$this->xxxxx = new xxxxx();
