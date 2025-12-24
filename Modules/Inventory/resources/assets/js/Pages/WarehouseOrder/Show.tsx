import { usePage, router, useForm } from '@inertiajs/react';
import { Check, XCircle, Truck, PackageCheck, Edit, Trash2 } from 'lucide-react';
import { useState } from 'react';

import ConfirmationAlert from '@/components/alerts/ConfirmationAlert';
import Badge from '@/components/badges/Badge';
import Button from '@/components/buttons/Button';
import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import Modal from '@/components/modals/Modal';
import FormTextArea from '@/components/forms/FormTextArea';
import GeneralTable from '@/components/tables/GeneralTable';

interface CartItem {
    id: number;
    item: {
        id: number;
        name: string;
        unit_of_measure: string;
        category?: { id: number; name: string } | null;
    };
    quantity: number;
    delivered_quantity: number | null;
    received_quantity: number | null;
}

interface WarehouseOrder {
    id: number;
    order_number: string;
    description: string | null;
    notes: string | null;
    status: string;
    user_id: number;
    accepted_date: string | null;
    delivery_date: string | null;
    receipt_date: string | null;
    delivery_images: string[] | null;
    receipt_images: string[] | null;
    created_at: string;
    user: { id: number; name: string } | null;
    division: { id: number; name: string } | null;
    deliveredBy: { id: number; name: string } | null;
    receivedBy: { id: number; name: string } | null;
    carts: CartItem[];
    latest_reject: { description: string } | null;
}

interface Props {
    order: WarehouseOrder;
}

interface PageProps {
    permissions?: string[];
    loggeduser?: { id: number };
    [key: string]: unknown;
}

