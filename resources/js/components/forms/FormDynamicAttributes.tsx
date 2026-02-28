import { Trash2, Plus } from 'lucide-react';
import FormInput from './FormInput';

interface FormDynamicAttributesProps {
    label?: string;
    value: Record<string, any>;
    onChange: (value: Record<string, any>) => void;
    error?: string;
}

export default function FormDynamicAttributes({
    label = "Atribut Tambahan",
    value,
    onChange,
    error
}: FormDynamicAttributesProps) {
    // value is an object like { "RAM": "16GB", "Storage": "512GB" }
    // We convert it to arrays to manage it in the UI
    const attributes = Object.entries(value).map(([key, val]) => ({ key, value: val }));

    const handleAdd = () => {
        onChange({ ...value, "": "" });
    };

    const handleRemove = (index: number) => {
        const newEntries = Object.entries(value).filter((_, i) => i !== index);
        onChange(Object.fromEntries(newEntries));
    };

    const handleUpdate = (index: number, newKey: string, newValue: any) => {
        const entries = Object.entries(value);
        // If we only change value of an existing key
        if (entries[index][0] === newKey) {
            entries[index][1] = newValue;
            onChange(Object.fromEntries(entries));
        } else {
            // If the key itself changed, we need to be careful about order and overwriting
            const newObj: Record<string, any> = {};
            entries.forEach(([k, v], i) => {
                if (i === index) {
                    newObj[newKey] = newValue;
                } else {
                    newObj[k] = v;
                }
            });
            onChange(newObj);
        }
    };

    return (
        <div className="space-y-4">
            {label && (
                <div className="flex items-center justify-between">
                    <label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                        {label}
                    </label>
                    <button
                        type="button"
                        onClick={handleAdd}
                        className="flex items-center gap-1.5 text-xs font-semibold text-primary hover:text-primary/80 transition-colors"
                    >
                        <Plus className="size-3.5" />
                        Tambah Atribut
                    </button>
                </div>
            )}

            {attributes.length === 0 ? (
                <div
                    onClick={handleAdd}
                    className="flex cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-slate-200 py-6 text-sm text-slate-400 transition-colors hover:border-primary/50 hover:text-slate-500 dark:border-slate-800 dark:hover:border-primary/40"
                >
                    Klik untuk menambah atribut tambahan
                </div>
            ) : (
                <div className="space-y-3">
                    {attributes.map((attr, index) => (
                        <div key={index} className="group relative flex items-start gap-3">
                            <div className="grid flex-1 grid-cols-1 gap-3 md:grid-cols-2">
                                <FormInput
                                    name={`attr_key_${index}`}
                                    placeholder="Nama Atribut (e.g. Garansi)"
                                    value={attr.key}
                                    onChange={(e) => handleUpdate(index, e.target.value, attr.value)}
                                    className="!mb-0"
                                />
                                <FormInput
                                    name={`attr_value_${index}`}
                                    placeholder="Nilai Atribut (e.g. 2 Tahun)"
                                    value={attr.value}
                                    onChange={(e) => handleUpdate(index, attr.key, e.target.value)}
                                    className="!mb-0"
                                />
                            </div>
                            <button
                                type="button"
                                onClick={() => handleRemove(index)}
                                className="mt-2.5 rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all dark:hover:bg-red-900/20"
                                title="Hapus Atribut"
                            >
                                <Trash2 className="size-4" />
                            </button>
                        </div>
                    ))}
                </div>
            )}

            {error && <p className="text-xs text-red-500">{error}</p>}
        </div>
    );
}
