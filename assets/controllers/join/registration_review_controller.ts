import { Controller } from '@hotwired/stimulus';

/**
 * Mirror what has been filled in onto the last step of the registration, and keep the checkout button shut until both
 * agreements are ticked.
 *
 * Reading the fields rather than posting the steps separately keeps the registration a single form and a single POST:
 * the review is a view of the inputs that are already in the document, so nothing is stored half-finished and going
 * back to a step is navigation rather than a round trip.
 *
 * ```
 * <dd data-registration-review-target="value" data-fields="form_initials form_firstName"></dd>
 * <dd data-registration-review-target="value" data-checked-in="form_lists" data-empty="Announcements only"></dd>
 * ```
 */
export default class extends Controller {
    static targets = ['value', 'agreement', 'checkout'];

    declare readonly valueTargets: HTMLElement[];
    declare readonly agreementTargets: HTMLInputElement[];
    declare readonly hasCheckoutTarget: boolean;
    declare readonly checkoutTarget: HTMLButtonElement;

    connect(): void {
        this.update();
    }

    update(): void {
        this.valueTargets.forEach((target) => {
            const text = this.read(target);

            target.textContent = '' !== text ? text : (target.dataset.empty ?? '—');
            target.classList.toggle('registration-review-empty', '' === text);
        });

        if (!this.hasCheckoutTarget) {
            return;
        }

        // The step this button sits on is the last one, so the stepper has just enabled it; this narrows that to
        // "and both agreements are ticked". Both controllers act on the same element, which is why the stepper's
        // actions name this method after their own.
        this.checkoutTarget.disabled = this.agreementTargets.some((agreement) => !agreement.checked);
    }

    private read(target: HTMLElement): string {
        const container = target.dataset.checkedIn;

        if (undefined !== container) {
            return this.checkedLabels(container).join(', ');
        }

        return (target.dataset.fields ?? '')
            .split(' ')
            .filter((id) => '' !== id)
            .map((id) => this.display(id))
            .filter((value) => '' !== value)
            .join(target.dataset.separator ?? ' ');
    }

    private checkedLabels(id: string): string[] {
        const container = document.getElementById(id);

        if (null === container) {
            return [];
        }

        return Array.from(container.querySelectorAll<HTMLInputElement>('input[type="checkbox"]:checked'))
            .map((checkbox) => {
                // The label of a mailing list carries its description as well as its name; only the name identifies
                // it here, and that is the label's first element.
                const label = document.querySelector<HTMLElement>(`label[for="${checkbox.id}"]`);

                return (label?.querySelector('strong') ?? label)?.textContent?.trim() ?? '';
            })
            .filter((label) => '' !== label);
    }

    private display(id: string): string {
        const field = document.getElementById(id);

        if (field instanceof HTMLSelectElement) {
            // A placeholder option ("Select a study") is not an answer, and it is always the one with an empty value.
            return '' !== field.value ? (field.selectedOptions[0]?.textContent?.trim() ?? '') : '';
        }

        return field instanceof HTMLInputElement ? field.value.trim() : '';
    }
}
