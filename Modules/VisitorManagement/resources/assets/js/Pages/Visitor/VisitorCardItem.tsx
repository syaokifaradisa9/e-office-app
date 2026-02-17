import { Eye, Check, X, User, Clock, Building2, ClipboardList, Phone } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import Badge from '@/components/badges/Badge';
import CardItemButton from '@/components/buttons/CardItemButton';

interface Visitor {
    id: number;
    visitor_name: string;
    organization: string;
    phone_number: string;
    division: { name: string };
    purpose: { name: string };
    status: 'pending' | 'approved' | 'rejected' | 'completed' | 'invited' | 'cancelled';
    check_in_at: string;
    photo_url: string | null;
}

interface PageProps {
    permissions?: string[];
    [key: string]: unknown;
}

interface Props {
    item: Visitor;
    onDetail?: (item: Visitor) => void;
    onApprove: (item: Visitor) => void;
    onReject: (item: Visitor) => void;
}

export default function VisitorCardItem({ item, onApprove, onReject }: Props) {
    const { permissions } = usePage<PageProps>().props;
    const canConfirm = permissions?.includes('konfirmasi_kunjungan');
    const isPending = item.status === 'pending';

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'pending': return 'warning';
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'completed': return 'primary';
            case 'invited': return 'info';
            case 'cancelled': return 'secondary';
            default: return 'secondary';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'pending': return 'Menunggu';
            case 'approved': return 'Disetujui';
            case 'rejected': return 'Ditolak';
            case 'completed': return 'Selesai';
            case 'invited': return 'Diundang';
            case 'cancelled': return 'Dibatalkan';
            default: return status;
        }
    };

    const actionCount = [true, isPending && canConfirm, isPending && canConfirm].filter(Boolean).length;

    return (
        <div className="group transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-700/20">
            <div className="flex items-start gap-3.5 px-4 py-4">
                {/* Icon/Photo Container */}
                <div className="mt-0.5 flex size-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary dark:bg-primary/15 dark:text-primary overflow-hidden">
                    {item.photo_url ? (
                        <img src={item.photo_url} alt={item.visitor_name} className="w-full h-full object-cover" />
                    ) : (
                        <User className="size-5" />
                    )}
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Header: Name & Status */}
                    <div className="flex items-start justify-between gap-2">
                        <h3 className="line-clamp-1 text-[15px] font-semibold text-slate-800 dark:text-white">
                            {item.visitor_name}
                        </h3>
                        <Badge color={getStatusBadgeVariant(item.status)} className="shrink-0 scale-90 origin-top-right">
                            {getStatusLabel(item.status)}
                        </Badge>
                    </div>

                    {/* Metadata: Organization & Phone */}
                    <div className="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[12px] text-slate-500 dark:text-slate-400">
                        <div className="flex items-center gap-1">
                            <Building2 className="size-3.5" />
                            <span className="truncate max-w-[150px]">{item.organization || 'Pribadi'}</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <Phone className="size-3.5" />
                            <span>{item.phone_number}</span>
                        </div>
                    </div>

                    {/* Division & Purpose Tags */}
                    <div className="mt-2.5 flex flex-wrap gap-1.5">
                        <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                            {item.division.name}
                        </span>
                        <span className="inline-flex items-center rounded-md bg-primary/5 px-2 py-0.5 text-[11px] font-medium text-primary dark:bg-primary/10">
                            {item.purpose?.name}
                        </span>
                    </div>

                    {/* Time Info */}
                    <div className="mt-3 flex items-center gap-1.5 text-[12px] text-slate-500 dark:text-slate-400">
                        <Clock className="size-3.5 text-slate-400" />
                        <span>
                            {item.check_in_at
                                ? new Date(item.check_in_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
                                : 'Belum Check-in'}
                        </span>
                    </div>

                    {/* Actions */}
                    <div className={`mt-4 grid gap-2 grid-cols-${actionCount}`}>
                        <CardItemButton
                            href={`/visitor/${item.id}`}
                            label="Detail"
                            icon={<Eye />}
                            variant="info"
                        />

                        {isPending && canConfirm && (
                            <>
                                <CardItemButton
                                    onClick={() => onApprove(item)}
                                    label="Setujui"
                                    icon={<Check />}
                                    variant="success"
                                />
                                <CardItemButton
                                    onClick={() => onReject(item)}
                                    label="Tolak"
                                    icon={<X />}
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
