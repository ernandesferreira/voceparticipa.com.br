<?php
/**
 * The template for displaying the footer.
 *
 * Contains footer content and the closing of the
 * #main div element.
 *
 * @package Odin
 * @since 2.2.0
 */
?>

		</div><!-- #main -->
		</div><!-- .container -->
		<div class="container-footer">
			<div class="faixa-2"></div>
			<div class="faixa-footer">
			<div class="container">
				<footer id="footer" role="contentinfo">
					<div class="site-info">
						<span>&copy; <?php echo date( 'Y' ); ?> <a href="<?php echo home_url(); ?>"><?php bloginfo( 'name' ); ?></a> - <?php _e( 'All rights reserved', 'odin' ); ?> | <?php echo sprintf( __( 'Desenvolvido por<a href="http://www.ernandesferreira.me"> ernandesferreira </a>.', 'odin' ), 'http://wpod.in/', 'http://wordpress.org/' ); ?></span>
					</div><!-- .site-info -->
				</footer><!-- #footer -->
			</div>
			</div>
		</div>

	<?php wp_footer(); ?>
</body>
</html>
