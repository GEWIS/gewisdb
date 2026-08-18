import { Controller } from '@hotwired/stimulus';
import type { OrganInfo, OrganMember } from './organ_lookup_controller.ts';

/**
 * Lists who an organ still has installed, so that abolishing it is not done blind. Purely informative: nothing here
 * is submitted.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['panel', 'list'];
    static values = {
        conjunction: String,
    };

    declare readonly panelTarget: HTMLElement;
    declare readonly listTarget: HTMLElement;

    declare readonly conjunctionValue: string;

    render(event: CustomEvent<OrganInfo>): void {
        this.listTarget.replaceChildren(...event.detail.members.map((member) => {
            const item = document.createElement('li');
            item.textContent = `${member.member.fullName} (${this.functions(member)})`;

            return item;
        }));

        this.panelTarget.classList.remove('d-none');
    }

    /**
     * The functions a member holds, read as a sentence: "chair", "chair and treasurer", "chair, secretary and
     * treasurer".
     */
    private functions(member: OrganMember): string {
        const names = member.installations.map((installation) => installation.functionName);

        if (2 > names.length) {
            return names.join('');
        }

        return `${names.slice(0, -1).join(', ')} ${this.conjunctionValue} ${names[names.length - 1]}`;
    }
}
