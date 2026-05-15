<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class mybb extends Board {
	public function __construct() {
		parent::__construct();
		WPF_GO2()->add_filter_to_all_text_contents( [ $this, 'do_inline_attachs' ], 11 );
		WPF_GO2()->add_filter_to_posts_text_contents( [ $this, 'do_attachs' ], 12, 2 );
		add_filter( 'go2wpf_do_avatar_from', [ $this, 'filter_avatar_from' ], 10, 2 );
	}

	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->prefix . "users" ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "forums`" ) ) return $count;

		return 0;
	}

	public function get_topics_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "threads`" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "posts`" ) ) return $count;

		return 0;
	}

	public function get_usergroups() {
		$sql = "SELECT `gid` AS groupid,
          `title` AS `name`
        FROM `" . $this->db->prefix . "usergroups`
        ORDER BY `name`";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_users( $lastid = 0 ) {
		$sql = "SELECT `uid` as old_id, 
            `usergroup` as groupid, 
            `username` as user_login, 
            `username` as display_name, 
            `email` as user_email, 
            IF(`regdate`, FROM_UNIXTIME(`regdate`), '0000-00-00 00:00:00') as user_registered, 
            IF(`lastactive`, FROM_UNIXTIME(`lastactive`), '0000-00-00 00:00:00') as online_time, 
            `avatar` as avatar,
            `signature` as signature,
            `website` as site,
            `skype` as skype
		FROM `" . $this->db->prefix . "users`
		WHERE `uid` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_forums() {
		$sql = "SELECT `fid` AS old_id, 
			`name` AS title, 
			`description` AS description, 
			`pid` AS parentid
		FROM `" . $this->db->prefix . "forums`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT t.`tid` AS old_id,
		    p.`pid` AS old_postid,
			p.`uid` AS userid, 
			t.`subject` AS title, 
			p.`message` AS body, 
			t.`fid` AS forumid, 
			t.`views` AS views,
			t.`sticky` AS `type`,
			IF(t.`dateline`, FROM_UNIXTIME(t.`dateline`), '0000-00-00 00:00:00') AS created 
		FROM `" . $this->db->prefix . "threads` t
		INNER JOIN `" . $this->db->prefix . "posts` p ON p.`pid` = t.`firstpost`
		WHERE t.`tid` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT p.`pid` AS old_id, 
		    p.`pid` AS old_postid,
			p.`uid` AS userid,
			IF(p.`subject`, p.`subject`, t.`subject`) AS title,
			p.`message` AS body, 
			p.`tid` AS topicid, 
			IF(p.`dateline`, FROM_UNIXTIME(p.`dateline`), '0000-00-00 00:00:00') AS created
		FROM `" . $this->db->prefix . "posts` p 
		INNER JOIN `" . $this->db->prefix . "threads` t ON t.`tid` = p.`tid` AND p.`pid` != t.`firstpost`
		WHERE p.`pid` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	private function _attach_sql_select( $where ) {
		if( ! $where ) $where = '';
		$sql = "SELECT `aid` as attachid,   
            `pid` as postid, 
            `uid` as userid,
            `filename` as `filename`, 
            `attachname` as hash_filename,
            if(`thumbnail`, `thumbnail`, '') as hash_thumb_filename
             FROM `" . $this->db->prefix . "attachments`" . $where;

		return $sql;
	}

	public function get_attach( $arg ) {
		if( ! $arg ) return null;
		$where = " WHERE " . ( is_numeric( $arg ) ? "`aid` = %d" : "`filename` = %s" );
		$sql   = $this->_attach_sql_select( $where );

		return $this->db->get_row( $this->db->prepare( $sql, $arg ), ARRAY_A );
	}

	public function get_attachs( $args ) {
		if( ! $args ) return null;

		$wheres = [];
		if( ! empty( $args['postids_in'] ) && $postids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_in'] ) ) ) {
			$wheres[] = "`pid` IN(" . implode( ',', $postids_in ) . ")";
		}

		if( ! empty( $args['postids_not_in'] ) && $postids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_not_in'] ) ) ) {
			$wheres[] = "`pid` NOT IN(" . implode( ',', $postids_not_in ) . ")";
		}

		if( ! empty( $args['attachids_in'] ) && $attachids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_in'] ) ) ) {
			$wheres[] = "`aid` IN(" . implode( ',', $attachids_in ) . ")";
		}

		if( ! empty( $args['attachids_not_in'] ) && $attachids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_not_in'] ) ) ) {
			$wheres[] = "`aid` NOT IN(" . implode( ',', $attachids_not_in ) . ")";
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
			if( isset( $match['attachid'] ) && $match['attachid'] ) $arg = $match['attachid'];

			if( $arg && $attach = $this->get_attach( $arg ) ) {
				if( $html = WPF_GO2()->do_attach( $attach ) ) $replacement = $html;
			}
		}

		return $replacement;
	}

	public function do_inline_attachs( $text ) {
		$pattern = '#\[attachment[\r\n\t\s\0]*=[\r\n\t\s\0]*(?<attachid>\d+)[^\[\]]*\]#isu';
		$text    = preg_replace_callback( $pattern, [ $this, 'do_inline_attach' ], (string) $text );

		return $text;
	}

	public function filter_avatar_from( $from, $user ) {
		if( ! file_exists( $from ) && ! empty( $user['avatar'] ) ) {
			$basename = preg_replace( '#\?[^\?]*$#isu', '', basename( (string) $user['avatar'] ) );
			$from     = WPF_GO2()->sdirs_avatar . "/" . $basename;
		}

		return $from;
	}
}

$this->mybb = new mybb();
