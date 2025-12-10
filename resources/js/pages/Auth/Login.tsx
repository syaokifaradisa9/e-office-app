import { Head, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@inertiajs/core';
import { LogIn } from 'lucide-react';
import { useEffect } from 'react';
import toast, { Toaster } from 'react-hot-toast';

import AuthBackground from '../../components/auth/AuthBackground';
import Button from '../../components/buttons/Button';
import ThemeToggle from '../../components/commons/ThemeToggle';
import FormInput from '../../components/forms/FormInput';
import Logo from '../../components/images/Logo';

interface FlashMessage {
    type?: 'success' | 'error';
    message?: string;
}

interface LoginPageProps extends PageProps {
    flash?: FlashMessage;
}

export default function Login() {
    const { setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const { flash } = usePage<LoginPageProps>().props;

    useEffect(() => {
        const { type, message } = flash || {};

        if (type === 'success' && message) toast.success(message);
        if (type === 'error' && message) toast.error(message);
    }, [flash]);

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/auth/verify');
    };

    return (
        <div className="relative min-h-screen w-full overflow-hidden bg-[#F5FAFA] dark:bg-slate-900">
            <Head title="Login | E-Office" />
            <Toaster position="bottom-right" />

            {/* Abstract Background Objects */}
            <AuthBackground />

            {/* Main Content */}
            <div className="relative z-10 flex min-h-screen w-full flex-col items-center justify-center text-foreground">
                {/* Theme Toggle */}
                <ThemeToggle />

                {/* Login Card - Same colors as TopBar & SideBar */}
                <div className="flex h-screen w-full flex-col border-0 border-gray-300/50 bg-white/95 px-8 pb-12 pt-0 backdrop-blur-xl dark:border-slate-700/50 dark:bg-slate-800/95 sm:h-auto sm:max-w-[420px] sm:rounded-2xl sm:border sm:p-10 sm:shadow-2xl sm:shadow-primary/5">
                    {/* Spacer for mobile - pushes content down slightly */}
                    <div className="flex-1 sm:hidden"></div>

                    {/* Header */}
                    <div className="mb-8 flex flex-col items-center text-center sm:mb-10">
                        <div className="mb-2">
                            <Logo iconSize="size-10" textSize="text-3xl" />
                        </div>
                        <p className="mt-2 max-w-[300px] text-sm leading-relaxed text-slate-500 dark:text-slate-400 sm:mt-3">
                            Masuk untuk mulai mengelola administrasi kantor Anda.
                        </p>
                    </div>

                    <form onSubmit={onSubmit} className="space-y-4">
                        <div className="space-y-4">
                            <FormInput
                                name="email"
                                label="Email"
                                placeholder="nama@kantor.com"
                                type="email"
                                autoComplete="username"
                                onChange={(e) => setData('email', e.target.value)}
                                error={errors?.email}
                            />

                            <FormInput
                                name="password"
                                label="Kata Sandi"
                                placeholder="••••••••"
                                type="password"
                                autoComplete="current-password"
                                onChange={(e) => setData('password', e.target.value)}
                                error={errors?.password}
                            />
                        </div>

                        <Button
                            isLoading={processing}
                            label="Masuk Aplikasi"
                            icon={<LogIn className="size-4" />}
                            type="submit"
                            className="w-full"
                        />
                    </form>

                    {/* Spacer for mobile - fills remaining space */}
                    <div className="flex-1 sm:hidden"></div>

                    {/* Footer */}
                    <div className="mt-8 border-t border-gray-200/50 pt-5 dark:border-slate-700/50 sm:mt-10 sm:pt-6">
                        <p className="text-center text-xs text-slate-400 dark:text-slate-500">
                            © {new Date().getFullYear()} E-Office. Sistem Administrasi Kantor Digital.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
