import { Controller } from '@hotwired/stimulus';
import type { OrganInfo, OrganInstallation } from './organ_lookup_controller.ts';

interface MemberMatch {
    lidnr: number;
    fullName: string;
}

type Collection = 'installations' | 'reappointments' | 'discharges';

/**
 * Editing an organ's membership as a single decision.
 *
 * Two tables carry the work: the left one is the organ as it stands, the right one is the organ as the decision will
 * leave it. Every button on the right adds a row to one of the three collections the form submits -- installations,
 * reappointments and discharges -- and the mutations are listed underneath in the words they will read as.
 *
 * The rows of those collections are built from the form's own prototypes rather than assembled here, so their field
 * names and their wording come from the server. A prototype carries two kinds of placeholder: the ones in its
 * sentence, which are filled with what the member and the function are called, and the ones in its hidden fields,
 * which are filled with the identifiers the form reads back. They are substituted separately, into text nodes and
 * into input values, so that a member's name is never treated as markup.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['panel', 'currentMembers', 'currentRowTemplate', 'result', 'resultRowTemplate',
        'installations', 'reappointments', 'discharges', 'memberInput', 'addMember', 'addInactiveMember',
        'dischargeName', 'dischargeFunction', 'reappointName', 'reappointFunctions', 'reappointFunctionTemplate',
        'inactiveName', 'functionName', 'functionSelect'];
    static values = {
        memberFunction: String,
        inactiveFunction: String,
        installationPrototype: String,
        reappointmentPrototype: String,
        dischargePrototype: String,
    };

    declare readonly panelTarget: HTMLElement;
    declare readonly currentMembersTarget: HTMLElement;
    declare readonly currentRowTemplateTarget: HTMLTemplateElement;
    declare readonly resultTarget: HTMLElement;
    declare readonly resultRowTemplateTarget: HTMLTemplateElement;
    declare readonly installationsTarget: HTMLElement;
    declare readonly reappointmentsTarget: HTMLElement;
    declare readonly dischargesTarget: HTMLElement;
    declare readonly memberInputTarget: HTMLInputElement;
    declare readonly addMemberTarget: HTMLButtonElement;
    declare readonly addInactiveMemberTarget: HTMLButtonElement;
    declare readonly dischargeNameTarget: HTMLElement;
    declare readonly dischargeFunctionTarget: HTMLElement;
    declare readonly reappointNameTarget: HTMLElement;
    declare readonly reappointFunctionsTarget: HTMLElement;
    declare readonly reappointFunctionTemplateTarget: HTMLTemplateElement;
    declare readonly inactiveNameTarget: HTMLElement;
    declare readonly functionNameTarget: HTMLElement;
    declare readonly hasFunctionSelectTarget: boolean;
    declare readonly functionSelectTarget: HTMLSelectElement;

    declare readonly memberFunctionValue: string;
    declare readonly inactiveFunctionValue: string;
    declare readonly installationPrototypeValue: string;
    declare readonly reappointmentPrototypeValue: string;
    declare readonly dischargePrototypeValue: string;

    private readonly names = new Map<string, string>();
    private candidate: MemberMatch | null = null;
    private subject: HTMLTableRowElement | null = null;
    private counts: Record<Collection, number> = { installations: 0, reappointments: 0, discharges: 0 };

    /**
     * A different organ means different membership, so everything recorded so far is dropped along with it.
     */
    organSelected(event: CustomEvent<OrganInfo>): void {
        this.names.clear();
        this.counts = { installations: 0, reappointments: 0, discharges: 0 };
        this.currentMembersTarget.replaceChildren();
        this.resultTarget.replaceChildren();
        this.installationsTarget.replaceChildren();
        this.reappointmentsTarget.replaceChildren();
        this.dischargesTarget.replaceChildren();

        for (const member of event.detail.members) {
            const lidnr = String(member.member.lidnr);
            this.names.set(lidnr, member.member.fullName);

            // Ordinary membership of the organ comes first; the extra functions hang off it.
            const installations = [...member.installations].sort(
                (left, right) => this.rank(left) - this.rank(right),
            );

            installations.forEach((installation, index) => {
                this.currentMembersTarget.append(this.currentRow(lidnr, installation, 0 === index));
                this.resultTarget.append(this.resultRow(lidnr, installation, 0 === index));
            });
        }

        this.panelTarget.classList.remove('d-none');
    }

    memberSelected(event: CustomEvent<MemberMatch>): void {
        this.candidate = event.detail;
        this.addMemberTarget.disabled = false;
        this.addInactiveMemberTarget.disabled = false;
    }

    addMember(): void {
        this.install(this.memberFunctionValue, this.memberFunctionValue);
    }

    addInactiveMember(): void {
        this.install(this.inactiveFunctionValue, this.inactiveFunctionValue);
    }

    prepareDischarge(event: Event): void {
        const row = this.rowOf(event);

        if (null === row) {
            return;
        }

        this.dischargeNameTarget.textContent = this.nameOf(row);
        this.dischargeFunctionTarget.textContent = row.dataset.functionName ?? '';
    }

    /**
     * Discharging someone's ordinary membership discharges the functions that hang off it as well: a function without
     * a membership behind it is not something the organ can have.
     */
    confirmDischarge(): void {
        const row = this.subject;

        if (null === row) {
            return;
        }

        if (this.memberFunctionValue === row.dataset.function) {
            this.siblings(row).reverse().forEach((sibling) => this.discharge(sibling));
        } else {
            this.discharge(row);
        }
    }

    prepareReappoint(event: Event): void {
        const row = this.rowOf(event);

        if (null === row) {
            return;
        }

        this.reappointNameTarget.textContent = this.nameOf(row);

        // Only the extra functions are a choice; ordinary membership is what is being reappointed.
        const functions = this.siblings(row).filter(
            (sibling) => this.memberFunctionValue !== sibling.dataset.function && this.hasReference(sibling),
        );

        this.reappointFunctionsTarget.replaceChildren(...functions.map((sibling, index) => {
            const entry = this.clone(this.reappointFunctionTemplateTarget);
            const checkbox = entry.querySelector('input') as HTMLInputElement;
            const label = entry.querySelector('label') as HTMLLabelElement;
            const id = `reappoint-function-${index}`;

            checkbox.id = id;
            checkbox.dataset.reference = this.reference(sibling);
            label.htmlFor = id;
            label.textContent = `${sibling.dataset.functionName ?? ''} (${this.decisionOf(sibling)})`;

            return entry;
        }));
    }

    /**
     * Whatever is not reappointed is discharged: a function left unchecked is one the organ will not have any more.
     */
    confirmReappoint(): void {
        const row = this.subject;

        if (null === row) {
            return;
        }

        this.reappoint(row);

        this.reappointFunctionsTarget.querySelectorAll<HTMLInputElement>('input').forEach((checkbox) => {
            const sibling = this.siblings(row).find(
                (candidate) => this.reference(candidate) === checkbox.dataset.reference,
            );

            if (undefined === sibling) {
                return;
            }

            if (checkbox.checked) {
                this.reappoint(sibling);
            } else {
                this.discharge(sibling);
            }
        });
    }

    prepareInactive(event: Event): void {
        const row = this.rowOf(event);

        if (null === row) {
            return;
        }

        this.inactiveNameTarget.textContent = this.nameOf(row);
    }

    confirmInactive(): void {
        const row = this.subject;

        if (null === row) {
            return;
        }

        const lidnr = row.dataset.lidnr ?? '';

        this.siblings(row).reverse().forEach((sibling) => this.discharge(sibling));
        this.createInstallation(lidnr, this.inactiveFunctionValue, this.inactiveFunctionValue);
    }

    prepareFunction(event: Event): void {
        const row = this.rowOf(event);

        if (null === row) {
            return;
        }

        this.functionNameTarget.textContent = this.nameOf(row);
    }

    confirmFunction(): void {
        const row = this.subject;

        if (
            null === row
            || !this.hasFunctionSelectTarget
        ) {
            return;
        }

        const chosen = this.functionSelectTarget.selectedOptions[0];

        if (undefined === chosen) {
            return;
        }

        this.createInstallation(row.dataset.lidnr ?? '', chosen.value, chosen.textContent ?? chosen.value);
    }

    private install(value: string, name: string): void {
        if (null === this.candidate) {
            return;
        }

        const lidnr = String(this.candidate.lidnr);
        this.names.set(lidnr, this.candidate.fullName);
        this.createInstallation(lidnr, value, name);

        this.candidate = null;
        this.memberInputTarget.value = '';
        this.addMemberTarget.disabled = true;
        this.addInactiveMemberTarget.disabled = true;
    }

    private createInstallation(lidnr: string, value: string, name: string): void {
        const rows = this.rowsFor(lidnr);

        // Someone can hold a function in an organ only once, so an installation that is already there is not repeated.
        if (rows.some((row) => row.dataset.function === value)) {
            return;
        }

        this.mutate(this.installationsTarget, this.installationPrototypeValue, 'installations', {
            '%name%': this.names.get(lidnr) ?? '',
            '%function%': name,
        }, {
            '%lidnr%': lidnr,
            '%function%': value,
        });

        const row = this.resultRow(lidnr, { function: value, functionName: name }, 0 === rows.length);
        const last = rows[rows.length - 1];

        if (undefined === last) {
            this.resultTarget.append(row);
        } else {
            last.after(row);
        }
    }

    private reappoint(row: HTMLTableRowElement): void {
        if (!this.hasReference(row)) {
            return;
        }

        this.mutate(this.reappointmentsTarget, this.reappointmentPrototypeValue, 'reappointments', {
            '%name%': this.nameOf(row),
            '%function%': row.dataset.functionName ?? '',
        }, this.referenceFields(row));
    }

    /**
     * A row that was added here has nothing to discharge -- it does not exist yet -- so only recorded installations
     * produce a discharge.
     */
    private discharge(row: HTMLTableRowElement): void {
        if (!this.hasReference(row)) {
            row.remove();

            return;
        }

        this.mutate(this.dischargesTarget, this.dischargePrototypeValue, 'discharges', {
            '%name%': this.nameOf(row),
            '%function%': row.dataset.functionName ?? '',
        }, this.referenceFields(row));

        row.remove();
    }

    private mutate(
        container: HTMLElement,
        prototype: string,
        collection: Collection,
        prose: Record<string, string>,
        fields: Record<string, string>,
    ): void {
        const template = document.createElement('template');
        template.innerHTML = prototype.replaceAll('__index__', String(this.counts[collection])).trim();

        const entry = template.content.firstElementChild;

        if (null === entry) {
            return;
        }

        this.counts[collection] += 1;

        const walker = document.createTreeWalker(entry, NodeFilter.SHOW_TEXT);

        for (let node = walker.nextNode(); null !== node; node = walker.nextNode()) {
            node.nodeValue = this.substitute(node.nodeValue ?? '', prose);
        }

        entry.querySelectorAll('input').forEach((input) => {
            input.value = this.substitute(input.value, fields);
        });

        container.append(entry);
    }

    private substitute(text: string, values: Record<string, string>): string {
        return Object.entries(values).reduce(
            (carry, [token, value]) => carry.replaceAll(token, value),
            text,
        );
    }

    private currentRow(
        lidnr: string,
        installation: OrganInstallation,
        first: boolean,
    ): HTMLTableRowElement {
        const row = this.clone(this.currentRowTemplateTarget) as HTMLTableRowElement;
        this.fill(row, first ? this.names.get(lidnr) ?? '' : '', installation.functionName);

        return row;
    }

    private resultRow(
        lidnr: string,
        installation: Pick<OrganInstallation, 'function' | 'functionName'> & Partial<OrganInstallation>,
        first: boolean,
    ): HTMLTableRowElement {
        const row = this.clone(this.resultRowTemplateTarget) as HTMLTableRowElement;
        this.fill(row, first ? this.names.get(lidnr) ?? '' : '', installation.functionName);

        row.dataset.lidnr = lidnr;
        row.dataset.function = installation.function;
        row.dataset.functionName = installation.functionName;

        if (undefined !== installation.meeting_type) {
            row.dataset.meetingType = installation.meeting_type;
            row.dataset.meetingNumber = String(installation.meeting_number);
            row.dataset.decisionPoint = String(installation.decision_point);
            row.dataset.decisionNumber = String(installation.decision_number);
            row.dataset.sequence = String(installation.subdecision_sequence);
        }

        const recorded = this.hasReference(row);
        const ordinary = this.memberFunctionValue === installation.function;

        this.keep(row, 'discharge', recorded);
        this.keep(row, 'reappoint', recorded && ordinary);
        this.keep(row, 'inactive', recorded && ordinary);
        this.keep(row, 'function', ordinary);

        return row;
    }

    private fill(row: HTMLElement, name: string, functionName: string): void {
        (row.querySelector('[data-field="name"]') as HTMLElement).textContent = name;
        (row.querySelector('[data-field="function"]') as HTMLElement).textContent = functionName;
    }

    private keep(row: HTMLElement, role: string, keep: boolean): void {
        if (keep) {
            return;
        }

        row.querySelector(`[data-action-role="${role}"]`)?.remove();
    }

    private clone(template: HTMLTemplateElement): HTMLElement {
        return template.content.firstElementChild!.cloneNode(true) as HTMLElement;
    }

    private rowOf(event: Event): HTMLTableRowElement | null {
        const trigger = (event as Event & { relatedTarget?: HTMLElement }).relatedTarget;
        this.subject = trigger?.closest('tr') ?? null;

        return this.subject;
    }

    private rowsFor(lidnr: string): HTMLTableRowElement[] {
        return Array.from(this.resultTarget.querySelectorAll<HTMLTableRowElement>(`tr[data-lidnr="${lidnr}"]`));
    }

    private siblings(row: HTMLTableRowElement): HTMLTableRowElement[] {
        return this.rowsFor(row.dataset.lidnr ?? '');
    }

    private nameOf(row: HTMLTableRowElement): string {
        return this.names.get(row.dataset.lidnr ?? '') ?? '';
    }

    private hasReference(row: HTMLTableRowElement): boolean {
        return undefined !== row.dataset.meetingType;
    }

    private reference(row: HTMLTableRowElement): string {
        return [
            row.dataset.meetingType,
            row.dataset.meetingNumber,
            row.dataset.decisionPoint,
            row.dataset.decisionNumber,
            row.dataset.sequence,
        ].join('-');
    }

    private decisionOf(row: HTMLTableRowElement): string {
        return `${row.dataset.meetingType} ${row.dataset.meetingNumber}`
            + `.${row.dataset.decisionPoint}.${row.dataset.decisionNumber}`;
    }

    private referenceFields(row: HTMLTableRowElement): Record<string, string> {
        return {
            '%meeting_type%': row.dataset.meetingType ?? '',
            '%meeting_number%': row.dataset.meetingNumber ?? '',
            '%decision_point%': row.dataset.decisionPoint ?? '',
            '%decision_number%': row.dataset.decisionNumber ?? '',
            '%sequence%': row.dataset.sequence ?? '',
        };
    }

    private rank(installation: OrganInstallation): number {
        return this.memberFunctionValue === installation.function ? 0 : 1;
    }
}
