<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class vbulletin_4 extends Board {
	public function __construct() {
		parent::__construct();
		WPF_GO2()->add_filter_to_all_text_contents( [ $this, 'specific_bb2normal' ], 9 );
		WPF_GO2()->add_filter_to_all_text_contents( [ $this, 'do_inline_attachs' ], 11 );
		WPF_GO2()->add_filter_to_posts_text_contents( [ $this, 'do_attachs' ], 12, 2 );
		add_filter( 'go2wpf_do_avatar_from', [ $this, 'filter_avatar_from' ] );
		add_filter( 'go2wpf_before_do_attach', [ $this, 'filter_attach' ] );
	}

	public function get_users_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM " . $this->db->prefix . "user" ) ) return $count;

		return 0;
	}

	public function get_forums_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "forum`" ) ) return $count;

		return 0;
	}

	public function get_topics_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "thread`" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "post`" ) ) return $count;

		return 0;
	}

	public function get_usergroups() {
		$sql = "SELECT `usergroupid` AS groupid,
          `title` AS `name`
        FROM `" . $this->db->prefix . "usergroup`
        ORDER BY `name`";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_users( $lastid = 0 ) {
		$sql = "SELECT u.`userid` as old_id, 
            u.`usergroupid` as groupid, 
            u.`username` as user_login, 
            u.`username` as display_name, 
            u.`email` as user_email, 
            IF(u.`joindate`, FROM_UNIXTIME(u.`joindate`), '0000-00-00 00:00:00') as user_registered, 
            IF(u.`lastactivity`, FROM_UNIXTIME(u.`lastactivity`), '0000-00-00 00:00:00') as online_time,
            CONCAT( 'avatar', u.`userid`, '_', u.`avatarrevision` ) as avatar,
            p.`signature` as signature,
            u.`icq` as icq,
            u.`aim` as aim,
            u.`msn` as msn,
            u.`skype` as skype
		FROM `" . $this->db->prefix . "user` u
		LEFT JOIN `" . $this->db->prefix . "usertextfield` p ON p.`userid` = u.`userid`
		WHERE u.`userid` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_forums() {
		$sql = "SELECT `forumid` AS old_id, 
			`title` AS title, 
			`description` AS description, 
			`parentid` AS parentid
		FROM `" . $this->db->prefix . "forum`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT t.`threadid` AS old_id,
		    p.`postid` AS old_postid,
			p.`userid` AS userid, 
			t.`title` AS title, 
			p.`pagetext` AS body, 
			t.`forumid` AS forumid,
			t.`views` AS views,
			t.`sticky` AS `type`,
			IF(p.`dateline`, FROM_UNIXTIME(p.`dateline`), '0000-00-00 00:00:00') AS created 
		FROM `" . $this->db->prefix . "thread` t
		INNER JOIN `" . $this->db->prefix . "post` p ON p.`postid` = t.`firstpostid`
		WHERE t.`threadid` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT p.`postid` AS old_id, 
		    p.`postid` AS old_postid,
			p.`userid` AS userid,
			IF(p.`title`, p.`title`, t.`title`) AS title, 
			p.`pagetext` AS body, 
			p.`threadid` AS topicid, 
			IF(p.`dateline`, FROM_UNIXTIME(p.`dateline`), '0000-00-00 00:00:00') AS created
		FROM `" . $this->db->prefix . "post` p 
		INNER JOIN `" . $this->db->prefix . "thread` t ON t.`threadid` = p.`threadid` AND p.`postid` != t.`firstpostid`
		WHERE p.`postid` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	private function _attach_sql_select( $where ) {
		if( ! $where ) $where = '';
		$sql = "SELECT a.`attachmentid` as attachid,   
            a.`contentid` as postid, 
            f.`userid` as userid,
            a.`filename` as `filename`, 
            CONCAT(f.`filedataid`, '.attach') as hash_filename,
            f.`extension` as ext
             FROM `" . $this->db->prefix . "attachment` a
             INNER JOIN `" . $this->db->prefix . "filedata` f ON f.`filedataid` = a.`filedataid`" . $where;

		return $sql;
	}

	public function get_attach( $arg ) {
		if( ! $arg ) return null;
		$where = " WHERE " . ( is_numeric( $arg ) ? "a.`attachmentid` = %d" : "a.`filename` = %s" );
		$sql   = $this->_attach_sql_select( $where );

		return $this->db->get_row( $this->db->prepare( $sql, $arg ), ARRAY_A );
	}

	public function get_attachs( $args ) {
		if( ! $args ) return null;

		$wheres = [];
		if( ! empty( $args['postids_in'] ) && $postids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_in'] ) ) ) {
			$wheres[] = "a.`contentid` IN(" . implode( ',', $postids_in ) . ")";
		}

		if( ! empty( $args['postids_not_in'] ) && $postids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_not_in'] ) ) ) {
			$wheres[] = "a.`contentid` NOT IN(" . implode( ',', $postids_not_in ) . ")";
		}

		if( ! empty( $args['attachids_in'] ) && $attachids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_in'] ) ) ) {
			$wheres[] = "a.`attachmentid` IN(" . implode( ',', $attachids_in ) . ")";
		}

		if( ! empty( $args['attachids_not_in'] ) && $attachids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_not_in'] ) ) ) {
			$wheres[] = "a.`attachmentid` NOT IN(" . implode( ',', $attachids_not_in ) . ")";
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
			if( isset( $match[1] ) && $match[1] ) {
				$arg = $match[1];
			} elseif( isset( $match[2] ) && $match[2] ) {
				$arg = $match[2];
			}

			if( $attach = $this->get_attach( $arg ) ) {
				if( $html = WPF_GO2()->do_attach( $attach ) ) $replacement = $html;
			}
		}

		return $replacement;
	}

	public function do_inline_attachs( $text ) {
		$pattern = '#\[attachment(?:[\r\n\t\s\0]*=?[\r\n\t\s\0]*(\d+)[\r\n\t\s\0]*|[\r\n\t\s\0]*)\][\r\n\t\s\0]*([^\[\]]+)[\r\n\t\s\0]*\[/attachment\]#isu';
		$text    = preg_replace_callback( $pattern, [ $this, 'do_inline_attach' ], (string) $text );

		$pattern = '#\[attach[^\[\]]*?][\r\n\t\s\0]*(\d+)[\r\n\t\s\0]*\[/attach[^\[\]]*?]#iu';
		$text    = preg_replace_callback( $pattern, [ $this, 'do_inline_attach' ], (string) $text );

		return $text;
	}

	public function specific_bb2normal( $text ) {
		$text = (string) $text;
		$text = preg_replace( '#\[([^\:\[\]]+)\:[\w]+\]#isu', '[$1]', $text );
		$text = preg_replace( '#<\!--.*?-->#isu', '', $text );

		return $text;
	}

	public function filter_avatar_from( $from ) {
		if( ! file_exists( $from ) ) {

			if( file_exists( $from . ".jpg" ) ) {
				$from = $from . ".jpg";
			} elseif( file_exists( $from . ".jpeg" ) ) {
				$from = $from . ".jpeg";
			} elseif( file_exists( $from . ".png" ) ) {
				$from = $from . ".png";
			} elseif( file_exists( $from . ".gif" ) ) {
				$from = $from . ".gif";
			} else {
				$from = '';
			}

		}

		return $from;
	}

	public function filter_attach( $attach ) {
		$dir                     = implode( '/', str_split( $attach['userid'], 1 ) );
		$attach['hash_filename'] = trim( $dir, '/' ) . '/' . $attach['hash_filename'];

		return $attach;
	}
}

$this->vbulletin_4 = new vbulletin_4();
