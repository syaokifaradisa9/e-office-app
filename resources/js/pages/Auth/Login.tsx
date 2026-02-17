import { Head, useForm, usePage } from '@inertiajs/react';
import { PageProps } from '@inertiajs/core';
import { LogIn, ShieldCheck } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import toast, { Toaster } from 'react-hot-toast';

import Button from '../../components/buttons/Button';
import ThemeToggle from '../../components/commons/ThemeToggle';
import FormInput from '../../components/forms/FormInput';


interface FlashMessage {
    type?: 'success' | 'error';
    message?: string;
}

interface LoginPageProps extends PageProps {
    flash?: FlashMessage;
}

const heroSlides = [
    {
        image: 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=960&q=80',
        title: 'Kelola administrasi kantor — sepenuhnya digital',
        description: 'Satu platform untuk menyederhanakan seluruh proses kerja Anda. Lebih cepat, lebih rapi, dan tanpa repot.',
    },
    {
        image: 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=960&q=80',
        title: 'Kolaborasi tim yang lebih efisien dan terstruktur',
        description: 'Tingkatkan produktivitas dan koordinasi antar tim dengan alur kerja yang terorganisir dalam satu tempat.',
    },
    {
        image: 'https://images.unsplash.com/photo-1497215842964-222b430dc094?auto=format&fit=crop&w=960&q=80',
        title: 'Laporan dan monitoring secara real-time',
        description: 'Dapatkan insight dan visibilitas penuh terhadap operasional kantor Anda — kapan saja, di mana saja.',
    },
];

