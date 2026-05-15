<?php

namespace go2wpforo\includes;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

abstract class Board {
	protected $db;
	protected $limit;

	public function __construct() {
		$this->db    = WPF_GO2()->sdb;
		$this->limit = WPF_GO2()->itempercycle ? ' LIMIT ' . WPF_GO2()->itempercycle : '';
	}

	abstract public function get_users_count();

	abstract public function get_forums_count();

	abstract public function get_topics_count();

	abstract public function get_posts_count();

	abstract public function get_usergroups();

	abstract public function get_users( $lastid = 0 );

	abstract public function get_forums();

	abstract public function get_topics( $lastid = 0 );

	abstract public function get_replies( $lastid = 0 );

	public function do_attachs( $text, $args ) {
		if( WPF_GO2()->sdirs_attach ) {
			if( ! empty( $args['old_postid'] ) ) {
				if( $attachs = $this->get_attachs( [ 'postids_in' => $args['old_postid'], 'attachids_not_in' => WPF_GO2()->current_post_attachids ] ) ) {
					foreach( $attachs as $attach ) {
						if( $html = WPF_GO2()->do_attach( $attach ) ) $text .= $html;
					}
				}
			}
		}

		WPF_GO2()->current_post_attachids = [];

		return $text;
	}
}
