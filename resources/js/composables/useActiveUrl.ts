import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed, readonly } from 'vue';

import { toUrl } from '@/lib/utils';

const page = usePage();
const currentUrlReactive = computed(
    () => new URL(page.url, window?.location.origin).pathname,
);

export function useActiveUrl() {
    function normalizePath(path: string): string {
        if (path === '/') return '/';
        return path.endsWith('/') ? path.slice(0, -1) : path;
    }

    function urlIsActive(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
        matchMode: 'exact' | 'prefix' = 'exact',
    ) {
        const currentPath = normalizePath(
            currentUrl ?? currentUrlReactive.value,
        );
        const targetPath = normalizePath(toUrl(urlToCheck));

        if (matchMode === 'prefix') {
            return (
                currentPath === targetPath ||
                currentPath.startsWith(`${targetPath}/`)
            );
        }

        return targetPath === currentPath;
    }

    return {
        currentUrl: readonly(currentUrlReactive),
        urlIsActive,
    };
}
