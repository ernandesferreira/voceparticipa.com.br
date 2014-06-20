<?php 

	/* 
	Template Name: Noticias - Blog
	*/ 

?>

<?php get_header();
$destaques = new WP_Query(array('post_type' => 'post', 'category_name=Destaques', 'posts_per_page' => '3'));

?>

	<article class="destaque-noticias" >
		<header class="panel-title"><?php _e('POSTS EM DESTAQUE', 'odin') ?></header>
		
			<ul class="slider-destaque-posts">
				<?php
					if ($destaques-> have_posts()) {
						while( $destaques-> have_posts()){
						 $destaques->the_post();
						 $imagem = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'destaque-noticias' );
				?>
				<li class="slide">

					<a href="<?php the_permalink(); ?>" >
						<img class="img-responsive img-thumbnail" src="<?php echo $imagem[0] ?>" width="405" height="251" >
												<div class="camada_pelicula" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0"></div>

						<div class="info" >
							<div class="titulo" ><?php echo get_the_title() ?></div>
							<p><?php echo wp_trim_words(get_the_content(), 12) ?></p>
						</div>

						<div class="camada_hover" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0">
							<span class='icon-plus-mult'>+</span> <?php _e("Saiba Mais","futura"); ?>
						</div>

					</a>
					
				</li>
				<?php }
				}
				?>
			</ul>

	</article>


	<nav class="nav-noticias">
	<div class="container">
		<div class="col-lg-12" >
		
			<?php 
					$defaults = array(
			            'theme_location'	=> 'menu-blog',
			            'container_class' 	=> 'col-lg-6',
			            'menu_class'      => 'menu-blog col-lg-6',
			            'depth'		      	=> '0'
			        );
					wp_nav_menu($defaults); ?>

					<form method="get" class="navbar-form navbar-right col-lg-6 busca-right" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
						<label for="navbar-search" class="sr-only"><?php _e( 'Search:', 'odin' ); ?></label>
						<span class=''><?php _e("O que deseja encontrar?","futura"); ?></span>
						<input type="text" class="form-control input-right" name="s" id="navbar-search" />
						<span class="glyphicon glyphicon-search search-lupa"></span>						
					</form>
					
		</div>
		</div>
		
	</nav>


	<article class="col-lg-12 content-noticias" >
			
		<section class="col-lg-8 posts-noticias" >

			<?php
				$query = new WP_Query(array('post_type'=>'post', 'posts_per_page'=>3));

				 if( $query->have_posts() ){
				 	$i = 0; #Variável contador
					while( $query->have_posts() ){
						$query->the_post();
						$imagem = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'padrao-noticias' );		

			?>
						
						<section class="item" >
							<?php
								if(get_the_category()){
									the_category();
								}
							?>
							<?php if($imagem[0]){ ?>
							<a href="<?php the_permalink() ?>" >
								<img class="img-responsive img-thumbnail" src="<?php echo $imagem[0] ?>" height="230" >
								<div class="camada_hover">
									<span class='icon-plus-mult'>+</span> <?php _e("Saiba Mais","odin"); ?>
								</div>
							</a>
							<?php } ?>
							<header class="titulo" ><a href="<?php the_permalink() ?>" ><?php the_title() ?></a></header>
							<div class="publicado" ><span><?php the_time('j \d\e F \d\e Y'); ?></span> - por <span><?php the_author_meta('nickname'); ?></span></div>
							<p><?php echo wp_trim_words(get_the_content(), 50) ?></p>
						</section>

			<?php 	
						$i++;
					}
				
				  }
			?>
			<div class="load-mais-posts" ></div>
			<div class="loading" ></div>
			<a href="#" class="btn mais-post" quantidade="<?php echo $i ?>" ><?php _e('MAIS POSTS', 'odin') ?></a>
		</section>
		
		<section class="col-lg-4 sidebar-noticias" >
			
			<?php
				$args = array('post_type' => 'post', 'posts_per_page' => '3', 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => 'visualizacao');
				$populares = new WP_Query( $args );

				if($populares->have_posts()){
			?>
				<section class="populares-noticias">
					<header class="panel-title"><?php _e( 'POSTS POPULARES', 'futura' ) ?></header>
					
						<ul>
						<?php
								while($populares->have_posts()){
									$populares->the_post();
									$imagem = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'populares-noticias' );
									$visualizados = get_post_meta( get_the_ID(), 'visualizacao' );
						?>
								
									<li class="box-light item">
										<div class="col-lg-4" >
											<img class="img-responsive img-thumbnail" src="<?php echo $imagem[0] ?>" width="125" height="90" >
										</div>
										<div class="col-lg-8" >
											<a href="<?php echo get_permalink(); ?>" >
											<div class="titulo" ><?php echo wp_trim_words(get_the_title(), 5); ?></div>
											<p><?php echo wp_trim_words(get_the_content(), 10); ?></p>
											</a>
											<span><span class="glyphicon glyphicon-calendar" ></span> <?php the_time('j M\, Y'); ?></span>
											<span><span class="glyphicon glyphicon-eye-open" ></span> <?php echo number_format($visualizados[0], 0, '', '.'); ?></span>
										</div>
									</li>
								
						<?php   } ?>
						</ul>
					
				</section>
			<?php } ?>

			<section class="social-noticias">
				<header class="panel-title"><?php _e( 'REDES SOCIAIS', 'odin' ) ?></header>		
				<section class="box-light compartilhar">
        			<div class="button-redes-sociais lista-videos">
        				<ul>
							<li><a href="<?php echo get_field('url-twitter', 'option'); ?>" class="icon-tt"><span class="dashicons dashicons-twitter"></span></a></li>
							<li><a href="<?php echo get_field('url-facebook', 'option'); ?>" class="icon-fb"><span class="dashicons dashicons-facebook-alt"></span></a></li>
							<li><a href="<?php echo get_field('url-google', 'option'); ?>" class="icon-gp"><span class="dashicons dashicons-googleplus"></span></a></li>
							<li><a href="<?php echo get_field('url-linkedin', 'option'); ?>" class="icon-in"><span class="dashicons dashicons-twitter"></span></a></li>
							<li><a href="<?php echo get_field('url-rss', 'option'); ?>" class="icon-rss"><span class="dashicons dashicons-rss"></span></a></li>
						</ul>
        			</div>
				</section>
			</section>

			<section class="enquete-noticias">
				<header class="panel-title"><?php _e( 'ENQUETE', 'odin' ) ?></header>		
				<section class="box-light">
					<h1> Título </h1>
					<div class="content-box-light resumo-enquete">
						<p>
							Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus feugiat risus in mauris 
							euismod, vel lobortis augue tempus. Sed rutrum ligula vel magna tincidunt volutpat. Integer 
							aliquam eu velit eget fermentum. Nunc gravida nibh sit amet varius tincidunt. 
						</p> 
					</div>
					<div class="radio">  
					    <input id="1" type="radio" name="futura" value="1">  
					    <label for="1">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sit amet ornare leo nullam.</label>  
					    
					    <input id="2" type="radio" name="futura" value="2">  
					    <label for="2">Lorem ipsum dolor sit amet, consectetur adipiscing.</label> 

					    <input id="3" type="radio" name="futura" value="3">  
					    <label for="3">
						    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer sit amet 
						    ornare leo. Sed accumsan dui sodales, rhoncus mi ut, vehicula mauris. Quisque ultricies risus quis 
						    rci.
					    </label>

					    <input id="4" type="radio" name="futura" value="4">  
					    <label for="4">Lorem ipsum dolor sit amet, consectetur adipiscing.</label> 
					</div>  
					
						<div class="botao-enquete">
							<a class="votar" href="#"><?php echo '<span>' . __( 'VOTAR', 'odin' ) . '</span>'; ?></a>
							<a class="ver-resultados" href="#"><?php echo '<span>' . __( 'VER RESULTADOS', 'futura' ) . '</span>'; ?></a>
						</div>
					
				</section>				
			</section>






			<?php //get_sidebar() ?>
		</section>

	</article>



<?php get_footer() ?>
