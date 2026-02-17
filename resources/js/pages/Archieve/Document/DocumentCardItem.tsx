import { FileText, Download, Calendar, User, Eye, Layers, Edit, Trash2 } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { ArchievePermission } from '@/enums/ArchievePermission';
import CardItemButton from '@/components/buttons/CardItemButton';

interface Category {
    id: number;
    name: string;
}

interface Division {
    id: number;
    name: string;
}

interface Classification {
    id: number;
    code: string;
    name: string;
}

interface Document {
    id: number;
    title: string;
    description: string | null;
    classification: Classification | null;
    categories: Category[];
    divisions: Division[];
    file_name: string;
    file_path: string;
    file_size_label: string;
    uploader?: { name: string };
    created_at: string;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface Props {
    item: Document;
    onDelete: (item: Document) => void;
}

export default function DocumentCardItem({ item, onDelete }: Props) {
    const { permissions } = usePage<PageProps>().props;
    const hasManagePermission = permissions?.includes(ArchievePermission.MANAGE_ALL)
        || permissions?.includes(ArchievePermission.MANAGE_DIVISION);

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-primary/15 dark:text-primary">
                    <FileText className="size-5" />
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Header: Title & Classification */}
                    <div className="flex flex-col">
                        <div className="flex items-start justify-between gap-1">
                            <h3 className="line-clamp-1 text-[15px] font-semibold text-slate-800 dark:text-white">
                                {item.title}
                            </h3>
                            <span className="flex-shrink-0 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                {item.file_size_label}
                            </span>
                        </div>
                        {item.classification && (
                            <span className="text-[12px] font-medium text-primary">
                                [{item.classification.code}] {item.classification.name}
                            </span>
                        )}
                    </div>

                    {/* Metadata */}
                    <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[12px] text-slate-500 dark:text-slate-400">
                        <div className="flex items-center gap-1">
                            <User className="size-3.5" />
                            <span className="truncate max-w-[100px]">{item.uploader?.name || 'Sistem'}</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <Calendar className="size-3.5" />
                            <span>{new Date(item.created_at).toLocaleDateString('id-ID')}</span>
                        </div>
                    </div>

                    {/* Categories Tags */}
                    {item.categories.length > 0 && (
                        <div className="mt-2.5 flex flex-wrap gap-1.5">
                            {item.categories.map((cat) => (
                                <span key={cat.id} className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    {cat.name}
                                </span>
                            ))}
                        </div>
                    )}

                    {/* Description */}
                    {item.description && (
                        <p className="mt-2 line-clamp-2 text-[13px] leading-relaxed text-slate-500 dark:text-slate-400">
                            {item.description}
                        </p>
                    )}

                    {/* Actions */}
                    <div className={`mt-4 grid gap-2 ${hasManagePermission ? 'grid-cols-3' : 'grid-cols-1'}`}>
                        <CardItemButton
                            href={`/storage/${item.file_name}`}
                            label="Unduh"
                            icon={<Download />}
                            variant="info"
                            isInertia={false}
                            target="_blank"
                            rel="noreferrer"
                        />
                        {hasManagePermission && (
                            <>
                                <CardItemButton
                                    href={`/archieve/documents/${item.id}/edit`}
                                    label="Edit"
                                    icon={<Edit />}
                                    variant="warning"
                                />
                                <CardItemButton
                                    onClick={() => onDelete(item)}
                                    label="Hapus"
                                    icon={<Trash2 />}
                                    variant="danger"
                                />
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
