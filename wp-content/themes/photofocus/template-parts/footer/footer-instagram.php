<?php
/**
 * Displays footer instagram widget
 *
 * @package PhotoFocus
 */
?>

<?php
	if ( is_active_sidebar( 'sidebar-instagram' ) ) :
	?>
	<div id="footer-instagram" class="widget-area section" role="complementary">
		<div class="wrapper">
			<div class="footer-instagram">
				<?php dynamic_sidebar( 'sidebar-instagram' ); ?>
			</div>
		</div><!-- .wrapper -->
	</div><!-- .widget-area -->
<?php endif;
