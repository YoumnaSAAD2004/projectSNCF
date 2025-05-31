<?php
    require"./include/header.php";
    require"./include/function.php";
?>
<form method="get">
    <label for="stationInput">Choisissez ou entrez le nom d'une station :</label>
    <input type="text" id="stationInput" name="station" list="stations" autocomplete="off" placeholder="Commencez à taper...">
    <datalist id="stations">
    </datalist>
    <button type="submit">Voir horaires</button>
</form>

<div class="affiche">
<section class="infoGareA">
    <h2>Information de Gare</h2>
        <?php
       //echo fetchTrafficInfo();
        ?>
</section>
<section class="horaireA">
        <?php
        echo printhoraire();
        ?>
</section>
</div>

<?php
    require "./include/footer.php";
?>