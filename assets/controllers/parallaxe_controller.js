import { Controller } from '@hotwired/stimulus';

/*
 * Effet de profondeur dans la bannière : chaque « couche » bouge un peu avec la souris.
 * Plus data-profondeur est grand, plus la couche se déplace.
 *
 * <section data-controller="parallaxe" data-action="mousemove->parallaxe#bouger mouseleave->parallaxe#reinitialiser">
 *     <div data-parallaxe-target="couche" data-profondeur="20">…</div>
 */
export default class extends Controller {
    static targets = ['couche'];

    connect() {
        this.mouvementReduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    bouger(event) {
        if (this.mouvementReduit) {
            return;
        }

        const zone = this.element.getBoundingClientRect();
        const x = (event.clientX - zone.left) / zone.width - 0.5;
        const y = (event.clientY - zone.top) / zone.height - 0.5;

        this.coucheTargets.forEach((couche) => {
            const profondeur = parseFloat(couche.dataset.profondeur || '10');
            couche.style.transform = `translate3d(${-x * profondeur}px, ${-y * profondeur}px, 0)`;
        });
    }

    reinitialiser() {
        this.coucheTargets.forEach((couche) => {
            couche.style.transform = '';
        });
    }
}
