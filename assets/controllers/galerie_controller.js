import { Controller } from '@hotwired/stimulus';

/*
 * Galerie horizontale : flèches précédent / suivant et défilement automatique lent
 * (en pause au survol, désactivé si l'utilisateur préfère réduire les animations).
 */
export default class extends Controller {
    static targets = ['piste'];
    static values = { intervalle: { type: Number, default: 4500 } };

    connect() {
        this.mouvementReduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.demarrer();
    }

    disconnect() {
        this.arreter();
    }

    demarrer() {
        this.arreter();
        if (!this.mouvementReduit) {
            this.minuteur = setInterval(() => this.suivant(), this.intervalleValue);
        }
    }

    arreter() {
        clearInterval(this.minuteur);
    }

    pas() {
        const carte = this.pisteTarget.querySelector('.carte');

        return carte ? carte.getBoundingClientRect().width + 16 : 300;
    }

    suivant() {
        const piste = this.pisteTarget;
        const finAtteinte = piste.scrollLeft + piste.clientWidth >= piste.scrollWidth - 5;
        piste.scrollTo({ left: finAtteinte ? 0 : piste.scrollLeft + this.pas(), behavior: 'smooth' });
    }

    precedent() {
        this.pisteTarget.scrollBy({ left: -this.pas(), behavior: 'smooth' });
    }
}
