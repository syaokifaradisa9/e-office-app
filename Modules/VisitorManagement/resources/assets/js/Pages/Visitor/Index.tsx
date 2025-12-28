import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import DataTable from '@/components/tables/Datatable';
import { useEffect, useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { User, Check, X, Eye } from 'lucide-react';
import Button from '@/components/buttons/Button';
import CheckPermissions from '@/components/utils/CheckPermissions';
import FormSelect from '@/components/forms/FormSelect';
import Badge from '@/components/badges/Badge';
import Modal from '@/components/modals/Modal';
import FormInput from '@/components/forms/FormInput';
import FormTextArea from '@/components/forms/FormTextArea';
import { useForm } from '@inertiajs/react';
import toast from 'react-hot-toast';
import Tooltip from '@/components/commons/Tooltip';

interface Visitor {
    id: number;
    visitor_name: string;
    organization: string;
    phone_number: string;
    division: { name: string };
    purpose: { name: string };
    status: 'pending' | 'approved' | 'rejected' | 'completed';
    check_in_at: string;
    photo_url: string | null;
}

interface PaginationData {
    data: Visitor[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
    [key: string]: any;
}

interface Params {
    search: string;
    status: string;
    limit: number;
    page: number;
    sort_by: string;
    sort_direction: 'asc' | 'desc';
}

export default function VisitorIndex({ initialVisitors }: { initialVisitors: PaginationData }) {
    const [dataTable, setDataTable] = useState<PaginationData>(initialVisitors);
    const [params, setParams] = useState<Params>({
        search: '',
        status: '',
        limit: 20,
        page: 1,
        sort_by: 'created_at',
        sort_direction: 'desc',
    });

    const [isLoading, setIsLoading] = useState(true);
    const [confirmModal, setConfirmModal] = useState({ open: false, type: 'approved' as 'approved' | 'rejected', visitor: null as Visitor | null });
    const [adminNote, setAdminNote] = useState('');
    const [isInvitationModalOpen, setIsInvitationModalOpen] = useState(false);

    const { data, setData, post, processing, reset, errors } = useForm({
        visitor_name: '',
        phone_number: '',
        organization: '',
        division_id: '',
        purpose_id: '',
        purpose_detail: '',
        visitor_count: 1,
    });

    async function loadDatatable() {
        setIsLoading(true);
        let url = `/visitor/datatable`;
        const queryParams = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (value) queryParams.append(key, value.toString());
        });

        url += `?${queryParams.toString()}`;

        try {
            const response = await fetch(url);
            const data = await response.json();
            setDataTable(data);
        } catch (err) {
            console.error("Failed to load datatable", err);
        } finally {
            setIsLoading(false);
        }
    }

    const [isInitialMount, setIsInitialMount] = useState(true);

    useEffect(() => {
        if (isInitialMount) {
            setIsInitialMount(false);
            setIsLoading(false);
            return;
        }
        loadDatatable();
    }, [params]);

    const handleConfirmAction = () => {
        if (!confirmModal.visitor) return;

        router.post(`/visitor/${confirmModal.visitor.id}/confirm`, {
            status: confirmModal.type,
            admin_note: adminNote
        }, {
            onSuccess: () => {
                setConfirmModal({ open: false, type: 'approved', visitor: null });
                setAdminNote('');
                loadDatatable();
            }
        });
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'pending': return 'warning';
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            case 'completed': return 'primary';
            default: return 'secondary';
        }
    };

    return (
        <RootLayout title="Manajemen Pengunjung">
            <Modal
                show={confirmModal.open}
                onClose={() => setConfirmModal({ ...confirmModal, open: false })}
                title={confirmModal.type === 'approved' ? 'Setujui Kunjungan' : 'Tolak Kunjungan'}
                maxWidth="md"
            >
                <div className="space-y-4">
                    <div className="flex items-center gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                        <div className={`size-12 rounded-full flex items-center justify-center ${confirmModal.type === 'approved' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'}`}>
                            {confirmModal.type === 'approved' ? <Check className="size-6" /> : <X className="size-6" />}
                        </div>
                        <div>
                            <p className="font-semibold text-slate-900 dark:text-white">
                                {confirmModal.type === 'approved' ? 'Konfirmasi Persetujuan' : 'Konfirmasi Penolakan'}
                            </p>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Pengunjung: <span className="font-medium text-slate-700 dark:text-slate-200">{confirmModal.visitor?.visitor_name}</span>
                            </p>
                        </div>
                    </div>

                    <FormTextArea
                        label="Catatan / Deskripsi"
                        name="admin_note"
                        placeholder="Tambahkan alasan atau catatan konfirmasi..."
                        value={adminNote}
                        onChange={(e) => setAdminNote(e.target.value)}
                        rows={3}
                    />

                    <div className="flex justify-end gap-3 pt-4 border-t dark:border-slate-800">
                        <Button
                            type="button"
                            variant="ghost"
                            label="Batal"
                            onClick={() => setConfirmModal({ ...confirmModal, open: false })}
                        />
                        <Button
                            type="button"
                            variant={confirmModal.type === 'approved' ? 'primary' : 'danger'}
                            label={confirmModal.type === 'approved' ? 'Setujui Kunjungan' : 'Tolak Kunjungan'}
                            onClick={handleConfirmAction}
                        />
                    </div>
                </div>
            </Modal>

            <ContentCard
                title="Daftar Pengunjung"
                additionalButton={
                    <div className="flex gap-2">
                        <CheckPermissions permissions={['buat_undangan_tamu']}>
                            <Button
                                variant="primary"
                                label="Buat Undangan"
                                icon={<User className="size-4" />}
                                onClick={() => setIsInvitationModalOpen(true)}
                            />
                        </CheckPermissions>
                    </div>
                }
            >


                <DataTable
                    isLoading={isLoading}
                    dataTable={dataTable}
                    limit={params.limit}
                    searchValue={params.search}
                    onParamsChange={(e) => {
                        const { name, value } = e.target;
                        setParams({ ...params, [name]: value, page: 1 });
                    }}
                    onSearchChange={(e) => {
                        setParams({ ...params, search: e.target.value, page: 1 });
                    }}

                    onChangePage={(e) => {
                        e.preventDefault();
                        const page = new URL(e.currentTarget.href).searchParams.get('page');
                        if (page) setParams({ ...params, page: parseInt(page) });
                    }}
                    columns={[
                        {
                            header: 'Pengunjung',
                            render: (item: Visitor) => (
                                <div className="flex items-center gap-3">
                                    <div className="size-10 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden flex-shrink-0 border border-slate-200 dark:border-slate-700">
                                        {item.photo_url ? (
                                            <img src={item.photo_url} alt={item.visitor_name} className="w-full h-full object-cover" />
                                        ) : (
                                            <User className="w-full h-full p-2 text-slate-400" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="font-bold text-slate-900 dark:text-white leading-tight">{item.visitor_name}</p>
                                        <p className="text-xs text-slate-500 dark:text-slate-400 capitalize">{item.organization}</p>
                                    </div>
                                </div>
                            )
                        },
                        {
                            header: 'Tujuan',
                            render: (item: Visitor) => (
                                <div>
                                    <p className="text-sm font-medium text-slate-700 dark:text-slate-300">{item.division.name}</p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">{item.purpose?.name}</p>
                                </div>
                            )
                        },
                        {
                            header: 'Waktu Masuk',
                            render: (item: Visitor) => (
                                <p className="text-xs text-slate-600 dark:text-slate-400">
                                    {new Date(item.check_in_at).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })}
                                </p>
                            )
                        },
                        {
                            header: 'Status',
                            render: (item: Visitor) => (
                                <Badge color={getStatusBadgeVariant(item.status)}>
                                    {item.status.toUpperCase()}
                                </Badge>
                            )
                        },
                        {
                            header: 'Aksi',
                            bodyClassname: 'text-right',
                            render: (item: Visitor) => (
                                <div className="flex justify-end gap-1">
                                    <Tooltip text="Detail">
                                        <Button
                                            href={`/visitor/${item.id}`}
                                            className="!bg-transparent !p-1 text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50"
                                            icon={<Eye className="size-4" />}
                                        />
                                    </Tooltip>
                                    {item.status === 'pending' && (
                                        <CheckPermissions permissions={['konfirmasi_kunjungan']}>
                                            <Tooltip text="Setujui">
                                                <Button
                                                    className="!bg-transparent !p-1 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/20"
                                                    icon={<Check className="size-4" />}
                                                    onClick={() => setConfirmModal({ open: true, type: 'approved', visitor: item })}
                                                />
                                            </Tooltip>
                                            <Tooltip text="Tolak">
                                                <Button
                                                    className="!bg-transparent !p-1 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                                    icon={<X className="size-4" />}
                                                    onClick={() => setConfirmModal({ open: true, type: 'rejected', visitor: item })}
                                                />
                                            </Tooltip>
                                        </CheckPermissions>
                                    )}
                                </div>
                            )
                        }
                    ]}
                />
            </ContentCard>

            <Modal
                show={isInvitationModalOpen}
                onClose={() => setIsInvitationModalOpen(false)}
                title="Buat Undangan Tamu"
                maxWidth="lg"
            >
                <form onSubmit={(e) => {
                    e.preventDefault();
                    post('/visitor/store-invitation', {
                        onSuccess: () => {
                            setIsInvitationModalOpen(false);
                            reset();
                            toast.success('Undangan berhasil dibuat');
                            loadDatatable();
                        }
                    });
                }} className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormInput
                            label="Nama Pengunjung"
                            name="visitor_name"
                            value={data.visitor_name}
                            onChange={(e) => setData('visitor_name', e.target.value)}
                            error={errors.visitor_name}
                            required
                        />
                        <FormInput
                            label="Nomor Telepon"
                            name="phone_number"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            error={errors.phone_number}
                            required
                        />
                    </div>
                    <FormInput
                        label="Asal Instansi/Organisasi"
                        name="organization"
                        value={data.organization}
                        onChange={(e) => setData('organization', e.target.value)}
                        error={errors.organization}
                        required
                    />
                    <div className="grid grid-cols-1 gap-4">
                        <FormSelect
                            label="Divisi Tujuan"
                            name="division_id"
                            value={data.division_id}
                            options={(usePage().props.divisions as any[] || []).map(d => ({ label: d.name, value: d.id }))}
                            onChange={(e) => setData('division_id', e.target.value)}
                            error={errors.division_id}
                            required
                        />
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormSelect
                            label="Kategori Keperluan"
                            name="purpose_id"
                            value={data.purpose_id}
                            options={(usePage().props.purposes as any[] || []).map(p => ({ label: p.name, value: p.id }))}
                            onChange={(e) => setData('purpose_id', e.target.value)}
                            error={errors.purpose_id}
                            required
                        />
                        <FormInput
                            label="Jumlah Tamu"
                            name="visitor_count"
                            type="number"
                            value={data.visitor_count}
                            onChange={(e) => setData('visitor_count', parseInt(e.target.value))}
                            error={errors.visitor_count}
                            required
                        />
                    </div>
                    <FormTextArea
                        label="Detail Keperluan"
                        name="purpose_detail"
                        value={data.purpose_detail}
                        onChange={(e) => setData('purpose_detail', e.target.value)}
                        error={errors.purpose_detail}
                    />
                    <div className="flex justify-end gap-3 pt-4 border-t dark:border-slate-800">
                        <Button
                            type="button"
                            variant="ghost"
                            label="Batal"
                            onClick={() => setIsInvitationModalOpen(false)}
                        />
                        <Button
                            type="submit"
                            variant="primary"
                            label="Simpan Undangan"
                            isLoading={processing}
                        />
                    </div>
                </form>
            </Modal>
        </RootLayout>
    );
}
