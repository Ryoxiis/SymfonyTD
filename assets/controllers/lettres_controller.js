import { Controller } from '@hotwired/stimulus';

/*
 * Fait apparaître un titre lettre par lettre.
 * Chaque <span> enfant direct est découpé en lettres ; le délai de chaque lettre est donné par la variable CSS --i.
 */
export default class extends Controller {
    connect() {
        // Le texte complet reste lisible par les lecteurs d'écran
        this.element.setAttribute('aria-label', this.element.textContent.replace(/\s+/g, ' ').trim());

        let index = 0;
        this.element.querySelectorAll(':scope > span').forEach((bloc) => {
            const texte = bloc.textContent;
            bloc.textContent = '';
            bloc.setAttribute('aria-hidden', 'true');

            for (const caractere of texte) {
                const lettre = document.createElement('span');
                lettre.className = caractere === ' ' ? 'lettre lettre--espace' : 'lettre';
                lettre.textContent = caractere === ' ' ? ' ' : caractere;
                lettre.style.setProperty('--i', index++);
                bloc.appendChild(lettre);
            }
        });
    }
}
