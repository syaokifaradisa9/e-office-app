import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import Button from '../../components/buttons/Button';
import FormInput from '../../components/forms/FormInput';
import RootLayout from '../../components/layouts/RootLayout';

export default function ProfilePassword() {
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put('/profile/password/update', {
            onSuccess: () => reset(),
        });
    };

    return (
        <RootLayout title="Ubah Kata Sandi">
            <div className="mx-auto max-w-2xl">
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div className="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Ubah Kata Sandi</h2>
                        <p className="mt-1 text-sm text-gray-500 dark:text-slate-400">Pastikan kata sandi baru Anda kuat dan mudah diingat</p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6 p-6">
                        <FormInput
                            name="current_password"
                            label="Kata Sandi Saat Ini"
                            type="password"
                            placeholder="Masukkan kata sandi saat ini"
                            value={data.current_password}
                            onChange={(e) => setData('current_password', e.target.value)}
                            error={errors.current_password}
                            required
                        />

                        <FormInput
                            name="password"
                            label="Kata Sandi Baru"
                            type="password"
                            placeholder="Masukkan kata sandi baru"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                            required
                        />

                        <FormInput
                            name="password_confirmation"
                            label="Konfirmasi Kata Sandi Baru"
                            type="password"
                            placeholder="Konfirmasi kata sandi baru"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                        />

                        <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                            <Button type="submit" label="Ubah Kata Sandi" icon={<Save className="h-4 w-4" />} isLoading={processing} />
                        </div>
                    </form>
                </div>
            </div>
        </RootLayout>
    );
}
