<?php
    require"./include/header.php";
    require"./include/function.php";
?>


<section class="welcome-section">
    <div class="overlay"></div> <!-- Ajout d'une superposition pour améliorer la lisibilité du texte -->
    <h1>Bienvenue sur notre site web de transport en commun</h1>
    <p>Découvrez les meilleurs itinéraires et horaires pour vos déplacements en train et RER dans la région parisienne.</p>
</section>


<section class="about1" class="about-section">
    <div class="container1">
        <h2 class="section-title1">À Propos de "Trains SNCF"</h2>
        <div class="about-content1">
            <p><strong>Notre Mission :</strong> Notre mission est de vous fournir des informations précises et à jour sur les horaires de trains et RER, ainsi que des détails pertinents sur les différentes gares de la région parisienne. Nous nous engageons à rendre votre expérience de voyage aussi fluide et agréable que possible.</p>
            <p><strong>Fonctionnalités Clés :</strong> "Horaire des RER" offre une gamme de fonctionnalités pour répondre à tous vos besoins en matière d'informations sur les transports en commun. De la recherche de gares à la consultation des horaires détaillés, en passant par la personnalisation de votre expérience de voyage, notre plateforme est conçue pour vous offrir une expérience utilisateur optimale.</p>
            <p><strong>Accessibilité :</strong> Nous accordons une grande importance à l'accessibilité et à l'inclusivité. Notre plateforme est conçue pour être facilement accessible à tous les utilisateurs, y compris ceux ayant des besoins spécifiques en termes d'accessibilité. Nous nous efforçons de garantir que chacun puisse accéder aux informations dont il a besoin, quel que soit son handicap.</p>
            <p><strong>Contactez-nous :</strong> Si vous avez des questions, des commentaires ou des suggestions, n'hésitez pas à nous contacter. Notre équipe est là pour vous aider et s'assurer que vous avez la meilleure expérience possible avec "Horaire des RER".</p>
            <p>Merci de nous avoir choisis pour vos besoins en matière d'informations sur les transports en commun. Nous sommes impatients de vous accompagner dans tous vos voyages !</p>
        </div>
    </div>
</section>


<section class="imgale">
<?php
      // Utilisation de la fonction pour afficher une image aléatoire à partir du dossier "photos"
    afficherImageAleatoire("images/photos/");
  ?>
</section>

<?php
    require "./include/footer.php";
?>