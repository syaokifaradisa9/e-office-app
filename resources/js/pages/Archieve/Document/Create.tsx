import { useForm, usePage, router } from '@inertiajs/react';
import { Save, Upload, X, ChevronRight } from 'lucide-react';
import { useState, useEffect } from 'react';

import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface Category {
    id: number;
    name: string;
    description: string | null;
}

interface Context {
    id: number;
    name: string;
    description: string | null;
    categories: Category[];
}

interface Classification {
    id: number;
    parent_id: number | null;
    code: string;
    name: string;
    description: string | null;
    children?: Classification[];
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

interface Document {
    id: number;
    title: string;
    description: string | null;
    classification_id: number;
    categories: Category[];
    divisions: Division[];
    users: User[];
}

interface Props {
    document?: Document;
    contexts: Context[];
    classifications: Classification[];
    divisions: Division[];
    users: User[];
    canManageAll?: boolean;
    userDivisionId?: number;
}

export default function DocumentCreate({ document, contexts, classifications, divisions, users, canManageAll = true, userDivisionId }: Props) {
    const isEdit = !!document;

    // Classification cascade state
    const [classificationPath, setClassificationPath] = useState<Classification[]>([]);
    const [availableClassifications, setAvailableClassifications] = useState<Classification[][]>([]);

    // Users filtered by selected divisions
    const [filteredUsers, setFilteredUsers] = useState<User[]>([]);

    const { data, setData, post, processing, errors } = useForm({
        title: document?.title || '',
        description: document?.description || '',
        classification_id: document?.classification_id?.toString() || '',
        category_ids: document?.categories?.map(c => c.id.toString()) || [],
        division_ids: document?.divisions?.map(d => d.id.toString()) || [],
        user_ids: document?.users?.map(u => u.id.toString()) || [],
        file: null as File | null,
    });

    // Initialize classification cascade
    useEffect(() => {
        // Get root classifications (those without parent)
        const roots = classifications.filter(c => !c.parent_id);
        setAvailableClassifications([roots]);

        // If editing, rebuild the path
        if (document?.classification_id) {
            rebuildClassificationPath(document.classification_id);
        }
    }, [classifications, document]);

    // Filter users based on selected divisions
    useEffect(() => {
        if (data.division_ids.length > 0) {
            const divIds = data.division_ids.map(id => parseInt(id));
            const filtered = users.filter(u => divIds.includes(u.division_id));
            setFilteredUsers(filtered);

            // Remove user_ids that are not in filtered users
            const validUserIds = data.user_ids.filter(id => filtered.some(u => u.id.toString() === id));
            if (validUserIds.length !== data.user_ids.length) {
                setData('user_ids', validUserIds);
            }
        } else {
            setFilteredUsers([]);
            setData('user_ids', []);
        }
    }, [data.division_ids, users]);

    // Auto-select user's division if not canManageAll
    useEffect(() => {
        if (!canManageAll && userDivisionId && !isEdit) {
            setData('division_ids', [userDivisionId.toString()]);
            setFilteredUsers(users);
        }
    }, [canManageAll, userDivisionId]);

    function rebuildClassificationPath(classificationId: number) {
        const path: Classification[] = [];
        let current = classifications.find(c => c.id === classificationId);

        while (current) {
            path.unshift(current);
            current = current.parent_id ? classifications.find(c => c.id === current!.parent_id) : undefined;
        }

        setClassificationPath(path);

        // Build available classifications for each level
        const available: Classification[][] = [];
        const roots = classifications.filter(c => !c.parent_id);
        available.push(roots);

        for (let i = 0; i < path.length; i++) {
            const children = classifications.filter(c => c.parent_id === path[i].id);
            if (children.length > 0) {
                available.push(children);
            }
        }

        setAvailableClassifications(available);
    }

    function handleClassificationChange(level: number, classificationId: string) {
        if (!classificationId) {
            // Clear this level and all below
            const newPath = classificationPath.slice(0, level);
            const newAvailable = availableClassifications.slice(0, level + 1);
            setClassificationPath(newPath);
            setAvailableClassifications(newAvailable);
            setData('classification_id', newPath.length > 0 ? newPath[newPath.length - 1].id.toString() : '');
            return;
        }

        const selected = classifications.find(c => c.id === parseInt(classificationId));
        if (!selected) return;

        // Update path
        const newPath = [...classificationPath.slice(0, level), selected];
        setClassificationPath(newPath);

        // Get children for next level
        const children = classifications.filter(c => c.parent_id === selected.id);
        const newAvailable = [...availableClassifications.slice(0, level + 1)];
        if (children.length > 0) {
            newAvailable.push(children);
        }
        setAvailableClassifications(newAvailable);

        // Set the deepest classification as selected
        setData('classification_id', selected.id.toString());
    }

    function handleCategorySelect(contextId: number, categoryId: string) {
        // Remove any existing category from this context and add new one
        const contextCategories = contexts.find(c => c.id === contextId)?.categories.map(cat => cat.id.toString()) || [];
        const otherCategories = data.category_ids.filter(id => !contextCategories.includes(id));

        if (categoryId) {
            setData('category_ids', [...otherCategories, categoryId]);
        } else {
            setData('category_ids', otherCategories);
        }
    }

    function getSelectedCategoryForContext(contextId: number): string {
        const contextCategories = contexts.find(c => c.id === contextId)?.categories.map(cat => cat.id.toString()) || [];
        return data.category_ids.find(id => contextCategories.includes(id)) || '';
    }

    function handleDivisionToggle(divisionId: string) {
        const current = [...data.division_ids];
        const index = current.indexOf(divisionId);
        if (index > -1) {
            current.splice(index, 1);
        } else {
            current.push(divisionId);
        }
        setData('division_ids', current);
    }

    function handleUserToggle(userId: string) {
        const current = [...data.user_ids];
        const index = current.indexOf(userId);
        if (index > -1) {
            current.splice(index, 1);
        } else {
            current.push(userId);
        }
        setData('user_ids', current);
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const formData = new FormData();
        formData.append('title', data.title);
        formData.append('description', data.description || '');
        formData.append('classification_id', data.classification_id);
        data.category_ids.forEach((id, index) => {
            formData.append(`category_ids[${index}]`, id);
        });
        data.division_ids.forEach((id, index) => {
            formData.append(`division_ids[${index}]`, id);
        });
        data.user_ids.forEach((id, index) => {
            formData.append(`user_ids[${index}]`, id);
        });
        if (data.file) {
            formData.append('file', data.file);
        }

        if (isEdit) {
            formData.append('_method', 'PUT');
            router.post(`/archieve/documents/${document.id}`, formData);
        } else {
            router.post('/archieve/documents', formData);
        }
    };
    return (
        <RootLayout title={isEdit ? 'Edit Dokumen' : 'Upload Dokumen'}>
            <form onSubmit={handleSubmit}>
                <ContentCard title={isEdit ? 'Edit Dokumen Arsip' : 'Upload Dokumen Baru'} backPath="/archieve/documents" mobileFullWidth>
                    <div className="divide-y divide-slate-100 dark:divide-slate-700/50 -mx-6 -mb-6">
                        {/* Section 1: Basic Info */}
                        <div className="px-6 py-5">
                            <div className="mb-4 flex items-center gap-2">
                                <span className="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">1</span>
                                <h3 className="font-semibold text-slate-800 dark:text-white">Informasi Dokumen</h3>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <FormInput
                                        name="title"
                                        label="Judul Dokumen"
                                        placeholder="Masukkan judul dokumen"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        error={errors.title}
                                        required
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Deskripsi</label>
                                    <textarea
                                        placeholder="Masukkan deskripsi dokumen (opsional)"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={2}
                                        className="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800/40 dark:text-slate-200"
                                    />
                                </div>
                                <div className="sm:col-span-2">
                                    <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                                        File Dokumen {!isEdit && <span className="text-red-500">*</span>}
                                    </label>
                                    <label className="flex cursor-pointer items-center justify-center gap-3 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/50 px-6 py-6 transition-all hover:border-primary hover:bg-primary/5 dark:border-slate-600 dark:bg-slate-800/20">
                                        <div className="flex flex-col items-center gap-2">
                                            <div className="flex size-10 items-center justify-center rounded-full bg-primary/10">
                                                <Upload className="size-5 text-primary" />
                                            </div>
                                            <div className="text-center">
                                                <span className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                    {data.file ? data.file.name : 'Klik untuk pilih file'}
                                                </span>
                                                {!data.file && (
                                                    <p className="text-xs text-slate-400">PDF, Word, Excel, atau gambar</p>
                                                )}
                                            </div>
                                        </div>
                                        <input
                                            type="file"
                                            className="hidden"
                                            onChange={(e) => setData('file', e.target.files?.[0] || null)}
                                        />
                                    </label>
                                    {data.file && (
                                        <button
                                            type="button"
                                            onClick={() => setData('file', null)}
                                            className="mt-2 flex items-center gap-1 text-sm text-red-500 hover:text-red-700"
                                        >
                                            <X className="size-4" /> Hapus file
                                        </button>
                                    )}
                                    {errors.file && <p className="mt-1 text-sm text-red-500">{errors.file}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Section 2: Classification */}
                        <div className="px-6 py-5">
                            <div className="mb-4 flex items-center gap-2">
                                <span className="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">2</span>
                                <h3 className="font-semibold text-slate-800 dark:text-white">Klasifikasi</h3>
                            </div>
                            <div className="space-y-3">
                                {availableClassifications.map((levelOptions, level) => (
                                    <div key={level} className="flex items-start gap-2">
                                        {level > 0 && <ChevronRight className="mt-3 size-4 flex-shrink-0 text-slate-400" />}
                                        <div className="flex-1">
                                            <FormSelect
                                                name={`classification_level_${level}`}
                                                placeholder={level === 0 ? 'Pilih Klasifikasi' : 'Pilih Sub-Klasifikasi'}
                                                options={levelOptions.map(c => ({
                                                    value: c.id.toString(),
                                                    label: `[${c.code}] ${c.name}`,
                                                }))}
                                                value={classificationPath[level]?.id?.toString() || ''}
                                                onChange={(e) => handleClassificationChange(level, e.target.value)}
                                            />
                                            {classificationPath[level]?.description && (
                                                <p className="mt-1 text-xs italic text-slate-500">{classificationPath[level].description}</p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            {errors.classification_id && <p className="mt-2 text-sm text-red-500">{errors.classification_id}</p>}
                        </div>

                        {/* Section 3: Categories */}
                        <div className="px-6 py-5">
                            <div className="mb-4 flex items-center gap-2">
                                <span className="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">3</span>
                                <h3 className="font-semibold text-slate-800 dark:text-white">Kategori</h3>
                            </div>
                            <div className="space-y-4">
                                {contexts.map((context) => (
                                    <div key={context.id}>
                                        <label className="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                                            {context.name}
                                            {context.description && (
                                                <span className="text-xs font-normal text-slate-400">— {context.description}</span>
                                            )}
                                        </label>
                                        <div className="flex flex-wrap gap-2">
                                            {context.categories.map((category) => (
                                                <button
                                                    key={category.id}
                                                    type="button"
                                                    onClick={() => handleCategorySelect(context.id, category.id.toString())}
                                                    className={`group relative rounded-lg border px-3 py-2 text-sm transition-all ${getSelectedCategoryForContext(context.id) === category.id.toString()
                                                        ? 'border-primary bg-primary text-white shadow-sm'
                                                        : 'border-slate-200 bg-white text-slate-600 hover:border-primary/50 hover:shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'
                                                        }`}
                                                    title={category.description || undefined}
                                                >
                                                    {category.name}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                            {errors.category_ids && <p className="mt-2 text-sm text-red-500">{errors.category_ids}</p>}
                        </div>

                        {/* Section 4: Divisions & Users */}
                        <div className="px-6 py-5">
                            <div className="mb-4 flex items-center gap-2">
                                <span className="flex size-6 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">4</span>
                                <h3 className="font-semibold text-slate-800 dark:text-white">Akses Dokumen</h3>
                            </div>

                            {canManageAll && (
                                <div className="mb-5">
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Divisi <span className="text-xs font-normal text-slate-400">— Pilih divisi yang dapat mengakses</span>
                                    </label>
                                    <div className="flex flex-wrap gap-2">
                                        {divisions.map((division) => (
                                            <button
                                                key={division.id}
                                                type="button"
                                                onClick={() => handleDivisionToggle(division.id.toString())}
                                                className={`rounded-lg border px-3 py-2 text-sm transition-all ${data.division_ids.includes(division.id.toString())
                                                    ? 'border-emerald-500 bg-emerald-500 text-white shadow-sm'
                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'
                                                    }`}
                                            >
                                                {division.name}
                                            </button>
                                        ))}
                                    </div>
                                    {errors.division_ids && <p className="mt-1 text-sm text-red-500">{errors.division_ids}</p>}
                                </div>
                            )}

                            {filteredUsers.length > 0 && (
                                <div>
                                    <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        Arsip Pribadi <span className="text-xs font-normal text-slate-400">— Pilih pengguna (opsional)</span>
                                    </label>
                                    <div className="space-y-3">
                                        {divisions.filter(d => data.division_ids.includes(d.id.toString())).map((division) => {
                                            const divisionUsers = filteredUsers.filter(u => u.division_id === division.id);
                                            if (divisionUsers.length === 0) return null;
                                            return (
                                                <div key={division.id} className="rounded-lg bg-slate-50 p-3 dark:bg-slate-800/50">
                                                    <span className="mb-2 block text-xs font-semibold text-emerald-600 dark:text-emerald-400">{division.name}</span>
                                                    <div className="flex flex-wrap gap-2">
                                                        {divisionUsers.map((user) => (
                                                            <button
                                                                key={user.id}
                                                                type="button"
                                                                onClick={() => handleUserToggle(user.id.toString())}
                                                                className={`rounded-lg border px-3 py-1.5 text-sm transition-all ${data.user_ids.includes(user.id.toString())
                                                                    ? 'border-purple-500 bg-purple-500 text-white'
                                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-purple-400 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300'
                                                                    }`}
                                                            >
                                                                {user.name}
                                                            </button>
                                                        ))}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="flex items-center justify-between bg-slate-50 px-6 py-4 dark:bg-slate-800/80">
                        <p className="text-xs text-slate-400">
                            <span className="text-red-500">*</span> Wajib diisi
                        </p>
                        <div className="flex gap-3">
                            <Button href="/archieve/documents" label="Batal" variant="secondary" />
                            <Button
                                type="submit"
                                label={isEdit ? 'Simpan Perubahan' : 'Upload Dokumen'}
                                icon={<Save className="size-4" />}
                                isLoading={processing}
                            />
                        </div>
                    </div>
                </ContentCard>
            </form>
        </RootLayout>
    );
}
