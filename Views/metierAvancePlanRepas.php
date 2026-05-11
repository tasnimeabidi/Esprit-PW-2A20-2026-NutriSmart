<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$spec = MetierAvancePlanRepas::specification();

include __DIR__ . '/header.php';
?>

<div class="container">
    <h1>Métier avancé — Plan repas</h1>
    <p class="lead"><strong><?php echo htmlspecialchars((string) $spec['referenceMetier'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
    <p><?php echo htmlspecialchars((string) $spec['accroche'], ENT_QUOTES, 'UTF-8'); ?></p>
    <p><?php echo htmlspecialchars((string) $spec['objectifProduit'], ENT_QUOTES, 'UTF-8'); ?></p>

    <h2>Fonctionnalités</h2>
    <ul class="metier-liste">
        <?php foreach ($spec['fonctionnalites'] as $f) { ?>
            <li>
                <strong><?php echo htmlspecialchars((string) $f['titre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                — <?php echo htmlspecialchars((string) $f['description'], ENT_QUOTES, 'UTF-8'); ?>
            </li>
        <?php } ?>
    </ul>

    <h2>Phases de travail</h2>
    <ol class="metier-phases">
        <?php foreach ($spec['phases'] as $p) { ?>
            <li>
                <strong><?php echo htmlspecialchars((string) $p['libelle'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <ul>
                    <?php foreach ($p['activites'] as $a) { ?>
                        <li><?php echo htmlspecialchars((string) $a, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php } ?>
                </ul>
            </li>
        <?php } ?>
    </ol>

    <h2>Critères d’entrée</h2>
    <ul>
        <?php foreach ($spec['criteresEntree'] as $c) { ?>
            <li><?php echo htmlspecialchars((string) $c, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php } ?>
    </ul>

    <h2>Livrables</h2>
    <ul>
        <?php foreach ($spec['livrables'] as $l) { ?>
            <li><?php echo htmlspecialchars((string) $l, ENT_QUOTES, 'UTF-8'); ?></li>
        <?php } ?>
    </ul>

    <?php
    $api = $spec['apiLiee'];
    $tpl = is_array($api) && isset($api['orchestrationPlan']) ? (string) $api['orchestrationPlan'] : '';
    ?>
    <h2>Lien technique NutriSmart</h2>
    <p>
        Orchestration pour un plan donné :
        <code><?php echo htmlspecialchars($tpl, ENT_QUOTES, 'UTF-8'); ?></code>
    </p>
    <p>Données JSON du métier (même contenu) : <a href="../api/metier-avance-plan-repas.php"><code>api/metier-avance-plan-repas.php</code></a></p>
</div>

<?php include __DIR__ . '/footer.php'; ?>
