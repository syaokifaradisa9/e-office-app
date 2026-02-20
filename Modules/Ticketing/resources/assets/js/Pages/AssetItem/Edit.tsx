import RootLayout from '@/components/layouts/RootLayout';
import ContentCard from '@/components/layouts/ContentCard';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import FormCheckboxGroup from '@/components/forms/FormCheckboxGroup';
import FormDynamicAttributes from '@/components/forms/FormDynamicAttributes';
import Button from '@/components/buttons/Button';
import { useForm } from '@inertiajs/react';
import { Save, X, Users, Calendar, History } from 'lucide-react';
import { useEffect, useState, useRef, useMemo } from 'react';

interface AnotherAttributes {
    specs?: Record<string, string>;
}

interface AssetModel {
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

interface AssetItem {
    id: number;
    asset_model_id: number;
    merk: string | null;
    model: string | null;
    serial_number: string | null;
    division_id: number;
    users: User[];
    another_attributes: AnotherAttributes;
    last_maintenance_date: string | null;
}

interface Props {
    asset: AssetItem;
    assetModels: AssetModel[];
    divisions: Division[];
    users: User[];
}

export default function AssetItemEdit({ asset, assetModels, divisions, users }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        asset_model_id: asset.asset_model_id,
        merk: asset.merk || '',
        model: asset.model || '',
        serial_number: asset.serial_number || '',
        division_id: asset.division_id,
        user_ids: asset.users.map(u => u.id),
        another_attributes: asset.another_attributes || {} as AnotherAttributes,
    });

    const [filteredUsers, setFilteredUsers] = useState<User[]>([]);
    const isFirstRender = useRef(true);

    useEffect(() => {
        if (data.division_id) {
            const divId = Number(data.division_id);
            const divUsers = users.filter(u => u.division_id === divId);
            setFilteredUsers(divUsers);

            // Only reset user_ids if division actually changed (not on first render)
            if (!isFirstRender.current) {
                setData('user_ids', []);
            }
        } else {
            setFilteredUsers([]);
            if (!isFirstRender.current) {
                setData('user_ids', []);
            }
        }
        isFirstRender.current = false;
    }, [data.division_id]);



    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/ticketing/assets/${asset.id}/update`);
    };

    return (
        <RootLayout title="Edit Asset" backPath="/ticketing/assets">
            <ContentCard
                title="Edit Asset"
                subtitle={`Perbarui informasi asset: ${asset.serial_number || asset.merk || asset.id}`}
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
                                name="asset_model_id"
                                label="Asset Model"
                                placeholder="Pilih Asset Model"
                                value={data.asset_model_id}
                                onChange={(e) => setData('asset_model_id', Number(e.target.value))}
                                options={assetModels.map(m => ({ value: m.id, label: m.name }))}
                                error={errors.asset_model_id}
                                required
                            />

                            <FormSelect
                                name="division_id"
                                label="Divisi"
                                placeholder="Pilih Divisi"
                                value={data.division_id}
                                onChange={(e) => setData('division_id', Number(e.target.value))}
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



                    <div className="flex justify-end gap-3 border-t border-slate-100 pt-6 dark:border-slate-800">
                        <Button
                            href="/ticketing/assets"
                            label="Batal"
                            variant="secondary"
                            icon={<X className="size-4" />}
                        />
                        <Button
                            type="submit"
                            label="Perbarui Asset"
                            icon={<Save className="size-4" />}
                            isLoading={processing}
                        />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