export default function WarehouseOrderShow({ order }: Props) {
    const { permissions, loggeduser } = usePage<PageProps>().props;
    const hasCreatePermission = permissions?.includes('buat_permintaan_barang');
    const hasConfirmPermission = permissions?.includes('konfirmasi_permintaan_barang');
    const hasHandoverPermission = permissions?.includes('serah_terima_barang');
    const hasReceivePermission = permissions?.includes('terima_barang');
    const isOwner = loggeduser?.id === order.user_id;

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Pending':
                return 'warning';
            case 'Confirmed':
                return 'info';
            case 'Delivered':
                return 'primary';
            case 'Finished':
                return 'success';
            case 'Rejected':
                return 'danger';
            case 'Revision':
                return 'warning';
            default:
                return 'secondary';
        }
    };

    // Status translations (English in DB, Indonesian for display)
    const statusLabels: Record<string, string> = {
        Pending: 'Menunggu',
        Confirmed: 'Dikonfirmasi',
        Delivered: 'Diserahkan',
        Finished: 'Selesai',
        Rejected: 'Ditolak',
        Revision: 'Revisi',
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const headers = [
        { label: 'Kategori', className: 'w-1/4' },
        { label: 'Nama Barang', className: 'w-1/3' },
        { label: 'Quantity', className: 'w-1/6 text-center' },
        { label: 'Satuan', className: 'w-1/6 text-center' },
    ];

    const columns = [
        {
            render: (item: CartItem) => item.item?.category?.name || '-',
            className: 'align-middle',
        },
        {
            render: (item: CartItem) => (
                <span className="font-medium text-gray-900 dark:text-white">{item.item?.name || 'Unknown Item'}</span>
            ),
            className: 'align-middle',
        },
        {
            render: (item: CartItem) => <div className="text-center font-bold text-gray-900 dark:text-white">{item.quantity}</div>,
            className: 'align-middle',
        },
        {
            render: (item: CartItem) => (
                <div className="text-center text-gray-500 dark:text-gray-400">{item.item?.unit_of_measure || '-'}</div>
            ),
            className: 'align-middle',
        },
    ];

    const [openConfirm, setOpenConfirm] = useState(false);
    const [openReject, setOpenReject] = useState(false);
    const [confirmType, setConfirmType] = useState<'confirm' | 'delete'>('confirm');

    const rejectForm = useForm({
        reason: '',
    });

    const handleConfirm = () => {
        router.patch(
            `/inventory/warehouse-orders/${order.id}/confirm`,
            {},
            {
                onSuccess: () => {
                    setOpenConfirm(false);
                },
            },
        );
    };

    const handleDelete = () => {
        router.delete(`/inventory/warehouse-orders/${order.id}/delete`, {
            onSuccess: () => {
                setOpenConfirm(false);
            },
        });
    };

    const handleReject = (e: React.FormEvent) => {
        e.preventDefault();
        rejectForm.post(`/inventory/warehouse-orders/${order.id}/reject`, {
            onSuccess: () => {
                setOpenReject(false);
                rejectForm.reset();
            },
        });
    };

    return (
        <RootLayout title="Detail Permintaan" backPath="/inventory/warehouse-orders">
            <ConfirmationAlert
                isOpen={openConfirm}
                setOpenModalStatus={setOpenConfirm}
                title={confirmType === 'delete' ? 'Hapus Permintaan' : 'Konfirmasi Permintaan'}
                message={
                    confirmType === 'delete'
                        ? 'Apakah Anda yakin ingin menghapus permintaan ini? Tindakan ini tidak dapat dibatalkan.'
                        : `Konfirmasi permintaan dari ${order.user?.name}? Status akan berubah menjadi Confirmed.`
                }
                confirmText={confirmType === 'delete' ? 'Ya, Hapus' : 'Ya, Konfirmasi'}
                cancelText="Batal"
                type={confirmType === 'delete' ? 'danger' : 'info'}
                onConfirm={() => {
                    if (confirmType === 'delete') {
                        handleDelete();
                    } else {
                        handleConfirm();
                    }
                }}
            />

            <Modal show={openReject} onClose={() => setOpenReject(false)} title="Tolak Permintaan" maxWidth="md">
                <form onSubmit={handleReject} className="space-y-4">
                    <FormTextArea
                        label="Alasan Penolakan"
                        name="rejection_note"
                        value={rejectForm.data.reason}
                        onChange={(e) => rejectForm.setData('reason', e.target.value)}
                        error={rejectForm.errors.reason}
                        placeholder="Masukkan alasan penolakan..."
                        required
                    />
                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="secondary" onClick={() => setOpenReject(false)} label="Batal" />
                        <Button type="submit" variant="danger" label="Tolak Permintaan" disabled={rejectForm.processing} />
                    </div>
                </form>
            </Modal>

            <ContentCard title="Detail Permintaan" backPath="/inventory/warehouse-orders">
                <div className="space-y-8">
                    {/* Data Order Section */}
                    <div>
                        <div className="space-y-2 text-sm">
                            <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                                <div className="font-medium text-gray-700 dark:text-gray-300">Pemohon</div>
                                <div className="text-gray-500 dark:text-gray-400">:</div>
                                <div className="font-medium text-gray-900 dark:text-white">{order.user?.name || '-'}</div>
                            </div>
                            <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                                <div className="font-medium text-gray-700 dark:text-gray-300">Divisi</div>
                                <div className="text-gray-500 dark:text-gray-400">:</div>
                                <div className="font-medium text-gray-900 dark:text-white">{order.division?.name || '-'}</div>
                            </div>
                            <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                                <div className="font-medium text-gray-700 dark:text-gray-300">Nomor Order</div>
                                <div className="text-gray-500 dark:text-gray-400">:</div>
                                <div className="font-medium text-gray-900 dark:text-white">{order.order_number || '-'}</div>
                            </div>
                            <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                                <div className="font-medium text-gray-700 dark:text-gray-300">Tanggal Permintaan</div>
                                <div className="text-gray-500 dark:text-gray-400">:</div>
                                <div className="font-medium text-gray-900 dark:text-white">{formatDate(order.created_at)}</div>
                            </div>
                            <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                                <div className="font-medium text-gray-700 dark:text-gray-300">Status</div>
                                <div className="text-gray-500 dark:text-gray-400">:</div>
                                <div className="font-medium text-gray-900 dark:text-white">
                                    <Badge color={getStatusColor(order.status)}>{statusLabels[order.status] || order.status}</Badge>
                                </div>
                            </div>
                            <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                                <div className="font-medium text-gray-700 dark:text-gray-300">Keperluan</div>
                                <div className="text-gray-500 dark:text-gray-400">:</div>
                                <div className="font-medium leading-relaxed text-gray-900 dark:text-white">
                                    {order.description || '-'}
                                </div>
                            </div>
                            {order.notes && (
                                <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                                    <div className="font-medium text-gray-700 dark:text-gray-300">Catatan</div>
                                    <div className="text-gray-500 dark:text-gray-400">:</div>
                                    <div className="font-medium leading-relaxed text-gray-900 dark:text-white">{order.notes}</div>
                                </div>
                            )}
                            {order.latest_reject && (
                                <div className="grid grid-cols-[140px_10px_1fr] items-start md:grid-cols-[200px_20px_1fr]">
                                    <div className="font-medium text-red-600 dark:text-red-400">Alasan Penolakan</div>
                                    <div className="text-gray-500 dark:text-gray-400">:</div>
                                    <div className="font-medium leading-relaxed text-red-600 dark:text-red-400">
                                        {order.latest_reject.description}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Items List */}
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                            Daftar Barang
                        </h3>
                        {/* Desktop Table */}
                        <div className="hidden overflow-hidden rounded-lg border border-gray-200 dark:border-slate-700 md:block">
                            <GeneralTable headers={headers} columns={columns} items={order.carts} />
                        </div>
                        {/* Mobile Card List */}
                        <div className="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-slate-700 dark:border-slate-700 md:hidden">
                            {order.carts && order.carts.length > 0 ? (
                                order.carts.map((cart, index) => (
                                    <div key={index} className="bg-white p-4 dark:bg-slate-800">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <div className="truncate font-medium text-gray-900 dark:text-white">
                                                    {cart.item?.name || 'Unknown Item'}
                                                </div>
                                                <div className="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                                    {cart.item?.category?.name || '-'}
                                                </div>
                                            </div>
                                            <div className="flex-shrink-0 text-right">
                                                <div className="font-bold text-gray-900 dark:text-white">{cart.quantity}</div>
                                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                                    {cart.item?.unit_of_measure || '-'}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="p-8 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada barang</div>
                            )}
                        </div>
                    </div>

                    {/* Delivery Proof */}
                    {order.delivery_images && order.delivery_images.length > 0 && (
                        <div>
                            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                                Bukti Penyerahan
                            </h3>
                            <div className="space-y-4">
                                {order.delivery_date && (
                                    <div className="text-sm">
                                        <span className="text-gray-500 dark:text-gray-400">Tanggal Penyerahan: </span>
                                        <span className="font-medium text-gray-900 dark:text-white">
                                            {formatDate(order.delivery_date)}
                                        </span>
                                    </div>
                                )}
                                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                                    {order.delivery_images.map((image, index) => (
                                        <div
                                            key={index}
                                            className="group relative aspect-square overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                                        >
                                            <img
                                                src={image}
                                                alt={`Bukti Penyerahan ${index + 1}`}
                                                className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            />
                                            <a
                                                href={image}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="absolute inset-0 flex items-center justify-center bg-black/0 transition-colors group-hover:bg-black/20"
                                            >
                                                <span className="sr-only">View Image</span>
                                            </a>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Receipt Proof */}
                    {order.receipt_images && order.receipt_images.length > 0 && (
                        <div>
                            <h3 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-900 dark:text-white">
                                Bukti Penerimaan
                            </h3>
                            <div className="space-y-4">
                                {order.receipt_date && (
                                    <div className="text-sm">
                                        <span className="text-gray-500 dark:text-gray-400">Tanggal Penerimaan: </span>
                                        <span className="font-medium text-gray-900 dark:text-white">
                                            {formatDate(order.receipt_date)}
                                        </span>
                                    </div>
                                )}
                                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                                    {order.receipt_images.map((image, index) => (
                                        <div
                                            key={index}
                                            className="group relative aspect-square overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                                        >
                                            <img
                                                src={image}
                                                alt={`Bukti Penerimaan ${index + 1}`}
                                                className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            />
                                            <a
                                                href={image}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="absolute inset-0 flex items-center justify-center bg-black/0 transition-colors group-hover:bg-black/20"
                                            >
                                                <span className="sr-only">View Image</span>
                                            </a>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Confirmation Button - Only for Pending or Revision Status (for approver) */}
                    {hasConfirmPermission && (order.status === 'Pending' || order.status === 'Revision') && (
                        <div className="flex gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                            <Button
                                onClick={() => setOpenReject(true)}
                                label="Tolak"
                                variant="danger"
                                className="w-full"
                                icon={<XCircle className="size-4" />}
                            />
                            <Button
                                onClick={() => {
                                    setConfirmType('confirm');
                                    setOpenConfirm(true);
                                }}
                                label="Terima"
                                variant="success"
                                className="w-full"
                                icon={<Check className="size-4" />}
                            />
                        </div>
                    )}

                    {/* Handover Button - Only for Confirmed Status */}
                    {hasHandoverPermission && order.status === 'Confirmed' && (
                        <div className="flex gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                            <Button
                                href={`/inventory/warehouse-orders/${order.id}/delivery`}
                                label="Serahkan Barang"
                                className="w-full"
                                icon={<Truck className="size-4" />}
                            />
                        </div>
                    )}

                    {/* Receive Button - Only for Delivered Status */}
                    {hasReceivePermission && order.status === 'Delivered' && (
                        <div className="flex gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                            <Button
                                href={`/inventory/warehouse-orders/${order.id}/receive`}
                                label="Terima Barang"
                                className="w-full"
                                icon={<PackageCheck className="size-4" />}
                            />
                        </div>
                    )}

                    {/* Edit & Delete - Only for Pending or Rejected Status (for creator) */}
                    {(order.status === 'Pending' || order.status === 'Rejected') && isOwner && hasCreatePermission && (
                        <div className="flex gap-3 border-t border-gray-200 pt-6 dark:border-slate-700">
                            <Button
                                onClick={() => {
                                    setConfirmType('delete');
                                    setOpenConfirm(true);
                                }}
                                label="Hapus Permintaan"
                                variant="danger"
                                className="w-full"
                                icon={<Trash2 className="size-4" />}
                            />
                            <Button
                                href={`/inventory/warehouse-orders/${order.id}/edit`}
                                label="Edit Permintaan"
                                className="w-full"
                                icon={<Edit className="size-4" />}
                            />
                        </div>
                    )}
                </div>
            </ContentCard>
        </RootLayout>
    );
}
