/**
 * Renders stored lesson body for display. If content looks like HTML (starts with a tag),
 * it is passed through (trusted instructor content). Otherwise plain text is escaped and
 * paragraphs / line breaks are preserved.
 */

function escapeHtml(s: string): string {
    return s
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function plainTextToHtml(text: string): string {
    const blocks = text.split(/\n\n+/);
    return blocks
        .map((block) => {
            const lines = block.split('\n').map((line) => escapeHtml(line));
            return `<p>${lines.join('<br />')}</p>`;
        })
        .join('');
}

export function lessonBodyHtml(
    content: string | null | undefined,
    emptyMessage = 'No lesson content has been provided yet.',
): string {
    if (!content?.trim()) {
        return `<p class="text-gray-500 italic">${escapeHtml(emptyMessage)}</p>`;
    }
    const trimmed = content.trim();
    if (/^<[a-z!/]/i.test(trimmed)) {
        return trimmed;
    }
    return plainTextToHtml(trimmed);
}

/** Course full description: HTML from the rich editor, or legacy plain text as safe paragraphs. */
export function courseDescriptionHtml(
    content: string | null | undefined,
): string {
    if (!content?.trim()) {
        return '';
    }
    const trimmed = content.trim();
    if (/^<[a-z!/]/i.test(trimmed)) {
        return trimmed;
    }
    return plainTextToHtml(trimmed);
}
