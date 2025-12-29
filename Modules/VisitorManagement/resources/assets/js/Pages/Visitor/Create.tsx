import ContentCard from '@/components/layouts/ContentCard';
import RootLayout from '@/components/layouts/RootLayout';
import { usePage, useForm, Link } from '@inertiajs/react';
import { UserPlus, ArrowLeft, Save } from 'lucide-react';
import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import FormTextArea from '@/components/forms/FormTextArea';
import toast from 'react-hot-toast';

export default function VisitorInvitation() {
    const { divisions, purposes } = usePage<any>().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        visitor_name: '',
        phone_number: '',
        organization: '',
        division_id: '',
        purpose_id: '',
        purpose_detail: '',
        visitor_count: 1,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/visitor/store-invitation', {
            onSuccess: () => {
                reset();
                toast.success('Undangan berhasil dikirim');
            }
        });
    };

    return (
        <RootLayout title="Buat Undangan Pengunjung">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2">
                    <ContentCard
                        title="Buat Undangan Tamu"
                        subtitle="Pre-registrasi data tamu untuk mempercepat proses check-in"
                        backPath="/visitor"
                    >
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <FormInput
                                    label="Nama Pengunjung"
                                    name="visitor_name"
                                    value={data.visitor_name}
                                    onChange={(e) => setData('visitor_name', e.target.value)}
                                    error={errors.visitor_name}
                                    placeholder="Contoh: Budi Santoso"
                                    required
                                />
                                <FormInput
                                    label="Nomor Telepon / WhatsApp"
                                    name="phone_number"
                                    value={data.phone_number}
                                    onChange={(e) => setData('phone_number', e.target.value)}
                                    error={errors.phone_number}
                                    placeholder="8123456789"
                                    prefix="+62"
                                    required
                                />
                            </div>

                            <FormInput
                                label="Asal Instansi / Organisasi"
                                name="organization"
                                value={data.organization}
                                onChange={(e) => setData('organization', e.target.value)}
                                error={errors.organization}
                                placeholder="Contoh: PT. Maju Bersama"
                                required
                            />

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <FormSelect
                                    label="Divisi Tujuan"
                                    name="division_id"
                                    value={data.division_id}
                                    options={(divisions || []).map((d: any) => ({ label: d.name, value: d.id }))}
                                    onChange={(e) => setData('division_id', e.target.value)}
                                    error={errors.division_id}
                                    required
                                />
                                <FormSelect
                                    label="Kategori Keperluan"
                                    name="purpose_id"
                                    value={data.purpose_id}
                                    options={(purposes || []).map((p: any) => ({ label: p.name, value: p.id }))}
                                    onChange={(e) => setData('purpose_id', e.target.value)}
                                    error={errors.purpose_id}
                                    required
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <FormInput
                                    label="Jumlah Tamu"
                                    name="visitor_count"
                                    type="number"
                                    min="1"
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
                                placeholder="Tuliskan alasan kunjungan secara spesifik..."
                                rows={4}
                            />

                            <div className="flex justify-end gap-3 pt-4 border-t dark:border-slate-800">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    label="Batal"
                                    href="/visitor"
                                />
                                <Button
                                    type="submit"
                                    variant="primary"
                                    label="Simpan & Buat Undangan"
                                    icon={<Save className="size-4" />}
                                    isLoading={processing}
                                />
                            </div>
                        </form>
                    </ContentCard>
                </div>

                <div className="space-y-6">
                    <ContentCard title="Informasi Undangan">
                        <div className="space-y-4">
                            <div className="rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20">
                                <h4 className="flex items-center gap-2 text-sm font-bold text-blue-900 dark:text-blue-300 mb-2">
                                    <UserPlus className="size-4" />
                                    Apa itu Undangan?
                                </h4>
                                <p className="text-xs leading-relaxed text-blue-700 dark:text-blue-400">
                                    Fitur undangan memungkinkan Anda mendaftarkan data tamu terlebih dahulu.
                                    Tamu tersebut tidak perlu mengisi formulir dari nol saat tiba di kantor.
                                </p>
                            </div>

                            <div className="space-y-3">
                                <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Langkah Selanjutnya:</p>
                                <ul className="space-y-2">
                                    <li className="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400">
                                        <div className="flex size-4 mt-0.5 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold dark:bg-slate-700">1</div>
                                        <span>Data tamu akan tersimpan dengan status <b>"Diundang"</b>.</span>
                                    </li>
                                    <li className="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400">
                                        <div className="flex size-4 mt-0.5 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold dark:bg-slate-700">2</div>
                                        <span>Tamu dapat mencari nama/no. telp di Kiosk pendaftaran.</span>
                                    </li>
                                    <li className="flex items-start gap-2 text-xs text-slate-600 dark:text-slate-400">
                                        <div className="flex size-4 mt-0.5 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold dark:bg-slate-700">3</div>
                                        <span>Tamu tinggal melengkapi foto dan konfirmasi kedatangan.</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </ContentCard>
                </div>
            </div>
        </RootLayout>
    );
}
