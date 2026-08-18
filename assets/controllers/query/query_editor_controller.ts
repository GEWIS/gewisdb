import { Controller } from '@hotwired/stimulus';

/**
 * Keyboard handling for the DQL box on the query page, in place of the CodeMirror instance jQuery used to attach to
 * it. It sits on the form rather than on the textarea, because running the query needs the "Execute" button: the form
 * carries a second submit button that saves the query instead, so a submit without a submitter would be ambiguous.
 *
 *     <form data-controller="query-editor">
 *         <textarea data-query-editor-target="editor" data-action="keydown->query-editor#keydown"></textarea>
 *         <button type="submit" data-query-editor-target="submit">Execute</button>
 *
 * Tab indents by `indentValue` spaces and Shift+Tab takes that indentation away again, which is what a Tab is for
 * inside a query. That does take Tab away from keyboard navigation, so Escape releases the field: the next Tab then
 * moves focus as it normally would, and typing anything puts indenting back.
 */
export default class extends Controller<HTMLElement> {
    static targets = ['editor', 'submit'];

    static values = {
        indent: { type: Number, default: 4 },
    };

    declare readonly editorTarget: HTMLTextAreaElement;
    declare readonly submitTarget: HTMLElement;
    declare readonly hasSubmitTarget: boolean;
    declare readonly indentValue: number;

    private indenting = true;

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
