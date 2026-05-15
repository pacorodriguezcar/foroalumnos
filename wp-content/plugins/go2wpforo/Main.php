<?php
/*
* Plugin Name: wpForo - Go2wpForo Migration Tool
* Plugin URI: https://wpforo.com/docs/root/migrate-to-wpforo/
* Description: Helps to migrate from bbPress, phpBB, SMF, MyBB, Simple:Press and other forum pieces of software and plugins to wpForo.
* Author: gVectors Team (A. Chakhoyan, R. Hovhannisyan)
* Author URI: http://gvectors.com/
* Version: 3.0.3
*/

namespace go2wpforo;

use wpdb;

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;
if( ! defined( 'GO2WPFORO_VERSION' ) ) define( 'GO2WPFORO_VERSION', '3.0.3' );
if( ! defined( 'GO2WPFORO_WPFORO_REQUIRED_VERSION' ) ) define( 'GO2WPFORO_WPFORO_REQUIRED_VERSION', '2.0.0' );

define( 'MG2WPFORO_DIR', rtrim( str_replace( '//', '/', dirname( __FILE__ ) ), '/' ) );
define( 'MG2WPFORO_URL', rtrim( plugins_url( '', __FILE__ ), '/' ) );
define( 'MG2WPFORO_FOLDER', rtrim( plugin_basename( dirname( __FILE__ ) ), '/' ) );
define( 'MG2WPFORO_BASENAME', plugin_basename( __FILE__ ) );

class Main {
	private static $_instance = null;
	
	private $db;
	public  $sdb;
	public  $sdbh;
	public  $itempercycle = 200;
	public  $layout       = 2;
	
	private $passwords = [ 'ACF45FG', 'CCG65RG', 'PLO12LK', 'YOU77TT', 'QWER784', 'KUYT78P', 'POIU789H', 'GYFTDR44', 'ASS05FQ', 'HCW22PU' ];
	
	public $current_post_attachids = [];
	
	public $upload_baseurl;
	public $upload_basedir;
	
	public $avatar_baseurl;
	public $avatar_basedir;
	
	public $attach_baseurl;
	public $attach_basedir;
	
	public $sdb_is_current_wpdb = false;
	public $sdb_config          = [];
	
	public $sdirs_avatar;
	public $sdirs_attach;
	
	public static function instance() {
		if( is_null( self::$_instance ) ) self::$_instance = new self();
		
		return self::$_instance;
	}
	
	private function __construct() {
		if( ! wpforo_is_session_started() ) session_start();
		$this->db = WPF()->db;
		$this->setup();
	}
	
	private function setup() {
		@set_time_limit( 0 );
		@ini_set( 'max_execution_time', 0 );
		@ini_set( 'memory_limit', '-1' );
		$this->db->hide_errors();
		$this->init_hooks();
		add_action( 'wpforo_after_init', [ $this, 'init_vars' ] );
		add_action( 'wpforo_after_init', [ $this, 'do_actions' ], 11 );
	}
	
	public function init_hooks() {
		register_deactivation_hook( __FILE__, [ &$this, 'deactivation' ] );
		if( wpforo_current_user_is( 'admin' ) ) {
			add_action( 'admin_menu', [ $this, 'add_menu' ], 40 );
			add_filter( 'wp_die_handler', [ $this, 'wp_die_handler_filter' ] );
			$this->add_filter_to_all_text_contents( 'go2wpf_bb2html' );
		}
	}
	
	public function wp_die_handler_filter() {
		return [ $this, 'unset_sessions_in_wp_die' ];
	}
	
	/**
	 * @param callable $function_to_add
	 * @param int $priority
	 * @param int $accepted_args
	 */
	public function add_filter_to_all_text_contents( $function_to_add, $priority = 10, $accepted_args = 1 ) {
		add_filter( 'go2wpf_do_users_about', $function_to_add, $priority, $accepted_args );
		add_filter( 'go2wpf_do_users_occupation', $function_to_add, $priority, $accepted_args );
		add_filter( 'go2wpf_do_users_signature', $function_to_add, $priority, $accepted_args );
		add_filter( 'go2wpf_do_forums_desc', $function_to_add, $priority, $accepted_args );
		add_filter( 'go2wpf_do_topics_body', $function_to_add, $priority, $accepted_args );
		add_filter( 'go2wpf_do_replies_body', $function_to_add, $priority, $accepted_args );
		add_filter( 'go2wpf_do_comments_body', $function_to_add, $priority, $accepted_args );
	}
	
	public function add_filter_to_posts_text_contents( $function_to_add, $priority = 10, $accepted_args = 1 ) {
		add_filter( 'go2wpf_do_topics_body', $function_to_add, $priority, $accepted_args );
		add_filter( 'go2wpf_do_replies_body', $function_to_add, $priority, $accepted_args );
		add_filter( 'go2wpf_do_comments_body', $function_to_add, $priority, $accepted_args );
	}
	
	public function init_vars() {
		if( WPF()->current_user_groupid != 1 ) return;
		$this->sdb_config = [
			'dbhost'         => $this->db->__get( 'dbhost' ),
			'dbuser'         => $this->db->__get( 'dbuser' ),
			'dbpassword'     => $this->db->__get( 'dbpassword' ),
			'dbname'         => $this->db->__get( 'dbname' ),
			'dbprefix'       => $this->db->prefix,
			'dbsecondprefix' => '',
		];
		if( wpforo_is_session_started() ) {
			if( ! empty( $_SESSION['go2wpf_sdb_config'] ) ) {
				$this->sdb_is_current_wpdb = ( $this->sdb_config == $_SESSION['go2wpf_sdb_config'] );
				$this->sdb_config          = array_merge( $this->sdb_config, $_SESSION['go2wpf_sdb_config'] );
			}
			if( ! empty( $_SESSION['go2wpf_sdirs'] ) ) {
				if( ! empty( $_SESSION['go2wpf_sdirs']['avatar'] ) ) $this->sdirs_avatar = $_SESSION['go2wpf_sdirs']['avatar'];
				if( ! empty( $_SESSION['go2wpf_sdirs']['attach'] ) ) $this->sdirs_attach = $_SESSION['go2wpf_sdirs']['attach'];
			}
		}
		
		$this->upload_baseurl = WPF()->folders['upload']['url//'];
		$this->upload_basedir = WPF()->folders['upload']['dir'];
		
		$this->avatar_baseurl = $this->upload_baseurl . "/avatars";
		$this->avatar_basedir = $this->upload_basedir . DIRECTORY_SEPARATOR . "avatars";
		
		$this->attach_baseurl = $this->upload_baseurl . "/attachments";
		$this->attach_basedir = $this->upload_basedir . DIRECTORY_SEPARATOR . "attachments";
		
		$this->init_source_db();
	}
	
	private function init_source_db() {
		$this->sdb = new wpdb(
			$this->sdb_config['dbuser'], $this->sdb_config['dbpassword'], $this->sdb_config['dbname'], $this->sdb_config['dbhost']
		);
		$this->sdb->set_prefix( $this->sdb_config['dbprefix'] );
		$this->sdb->prefix = $this->sdb_config['dbprefix'] . $this->sdb_config['dbsecondprefix'];
		$this->sdb->hide_errors();
		
		$this->sdbh = $this->sdb->__get( 'dbh' );
	}
	
	private function unset_session( $name ) {
		if( $name ) unset( $_SESSION[ $name ] );
	}
	
	private function unset_sessions() {
		$this->unset_session( 'go2wpf_sdb_config' );
		$this->unset_session( 'go2wpf_sdirs' );
		$this->unset_session( 'go2wpf_all_OK' );
	}
	
	public function unset_sessions_in_wp_die( $message, $title = '', $args = [] ) {
		$this->unset_sessions();
		
		_default_wp_die_handler( $message, $title, $args );
	}
	
	public function add_menu() {
		add_menu_page( 'Go2wpForo', 'Go2wpForo', 'activate_plugins', 'Go2wpForo', [ &$this, 'main_page' ], 'dashicons-migrate' );
		
		$boards = glob( MG2WPFORO_DIR . '/boards/*.php' );
		if( ! empty( $boards ) ) {
			foreach( $boards as $board ) {
				if( strpos( (string) $board, 'vbulletin' ) !== false ) continue;
				$board = basename( (string) $board, '.php' );
				add_submenu_page( 'Go2wpForo', $board, '&nbsp;' . strtoupper( $board ) . '&nbsp;&raquo;', 'activate_plugins', $board . '-mg2wpforo', [ &$this, 'board_page' ] );
			}
		}
	}
	
