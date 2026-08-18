import { Controller } from '@hotwired/stimulus';

/**
 * The two things founding an organ needs beyond a plain form.
 *
 * A voting committee is founded for a purpose rather than under a name, and its abbreviation is only the suffix after
 * `SC<meeting>-`; picking that type relabels both fields to say so.
 *
 * The membership starts with blank rows to fill in, and the ones that were left blank should not be submitted as
 * empty members, so they are dropped on the way out.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['name', 'nameLabel', 'abbr', 'abbrLabel', 'members'];
    static values = {
        purposeType: String,
        nameLabel: String,
        abbrLabel: String,
        purposeLabel: String,
        suffixLabel: String,
    };

    declare readonly nameTarget: HTMLInputElement;
    declare readonly nameLabelTarget: HTMLElement;
    declare readonly abbrTarget: HTMLInputElement;
    declare readonly abbrLabelTarget: HTMLElement;
    declare readonly membersTarget: HTMLElement;

    declare readonly purposeTypeValue: string;
    declare readonly nameLabelValue: string;
    declare readonly abbrLabelValue: string;
    declare readonly purposeLabelValue: string;
    declare readonly suffixLabelValue: string;

    relabel(event: Event): void {
        const chosen = event.target as HTMLInputElement;

        if ('radio' !== chosen.type) {
            return;
        }

        const purpose = this.purposeTypeValue === chosen.value;

        this.apply(this.nameTarget, this.nameLabelTarget, purpose ? this.purposeLabelValue : this.nameLabelValue);
        this.apply(this.abbrTarget, this.abbrLabelTarget, purpose ? this.suffixLabelValue : this.abbrLabelValue);
    }

    prune(): void {
        this.membersTarget
            .querySelectorAll<HTMLInputElement>('[data-member-lookup-target="lidnr"]')
            .forEach((lidnr) => {
                if ('' !== lidnr.value) {
                    return;
                }

                lidnr.closest('[data-form-collection-target="entry"]')?.remove();
            });
    }

    private apply(field: HTMLInputElement, label: HTMLElement, text: string): void {
        field.placeholder = text;
        label.textContent = text;
    }
}
