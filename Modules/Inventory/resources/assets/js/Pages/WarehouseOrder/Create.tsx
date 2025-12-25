import { useForm } from '@inertiajs/react';
import { Minus, Plus, ShoppingCart, Search, Package, X } from 'lucide-react';
import React, { useState, useMemo, useRef, useEffect } from 'react';

import Badge from '@/components/badges/Badge';
import Button from '@/components/buttons/Button';
import FormTextArea from '@/components/forms/FormTextArea';
import RootLayout from '@/components/layouts/RootLayout';

interface Category {
    id: number;
    name: string;
}

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
    category_id: number;
    description: string;
    image_url?: string;
}

interface CartItem {
    item_id: number;
    quantity: number;
}

interface WarehouseOrder {
    id: number;
    description: string;
    notes: string;
    status: string;
    carts?: {
        item_id: number;
        quantity: number;
    }[];
    latest_reject?: {
        reason: string;
    };
}

interface Props {
    items: Item[];
    categories: Category[];
    warehouseOrder?: WarehouseOrder;
}

export default function WarehouseOrderCreate({ items, categories, warehouseOrder }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        description: warehouseOrder?.description || '',
        notes: warehouseOrder?.notes || '',
        items: warehouseOrder?.carts?.map(cart => ({
            item_id: cart.item_id,
            quantity: cart.quantity
        })) || [] as CartItem[],
    });

    const [selectedCategory, setSelectedCategory] = useState<number | 'ALL'>('ALL');
    const [searchQuery, setSearchQuery] = useState('');
    const [quantities, setQuantities] = useState<Record<number, number>>(() => {
        const initialQuantities: Record<number, number> = {};
        if (warehouseOrder?.carts) {
            warehouseOrder.carts.forEach(cart => {
                initialQuantities[cart.item_id] = cart.quantity;
            });
        }
        return initialQuantities;
    });

    const [isDarkMode, setIsDarkMode] = useState(false);
    const [failedImages, setFailedImages] = useState<Record<number, boolean>>({});
    const [loadedImages, setLoadedImages] = useState<Record<number, boolean>>({});

    useEffect(() => {
        const checkDarkMode = () => {
            setIsDarkMode(document.documentElement.classList.contains('dark'));
        };

        checkDarkMode();
        const observer = new MutationObserver(checkDarkMode);
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        return () => observer.disconnect();
    }, []);

    const getPlaceholderImage = () => {
        return isDarkMode ? '/images/no-photo-dark.png' : '/images/no-photo.png';
    };

    const filteredItems = useMemo(() => {
        return items.filter((item) => {
            const matchesCategory = selectedCategory === 'ALL' || item.category_id === selectedCategory;
            const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase());
            return matchesCategory && matchesSearch;
        });
    }, [items, selectedCategory, searchQuery]);

    const updateFormData = (currentQuantities: Record<number, number>) => {
        const newItems = Object.entries(currentQuantities)
            .filter(([_, qty]) => qty > 0)
            .map(([itemId, qty]) => ({
                item_id: parseInt(itemId),
                quantity: qty
            }));

        setData('items', newItems);
    };

    const handleQuantityChange = (itemId: number, change: number) => {
        setQuantities((prev) => {
            const currentQty = prev[itemId] || 0;
            const newQty = Math.max(0, currentQty + change);
            const item = items.find(i => i.id === itemId);

            if (item && newQty > item.stock) return prev;

            const newQuantities = { ...prev, [itemId]: newQty };
            updateFormData(newQuantities);
            return newQuantities;
        });
    };

    const handleManualQuantityChange = (itemId: number, value: string) => {
        const item = items.find(i => i.id === itemId);
        if (!item) return;

        let newQty = parseInt(value);
        if (isNaN(newQty)) newQty = 0;
        if (newQty < 0) newQty = 0;
        if (newQty > item.stock) newQty = item.stock;

        setQuantities((prev) => {
            const newQuantities = { ...prev, [itemId]: newQty };
            updateFormData(newQuantities);
            return newQuantities;
        });
    };

    const timerRef = useRef<NodeJS.Timeout | null>(null);
    const intervalRef = useRef<NodeJS.Timeout | null>(null);

    const startPress = (itemId: number, change: number) => {
        handleQuantityChange(itemId, change);
        timerRef.current = setTimeout(() => {
            intervalRef.current = setInterval(() => {
                handleQuantityChange(itemId, change);
            }, 100);
        }, 500);
    };

    const stopPress = () => {
        if (timerRef.current) clearTimeout(timerRef.current);
        if (intervalRef.current) clearInterval(intervalRef.current);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (warehouseOrder) {
            put(`/inventory/warehouse-orders/${warehouseOrder.id}/update`);
        } else {
            post('/inventory/warehouse-orders/store');
        }
    };

    return (
        <RootLayout
            title={warehouseOrder ? "Edit Permintaan Barang" : "Buat Permintaan Barang"}
            forceCollapse={true}
            backPath="/inventory/warehouse-orders"
        >
            <div className="flex h-[calc(100vh-8rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                {/* Left Side - Items Area */}
                <div className="relative flex h-full flex-1 flex-col gap-6 overflow-hidden pt-6">
                    {/* Header Section */}
                    <div className="flex flex-col gap-4 px-6">
                        {/* Search Section */}
                        <div className="group relative">
                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <Search className="size-5 text-gray-400 Transition-colors group-focus-within:text-blue-500" />
                            </div>
                            <input
                                type="text"
                                placeholder="Cari barang berdasarkan nama..."
                                className="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-12 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-white dark:placeholder-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>

                        {/* Category Tabs Section */}
                        <div className="flex overflow-x-auto [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:hover:bg-gray-300 [&::-webkit-scrollbar-track]:bg-transparent dark:[&::-webkit-scrollbar-thumb]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:hover:bg-slate-600">
                            <button
                                onClick={() => setSelectedCategory('ALL')}
                                className={`relative whitespace-nowrap border-b-2 px-6 py-3 text-sm font-medium transition-all ${selectedCategory === 'ALL'
                                    ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-slate-700 dark:hover:text-gray-200'
                                    }`}
                            >
                                Semua Kategori
                            </button>
                            {categories.map((category) => (
                                <button
                                    key={category.id}
                                    onClick={() => setSelectedCategory(category.id)}
                                    className={`relative whitespace-nowrap border-b-2 px-6 py-3 text-sm font-medium transition-all ${selectedCategory === category.id
                                        ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-slate-700 dark:hover:text-gray-200'
                                        }`}
                                >
                                    {category.name}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Items Grid - Full Width */}
                    <div className="flex-1 overflow-y-auto px-6 pb-6 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:hover:bg-gray-300 [&::-webkit-scrollbar-track]:bg-transparent dark:[&::-webkit-scrollbar-thumb]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:hover:bg-slate-600">
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                            {filteredItems.map((item) => (
                                <div
                                    key={item.id}
                                    className="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-300 hover:border-blue-200 hover:shadow-md dark:border-slate-700/50 dark:bg-slate-800 dark:hover:border-blue-500/50"
                                >
                                    <div className="relative aspect-[4/3] overflow-hidden bg-gray-50 dark:bg-slate-700">
                                        {(failedImages[item.id] || !item.image_url) ? (
                                            <div className="flex h-full w-full items-center justify-center bg-gray-100 dark:bg-slate-700">
                                                <span className="text-xs font-medium text-gray-400 dark:text-gray-500">Tidak ada Foto</span>
                                            </div>
                                        ) : (
                                            <>
                                                {!loadedImages[item.id] && (
                                                    <div className="absolute inset-0 z-10 flex animate-pulse items-center justify-center bg-gray-100 dark:bg-slate-800">
                                                        <Package className="size-8 text-gray-300 opacity-50 dark:text-slate-600" />
                                                    </div>
                                                )}
                                                <img
                                                    src={item.image_url}
                                                    alt={item.name}
                                                    className={`h-full w-full object-cover transition-all duration-500 group-hover:scale-105 ${!loadedImages[item.id] ? 'opacity-0' : 'opacity-100'}`}
                                                    onLoad={() => setLoadedImages(prev => ({ ...prev, [item.id]: true }))}
                                                    onError={() => setFailedImages(prev => ({ ...prev, [item.id]: true }))}
                                                />
                                            </>
                                        )}
                                        <div className="absolute right-3 top-3">
                                            <Badge
                                                color={item.stock > 0 ? 'success' : 'danger'}
                                                className="bg-opacity-90 shadow-sm backdrop-blur-sm"
                                            >
                                                {item.stock} {item.unit_of_measure}
                                            </Badge>
                                        </div>
                                    </div>

                                    <div className="flex flex-1 flex-col p-4">
                                        <div className="mb-2">
                                            <h3 className="mb-1 line-clamp-1 text-[15px] font-bold text-gray-800 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                                                {item.name}
                                            </h3>
                                            <p className="line-clamp-2 text-justify text-[13px] leading-relaxed text-gray-500 dark:text-gray-400">
                                                {item.description || "Tidak ada deskripsi tersedia."}
                                            </p>
                                        </div>

                                        <div className="mt-auto border-t border-gray-50 pt-2 dark:border-slate-700/50">
                                            <div className="flex items-center justify-center gap-3">
                                                <button
                                                    type="button"
                                                    onPointerDown={() => startPress(item.id, -1)}
                                                    onPointerUp={stopPress}
                                                    onPointerLeave={stopPress}
                                                    className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500 transition-all hover:border-red-200 hover:bg-red-50 hover:text-red-500 disabled:opacity-30 disabled:hover:border-gray-200 disabled:hover:bg-gray-50 disabled:hover:text-gray-500 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-400 dark:hover:border-red-800 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                                                    disabled={!quantities[item.id]}
                                                >
                                                    <Minus className="size-4" />
                                                </button>
                                                <div className="flex min-w-[60px] items-center justify-center">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={item.stock}
                                                        value={quantities[item.id] || 0}
                                                        onChange={(e) => handleManualQuantityChange(item.id, e.target.value)}
                                                        onFocus={(e) => e.target.select()}
                                                        className="w-[60px] border-none bg-transparent p-0 text-center text-[17px] font-bold text-gray-700 focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none dark:text-gray-200"
                                                    />
                                                </div>
                                                <button
                                                    type="button"
                                                    onPointerDown={() => startPress(item.id, 1)}
                                                    onPointerUp={stopPress}
                                                    onPointerLeave={stopPress}
                                                    className="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-500 transition-all hover:border-blue-200 hover:bg-blue-50 hover:text-blue-500 disabled:opacity-30 disabled:hover:border-gray-200 disabled:hover:bg-gray-50 disabled:hover:text-gray-500 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-400 dark:hover:border-blue-800 dark:hover:bg-blue-900/20 dark:hover:text-blue-400"
                                                    disabled={(quantities[item.id] || 0) >= item.stock}
                                                >
                                                    <Plus className="size-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}

                            {filteredItems.length === 0 && (
                                <div className="col-span-full flex flex-col items-center justify-center py-20 text-center">
                                    <div className="mb-4 rounded-full bg-gray-50 p-6 dark:bg-slate-800">
                                        <Search className="size-10 text-gray-300 dark:text-slate-600" />
                                    </div>
                                    <h3 className="mb-1 text-lg font-medium text-gray-900 dark:text-white">
                                        Tidak ada barang ditemukan
                                    </h3>
                                    <p className="mx-auto max-w-xs text-gray-500 dark:text-gray-400">
                                        Coba ubah kata kunci pencarian atau pilih kategori lain.
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Right Side - Fixed Cart */}
                <div className="relative z-30 flex h-full w-[400px] flex-col border-l border-gray-100 bg-white dark:border-slate-700/50 dark:bg-slate-800">
                    {/* Header */}
                    <div className="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800">
                        <div className="flex items-center gap-2">
                            <ShoppingCart className="size-4 text-gray-700 dark:text-gray-300" />
                            <h2 className="text-sm font-semibold text-gray-900 dark:text-white">Keranjang</h2>
                        </div>
                        <span className="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-slate-700 dark:text-gray-300">
                            {data.items.length}
                        </span>
                    </div>

                    {/* Cart Items */}
                    <div className="flex-1 overflow-y-auto bg-gray-50/30 px-4 py-2 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:hover:bg-gray-300 [&::-webkit-scrollbar-track]:bg-transparent dark:bg-slate-900/20 dark:[&::-webkit-scrollbar-thumb]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:hover:bg-slate-600">
                        {data.items.length === 0 ? (
                            <div className="flex h-full flex-col items-center justify-center text-center opacity-40">
                                <ShoppingCart className="mb-4 size-16 text-gray-300 dark:text-slate-600" />
                                <p className="font-medium text-gray-900 dark:text-white">Keranjang Kosong</p>
                                <p className="text-sm text-gray-500">Belum ada barang dipilih</p>
                            </div>
                        ) : (
                            data.items.map((cartItem) => {
                                const item = items.find(i => i.id === cartItem.item_id);
                                if (!item) return null;

                                return (
                                    <div key={cartItem.item_id} className="group flex gap-3 border-b border-gray-100 py-3 last:border-0 dark:border-slate-700/50">
                                        {/* Thumbnail */}
                                        <div className="size-14 shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-slate-700">
                                            {(failedImages[item.id] || !item.image_url) ? (
                                                <div className="flex h-full w-full items-center justify-center bg-gray-100 dark:bg-slate-700">
                                                    <span className="text-[10px] text-center font-medium text-gray-400 dark:text-gray-500 leading-tight">Tidak ada Foto</span>
                                                </div>
                                            ) : (
                                                <img
                                                    src={item.image_url}
                                                    alt={item.name}
                                                    className="h-full w-full object-cover"
                                                    onError={() => setFailedImages(prev => ({ ...prev, [item.id]: true }))}
                                                />
                                            )}
                                        </div>

                                        {/* Content */}
                                        <div className="flex min-w-0 flex-1 flex-col justify-between">
                                            <div className="flex items-start justify-between gap-2">
                                                <h3 className="truncate text-sm font-semibold leading-tight text-gray-900 dark:text-white" title={item.name}>
                                                    {item.name}
                                                </h3>
                                                <button
                                                    onClick={() => handleQuantityChange(item.id, -cartItem.quantity)}
                                                    className="-mr-1 -mt-1 p-1 text-gray-400 opacity-0 transition-colors hover:text-red-500 group-hover:opacity-100"
                                                >
                                                    <X className="size-4" />
                                                </button>
                                            </div>

                                            <div className="mt-1 flex items-center justify-between">
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {item.unit_of_measure}
                                                </p>

                                                {/* Quantity Controls */}
                                                <div className="flex items-center gap-0">
                                                    <button
                                                        onPointerDown={() => startPress(item.id, -1)}
                                                        onPointerUp={stopPress}
                                                        onPointerLeave={stopPress}
                                                        className="flex size-7 items-center justify-center rounded-full text-gray-500 transition-all hover:bg-gray-100 hover:text-red-600 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-slate-800 dark:hover:text-red-400"
                                                        disabled={cartItem.quantity <= 1}
                                                    >
                                                        <Minus className="size-3.5" />
                                                    </button>
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        max={item.stock}
                                                        value={cartItem.quantity}
                                                        onChange={(e) => handleManualQuantityChange(item.id, e.target.value)}
                                                        onFocus={(e) => e.target.select()}
                                                        className="w-8 border-none bg-transparent p-0 text-center text-sm font-bold text-gray-900 focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none dark:text-white"
                                                    />
                                                    <button
                                                        onPointerDown={() => startPress(item.id, 1)}
                                                        onPointerUp={stopPress}
                                                        onPointerLeave={stopPress}
                                                        className="flex size-7 items-center justify-center rounded-full text-gray-500 transition-all hover:bg-gray-100 hover:text-blue-600 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-slate-800 dark:hover:text-blue-400"
                                                        disabled={cartItem.quantity >= item.stock}
                                                    >
                                                        <Plus className="size-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>

                    {/* Footer Form */}
                    <div className="space-y-4 border-t border-gray-100 bg-white p-6 dark:border-slate-700/50 dark:bg-slate-800">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            {warehouseOrder?.latest_reject && (
                                <div className="rounded-lg border border-red-100 bg-red-50 p-3 dark:border-red-800/30 dark:bg-red-900/20">
                                    <p className="mb-1 text-xs font-semibold text-red-600 dark:text-red-400">Alasan Penolakan:</p>
                                    <p className="text-sm text-red-700 dark:text-red-300">{warehouseOrder.latest_reject.reason}</p>
                                </div>
                            )}
                            <div className="space-y-3">
                                <FormTextArea
                                    label="Deskripsi Penggunaan"
                                    name="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Contoh: Untuk keperluan maintenance mesin A..."
                                    error={errors.description}
                                    rows={2}
                                />

                                <FormTextArea
                                    label="Catatan Tambahan"
                                    name="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Opsional..."
                                    error={errors.notes}
                                    rows={2}
                                />
                            </div>

                            <Button
                                label={warehouseOrder ? "Simpan Perubahan" : "Ajukan Permintaan"}
                                type="submit"
                                isLoading={processing}
                                className="w-full justify-center py-2.5"
                                disabled={data.items.length === 0}
                                icon={<ShoppingCart className="size-4" />}
                            />
                        </form>
                    </div>
                </div>
            </div>
        </RootLayout>
    );
}
