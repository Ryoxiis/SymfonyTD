import { Controller } from '@hotwired/stimulus';

/*
 * Ajout au panier sans recharger la page.
 * Le formulaire est envoyé en arrière-plan ; le serveur répond en JSON ;
 * on met à jour la pastille (avec un petit rebond) et on affiche le tiroir latéral.
 * Sans JavaScript, le formulaire fonctionne quand même normalement.
 */
export default class extends Controller {
    static targets = ['pastille', 'tiroir', 'titre', 'article', 'image', 'nom', 'detail'];

    async ajouter(event) {
        event.preventDefault();
        const formulaire = event.target;
        const bouton = formulaire.querySelector('[type=submit]');
        bouton.disabled = true;

        try {
            const reponse = await fetch(formulaire.action, {
                method: 'POST',
                body: new FormData(formulaire),
                headers: { Accept: 'application/json' },
            });
            const donnees = await reponse.json();

            // Pas connecté : on part vers la page de connexion
            if (donnees.redirect) {
                window.Turbo ? window.Turbo.visit(donnees.redirect) : (window.location.href = donnees.redirect);

                return;
            }

            this.mettreAJourPastille(donnees.quantite);
            this.afficherTiroir(donnees);
        } catch (erreur) {
            // En cas de souci réseau, on retombe sur l'envoi classique
            formulaire.submit();
        } finally {
            bouton.disabled = false;
        }
    }

    mettreAJourPastille(quantite) {
        if (quantite === undefined) {
            return;
        }

        this.pastilleTargets.forEach((pastille) => {
            pastille.textContent = quantite;
            pastille.classList.remove('rebond');
            void pastille.offsetWidth; // relance l'animation
            pastille.classList.add('rebond');
        });
    }

    afficherTiroir(donnees) {
        this.titreTarget.textContent = donnees.message;

        if (donnees.article) {
            this.articleTarget.hidden = false;
            this.imageTarget.src = donnees.article.photo;
            this.imageTarget.alt = donnees.article.nom;
            this.nomTarget.textContent = donnees.article.nom;
            this.detailTarget.textContent = donnees.article.detail;
        } else {
            this.articleTarget.hidden = true;
        }

        this.tiroirTarget.classList.add('ouvert');
        clearTimeout(this.minuteur);
        this.minuteur = setTimeout(() => this.fermer(), 5000);
    }

    fermer() {
        this.tiroirTarget.classList.remove('ouvert');
    }

    disconnect() {
        clearTimeout(this.minuteur);
    }
}
