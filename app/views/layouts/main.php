<?php /** @var string $content */ ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($title) ? e($title) : 'IdiriCoaching' ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="<?= BASE_URL ?>?action=home">Idiri Coaching</a>
  <nav class="nav">
    <a href="<?= BASE_URL ?>?action=home">Accueil</a>

    <?php if (empty($_SESSION['user'])): ?>
      <a href="<?= BASE_URL ?>?action=inscription">Inscription</a>
      <a href="<?= BASE_URL ?>?action=connexion">Connexion</a>
    <?php else: ?>
      <span class="nav-user">Bonjour, <?= e($_SESSION['user']['first_name']) ?></span>
      <?php if (($_SESSION['user']['role'] ?? 'adherent') === 'coach'): ?>
        <a href="<?= BASE_URL ?>?action=coachDashboard">Mon tableau de bord</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>?action=adherentDashboard">Mon tableau de bord</a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>?action=logout">Déconnexion</a>
    <?php endif; ?>
  </nav>
</header>

<main class="container">
  <?php foreach (flashes() as $type => $msgs): ?>
    <?php foreach ($msgs as $m): ?>
      <div class="flash <?= e($type) ?>"><?= e($m) ?></div>
    <?php endforeach; ?>
  <?php endforeach; ?>

  <?= $content ?>
</main>

<footer class="footer">
  &copy; <?= date('Y') ?> IdiriCoaching
</footer>
</body>
</html>

