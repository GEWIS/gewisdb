import { Controller } from '@hotwired/stimulus';
import type { ActionEvent } from '@hotwired/stimulus';

/**
 * The DQL box on the query page, in place of the CodeMirror instance jQuery used to attach to it: keyboard handling,
 * the line numbers beside it, and the entity list that types into it. It sits above the form rather than on the
 * textarea, because running the query needs the "Execute" button (the form carries a second submit button that saves
 * the query instead, so a submit without a submitter would be ambiguous) and because the entity list is outside the
 * form.
 *
 *     <div data-controller="query-editor">
 *         <span data-query-editor-target="gutter"></span>
 *         <textarea data-query-editor-target="editor" data-action="keydown->query-editor#keydown"></textarea>
 *         <button type="submit" data-query-editor-target="submit">Execute</button>
 *         <button data-query-editor-target="entity" data-action="query-editor#insert"
 *                 data-query-editor-entity-param="db:Member">db:Member</button>
 *
 * Tab indents by `indentValue` spaces and Shift+Tab takes that indentation away again, which is what a Tab is for
 * inside a query. That does take Tab away from keyboard navigation, so Escape releases the field: the next Tab then
 * moves focus as it normally would, and typing anything puts indenting back.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['editor', 'submit', 'gutter', 'meta', 'entity', 'entityEmpty'];

    static values = {
        indent: { type: Number, default: 4 },
    };

    declare readonly editorTarget: HTMLTextAreaElement;
    declare readonly submitTarget: HTMLElement;
    declare readonly hasSubmitTarget: boolean;
    declare readonly hasGutterTarget: boolean;
    declare readonly gutterTarget: HTMLElement;
    declare readonly hasMetaTarget: boolean;
    declare readonly metaTarget: HTMLElement;
    declare readonly entityTargets: HTMLElement[];
    declare readonly hasEntityEmptyTarget: boolean;
    declare readonly entityEmptyTarget: HTMLElement;

    private indenting = true;

    connect(): void {
        this.renumber();
    }

    /**
     * Number the lines beside the box, and say how many there are.
     *
     * The box does not soft-wrap (`wrap="off"`), so a line in the value is a line on screen and the two lists stay
     * aligned however long a query gets.
     */
    renumber(): void {
        const lines = this.editorTarget.value.split('\n').length;

        if (this.hasGutterTarget) {
            this.gutterTarget.textContent = Array.from({ length: lines }, (_, i) => i + 1).join('\n');
        }

        if (this.hasMetaTarget) {
            const label = this.metaTarget.dataset.label ?? '%number% lines · DQL';
            this.metaTarget.textContent = label.replace('%number%', String(lines));
        }
    }

    /**
     * Keep the numbers level with the text when a long query is scrolled.
     */
    sync(): void {
        if (!this.hasGutterTarget) {
            return;
        }

        this.gutterTarget.scrollTop = this.editorTarget.scrollTop;
    }

    clear(): void {
        this.editorTarget.value = '';
        this.editorTarget.focus();
        this.renumber();
    }

    /**
     * Type a `SELECT` over the clicked entity into the box, on its own line, so the list is a way to start a query
     * rather than a list to copy from.
     */
    insert(event: ActionEvent): void {
        const entity = String(event.params.entity);
        const editor = this.editorTarget;
        const separator = '' === editor.value.trim() ? '' : '\n';

        editor.value += separator + 'SELECT x FROM ' + entity + ' x';
        editor.focus();
        editor.setSelectionRange(editor.value.length, editor.value.length);
        this.renumber();
    }

    filter(event: Event): void {
        const needle = (event.target as HTMLInputElement).value.trim().toLowerCase();
        let shown = 0;

        this.entityTargets.forEach((entity) => {
            const matches = '' === needle || (entity.textContent ?? '').toLowerCase().includes(needle);

            entity.hidden = !matches;
            shown += matches ? 1 : 0;
        });

        if (this.hasEntityEmptyTarget) {
            this.entityEmptyTarget.hidden = 0 !== shown;
        }
    }

    keydown(event: KeyboardEvent): void {
        if ('Enter' === event.key && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            this.run();

            return;
        }

        if ('Escape' === event.key) {
            this.indenting = false;

            return;
        }

        if ('Tab' !== event.key) {
            this.indenting = true;

            return;
        }

        if (!this.indenting) {
            return;
        }

        event.preventDefault();

        if (event.shiftKey) {
            this.outdent();
        } else {
            this.indent();
        }
    }

    /**
     * Submits through the "Execute" button so the query is run and not saved.
     */
    private run(): void {
        if (!this.hasSubmitTarget) {
            return;
        }

        this.submitTarget.click();
    }

    private indent(): void {
        const editor = this.editorTarget;
        const spaces = ' '.repeat(this.indentValue);

        // A caret indents where it stands; a selection indents every line it touches, so that indenting a block does
        // not replace the block.
        if (editor.selectionStart === editor.selectionEnd) {
            this.replace(editor.selectionStart, editor.selectionEnd, spaces);

            return;
        }

        this.mapSelectedLines((line) => spaces + line);
    }

    private outdent(): void {
        const leading = new RegExp(`^ {1,${this.indentValue}}`);

        this.mapSelectedLines((line) => line.replace(leading, ''));
    }

    /**
     * Rewrites every line the selection touches, and leaves the rewritten lines selected so the same key can be held
     * to indent further.
     */
    private mapSelectedLines(map: (line: string) => string): void {
        const editor = this.editorTarget;
        const start = editor.value.lastIndexOf('\n', editor.selectionStart - 1) + 1;
        const newline = editor.value.indexOf('\n', editor.selectionEnd);
        const end = -1 === newline ? editor.value.length : newline;

        const replacement = editor.value
            .slice(start, end)
            .split('\n')
            .map(map)
            .join('\n');

        this.replace(start, end, replacement);
        editor.setSelectionRange(start, start + replacement.length);
    }

    private replace(
        start: number,
        end: number,
        text: string,
    ): void {
        const editor = this.editorTarget;
        editor.focus();
        editor.setSelectionRange(start, end);

        // `insertText` is deprecated, but it is the only way to change the value that leaves the browser's own undo
        // stack intact; `setRangeText` empties it. Where the command is refused the edit still lands, only undoing it
        // does not work.
        if (document.execCommand('insertText', false, text)) {
            return;
        }

        editor.setRangeText(text, start, end, 'end');
        editor.dispatchEvent(new Event('input', { bubbles: true }));
    }
}
