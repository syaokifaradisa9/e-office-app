import { useForm } from '@inertiajs/react';
import { Save, Info } from 'lucide-react';

import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface Category {
    id: number;
    name: string;
}

interface ReferenceItem {
    id: number;
    name: string;
    unit_of_measure: string;
}

interface Item {
    id: number;
    name: string;
    category_id: number | null;
    unit_of_measure: string;
    stock: number;
    description: string | null;
    image_url: string | null;
    multiplier: number | null;
    reference_item_id: number | null;
    category?: Category;
    referenceItem?: ReferenceItem;
}

interface Props {
    item?: Item;
    categories: Category[];
    referenceItems?: ReferenceItem[];
}

export default function ItemCreate({ item, categories, referenceItems = [] }: Props) {
    const isEdit = !!item;

    const { data, setData, post, put, processing, errors } = useForm({
        name: item?.name || '',
        category_id: item?.category_id?.toString() || '',
        unit_of_measure: item?.unit_of_measure || 'pcs',
        stock: item?.stock?.toString() || '0',
        description: item?.description || '',
        multiplier: item?.multiplier?.toString() || '1',
        reference_item_id: item?.reference_item_id?.toString() || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(`/inventory/items/${item.id}/update`);
        } else {
            post('/inventory/items/store');
        }
    };

    const categoryOptions = categories.map((c) => ({ value: c.id.toString(), label: c.name }));
    const referenceItemOptions = [
        { value: '', label: 'Tidak Ada (Barang Satuan Terkecil)' },
        ...referenceItems.map((r) => ({ value: r.id.toString(), label: `${r.name} (${r.unit_of_measure})` })),
    ];

    const selectedReference = referenceItems.find((r) => r.id.toString() === data.reference_item_id);

    return (
        <RootLayout title={isEdit ? 'Edit Barang' : 'Tambah Barang'} backPath="/inventory/items">
            <ContentCard
                title={isEdit ? 'Edit Barang' : 'Tambah Barang Baru'}
                mobileFullWidth
            >
                <p className="mb-6 text-sm text-gray-500 dark:text-slate-400">Isi informasi barang gudang utama di bawah ini</p>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <FormInput name="name" label="Nama Barang" placeholder="Masukkan nama barang" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />

                    <FormSelect
                        name="category_id"
                        label="Kategori"
                        placeholder="Pilih kategori"
                        options={categoryOptions}
                        value={data.category_id}
                        onChange={(e) => setData('category_id', e.target.value)}
                        error={errors.category_id}
                    />

                    <div className="grid grid-cols-2 gap-4">
                        <FormInput
                            name="unit_of_measure"
                            label="Satuan"
                            placeholder="pcs, box, kg, dll"
                            value={data.unit_of_measure}
                            onChange={(e) => setData('unit_of_measure', e.target.value)}
                            error={errors.unit_of_measure}
                            required
                        />

                        <FormInput
                            name="stock"
                            label="Stok Awal"
                            placeholder="0"
                            type="number"
                            value={data.stock}
                            onChange={(e) => setData('stock', e.target.value)}
                            error={errors.stock}
                            required
                        />
                    </div>

                    {/* Conversion Settings */}
                    <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                        <div className="mb-3 flex items-center gap-2">
                            <Info className="size-4 text-primary" />
                            <h3 className="text-sm font-medium text-gray-700 dark:text-slate-300">Pengaturan Konversi (Opsional)</h3>
                        </div>
                        <p className="mb-4 text-xs text-gray-500 dark:text-slate-400">
                            Jika barang ini dapat dikonversi ke satuan lebih kecil, atur multiplier dan barang referensi.
                            Contoh: 1 Box = 12 Pcs, maka multiplier = 12 dan referensi = Pcs.
                        </p>

                        <div className="grid grid-cols-2 gap-4">
                            <FormInput
                                name="multiplier"
                                label="Multiplier"
                                placeholder="Contoh: 12"
                                type="number"
                                min="1"
                                value={data.multiplier}
                                onChange={(e) => setData('multiplier', e.target.value)}
                                error={errors.multiplier}
                            />

                            <FormSelect
                                name="reference_item_id"
                                label="Barang Referensi"
                                placeholder="Pilih barang satuan kecil"
                                options={referenceItemOptions}
                                value={data.reference_item_id}
                                onChange={(e) => setData('reference_item_id', e.target.value)}
                                error={errors.reference_item_id}
                            />
                        </div>

                        {data.multiplier && selectedReference && (
                            <div className="mt-3 rounded bg-blue-50 p-2 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                                Hasil konversi: 1 {data.unit_of_measure} = {data.multiplier} {selectedReference.unit_of_measure}
                            </div>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium text-gray-700 dark:text-slate-300">Deskripsi</label>
                        <textarea
                            placeholder="Masukkan deskripsi barang (opsional)"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={4}
                            className="w-full rounded-lg border border-gray-400/50 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200 dark:placeholder:text-slate-400"
                        />
                        {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                        <Button href="/inventory/items" label="Batal" variant="secondary" />
                        <Button type="submit" label={isEdit ? 'Simpan Perubahan' : 'Simpan'} icon={<Save className="h-4 w-4" />} isLoading={processing} />
                    </div>
                </form>
            </ContentCard>
        </RootLayout>
    );
}
