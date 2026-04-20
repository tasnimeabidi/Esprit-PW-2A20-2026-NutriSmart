<?php include 'header.php'; ?>
 
<div class="container">
    <h1>Bienvenue dans le Workshop Jointure</h1>
    <p>Ce projet illustre la jointure entre les tables <strong>PlanRepas</strong>, <strong>Repas</strong> et <strong>ProgrammeSportif</strong>.</p>
 
    <div class="cards">
        <div class="card">
            <h2>Repas par Plan</h2>
            <p>Recherchez les repas correspondants à un plan repas.</p>
            <a href="searchAlbums.php">Rechercher</a>
        </div>
        <div class="card">
            <h2>Tous les Repas</h2>
            <p>Consultez tous les repas avec leur plan associé.</p>
            <a href="showAlbums.php">Voir les repas</a>
        </div>
        <div class="card">
            <h2>Programmes Sportifs</h2>
            <p>Consultez tous les programmes sportifs avec leur plan.</p>
            <a href="Shop.php">Voir les programmes</a>
        </div>
        <div class="card">
            <h2>Ajouter un Repas</h2>
            <p>Ajoutez un nouveau repas à un plan existant.</p>
            <a href="addAlbum.php">Ajouter</a>
        </div>
    </div>
</div>
 
<?php include 'footer.php'; ?>
 