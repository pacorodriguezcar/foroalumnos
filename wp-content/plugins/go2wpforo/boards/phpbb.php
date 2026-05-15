<?php

namespace go2wpforo\boards;

use go2wpforo\includes\Board;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class phpbb extends Board {
	private $avatar_salt;

	public function __construct() {
		parent::__construct();
		WPF_GO2()->add_filter_to_all_text_contents( [ $this, 'specific_bb2normal' ], 9 );
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
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "topics`" ) ) return $count;

		return 0;
	}

	public function get_posts_count() {
		if( $count = $this->db->get_var( "SELECT COUNT(*) FROM `" . $this->db->prefix . "posts`" ) ) return $count;

		return 0;
	}

	public function get_usergroups() {
		$sql = "SELECT `group_id` AS groupid,
          `group_name` AS `name`
        FROM `" . $this->db->prefix . "groups`
        ORDER BY `name`";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_users( $lastid = 0 ) {
		$old_fields = $this->db->get_var( "SHOW COLUMNS FROM `" . $this->db->prefix . "users` LIKE 'user_website'" );
		if( $old_fields ) {
			$sql = "SELECT `user_id` as old_id, 
            `group_id` as groupid, 
            `username_clean` as user_login, 
            `username` as display_name, 
            `user_email` as user_email, 
            IF(`user_regdate`, FROM_UNIXTIME(`user_regdate`), '0000-00-00 00:00:00') as user_registered, 
            IF(`user_lastpost_time`, FROM_UNIXTIME(`user_lastpost_time`), '0000-00-00 00:00:00') as online_time, 
            `user_avatar` as avatar,
            `user_sig` as signature,
            `user_website` as site,
            `user_icq` as icq,
            `user_aim` as aim,
            `user_msnm` as msn,
            `user_occ` as occupation,
            `user_interests` as about
            FROM `" . $this->db->prefix . "users`
            WHERE `user_id` > $lastid
            ORDER BY old_id";
		} else {
			$sql = "SELECT u.`user_id` as old_id, 
            u.`group_id` as groupid, 
            u.`username_clean` as user_login, 
            u.`username` as display_name, 
            u.`user_email` as user_email, 
            IF(u.`user_regdate`, FROM_UNIXTIME(u.`user_regdate`), '0000-00-00 00:00:00') as user_registered, 
            IF(u.`user_lastvisit`, FROM_UNIXTIME(u.`user_lastvisit`), '0000-00-00 00:00:00') as online_time, 
            u.`user_avatar` as avatar,
            u.`user_sig` as signature,
            IF(pf.`pf_phpbb_website`, pf.`pf_phpbb_website`, '') as site,
            IF(pf.`pf_phpbb_occupation`, pf.`pf_phpbb_occupation`, '') as occupation,
            IF(pf.`pf_phpbb_interests`, pf.`pf_phpbb_interests`, '') as about
            FROM `" . $this->db->prefix . "users` as u
            LEFT JOIN `" . $this->db->prefix . "profile_fields_data` as pf
            ON u.`user_id` = pf.`user_id` 
            WHERE u.`user_id` > $lastid
            ORDER BY u.`user_id`";
		}

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_forums() {
		$sql = "SELECT `forum_id` AS old_id, 
			`forum_name` AS title, 
			`forum_desc` AS description, 
			`parent_id` AS parentid
		FROM `" . $this->db->prefix . "forums`
		ORDER BY parentid, old_id";

		return $this->db->get_results( $sql, ARRAY_A );
	}

	public function get_topics( $lastid = 0 ) {
		$sql = "SELECT t.`topic_id` AS old_id,
		    p.`post_id` AS old_postid,
			p.`poster_id` AS userid, 
			t.`topic_title` AS title, 
			p.`post_text` AS body, 
			t.`forum_id` AS forumid, 
			t.`topic_views` AS views,
			t.`topic_type` AS `type`,
			IF(t.`topic_time`, FROM_UNIXTIME(t.`topic_time`), '0000-00-00 00:00:00') AS created 
		FROM `" . $this->db->prefix . "topics` t
		INNER JOIN `" . $this->db->prefix . "posts` p ON p.`post_id` = t.`topic_first_post_id`
		WHERE t.`topic_id` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	public function get_replies( $lastid = 0 ) {
		$sql = "SELECT p.`post_id` AS old_id, 
		    p.`post_id` AS old_postid,
			p.`poster_id` AS userid, 
			p.`post_subject` AS title, 
			p.`post_text` AS body, 
			p.`topic_id` AS topicid, 
			IF(p.`post_time`, FROM_UNIXTIME(p.`post_time`), '0000-00-00 00:00:00') AS created
		FROM `" . $this->db->prefix . "posts` p 
		INNER JOIN `" . $this->db->prefix . "topics` t ON t.`topic_id` = p.`topic_id` AND p.`post_id` != t.`topic_first_post_id`
		WHERE p.`post_id` > $lastid
		ORDER BY old_id";

		return $this->db->get_results( $sql . $this->limit, ARRAY_A );
	}

	private function _attach_sql_select( $where ) {
		if( ! $where ) $where = '';
		$sql = "SELECT `attach_id` as attachid,   
            `post_msg_id` as postid, 
            `poster_id` as userid,
            `real_filename` as `filename`, 
            `physical_filename` as hash_filename,
            if(`thumbnail`, CONCAT('thumb_', `physical_filename`), '') as hash_thumb_filename,
            `extension` as ext
             FROM `" . $this->db->prefix . "attachments`" . $where;

		return $sql;
	}

	public function get_attach( $arg ) {
		if( ! $arg ) return null;
		$where = " WHERE " . ( is_numeric( $arg ) ? "`attach_id` = %d" : "`real_filename` = %s" );
		$sql   = $this->_attach_sql_select( $where );

		return $this->db->get_row( $this->db->prepare( $sql, $arg ), ARRAY_A );
	}

	public function get_attachs( $args ) {
		if( ! $args ) return null;

		$wheres = [];
		if( ! empty( $args['postids_in'] ) && $postids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_in'] ) ) ) {
			$wheres[] = "`post_msg_id` IN(" . implode( ',', $postids_in ) . ")";
		}

		if( ! empty( $args['postids_not_in'] ) && $postids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['postids_not_in'] ) ) ) {
			$wheres[] = "`post_msg_id` NOT IN(" . implode( ',', $postids_not_in ) . ")";
		}

		if( ! empty( $args['attachids_in'] ) && $attachids_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_in'] ) ) ) {
			$wheres[] = "`attach_id` IN(" . implode( ',', $attachids_in ) . ")";
		}

		if( ! empty( $args['attachids_not_in'] ) && $attachids_not_in = array_filter( array_map( 'wpforo_bigintval', (array) $args['attachids_not_in'] ) ) ) {
			$wheres[] = "`attach_id` NOT IN(" . implode( ',', $attachids_not_in ) . ")";
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

			//for old version phpbb when [attach={ID}]{FILENAME}[/attach]
			/*if( isset($match[1]) && $match[1] ){
				$arg = $match[1];
			}elseif ( isset($match[2]) && $match[2] ){
				$arg = $match[2];
			}*/

			//for new version phpbb when [attach={INDEX_IN_PAGE_CONTENT}]{FILENAME}[/attach]
			if( isset( $match[2] ) && $match[2] ) {
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

		return $text;
	}

	public function specific_bb2normal( $text ) {
		$text = (string) $text;
		$text = preg_replace( '#\[([^\:\[\]]+)\:[\w]+\]#isu', '[$1]', $text );
		$text = preg_replace( '#<\!--.*?-->#isu', '', $text );
		$text = preg_replace( '#</?attachment[^<>]*?>#isu', '$2', $text );
		$text = preg_replace( '#<(s|e)>[\r\n\t\s\0]*(\[/?attach[^\[\]]*?\])[\r\n\t\s\0]*</\1>#isu', '$2', $text );
		$text = preg_replace( '#<(s|e)>.*?</\1>#su', '', $text );
		$text = preg_replace( '#</?(E|r)>#su', '', $text );
		$text = preg_replace( '#<img[^<>]*?src[\r\n\t\s\0]*=[\r\n\t\s\0]*[\'\"](?<src>[^\'\"]*)[\'\"][^<>]*?>[\r\n\t\s\0]*<url[^<>]*?url[\r\n\t\s\0]*=[\r\n\t\s\0]*[\'\"](?<href>[^\'\"]*)[\'\"][^<>]*>.*?</img>#isu', '<a href="$2"><img src="$1"></a>', $text );
		$text = preg_replace( '#<url[^<>]*?url[\r\n\t\s\0]*=[\r\n\t\s\0]*[\'\"](?<href>[^\'\"]*)[\'\"][^<>]*?>(?<txt>.*?)</url>#isu', '<a href="$1">$2</a>', $text );
		$text = preg_replace( '#(</?)quote(>|\s[^<>]*?>)#iu', '$1blockquote$2', $text );

		return $text;
	}

	public function filter_avatar_from( $from, $user ) {
		if( ! file_exists( $from ) && ! empty( $user['avatar'] ) ) {
			if( ! $this->avatar_salt ) {
				$sql               = "SELECT `config_value` FROM `" . $this->db->prefix . "config` WHERE `config_name` = 'avatar_salt'";
				$this->avatar_salt = $this->db->get_var( $sql );
			}
			if( $this->avatar_salt ) {
				$ext      = '.jpg';
				$filetype = wp_check_filetype( basename( (string) $user['avatar'] ) );
				if( $filetype['ext'] ) $ext = "." . $filetype['ext'];
				$from = WPF_GO2()->sdirs_avatar . "/" . $this->avatar_salt . '_' . $user['old_id'] . $ext;
			}
		}

		return $from;
	}
}

$this->phpbb = new phpbb();
