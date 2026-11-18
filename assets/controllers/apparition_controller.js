import { Controller } from '@hotwired/stimulus';

/*
 * Apparition en fondu des éléments .apparition quand ils entrent dans l'écran.
 * Placé sur <body> : fonctionne sur toutes les pages, et après chaque navigation Turbo.
 */
export default class extends Controller {
    connect() {
        if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.element.querySelectorAll('.apparition').forEach((el) => el.classList.add('est-visible'));

            return;
        }

        this.observateur = new IntersectionObserver((entrees) => {
            entrees.forEach((entree) => {
                if (entree.isIntersecting) {
                    entree.target.classList.add('est-visible');
                    this.observateur.unobserve(entree.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        this.observer();
        this.surChargementFrame = () => this.observer();
        document.addEventListener('turbo:frame-load', this.surChargementFrame);
    }

    disconnect() {
        this.observateur?.disconnect();
        document.removeEventListener('turbo:frame-load', this.surChargementFrame);
    }

    observer() {
        this.element.querySelectorAll('.apparition:not(.est-visible)').forEach((element, index) => {
            // Petit décalage entre éléments voisins pour un effet « en cascade »
            element.style.setProperty('--delai', `${(index % 6) * 70}ms`);
            this.observateur.observe(element);
        });
    }
}
