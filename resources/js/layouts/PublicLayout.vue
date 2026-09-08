<template>
    <div class="flex min-h-screen flex-col">
        <!-- Navigation -->
        <nav
            class="sticky top-0 z-50 border-b border-gray-100 bg-white shadow-sm"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    <!-- Logo -->
                    <Link href="/" class="flex flex-shrink-0 items-center">
                        <img
                            :src="
                                $page.props.siteSettings.logo_url ||
                                '/images/Tratz Academy-Horizontal Profile.svg'
                            "
                            :alt="$page.props.siteSettings.logo_text"
                            class="h-11 w-auto transition-opacity duration-200 hover:opacity-80"
                        />
                    </Link>

                    <!-- Desktop Navigation Links -->
                    <div class="hidden items-center gap-1 lg:flex">
                        <Link
                            href="/"
                            :class="[
                                'relative rounded-lg px-3 py-2 text-sm font-semibold transition-colors duration-200',
                                page.url === '/'
                                    ? 'bg-[#42b6c5]/5 text-[#42b6c5]'
                                    : 'text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5]',
                            ]"
                        >
                            Home
                        </Link>

                        <Link
                            href="/programs"
                            :class="[
                                'relative rounded-lg px-3 py-2 text-sm font-semibold transition-colors duration-200',
                                page.url.includes('/programs')
                                    ? 'bg-[#42b6c5]/5 text-[#42b6c5]'
                                    : 'text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5]',
                            ]"
                        >
                            Programs
                        </Link>

                        <Link
                            href="/events"
                            :class="[
                                'relative rounded-lg px-3 py-2 text-sm font-semibold transition-colors duration-200',
                                page.url.includes('/events')
                                    ? 'bg-[#42b6c5]/5 text-[#42b6c5]'
                                    : 'text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5]',
                            ]"
                        >
                            Events
                        </Link>

                        <!-- Community Dropdown -->
                        <div
                            class="relative"
                            @mouseenter="communityOpen = true"
                            @mouseleave="communityOpen = false"
                        >
                            <Link
                                href="/community"
                                :class="[
                                    'flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold transition-colors duration-200',
                                    page.url.includes('/community')
                                        ? 'bg-[#42b6c5]/5 text-[#42b6c5]'
                                        : 'text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5]',
                                ]"
                            >
                                Community
                                <svg
                                    :class="[
                                        'h-3.5 w-3.5 transition-transform duration-200',
                                        communityOpen ? 'rotate-180' : '',
                                    ]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </Link>

                            <transition
                                enter-active-class="transition ease-out duration-150"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-1"
                            >
                                <div
                                    v-if="communityOpen"
                                    class="absolute top-full left-1/2 z-50 mt-1 w-56 -translate-x-1/2 rounded-xl border border-gray-100 bg-white py-2 shadow-lg"
                                >
                                    <Link
                                        v-for="item in communityLinks"
                                        :key="item.href"
                                        :href="item.href"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                                    >
                                        {{ item.label }}
                                    </Link>
                                </div>
                            </transition>
                        </div>

                        <!-- Discover Dropdown -->
                        <div
                            class="relative"
                            @mouseenter="discoverOpen = true"
                            @mouseleave="discoverOpen = false"
                        >
                            <button
                                :class="[
                                    'flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-semibold transition-colors duration-200',
                                    [
                                        '/gallery',
                                        '/resources',
                                        '/about',
                                        '/success-stories',
                                        '/ai-forge',
                                    ].some((p) => page.url.includes(p))
                                        ? 'bg-[#42b6c5]/5 text-[#42b6c5]'
                                        : 'text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5]',
                                ]"
                            >
                                Discover
                                <svg
                                    :class="[
                                        'h-3.5 w-3.5 transition-transform duration-200',
                                        discoverOpen ? 'rotate-180' : '',
                                    ]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>

                            <transition
                                enter-active-class="transition ease-out duration-150"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-1"
                            >
                                <div
                                    v-if="discoverOpen"
                                    class="absolute top-full left-1/2 z-50 mt-1 w-56 -translate-x-1/2 rounded-xl border border-gray-100 bg-white py-2 shadow-lg"
                                >
                                    <Link
                                        href="/gallery"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                                    >
                                        <svg
                                            class="h-4 w-4 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                        Gallery
                                    </Link>
                                    <Link
                                        href="/ai-forge"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                                    >
                                        <svg
                                            class="h-4 w-4 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                            />
                                        </svg>
                                        AI Forge
                                    </Link>
                                    <Link
                                        href="/resources"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                                    >
                                        <svg
                                            class="h-4 w-4 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                            />
                                        </svg>
                                        Resources
                                    </Link>
                                    <Link
                                        href="/success-stories"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                                    >
                                        <svg
                                            class="h-4 w-4 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                                            />
                                        </svg>
                                        Success Stories
                                    </Link>
                                    <Link
                                        href="/about"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                                    >
                                        <svg
                                            class="h-4 w-4 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        About Us
                                    </Link>
                                </div>
                            </transition>
                        </div>

                        <Link
                            href="/contact"
                            :class="[
                                'relative rounded-lg px-3 py-2 text-sm font-semibold transition-colors duration-200',
                                page.url === '/contact'
                                    ? 'bg-[#42b6c5]/5 text-[#42b6c5]'
                                    : 'text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5]',
                            ]"
                        >
                            Contact
                        </Link>

                        <!-- Online Courses -->
                        <Link
                            href="/online-courses"
                            :class="[
                                'relative rounded-lg px-3 py-2 text-sm font-semibold transition-colors duration-200',
                                page.url.includes('/online-courses')
                                    ? 'bg-[#42b6c5]/5 text-[#42b6c5]'
                                    : 'text-gray-700 hover:bg-gray-50 hover:text-[#42b6c5]',
                            ]"
                        >
                            Online Courses
                        </Link>
                    </div>

                    <!-- Desktop Right: Cart + Auth -->
                    <div class="hidden items-center gap-3 lg:flex">
                        <!-- Cart Icon -->
                        <Link
                            href="/ai-forge/cart"
                            class="relative rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                            title="Shopping Cart"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"
                                />
                            </svg>
                            <span
                                v-if="cartCount > 0"
                                class="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[#42b6c5] px-1 text-[10px] font-bold text-white"
                            >
                                {{ cartCount > 99 ? '99+' : cartCount }}
                            </span>
                        </Link>

                        <div class="h-6 w-px bg-gray-200"></div>

                        <template v-if="$page.props.auth.user">
                            <Link
                                :href="
                                    [
                                        'cto',
                                        'ceo',
                                        'program_coordinator',
                                        'admin',
                                    ].includes($page.props.auth.user.role)
                                        ? '/admin/dashboard'
                                        : '/dashboard'
                                "
                                class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                            >
                                Dashboard
                            </Link>
                            <Link
                                v-if="$page.props.communityMembership?.is_member"
                                href="/community/me"
                                class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                            >
                                Member Area
                            </Link>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                title="Logout"
                                aria-label="Logout"
                                class="rounded-lg p-2 text-gray-600 transition-colors hover:bg-red-50 hover:text-red-600"
                            >
                                <LogOut class="h-5 w-5" />
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                href="/login"
                                class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 hover:text-[#42b6c5]"
                            >
                                Login
                            </Link>
                            <Link
                                href="/programs"
                                class="inline-flex transform items-center rounded-lg bg-[#42b6c5] px-6 py-2.5 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:scale-105 hover:bg-[#35919e] hover:shadow-lg"
                            >
                                Apply Now
                                <svg
                                    class="ml-2 h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile: Cart + menu button -->
                    <div class="flex items-center gap-2 lg:hidden">
                        <!-- Mobile Cart Icon -->
                        <Link
                            href="/ai-forge/cart"
                            class="relative rounded-lg p-2 text-gray-600 transition-colors hover:text-[#42b6c5]"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"
                                />
                            </svg>
                            <span
                                v-if="cartCount > 0"
                                class="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[#42b6c5] px-1 text-[10px] font-bold text-white"
                            >
                                {{ cartCount > 99 ? '99+' : cartCount }}
                            </span>
                        </Link>

                        <Link
                            href="/programs"
                            class="inline-flex items-center rounded-lg bg-[#42b6c5] px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#35919e]"
                        >
                            Apply
                        </Link>
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="rounded-lg p-2.5 text-gray-700 transition-colors hover:bg-gray-100"
                            :aria-expanded="mobileMenuOpen"
                        >
                            <svg
                                v-if="!mobileMenuOpen"
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>
                            <svg
                                v-else
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile menu -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div
                        v-if="mobileMenuOpen"
                        class="max-h-[calc(100vh-5rem)] overflow-y-auto border-t border-gray-100 bg-white py-4 lg:hidden"
                    >
                        <div class="space-y-1 px-2">
                            <!-- Main Links -->
                            <Link
                                href="/"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'block rounded-lg px-4 py-3 text-sm font-semibold transition-colors duration-200',
                                    page.url === '/'
                                        ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                                        : 'text-gray-700 hover:bg-gray-100',
                                ]"
                            >
                                Home
                            </Link>

                            <Link
                                href="/programs"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'block rounded-lg px-4 py-3 text-sm font-semibold transition-colors duration-200',
                                    page.url.includes('/programs')
                                        ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                                        : 'text-gray-700 hover:bg-gray-100',
                                ]"
                            >
                                Programs
                            </Link>

                            <Link
                                href="/events"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'block rounded-lg px-4 py-3 text-sm font-semibold transition-colors duration-200',
                                    page.url.includes('/events')
                                        ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                                        : 'text-gray-700 hover:bg-gray-100',
                                ]"
                            >
                                Events
                            </Link>

                            <!-- Community Section -->
                            <div class="mt-2 border-t border-gray-100 pt-2">
                                <p
                                    class="px-4 py-1 text-xs font-semibold tracking-wider text-gray-400 uppercase"
                                >
                                    Community
                                </p>
                                <Link
                                    v-for="item in communityLinks"
                                    :key="item.href"
                                    :href="item.href"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'block rounded-lg px-4 py-2.5 text-sm transition-colors duration-200',
                                        page.url === item.href
                                            ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]'
                                            : 'text-gray-600 hover:bg-gray-100',
                                    ]"
                                >
                                    {{ item.label }}
                                </Link>
                            </div>

                            <Link
                                href="/contact"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'block rounded-lg px-4 py-3 text-sm font-semibold transition-colors duration-200',
                                    page.url === '/contact'
                                        ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                                        : 'text-gray-700 hover:bg-gray-100',
                                ]"
                            >
                                Contact
                            </Link>

                            <Link
                                href="/online-courses"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'block rounded-lg px-4 py-3 text-sm font-semibold transition-colors duration-200',
                                    page.url.includes('/online-courses')
                                        ? 'bg-[#42b6c5]/10 text-[#42b6c5]'
                                        : 'text-gray-700 hover:bg-gray-100',
                                ]"
                            >
                                Online Courses
                            </Link>

                            <!-- Discover Section -->
                            <div class="mt-2 border-t border-gray-100 pt-2">
                                <p
                                    class="px-4 py-1 text-xs font-semibold tracking-wider text-gray-400 uppercase"
                                >
                                    Discover
                                </p>
                                <Link
                                    href="/gallery"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'block rounded-lg px-4 py-2.5 text-sm transition-colors duration-200',
                                        page.url.includes('/gallery')
                                            ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]'
                                            : 'text-gray-600 hover:bg-gray-100',
                                    ]"
                                >
                                    Gallery
                                </Link>
                                <Link
                                    href="/ai-forge"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'block rounded-lg px-4 py-2.5 text-sm transition-colors duration-200',
                                        page.url.includes('/ai-forge')
                                            ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]'
                                            : 'text-gray-600 hover:bg-gray-100',
                                    ]"
                                >
                                    AI Forge
                                </Link>
                                <Link
                                    href="/resources"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'block rounded-lg px-4 py-2.5 text-sm transition-colors duration-200',
                                        page.url.includes('/resources')
                                            ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]'
                                            : 'text-gray-600 hover:bg-gray-100',
                                    ]"
                                >
                                    Resources
                                </Link>
                                <Link
                                    href="/success-stories"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'block rounded-lg px-4 py-2.5 text-sm transition-colors duration-200',
                                        page.url === '/success-stories'
                                            ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]'
                                            : 'text-gray-600 hover:bg-gray-100',
                                    ]"
                                >
                                    Success Stories
                                </Link>
                                <Link
                                    href="/about"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'block rounded-lg px-4 py-2.5 text-sm transition-colors duration-200',
                                        page.url === '/about'
                                            ? 'bg-[#42b6c5]/10 font-semibold text-[#42b6c5]'
                                            : 'text-gray-600 hover:bg-gray-100',
                                    ]"
                                >
                                    About Us
                                </Link>
                            </div>

                            <!-- Mobile Auth Links -->
                            <div class="mt-2 border-t border-gray-100 pt-2">
                                <template v-if="$page.props.auth.user">
                                    <Link
                                        :href="
                                            [
                                                'cto',
                                                'ceo',
                                                'program_coordinator',
                                                'admin',
                                            ].includes(
                                                $page.props.auth.user.role,
                                            )
                                                ? '/admin/dashboard'
                                                : '/dashboard'
                                        "
                                        @click="mobileMenuOpen = false"
                                        class="block rounded-lg px-4 py-3 text-sm font-semibold text-gray-700 transition-colors duration-200 hover:bg-gray-100"
                                    >
                                        Dashboard
                                    </Link>
                                    <Link
                                        v-if="$page.props.communityMembership?.is_member"
                                        href="/community/me"
                                        @click="mobileMenuOpen = false"
                                        class="block rounded-lg px-4 py-3 text-sm font-semibold text-gray-700 transition-colors duration-200 hover:bg-gray-100"
                                    >
                                        Member Area
                                    </Link>
                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        @click="mobileMenuOpen = false"
                                        class="block w-full rounded-lg px-4 py-3 text-sm font-semibold text-red-600 transition-colors duration-200 hover:bg-red-50"
                                    >
                                        Logout
                                    </Link>
                                </template>
                                <template v-else>
                                    <Link
                                        href="/login"
                                        @click="mobileMenuOpen = false"
                                        class="block rounded-lg px-4 py-3 text-sm font-semibold text-gray-700 transition-colors duration-200 hover:bg-gray-100"
                                    >
                                        Login
                                    </Link>
                                </template>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </nav>

        <!-- Flash Messages -->
        <div
            v-if="page.props.flash?.success"
            class="border-l-4 border-green-500 bg-green-50 p-4"
        >
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg
                        class="h-5 w-5 text-green-500"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        {{ page.props.flash?.success }}
                    </p>
                </div>
            </div>
        </div>
        <div
            v-if="page.props.flash?.error"
            class="border-l-4 border-red-500 bg-red-50 p-4"
        >
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg
                        class="h-5 w-5 text-red-500"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">
                        {{ page.props.flash?.error }}
                    </p>
                </div>
            </div>
        </div>
        <div
            v-if="page.props.flash?.info"
            class="border-l-4 border-blue-500 bg-blue-50 p-4"
        >
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg
                        class="h-5 w-5 text-blue-500"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-800">
                        {{ page.props.flash?.info }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <Toaster />

        <!-- Main Content -->
        <main class="flex-grow">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-24 bg-[#000928] py-16 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-4">
                    <!-- Brand -->
                    <div>
                        <h3 class="mb-4 text-lg font-bold">
                            {{ $page.props.siteSettings.site_title }}
                        </h3>
                        <p class="text-sm text-gray-300">
                            {{ $page.props.siteSettings.site_description }}
                        </p>
                    </div>

                    <!-- Programs -->
                    <div>
                        <h4 class="mb-4 font-semibold">Programs</h4>
                        <ul class="space-y-2 text-sm text-gray-300">
                            <li>
                                <Link
                                    href="/programs"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >All Programs</Link
                                >
                            </li>
                            <li>
                                <Link
                                    href="/programs"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >Trainings</Link
                                >
                            </li>
                            <li>
                                <Link
                                    href="/programs"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >Internships</Link
                                >
                            </li>
                        </ul>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="mb-4 font-semibold">Quick Links</h4>
                        <ul class="space-y-2 text-sm text-gray-300">
                            <li>
                                <Link
                                    href="/about"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >About Us</Link
                                >
                            </li>
                            <li>
                                <Link
                                    href="/online-courses"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >Online Courses</Link
                                >
                            </li>
                            <li>
                                <Link
                                    href="/events"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >Events</Link
                                >
                            </li>
                            <li>
                                <Link
                                    href="/community"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >Community</Link
                                >
                            </li>
                            <li>
                                <Link
                                    href="/success-stories"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >Success Stories</Link
                                >
                            </li>
                            <li>
                                <Link
                                    href="/ai-forge"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >AI Forge</Link
                                >
                            </li>
                            <li>
                                <Link
                                    href="/contact"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >Contact</Link
                                >
                            </li>
                        </ul>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h4 class="mb-4 font-semibold">Get in Touch</h4>
                        <template v-if="$page.props.siteSettings.contact_email">
                            <p class="mb-2 text-sm text-gray-300">
                                Email:
                                <a
                                    :href="`mailto:${$page.props.siteSettings.contact_email}`"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >{{
                                        $page.props.siteSettings.contact_email
                                    }}</a
                                >
                            </p>
                        </template>
                        <template
                            v-if="$page.props.siteSettings.contact_whatsapp"
                        >
                            <p class="mb-4 text-sm text-gray-300">
                                WhatsApp:
                                <a
                                    :href="`https://wa.me/${$page.props.siteSettings.contact_whatsapp.replace(/[^0-9]/g, '')}`"
                                    target="_blank"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >{{
                                        $page.props.siteSettings
                                            .contact_whatsapp
                                    }}</a
                                >
                            </p>
                        </template>
                        <template
                            v-if="!$page.props.siteSettings.contact_whatsapp"
                        >
                            <p class="mb-4 text-sm text-gray-300">
                                WhatsApp:
                                <a
                                    href="https://wa.me/"
                                    class="transition-colors hover:text-[#42b6c5]"
                                    >Contact us</a
                                >
                            </p>
                        </template>
                        <div class="mt-4 flex space-x-4">
                            <a
                                v-if="$page.props.siteSettings.social_linkedin"
                                :href="$page.props.siteSettings.social_linkedin"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-[#42b6c5] transition-colors hover:text-white"
                            >
                                LinkedIn
                            </a>
                            <a
                                v-if="$page.props.siteSettings.social_twitter"
                                :href="$page.props.siteSettings.social_twitter"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-[#42b6c5] transition-colors hover:text-white"
                            >
                                Twitter
                            </a>
                            <a
                                v-if="$page.props.siteSettings.social_facebook"
                                :href="$page.props.siteSettings.social_facebook"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-[#42b6c5] transition-colors hover:text-white"
                            >
                                Facebook
                            </a>
                            <a
                                v-if="$page.props.siteSettings.social_instagram"
                                :href="
                                    $page.props.siteSettings.social_instagram
                                "
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-[#42b6c5] transition-colors hover:text-white"
                            >
                                Instagram
                            </a>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-700 pt-8">
                    <p class="text-center text-sm text-gray-400">
                        {{ $page.props.siteSettings.footer_copyright_text }} |
                        {{ $page.props.siteSettings.footer_powered_by }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Toaster } from '@/components/ui/toast';

const page = usePage();
const mobileMenuOpen = ref(false);
const discoverOpen = ref(false);
const communityOpen = ref(false);

const communityLinks = [
    { href: '/community', label: 'Overview' },
    { href: '/community/about', label: 'About TAC' },
    { href: '/community/tracks', label: 'Tracks' },
    { href: '/community/activities', label: 'Activities' },
    { href: '/community/team', label: 'Team' },
    { href: '/community/partners', label: 'Partners' },
    { href: '/community/get-involved', label: 'Get Involved' },
    { href: '/community/join', label: 'Join TAC' },
];

const cartCount = computed(() => {
    return ((page.props as Record<string, unknown>).cartCount as number) ?? 0;
});
</script>
