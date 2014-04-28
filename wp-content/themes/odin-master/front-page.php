<?php
get_header(); ?>

<div class="container">
	<article>
		<section class="row col-md-12 conteudo_home">
			<div class="col-md-6 desenho_cidade"></div>
			<div class="col-md-6 formulario">
				<h2>Dê sua sugestão de nome pelo formulário a baixo</h2>
				<div class="formulario_sug">
					<?php gravity_form(1, false, false, false, '', true, 12); ?>
				</div>
				<div class="compartilhar">
						<?php /* Retornando minha primeira sidebar */
							if ( is_active_sidebar('sidebar_share') ) {
							dynamic_sidebar('sidebar_share');
							}
						?>
				</div>
			</div>
		</section>
		<section class="row col-md-12 texto-content">
			Os 10 nomes escolhidos entrará para uma votação, onde você vai poder votar e decidir. Venha participar, 
			faça de sua cidade um lugar melhor de se morar.
		</section>
	</article>
</div>
<?php
get_footer();