import Button from '@/components/buttons/Button';
import FormInput from '@/components/forms/FormInput';
import FormSelect from '@/components/forms/FormSelect';
import FormTextArea from '@/components/forms/FormTextArea';
import { Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    Building2,
    Camera,
    Check,
    ClipboardList,
    Phone,
    RotateCcw,
    Search,
    User,
    Users,
} from 'lucide-react';
import React, { useEffect, useRef, useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

interface CheckInProps {
    divisions: Array<{ id: number; name: string }>;
    purposes: Array<{ id: number; name: string }>;
    visitor?: any;
    isEdit?: boolean;
}

export default function CheckIn({ divisions, purposes, visitor, isEdit = false }: CheckInProps) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const [currentStep, setCurrentStep] = useState(1);
    const [isCameraActive, setIsCameraActive] = useState(false);
    const [photo, setPhoto] = useState<string | null>(visitor?.photo_url || null);

    const { data, setData, post, processing, errors } = useForm({
        visitor_name: visitor?.visitor_name || '',
        phone_number: visitor?.phone_number || '',
        organization: visitor?.organization || '',
        division_id: visitor?.division_id?.toString() || '',
        purpose_id: visitor?.purpose_id?.toString() || '',
        purpose_detail: visitor?.purpose_detail || '',
        visitor_count: visitor?.visitor_count || 1,
        photo_url: visitor?.photo_url || '',
    });

    // Start camera when entering step 2
    useEffect(() => {
        if (currentStep === 2 && !photo) {
            startCamera();
        }
        return () => {
            if (currentStep !== 2) {
                stopCamera();
            }
        };
    }, [currentStep, photo]);

    // Cleanup camera on unmount
    useEffect(() => {
        return () => stopCamera();
    }, []);



    useEffect(() => {
        if (isCameraActive && videoRef.current && streamRef.current) {
            videoRef.current.srcObject = streamRef.current;
            videoRef.current.play().catch(console.error);
        }
    }, [isCameraActive]);

    const startCamera = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
            streamRef.current = stream;
            setIsCameraActive(true);
        } catch (err) {
            console.error('Error accessing camera: ', err);
        }
    };

    const stopCamera = () => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach((t) => t.stop());
            streamRef.current = null;
        }
        setIsCameraActive(false);
    };

    const takePhoto = () => {
        if (videoRef.current && canvasRef.current) {
            const ctx = canvasRef.current.getContext('2d');
            if (ctx) {
                const w = Math.min(videoRef.current.videoWidth, 800);
                const h = (videoRef.current.videoHeight / videoRef.current.videoWidth) * w;
                canvasRef.current.width = w;
                canvasRef.current.height = h;
                ctx.translate(w, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(videoRef.current, 0, 0, w, h);
                const img = canvasRef.current.toDataURL('image/jpeg', 0.75);
                setPhoto(img);
                setData('photo_url', img);
                stopCamera();
            }
        }
    };

    const resetPhoto = () => {
        setPhoto(null);
        setData('photo_url', '');
        startCamera();
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit && visitor?.id) {
            post(`/visitor/check-in/${visitor.id}`);
        } else {
            post('/visitor/check-in');
        }
    };

    const goToNextStep = () => {
        if (currentStep === 1) {
            setCurrentStep(2);
        }
    };

    const goToPrevStep = () => {
        if (currentStep === 2) {
            setCurrentStep(1);
        }
    };

    const isFormValid = () => {
        return (
            data.visitor_name.trim() !== '' &&
            data.phone_number.trim() !== '' &&
            data.division_id !== '' &&
            data.purpose_id !== ''
        );
    };

    return (
        <PublicLayout title={isEdit ? "Edit Data Kunjungan" : "Check-In Pengunjung"} fullWidth hideHeader>
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-slate-100 via-slate-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
                {/* Main Content */}
                <div className="flex flex-1 flex-col overflow-auto">
                    <form onSubmit={handleSubmit} className="flex flex-1 flex-col">
                        <div className="flex flex-1 items-start justify-center px-4 py-6 sm:items-center sm:px-6 sm:py-8 lg:px-8">
                            {/* Step 1: Form */}
                            {currentStep === 1 && (
                                <div className="w-full max-w-5xl animate-in fade-in slide-in-from-left-4 duration-300">
                                    {/* Step Indicator - Compact */}
                                    <div className="mb-6 flex items-center justify-center">
                                        <div className="flex items-center rounded-full bg-white/80 px-4 py-2 shadow-lg ring-1 ring-slate-200/50 backdrop-blur-sm dark:bg-slate-800/80 dark:ring-slate-700">
                                            {/* Step 1 - Active */}
                                            <div className="flex items-center gap-2">
                                                <div className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-md shadow-emerald-500/30">
                                                    <ClipboardList className="size-4" />
                                                </div>
                                                <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Isi Data</span>
                                            </div>

                                            {/* Connector */}
                                            <div className="mx-3 h-0.5 w-8 rounded-full bg-slate-200 dark:bg-slate-600" />

                                            {/* Step 2 - Inactive */}
                                            <div className="flex items-center gap-2">
                                                <div className="flex size-8 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-700">
                                                    <Camera className="size-4" />
                                                </div>
                                                <span className="text-sm font-medium text-slate-400">Ambil Foto</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-200/50 dark:bg-slate-900 dark:shadow-none dark:ring-slate-800">
                                        {/* Header */}
                                        <div className="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-800 dark:from-slate-900 dark:to-slate-800/50">
                                            <h2 className="text-xl font-semibold text-slate-900 dark:text-white">
                                                {isEdit ? 'Edit Data Kunjungan' : 'Formulir Check-In'}
                                            </h2>
                                            <p className="mt-1 text-sm text-slate-500">
                                                {isEdit ? 'Perbarui data kunjungan Anda' : 'Lengkapi data berikut untuk melakukan check-in'}
                                            </p>
                                        </div>

                                        {/* Form Content */}
                                        <div className="space-y-5 p-6">
                                            {/* Form Fields */}
                                            {/* Row 1: Nama, Telepon, Asal */}
                                            <div className="grid gap-4 sm:grid-cols-3">
                                                <FormInput
                                                    label="Nama Lengkap"
                                                    name="visitor_name"
                                                    value={data.visitor_name}
                                                    onChange={(e) => setData('visitor_name', e.target.value)}
                                                    error={errors.visitor_name}
                                                    required
                                                    icon={<User className="size-4" />}
                                                    placeholder="Nama sesuai KTP"
                                                />
                                                <FormInput
                                                    label="Nomor WhatsApp"
                                                    name="phone_number"
                                                    value={data.phone_number}
                                                    onChange={(e) => setData('phone_number', e.target.value)}
                                                    error={errors.phone_number}
                                                    required
                                                    prefix="+62"
                                                    placeholder="8xxxxxxx"
                                                />
                                                <FormInput
                                                    label="Instansi / Organisasi"
                                                    name="organization"
                                                    value={data.organization}
                                                    onChange={(e) => setData('organization', e.target.value)}
                                                    error={errors.organization}
                                                    icon={<Building2 className="size-4" />}
                                                    placeholder="Nama kantor"
                                                />
                                            </div>

                                            {/* Row 2: Tujuan, Keperluan, Jumlah Orang */}
                                            <div className="grid gap-4 sm:grid-cols-3">
                                                <FormSelect
                                                    label="Divisi Tujuan"
                                                    name="division_id"
                                                    value={data.division_id}
                                                    onChange={(e) => setData('division_id', e.target.value)}
                                                    error={errors.division_id}
                                                    required
                                                    options={divisions.map((d) => ({
                                                        value: d.id.toString(),
                                                        label: d.name,
                                                    }))}
                                                    placeholder="Pilih divisi..."
                                                />
                                                <FormSelect
                                                    label="Kategori Keperluan"
                                                    name="purpose_id"
                                                    value={data.purpose_id}
                                                    onChange={(e) => setData('purpose_id', e.target.value)}
                                                    error={errors.purpose_id}
                                                    required
                                                    options={purposes.map((p) => ({
                                                        value: p.id.toString(),
                                                        label: p.name,
                                                    }))}
                                                    placeholder="Pilih kategori..."
                                                />
                                                <FormInput
                                                    label="Jumlah Orang"
                                                    name="visitor_count"
                                                    type="number"
                                                    value={data.visitor_count.toString()}
                                                    onChange={(e) => setData('visitor_count', parseInt(e.target.value) || 1)}
                                                    error={errors.visitor_count}
                                                    required
                                                    icon={<Users className="size-4" />}
                                                />
                                            </div>

                                            {/* Row 3: Detail Keperluan (Full Width) */}
                                            <FormTextArea
                                                label="Detail Keperluan"
                                                name="purpose_detail"
                                                value={data.purpose_detail}
                                                onChange={(e) => setData('purpose_detail', e.target.value)}
                                                error={errors.purpose_detail}
                                                placeholder="Jelaskan secara singkat tujuan kunjungan Anda..."
                                                rows={3}
                                            />
                                        </div>

                                        {/* Footer */}
                                        <div className="border-t border-slate-100 bg-slate-50/50 px-6 py-4 dark:border-slate-800 dark:bg-slate-800/30">
                                            <Button
                                                type="button"
                                                onClick={goToNextStep}
                                                disabled={!isFormValid()}
                                                className="w-full"
                                                label="Lanjutkan"
                                                icon={<ArrowRight className="size-4" />}
                                            />
                                            {!isEdit && (
                                                <div className="mt-4 text-center">
                                                    <Link
                                                        href="/visitor/check-in/list"
                                                        className="text-sm text-slate-500 transition-colors hover:text-emerald-600"
                                                    >
                                                        <span className="block mb-1">Lihat Daftar Pengunjung atau Mau Edit Data?</span>
                                                        <span className="font-medium text-emerald-600 hover:underline">
                                                            Klik di sini
                                                        </span>
                                                    </Link>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Step 2: Photo */}
                            {currentStep === 2 && (
                                <div className="w-full max-w-5xl animate-in fade-in slide-in-from-right-4 duration-300">
                                    {/* Step Indicator - Compact */}
                                    <div className="mb-6 flex items-center justify-center">
                                        <div className="flex items-center rounded-full bg-white/80 px-4 py-2 shadow-lg ring-1 ring-slate-200/50 backdrop-blur-sm dark:bg-slate-800/80 dark:ring-slate-700">
                                            {/* Step 1 - Completed */}
                                            <div className="flex items-center gap-2">
                                                <div className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-md shadow-emerald-500/30">
                                                    <Check className="size-4" />
                                                </div>
                                                <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Isi Data</span>
                                            </div>

                                            {/* Connector - Filled */}
                                            <div className="mx-3 h-0.5 w-8 rounded-full bg-emerald-500" />

                                            {/* Step 2 - Active */}
                                            <div className="flex items-center gap-2">
                                                <div className="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-md shadow-emerald-500/30">
                                                    <Camera className="size-4" />
                                                </div>
                                                <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Ambil Foto</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-200/50 dark:bg-slate-900 dark:shadow-none dark:ring-slate-800">
                                        {/* Header */}
                                        <div className="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-800 dark:from-slate-900 dark:to-slate-800/50">
                                            <h2 className="text-xl font-semibold text-slate-900 dark:text-white">
                                                {isEdit ? 'Update Foto' : 'Foto Pengunjung'}
                                            </h2>
                                            <p className="mt-1 text-sm text-slate-500">
                                                {isEdit ? 'Ambil ulang foto jika diperlukan' : 'Ambil foto wajah Anda dengan jelas'}
                                            </p>
                                        </div>

                                        {/* Camera Content */}
                                        <div className="p-4 sm:p-6">
                                            <div className="relative aspect-video w-full overflow-hidden rounded-xl bg-slate-900">
                                                {photo ? (
                                                    <div className="group relative h-full w-full">
                                                        <img src={photo.startsWith('data:') ? photo : `${photo}`} alt="Foto" className="h-full w-full object-cover" />
                                                        <div className="absolute inset-0 flex items-center justify-center bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 transition-all duration-300 group-hover:opacity-100">
                                                            <button
                                                                type="button"
                                                                onClick={resetPhoto}
                                                                className="flex items-center gap-2 rounded-full bg-white/90 px-5 py-2.5 text-sm font-medium text-slate-900 shadow-xl backdrop-blur-sm transition-all hover:scale-105 hover:bg-white"
                                                            >
                                                                <RotateCcw className="size-4" /> Ambil Ulang
                                                            </button>
                                                        </div>
                                                        <div className="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-1.5 rounded-full bg-emerald-500 px-4 py-2 text-sm font-medium text-white shadow-lg">
                                                            <Check className="size-4" /> Foto Tersimpan
                                                        </div>
                                                    </div>
                                                ) : isCameraActive ? (
                                                    <div className="relative h-full w-full">
                                                        <video
                                                            ref={videoRef}
                                                            autoPlay
                                                            playsInline
                                                            muted
                                                            className="h-full w-full scale-x-[-1] object-cover"
                                                        />
                                                        {/* Gradient overlay */}
                                                        <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent" />
                                                        {/* Face guide */}
                                                        <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                                                            <div className="relative">
                                                                <div className="size-32 rounded-full border-2 border-white/50 shadow-[0_0_0_4px_rgba(0,0,0,0.2)] sm:size-40" />
                                                                <div className="absolute -bottom-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-black/50 px-4 py-1.5 text-sm text-white/80 backdrop-blur-sm">
                                                                    Posisikan wajah di sini
                                                                </div>
                                                            </div>
                                                        </div>
                                                        {/* Capture button */}
                                                        <button
                                                            type="button"
                                                            onClick={takePhoto}
                                                            className="absolute bottom-6 left-1/2 flex size-16 -translate-x-1/2 items-center justify-center rounded-full border-4 border-white bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-[0_4px_20px_rgba(16,185,129,0.4)] transition-all duration-200 hover:scale-110 hover:shadow-[0_6px_25px_rgba(16,185,129,0.5)] active:scale-95 sm:size-20"
                                                        >
                                                            <div className="size-12 rounded-full bg-white/20 sm:size-14" />
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <div className="flex h-full flex-col items-center justify-center gap-4 bg-gradient-to-br from-slate-800 to-slate-900 py-16">
                                                        <div className="relative">
                                                            <div className="rounded-full bg-slate-700/50 p-6">
                                                                <Camera className="size-12 text-slate-400" />
                                                            </div>
                                                            <div className="absolute -right-1 -top-1 flex size-6 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white">
                                                                !
                                                            </div>
                                                        </div>
                                                        <div className="text-center">
                                                            <p className="text-base font-medium text-slate-300">Kamera belum aktif</p>
                                                            <p className="text-sm text-slate-500">Klik tombol di bawah untuk memulai</p>
                                                        </div>
                                                        <button
                                                            type="button"
                                                            onClick={startCamera}
                                                            className="rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-2.5 text-sm font-medium text-white shadow-lg shadow-emerald-500/25 transition-all hover:scale-105 hover:shadow-emerald-500/40"
                                                        >
                                                            Aktifkan Kamera
                                                        </button>
                                                    </div>
                                                )}
                                                <canvas ref={canvasRef} className="hidden" />
                                            </div>
                                            {errors.photo_url && (
                                                <p className="mt-3 text-center text-sm text-rose-500">{errors.photo_url}</p>
                                            )}
                                        </div>

                                        {/* Footer */}
                                        <div className="flex items-center justify-between gap-4 border-t border-slate-100 bg-slate-50/50 px-6 py-4 dark:border-slate-800 dark:bg-slate-800/30">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={goToPrevStep}
                                                label="Kembali"
                                                icon={<ArrowLeft className="size-4" />}
                                            />
                                            <Button
                                                type="submit"
                                                disabled={processing || !photo}
                                                isLoading={processing}
                                                className="flex-1 sm:flex-none"
                                                label={isEdit ? "Simpan Perubahan" : "Check-In Sekarang"}
                                                icon={<Check className="size-4" />}
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </form>
                </div>
            </div>
        </PublicLayout>
    );
}
