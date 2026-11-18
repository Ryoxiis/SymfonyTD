import { Controller } from '@hotwired/stimulus';

/*
 * Fenêtre modale de la fiche produit / animal (comme sur la maquette).
 * Les liens « Agrandir » ciblent le <turbo-frame id="modale"> : quand la fiche y est chargée, on ouvre la modale.
 */
export default class extends Controller {
    static targets = ['frame'];

    ouvrir() {
        this.element.classList.add('ouverte');
        document.body.classList.add('modale-ouverte');
    }

    fermer() {
        if (!this.element.classList.contains('ouverte')) {
            return;
        }

        this.element.classList.remove('ouverte');
        document.body.classList.remove('modale-ouverte');

        // On vide la frame après l'animation de fermeture
        setTimeout(() => {
            this.frameTarget.removeAttribute('src');
            this.frameTarget.innerHTML = '';
        }, 300);
    }
}