	public function main_page() { ?>
        <h1 style="text-align:center; font-weight:500; font-size:26px; padding:30px 10px 10px 10px;">Welcome to Go2wpForo <?php echo GO2WPFORO_VERSION ?> Tool</h1>
        <p style="text-align: center; margin: 0;">
            <img src="<?php echo MG2WPFORO_URL ?>/go2wpforo.png" style="height:60px;" alt="">
        </p>
        <div class="wrap" style="text-align:center; margin-top: 20px;">
            <table style="margin:0 auto; width: 90%;">
                <tbody>
                <tr>
                    <td style="text-align:left; background:#fff; padding:20px 20px 0; font-size:15px; line-height: 22px; font-style: italic;" colspan="3">
                        <h4 style="margin: 0;">What migrates this tool?</h4>
                        We do our best to improve this free migration tool as much as possible, however that's very hard to make migration tool for all kind and size of forums.
                        This tool is designed for default forum components migration like Forums, Topics, Posts, Attachments and Users. This will not migrate data like PMs, Polls, etc...
                    </td>
                </tr>
                <tr>
                    <td style="text-align:left; background:#fff; padding:20px; font-size:15px; line-height: 22px; font-style: italic;" colspan="3">
                        <h4 style="margin: 0;">Is there any support for this tool?</h4>
                        Forum to Forum migration is a large project. We're sorry, but we cannot support issues arisen during your forum migration.
                        <u>We only support questions related how to use and configure this tool in <a href="https://wpforo.com/community/" target="_blank">wpForo support forum</a>.</u> We don't
                        support the result of migration. This kind of large work is out of our support.
                        In case the migration result of this tool doesn't satisfy you, you should contact to professional forum migration services for custom migration.
                        We only recommend <a href="https://gconverters.com/forum-to-forum-migration/" target="_blank">gConverter</a> or <a href="https://profprojects.com/migration-services/#forum"
                                                                                                                                           target="_blank">ProfProjects</a> services for such projects.
                        They have already done hundreds of forum to wpForo migration projects with the best result.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <br>
        <div class="wrap" style="text-align:center;">
            <table style="margin:10px auto;">
                <tbody>
                <tr>
                    <td style="text-align:center; background:#fff; padding:10px;" colspan="6"><h3>Available Migrations</h3></td>
                </tr>
                <tr>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=bbpress-mg2wpforo' ) ?>" style="text-decoration:none;"><img src="<?php echo MG2WPFORO_URL ?>/images/bbpress.png"
                                                                                                                                   style="height:70px;" alt="bbpress"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=bbpress-mg2wpforo' ) ?>" style="text-decoration:none;">bbPress to wpForo</a></p>
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=simplepress-mg2wpforo' ) ?>" style="text-decoration:none;"><img alt="" src="<?php echo MG2WPFORO_URL ?>/images/simplepress.png"
                                                                                                                                       style="height:70px;"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=simplepress-mg2wpforo' ) ?>" style="text-decoration:none;">Simple:Press to
                                wpForo</a></p>
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=asgaros-mg2wpforo' ) ?>" style="text-decoration:none;"><img alt="" src="<?php echo MG2WPFORO_URL ?>/images/asgaros.png"
                                                                                                                                   style="height:70px;"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=asgaros-mg2wpforo' ) ?>" style="text-decoration:none;">Asgaros to wpForo</a></p>
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=anspress-mg2wpforo' ) ?>" style="text-decoration:none;"><img alt="" src="<?php echo MG2WPFORO_URL ?>/images/anspress.png"
                                                                                                                                    style="height:70px;"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=anspress-mg2wpforo' ) ?>" style="text-decoration:none;">AnsPress to wpForo</a>
                        </p>
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=dwqa-mg2wpforo' ) ?>" style="text-decoration:none;"><img alt="" src="<?php echo MG2WPFORO_URL ?>/images/dwqa.jpg"
                                                                                                                                style="height:70px;"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=dwqa-mg2wpforo' ) ?>" style="text-decoration:none;">DW Q&amp;A to wpForo</a></p>
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=anspress-mg2wpforo' ) ?>" style="text-decoration:none;"><img alt="" src="<?php echo MG2WPFORO_URL ?>/images/wpqa.png"
                                                                                                                                    style="height:70px;"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=anspress-mg2wpforo' ) ?>" style="text-decoration:none;">WP Q&amp;A to wpForo</a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=phpbb-mg2wpforo' ) ?>" style="text-decoration:none;"><img alt="" src="<?php echo MG2WPFORO_URL ?>/images/phpbb.jpg"
                                                                                                                                 style="height:70px;"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=phpbb-mg2wpforo' ) ?>" style="text-decoration:none;">phpBB to wpForo</a></p>
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=smf-mg2wpforo' ) ?>" style="text-decoration:none;"><img alt="" src="<?php echo MG2WPFORO_URL ?>/images/smf.png"
                                                                                                                               style="height:70px;"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=smf-mg2wpforo' ) ?>" style="text-decoration:none;">SMF to wpForo</a></p>
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=mybb-mg2wpforo' ) ?>" style="text-decoration:none;"><img alt="" src="<?php echo MG2WPFORO_URL ?>/images/mybb.png"
                                                                                                                                style="height:70px;"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=mybb-mg2wpforo' ) ?>" style="text-decoration:none;">MyBB to wpForo</a></p>
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        <a href="<?php echo admin_url( 'admin.php?page=kunena-mg2wpforo' ) ?>" style="text-decoration:none;"><img alt="" src="<?php echo MG2WPFORO_URL ?>/images/kunena.png"
                                                                                                                                  style="height:70px;"></a>
                        <p style="padding:0; margin:0; font-size:16px;"><a href="<?php echo admin_url( 'admin.php?page=kunena-mg2wpforo' ) ?>" style="text-decoration:none;">Joomla Kunena to wpForo</a>
                        </p>
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        &nbsp
                    </td>
                    <td style="text-align:center; background:#fff; padding:10px;">
                        &nbsp
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
		<?php
	}
	
	public function board_page() {
		if( isset( $_GET['page'] ) ) {
			$board = trim( basename( (string) $_GET['page'], '-mg2wpforo' ) );
			if( $board && wpforo_is_session_started() ) {
				if( empty( $_SESSION['go2wpf_sdb_config'] ) ) {
					$this->show_db_config_form( $board );
				} elseif( empty( $_SESSION['go2wpf_sdirs'] ) ) {
					$this->show_source_dirs_form( $board );
				} elseif( empty( $_SESSION['go2wpf_all_OK'] ) ) {
					$this->show_connections_status( $board );
				} else {
					$this->init_action( $board );
				}
			}
		}
	}
	
	public function deactivation() {
		/* if( version_compare($this->db->db_version(), '5.6.4', '>=') ){
			 $sql = "ALTER TABLE `" . WPF()->tables->topics . "` ENGINE = InnoDB";
			 $this->db->query($sql);
			 $sql = "ALTER TABLE `" . WPF()->tables->posts . "` ENGINE = InnoDB";
			 $this->db->query($sql);
		 }*/
	}
	
	private function show_statistics_table( $board ) {
		$statistic = WPF()->statistic();
		?>
        <style type="text/css">
            th, td {
                padding: 7px;
            }

            td {
                border-bottom: 1px solid #ccc;
            }

            tr:last-child td {
                border: none;
            }
        </style>
        <table cellpadding="0" cellspacing="0" width="100%" style="font-size: 15px;">
            <tr>
                <th style="text-align: left;">Components</th>
                <th style="text-align: center;"><?php echo strtoupper( (string) $board ) ?></th>
                <th style="text-align: center;">wpForo</th>
            </tr>
            <tr>
                <td>Users:</td>
                <td style="text-align: center;"><b><?php echo $this->$board->get_users_count() ?></b></td>
                <td style="text-align: center;"><b><?php echo ( isset( $statistic['members'] ) ) ? $statistic['members'] : 'not available'; ?></b></td>
            </tr>
            <tr>
                <td>Forums:</td>
                <td style="text-align: center;"><b><?php echo $this->$board->get_forums_count() ?></b></td>
                <td style="text-align: center;"><b><?php echo ( isset( $statistic['forums'] ) ) ? $statistic['forums'] : 'not available'; ?></b></td>
            </tr>
            <tr>
                <td>Topics:</td>
                <td style="text-align: center;"><b><?php echo $this->$board->get_topics_count() ?></b></td>
                <td style="text-align: center;"><b><?php echo ( isset( $statistic['topics'] ) ) ? $statistic['topics'] : 'not available'; ?></b></td>
            </tr>
            <tr>
                <td>Posts:</td>
                <td style="text-align: center;"><b><?php echo $this->$board->get_posts_count() ?></b></td>
                <td style="text-align: center;"><b><?php echo ( isset( $statistic['posts'] ) ) ? $statistic['posts'] : 'not available'; ?></b></td>
            </tr>
        </table>
		<?php
	}
	
	private function get_progress_percent( $board ) {
		if( isset( $_GET['convert'] ) && $_GET['convert'] == 'start' ) return 0;
		$objs        = [ 'forum', 'topic', 'reply' ];
		$items_count = $this->$board->get_forums_count() + $this->$board->get_posts_count();
		if( ! $this->sdb_is_current_wpdb ) {
			$objs[]      = 'user';
			$items_count += $this->$board->get_users_count();
		}
		if( ! $items_count ) return 0;
		$percent = ceil( $this->get_log_count( $objs ) * 100 / $items_count );
		if( ! $percent || $percent < 1 ) $percent = 1;
		if( $percent > 100 ) $percent = 100;
		
		return $percent;
	}
	
	private function show_progress_bar( $board ) {
		$percent = $this->get_progress_percent( $board ); ?>
        <div style="position: relative; background-color: white; width: 650px; height: 50px; border: 1px solid grey; padding: 2px; text-align: left;">
            <span style="color: rgb(13, 110, 62); font-size: 2.5em; position: absolute; left: 47%; top: 32%;"><?php echo $percent ?>%</span>
            <div style="background: rgba(79,240,119,1);
                    background: -moz-linear-gradient(top, rgba(79,240,119,1) 0%, rgba(87,233,100,1) 100%);
                    background: -webkit-gradient(left top, left bottom, color-stop(0%, rgba(79,240,119,1)), color-stop(100%, rgba(87,233,100,1)));
                    background: -webkit-linear-gradient(top, rgba(79,240,119,1) 0%, rgba(87,233,100,1) 100%);
                    background: -o-linear-gradient(top, rgba(79,240,119,1) 0%, rgba(87,233,100,1) 100%);
                    background: -ms-linear-gradient(top, rgba(79,240,119,1) 0%, rgba(87,233,100,1) 100%);
                    background: linear-gradient(to bottom, rgba(79,240,119,1) 0%, rgba(87,233,100,1) 100%);
                    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#4ff077', endColorstr='#57e964', GradientType=0 );
                    height: 100%; width: <?php echo $percent ?>%"></div>
        </div>
		<?php
	}
	
	private function add_log( $obj, $old_id, $new_id ) {
		$this->db->query(
			$this->db->prepare(
				"INSERT IGNORE INTO `" . WPF()->tables->mg2wpforo . "` ( `obj`, `old_id`, `new_id` ) VALUES ( %s, %d, %d )",
				$obj,
				$old_id,
				$new_id
			)
		);
	}
	
	private function clear_log() {
		$this->db->query( "TRUNCATE " . WPF()->tables->mg2wpforo );
	}
	
	private function get_log_count( $objs = [] ) {
		$objs = array_filter( (array) $objs );
		$sql  = "SELECT COUNT(*) FROM " . WPF()->tables->mg2wpforo;
		if( $objs ) $sql .= " WHERE `obj` IN('" . implode( "','", array_map( 'trim', $objs ) ) . "')";
		if( $count = $this->db->get_var( $sql ) ) return $count;
		
		return 0;
	}
	
	private function get_last_old_id( $obj ) {
		$sql = "SELECT old_id FROM `" . WPF()->tables->mg2wpforo . "` WHERE obj = %s ORDER BY 1 DESC LIMIT 1";
		$sql = $this->db->prepare( $sql, $obj );
		
		return wpforo_bigintval( $this->db->get_var( $sql ) );
	}
	
	private function get_old_id( $obj, $new_id ) {
		$sql = "SELECT old_id FROM `" . WPF()->tables->mg2wpforo . "` WHERE obj = %s AND new_id = %d";
		$sql = $this->db->prepare( $sql, $obj, $new_id );
		
		return wpforo_bigintval( $this->db->get_var( $sql ) );
	}
	
	public function _get_new_id( $obj, $old_id ) {
		$sql = "SELECT new_id FROM `" . WPF()->tables->mg2wpforo . "` WHERE obj = %s AND old_id = %d";
		$sql = $this->db->prepare( $sql, $obj, $old_id );
		
		return wpforo_bigintval( $this->db->get_var( $sql ) );
	}
	
	public function get_new_id( $obj, $old_id ) {
		return wpforo_ram_get( [ $this, '_get_new_id' ], $obj, $old_id );
	}
	
	private function get_new_groupid( $old_id ) {
		return $this->get_new_id( 'usergroup', $old_id );
	}
	
	private function get_rand_password() {
		return $this->passwords[ array_rand( $this->passwords ) ];
	}
	
	private function do_usergroups() {
		if( ! $this->sdb_is_current_wpdb ) {
			if( ! empty( $_REQUEST['go2wpf_groups'] ) && is_array( $_REQUEST['go2wpf_groups'] ) ) {
				foreach( $_REQUEST['go2wpf_groups'] as $old_id => $new_id ) {
					if( ! $this->get_new_id( 'usergroup', $old_id ) ) {
						$this->add_log( 'usergroup', $old_id, $new_id );
					}
				}
			}
		}
	}
	
	private function do_users( $board ) {
		if( ! $this->sdb_is_current_wpdb ) {
			$lastid = $this->get_last_old_id( 'user' );
			if( $users = $this->$board->get_users( $lastid ) ) {
				foreach( $users as $user ) {
					$new_id = 0;
					if( ! $this->get_new_id( 'user', $user['old_id'] ) ) {
						
						if( ! $user['user_login'] ) {
							continue;
						}
						
						if( strlen( (string) $user['user_login'] ) < 3 ) {
							$user['user_login'] = '_' . $user['user_login'] . '_';
						}
						
						if( ! $user['display_name'] ) {
							$user['display_name'] = $user['user_login'];
						}
						
						if( ! $user['user_email'] || ! is_email( $user['user_email'] ) ) {
							$user['user_email'] = uniqid( 'anonymous-' ) . '@example.com';
						}
						
						if( isset( $user['about'] ) ) {
							$user['about'] = apply_filters( 'go2wpf_do_users_about', $user['about'] );
						}
						
						if( isset( $user['signature'] ) ) {
							$user['signature'] = apply_filters( 'go2wpf_do_users_signature', $user['signature'] );
						}
						
						if( isset( $user['occupation'] ) ) {
							$user['occupation'] = apply_filters( 'go2wpf_do_users_occupation', $user['occupation'] );
						}
						
						if( empty( $user['groupid'] ) && method_exists( $this->$board, 'get_groupid' ) ) {
							$user['groupid'] = $this->$board->get_groupid( $user['old_id'] );
						}
						
						if( array_key_exists( 'groupid', $user ) ) {
							$user['groupid'] = $this->get_new_id( 'usergroup', $user['groupid'] );
						} elseif( array_key_exists( 'group_name', $user ) ) {
							if( is_serialized( $user['group_name'] ) ) {
								$unsrlz             = unserialize( $user['group_name'] );
								$user['group_name'] = implode( ',', $unsrlz );
							}
							$user['groupid'] = $this->get_new_id( 'usergroup', go2wpf_str_to_int_id( $user['group_name'] ) );
						}
						
						if( empty( $user['groupid'] ) ) $user['groupid'] = WPF()->usergroup->default_groupid;
						
						if( method_exists( $this->$board, 'get_secondary_groupids' ) ) {
							$user['secondary_groupids'] = $this->$board->get_secondary_groupids( $user['old_id'] );
							$user['secondary_groupids'] = array_map( [ $this, 'get_new_groupid' ], $user['secondary_groupids'] );
							$user['secondary_groupids'] = array_diff( $user['secondary_groupids'], (array) $user['groupid'] );
							$user['secondary_groupids'] = array_unique( array_filter( $user['secondary_groupids'] ) );
						}
						
						$user['user_pass1'] = $this->get_rand_password();
						
						$sanitized_user_login = sanitize_user( $user['user_login'] );
						
						if( $email_exists_uid = email_exists( $user['user_email'] ) ) {
							$new_id = $email_exists_uid;
						} else {
							if( username_exists( trim( sanitize_user( $sanitized_user_login, true ) ) ) ) {
								$sanitized_user_login .= "_" . time();
							}
							$u_id = wp_create_user( $sanitized_user_login, $user['user_pass1'], $user['user_email'] );
							if( ! is_wp_error( $u_id ) && $u_id ) {
								$new_id = $user['userid'] = $u_id;
								
								if( ! empty( $user['user_registered'] ) ) {
									$this->db->update(
										$this->db->users,
										[ 'user_registered' => $user['user_registered'] ],
										[ 'ID' => $new_id ],
										[ '%s' ], [ '%d' ]
									);
								}
								
								$user['avatar_type'] = 'remote';
								$user['avatar_url']  = $this->do_avatar( $user );
								unset( $user['avatar'] );
								
								WPF()->member->update_user_fields( $u_id, $user, false );
								WPF()->member->update_profile_fields( $u_id, $user, false );
							}
						}
						
					}
					$this->add_log( 'user', $user['old_id'], $new_id );
				}
				WPF()->notice->clear();
				
				return false;
			}
		}
		
		WPF()->member->synchronize_users();
		WPF()->member->init_current_user();
		WPF()->notice->clear();
		
		return true;
	}
	
	private function do_forums( $board ) {
		if( method_exists( $this->$board, 'get_extracats' ) && ( $extracats = $this->$board->get_extracats() ) ) {
			foreach( $extracats as $extracat ) {
				$new_id = 0;
				if( ! $this->get_new_id( 'extracat', $extracat['old_id'] ) ) {
					$extracat['layout'] = $this->layout;
					
					if( isset( $extracat['description'] ) ) {
						$extracat['description'] = apply_filters( 'go2wpf_do_forums_desc', $extracat['description'] );
					}
					
					if( $f_id = WPF()->forum->add( $extracat ) ) $new_id = $f_id;
				}
				$this->add_log( 'extracat', $extracat['old_id'], $new_id );
			}
			$fid = 0;
		} else {
			$fid = WPF()->forum->add( [ 'title' => 'Migrated Forums', 'layout' => $this->layout ] );
		}
		
		if( $forums = $this->$board->get_forums() ) {
			foreach( $forums as $forum ) {
				if( ! $fid && isset( $forum['extracat'] ) && ( $forum['extracat'] = intval( $forum['extracat'] ) ) ) $fid = $this->get_new_id( 'extracat', $forum['extracat'] );
				$new_id = 0;
				if( ! $this->get_new_id( 'forum', $forum['old_id'] ) ) {
					$forum['parentid'] = ( isset( $forum['parentid'] ) && $forum['parentid'] ? $this->get_new_id( 'forum', $forum['parentid'] ) : $fid );
					$forum['layout']   = $this->layout;
					
					if( isset( $forum['description'] ) ) {
						$forum['description'] = apply_filters( 'go2wpf_do_forums_desc', $forum['description'] );
					}
					
					if( $f_id = WPF()->forum->add( $forum ) ) $new_id = $f_id;
				}
				$this->add_log( 'forum', $forum['old_id'], $new_id );
			}
		}
		WPF()->notice->clear();
	}
	
	private function do_topics( $board ) {
		$lastid = $this->get_last_old_id( 'topic' );
		if( $topics = $this->$board->get_topics( $lastid ) ) {
			$sticky_topicids = [];
			if( method_exists( $this->$board, 'get_sticky_topicids' ) ) {
				$sticky_topicids = $this->$board->get_sticky_topicids();
			}
			foreach( $topics as $topic ) {
				$new_id = 0;
				if( ! $this->get_new_id( 'topic', $topic['old_id'] ) ) {
					if( ! $this->sdb_is_current_wpdb ) $topic['userid'] = $this->get_new_id( 'user', $topic['userid'] );
					if( isset( $topic['private'] ) && $topic['private'] == 0 ) unset( $topic['private'] );
					$topic['forumid'] = $this->get_new_id( 'forum', $topic['forumid'] );
					if( in_array( $topic['old_id'], $sticky_topicids ) ) $topic['type'] = 1;
					$topic['type'] = (int) (bool) wpfval( $topic, 'type' );
					
					$topic['body'] = apply_filters( 'go2wpf_do_topics_body', $topic['body'], $topic );
					
					if( $t_id = WPF()->topic->add( $topic ) ) $new_id = $t_id;
				}
				$this->add_log( 'topic', $topic['old_id'], $new_id );
			}
			WPF()->notice->clear();
			
			return false;
		}
		WPF()->notice->clear();
		
		return true;
	}
	
	private function do_replies( $board ) {
		$lastid = $this->get_last_old_id( 'reply' );
		if( $replies = $this->$board->get_replies( $lastid ) ) {
			foreach( $replies as $reply ) {
				$new_id = 0;
				if( ! $this->get_new_id( 'reply', $reply['old_id'] ) ) {
					if( ! $this->sdb_is_current_wpdb ) $reply['userid'] = $this->get_new_id( 'user', $reply['userid'] );
					$reply['topicid'] = $this->get_new_id( 'topic', $reply['topicid'] );
					
					$reply['body'] = apply_filters( 'go2wpf_do_replies_body', $reply['body'], $reply );
					
					if( $r_id = WPF()->post->add( $reply ) ) $new_id = $r_id;
				}
				$this->add_log( 'reply', $reply['old_id'], $new_id );
			}
			WPF()->notice->clear();
			
			return false;
		}
		if( method_exists( $this->$board, 'get_comments' ) ) return $this->do_comments( $board );
		WPF()->notice->clear();
		
		return true;
	}
	
	private function do_comments( $board ) {
		$lastid = $this->get_last_old_id( 'comment' );
		if( $comments = $this->$board->get_comments( $lastid ) ) {
			foreach( $comments as $comment ) {
				$new_id = 0;
				if( ! $this->get_new_id( 'comment', $comment['old_id'] ) ) {
					if( ! $this->sdb_is_current_wpdb ) $comment['userid'] = $this->get_new_id( 'user', $comment['userid'] );
					$comment['topicid'] = $this->get_new_id( 'topic', $comment['topicid'] );
					$parentid           = $this->get_new_id( 'reply', $comment['parentid'] );
					if( ! $parentid ) {
						if( $parentid = $this->get_new_id( 'topic', $comment['parentid'] ) ) {
							if( $topic = WPF()->topic->get_topic( $parentid ) ) $parentid = $topic['first_postid'];
						}
					}
					$comment['parentid'] = $parentid;
					
					$comment['body'] = apply_filters( 'go2wpf_do_comments_body', $comment['body'], $comment );
					
					if( $c_id = WPF()->post->add( $comment ) ) $new_id = $c_id;
				}
				$this->add_log( 'comment', $comment['old_id'], $new_id );
			}
			WPF()->notice->clear();
			
			return false;
		}
		WPF()->notice->clear();
		
		return true;
	}
	
	public function do_avatar( $user ) {
		$avatar_url = '';
		if( $this->sdirs_avatar && ! empty( $user['avatar'] ) ) {
			if( strpos( (string) $user['avatar'], 'http' ) === false ) {
				$from = go2wpf_fix_directory( $this->sdirs_avatar . "/" . basename( (string) $user['avatar'] ) );
				if( $from = apply_filters( 'go2wpf_do_avatar_from', $from, $user ) ) {
					$from = go2wpf_fix_directory( $from );
					
					$ext = '.jpg';
					if( ! empty( $user['avatar_ext'] ) ) {
						$ext = "." . trim( trim( (string) $user['avatar_ext'], '. ' ) );
					} else {
						$filetype = wp_check_filetype( basename( (string) $from ) );
						if( $filetype['ext'] ) {
							$ext = "." . $filetype['ext'];
						}
					}
					$avatar_basename = sanitize_user( $user['user_login'] ) . '_' . $user['userid'] . $ext;
					
					$to = go2wpf_fix_directory( $this->avatar_basedir . "/" . $avatar_basename );
					if( file_exists( $from ) && copy( $from, $to ) ) {
						$avatar_url = $this->avatar_baseurl . "/" . $avatar_basename;
					}
				}
			} else {
				$avatar_url = $user['avatar'];
			}
		}
		
		return $avatar_url;
	}
	
	public function do_attach( $attach ) {
		$html = '';
		if( ! $this->sdirs_attach ) return $html;
		
		$attach = apply_filters( 'go2wpf_before_do_attach', $attach );
		
		if( empty( $attach['hash_filename'] ) ) return $html;
		
		$userid = ( ! empty( $attach['userid'] ) && ( $u_id = $this->get_new_id( 'user', $attach['userid'] ) ) ? $u_id : 0 );
		
		$filetype = wp_check_filetype( $attach['filename'] );
		if( ! $filetype['ext'] ) $attach['filename'] .= "." . trim( (string) $attach['ext'], '.' );
		$filename = $attach['postid'] . "=" . $attach['attachid'] . "-" . sanitize_file_name( $attach['filename'] );
		
		$attach_baseurl = $this->attach_baseurl . "/" . $userid;
		$attach_basedir = go2wpf_fix_directory( $this->attach_basedir . "/" . $userid );
		if( ! is_dir( $attach_basedir ) ) wp_mkdir_p( $attach_basedir );
		
		$attach_thumb_basedir = go2wpf_fix_directory( $attach_basedir . "/thumbnail" );
		if( ! is_dir( $attach_thumb_basedir ) ) wp_mkdir_p( $attach_thumb_basedir );
		
		$from     = go2wpf_fix_directory( $this->sdirs_attach . "/" . $attach['hash_filename'] );
		$to       = go2wpf_fix_directory( $attach_basedir . "/" . $filename );
		$to_thumb = go2wpf_fix_directory( $attach_thumb_basedir . "/" . $filename );
		
		if( file_exists( $from ) && copy( $from, $to ) ) {
			$fileurl  = $attach_baseurl . "/" . $filename;
			$filetype = wp_check_filetype( $filename );
			$mime     = $filetype['type'];
			
			if( strpos( (string) $mime, 'image/' ) !== false ) {
				$has_thumb = false;
				if( ! empty( $attach['hash_thumb_filename'] ) ) {
					$from_thumb = go2wpf_fix_directory( $this->sdirs_attach . "/" . $attach['hash_thumb_filename'] );
					if( file_exists( $from_thumb ) && copy( $from_thumb, $to_thumb ) ) {
						$has_thumb = true;
					}
				}
				if( ! $has_thumb ) copy( $from, $to_thumb );
			}
			
			if( function_exists( 'WPF_ATTACH' ) ) {
				if( ! $attachid = $this->get_new_id( 'attach', $attach['attachid'] ) ) {
					$size = filesize( $to );
					$args = compact( 'userid', 'filename', 'fileurl', 'size', 'mime' );
					if( $attachid = WPF_ATTACH()->add( $args ) ) {
						$this->add_log( 'attach', $attach['attachid'], $attachid );
					}
				}
				
				if( $shortcode = WPF_ATTACH()->tools->make_shortcode( $attachid ) ) $html = $shortcode;
			} else {
				$html = go2wpf_attach_make_html( $fileurl, $attach['filename'], $mime );
			}
		}
		
		if( $html ) $this->current_post_attachids[] = $attach['attachid'];
		
		return $html;
	}
	
	private function show_unset_sessions_button( $board ) {
		if( ! isset( $_GET['convert'] ) ) : ?>
            <form action="<?php echo wpforo_get_request_uri(); ?>" method="post" style="text-align: right; padding: 0 10px 20px;">
                <input class="button" type="submit" name="go2wpf_sdb_config_remove" value="Change <?php echo strtoupper( (string) $board ) ?> Configurations">
            </form>
		<?php endif;
	}
	
	private function show_db_config_form( $board ) {
		?>
        <style type="text/css">
            #go2db_config #use-current-db-configs {
                cursor: pointer;
                font-size: 1.3em;
            }

            #go2db_config input[type="reset"] {
                cursor: pointer;
                color: red;
                font-size: 1.3em;
            }

            #go2db_config input[type="reset"]:hover {
                opacity: 0.6;
            }

            #go2db_config input[type="submit"] {
                float: right;
                margin-top: 10px;
                width: 100%;
                max-width: 500px;
                height: 50px;
                font-size: 2em;
            }

            #go2db_config input[type="text"] {
                box-sizing: border-box;
                width: 100%;
                max-width: 500px;
                height: 38px;
                font-size: 1.2em;
                font-weight: normal;
                padding: 10px;
            }

            #go2db_config label {
                font-size: 1.2em;
            }

            #go2db_config fieldset {
                border: 1px solid black;
                padding: 40px 20px;
                text-align: center;
            }

            #go2db_config table {
                margin-left: auto;
                margin-right: auto;
            }

            input::placeholder {
                color: #777777;
                font-size: 1em;
                font-style: italic;
            }
        </style>
        <script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('#use-current-db-configs').on('click', function () {
					$('#go2db_config input[type="text"]').each(function () {
						$(this).val($(this).data('current'));
					});
				});
			});
        </script>
        <h1 style="color: gray">Go2wpForo</h1><br/>
        <div style="margin: 10px;">
            <form id="go2db_config" action="<?php echo wpforo_get_request_uri() ?>" method="post">
                <fieldset>
                    <legend style="margin-left: 5px; font-size: 2em"><?php echo strtoupper( (string) $board ) ?> Database Connection</legend>

                    <div style="float: left; width: 50%; min-width: 400px; padding: 20px; box-sizing: border-box;">
                        <table>
                            <tr>
                                <td><label for="dbhost"><input id="dbhost" type="text" name="go2wpf_sdb_config[dbhost]" data-current="<?php echo $this->sdb_config['dbhost'] ?>"
                                                               placeholder="Database Host" required autofocus></label></td>
                            </tr>
                            <tr>
                                <td><label for="dbuser"><input id="dbuser" type="text" name="go2wpf_sdb_config[dbuser]" data-current="<?php echo $this->sdb_config['dbuser'] ?>"
                                                               placeholder="Database Username" required></label></td>
                            </tr>
                            <tr>
                                <td><label for="dbpassword"><input id="dbpassword" type="text" name="go2wpf_sdb_config[dbpassword]" data-current="<?php echo $this->sdb_config['dbpassword'] ?>"
                                                                   placeholder="Database Password"></label></td>
                            </tr>
                            <tr>
                                <td><label for="dbname"><input id="dbname" type="text" name="go2wpf_sdb_config[dbname]" data-current="<?php echo $this->sdb_config['dbname'] ?>"
                                                               placeholder="Database Name" required></label></td>
                            </tr>
                            <tr>
                                <td><label for="dbprefix"><input id="dbprefix" type="text" name="go2wpf_sdb_config[dbprefix]" data-current="<?php echo $this->sdb_config['dbprefix'] ?>"
                                                                 placeholder="Database Table Prefix"></label></td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="dbsecondprefix"><input id="dbsecondprefix" type="text" name="go2wpf_sdb_config[dbsecondprefix]"
                                                                       data-current="<?php echo $this->sdb_config['dbsecondprefix'] ?>" placeholder="Database Table Second Prefix"></label>
                                    <p style="font-size: 13px; line-height: 18px; margin:5px 0; ">Leave "Second Prefix" field blank if your source forum doesn't have the second prefix.</p>
                                </td>
                            </tr>
                            <tr>
                                <td align="right">
                                    <div style="margin: 0 auto;  width: 100%; max-width: 500px;">
                                        <a id="use-current-db-configs">Use current database connection credentials</a>&nbsp;&nbsp;|&nbsp;
                                        <input type="reset" value="reset">
                                        <p style="font-size: 13px; line-height: 18px; margin:5px 0; ">If <?php echo strtoupper( (string) $board ) ?> database tables are located in current WordPress
                                            database you can click on the <span style="color: #0073aa;">Use current database connection credentials</span> link button above and fill db connection
                                            details.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td align="right">
                                    <input class="button button-primary" type="submit" name="go2wpf_sdb_config_submit" value="Connect">
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div style="float: left; width: 45%; min-width: 400px; padding: 20px; box-sizing: border-box;">
                        <table style="margin:0 auto; width: 100%;">
                            <tbody>
                            <tr>
                                <td style="text-align:left; background:#fff; padding:20px 20px 0; font-size:15px; line-height: 22px; font-style: italic;" colspan="3">
                                    <h4 style="margin: 0;">What migrates this tool?</h4>
                                    We do our best to improve this free migration tool as much as possible, however that's very hard to make migration tool for all kind and size of forums.
                                    This tool is designed for default forum components migration like Forums, Topics, Posts, Attachments and Users. This will not migrate data like PMs, Polls, etc...
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align:left; background:#fff; padding:20px; font-size:15px; line-height: 22px; font-style: italic;" colspan="3">
                                    <h4 style="margin: 0;">Is there any support for this tool?</h4>
                                    Forum to Forum migration is a large project. We're sorry, but we cannot support issues arisen during your forum migration.
                                    <u>We only support questions related how to use and configure this tool in <a href="https://wpforo.com/community/" target="_blank">wpForo support forum</a>.</u> We
                                    don't support the result of migration. This kind of large work is out of our support.
                                    In case the migration result of this tool doesn't satisfy you, you should contact to professional forum migration services for custom migration.
                                    We only recommend <a href="https://gconverters.com/forum-to-forum-migration/" target="_blank">gConverter</a> or <a
                                            href="https://profprojects.com/migration-services/#forum" target="_blank">ProfProjects</a> services for such projects.
                                    They have already done hundreds of forum to wpForo migration projects with the best result.
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="clear: both"></div>


                </fieldset>
            </form>
        </div>
		<?php
	}
	
	private function show_source_dirs_form( $board ) {
		?>
        <br/>
        <style type="text/css">
            input::placeholder {
                color: #999999;
                font-size: 1em;
                font-style: italic;
            }

            input.button.go2wpf_all_OK {
                width: 59%;
                height: 40px;
                font-size: 1.3em;
            }
        </style>
        <form action="<?php echo wpforo_get_request_uri() ?>" method="post">
            <fieldset style="border: 1px solid #aaaaaa; padding: 25px; margin: 30px 5%;">
                <legend style="margin-left: 5px; font-size: 2em;">Set <?php echo strtoupper( (string) $board ) ?> File Paths</legend>
                <table width="100%">
                    <tr>
                        <td style="padding: 5px 5px 10px 5px; font-size: 14px; font-weight: 600; color: #0A246A;">Current wpForo files directory:</td>
                        <td style="padding: 5px 5px 10px 5px; color: #0A246A; font-size: 14px; font-family: 'Courier New', Courier, monospace">
							<?php echo $this->upload_basedir ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="line-height: 20px; padding-bottom: 10px;">
							<?php if( $board == 'phpbb' ): ?>
								<?php echo strtoupper( (string) $board ) ?> avatars and attachments folders should be moved to <span style="color:#0A246A;">wpForo files directory</span>.<br>
                                1. Please create a new folder for example <span style="color: #DD0000;">/source/</span> in <span style="color:#0A246A;">wpForo files directory</span>.
                                <br>2. Copy or just move <?php echo strtoupper( (string) $board ) ?> avatars and attachments folders to this folder.
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; Find <?php echo strtoupper( (string) $board ) ?> avatar folder (by default): &nbsp;./images/avatars<span
                                        style="color: #0000dd;">/upload/</span>
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; Find <?php echo strtoupper( (string) $board ) ?> attachments folder (by default): &nbsp;.<span style="color: #0000dd;">/files/</span>
                                <br>3. Then delete <span style="color: #7b1fa2;">.htaccess</span> files from just moved <?php echo strtoupper(
									(string) $board
								) ?> avatars and attachments folders. This file may restrcit access during the import process.
                                <br>4. After these steps the end full path to the source <?php echo strtoupper( (string) $board ) ?> avatars and attachments folders should be:
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; Full path to <?php echo strtoupper( (string) $board ) ?> avatars folder: &nbsp; <code
                                        style="background: transparent;"><?php echo $this->upload_basedir ?><span style="color: #DD0000;"><?php echo DIRECTORY_SEPARATOR ?>source</span><span
                                            style="color: #0000DD;"><?php echo DIRECTORY_SEPARATOR ?>upload</span></code>
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; Full path to <?php echo strtoupper( (string) $board ) ?> attachments folder: &nbsp; <code
                                        style="background: transparent;"><?php echo $this->upload_basedir ?><span style="color: #DD0000;"><?php echo DIRECTORY_SEPARATOR ?>source</span><span
                                            style="color: #0000DD;"><?php echo DIRECTORY_SEPARATOR ?>files</span></code>
                                <br>5. Fill according fields below and click on [Next Step &gt;] button.
							<?php elseif( $board == 'mybb' ): ?>
								<?php echo strtoupper( (string) $board ) ?> avatars and attachments folders should be moved to <span style="color:#0A246A;">wpForo files directory</span>.<br>
                                <br>1. Copy or just move <?php echo strtoupper( (string) $board ) ?> <span style="color: #0000dd;">/uploads/</span> folders to the <span style="color:#0A246A;">wpForo files directory</span>, it contains avatars as well as attachments.
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; <?php echo strtoupper( (string) $board ) ?> avatar folder (by default): &nbsp;./uploads<span style="color: #0000dd;">/avatars/</span>
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; <?php echo strtoupper( (string) $board ) ?> attachments folder (by default): &nbsp;.<span style="color: #0000dd;">/uploads/</span>
                                <br>2. After this step the end full path to the source <?php echo strtoupper( (string) $board ) ?> avatars and attachments folders should be:
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; Full path to <?php echo strtoupper( (string) $board ) ?> avatars folder: &nbsp; <code
                                        style="background: transparent;"><?php echo $this->upload_basedir ?><span
                                            style="color: #0000DD;"><?php echo DIRECTORY_SEPARATOR ?>uploads<?php echo DIRECTORY_SEPARATOR ?>avatars</span></code>
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; Full path to <?php echo strtoupper( (string) $board ) ?> attachments folder: &nbsp; <code
                                        style="background: transparent;"><?php echo $this->upload_basedir ?><span style="color: #0000DD;"><?php echo DIRECTORY_SEPARATOR ?>uploads</span></code>
                                <br>3. Fill according fields below and click on [Next Step &gt;] button.
							<?php elseif( $board == 'smf' ): ?>
								<?php echo strtoupper( (string) $board ) ?> avatars and attachments folders should be moved to <span style="color:#0A246A;">wpForo files directory</span>.<br>
                                1. Copy or just move <?php echo strtoupper( (string) $board ) ?> <span style="color: #0000dd;">/attachments/</span> folders to the <span style="color:#0A246A;">wpForo files directory</span>, it contains avatars as well as attachments.
                                <br>2. Then delete <span style="color: #7b1fa2;">.htaccess</span> file from just moved <?php echo strtoupper( (string) $board ) ?> <span style="color: #0000dd;">/attachments/</span> folders. This file may restrcit access during the import process.
                                <br>3. After these steps the end full path to the source <?php echo strtoupper( (string) $board ) ?> avatars and attachemnts folders should be:
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; Full path to <?php echo strtoupper( (string) $board ) ?> avatars folder: &nbsp; <code
                                        style="background: transparent;"><?php echo $this->upload_basedir ?><span style="color: #0000DD;"><?php echo DIRECTORY_SEPARATOR ?>attachments</span></code>
                                <br> &nbsp;&nbsp;&nbsp;-&nbsp; Full path to <?php echo strtoupper( (string) $board ) ?> attachments folder: &nbsp; <code
                                        style="background: transparent;"><?php echo $this->upload_basedir ?><span style="color: #0000DD;"><?php echo DIRECTORY_SEPARATOR ?>attachments</span></code>
                                <br>4. Fill according fields below and click on [Next Step &gt;] button.
							<?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 30%;padding: 5px; font-size: 14px; font-weight: 600;"><label for="avatar_path">Full path to <?php echo strtoupper( (string) $board ) ?> avatars
                                folder:</label></td>
                        <td style="padding: 5px;">
                            <input id="avatar_path" name="go2wpf_sdirs[avatar]" placeholder="leave empty if <?php echo $board ?> users don't have avatars"
                                   style="width: 60%; border: 1px solid #cccccc; padding: 5px 10px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 5px; font-size: 14px; font-weight: 600;"><label for="attach_path">Full path to <?php echo strtoupper( (string) $board ) ?> attachments folder:</label></td>
                        <td style="padding: 5px;">
                            <input id="attach_path" name="go2wpf_sdirs[attach]" placeholder="leave empty if there is no attachments in forum"
                                   style="width: 60%; border: 1px solid #cccccc; padding: 5px 10px;">
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td style="line-height: 20px; padding-bottom: 10px; padding-left: 10px;">
                            <input class="button button-primary  go2wpf_all_OK" style="margin-top: 20px;" type="submit" value="Next Step &gt;"/>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="line-height: 20px; padding-bottom: 10px; font-size: 14px; font-style: italic;">
                            <h3 style="margin: 5px 0; font-weight: 400;">Important Notes</h3>
                            <ul style="margin: 10px 30px; list-style: disc;">
                                <li>If you're transferring <?php echo strtoupper( (string) $board ) ?> avatars and attachments folders from other hosting servers using file transfer software like
                                    FileZilla, TotalCommander or CuteFTP,
                                    please make sure you download and upload all files with <a
                                            href="https://www.templatemonster.com/help/how-to-set-binary-transfer-mode-in-filezilla-totalcommander-and-cuteftp.html" target="_blank" rel="noreferrer">Binary
                                        Mode</a>, otherwise all files will be corrupted.
                                </li>
                                <li>If you've already got the <a href="https://gvectors.com/product/wpforo-advanced-attachments/" target="_blank" rel="noreferrer">wpForo Advanced Attachments</a>
                                    addon, please keep it activated to let Go2wpForo tool import all attachments in this powerful attachment system. If you don't have this addon, all attachments will
                                    be imorted to the default file attachment system.
                                </li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </fieldset>
        </form>
        <br/>
		<?php
	}
	
	private function show_connections_status( $board ) {
		$go2wpf_all_OK = [ (bool) $this->sdbh ];
		?>
        <style type="text/css">
            #go2wpf_connect_status_wrap {
                width: 95%;
                padding: 2px;
                margin-left: auto;
                margin-right: auto;
            }

            #go2wpf_connect_status_wrap table#go2wpf_connect_status {
                border-collapse: collapse;
                width: 100%;
                font-size: 1.3em;
            }

            table#go2wpf_connect_status th {
                text-align: left;
            }

            table#go2wpf_connect_status tr {
                border-bottom: 1px solid black !important;
            }

            table#go2wpf_connect_status td,
            table#go2wpf_connect_status th {
                padding: 10px 5px;
            }

            table#go2wpf_connect_status span {
                font-weight: bold;
            }

            #go2wpf_connect_status_wrap input.button.go2wpf_all_OK {
                width: 300px;
                height: 40px;
                font-size: 1.3em;
            }

            code {
                font-size: 14px;
            }
        </style>
        <h1 style="color: gray"><?php echo strtoupper( (string) $board ) ?> to wpForo Migration</h1><br/>
        <div id="go2wpf_connect_status_wrap">
			<?php $this->show_unset_sessions_button( $board ) ?>
            <fieldset style="border: 1px solid #aaaaaa; padding: 35px;">
                <legend style="margin-left: 5px; font-size: 2em">Check provided information</legend>
                <table id="go2wpf_connect_status">
                    <tr>
                        <th>Checked information</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td><?php echo strtoupper( (string) $board ) ?> Database Connection</td>
                        <td><?php echo( $this->sdbh ? '<span style="color: #00A321">OK</span>' : '<span style="color: orangered">NOT CONNECTED</span>' ) ?></td>
                    </tr>
					<?php if( $this->sdbh ) :
						$prefix_found = $go2wpf_all_OK[] = (bool) $this->sdb->get_results( "SHOW TABLES LIKE '" . $this->sdb->base_prefix . "%'", ARRAY_A ); ?>
                        <tr>
                            <td><?php echo strtoupper( (string) $board ) ?> Database Tables Prefix <b><?php echo $this->sdb->base_prefix ?></b></td>
                            <td><?php echo( $prefix_found ? '<span style="color: #00A321">OK</span>' : '<span style="color: orangered">NOT FOUND</span>' ) ?></td>
                        </tr>
						
						<?php if( $this->sdb_config['dbsecondprefix'] ) :
						$secondprefix_found = $go2wpf_all_OK[] = (bool) $this->sdb->get_results( "SHOW TABLES LIKE '" . $this->sdb->prefix . "%'", ARRAY_A ); ?>
                        <tr>
                            <td><?php echo strtoupper( (string) $board ) ?> Database Tables With Second Prefix <b><?php echo $this->sdb->prefix ?></b></td>
                            <td><?php echo( $secondprefix_found ? '<span style="color: #00A321">OK</span>' : '<span style="color: orangered">NOT FOUND</span>' ) ?></td>
                        </tr>
					<?php endif ?>
					<?php endif ?>
					<?php if( ! empty( $_SESSION['go2wpf_sdirs'] ) ):
						foreach( $_SESSION['go2wpf_sdirs'] as $key => $dir ) :
							if( $dir ) :
								$is_dir = $go2wpf_all_OK[] = is_dir( $dir );
								[ $status, $status_color ] = ( $is_dir ? [ 'OK', '#00A321' ] : [ 'NOT FOUND', 'orangered' ] ); ?>
                                <tr>
                                    <td><?php echo strtoupper( (string) $board ) ?> - <?php echo $key ?> - path - <code style="background: transparent"><?php echo $dir ?></code></td>
                                    <td><span style="color: <?php echo $status_color; ?>"><?php echo $status ?></span></td>
                                </tr>
							<?php endif;
						endforeach;
					endif; ?>
                </table>
				<?php if( go2wpf_array_values_is_all_true( $go2wpf_all_OK ) ) : ?>
                    <form action="<?php echo wpforo_get_request_uri(); ?>" method="post" style="text-align: right; padding: 20px 5px;">
                        <input class="button button-primary go2wpf_all_OK" type="submit" name="go2wpf_all_OK" value="Next Step &gt;">
                    </form>
				<?php endif; ?>
            </fieldset>
        </div>
		<?php
	}
	
	private function form_elements( $board ) {
		if( ! $this->sdb_is_current_wpdb ) {
			if( $groups = $this->$board->get_usergroups() ) { ?>
                <tr>
                    <td colspan="2">
                        <h3>Map <?php echo strtoupper( (string) $board ) ?> usergroups to wpForo usergroups</h3>
                        <p style="max-width: 900px;margin: 0; font-size: 13px; font-style: italic; line-height: 20px;">
                            This options allows to set wpForo Usergroups to imported users based on their Usergroups in <?php echo strtoupper( (string) $board ) ?> forum.
                            If you don't have enough wpForo Usergroups, you can create new usergroups in Dashboard > Forums > Usergroup admin page in a new browser Tab. Then refresh this page and find
                            new Usergroups in wpForo Usergroup selectors.</p>
                    </td>
                </tr>
				<?php
				foreach( $groups as $group ) :
					if( ! array_key_exists( 'groupid', $group ) ) {
						if( is_serialized( $group['name'] ) ) {
							$unsrlz        = unserialize( $group['name'] );
							$group['name'] = implode( ',', $unsrlz );
						}
						$group['groupid'] = go2wpf_str_to_int_id( $group['name'] );
					}
					?>
                    <tr>
                        <td align="right" width="50%"><label for="go2wpf_groups_<?php echo $group['groupid'] ?>">" <b><?php echo $group['name'] ?></b> " to wpForo</label></td>
                        <td align="left">
                            <select id="go2wpf_groups_<?php echo $group['groupid'] ?>" name="go2wpf_groups[<?php echo $group['groupid'] ?>]">
								<?php WPF()->usergroup->show_selectbox( WPF()->usergroup->default_groupid ) ?>
                            </select>
                        </td>
                    </tr>
				<?php endforeach;
			}
		}
	}
	
	private function init_action( $board ) { ?>
        <h1 style="color: gray"><?php echo strtoupper( (string) $board ) ?> to wpForo Migration</h1><br/>
		<?php
		$this->show_unset_sessions_button( $board );
		
		if( ! $this->$board->get_forums_count() || ! $this->$board->get_topics_count() || ! $this->$board->get_posts_count() ) {
			mg2wpforo_admin_notice__no_forum_found();
			
			return;
		}
		?>
        <div style="margin: 10px; padding: 0 30px;">
			<?php if( isset( $_GET['convert'] ) ) : ?>

                <center>
                    <img alt="" src="<?php echo MG2WPFORO_URL ?>/loading.gif"/><br/>
                    <span style="color: #7C8183; font-size: 3em;">Processing <b style="color: #028FB1;"><?php echo strtoupper( (string) $_GET['convert'] ) ?></b> migration . . .</span><br/><br/>
                    <span style="color: #CB451F; font-size: 3em;">don't stop or close browser</span><br/><br/>
					<?php $this->show_progress_bar( $board ) ?>
                </center>
			
			<?php else : ?>

                <fieldset style="padding: 25px;border: 1px solid #aaaaaa;">
                    <legend style="margin-left: 5px; font-size: 2em">Statistics</legend>
					<?php $this->show_statistics_table( $board ) ?>
                </fieldset>
                <br/>
				
				<?php if( ! isset( $_GET['mg2wpforo'] ) || $_GET['mg2wpforo'] != 'finish' ) : ?>
                    <form action="<?php echo wpforo_get_request_uri() ?>&action=show&convert=start" method="post"
                          onsubmit="return confirm('if you are doing again this action for this forum will be duplicated ( click CANCEL for break action or click OK for the continuing )');">
                        <fieldset style="padding: 25px;border: 1px solid #aaaaaa; margin-top: 10px;">
                            <legend style="margin-left: 5px; font-size: 2em">Import Options</legend>
                            <table width="100%">
                                <tr>
                                    <td align="left">
                                        <label style="font-size: 14px; font-weight: bold;">Forum Layout</label>
                                        <p style="margin: 0; font-size: 13px; font-style: italic; line-height: 28px;">Please select one of wpForo <a
                                                    href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/" rel="noreferrer" target="_blank">forum layouts</a> you'd like to set for
                                            imported forums.</p>
                                    </td>
                                    <td align="left">
                                        <label for="layout">
                                            <select id="layout" name="layout">
												<?php WPF()->tpl->show_layout_selectbox( 2 ) ?>
                                            </select>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left">
                                        <label style="font-size: 14px; font-weight: bold;">Items to import per cycle</label>
                                    </td>
                                    <td align="left">
                                        <label for="itempercycle"><input style="width: 96px;" id="itempercycle" type="number" min="1" name="itempercycle" value="200"></label>
                                    </td>
                                </tr>
								<?php $this->form_elements( $board ); ?>
                            </table>
                        </fieldset>
                        <input class="button button-primary" style="float: right; margin-top: 20px; width: 500px; height: 50px; font-size: 2em;" type="submit" value="Start Migration"/>
                    </form>
				<?php endif ?>
			
			<?php endif ?>

        </div>
		
		<?php
	}
	
	public function do_actions() {
		if( ! wpforo_is_admin() || WPF()->current_user_groupid != 1 ) return;
		
		if( isset( $_GET['page'] ) && strpos( (string) $_GET['page'], '-mg2wpforo' ) !== false && $board = trim( basename( (string) $_GET['page'], '-mg2wpforo' ) ) ) {
			
			if( empty( $_GET['action'] ) && ! empty( $_GET['convert'] ) ) {
				wp_redirect( "Location: " . rtrim( preg_replace( '#&action=?#isu', '', wpforo_get_request_uri() ), '/' ) . '&action=show' );
				exit();
			}
			
			if( ! empty( $_SERVER['HTTP_REFERER'] ) && wpforo_is_session_started() && ! empty( $_SESSION['go2wpf_sdb_config'] ) ) {
				$HTTP_REFERER_QUERY = parse_url( $_SERVER['HTTP_REFERER'] );
				if( ! empty( $HTTP_REFERER_QUERY['query'] ) ) {
					parse_str( $HTTP_REFERER_QUERY['query'], $HTTP_REFERER );
					if( wpfkey( $HTTP_REFERER, 'page' ) && $HTTP_REFERER['page'] != $_GET['page'] ) {
						$this->unset_sessions();
						wp_redirect( wpforo_get_request_uri() );
						exit();
					}
				}
			}
			
			if( ! empty( $_POST['go2wpf_sdb_config_remove'] ) && wpforo_is_session_started() ) {
				$this->unset_sessions();
				wp_redirect( wpforo_get_request_uri() );
				exit();
			}
			
			if( ! empty( $_POST['go2wpf_all_OK'] ) && wpforo_is_session_started() ) {
				$_SESSION['go2wpf_all_OK'] = true;
				wp_redirect( wpforo_get_request_uri() );
				exit();
			}
			
			if( ! empty( $_POST['go2wpf_sdb_config'] ) && wpforo_is_session_started() ) {
				$_SESSION['go2wpf_sdb_config'] = $_POST['go2wpf_sdb_config'];
				wp_redirect( wpforo_get_request_uri() );
				exit();
			}
			
			if( ! empty( $_POST['go2wpf_sdirs'] ) && wpforo_is_session_started() ) {
				$sdirs                    = array_map( 'go2wpf_fix_directory', $_POST['go2wpf_sdirs'] );
				$_SESSION['go2wpf_sdirs'] = $sdirs;
				wp_redirect( wpforo_get_request_uri() );
				exit();
			}
			
			if( wpforo_is_session_started() && ! empty( $_SESSION['go2wpf_all_OK'] ) ) {
				if( ! empty( $_REQUEST['go2wpf_groups'] ) && is_array( $_REQUEST['go2wpf_groups'] ) ) {
					$this->clear_log();
					$this->do_usergroups();
				}
				
				if( isset( $_REQUEST['itempercycle'] ) ) $this->itempercycle = intval( $_REQUEST['itempercycle'] );
				if( ! empty( $_REQUEST['layout'] ) && ( $layout = intval( $_REQUEST['layout'] ) ) ) $this->layout = $layout;
				
				$request_uri = wpforo_get_request_uri();
				if( empty( $_GET['layout'] ) ) {
					$request_uri = rtrim( preg_replace( '#&layout=\d*#isu', '', $request_uri ), '/' ) . '&layout=' . $this->layout;
				}
				if( empty( $_GET['itempercycle'] ) ) {
					$request_uri = rtrim( preg_replace( '#&itempercycle=\d*#isu', '', $request_uri ), '/' ) . '&itempercycle=' . $this->itempercycle;
				}
				
				/* require_once( MG2WPFORO_DIR . "/includes/board.php" ); */
				require_once( MG2WPFORO_DIR . "/boards/$board.php" );
				
				if( isset( $_GET['convert'] ) && isset( $_GET['action'] ) ) {
					define( 'IS_GO2WPFORO', true );
					if( WPF()->sbscrb ) {
						remove_action( 'wpforo_after_add_topic', [ WPF()->sbscrb->Actions, 'after_add_topic' ] );
						remove_action( 'wpforo_after_add_post', [ WPF()->sbscrb->Actions, 'after_add_post' ] );
						remove_action( 'wpforo_after_add_topic', [ WPF()->sbscrb->Mentioning->Actions, 'send_mail_to_mentioned_users' ], 5 );
						remove_action( 'wpforo_after_add_post', [ WPF()->sbscrb->Mentioning->Actions, 'send_mail_to_mentioned_users' ], 5 );
						remove_action( 'wpforo_after_add_topic', [ WPF()->sbscrb->Follows->Actions, 'after_add_topic' ], 7 );
						remove_action( 'wpforo_after_add_post', [ WPF()->sbscrb->Follows->Actions, 'after_add_post' ], 7 );
					}
					remove_action( 'wpforo_after_add_post', [ WPF()->activity, 'after_add_post' ] );
					
					if( $_GET['action'] === 'do' ) {
						$search  = [ '&action=do' ];
						$replace = [ '&action=show' ];
						
						switch( $_GET['convert'] ) {
							case 'reply':
								if( $this->do_replies( $board ) ) {
									wp_redirect( admin_url( 'admin.php?page=' . $_GET['page'] . '&mg2wpforo=finish' ) );
									exit();
								}
							break;
							case 'topic':
								if( $this->do_topics( $board ) ) {
									$search[]  = '&convert=topic';
									$replace[] = '&convert=reply';
								}
							break;
							case 'user':
								if( $this->do_users( $board ) ) {
									$search[]  = '&convert=user';
									$replace[] = '&convert=forum';
								}
							break;
							case 'forum':
								$this->do_forums( $board );
								$search[]  = '&convert=forum';
								$replace[] = '&convert=topic';
							break;
							case 'usergroup':
								$search[]  = '&convert=usergroup';
								$replace[] = '&convert=user';
							break;
							case 'start':
								$search[]  = '&convert=start';
								$replace[] = '&convert=usergroup';
							break;
						}
						
						wp_redirect( str_replace( $search, $replace, $request_uri ) );
						exit();
					} elseif( $_GET['action'] === 'show' ) {
						header( "refresh:0.1;url=" . str_replace( '&action=show', '&action=do', $request_uri ) );
					}
				}
				
				if( isset( $_GET['mg2wpforo'] ) ) {
					add_action( 'admin_notices', 'mg2wpforo_admin_notice__finish' );
				}
			}
		}
	}
}

require_once( "includes/functions.php" );
