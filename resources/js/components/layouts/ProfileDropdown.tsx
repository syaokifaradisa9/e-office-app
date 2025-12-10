import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, KeyRound, LogOut, Moon, Sun, User } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface LoggedUser {
    name: string;
    email: string;
    position?: string;
}

interface PageProps {
    loggeduser?: LoggedUser;
    [key: string]: unknown;
}

interface ProfileDropdownProps {
    isMobile?: boolean;
}

export default function ProfileDropdown({ isMobile = false }: ProfileDropdownProps) {
    const { loggeduser } = usePage<PageProps>().props;
    const [isProfileOpen, setProfileOpen] = useState(false);
    const [isDark, setIsDark] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);

    const getInitials = (name?: string): string => {
        if (!name) return 'U';
        const words = name.split(' ');
        if (words.length >= 2) {
            return (words[0][0] + words[1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    useEffect(() => {
        const darkMode = localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches);
        setIsDark(darkMode);
        document.documentElement.classList.toggle('dark', darkMode);
    }, []);

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setProfileOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const toggleTheme = () => {
        setIsDark(!isDark);
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', !isDark ? 'dark' : 'light');
    };

    if (isMobile) {
        return (
            <div className="relative" ref={dropdownRef}>
                <button
                    onClick={() => setProfileOpen(!isProfileOpen)}
                    className="flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-bold text-slate-700 transition-all duration-200 hover:bg-gray-100 active:scale-95 active:bg-gray-200 dark:text-white dark:hover:bg-slate-700"
                    aria-label="Menu"
                >
                    {getInitials(loggeduser?.name)}
                    <ChevronDown className={`size-4 transition-transform duration-200 ${isProfileOpen ? 'rotate-180' : ''}`} />
                </button>

                {isProfileOpen && (
                    <div className="absolute right-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
                        <div className="border-b border-gray-100 p-3 dark:border-slate-700">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-full bg-primary font-semibold text-primary-foreground">{getInitials(loggeduser?.name)}</div>
                                <div className="min-w-0 flex-1">
                                    <div className="truncate font-medium text-gray-900 dark:text-white">{loggeduser?.name}</div>
                                    <div className="truncate text-xs text-gray-500 dark:text-slate-400">{loggeduser?.email}</div>
                                </div>
                            </div>
                        </div>

                        <div className="p-1.5">
                            <Link href="/profile" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-gray-700 hover:bg-gray-100 dark:text-slate-200 dark:hover:bg-slate-700">
                                <User className="size-4 text-gray-500 dark:text-slate-400" />
                                <span className="text-sm">Ubah Profil</span>
                            </Link>

                            <Link href="/profile/password" className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-gray-700 hover:bg-gray-100 dark:text-slate-200 dark:hover:bg-slate-700">
                                <KeyRound className="size-4 text-gray-500 dark:text-slate-400" />
                                <span className="text-sm">Ubah Password</span>
                            </Link>

                            <button onClick={toggleTheme} className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-gray-700 hover:bg-gray-100 dark:text-slate-200 dark:hover:bg-slate-700">
                                {isDark ? <Sun className="size-4 text-amber-500" /> : <Moon className="size-4 text-indigo-500" />}
                                <span className="text-sm">{isDark ? 'Mode Terang' : 'Mode Gelap'}</span>
                            </button>

                            <div className="my-1 h-px bg-gray-100 dark:bg-slate-700" />

                            <Link
                                href="/auth/logout"
                                method="post"
                                as="button"
                                className="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                            >
                                <LogOut className="size-4" />
                                <span className="text-sm">Keluar</span>
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        );
    }

    return (
        <div className="relative" ref={dropdownRef}>
            <button
                onClick={() => setProfileOpen(!isProfileOpen)}
                className="flex items-center gap-3 rounded-lg px-3 py-1.5 transition-colors duration-200 hover:bg-primary/10 dark:hover:bg-slate-700/50"
            >
                <div className="flex size-8 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">{getInitials(loggeduser?.name)}</div>
                <div className="hidden text-left md:block">
                    <div className="text-sm font-medium text-slate-700 dark:text-slate-300">{loggeduser?.name}</div>
                    <div className="text-xs text-slate-500 dark:text-slate-400">{loggeduser?.position}</div>
                </div>
                <ChevronDown className={`size-4 text-slate-400 transition-transform duration-200 ${isProfileOpen ? 'rotate-180' : ''}`} />
            </button>

            {isProfileOpen && (
                <div className="absolute right-0 mt-1 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                    <div className="border-b border-gray-100 p-3 dark:border-slate-700">
                        <div className="text-sm font-medium text-slate-700 dark:text-white">{loggeduser?.name}</div>
                        <div className="truncate text-sm text-slate-500 dark:text-slate-400">{loggeduser?.email}</div>
                    </div>
                    <div className="p-1.5">
                        <Link href="/profile" className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700">
                            <User className="size-4" />
                            Ubah Profile
                        </Link>
                        <Link
                            href="/profile/password"
                            className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <KeyRound className="size-4" />
                            Ubah Password
                        </Link>
                        <button
                            onClick={toggleTheme}
                            className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            {isDark ? (
                                <>
                                    <Sun className="size-4" />
                                    Mode Terang
                                </>
                            ) : (
                                <>
                                    <Moon className="size-4" />
                                    Mode Gelap
                                </>
                            )}
                        </button>
                        <div className="my-1 h-px bg-gray-100 dark:bg-slate-700" />
                        <Link
                            href="/auth/logout"
                            method="post"
                            as="button"
                            className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                        >
                            <LogOut className="size-4" />
                            Keluar
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}
