import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Ban, Clock, DoorOpen, Search, User, Building2, Calendar, Users, MapPin, Edit, LogOut } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';
import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';

interface Division {
    id: number;
    name: string;
}

interface PurposeCategory {
    id: number;
    name: string;
}

interface Visitor {
    id: number;
    visitor_name: string;
    phone_number: string;
    organization: string;
    status: 'pending' | 'approved' | 'rejected' | 'completed' | 'invited';
    check_in_at: string | null;
    photo_url: string | null;
    division?: Division;
    purpose?: PurposeCategory;
}

interface VisitorListProps {
    visitors: Visitor[];
}

export default function VisitorList({ visitors }: VisitorListProps) {
    const [searchQuery, setSearchQuery] = useState('');
    const [filteredVisitors, setFilteredVisitors] = useState<Visitor[]>(visitors);
    const [cancellingId, setCancellingId] = useState<number | null>(null);
    const [openConfirmCancel, setOpenConfirmCancel] = useState(false);
    const [selectedVisitor, setSelectedVisitor] = useState<Visitor | null>(null);

    useEffect(() => {
        if (searchQuery.trim() === '') {
            setFilteredVisitors(visitors);
        } else {
            const lowerQuery = searchQuery.toLowerCase();
            const filtered = visitors.filter(visitor =>
                visitor.visitor_name.toLowerCase().includes(lowerQuery) ||
                (visitor.organization && visitor.organization.toLowerCase().includes(lowerQuery)) ||
                visitor.phone_number.includes(lowerQuery)
            );
            setFilteredVisitors(filtered);
        }
    }, [searchQuery, visitors]);

    const handleEditOrCheckIn = (visitor: Visitor) => {
        if (visitor.status === 'pending') {
            router.get(`/visitor/check-in/${visitor.id}`);
        } else if (visitor.status === 'invited') {
            router.get(`/visitor/check-in?visitor_id=${visitor.id}`);
        }
    };

    const handleCheckout = (visitorId: number) => {
        router.get(`/visitor/check-out/${visitorId}`);
    };

    const handleCancelVisit = (visitor: Visitor) => {
        setSelectedVisitor(visitor);
        setOpenConfirmCancel(true);
    };

    const confirmCancelVisit = () => {
        if (!selectedVisitor) return;

        setCancellingId(selectedVisitor.id);
        router.post(`/visitor/check-out/${selectedVisitor.id}/cancel`, {}, {
            onFinish: () => {
                setCancellingId(null);
                setOpenConfirmCancel(false);
                setSelectedVisitor(null);
            }
        });
    };

    const formatTime = (dateString: string | null) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'invited':
                return (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                        <Calendar className="size-3" />
                        Diundang
                    </span>
                );
            case 'pending':
                return (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        <Clock className="size-3" />
                        Menunggu
                    </span>
                );
            case 'approved':
                return (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <DoorOpen className="size-3" />
                        Berkunjung
                    </span>
                );
            default:
                return null;
        }
    };

    const VisitorCard = ({ visitor }: { visitor: Visitor }) => (
        <div
            className="group flex flex-col gap-4 p-5 transition-all duration-200 hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:flex-row sm:items-center sm:justify-between"
        >
            <div className="flex items-start gap-4">
                <div className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-600 shadow-sm ring-1 ring-slate-200/50 transition-transform group-hover:scale-105 dark:from-slate-800 dark:to-slate-700 dark:text-slate-400 dark:ring-slate-700">
                    {visitor.photo_url ? (
                        <img src={visitor.photo_url} alt={visitor.visitor_name} className="h-full w-full object-cover" />
                    ) : (
                        <User className="size-6" />
                    )}
                </div>
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-3">
                        <h3 className="font-bold text-slate-900 dark:text-white">
                            {visitor.visitor_name}
                        </h3>
                        {getStatusBadge(visitor.status)}
                    </div>
                    <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                        {visitor.organization && (
                            <span className="flex items-center gap-1.5">
                                <Building2 className="size-3.5 text-slate-400" />
                                {visitor.organization}
                            </span>
                        )}
                        {visitor.division && (
                            <span className="flex items-center gap-1.5">
                                <MapPin className="size-3.5 text-slate-400" />
                                {visitor.division.name}
                            </span>
                        )}
                        {visitor.check_in_at && (
                            <span className="flex items-center gap-1.5">
                                <Clock className="size-3.5 text-slate-400" />
                                Check-in: {formatTime(visitor.check_in_at)}
                            </span>
                        )}
                    </div>
                </div>
            </div>
            <div className="flex shrink-0 gap-2 pl-16 sm:pl-0">
                {visitor.status === 'invited' && (
                    <button
                        onClick={() => handleEditOrCheckIn(visitor)}
                        className="flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all hover:scale-[1.02] hover:shadow-blue-500/40 active:scale-[0.98]"
                    >
                        <Calendar className="size-4" />
                        Lanjut Check-In
                    </button>
                )}
                {visitor.status === 'pending' && (
                    <div className="flex gap-2">
                        <button
                            onClick={() => handleEditOrCheckIn(visitor)}
                            className="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:shadow dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            <Edit className="size-4" />
                            Edit Data
                        </button>
                        <button
                            onClick={() => handleCancelVisit(visitor)}
                            disabled={cancellingId === visitor.id}
                            className="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition-all hover:bg-rose-100 disabled:opacity-50 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400"
                        >
                            <Ban className="size-4" />
                            Batal
                        </button>
                    </div>
                )}
                {visitor.status === 'approved' && (
                    <button
                        onClick={() => handleCheckout(visitor.id)}
                        className="flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition-all hover:scale-[1.02] hover:shadow-emerald-500/40 active:scale-[0.98]"
                    >
                        <LogOut className="size-4" />
                        Check-Out
                    </button>
                )}
            </div>
        </div>
    );

    return (
        <PublicLayout title="Daftar Pengunjung" fullWidth hideHeader>
            <ConfirmationAlert
                isOpen={openConfirmCancel}
                setOpenModalStatus={setOpenConfirmCancel}
                title="Batalkan Kunjungan"
                message={`Apakah Anda yakin ingin membatalkan kunjungan atas nama ${selectedVisitor?.visitor_name}? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Ya, Batalkan"
                cancelText="Tidak, Kembali"
                type="danger"
                onConfirm={confirmCancelVisit}
            />
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-slate-100 via-slate-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
                {/* Main Content */}
                <div className="flex flex-1 items-start justify-center px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                    <div className="w-full max-w-5xl animate-in fade-in zoom-in duration-300">
                        {/* Navigation */}
                        <div className="mb-6 flex items-center justify-between">
                            <Link
                                href="/visitor/check-in"
                                className="flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 text-sm font-medium text-slate-600 shadow-lg ring-1 ring-slate-200/50 backdrop-blur-sm transition-all hover:text-slate-900 dark:bg-slate-800/80 dark:text-slate-400 dark:ring-slate-700 dark:hover:text-white"
                            >
                                <ArrowLeft className="size-4" />
                                <span>Kembali ke Check-In</span>
                            </Link>

                            <div className="flex items-center gap-3 rounded-full bg-white/80 px-4 py-2 shadow-lg ring-1 ring-slate-200/50 backdrop-blur-sm dark:bg-slate-800/80 dark:ring-slate-700">
                                <div className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-400 to-purple-600 text-white shadow-md shadow-indigo-500/30">
                                    <Users className="size-4" />
                                </div>
                                <span className="text-sm font-semibold text-slate-700 dark:text-slate-300">Daftar Pengunjung & Check-Out</span>
                            </div>
                        </div>

                        {/* Main Card */}
                        <div className="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-200/50 dark:bg-slate-900 dark:shadow-none dark:ring-slate-800">
                            {/* Header */}
                            <div className="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-800 dark:from-slate-900 dark:to-slate-800/50">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h2 className="text-xl font-bold text-slate-900 dark:text-white">
                                            Daftar Pengunjung Aktif
                                        </h2>
                                        <p className="mt-1 text-sm text-slate-500">
                                            Cari nama Anda untuk Check-Out, Edit Data, atau Melanjutkan Undangan
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 dark:bg-slate-800">
                                        <Users className="size-4 text-slate-500" />
                                        <span className="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                            {filteredVisitors.length} Pengunjung
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Search Input */}
                            <div className="border-b border-slate-100 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                                <div className="relative">
                                    <Search className="absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                                    <input
                                        type="text"
                                        className="w-full rounded-xl border border-slate-200 bg-white py-3.5 pl-12 pr-4 text-base text-slate-900 outline-none transition-all placeholder:text-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        placeholder="Cari nama atau nomor telepon..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                    />
                                </div>
                            </div>

                            {/* Results List */}
                            {filteredVisitors.length > 0 ? (
                                <div className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {filteredVisitors.map((visitor) => (
                                        <VisitorCard key={visitor.id} visitor={visitor} />
                                    ))}
                                </div>
                            ) : (
                                <div className="p-12 text-center">
                                    <div className="mx-auto flex size-20 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                        <Search className="size-10 text-slate-400" />
                                    </div>
                                    <h3 className="mt-5 text-lg font-bold text-slate-900 dark:text-white">
                                        Tidak Ditemukan
                                    </h3>
                                    <p className="mt-2 text-sm text-slate-500">
                                        {searchQuery.trim() !== ''
                                            ? 'Tidak ada data kunjungan aktif dengan kata kunci tersebut.'
                                            : 'Belum ada pengunjung yang melakukan check-in hari ini.'}
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Footer text */}
                        <div className="mt-6 text-center">
                            <p className="text-xs font-medium text-slate-400">
                                &copy; {new Date().getFullYear()} E-Office Digital Visitor System
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
