<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.login') ?> <?= $this->endSection() ?>

<?= $this->section('pageStyles')?> 
    <meta name="author" content="Especialistas Web">
    <link rel="canonical" href="https://appstack.bootlab.io/auth-sign-up-cover.html" />
    <link rel="shortcut icon" href="<?= base_url('img/favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="<?= base_url('bootstrap/css/app.css') ?>" rel="stylesheet">
    <style>
        .auth-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }
        .auth-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3); /* Capa oscura translúcida */
            backdrop-filter: blur(3px);    /* Efecto de difuminado */
            z-index: 1;
        }
        .auth-quote {
            position: relative;
            z-index: 2;
        }
    </style>
<?= $this->endSection() ?>


<? $this->section('main') ?>

	<div class="container-fluid p-0">
		<div class="row g-0">
			<div class="col-xl-6 d-none d-xl-flex">
				<div class="auth-full-page position-relative d-flex flex-column justify-content-end">
                    <video autoplay muted loop playsinline  class="auth-bg">
                        <source src="<?= base_url('bootstrap/video/Video_Alternativo_Para_ERP_Hosting.mp4') ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="auth-overlay"></div>
<!--<img src="<?php echo base_url('bootstrap/img/photos/unsplash-1.jpg') ?>" class="auth-bg" alt="Unsplash"> -->

					<div class="auth-quote">
						<i data-lucide="quote"></i>
						<figure>
							<blockquote>
								<p>Soluciones de hosting de alto rendimiento y gestión integral para potenciar tu presencia digital con la máxima eficiencia.</p>
							</blockquote>
							<figcaption>
								— Especialistas Web
							</figcaption>
						</figure>
					</div>
				</div>
			</div>
			<div class="col-xl-6">
				<div class="auth-full-page d-flex p-4 p-xl-5">
					<div class="d-flex flex-column w-100 h-100">
						<div class="auth-form">

							<div class="text-center">
								<h1 class="h2"><?= lang('Auth.login') ?></h1>
								<p class="lead">
									Ingresa a tu cuenta para continuar
								</p>
							</div>

							<div class="mb-3">
								
								<?php if (session('error') !== null) : ?>
									<div class="alert alert-danger" role="alert"><?= session('error') ?></div>
								<?php elseif (session('errors') !== null) : ?>
									<div class="alert alert-danger" role="alert">
										<?php if (is_array(session('errors'))) : ?>
											<?php foreach (session('errors') as $error) : ?>
												<?= $error ?>
												<br>
											<?php endforeach ?>
										<?php else : ?>
											<?= session('errors') ?>
										<?php endif ?>
									</div>
								<?php endif ?>

								<?php if (session('message') !== null) : ?>
									<div class="alert alert-success" role="alert"><?= session('message') ?></div>
								<?php endif ?>

								<div class="row">
									<div class="col">
										<hr>
									</div>
									<div class="col-auto text-uppercase d-flex align-items-center">O accede con</div>
									<div class="col">
										<hr>
									</div>
								</div>

								<form action="<?= url_to('login') ?>" method="post">
									<?= csrf_field() ?>

									<div class="mb-3">
										<label class="form-label"><?= lang('Auth.email') ?></label>
										<input class="form-control form-control-lg" type="email" name="email" inputmode="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required />
									</div>
									<div class="mb-3">
										<label class="form-label"><?= lang('Auth.password') ?></label>
										<input class="form-control form-control-lg" type="password" name="password" inputmode="text" autocomplete="current-password" placeholder="<?= lang('Auth.password') ?>" required />
									</div>

									<!-- Remember me -->
									<?php if (setting('Auth.sessionConfig')['allowRemembering']): ?>
										<div class="mb-3">
											<div class="form-check">
												<input type="checkbox" name="remember" class="form-check-input" id="remember" <?php if (old('remember')): ?> checked<?php endif ?>>
												<label class="form-check-label" for="remember">
													<?= lang('Auth.rememberMe') ?>
												</label>
											</div>
										</div>
									<?php endif; ?>

									<div class="d-grid gap-2 mt-3">
										<button type="submit" class="btn btn-lg btn-primary"><?= lang('Auth.login') ?></button>
									</div>
								</form>
							</div>

							<div class="text-center">
								<?php if (setting('Auth.allowRegistration')) : ?>
									<?= lang('Auth.needAccount') ?> <a href="<?= url_to('register') ?>"><?= lang('Auth.register') ?></a><br>
								<?php endif ?>
								
								<?php if (setting('Auth.allowMagicLinkLogins')) : ?>
									<?= lang('Auth.forgotPassword') ?> <a href="<?= url_to('magic-link') ?>"><?= lang('Auth.useMagicLink') ?></a>
								<?php endif ?>
							</div>
						</div>
						<div class="text-center mt-auto">
							<p class="mb-0">
								&copy; <?= date('Y') ?> - <a href="<?= base_url() ?>">ERP Hosting</a>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts')?> 
	
<?= $this->endSection() ?>
