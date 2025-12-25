import { usePage } from '@inertiajs/react';

interface PageProps {
    auth: { user: { name: string } };
    [key: string]: unknown;
}

export default function Welcome() {
    const { auth } = usePage<PageProps>().props;
    const userName = auth?.user?.name || 'User';

    const formatDate = () => {
        return new Date().toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    return (
        <div className="flex items-center justify-between">
            <div>
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                    Selamat Datang, {userName}
                </h1>
                <p className="mt-1 text-slate-500 dark:text-slate-400">
                    {formatDate()}
                </p>
            </div>
        </div>
    );
}