export default function Login() {
    const { setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const { flash } = usePage<LoginPageProps>().props;
    const [activeSlide, setActiveSlide] = useState(0);

    useEffect(() => {
        const { type, message } = flash || {};

        if (type === 'success' && message) toast.success(message);
        if (type === 'error' && message) toast.error(message);
    }, [flash]);

    // Auto-slide every 6 seconds
    const nextSlide = useCallback(() => {
        setActiveSlide((prev) => (prev + 1) % heroSlides.length);
    }, []);

    useEffect(() => {
        const interval = setInterval(nextSlide, 6000);
        return () => clearInterval(interval);
    }, [nextSlide]);

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/auth/verify');
    };

    return (
        <div className="relative flex h-screen w-full items-center justify-center overflow-hidden bg-[#E8EDF2] p-4 dark:bg-slate-900 sm:p-6 lg:p-8">
            <Head title="Login | e-Office" />
            <Toaster position="bottom-right" />

            {/* Abstract Animated Background */}
            <div className="pointer-events-none absolute inset-0 overflow-hidden">
                {/* Gradient Orbs */}
                <div className="absolute -left-20 -top-20 h-80 w-80 rounded-full bg-gradient-to-br from-primary/20 to-blue-400/15 opacity-60 blur-3xl dark:from-primary/25 dark:to-blue-500/15 dark:opacity-50"></div>
                <div className="absolute -right-16 top-1/4 h-96 w-96 rounded-full bg-gradient-to-bl from-purple-400/15 to-pink-400/10 opacity-50 blur-3xl dark:from-purple-500/20 dark:to-pink-500/12"></div>
                <div className="absolute -bottom-24 left-1/3 h-80 w-80 rounded-full bg-gradient-to-tr from-cyan-400/15 to-blue-400/10 opacity-50 blur-3xl dark:from-cyan-500/18 dark:to-blue-500/12"></div>
                <div className="absolute bottom-1/4 right-1/4 h-64 w-64 rounded-full bg-gradient-to-tl from-emerald-400/10 to-teal-400/15 opacity-40 blur-3xl dark:from-emerald-500/15 dark:to-teal-500/12"></div>

                {/* Floating Geometric Shapes */}
                <div className="animate-float absolute left-[6%] top-[12%] h-16 w-16 rotate-12 rounded-2xl border-2 border-primary/15 dark:border-primary/30"></div>
                <div className="animate-float-delayed absolute right-[8%] top-[15%] h-12 w-12 rounded-full border-2 border-blue-400/15 dark:border-blue-400/25"></div>
                <div className="animate-float absolute bottom-[18%] left-[10%] h-10 w-10 -rotate-12 rounded-xl border-2 border-purple-400/15 dark:border-purple-400/25"></div>
                <div className="animate-float-delayed absolute bottom-[12%] right-[6%] h-14 w-14 rotate-45 rounded-2xl border-2 border-pink-400/12 dark:border-pink-400/22"></div>
                <div className="animate-float absolute left-[3%] top-[55%] h-8 w-8 rotate-6 rounded-lg border-2 border-cyan-400/15 dark:border-cyan-400/25"></div>
                <div className="animate-float-delayed absolute right-[4%] top-[65%] h-10 w-10 -rotate-45 rounded-xl border-2 border-emerald-400/12 dark:border-emerald-400/22"></div>
                <div className="animate-float absolute left-[45%] top-[5%] h-6 w-6 rotate-45 rounded-md border-2 border-indigo-400/12 dark:border-indigo-400/22"></div>
                <div className="animate-float-delayed absolute bottom-[8%] left-[40%] h-8 w-8 rounded-full border-2 border-sky-400/12 dark:border-sky-400/22"></div>

                {/* Decorative Dots */}
                <div className="absolute left-[4%] top-[35%] grid grid-cols-3 gap-1.5">
                    {[...Array(9)].map((_, i) => (
                        <div key={`dl-${i}`} className="h-1.5 w-1.5 rounded-full bg-primary/10 dark:bg-primary/20"></div>
                    ))}
                </div>
                <div className="absolute bottom-[30%] right-[5%] grid grid-cols-3 gap-1.5">
                    {[...Array(9)].map((_, i) => (
                        <div key={`dr-${i}`} className="h-1.5 w-1.5 rounded-full bg-blue-400/10 dark:bg-blue-400/18"></div>
                    ))}
                </div>

                {/* Abstract SVG Lines */}
                <svg className="absolute left-[2%] top-[75%] h-20 w-20 text-primary/8 dark:text-primary/20" viewBox="0 0 100 100">
                    <path d="M10,50 Q30,20 50,50 T90,50" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                    <path d="M10,65 Q30,35 50,65 T90,65" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                </svg>
                <svg className="absolute bottom-[5%] right-[12%] h-24 w-24 rotate-180 text-purple-400/8 dark:text-purple-400/18" viewBox="0 0 100 100">
                    <circle cx="20" cy="20" r="8" fill="none" stroke="currentColor" strokeWidth="1.5" />
                    <circle cx="55" cy="35" r="12" fill="none" stroke="currentColor" strokeWidth="1.5" />
                    <circle cx="80" cy="18" r="6" fill="none" stroke="currentColor" strokeWidth="1.5" />
                </svg>
            </div>

            {/* Theme Toggle */}
            <ThemeToggle />

            {/* Main Card Container - Bigger */}
            <div className="flex w-full max-w-[1200px] overflow-hidden rounded-3xl bg-white shadow-2xl shadow-slate-300/50 dark:bg-slate-800 dark:shadow-black/30 lg:max-h-[90vh]">

                {/* Left Panel - Hero Image Slideshow */}
                <div className="relative hidden shrink-0 overflow-hidden lg:block lg:w-[55%]">
                    {/* Slide Images */}
                    {heroSlides.map((slide, index) => (
                        <img
                            key={index}
                            src={slide.image}
                            alt={`Slide ${index + 1}`}
                            className={`absolute inset-0 h-full w-full object-cover transition-opacity duration-1000 ease-in-out ${index === activeSlide ? 'opacity-100' : 'opacity-0'
                                }`}
                        />
                    ))}

                    {/* Dark Overlay Gradient */}
                    <div className="absolute inset-0 bg-gradient-to-b from-slate-900/60 via-slate-900/40 to-slate-900/70"></div>

                    {/* Overlay Text Content */}
                    <div className="relative z-10 flex h-full flex-col justify-between p-10">
                        <div className="mt-6">
                            {/* Slide Texts with transition */}
                            {heroSlides.map((slide, index) => (
                                <div
                                    key={index}
                                    className={`absolute left-10 right-10 top-16 transition-all duration-700 ease-in-out ${index === activeSlide
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                        }`}
                                >
                                    <h2 className="max-w-[340px] text-3xl font-bold leading-tight text-white">
                                        {slide.title}
                                    </h2>
                                    <p className="mt-4 max-w-[360px] text-sm leading-relaxed text-white/80">
                                        {slide.description}
                                    </p>
                                </div>
                            ))}
                        </div>

                        {/* Slide Indicators */}
                        <div className="flex gap-2 pb-4">
                            {heroSlides.map((_, index) => (
                                <button
                                    key={index}
                                    type="button"
                                    onClick={() => setActiveSlide(index)}
                                    className={`h-2 rounded-full transition-all duration-500 ${index === activeSlide
                                        ? 'w-8 bg-white'
                                        : 'w-2 bg-white/40 hover:bg-white/60'
                                        }`}
                                />
                            ))}
                        </div>
                    </div>
                </div>

                {/* Right Panel - Login Form */}
                <div className="flex w-full flex-col justify-center px-8 py-10 sm:px-12 lg:px-16">
                    {/* Auth Icon */}
                    <div className="mb-3 flex justify-center">
                        <div className="flex items-center justify-center rounded-xl bg-primary/10 p-3 dark:bg-primary/15">
                            <ShieldCheck className="size-8 text-primary" strokeWidth={1.5} />
                        </div>
                    </div>

                    {/* Heading */}
                    <h1 className="mb-2 text-center text-2xl font-bold text-slate-800 dark:text-white">
                        Selamat Datang 👋
                    </h1>
                    <p className="mb-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        Kelola seluruh administrasi kantor dalam satu sistem terintegrasi.
                    </p>

                    {/* Form */}
                    <form onSubmit={onSubmit} className="space-y-4">
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

                        <div className="pt-2"></div>

                        <Button
                            isLoading={processing}
                            label="Masuk"
                            icon={<LogIn className="size-4" />}
                            type="submit"
                            className="w-full"
                        />
                    </form>

                    {/* Footer */}
                    <div className="mt-8 border-t border-gray-200/80 pt-4 dark:border-slate-700/50">
                        <p className="text-center text-xs text-slate-400 dark:text-slate-500">
                            © {new Date().getFullYear()} E-Office. Sistem Administrasi Kantor Digital.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
