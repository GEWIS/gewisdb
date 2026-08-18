import { Controller } from '@hotwired/stimulus';

/**
 * Add and remove entries of a Symfony CollectionType (allow_add / allow_delete).
 *
 * The prototype is the whole entry as the server would have rendered it, wrapper and remove button included, so
 * adding one is a matter of cloning it with the placeholder replaced by a fresh index. Stimulus connects whatever
 * controllers the new markup declares as soon as it is inserted, which is how each row gets its own lookup.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['entries', 'add'];
    static values = {
        prototype: String,
        prototypeName: { type: String, default: '__name__' },
        index: Number,
    };

    declare readonly entriesTarget: HTMLElement;
    declare readonly hasAddTarget: boolean;
    declare readonly addTarget: HTMLElement;

    declare readonly prototypeValue: string;
    declare readonly prototypeNameValue: string;
    declare readonly hasIndexValue: boolean;
    declare indexValue: number;

    connect(): void {
        if (this.hasIndexValue) {
            return;
        }

        this.indexValue = this.entriesTarget.querySelectorAll(
            ':scope > [data-form-collection-target="entry"]',
        ).length;
    }

    add(event: Event): void {
        event.preventDefault();

        const template = document.createElement('template');
        template.innerHTML = this.prototypeValue.replaceAll(this.prototypeNameValue, String(this.indexValue)).trim();

        const entry = template.content.firstElementChild;

        if (null === entry) {
            return;
        }

        this.entriesTarget.append(entry);
        this.indexValue += 1;
    }

    remove(event: Event): void {
        event.preventDefault();
        (event.currentTarget as HTMLElement).closest('[data-form-collection-target="entry"]')?.remove();
    }
}
