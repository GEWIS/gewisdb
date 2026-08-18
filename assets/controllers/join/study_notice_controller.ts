import { Controller } from '@hotwired/stimulus';

/**
 * Shows the footnote about DSA Pattern while a data science program, or nothing at all, is selected. The marker is
 * put on the option label by `StudyChoices`, so the studies it applies to are not repeated here.
 */
export default class extends Controller {
    static targets = ['notice'];

    declare readonly noticeTarget: HTMLElement;

    toggle(event: Event): void {
        const select = event.target as HTMLSelectElement;
        const selected = select.options[select.selectedIndex];
        const applies = '' === select.value || (undefined !== selected && selected.text.includes('¹'));

        this.noticeTarget.classList.toggle('d-none', !applies);
    }
}
