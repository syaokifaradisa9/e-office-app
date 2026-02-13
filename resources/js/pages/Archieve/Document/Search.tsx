import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { Search, ChevronRight, ChevronDown, FileText, Folder, FolderOpen, Download, Filter, X, File, Loader2, Check } from 'lucide-react';
import { ArchievePermission } from '@/enums/ArchievePermission';

import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';

interface Category {
    id: number;
    name: string;
}

interface Context {
    id: number;
    name: string;
    categories: Category[];
}

interface Classification {
    id: number;
    code: string;
    name: string;
    children?: Classification[];
    direct_documents_count: number;
    total_documents_count: number;
}

interface Division {
    id: number;
    name: string;
}

interface Document {
    id: number;
    title: string;
    file_name: string;
    file_path: string;
    file_size_label: string;
    created_at: string;
    classification: { code: string; name: string };
    categories: Category[];
    divisions: Division[];
    users: { id: number; name: string }[];
    uploader: { name: string };
}

interface Props {
    classifications: Classification[];
    contexts: Context[];
    divisions: Division[];
}

export default function DocumentSearch({ classifications: initialClassifications, contexts, divisions }: Props) {
    const { permissions } = usePage<any>().props;

    // Explicit visibility logic based on permission levels
    const hasKeseluruhan = permissions?.includes(ArchievePermission.SEARCH_ALL_SCOPE);
    const hasDivisi = permissions?.includes(ArchievePermission.SEARCH_DIVISION_SCOPE);
    const hasPribadi = permissions?.includes(ArchievePermission.SEARCH_PERSONAL_SCOPE);

    const showDivisionFilter = hasKeseluruhan;
    const showStaffFilter = hasKeseluruhan || hasDivisi;

    const [classifications, setClassifications] = useState<Classification[]>(initialClassifications);
    const [expandedNodes, setExpandedNodes] = useState<Set<number>>(new Set());
    const [loadingNodes, setLoadingNodes] = useState<Set<number>>(new Set());
    const [documentsByClassification, setDocumentsByClassification] = useState<Record<number, Document[]>>({});

    const [filters, setFilters] = useState({
        category_ids: contexts.flatMap(ctx => ctx.categories.map(c => c.id)),
        division_ids: divisions.map(d => d.id),
        user_name: '',
        search: '',
    });

    const [isFiltering, setIsFiltering] = useState(false);
    const [hasActiveFilters, setHasActiveFilters] = useState(true);

    // Initial filter application on mount
    useEffect(() => {
        applyFilters();
    }, []);

    // Check if any filter is active
    useEffect(() => {
        const active = filters.category_ids.length > 0 || filters.division_ids.length > 0 || filters.user_name || filters.search;
        setHasActiveFilters(!!active);
    }, [filters]);

    // Handle multi-category toggle
    const toggleCategory = (categoryId: number) => {
        setFilters(prev => {
            const newIds = prev.category_ids.includes(categoryId)
                ? prev.category_ids.filter(id => id !== categoryId)
                : [...prev.category_ids, categoryId];
            return { ...prev, category_ids: newIds };
        });
    };

    // Handle multi-division toggle
    const toggleDivision = (divisionId: number) => {
        setFilters(prev => {
            const newIds = prev.division_ids.includes(divisionId)
                ? prev.division_ids.filter(id => id !== divisionId)
                : [...prev.division_ids, divisionId];
            return { ...prev, division_ids: newIds };
        });
    };

    // Toggle tree node and load documents
    const toggleNode = async (classification: Classification) => {
        const newExpanded = new Set(expandedNodes);

        if (newExpanded.has(classification.id)) {
            newExpanded.delete(classification.id);
        } else {
            newExpanded.add(classification.id);

            // Load documents for this classification if not already loaded
            if (!documentsByClassification[classification.id]) {
                await loadDocumentsForClassification(classification.id);
            }
        }
        setExpandedNodes(newExpanded);
    };

    // Load documents for a specific classification
    const loadDocumentsForClassification = async (classificationId: number) => {
        setLoadingNodes(prev => new Set(prev).add(classificationId));

        try {
            const params = new URLSearchParams();
            params.append('classification_id', classificationId.toString());

            filters.category_ids.forEach(id => params.append('category_ids[]', id.toString()));
            filters.division_ids.forEach(id => params.append('division_ids[]', id.toString()));

            if (filters.user_name) params.append('user_name', filters.user_name);
            if (filters.search) params.append('search', filters.search);
            params.append('per_page', '100');

            const response = await fetch(`/archieve/documents/search/results?${params.toString()}`);
            const data = await response.json();

            setDocumentsByClassification(prev => ({
                ...prev,
                [classificationId]: data.data || []
            }));
        } catch (error) {
            console.error('Load documents error:', error);
        } finally {
            setLoadingNodes(prev => {
                const newSet = new Set(prev);
                newSet.delete(classificationId);
                return newSet;
            });
        }
    };

    // Apply filters - fetch filtered classifications
    const applyFilters = async () => {
        setIsFiltering(true);
        setExpandedNodes(new Set());
        setDocumentsByClassification({});

        try {
            const params = new URLSearchParams();
            filters.category_ids.forEach(id => params.append('category_ids[]', id.toString()));
            filters.division_ids.forEach(id => params.append('division_ids[]', id.toString()));

            if (filters.user_name) params.append('user_name', filters.user_name);
            if (filters.search) params.append('search', filters.search);

            const response = await fetch(`/archieve/documents/search/classifications?${params.toString()}`);
            const data = await response.json();
            setClassifications(data);
        } catch (error) {
            console.error('Filter error:', error);
        } finally {
            setIsFiltering(false);
        }
    };

    // Clear filters
    const clearFilters = () => {
        setFilters({ category_ids: [], division_ids: [], user_name: '', search: '' });
        setClassifications(initialClassifications);
        setExpandedNodes(new Set());
        setDocumentsByClassification({});
    };

    // Render document item
    const renderDocument = (doc: Document) => (
        <div
            key={doc.id}
            className="ml-8 flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-700/50"
        >
            <File className="size-4 flex-shrink-0 text-primary" />
            <div className="flex-1 min-w-0">
                <span className="font-medium text-slate-700 dark:text-slate-300 truncate block">
                    {doc.title}
                </span>
                <div className="flex items-center gap-2 mt-0.5 flex-wrap">
                    <span className="text-xs text-slate-400">{doc.file_size_label}</span>
                    {doc.categories?.slice(0, 3).map((cat) => (
                        <span key={cat.id} className="rounded bg-primary/10 px-1.5 py-0.5 text-xs text-primary">
                            {cat.name}
                        </span>
                    ))}
                    {doc.divisions?.slice(0, 2).map((div) => (
                        <span key={div.id} className="rounded bg-emerald-100 px-1.5 py-0.5 text-xs text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                            {div.name}
                        </span>
                    ))}
                </div>
            </div>
            <a
                href={`/storage/${doc.file_path}`}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-1 rounded bg-primary px-2 py-1 text-xs text-white hover:bg-primary/90"
            >
                <Download className="size-3" />
            </a>
        </div>
    );

    // Render classification tree node
    const renderTreeNode = (classification: Classification, level: number = 0) => {
        if (classification.total_documents_count === 0) return null;

        const hasChildren = classification.children && classification.children.length > 0;
        const isExpanded = expandedNodes.has(classification.id);
        const isLoading = loadingNodes.has(classification.id);
        const documents = documentsByClassification[classification.id] || [];

        return (
            <div key={classification.id} className="select-none">
                <div
                    className={`flex items-center gap-2 rounded-lg px-3 py-2.5 cursor-pointer transition-all hover:bg-slate-100 dark:hover:bg-slate-700/50 ${isExpanded ? 'bg-slate-50 dark:bg-slate-800/30' : ''
                        }`}
                    style={{ paddingLeft: `${level * 20 + 12}px` }}
                    onClick={() => toggleNode(classification)}
                >
                    {/* Expand/Collapse Icon */}
                    <div className="w-5 flex-shrink-0">
                        {isLoading ? (
                            <Loader2 className="size-4 animate-spin text-primary" />
                        ) : (
                            isExpanded ? (
                                <ChevronDown className="size-4 text-slate-400" />
                            ) : (
                                <ChevronRight className="size-4 text-slate-400" />
                            )
                        )}
                    </div>

                    {/* Folder Icon */}
                    {isExpanded ? (
                        <FolderOpen className="size-5 text-amber-500 flex-shrink-0" />
                    ) : (
                        <Folder className="size-5 text-amber-500 flex-shrink-0" />
                    )}

                    {/* Classification Info */}
                    <div className="flex-1 min-w-0">
                        <span className="font-mono text-xs text-slate-500 dark:text-slate-400">[{classification.code}]</span>
                        <span className="ml-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            {classification.name}
                        </span>
                    </div>

                    {/* Document Count Badge */}
                    {classification.total_documents_count > 0 && (
                        <span className="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-bold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                            {classification.total_documents_count}
                        </span>
                    )}
                </div>

                {/* Expanded Content */}
                {isExpanded && (
                    <div className="mt-1 space-y-1">
                        {/* Child Classifications */}
                        {hasChildren && classification.children!.map((child) => renderTreeNode(child, level + 1))}

                        {/* Documents */}
                        {classification.direct_documents_count > 0 && (
                            <div className="space-y-1 py-1" style={{ paddingLeft: `${level * 20 + 12}px` }}>
                                {documents.length > 0 ? (
                                    documents.map(renderDocument)
                                ) : isLoading ? (
                                    <div className="ml-8 py-2 text-xs text-slate-400 flex items-center gap-2">
                                        <Loader2 className="size-3 animate-spin" /> Memuat file...
                                    </div>
                                ) : null}
                            </div>
                        )}
                    </div>
                )}
            </div>
        );
    };

    return (
        <RootLayout title="Pencarian Dokumen">
            <div className="space-y-6">
                {/* Advanced Filters */}
                <ContentCard title="Filter Pencarian" mobileFullWidth>
                    <div className="space-y-6">
                        {/* Search & User */}
                        <div className={`grid gap-4 ${!showStaffFilter ? 'sm:grid-cols-1' : 'sm:grid-cols-2'}`}>
                            <FormInput
                                name="search"
                                label="Cari Judul Dokumen"
                                placeholder="Ketik judul dokumen..."
                                value={filters.search}
                                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                            />
                            {showStaffFilter && (
                                <FormInput
                                    name="user_name"
                                    label="Nama Pegawai (Terkait)"
                                    placeholder="Cari nama pegawai..."
                                    value={filters.user_name}
                                    onChange={(e) => setFilters({ ...filters, user_name: e.target.value })}
                                />
                            )}
                        </div>

                        {showDivisionFilter && (
                            /* Multi Division */
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Divisi Terkait</label>
                                <div className="flex flex-wrap gap-2">
                                    {divisions.map((division) => {
                                        const isSelected = filters.division_ids.includes(division.id);
                                        return (
                                            <button
                                                key={division.id}
                                                type="button"
                                                onClick={() => toggleDivision(division.id)}
                                                className={`flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs transition-all ${isSelected
                                                    ? 'border-emerald-500 bg-emerald-500 text-white shadow-sm'
                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-400 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'
                                                    }`}
                                            >
                                                {isSelected && <Check className="size-3" />}
                                                {division.name}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* Multi Category per Context */}
                        <div className="space-y-4">
                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">Kategori Arsip</label>
                            <div className="grid gap-4 sm:grid-cols-2">
                                {contexts.map((context) => (
                                    <div key={context.id} className="rounded-xl border border-slate-100 bg-slate-50/30 p-4 dark:border-slate-700 dark:bg-slate-800/20">
                                        <h4 className="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{context.name}</h4>
                                        <div className="flex flex-wrap gap-2">
                                            {context.categories.map((category) => {
                                                const isSelected = filters.category_ids.includes(category.id);
                                                return (
                                                    <button
                                                        key={category.id}
                                                        type="button"
                                                        onClick={() => toggleCategory(category.id)}
                                                        className={`flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs transition-all ${isSelected
                                                            ? 'border-primary bg-primary text-white shadow-sm'
                                                            : 'border-slate-200 bg-white text-slate-600 hover:border-primary/50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300'
                                                            }`}
                                                    >
                                                        {isSelected && <Check className="size-3" />}
                                                        {category.name}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex gap-3 pt-2 border-t border-slate-100 dark:border-slate-700">
                            <Button
                                type="button"
                                label="Terapkan Filter"
                                icon={<Search className="size-4" />}
                                onClick={applyFilters}
                                isLoading={isFiltering}
                            />
                            {hasActiveFilters && (
                                <Button
                                    type="button"
                                    label="Reset Filter"
                                    variant="secondary"
                                    icon={<X className="size-4" />}
                                    onClick={clearFilters}
                                />
                            )}
                        </div>
                    </div>
                </ContentCard>

                {/* Classification Tree with Documents */}
                <ContentCard
                    title={hasActiveFilters ? 'Hasil Filter Klasifikasi' : 'Klasifikasi Dokumen'}
                    mobileFullWidth
                >
                    {hasActiveFilters && (
                        <div className="mb-4 flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-2 text-sm font-medium text-primary">
                            <Filter className="size-4" />
                            <span>
                                Menampilkan klasifikasi yang memiliki dokumen sesuai filter
                            </span>
                        </div>
                    )}

                    {!hasActiveFilters && (
                        <p className="mb-4 text-sm text-slate-500">
                            Klik pada klasifikasi untuk melihat dokumen di dalamnya
                        </p>
                    )}

                    {isFiltering ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="size-8 animate-spin text-primary" />
                        </div>
                    ) : classifications.length === 0 ? (
                        <div className="py-12 text-center text-slate-500">
                            <Folder className="mx-auto size-12 text-slate-300" />
                            <p className="mt-4">
                                {hasActiveFilters
                                    ? 'Tidak ada dokumen yang cocok dengan filter'
                                    : 'Belum ada klasifikasi dokumen'
                                }
                            </p>
                        </div>
                    ) : (
                        <div className="divide-y divide-slate-100 dark:divide-slate-700/50 -mx-6">
                            <div className="px-3 py-2">
                                {classifications.map((classification) => renderTreeNode(classification))}
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>
        </RootLayout>
    );
}
