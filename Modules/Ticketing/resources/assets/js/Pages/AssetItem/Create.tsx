import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import FormCheckboxGroup from '@/components/forms/FormCheckboxGroup';
import FormTextArea from '@/components/forms/FormTextArea';
import FormDynamicAttributes from '@/components/forms/FormDynamicAttributes';
import Button from '@/components/buttons/Button';
import { useForm } from '@inertiajs/react';
import { Save, X, Users, Calendar, History } from 'lucide-react';
import { useEffect, useState, useMemo } from 'react';

interface AnotherAttributes {
    specs?: Record<string, string>;
}

interface AssetCategory {
    id: number;
    name: string;
    maintenance_count: number;
}

interface Division {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    division_id: number;
}

interface Props {
    assetCategories: AssetCategory[];
    divisions: Division[];
    users: User[];
}

export default function AssetItemCreate({ assetCategories, divisions, users }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        asset_category_id: '',
        merk: '',
        model: '',
        serial_number: '',
        division_id: '',
        user_ids: [] as number[],
        another_attributes: {} as AnotherAttributes,
        last_maintenance_date: '',
    });

    const [filteredUsers, setFilteredUsers] = useState<User[]>([]);

    useEffect(() => {
        if (data.division_id) {
            const divId = Number(data.division_id);
            const divUsers = users.filter(u => u.division_id === divId);
            setFilteredUsers(divUsers);
            setData('user_ids', []);
        } else {
            setFilteredUsers([]);
            setData('user_ids', []);
        }
    }, [data.division_id]);

    const estimatedSchedules = useMemo(() => {
        if (!data.asset_category_id || !data.last_maintenance_date) return [];

        const selectedCategory = assetCategories.find((m) => m.id === Number(data.asset_category_id));
        if (!selectedCategory || !selectedCategory.maintenance_count) return [];

        const schedules: Date[] = [];
        const lastDate = new Date(data.last_maintenance_date);
        const currentYear = lastDate.getFullYear();
        const intervalMonths = Math.floor(12 / selectedCategory.maintenance_count);

        const nextDate = new Date(lastDate);
        nextDate.setMonth(nextDate.getMonth() + intervalMonths);

        while (nextDate.getFullYear() === currentYear) {
            schedules.push(new Date(nextDate));
            nextDate.setMonth(nextDate.getMonth() + intervalMonths);
        }

        // If no schedules found this year (meaning we just finished the last one for the year),
        // then show the full cycle for the next year.
        if (schedules.length === 0) {
            for (let i = 0; i < selectedCategory.maintenance_count; i++) {
                schedules.push(new Date(nextDate));
                nextDate.setMonth(nextDate.getMonth() + intervalMonths);
            }
        }

        return schedules;
    }, [data.asset_category_id, data.last_maintenance_date]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/ticketing/assets/store');
    };

    return (
        <RootLayout title="Tambah Asset" backPath="/ticketing/assets">
            <ContentCard
                title="Tambah Asset Baru"
                subtitle="Daftarkan item asset baru ke dalam sistem"
                backPath="/ticketing/assets"
                mobileFullWidth
            >
                <form onSubmit={handleSubmit} className="space-y-8">
                    <div className="space-y-6">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Informasi Dasar</h3>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <FormSelect
                                name="asset_category_id"
                                label="Kategori Asset"
                                placeholder="Pilih Kategori Asset"
                                value={data.asset_category_id}
                                onChange={(e) => setData('asset_category_id', e.target.value)}
                                options={assetCategories.map(m => ({ value: m.id, label: m.name }))}
                                error={errors.asset_category_id}
                                required
                            />

                            <FormSelect
                                name="division_id"
                                label="Divisi"
                                placeholder="Pilih Divisi"
                                value={data.division_id}
                                onChange={(e) => setData('division_id', e.target.value)}
                                options={divisions.map(d => ({ value: d.id, label: d.name }))}
                                error={errors.division_id}
                                required
                            />

                            <FormInput
                                name="merk"
                                label="Merk"
                                placeholder="Contoh: Dell, HP, Lenovo"
                                value={data.merk}
                                onChange={(e) => setData('merk', e.target.value)}
                                error={errors.merk}
                            />

                            <FormInput
                                name="model"
                                label="Model"
                                placeholder="Contoh: Latitude 5420, ThinkPad X1"
                                value={data.model}
                                onChange={(e) => setData('model', e.target.value)}
                                error={errors.model}
                            />

                            <FormInput
                                name="serial_number"
                                label="Serial Number (S/N)"
                                placeholder="Nomor seri perangkat"
                                value={data.serial_number}
                                onChange={(e) => setData('serial_number', e.target.value)}
                                error={errors.serial_number}
                            />
                        </div>
                    </div>



                    <div className="space-y-6">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Atribut Lainnya</h3>
                        </div>

                        <div className="space-y-4">
                            <FormDynamicAttributes
                                label="Spesifikasi / Atribut Terstruktur"
                                value={data.another_attributes.specs || {}}
                                onChange={(specs) => setData('another_attributes', { ...data.another_attributes, specs: specs as Record<string, string> })}
                                error={errors.another_attributes as unknown as string}
                            />
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <Users className="size-5 text-primary" />
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Pemegang Asset</h3>
                        </div>

                        <FormCheckboxGroup
                            name="user_ids"
                            options={filteredUsers.map(u => ({ value: u.id, label: u.name }))}
                            value={data.user_ids}
                            onChange={(val) => setData('user_ids', val as number[])}
                            error={errors.user_ids}
                            disabled={!data.division_id}
                            columns={3}
                        />
                    </div>

                    <div className="space-y-6">
                        <div className="flex items-center gap-2 border-l-4 border-primary pl-3">
                            <h3 className="text-base font-bold text-slate-800 dark:text-white">Estimasi Maintenance</h3>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <FormInput
                                name="last_maintenance_date"
                                type="date"
                                label="Tanggal Terakhir Maintenance"
                                value={data.last_maintenance_date}
                                onChange={(e) => setData('last_maintenance_date', e.target.value)}
                                error={errors.last_maintenance_date}
                            />

                            {data.asset_category_id && (
                                <div className="flex flex-col justify-center rounded-xl bg-slate-50 dark:bg-slate-900/50 p-4 border border-slate-100 dark:border-slate-800">
                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Frekuensi Maintenance</p>
                                    <div className="flex items-baseline gap-2 mt-1">
                                        <span className="text-2xl font-black text-primary">
                                            {assetCategories.find(m => m.id === Number(data.asset_category_id))?.maintenance_count || 0}
                                        </span>
                                        <span className="text-sm font-medium text-slate-500 italic">Kali per Tahun</span>
                                    </div>
                                </div>
                            )}
                        </div>

                        {estimatedSchedules.length > 0 && (
                            <div className="rounded-xl bg-primary/5 p-5 border border-primary/10">
                                <div className="flex items-start gap-4">
                                    <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 shrink-0">
                                        <History className="size-5 text-primary" />
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Jadwal Estimasi Maintenance Berikutnya</p>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                            {estimatedSchedules.map((date, idx) => (
                                                <div key={idx} className="flex items-center gap-3 bg-white dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700 shadow-sm">
                                                    <div className="flex size-8 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-[10px] font-bold text-slate-400">
                                                        {idx + 1}
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-bold text-slate-700 dark:text-slate-200">
                                                            {date.toLocaleDateString('id-ID', {
                                                                day: 'numeric',
                                                                month: 'long',
                                                                year: 'numeric'
                                                            })}
                                                        </p>
                                                        <p className="text-[10px] text-slate-400">Mendatang</p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                        <p className="text-[11px] text-primary/70 italic mt-4 flex items-center gap-1.5">
                                            <span className="size-1 rounded-full bg-primary/40" />
                                            Dihitung otomatis berdasarkan frekuensi maintenance kategori aset ({assetCategories.find(m => m.id === Number(data.asset_category_id))?.maintenance_count}x per tahun)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
                        <Button
                            href="/ticketing/assets"
                            label="Batal"
                            variant="secondary"
                            icon={<X className="size-4" />}
                        />
                        <Button
                            type="submit"
                            label="Simpan Asset"
                            icon={<Save className="size-4" />}
                            isLoading={processing}
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
